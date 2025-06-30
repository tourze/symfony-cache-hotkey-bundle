<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Service\HotkeySmartCache;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Symfony\CacheHotKey\Tests\Mock\CacheAdapterInterface;
use Tourze\Symfony\CacheHotKey\Service\HotkeySmartCache;

class HotkeyGetTest extends TestCase
{
    /** @var CacheAdapterInterface&MockObject */
    private $decoratedMock;

    /** @var LoggerInterface&MockObject */
    private $loggerMock;

    private HotkeySmartCache $cache;

    protected function setUp(): void
    {
        $this->decoratedMock = $this->createMock(CacheAdapterInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->cache = new HotkeySmartCache($this->decoratedMock, $this->loggerMock);
    }

    public function testGet_withNormalKey_callsDecoratedDirectly(): void
    {
        $key = 'normal_key';
        $callback = function () {
            return 'value';
        };
        $expectedValue = 'value';

        $this->decoratedMock
            ->expects($this->once())
            ->method('get')
            ->with($key, $callback, null, null)
            ->willReturn($expectedValue);

        $result = $this->cache->get($key, $callback);

        $this->assertSame($expectedValue, $result);
    }

    public function testGet_withHotkeyPrefix_callsRandomSubKey(): void
    {
        $key = 'hotkey_test';
        $callback = function () {
            return 'value';
        };
        $expectedValue = 'hotkey_value';

        // 使用 willReturnCallback 来捕获实际调用的 key
        $calledKeys = [];
        $this->decoratedMock
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($actualKey, $actualCallback) use (&$calledKeys, $expectedValue) {
                $calledKeys[] = $actualKey;
                return $expectedValue;
            });

        $result = $this->cache->get($key, $callback);

        $this->assertSame($expectedValue, $result);
        $this->assertCount(1, $calledKeys);
        
        // 验证调用的 key 是否为预期的分片 key 格式
        $calledKey = $calledKeys[0];
        $this->assertStringStartsWith('hotkey_test_', $calledKey);
        $this->assertStringEndsWith('_split', $calledKey);
        
        // 验证分片编号在有效范围内 (0-9)
        $matches = [];
        preg_match('/hotkey_test_(\d+)_split/', $calledKey, $matches);
        $this->assertArrayHasKey(1, $matches);
        $shardNumber = (int) $matches[1];
        $this->assertGreaterThanOrEqual(0, $shardNumber);
        $this->assertLessThanOrEqual(9, $shardNumber);
    }

    public function testGet_withSplitSuffix_callsDecoratedDirectly(): void
    {
        $key = 'hotkey_test_5_split';
        $callback = function () {
            return 'value';
        };
        $expectedValue = 'split_value';

        $this->decoratedMock
            ->expects($this->once())
            ->method('get')
            ->with($key, $callback, null, null)
            ->willReturn($expectedValue);

        $result = $this->cache->get($key, $callback);

        $this->assertSame($expectedValue, $result);
    }

    public function testGet_withBetaParameter_passesToDecorated(): void
    {
        $key = 'normal_key';
        $callback = function () {
            return 'value';
        };
        $beta = 1.5;
        $expectedValue = 'value';

        $this->decoratedMock
            ->expects($this->once())
            ->method('get')
            ->with($key, $callback, $beta, null)
            ->willReturn($expectedValue);

        $result = $this->cache->get($key, $callback, $beta);

        $this->assertSame($expectedValue, $result);
    }

    public function testGet_withMetadataParameter_passesToDecorated(): void
    {
        $key = 'normal_key';
        $callback = function () {
            return 'value';
        };
        $metadata = [];
        $expectedValue = 'value';

        $this->decoratedMock
            ->expects($this->once())
            ->method('get')
            ->with($key, $callback, null, $this->anything())
            ->willReturn($expectedValue);

        $result = $this->cache->get($key, $callback, null, $metadata);

        $this->assertSame($expectedValue, $result);
    }

    public function testGet_withEmptyHotkeyPrefix_callsRandomSubKey(): void
    {
        $key = 'hotkey_';
        $callback = function () {
            return 'value';
        };
        $expectedValue = 'value';

        $calledKeys = [];
        $this->decoratedMock
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($actualKey) use (&$calledKeys, $expectedValue) {
                $calledKeys[] = $actualKey;
                return $expectedValue;
            });

        $result = $this->cache->get($key, $callback);

        $this->assertSame($expectedValue, $result);
        $this->assertCount(1, $calledKeys);
        
        $calledKey = $calledKeys[0];
        $this->assertStringStartsWith('hotkey__', $calledKey);
        $this->assertStringEndsWith('_split', $calledKey);
    }
} 