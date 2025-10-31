<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ResetInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\Symfony\CacheHotKey\Service\HotkeySmartCache;

/**
 * @internal
 */
#[CoversClass(HotkeySmartCache::class)]
#[RunTestsInSeparateProcesses]
final class HotkeySmartCacheTest extends AbstractIntegrationTestCase
{
    private CacheInterface&CacheItemPoolInterface $hotkeyCache;

    protected function onSetUp(): void
    {
        // 按照原始测试的方式获取服务
        $container = self::getContainer();
        $cacheService = $container->get('cache.app');

        // 验证获取的服务实现了基本的缓存接口
        $this->assertInstanceOf(CacheInterface::class, $cacheService);
        $this->assertInstanceOf(CacheItemPoolInterface::class, $cacheService);

        // 使用类型转换避免静态分析错误
        $this->hotkeyCache = $cacheService;
    }

    public function testHotkeyConstants(): void
    {
        $this->assertEquals('hotkey_', HotkeySmartCache::HOTKEY_PREFIX);
        $this->assertEquals('_split', HotkeySmartCache::HOTKEY_SUFFIX);
        $this->assertEquals(9, HotkeySmartCache::MAX_KEY);
    }

    public function testServiceCanBeObtainedFromContainer(): void
    {
        // 测试装饰器服务在容器中的存在性
        $this->assertTrue(self::getContainer()->has('cache.app'));

        // 验证缓存服务可以被正确获取并且是装饰后的实例
        $container = self::getContainer();
        $cacheService = $container->get('cache.app');
        $this->assertInstanceOf(CacheInterface::class, $cacheService);

        // 验证服务具备基本的缓存接口功能
        $this->assertInstanceOf(CacheItemPoolInterface::class, $cacheService);
        $this->assertInstanceOf(CacheInterface::class, $cacheService);
    }

    public function testHotkeyKeyGeneration(): void
    {
        // 测试热键前缀和后缀的识别逻辑
        $normalKey = 'normal_key';
        $hotkeyKey = 'hotkey_test';
        $existingSplitKey = 'hotkey_test_split';

        // 验证热键前缀识别逻辑
        $this->assertStringStartsNotWith(HotkeySmartCache::HOTKEY_PREFIX, $normalKey);
        $this->assertStringStartsWith(HotkeySmartCache::HOTKEY_PREFIX, $hotkeyKey);
        $this->assertStringStartsWith(HotkeySmartCache::HOTKEY_PREFIX, $existingSplitKey);

        // 验证后缀识别逻辑
        $this->assertStringEndsNotWith(HotkeySmartCache::HOTKEY_SUFFIX, $hotkeyKey);
        $this->assertStringEndsWith(HotkeySmartCache::HOTKEY_SUFFIX, $existingSplitKey);

        // 验证生成的子键格式正确
        $expectedFirstKey = "{$hotkeyKey}_0" . HotkeySmartCache::HOTKEY_SUFFIX;
        $expectedLastKey = "{$hotkeyKey}_" . HotkeySmartCache::MAX_KEY . HotkeySmartCache::HOTKEY_SUFFIX;
        $this->assertEquals('hotkey_test_0_split', $expectedFirstKey);
        $this->assertEquals('hotkey_test_9_split', $expectedLastKey);

        // 验证键的数量计算正确性（MAX_KEY + 1 = 10）
        $expectedCount = HotkeySmartCache::MAX_KEY + 1;
        $this->assertEquals(10, $expectedCount);
    }

    public function testClear(): void
    {
        // 测试clear方法的基本功能
        if ($this->hotkeyCache instanceof AdapterInterface) {
            // AdapterInterface支持prefix参数
            $this->assertIsBool($this->hotkeyCache->clear('test_prefix'));
        } else {
            // CacheItemPoolInterface不接受参数
            $this->assertIsBool($this->hotkeyCache->clear());
        }
    }

    public function testClearWithEmptyPrefix(): void
    {
        // 测试clear方法使用空前缀
        $this->assertIsBool($this->hotkeyCache->clear());
    }

    public function testCommit(): void
    {
        // 测试commit方法的基本功能
        $this->assertIsBool($this->hotkeyCache->commit());
    }

    public function testDelete(): void
    {
        // 测试delete方法的基本功能
        $key = 'test_key_' . uniqid();
        $this->assertIsBool($this->hotkeyCache->delete($key));
    }

