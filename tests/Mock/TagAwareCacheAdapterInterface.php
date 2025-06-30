<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Mock;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * 组合接口用于测试 TagAware 功能
 * @internal
 */
interface TagAwareCacheAdapterInterface extends AdapterInterface, CacheInterface, TagAwareCacheInterface, TagAwareAdapterInterface, ResetInterface
{
}