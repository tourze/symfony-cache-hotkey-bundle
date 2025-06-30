<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Service\HotkeySmartCache;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Symfony\CacheHotKey\Tests\Mock\CacheAdapterInterface;
use Tourze\Symfony\CacheHotKey\Service\HotkeySmartCache;

class HotkeyDeleteTest extends TestCase
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

    public function testDelete_withNormalKey_deletesOnlyMainKey(): void
    {
        $key = 'normal_key';

        $this->decoratedMock
            ->expects($this->once())
            ->method('delete')
            ->with($key)
            ->willReturn(true);

        $result = $this->cache->delete($key);

        $this->assertTrue($result);
    }

    public function testDelete_withHotkeyPrefix_deletesMainKeyAndSubKeys(): void
    {
        $key = 'hotkey_test';

        // 首先删除主 key
        $this->decoratedMock
            ->expects($this->exactly(11)) // 1 次主 key + 10 次子 key
            ->method('delete')
            ->willReturn(true);

        $result = $this->cache->delete($key);

        $this->assertTrue($result);
    }

    public function testDelete_withSplitSuffix_deletesOnlyMainKey(): void
    {
        $key = 'hotkey_test_5_split';

        $this->decoratedMock
            ->expects($this->once())
            ->method('delete')
            ->with($key)
            ->willReturn(true);

        $result = $this->cache->delete($key);

        $this->assertTrue($result);
    }

    public function testDeleteItem_withNormalKey_deletesOnlyMainKey(): void
    {
        $key = 'normal_key';

        $this->decoratedMock
            ->expects($this->once())
            ->method('deleteItem')
            ->with($key)
            ->willReturn(true);

        // 不应该调用 deleteItems
        $this->decoratedMock
            ->expects($this->never())
            ->method('deleteItems');

        $result = $this->cache->deleteItem($key);

        $this->assertTrue($result);
    }

    public function testDeleteItem_withHotkeyPrefix_deletesMainKeyAndSubKeys(): void
    {
        $key = 'hotkey_test';

        // 删除主 key
        $this->decoratedMock
            ->expects($this->once())
            ->method('deleteItem')
            ->with($key)
            ->willReturn(true);

        // 删除子 keys
        $expectedSubKeys = [
            'hotkey_test_0_split',
            'hotkey_test_1_split',
            'hotkey_test_2_split',
            'hotkey_test_3_split',
            'hotkey_test_4_split',
            'hotkey_test_5_split',
            'hotkey_test_6_split',
            'hotkey_test_7_split',
            'hotkey_test_8_split',
            'hotkey_test_9_split',
        ];

        $this->decoratedMock
            ->expects($this->once())
            ->method('deleteItems')
            ->with($expectedSubKeys)
            ->willReturn(true);

        $result = $this->cache->deleteItem($key);

        $this->assertTrue($result);
    }

    public function testDeleteItem_withSplitSuffix_deletesOnlyMainKey(): void
    {
        $key = 'hotkey_test_5_split';

        $this->decoratedMock
            ->expects($this->once())
            ->method('deleteItem')
            ->with($key)
            ->willReturn(true);

        // 不应该调用 deleteItems，因为分片 key 本身不会产生子 key
        $this->decoratedMock
            ->expects($this->never())
            ->method('deleteItems');

        $result = $this->cache->deleteItem($key);

        $this->assertTrue($result);
    }

    public function testDeleteItems_withMixedKeys_deletesAllKeysAndSubKeys(): void
    {
        $keys = [
            'normal_key',
            'hotkey_test1',
            'another_normal_key',
            'hotkey_test2',
            'hotkey_split_suffix_split'
        ];

        $expectedAllKeys = [
            'normal_key',
            'hotkey_test1',
            'another_normal_key',
            'hotkey_test2',
            'hotkey_split_suffix_split',
            // hotkey_test1 的子 keys
            'hotkey_test1_0_split',
            'hotkey_test1_1_split',
            'hotkey_test1_2_split',
            'hotkey_test1_3_split',
            'hotkey_test1_4_split',
            'hotkey_test1_5_split',
            'hotkey_test1_6_split',
            'hotkey_test1_7_split',
            'hotkey_test1_8_split',
            'hotkey_test1_9_split',
            // hotkey_test2 的子 keys
            'hotkey_test2_0_split',
            'hotkey_test2_1_split',
            'hotkey_test2_2_split',
            'hotkey_test2_3_split',
            'hotkey_test2_4_split',
            'hotkey_test2_5_split',
            'hotkey_test2_6_split',
            'hotkey_test2_7_split',
            'hotkey_test2_8_split',
            'hotkey_test2_9_split',
        ];

        $this->decoratedMock
            ->expects($this->once())
            ->method('deleteItems')
            ->with($this->callback(function ($actualKeys) use ($expectedAllKeys) {
                // 检查数组长度
                if (count($actualKeys) !== count($expectedAllKeys)) {
                    return false;
                }
                // 检查所有期望的 key 都存在
                foreach ($expectedAllKeys as $expectedKey) {
                    if (!in_array($expectedKey, $actualKeys, true)) {
                        return false;
                    }
                }
                return true;
            }))
            ->willReturn(true);

        $result = $this->cache->deleteItems($keys);

        $this->assertTrue($result);
    }

    public function testDeleteItems_withOnlyNormalKeys_deletesOnlyMainKeys(): void
    {
        $keys = ['key1', 'key2', 'key3'];

        $this->decoratedMock
            ->expects($this->once())
            ->method('deleteItems')
            ->with($keys)
            ->willReturn(true);

        $result = $this->cache->deleteItems($keys);

        $this->assertTrue($result);
    }

    public function testDeleteItems_withEmptyArray_deletesNothing(): void
    {
        $keys = [];

        $this->decoratedMock
            ->expects($this->once())
            ->method('deleteItems')
            ->with([])
            ->willReturn(true);

        $result = $this->cache->deleteItems($keys);

        $this->assertTrue($result);
    }

    public function testDelete_withEmptyHotkeyPrefix_deletesMainKeyAndSubKeys(): void
    {
        $key = 'hotkey_';

        // 应该删除主 key + 10 个子 key
        $this->decoratedMock
            ->expects($this->exactly(11))
            ->method('delete')
            ->willReturn(true);

        $result = $this->cache->delete($key);

        $this->assertTrue($result);
    }
} 