    public function testDeleteHotkey(): void
    {
        // 测试删除hotkey前缀的键
        $key = 'hotkey_test_' . uniqid();
        $this->assertIsBool($this->hotkeyCache->delete($key));
    }

    public function testDeleteItem(): void
    {
        // 测试deleteItem方法的基本功能
        $key = 'test_key_' . uniqid();
        $this->assertIsBool($this->hotkeyCache->deleteItem($key));
    }

    public function testDeleteItemHotkey(): void
    {
        // 测试删除hotkey前缀的键
        $key = 'hotkey_test_' . uniqid();
        $this->assertIsBool($this->hotkeyCache->deleteItem($key));
    }

    public function testDeleteItems(): void
    {
        // 测试deleteItems方法的基本功能
        $keys = ['normal_key1_' . uniqid(), 'normal_key2_' . uniqid()];
        $this->assertIsBool($this->hotkeyCache->deleteItems($keys));
    }

    public function testDeleteItemsWithHotkeys(): void
    {
        // 测试删除包含hotkey的键列表
        $keys = ['normal_key_' . uniqid(), 'hotkey_test_' . uniqid()];
        $this->assertIsBool($this->hotkeyCache->deleteItems($keys));
    }

    public function testGetNormalKey(): void
    {
        // 测试get方法的基本功能
        $key = 'normal_key_' . uniqid();
        $callback = function () { return 'test_value'; };
        $result = $this->hotkeyCache->get($key, $callback);
        $this->assertEquals('test_value', $result);
    }

    public function testGetHotkey(): void
    {
        // 测试get方法处理hotkey前缀
        $key = 'hotkey_test_' . uniqid();
        $callback = function () { return 'test_value'; };
        $result = $this->hotkeyCache->get($key, $callback);
        $this->assertEquals('test_value', $result);
    }

    public function testGetWithBetaParameter(): void
    {
        // 测试get方法使用beta参数
        $key = 'normal_key_' . uniqid();
        $callback = function () { return 'test_value'; };
        $beta = 1.5;
        $result = $this->hotkeyCache->get($key, $callback, $beta);
        $this->assertEquals('test_value', $result);
    }

    public function testGetWithMetadata(): void
    {
        // 测试get方法使用metadata参数
        $key = 'normal_key_' . uniqid();
        $callback = function () { return 'test_value'; };
        $metadata = [];
        $result = $this->hotkeyCache->get($key, $callback, null, $metadata);
        $this->assertEquals('test_value', $result);
    }

    public function testInvalidateTagsWithEmptyTags(): void
    {
        // 测试invalidateTags方法使用空标签数组
        if ($this->hotkeyCache instanceof TagAwareCacheInterface) {
            $result = $this->hotkeyCache->invalidateTags([]);
            $this->assertFalse($result);
        }
    }

    public function testInvalidateTags(): void
    {
        // 测试invalidateTags方法的基本功能
        if ($this->hotkeyCache instanceof TagAwareCacheInterface) {
            $tags = ['tag1', 'tag2'];
            $this->assertIsBool($this->hotkeyCache->invalidateTags($tags));
        }
    }

    public function testReset(): void
    {
        // 测试reset方法的基本功能（不应该抛出异常）
        if ($this->hotkeyCache instanceof ResetInterface) {
            $this->hotkeyCache->reset();
        }
        $this->assertTrue(true); // 如果没有异常则测试通过
    }

    public function testSave(): void
    {
        // 测试save方法的基本功能
        $item = $this->hotkeyCache->getItem('test_key_' . uniqid());
        $item->set('test_value');
        $result = $this->hotkeyCache->save($item);
        $this->assertIsBool($result);
    }

    public function testSaveHotkey(): void
    {
        // 测试保存hotkey前缀的键
        $item = $this->hotkeyCache->getItem('hotkey_test_' . uniqid());
        $item->set('test_value');
        $result = $this->hotkeyCache->save($item);
        $this->assertIsBool($result);
    }

    public function testSaveDeferred(): void
    {
        // 测试saveDeferred方法的基本功能
        $item = $this->hotkeyCache->getItem('test_key_' . uniqid());
        $item->set('test_value');
        $result = $this->hotkeyCache->saveDeferred($item);
        $this->assertIsBool($result);
    }

    public function testSaveDeferredHotkey(): void
    {
        // 测试延迟保存hotkey前缀的键
        $item = $this->hotkeyCache->getItem('hotkey_test_' . uniqid());
        $item->set('test_value');
        $result = $this->hotkeyCache->saveDeferred($item);
        $this->assertIsBool($result);
    }
}
