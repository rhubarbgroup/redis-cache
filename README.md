# Redis Object Cache for WordPress

A persistent object cache backend powered by Redis. Supports [Predis](https://github.com/predis/predis/), [PhpRedis (PECL)](https://github.com/phpredis/phpredis), [Relay](https://relaycache.com), replication, sentinels, clustering and [WP-CLI](http://wp-cli.org/).

[![Redis Object Cache screenshots](/.wordpress-org/collage-sm.jpg?raw=true)](/.wordpress-org/collage.png?raw=true)

## Object Cache Pro

A **business class** Redis object cache backend. Truly reliable, highly optimized, fully customizable and with a dedicated engineer when you most need it.

* Rewritten for raw performance
* 100% WordPress API compliant
* Faster serialization and compression
* Easy debugging & logging
* Cache prefetching and advanced analytics
* Fully unit tested (100% code coverage)
* Optimized for WooCommerce, Jetpack & Yoast SEO
* [And much more...](https://objectcache.pro/?ref=oss&amp;utm_source=wp-plugin&amp;utm_medium=readme)

## Installation

To get started, please see the [INSTALL.md](https://github.com/rhubarbgroup/redis-cache/blob/develop/INSTALL.md).

## Configuration

The plugin comes with quite a few configuration options, such as key prefixes, a maximum time-to-live for keys, ignored group and many more.

Please see the [configuration options wiki page](https://github.com/rhubarbgroup/redis-cache/wiki/Configuration-Options) for a full list.

## WP CLI commands

Redis Object Cache has various WP CLI commands, for more information run `wp help redis`.

| Command                  | Description                                   |
| ------------------------ | --------------------------------------------- |
| `wp redis status`        | Shows the object cache status and diagnostics |
| `wp redis enable`        | Enables the object cache                      |
| `wp redis disable`       | Disables the object cache                     |
| `wp redis update-dropin` | Updates the object cache drop-in              |

## Actions & Filters

Redis Object Cache has various hooks and the commonly used ones are listed below.

| Filter / Action                         | Description                                       |
| --------------------------------------- | ------------------------------------------------- |
| `redis_cache_expiration`                | Filters the cache expiration for individual keys  |
| `redis_cache_validate_dropin`           | Filters whether the drop-in is valid              |
| `redis_cache_add_non_persistent_groups` | Filters the groups to be marked as non persistent |

## Replication

Redis Object Cache offers various replication, sharding, cluster and sentinel setups to users with advanced technical knowledge of Redis and PHP, that have consulted the [Predis](https://github.com/predis/predis) or [PhpRedis](https://github.com/phpredis/phpredis) documentation.

<details>
<summary>Replication</summary>

```php
define( 'WP_REDIS_CLIENT', 'predis' );

define( 'WP_REDIS_SERVERS', [
    'tcp://127.0.0.1:6379?database=5&alias=master',
    'tcp://127.0.0.2:6379?database=5&alias=replica-01',
] );
```

</details>

<details>
<summary>Sharding</summary>

```php
define( 'WP_REDIS_CLIENT', 'phpredis' );

define( 'WP_REDIS_SHARDS', [
    'tcp://127.0.0.1:6379?database=10&alias=shard-01',
    'tcp://127.0.0.2:6379?database=10&alias=shard-02',
    'tcp://127.0.0.3:6379?database=10&alias=shard-03',
] );
```

</details>

<details>
<summary>Redis Sentinel</summary>

```php
define( 'WP_REDIS_CLIENT', 'predis' );

define( 'WP_REDIS_SENTINEL', 'my-sentinel' );
define( 'WP_REDIS_SERVERS', [
    'tcp://127.0.0.1:5380',
    'tcp://127.0.0.2:5381',
    'tcp://127.0.0.3:5382',
] );
```

</details>

<details>
<summary>Redis Cluster</summary>

```php
define( 'WP_REDIS_CLUSTER', [
    'tcp://127.0.0.1:6379?alias=node-01',
    'tcp://127.0.0.2:6379?alias=node-02',
    'tcp://127.0.0.3:6379?alias=node-03',
] );
```

</details>
