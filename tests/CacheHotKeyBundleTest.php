<?php

declare(strict_types=1);

namespace Tourze\Symfony\CacheHotKey\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\Symfony\CacheHotKey\CacheHotKeyBundle;

/**
 * @internal
 */
#[CoversClass(CacheHotKeyBundle::class)]
#[RunTestsInSeparateProcesses]
final class CacheHotKeyBundleTest extends AbstractBundleTestCase
{
}
