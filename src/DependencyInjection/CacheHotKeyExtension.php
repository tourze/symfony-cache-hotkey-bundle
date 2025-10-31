<?php

namespace Tourze\Symfony\CacheHotKey\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class CacheHotKeyExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
