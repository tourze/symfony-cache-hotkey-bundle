<?php

namespace Tourze\Symfony\CacheHotKey\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\Symfony\CacheHotKey\DependencyInjection\CacheHotKeyExtension;

/**
 * @internal
 */
#[CoversClass(CacheHotKeyExtension::class)]
final class CacheHotKeyExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private CacheHotKeyExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = new CacheHotKeyExtension();
    }

    public function testExtensionIsInstanceOfExtension(): void
    {
        $this->assertInstanceOf(
            Extension::class,
            $this->extension
        );
    }
}
