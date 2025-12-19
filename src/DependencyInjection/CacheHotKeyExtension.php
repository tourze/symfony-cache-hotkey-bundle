<?php

namespace Tourze\Symfony\CacheHotKey\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

final class CacheHotKeyExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
