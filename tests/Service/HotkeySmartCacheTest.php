<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ResetInterface;
use Tourze\Symfony\CacheHotKey\Service\HotkeySmartCache;

interface DecoratedCacheMock extends AdapterInterface, CacheInterface, TagAwareCacheInterface, TagAwareAdapterInterface, ResetInterface
{
}

interface NonTagAwareCacheMock extends AdapterInterface, CacheInterface, ResetInterface
{
}

interface NonResettableCacheMock extends AdapterInterface, CacheInterface, TagAwareCacheInterface, TagAwareAdapterInterface
{
}

class HotkeySmartCacheTest extends TestCase
{
    /** @var AdapterInterface&MockObject */
    private $decoratedAdapterMock;

    /** @var LoggerInterface&MockObject */
    private $loggerMock;

    /** @var HotkeySmartCache */
    private $hotkeyCache;

    protected function setUp(): void
    {
        $this->decoratedAdapterMock = $this->createMock(AdapterInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->hotkeyCache = new HotkeySmartCache($this->decoratedAdapterMock, $this->loggerMock);
    }

    public function testSaveItem(): void
    {
        /** @var CacheItemInterface&MockObject $itemMock */
        $itemMock = $this->createMock(CacheItemInterface::class);
        $itemMock->expects($this->once())
            ->method('getKey')
            ->willReturn('test_key');

        $this->decoratedAdapterMock->expects($this->once())
            ->method('save')
            ->with($itemMock)
            ->willReturn(true);

        $this->assertTrue($this->hotkeyCache->save($itemMock));
    }

    public function testSaveHotkeyItem(): void
    {
        /** @var CacheItemInterface&MockObject $itemMock */
        $itemMock = $this->createMock(CacheItemInterface::class);
        $itemMock->expects($this->once())
            ->method('getKey')
            ->willReturn('test_key');

        $this->decoratedAdapterMock->expects($this->once())
            ->method('save')
            ->with($itemMock)
            ->willReturn(true);

        $this->assertTrue($this->hotkeyCache->save($itemMock));
    }

    public function testSaveDeferredItem(): void
    {
        /** @var CacheItemInterface&MockObject $itemMock */
        $itemMock = $this->createMock(CacheItemInterface::class);
        $itemMock->expects($this->once())
            ->method('getKey')
            ->willReturn('test_key');

        $this->decoratedAdapterMock->expects($this->once())
            ->method('saveDeferred')
            ->with($itemMock)
            ->willReturn(true);

        $this->assertTrue($this->hotkeyCache->saveDeferred($itemMock));
    }

    public function testSaveDeferredHotkeyItem(): void
    {
        /** @var CacheItemInterface&MockObject $itemMock */
        $itemMock = $this->createMock(CacheItemInterface::class);
        $itemMock->expects($this->once())
            ->method('getKey')
            ->willReturn('test_key');

        $this->decoratedAdapterMock->expects($this->once())
            ->method('saveDeferred')
            ->with($itemMock)
            ->willReturn(true);

        $this->assertTrue($this->hotkeyCache->saveDeferred($itemMock));
    }

    public function testInvalidateTagsWhenSupported(): void
    {
        /** @var DecoratedCacheMock|MockObject $decoratedAdapterMock */
        $decoratedAdapterMock = $this->createMock(DecoratedCacheMock::class);
        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $tags = ['tag1', 'tag2'];

        $decoratedAdapterMock->expects($this->once())
            ->method('invalidateTags')
            ->with($tags)
            ->willReturn(true);

        $cache = new HotkeySmartCache($decoratedAdapterMock, $loggerMock);
        $result = $cache->invalidateTags($tags);

        $this->assertTrue($result);
    }

    public function testResetWhenSupported(): void
    {
        /** @var DecoratedCacheMock|MockObject $decoratedAdapterMock */
        $decoratedAdapterMock = $this->createMock(DecoratedCacheMock::class);
        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $decoratedAdapterMock->expects($this->once())
            ->method('reset');

        $cache = new HotkeySmartCache($decoratedAdapterMock, $loggerMock);
        $cache->reset();
    }

    public function testInvalidateTagsWhenNotSupported(): void
    {
        /** @var NonTagAwareCacheMock|MockObject $decoratedAdapterMock */
        $decoratedAdapterMock = $this->createMock(NonTagAwareCacheMock::class);
        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $tags = ['tag1', 'tag2'];

        $loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                '主动让缓存标签过期时失败，当前缓存驱动不支持',
                ['className' => get_class($decoratedAdapterMock)]
            );

        $cache = new HotkeySmartCache($decoratedAdapterMock, $loggerMock);
        $result = $cache->invalidateTags($tags);

        $this->assertFalse($result);
    }

    public function testResetWhenNotSupported(): void
    {
        /** @var NonResettableCacheMock|MockObject $decoratedAdapterMock */
        $decoratedAdapterMock = $this->createMock(NonResettableCacheMock::class);
        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $cache = new HotkeySmartCache($decoratedAdapterMock, $loggerMock);
        $cache->reset();

        // No assertion needed, just verifying no error is thrown
        $this->expectNotToPerformAssertions();
    }
}
