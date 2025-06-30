<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Mock;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * 组合接口用于测试 Resettable 功能
 * @internal
 */
interface ResettableCacheAdapterInterface extends AdapterInterface, CacheInterface, ResetInterface
{
}