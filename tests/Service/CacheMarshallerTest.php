<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;
use Symfony\Contracts\Service\ResetInterface;
use Tourze\Symfony\CacheHotKey\Service\CacheMarshaller;

class CacheMarshallerTest extends TestCase
{
    private MarshallerInterface $innerMarshallerMock;
    private LoggerInterface $loggerMock;
    private CacheMarshaller $cacheMarshaller;

    protected function setUp(): void
    {
        $this->innerMarshallerMock = $this->createMock(MarshallerInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->cacheMarshaller = new CacheMarshaller($this->innerMarshallerMock, $this->loggerMock);
    }

    public function testMarshallSuccess(): void
    {
        $values = ['key1' => 'value1', 'key2' => 123];
        $marshalledValues = ['key1' => 'serialized_value1', 'key2' => 'serialized_123'];
        $failed = [];

        $this->innerMarshallerMock
            ->expects($this->once())
            ->method('marshall')
            ->with($values, $this->anything()) // $failed is passed by reference
            ->willReturn($marshalledValues);

        $this->loggerMock
            ->expects($this->never())
            ->method('warning');

        $result = $this->cacheMarshaller->marshall($values, $failed);

        $this->assertSame($marshalledValues, $result);
    }

    public function testMarshallLargeValueLogsWarning(): void
    {
        // 模拟环境变量，设置一个较小的阈值方便测试
        $_ENV['CACHE_MARSHALLER_WARNING_VALUE_SIZE'] = 10;
        $_ENV['CACHE_MARSHALLER_WARNING_DEMO_SIZE'] = 5;

        $values = ['large_key' => 'this is a very large string'];
        $marshalledValues = ['large_key' => 'serialized_large_string_more_than_10_chars'];
        $failed = [];

        $this->innerMarshallerMock
            ->expects($this->once())
            ->method('marshall')
            ->with($values, $this->anything())
            ->willReturn($marshalledValues);

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                '发现一个数据比较大的缓存数据，请考虑拆分缓存',
                $this->callback(function ($context) use ($marshalledValues) {
                    return isset($context['key']) && $context['key'] === 'large_key' &&
                        isset($context['size']) && $context['size'] === mb_strlen($marshalledValues['large_key']) &&
                        isset($context['demo']) && $context['demo'] === mb_substr($marshalledValues['large_key'], 0, 5);
                })
            );

        $result = $this->cacheMarshaller->marshall($values, $failed);

        $this->assertSame($marshalledValues, $result);

        // 清理环境变量，避免影响其他测试
        unset($_ENV['CACHE_MARSHALLER_WARNING_VALUE_SIZE'], $_ENV['CACHE_MARSHALLER_WARNING_DEMO_SIZE']);
    }

    public function testUnmarshall(): void
    {
        $value = 'serialized_value';
        $unmarshalledValue = 'unserialized_value';

        $this->innerMarshallerMock
            ->expects($this->once())
            ->method('unmarshall')
            ->with($value)
            ->willReturn($unmarshalledValue);

        $result = $this->cacheMarshaller->unmarshall($value);

        $this->assertSame($unmarshalledValue, $result);
    }

    public function testResetCallsInnerResetWhenPossible(): void
    {
        // Case 1: Inner marshaller implements ResetInterface
        /** @var MarshallerInterface&ResetInterface|MockObject $resettableInnerMock */
        $resettableInnerMock = $this->createMock(ResettableMarshaller::class); // Dummy class implementing both
        $resettableInnerMock->expects($this->once())->method('reset');

        $marshallerWithResettableInner = new CacheMarshaller($resettableInnerMock, $this->loggerMock);
        $marshallerWithResettableInner->reset();
        // Assertion is performed via expects() above
    }

    public function testResetDoesNotCallInnerResetWhenNotPossible(): void
    {
        // Case 2: Inner marshaller does not implement ResetInterface
        /** @var MarshallerInterface|MockObject $nonResettableInnerMock */
        $nonResettableInnerMock = $this->createMock(MarshallerInterface::class);
        // We expect reset *not* to be called, and no error to be thrown.
        // Since expects($this->never()) can be tricky with mocks not having the method,
        // we simply call reset and rely on PHPUnit to fail if an unexpected error occurs.

        $marshallerWithNonResettableInner = new CacheMarshaller($nonResettableInnerMock, $this->loggerMock);
        $marshallerWithNonResettableInner->reset();

        $this->expectNotToPerformAssertions(); // Explicitly state no assertions are expected here
    }
}

// Dummy interface/class for testing reset behaviour
interface ResettableMarshaller extends MarshallerInterface, ResetInterface
{
}
