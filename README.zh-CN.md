# Symfony Cache HotKey Bundle

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.1-blue.svg)](https://www.php.net/)  
[![Symfony Version](https://img.shields.io/badge/Symfony-%3E%3D6.4-green.svg)](https://symfony.com/)  
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)  
[![Build Status](https://github.com/tourze/php-monorepo/workflows/CI/badge.svg)]
(https://github.com/tourze/php-monorepo/actions)  
[![Coverage Status](https://coveralls.io/repos/github/tourze/php-monorepo/badge.svg?branch=master)]
(https://coveralls.io/github/tourze/php-monorepo?branch=master)

[English](README.md) | [中文](README.zh-CN.md)

一个专为解决缓存热点（Hot Key）问题而设计的 Symfony Bundle，
通过自动分散热点 key 到多个子 key，有效防止单一缓存节点过载。

---

## 功能特性

- 自动识别以 `hotkey_` 开头的热点 key
- 热点 key 内容自动复制到多个子 key，实现读写负载均衡
- 读取时随机选择一个子 key 返回
- 支持缓存体积监控与告警
- 支持缓存标签失效日志

## 安装说明

### 系统要求
- PHP 8.1 及以上
- Symfony 6.4 及以上
- PSR-3 Logger
- Symfony Cache 组件

### 使用 Composer 安装
```bash
composer require tourze/symfony-cache-hotkey-bundle
```

## 快速开始

### Bundle 注册
在 `config/bundles.php` 中添加 Bundle：

```php
return [
    // ... 其他bundles
    Tourze\Symfony\CacheHotKey\CacheHotKeyBundle::class => ['all' => true],
];
```

### 标记热点 Key

只需在缓存 key 前加上 `hotkey_` 前缀即可：

```php
// 普通缓存
$cache->get('normal_key', fn() => 'value');

// 热点缓存
$cache->get('hotkey_popular_data', fn() => 'value');
```

该 Bundle 会自动：

1. 将热点 key 内容复制到 10 个子 key（hotkey_popular_data_0_split ~ hotkey_popular_data_9_split）
2. 读取时随机选择一个子 key
3. 删除主 key 时自动清理所有子 key

## 配置说明

### 主要配置项（环境变量）

```dotenv
CACHE_MARSHALLER_WARNING_VALUE_SIZE=1048576 # 缓存序列化警告阈值（字节）
CACHE_MARSHALLER_WARNING_DEMO_SIZE=400      # 缓存内容预览大小（字节）
CACHE_INVALIDATE_TAG_LOG=false              # 是否启用缓存标签失效日志
```

## 高级用法

### 缓存标签失效
- 支持缓存标签失效，且可选日志记录（基于 `CACHE_INVALIDATE_TAG_LOG`）
- 缓存体积超阈值时自动日志告警，便于及时优化数据结构

### 性能优化建议
- 对于高并发场景，建议仅对热点数据使用 `hotkey_` 前缀，避免无效分片
- 可通过调整 `MAX_KEY` 常量自定义分片数量

## 贡献指南

欢迎 Issue 和 PR，代码需遵循 PSR 标准，提交前请通过 PHPUnit 测试。

## 版权和许可

MIT License © Tourze

## 更新日志

详见项目 [Releases](https://github.com/tourze/symfony-cache-hotkey-bundle/releases)
