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

To get started, please see the [installation instructions](https://github.com/rhubarbgroup/redis-cache/blob/develop/INSTALL.md).

## FAQ & Troubleshooting

Answers to common questions and troubleshooting of common errors can be found in the [FAQ](https://github.com/rhubarbgroup/redis-cache/blob/develop/FAQ.md).

## Configuration

The plugin comes with vast set of configuration options.

| Configuration constant               | Default     | Description                                   |
| ------------------------------------ | ----------- | --------------------------------------------- |
| `WP_REDIS_HOST`                      | `127.0.0.1` | The hostname of the Redis server |
| `WP_REDIS_PORT`                      | `6379`      | The port of the Redis server |
| `WP_REDIS_PATH`                      |             | The path to the unix socket of the Redis server |
| `WP_REDIS_SCHEME`                    | `tcp`       | The scheme used to connect: `tcp` or `unix` |
| `WP_REDIS_DATABASE`                  | `0`         | The database used by the cache: `0-15` |
| `WP_REDIS_PREFIX`                    |             | The prefix used for all cache keys to avoid data collisions, replaces `WP_CACHE_KEY_SALT` |
| `WP_REDIS_PASSWORD`                  |             | The password of the Redis server, supports ACLs array: `['username', 'password']` |
| `WP_REDIS_MAXTTL`                    | `0`         | The maximum time-to-live of cache keys |
| `WP_REDIS_CLIENT`                    |             | The client used to communicate with Redis: `predis`, `phpredis` or `relay` |
| `WP_REDIS_TIMEOUT`                   | `1`         | The connection timeout in seconds |
| `WP_REDIS_READ_TIMEOUT`              | `1`         | The timeout in seconds when reading/writing  |
| `WP_REDIS_IGNORED_GROUPS`            | `[]`        | Groups that should not be cached between requests in Redis |

<details>
<summary>Advanced configuration options</summary>

| Configuration constant               | Default     | Description                                   |
| ------------------------------------ | ----------- | --------------------------------------------- |
| `WP_CACHE_KEY_SALT`                  |             | Deprecated. Replaced by `WP_REDIS_PREFIX` |
| `WP_REDIS_RETRY_INTERVAL`            |             | The number of milliseconds between retries |
| `WP_REDIS_GLOBAL_GROUPS`             | `[]`        | Additional groups that are considered global on multisite networks |
| `WP_REDIS_METRICS_MAX_TIME`          | `3600`      | The maximum number of seconds metrics should be stored |
| `WP_REDIS_IGBINARY`                  | `false`     | Whether to use the igbinary PHP extension for serialization |
| `WP_REDIS_SERIALIZER`                |             | The PhpRedis/Relay serializer used for data serialization |
| `WP_REDIS_DISABLED`                  | `false`     | Emergency switch to bypass the object cache without deleting the drop-in |
| `WP_REDIS_DISABLE_ADMINBAR`          | `false`     | Disables admin bar display |
| `WP_REDIS_DISABLE_METRICS`           | `false`     | Disables metrics collection and display |
| `WP_REDIS_DISABLE_BANNERS`           | `false`     | Disables promotional banners |
| `WP_REDIS_DISABLE_DROPIN_AUTOUPDATE` | `false`     | Disables the drop-in auto-update |
| `WP_REDIS_SSL_CONTEXT`               | `[]`        | TLS connection options for `tls` or `rediss` scheme |

</details>

<details>
<summary><em>Unsupported</em> configuration options</summary>

Options that exist, but **should not**, **may break without notice** in future releases and **won't receive any support** whatsoever from our team:

| Configuration constant        | Default     | Description                                                         |
| ----------------------------- | ----------- | ------------------------------------------------------------------- |
| `WP_REDIS_GRACEFUL`           | `false`     | Prevents exceptions from being thrown, but can cause data coruption |
| `WP_REDIS_SELECTIVE_FLUSH`    | `false`     | Uses terribly slow Lua script for flushing                          |
| `WP_REDIS_UNFLUSHABLE_GROUPS` | `[]`        | Uses terribly slow Lua script to prevent groups from being flushed  |

</details>

## Connections

<details>
<summary>Connecting over Unix socket</summary>

```php
define( 'WP_REDIS_SCHEME', 'unix' );
define( 'WP_REDIS_PATH', '/var/run/redis.sock' );
```

</details>

<details>
<summary>Connecting over TCP+TLS</summary>

```php
define( 'WP_REDIS_SCHEME', 'tls' );
define( 'WP_REDIS_HOST', 'master.ncit.ameaqx.use1.cache.amazonaws.com' );
define( 'WP_REDIS_PORT', 6379 );
```

Additional TLS/SSL stream connection options for connections can be defined using `WP_REDIS_SSL_CONTEXT`:

```php
define( 'WP_REDIS_SSL_CONTEXT', [
    'verify_host' => false,
    'verify_host_name' => false
]);
```

</details>

## Scaling

Redis Object Cache offers various replication, sharding, cluster and sentinel setups to users with advanced technical knowledge of Redis and PHP, that have consulted the [Predis](https://github.com/predis/predis), [PhpRedis](https://github.com/phpredis/phpredis) or [Relay](https://relay.so/docs) documentation.

<details>
<summary>Relay</summary>

Relay is a next-generation cache that keeps a partial replica of Redis' dataset in PHP's memory for ridiculously fast lookups, especially when Redis Server is not on the same machine as WordPress.

```php
define( 'WP_REDIS_CLIENT', 'relay' );
define( 'WP_REDIS_SERIALIZER', 'igbinary' );

define( 'WP_REDIS_HOST', '127.0.0.1' );
define( 'WP_REDIS_PORT', 6379 );

// when using Relay, each WordPress installation
// MUST a dedicated Redis database and unique prefix
define( 'WP_REDIS_DATABASE', 0 );
define( 'WP_REDIS_PREFIX', 'db3:' );
```

</details>

<details>
<summary>Replication</summary>

<https://redis.io/docs/management/replication/>

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

This is a PhpRedis specific feature using [`RedisArray`](https://github.com/phpredis/phpredis/blob/develop/array.md).

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

<https://redis.io/docs/management/sentinel/>

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

<https://redis.io/docs/management/scaling/>

```php
define( 'WP_REDIS_CLUSTER', [
    'tcp://127.0.0.1:6379?alias=node-01',
    'tcp://127.0.0.2:6379?alias=node-02',
    'tcp://127.0.0.3:6379?alias=node-03',
] );
```

</details>

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
