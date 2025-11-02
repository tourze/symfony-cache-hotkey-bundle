<?php

declare(strict_types=1);

namespace Tourze\Symfony\CacheHotKey\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Contracts\Service\ResetInterface;

/**
 * 单次记录的缓存数据，如果太多的话可能会造成redis进程阻塞，为此我们加一个数据去判断下大小
 */
#[AsDecorator(decorates: 'cache.default_marshaller')]
#[WithMonologChannel(channel: 'cache_hot_key')]
readonly class CacheMarshaller implements MarshallerInterface, ResetInterface
{
    public function __construct(
        #[AutowireDecorated] private MarshallerInterface $inner,
        private LoggerInterface $logger,
    ) {
    }

    public function marshall(array $values, ?array &$failed): array
    {
        $maxSizeEnv = $_ENV['CACHE_MARSHALLER_WARNING_VALUE_SIZE'] ?? '1048576';
        $demoSizeEnv = $_ENV['CACHE_MARSHALLER_WARNING_DEMO_SIZE'] ?? '400';
        $maxSize = is_numeric($maxSizeEnv) ? (int) $maxSizeEnv : 1048576;
        $demoSize = is_numeric($demoSizeEnv) ? (int) $demoSizeEnv : 400;
        $result = $this->inner->marshall($values, $failed);
        foreach ($result as $k => $v) {
            if (!is_string($v)) {
                continue;
            }
            if (mb_strlen($v) > $maxSize) {
                $this->logger->warning('发现一个数据比较大的缓存数据，请考虑拆分缓存', [
                    'key' => $k,
                    'size' => mb_strlen($v),
                    'demo' => mb_substr($v, 0, $demoSize),
                ]);
            }
        }

        /** @var array<string, string> $result */
        return $result;
    }

    public function unmarshall(string $value): mixed
    {
        return $this->inner->unmarshall($value);
    }

    public function reset(): void
    {
        if ($this->inner instanceof ResetInterface) {
            $this->inner->reset();
        }
    }
}
