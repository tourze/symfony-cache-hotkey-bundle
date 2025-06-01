<?php

namespace Tourze\Symfony\CacheHotKey\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\Symfony\CacheHotKey\CacheHotKeyBundle;

class CacheHotKeyBundleTest extends TestCase
{
    public function testInstanceOfBundle(): void
    {
        $bundle = new CacheHotKeyBundle();
        $this->assertInstanceOf(Bundle::class, $bundle);
    }

    public function testCanBeInstantiated(): void
    {
        $bundle = new CacheHotKeyBundle();
        $this->assertInstanceOf(CacheHotKeyBundle::class, $bundle);
    }
} 