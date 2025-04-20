<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class MockCacheAdapter extends TestCase implements AdapterInterface
{
    private array $items = [];

    public function getItem(string $key): CacheItemInterface
    {
        /** @var CacheItemInterface&MockObject $item */
        $item = $this->createMock(CacheItemInterface::class);
        $item->method('getKey')->willReturn($key);
        $item->method('get')->willReturn($this->items[$key] ?? null);
        $item->method('isHit')->willReturn(isset($this->items[$key]));
        return $item;
    }

    public function getItems(array $keys = []): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->getItem($key);
        }
        return $result;
    }

    public function hasItem(mixed $key): bool
    {
        return isset($this->items[$key]);
    }

    public function clear(string $prefix = ''): bool
    {
        if ($prefix === '') {
            $this->items = [];
            return true;
        }

        foreach ($this->items as $key => $item) {
            if (str_starts_with($key, $prefix)) {
                unset($this->items[$key]);
            }
        }
        return true;
    }

    public function deleteItem(mixed $key): bool
    {
        unset($this->items[$key]);
        return true;
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }
        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        $this->items[$item->getKey()] = $item;
        return true;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->save($item);
    }

    public function commit(): bool
    {
        return true;
    }
}
