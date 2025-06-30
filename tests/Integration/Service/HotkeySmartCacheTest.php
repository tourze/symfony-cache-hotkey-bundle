<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tourze\Symfony\CacheHotKey\Service\HotkeySmartCache;
use Psr\Log\NullLogger;

/**
 * 集成测试 HotkeySmartCache
 */
class HotkeySmartCacheTest extends TestCase
{
    private HotkeySmartCache $cache;
    private ArrayAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new ArrayAdapter();
        $this->cache = new HotkeySmartCache($this->adapter, new NullLogger());
    }

    public function testHotkeyDistribution(): void
    {
        $key = 'hotkey_test';
        $value = 'test_value';
        
        // 写入值
        $result = $this->cache->get($key, function () use ($value) {
            return $value;
        });
        
        $this->assertSame($value, $result);
        
        // 验证值被分布到多个 key
        $foundKeys = [];
        for ($i = 0; $i <= 9; $i++) {
            $splitKey = "hotkey_test_{$i}_split";
            if ($this->adapter->hasItem($splitKey)) {
                $foundKeys[] = $splitKey;
            }
        }
        
        // 应该只有一个分片 key 有值
        $this->assertCount(1, $foundKeys, '应该只有一个分片存储了数据');
    }

    public function testNormalKeyHandling(): void
    {
        $key = 'normal_key';
        $value = 'normal_value';
        
        // 写入值
        $result = $this->cache->get($key, function () use ($value) {
            return $value;
        });
        
        $this->assertSame($value, $result);
        
        // 验证值存储在原始 key
        $this->assertTrue($this->adapter->hasItem($key));
        
        // 验证没有创建分片 key
        for ($i = 0; $i <= 9; $i++) {
            $splitKey = "normal_key_{$i}_split";
            $this->assertFalse($this->adapter->hasItem($splitKey));
        }
    }

    public function testDeleteHotkeyRemovesAllShards(): void
    {
        $key = 'hotkey_delete_test';
        
        // 先设置所有分片
        for ($i = 0; $i <= 9; $i++) {
            $splitKey = "hotkey_delete_test_{$i}_split";
            $item = $this->adapter->getItem($splitKey);
            $item->set("shard_{$i}");
            $this->adapter->save($item);
        }
        
        // 删除 hotkey
        $this->cache->delete($key);
        
        // 验证所有分片都被删除
        for ($i = 0; $i <= 9; $i++) {
            $splitKey = "hotkey_delete_test_{$i}_split";
            $this->assertFalse($this->adapter->hasItem($splitKey));
        }
    }
}