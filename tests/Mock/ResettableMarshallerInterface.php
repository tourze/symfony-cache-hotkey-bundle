<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Mock;

use Symfony\Component\Cache\Marshaller\MarshallerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * 组合接口用于测试 Resettable Marshaller
 * @internal
 */
interface ResettableMarshallerInterface extends MarshallerInterface, ResetInterface
{
}