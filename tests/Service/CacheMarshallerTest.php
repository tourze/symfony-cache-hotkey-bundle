<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\Symfony\CacheHotKey\Service\CacheMarshaller;

/**
 * @internal
 */
#[CoversClass(CacheMarshaller::class)]
#[RunTestsInSeparateProcesses]
final class CacheMarshallerTest extends AbstractIntegrationTestCase
{
    private CacheMarshaller $marshaller;

    protected function onSetUp(): void
    {
        $this->marshaller = self::getService(CacheMarshaller::class);
    }

    public function testServiceCanBeObtainedFromContainer(): void
    {
        $this->assertInstanceOf(CacheMarshaller::class, $this->marshaller);
    }

    public function testMarshallWithEmptyArray(): void
    {
        $result = $this->marshaller->marshall([], $failed);
        $this->assertEmpty($result);
    }

    public function testUnmarshallWithValidData(): void
    {
        // 先序列化一些数据来获得有效的格式
        $testData = ['test' => 'data'];
        $marshalled = $this->marshaller->marshall(['key' => $testData], $failed);

        if (isset($marshalled['key']) && '' !== $marshalled['key']) {
            $marshalledValue = $marshalled['key'];
            if (is_string($marshalledValue)) {
                $result = $this->marshaller->unmarshall($marshalledValue);
                $this->assertEquals($testData, $result);
            } else {
                self::fail('Marshall返回的数据类型不正确');
            }
        } else {
            self::fail('Marshall操作未返回预期数据');
        }
    }

    public function testResetDoesNotThrow(): void
    {
        $this->marshaller->reset();
        $this->expectNotToPerformAssertions();
    }
}
