# Symfony Cache HotKey Bundle

一个用于解决缓存热点问题的Symfony Bundle，通过自动将热点key分散到多个子key中来避免单个缓存节点被打爆的问题。

A Symfony Bundle designed to solve cache hotkey issues by automatically distributing hot keys across multiple sub-keys to prevent overloading of single cache nodes.

## 功能特性 | Features

- 自动识别热点key (以 `hotkey_` 开头的key会被识别为热点key)
- 将热点key的内容自动复制到多个子key中
- 读取时随机选择一个子key返回，实现负载均衡
- 支持缓存大小监控告警
- 支持缓存标签失效日志

- Automatic hot key detection (keys starting with `hotkey_` are identified as hot keys)
- Automatically replicates hot key content across multiple sub-keys
- Random sub-key selection during reads for load balancing
- Cache size monitoring and alerting
- Cache tag invalidation logging

## 安装 | Installation

```bash
composer require tourze/symfony-cache-hotkey-bundle
```

## 配置 | Configuration

在环境变量中可以配置以下参数：

The following parameters can be configured via environment variables:

```dotenv
# 缓存序列化警告阈值（字节）| Cache serialization warning threshold (bytes)
CACHE_MARSHALLER_WARNING_VALUE_SIZE=1048576

# 缓存内容预览大小（字节）| Cache content preview size (bytes)
CACHE_MARSHALLER_WARNING_DEMO_SIZE=400

# 是否启用缓存标签失效日志 | Enable cache tag invalidation logging
CACHE_INVALIDATE_TAG_LOG=false
```

## 使用方法 | Usage

要将一个key标记为热点key，只需要在key前面加上 `hotkey_` 前缀：

To mark a key as a hot key, simply add the `hotkey_` prefix to the key:

```php
// 普通缓存使用 | Normal cache usage
$cache->get('normal_key', fn() => 'value');

// 热点key缓存使用 | Hot key cache usage
$cache->get('hotkey_popular_data', fn() => 'value');
```

这个Bundle会自动：
1. 将热点key的内容复制到10个子key中（hotkey_popular_data_0_split 到 hotkey_popular_data_9_split）
2. 读取时随机选择一个子key返回
3. 当主key被删除时，自动清理所有子key

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
