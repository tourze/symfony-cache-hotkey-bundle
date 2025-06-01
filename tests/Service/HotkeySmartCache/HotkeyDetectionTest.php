<?php

namespace Tourze\Symfony\CacheHotKey\Tests\Service\HotkeySmartCache;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Tourze\Symfony\CacheHotKey\Service\HotkeySmartCache;

class HotkeyDetectionTest extends TestCase
{
    private HotkeySmartCache $cache;
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        /** @var AdapterInterface&MockObject $decorated */
        $decorated = $this->createMock(AdapterInterface::class);
        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $this->cache = new HotkeySmartCache($decorated, $logger);
        $this->reflection = new ReflectionClass($this->cache);
    }

    public function testGetHotkeyChildrenKeys_withHotkeyPrefix_returnsChildrenKeys(): void
    {
        $method = $this->reflection->getMethod('getHotkeyChildrenKeys');
        $method->setAccessible(true);

        $result = $method->invoke($this->cache, 'hotkey_test');

        $expected = [
            'hotkey_test_0_split',
            'hotkey_test_1_split',
            'hotkey_test_2_split',
            'hotkey_test_3_split',
            'hotkey_test_4_split',
            'hotkey_test_5_split',
            'hotkey_test_6_split',
            'hotkey_test_7_split',
            'hotkey_test_8_split',
            'hotkey_test_9_split',
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetHotkeyChildrenKeys_withoutHotkeyPrefix_returnsEmptyArray(): void
    {
        $method = $this->reflection->getMethod('getHotkeyChildrenKeys');
        $method->setAccessible(true);

        $result = $method->invoke($this->cache, 'normal_key');

        $this->assertSame([], $result);
    }

    public function testGetHotkeyChildrenKeys_withSplitSuffix_returnsEmptyArray(): void
    {
        $method = $this->reflection->getMethod('getHotkeyChildrenKeys');
        $method->setAccessible(true);

        $result = $method->invoke($this->cache, 'hotkey_test_0_split');

        $this->assertSame([], $result);
    }

    public function testGetHotkeyChildrenKeys_withEmptyKey_returnsEmptyArray(): void
    {
        $method = $this->reflection->getMethod('getHotkeyChildrenKeys');
        $method->setAccessible(true);

        $result = $method->invoke($this->cache, '');

        $this->assertSame([], $result);
    }

    public function testGetHotkeyChildrenKeys_withOnlyHotkeyPrefix_returnsChildrenKeys(): void
    {
        $method = $this->reflection->getMethod('getHotkeyChildrenKeys');
        $method->setAccessible(true);

        $result = $method->invoke($this->cache, 'hotkey_');

        $expected = [
            'hotkey__0_split',
            'hotkey__1_split',
            'hotkey__2_split',
            'hotkey__3_split',
            'hotkey__4_split',
            'hotkey__5_split',
            'hotkey__6_split',
            'hotkey__7_split',
            'hotkey__8_split',
            'hotkey__9_split',
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetHotkeyChildrenKeys_withComplexKey_returnsChildrenKeys(): void
    {
        $method = $this->reflection->getMethod('getHotkeyChildrenKeys');
        $method->setAccessible(true);

        $result = $method->invoke($this->cache, 'hotkey_user:123:profile');

        $expected = [
            'hotkey_user:123:profile_0_split',
            'hotkey_user:123:profile_1_split',
            'hotkey_user:123:profile_2_split',
            'hotkey_user:123:profile_3_split',
            'hotkey_user:123:profile_4_split',
            'hotkey_user:123:profile_5_split',
            'hotkey_user:123:profile_6_split',
            'hotkey_user:123:profile_7_split',
            'hotkey_user:123:profile_8_split',
            'hotkey_user:123:profile_9_split',
        ];

        $this->assertSame($expected, $result);
    }
} 