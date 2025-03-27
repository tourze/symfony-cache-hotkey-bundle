<?php

namespace Tourze\Symfony\CacheHotKey\Service;

use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ResetInterface;
use Tourze\BacktraceHelper\Backtrace;

/**
 * 解决hotkey打爆单个节点的性能问题
 * 主要原理是判断key是否以特定字符串开头，如果是的话就将缓存内容分别添加到N个不同的key，读取时再随机读取一个
 */
#[AsDecorator(decorates: 'cache.app')]
#[Autoconfigure(lazy: true)]
class HotkeySmartCache implements AdapterInterface, CacheInterface, TagAwareCacheInterface, TagAwareAdapterInterface, ResetInterface
{
    public const HOTKEY_PREFIX = 'hotkey_';

    public const HOTKEY_SUFFIX = '_split';

    public const MAX_KEY = 9;

    public function __construct(
        #[AutowireDecorated] private readonly AdapterInterface|CacheInterface $decorated,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 检查这个key，是否需要存储多份
     *
     * @return string[]
     */
    protected function getHotkeyChildrenKeys(string $key): array
    {
        if (!str_starts_with($key, self::HOTKEY_PREFIX)) {
            return [];
        }
        // 防止被循环读取
        if (str_ends_with($key, self::HOTKEY_SUFFIX)) {
            return [];
        }

        $list = [];
        $i = 0;
        while ($i <= static::MAX_KEY) {
            $list[] = "{$key}_{$i}" . self::HOTKEY_SUFFIX;
            ++$i;
        }

        return $list;
    }

    public function getItem(mixed $key): CacheItem
    {
        // 主key存在，就当作存在了
        return $this->decorated->getItem($key);
    }

    public function getItems(array $keys = []): iterable
    {
        return $this->decorated->getItems($keys);
    }

    public function clear(string $prefix = ''): bool
    {
        // 因为hotkey的子key只是在后面加了冒号，所以这个应该没啥问题
        return $this->decorated->clear($prefix);
    }

    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
    {
        // 传入 test_key，实际希望他随机读取一个下面的key来作为返回值
        $newKey = $key;
        if (str_starts_with($key, self::HOTKEY_PREFIX) && !str_ends_with($key, self::HOTKEY_SUFFIX)) {
            $newKey = "{$key}_" . rand(0, static::MAX_KEY) . self::HOTKEY_SUFFIX;
        }

        return $this->decorated->get($newKey, $callback, $beta, $metadata);
    }

    public function delete(string $key): bool
    {
        try {
            return $this->decorated->delete($key);
        } finally {
            foreach ($this->getHotkeyChildrenKeys($key) as $possibleKey) {
                $this->decorated->delete($possibleKey);
            }
        }
    }

    public function hasItem(string $key): bool
    {
        // 主key存在，就当作存在了
        return $this->decorated->hasItem($key);
    }

    public function deleteItem(string $key): bool
    {
        try {
            return $this->decorated->deleteItem($key);
        } finally {
            $keys = $this->getHotkeyChildrenKeys($key);
            if (!empty($keys)) {
                $this->decorated->deleteItems($keys);
            }
        }
    }

    public function deleteItems(array $keys): bool
    {
        $newKeys = $keys;
        foreach ($keys as $key) {
            $newKeys = array_merge($newKeys, $this->getHotkeyChildrenKeys($key));
        }
        $newKeys = array_values(array_unique($newKeys));

        return $this->decorated->deleteItems($newKeys);
    }

    public function save(CacheItemInterface $item): bool
    {
        try {
            return $this->decorated->save($item);
        } finally {
            $possibleKeys = $this->getHotkeyChildrenKeys($item->getKey());
            if (!empty($possibleKeys)) {
                foreach ($possibleKeys as $possibleKey) {
                    $subItem = $this->getItem($possibleKey);
                    $subItem->set($item->get());
                    $this->decorated->saveDeferred($subItem);
                }
                $this->decorated->commit();
            }
        }
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        try {
            return $this->decorated->saveDeferred($item);
        } finally {
            foreach ($this->getHotkeyChildrenKeys($item->getKey()) as $possibleKey) {
                $subItem = $this->getItem($possibleKey);
                $subItem->set($item->get());
                $this->decorated->saveDeferred($subItem);
            }
        }
    }

    public function commit(): bool
    {
        return $this->decorated->commit();
    }

    public function invalidateTags(array $tags): bool
    {
        if (empty($tags)) {
            return false;
        }

        if ($this->decorated instanceof TagAwareCacheInterface) {
            if ($_ENV['CACHE_INVALIDATE_TAG_LOG'] ?? false) {
                $this->logger->debug('清空标签关联缓存', [
                    'tags' => $tags,
                    'backtrace' => Backtrace::create()->toString(),
                ]);
            }
            return $this->decorated->invalidateTags($tags);
        }
        $this->logger->warning('主动让缓存标签过期时失败，当前缓存驱动不支持', [
            'className' => get_class($this->decorated),
        ]);

        return false;
    }

    public function reset(): void
    {
        if ($this->decorated instanceof ResetInterface) {
            $this->decorated->reset();
        }
    }

    public function getDecorated(): CacheInterface|AdapterInterface
    {
        return $this->decorated;
    }
}
