<?php

namespace Tourze\Symfony\CacheHotKey\Interface;

use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * 组合接口用于测试 - TagAware缓存适配器
 *
 * @internal
 */
interface TagAwareCacheAdapterInterface extends TagAwareAdapterInterface, TagAwareCacheInterface
{
}
