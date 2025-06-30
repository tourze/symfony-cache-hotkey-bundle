<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Mock;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * 组合接口用于测试
 * @internal
 */
interface CacheAdapterInterface extends AdapterInterface, CacheInterface
{
}