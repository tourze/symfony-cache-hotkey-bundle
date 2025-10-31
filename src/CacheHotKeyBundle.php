<?php

namespace Tourze\Symfony\CacheHotKey;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class CacheHotKeyBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            FrameworkBundle::class => ['all' => true],
            MonologBundle::class => ['all' => true],
        ];
    }
}
