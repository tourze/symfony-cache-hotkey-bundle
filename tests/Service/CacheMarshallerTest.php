<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;
use Symfony\Contracts\Service\ResetInterface;
use Tourze\Symfony\CacheHotKey\Service\CacheMarshaller;

interface ResettableMarshallerMock extends MarshallerInterface, ResetInterface
{
}

class CacheMarshallerTest extends TestCase
{
    /** @var MarshallerInterface&MockObject */
    private $innerMock;

    /** @var LoggerInterface&MockObject */
    private $loggerMock;

    private CacheMarshaller $marshaller;

    protected function setUp(): void
    {
        $this->innerMock = $this->createMock(MarshallerInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->marshaller = new CacheMarshaller($this->innerMock, $this->loggerMock);
    }

    public function testMarshall_withSmallData_noWarning(): void
    {
        $values = ['key1' => 'small_value', 'key2' => 'another_small_value'];
        $failed = [];
        $expectedResult = ['key1' => 'marshalled_small', 'key2' => 'marshalled_small2'];

        $this->innerMock
            ->expects($this->once())
            ->method('marshall')
            ->with($values, $this->anything())
            ->willReturn($expectedResult);

        // 不应该记录警告
        $this->loggerMock
            ->expects($this->never())
            ->method('warning');

        $result = $this->marshaller->marshall($values, $failed);

        $this->assertSame($expectedResult, $result);
    }

    public function testMarshall_withLargeData_logsWarning(): void
    {
        $largeValue = str_repeat('x', 2000000); // 2MB 数据
        $values = ['large_key' => $largeValue];
        $failed = [];
        $expectedResult = ['large_key' => $largeValue];

        $this->innerMock
            ->expects($this->once())
            ->method('marshall')
            ->with($values, $this->anything())
            ->willReturn($expectedResult);

        // 应该记录警告
        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                '发现一个数据比较大的缓存数据，请考虑拆分缓存',
                $this->callback(function ($context) use ($largeValue) {
                    return $context['key'] === 'large_key' &&
                           $context['size'] === mb_strlen($largeValue) &&
                           isset($context['demo']) &&
                           mb_strlen($context['demo']) <= 400;
                })
            );

        $result = $this->marshaller->marshall($values, $failed);

        $this->assertSame($expectedResult, $result);
    }

    public function testMarshall_withCustomWarningSize_usesCustomSize(): void
    {
        // 设置自定义警告大小
        $_ENV['CACHE_MARSHALLER_WARNING_VALUE_SIZE'] = 100;
        $_ENV['CACHE_MARSHALLER_WARNING_DEMO_SIZE'] = 50;

        $mediumValue = str_repeat('y', 150); // 150 字节，超过自定义限制
        $values = ['medium_key' => $mediumValue];
        $failed = [];
        $expectedResult = ['medium_key' => $mediumValue];

        $this->innerMock
            ->expects($this->once())
            ->method('marshall')
            ->with($values, $this->anything())
            ->willReturn($expectedResult);

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                '发现一个数据比较大的缓存数据，请考虑拆分缓存',
                $this->callback(function ($context) {
                    return $context['key'] === 'medium_key' &&
                           $context['size'] === 150 &&
                           mb_strlen($context['demo']) === 50;
                })
            );

        $result = $this->marshaller->marshall($values, $failed);

        $this->assertSame($expectedResult, $result);

        // 清理环境变量
        unset($_ENV['CACHE_MARSHALLER_WARNING_VALUE_SIZE']);
        unset($_ENV['CACHE_MARSHALLER_WARNING_DEMO_SIZE']);
    }

    public function testMarshall_withMixedData_onlyWarnsForLargeData(): void
    {
        $smallValue = 'small';
        $largeValue = str_repeat('z', 2000000);
        $values = [
            'small_key' => $smallValue,
            'large_key' => $largeValue,
            'another_small' => 'also_small'
        ];
        $failed = [];
        $expectedResult = [
            'small_key' => $smallValue,
            'large_key' => $largeValue,
            'another_small' => 'also_small'
        ];

        $this->innerMock
            ->expects($this->once())
            ->method('marshall')
            ->with($values, $this->anything())
            ->willReturn($expectedResult);

        // 只应该为大数据记录一次警告
        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                '发现一个数据比较大的缓存数据，请考虑拆分缓存',
                $this->callback(function ($context) {
                    return $context['key'] === 'large_key';
                })
            );

        $result = $this->marshaller->marshall($values, $failed);

        $this->assertSame($expectedResult, $result);
    }

    public function testUnmarshall_callsInnerUnmarshall(): void
    {
        $value = 'marshalled_data';
        $expectedResult = ['unmarshalled' => 'data'];

        $this->innerMock
            ->expects($this->once())
            ->method('unmarshall')
            ->with($value)
            ->willReturn($expectedResult);

        $result = $this->marshaller->unmarshall($value);

        $this->assertSame($expectedResult, $result);
    }

    public function testReset_withResettableInner_callsInnerReset(): void
    {
        /** @var ResettableMarshallerMock&MockObject $resettableInner */
        $resettableInner = $this->createMock(ResettableMarshallerMock::class);
        $marshaller = new CacheMarshaller($resettableInner, $this->loggerMock);

        $resettableInner
            ->expects($this->once())
            ->method('reset');

        $marshaller->reset();
    }

    public function testReset_withNonResettableInner_doesNothing(): void
    {
        // 使用普通的 MarshallerInterface，不实现 ResetInterface
        $this->marshaller->reset();

        // 测试通过意味着没有异常抛出
        $this->assertTrue(true);
    }

    public function testMarshall_withEmptyValues_returnsEmpty(): void
    {
        $values = [];
        $failed = [];
        $expectedResult = [];

        $this->innerMock
            ->expects($this->once())
            ->method('marshall')
            ->with($values, $this->anything())
            ->willReturn($expectedResult);

        $this->loggerMock
            ->expects($this->never())
            ->method('warning');

        $result = $this->marshaller->marshall($values, $failed);

        $this->assertSame($expectedResult, $result);
    }
}
