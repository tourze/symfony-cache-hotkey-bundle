<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Service\HotkeySmartCache;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ResetInterface;
use Tourze\Symfony\CacheHotKey\Service\HotkeySmartCache;

interface TagAwareCacheMock extends AdapterInterface, CacheInterface, TagAwareCacheInterface, TagAwareAdapterInterface, ResetInterface
{
}

interface NonTagAwareCacheMock extends AdapterInterface, CacheInterface, ResetInterface
{
}

class HotkeyTagOperationsTest extends TestCase
{
    public function testInvalidateTags_withTagAwareCache_succeeds(): void
    {
        /** @var TagAwareCacheMock&MockObject $decoratedMock */
        $decoratedMock = $this->createMock(TagAwareCacheMock::class);
        /** @var LoggerInterface&MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $tags = ['tag1', 'tag2'];

        $decoratedMock
            ->expects($this->once())
            ->method('invalidateTags')
            ->with($tags)
            ->willReturn(true);

        // 不应该记录日志（默认情况下）
        $loggerMock
            ->expects($this->never())
            ->method('debug');

        $cache = new HotkeySmartCache($decoratedMock, $loggerMock);
        $result = $cache->invalidateTags($tags);

        $this->assertTrue($result);
    }

    public function testInvalidateTags_withTagAwareCacheAndLoggingEnabled_logsDebug(): void
    {
        /** @var TagAwareCacheMock&MockObject $decoratedMock */
        $decoratedMock = $this->createMock(TagAwareCacheMock::class);
        /** @var LoggerInterface&MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        // 启用日志记录
        $_ENV['CACHE_INVALIDATE_TAG_LOG'] = true;

        $tags = ['tag1', 'tag2'];

        $decoratedMock
            ->expects($this->once())
            ->method('invalidateTags')
            ->with($tags)
            ->willReturn(true);

        // 应该记录调试日志
        $loggerMock
            ->expects($this->once())
            ->method('debug')
            ->with(
                '清空标签关联缓存',
                $this->callback(function ($context) use ($tags) {
                    return isset($context['tags']) && $context['tags'] === $tags &&
                           isset($context['backtrace']) && is_string($context['backtrace']);
                })
            );

        $cache = new HotkeySmartCache($decoratedMock, $loggerMock);
        $result = $cache->invalidateTags($tags);

        $this->assertTrue($result);

        // 清理环境变量
        unset($_ENV['CACHE_INVALIDATE_TAG_LOG']);
    }

    public function testInvalidateTags_withTagAwareCacheAndLoggingEnabledAsString_logsDebug(): void
    {
        /** @var TagAwareCacheMock&MockObject $decoratedMock */
        $decoratedMock = $this->createMock(TagAwareCacheMock::class);
        /** @var LoggerInterface&MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        // 启用日志记录（字符串形式）
        $_ENV['CACHE_INVALIDATE_TAG_LOG'] = 'true';

        $tags = ['user', 'profile'];

        $decoratedMock
            ->expects($this->once())
            ->method('invalidateTags')
            ->with($tags)
            ->willReturn(true);

        $loggerMock
            ->expects($this->once())
            ->method('debug');

        $cache = new HotkeySmartCache($decoratedMock, $loggerMock);
        $result = $cache->invalidateTags($tags);

        $this->assertTrue($result);

        // 清理环境变量
        unset($_ENV['CACHE_INVALIDATE_TAG_LOG']);
    }

    public function testInvalidateTags_withNonTagAwareCache_logsWarningAndReturnsFalse(): void
    {
        /** @var NonTagAwareCacheMock&MockObject $decoratedMock */
        $decoratedMock = $this->createMock(NonTagAwareCacheMock::class);
        /** @var LoggerInterface&MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $tags = ['tag1', 'tag2'];

        // 应该记录警告日志
        $loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                '主动让缓存标签过期时失败，当前缓存驱动不支持',
                ['className' => get_class($decoratedMock)]
            );

        $cache = new HotkeySmartCache($decoratedMock, $loggerMock);
        $result = $cache->invalidateTags($tags);

        $this->assertFalse($result);
    }

    public function testInvalidateTags_withEmptyTags_returnsFalse(): void
    {
        /** @var TagAwareCacheMock&MockObject $decoratedMock */
        $decoratedMock = $this->createMock(TagAwareCacheMock::class);
        /** @var LoggerInterface&MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $tags = [];

        // 不应该调用底层的 invalidateTags
        $decoratedMock
            ->expects($this->never())
            ->method('invalidateTags');

        // 不应该记录任何日志
        $loggerMock
            ->expects($this->never())
            ->method('debug');
        $loggerMock
            ->expects($this->never())
            ->method('warning');

        $cache = new HotkeySmartCache($decoratedMock, $loggerMock);
        $result = $cache->invalidateTags($tags);

        $this->assertFalse($result);
    }

    public function testInvalidateTags_withSingleTag_works(): void
    {
        /** @var TagAwareCacheMock&MockObject $decoratedMock */
        $decoratedMock = $this->createMock(TagAwareCacheMock::class);
        /** @var LoggerInterface&MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $tags = ['single_tag'];

        $decoratedMock
            ->expects($this->once())
            ->method('invalidateTags')
            ->with($tags)
            ->willReturn(true);

        $cache = new HotkeySmartCache($decoratedMock, $loggerMock);
        $result = $cache->invalidateTags($tags);

        $this->assertTrue($result);
    }

    public function testInvalidateTags_withManyTags_works(): void
    {
        /** @var TagAwareCacheMock&MockObject $decoratedMock */
        $decoratedMock = $this->createMock(TagAwareCacheMock::class);
        /** @var LoggerInterface&MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $tags = ['tag1', 'tag2', 'tag3', 'tag4', 'tag5'];

        $decoratedMock
            ->expects($this->once())
            ->method('invalidateTags')
            ->with($tags)
            ->willReturn(true);

        $cache = new HotkeySmartCache($decoratedMock, $loggerMock);
        $result = $cache->invalidateTags($tags);

        $this->assertTrue($result);
    }

    public function testInvalidateTags_withSpecialTagNames_works(): void
    {
        /** @var TagAwareCacheMock&MockObject $decoratedMock */
        $decoratedMock = $this->createMock(TagAwareCacheMock::class);
        /** @var LoggerInterface&MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $tags = ['user:123', 'profile_data', 'cache-key.special'];

        $decoratedMock
            ->expects($this->once())
            ->method('invalidateTags')
            ->with($tags)
            ->willReturn(true);

        $cache = new HotkeySmartCache($decoratedMock, $loggerMock);
        $result = $cache->invalidateTags($tags);

        $this->assertTrue($result);
    }

    public function testReset_withResettableCache_callsReset(): void
    {
        /** @var TagAwareCacheMock&MockObject $decoratedMock */
        $decoratedMock = $this->createMock(TagAwareCacheMock::class);
        /** @var LoggerInterface&MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $decoratedMock
            ->expects($this->once())
            ->method('reset');

        $cache = new HotkeySmartCache($decoratedMock, $loggerMock);
        $cache->reset();
    }

    public function testReset_withNonResettableCache_doesNothing(): void
    {
        /** @var NonTagAwareCacheMock&MockObject $decoratedMock */
        $decoratedMock = $this->createMock(NonTagAwareCacheMock::class);
        /** @var LoggerInterface&MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $decoratedMock
            ->expects($this->once())
            ->method('reset');

        $cache = new HotkeySmartCache($decoratedMock, $loggerMock);
        $cache->reset();

        // 测试通过意味着没有异常抛出
        $this->assertTrue(true);
    }
} 