# Symfony Cache HotKey Bundle

A Symfony Bundle designed to solve cache hotkey issues by automatically distributing hot keys across multiple sub-keys, effectively preventing a single cache node from being overloaded.

---

## Features

- Automatically detects hot keys (keys starting with `hotkey_`)
- Replicates hot key content to multiple sub-keys for load balancing
- Randomly selects a sub-key for reads
- Supports cache size monitoring and alerting
- Supports cache tag invalidation logging

## Installation

### Requirements
- PHP 8.1 or higher
- Symfony 6.4 or higher
- PSR-3 Logger
- Symfony Cache component

### Composer Install
```bash
composer require tourze/symfony-cache-hotkey-bundle
```

## Quick Start

### Marking a Hot Key
Simply add the `hotkey_` prefix to your cache key:

```php
// Normal cache usage
$cache->get('normal_key', fn() => 'value');

// Hot key cache usage
$cache->get('hotkey_popular_data', fn() => 'value');
```

This bundle will automatically:
1. Replicate the hot key content to 10 sub-keys (`hotkey_popular_data_0_split` to `hotkey_popular_data_9_split`)
2. Randomly select a sub-key when reading
3. Clean up all sub-keys when the main key is deleted

### Main Configuration (Environment Variables)
```dotenv
CACHE_MARSHALLER_WARNING_VALUE_SIZE=1048576 # Cache serialization warning threshold (bytes)
CACHE_MARSHALLER_WARNING_DEMO_SIZE=400      # Cache content preview size (bytes)
CACHE_INVALIDATE_TAG_LOG=false              # Enable cache tag invalidation logging
```

## Documentation

- Supports cache tag invalidation with optional logging (`CACHE_INVALIDATE_TAG_LOG`)
- Automatically logs a warning if cache value size exceeds threshold, helping you optimize data structures

## Performance Tips

- For high concurrency scenarios, only use the `hotkey_` prefix for truly hot data to avoid unnecessary sharding
- You can customize the number of shards by adjusting the `MAX_KEY` constant

## Contributing

Issues and PRs are welcome. Please follow PSR coding standards and ensure PHPUnit tests pass before submitting.

## License

MIT License © Tourze

## Changelog

See [Releases](https://github.com/tourze/symfony-cache-hotkey-bundle/releases) for details

This Bundle will automatically:
1. Replicate hot key content across 10 sub-keys (from hotkey_popular_data_0_split to hotkey_popular_data_9_split)
2. Randomly select a sub-key when reading
3. Clean up all sub-keys when the main key is deleted

## 要求 | Requirements

- PHP 8.1+
- Symfony 6.4+
- PSR-3 Logger
- Symfony Cache Component

## License

MIT License
