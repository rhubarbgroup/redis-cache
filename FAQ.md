# FAQ & Troubleshooting

When in doubt, try flushing the cache. You'd be surprised how often this resolves issues. Welcome to WordPress ✌️

- [HELP! My site is down!1!!11!!11](#help-my-site-is-down11111)
- [Plugin is incompatible with OtherPlugin](#plugin-is-incompatible-with-otherplugin)
- [How can I ignore a cache group?](#how-can-i-ignore-a-cache-group)
- [How can I exclude a page from the cache?](#how-can-i-exclude-a-page-from-the-cache)
- [Status: `Not connected`](#status-not-connected)
- [`connection timed out` and `read error on connection`](#connection-timed-out-and-read-error-on-connection)
- [How do I handle Redis Server outages?](#how-do-i-handle-redis-server-outages)
- [My site is getting redirected to another domain](#my-site-is-getting-redirected-to-another-domain)
- [Are transients stored in Redis?](#are-transients-stored-in-redis)
- [I'm getting `404` errors](#im-getting-404-errors)
- [WordPress is slower with Redis Object Cache enabled](#wordpress-is-slower-with-redis-object-cache-enabled)
- [`NOAUTH Authentication required`](#noauth-authentication-required)
- [`Allowed memory size of 1337 bytes exhausted`](#allowed-memory-size-of-1337-bytes-exhausted)
- [`OOM command not allowed`](#oom-command-not-allowed)
- [Flushing the cache causes timeout](#flushing-the-cache-causes-timeout)
- [Unable to flush the cache](#unable-to-flush-the-cache)
- [Cache is flushed constantly](#cache-is-flushed-constantly)
- [Flushing the cache regularly](#flushing-the-cache-regularly)
- [How can I uninstall the cache?](#how-can-i-uninstall-the-cache)

## HELP! My site is down!1!!11!!11

The easiest way to disable Redis on your site is to delete the `wp-content/object-cache.php` drop-in file. Alternatively, set the `WP_REDIS_DISABLED` constant to `true` in your `wp-config.php` to bypass it.

## Plugin is incompatible with OtherPlugin

Unfortunately, many plugin authors don't test their plugins with a persistent object cache. If you're experiencing a compatibility issue with another plugin in combination with Redis Object Cache, please contact the support team of the **other plugin**.

This plugin is **not the issue**, it only provides WordPress with the `wp_cache_*()` functions for persistent caching.

## How can I ignore a cache group?

Some plugins don't play nicely with a persistent object cache and either cause stale cache data or break functionality. In such cases, you might be able to exclude the plugin's cache groups, so they're only cached in memory for the current request:

```php
define( 'WP_REDIS_IGNORED_GROUPS', [
    'wpseo', // just an example
] );
```

## How can I exclude a page from the cache?

Object caching caches **objects**, not **pages**. Because object caching isn't tied to URLs, you can't exclude a page from it. You also can't exclude the WordPress admin dashboard, because then you risk the cache going stale and even losing data.

If another plugin is causing issues, see [Plugin is incompatible with OtherPlugin](#plugin-is-incompatible-with-otherplugin).

## Status: `Not connected`

This means that either [Redis Server](https://redis.io) isn't installed and running, or the plugin isn't configured correctly.

First, make sure you followed the [installation instructions](https://github.com/rhubarbgroup/redis-cache/blob/develop/INSTALL.md) and that Redis Server is up and running:

```bash
redis-cli PING

# or specify a custom host/port
redis-cli -h 127.0.0.1 -p 6379 PING
```

If Redis Server isn't installed and running, follow the installation instructions or ask your hosting provider for assistance.

Next, make sure your `wp-config.php` contains the correct `WP_REDIS_*` constants, and that they're defined **above** these lines:

```php
/* That's all, stop editing! Happy publishing. */
require_once(ABSPATH . 'wp-settings.php');
```

If the plugin still shows `Not connected`, double-check your [connection options](https://github.com/rhubarbgroup/redis-cache#connections) or ask your hosting provider for assistance.

## `connection timed out` and `read error on connection`

If the error occurs rarely, you can ignore it, Redis Server is just having a hiccup. If it persists, see [Status: `Not connected`](#status-not-connected).

## How do I handle Redis Server outages?

Treat Redis Server as a required service, like MySQL.

On a single server, run Redis Server as a pure cache by disabling persistence in the `redis.conf`:

```
save ""
appendonly no
maxmemory-policy allkeys-lru
```

With persistence enabled, Redis Server reloads an old snapshot after a crash and serves stale data. Without it, Redis Server restarts empty almost instantly and always matches the database.

Also set a `maxmemory` limit that fits your server, so Redis Server can't use up all of its memory. The eviction policy only applies once that limit is set.

If you're running more than one server, use [Redis Sentinel](https://github.com/rhubarbgroup/redis-cache#scaling) for high availability (requires Predis or Credis).

<a name="my-site-is-getting-redirected-another-domain"></a>

## My site is getting redirected to another domain

This happens when multiple WordPress installations use the same `WP_REDIS_DATABASE` index. You **MUST** set a unique `WP_REDIS_DATABASE` and `WP_REDIS_PREFIX` for each site to avoid data collisions.

Once you've fixed the configuration, you **MUST** flush the entire Redis Server using the `FLUSHALL` command to recover:

```bash
redis-cli -h 127.0.0.1 -p 6379 FLUSHALL
```

## Are transients stored in Redis?

Yes. The WordPress [Transients API](https://developer.wordpress.org/apis/transients/) stores transients in Redis instead of the `options` table.

After enabling Redis Object Cache, consider deleting the old transients from the database:

```bash
wp transient delete --all
```

Or using SQL (adjust the `wp_` table prefix if needed):

```sql
DELETE FROM `wp_options`
WHERE `option_name` LIKE '\_transient\_%'
OR `option_name` LIKE '\_site\_transient\_%';
```

## I'm getting `404` errors

This may be caused by the [query caching](https://make.wordpress.org/core/2022/10/07/improvements-to-wp_query-performance-in-6-1/) introduced in WordPress 6.1. You can disable it with a [must-use plugin](https://wordpress.org/documentation/article/must-use-plugins/) containing this snippet:

```php
add_action( 'parse_query', function ( $wp_query ) {
    $wp_query->query_vars[ 'cache_results' ] = false;
} );
```

## WordPress is slower with Redis Object Cache enabled

This should never happen and usually means **something is broken**.

1. Does the plugin show `Connected`?
2. Is Redis Server responding slowly? Run `redis-cli --latency-history` to find out.
3. Is Redis Server maxing out its RAM or CPU?

## `NOAUTH Authentication required`

Redis Server requires a password. Add the `WP_REDIS_PASSWORD` constant to your `wp-config.php` file, or if it's already there, move it above these lines:

```php
/* That's all, stop editing! Happy publishing. */
require_once(ABSPATH . 'wp-settings.php');
```

## `Allowed memory size of 1337 bytes exhausted`

This can happen when using a persistent object cache. Increase PHP's memory limit:

- <https://wordpress.org/documentation/article/common-wordpress-errors/#allowed-memory-size-exhausted>
- <https://woocommerce.com/document/increasing-the-wordpress-memory-limit/>

## `OOM command not allowed`

This happens when Redis Server reaches its `maxmemory` limit and the `maxmemory-policy` is `noeviction` (the default). Set an eviction policy in the `redis.conf`, so Redis Server evicts old keys instead:

```
maxmemory-policy allkeys-lru
```

Alternatively, set the `WP_REDIS_MAXTTL` constant to something relatively low (like `3600` seconds) and flush the cache.

## Flushing the cache causes timeout

This can happen when the Redis Server dataset is large. Consider increasing `WP_REDIS_READ_TIMEOUT` and `WP_REDIS_FLUSH_TIMEOUT` to 5-10 seconds.

Alternatively, starting with Redis 6.2, setting `lazyfree-lazy-user-flush` to `yes` in the `redis.conf` makes flushes asynchronous.

## Unable to flush the cache

If your site is unreachable, you can flush the cache without access to the WordPress dashboard.

Try running `wp cache flush`, or use `redis-cli` directly:

```bash
redis-cli -h 127.0.0.1 -p 6379 FLUSHALL
```

Note that `FLUSHALL` deletes the data of all databases on that Redis Server.

Alternatively, you can use a desktop client like [Medis](https://getmedis.com) or [RedisInsight](https://redis.com/redis-enterprise/redis-insight/) to connect to your Redis Server and flush it by executing `FLUSHALL`.

## Cache is flushed constantly

If you don't see metrics building up, or your site isn't getting faster, you might have an active plugin that flushes the object cache frequently. To find the source of the cache flushes, log a backtrace to the PHP error log whenever the cache is flushed:

```php
add_action( 'redis_object_cache_flush', function ( $results ) {
    ob_start();
    var_dump( $results );
    debug_print_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
    error_log( ob_get_clean() );
} );
```

Once you've found the responsible plugin, report the issue to its author.

## Flushing the cache regularly

Flushing the cache frequently is considered bad practice, but sometimes third-party plugins and themes just don't play nicely with persistent object caches. If their authors refuse to fix their code, you can use [WP-Cron](https://developer.wordpress.org/plugins/cron/) in a must-use plugin to flush the object cache regularly:

```php
if ( ! wp_next_scheduled( 'flush_redis_cache' ) ) {
    wp_schedule_event( time(), 'hourly', 'flush_redis_cache' );
}

add_action( 'flush_redis_cache', 'wp_cache_flush' );
```

## How can I uninstall the cache?

Before [uninstalling the plugin](https://wordpress.org/documentation/article/manage-plugins/#uninstalling-plugins-1), disable the cache under **Settings → Redis**, or run `wp redis disable`.

If you've already removed the plugin, delete the `wp-content/object-cache.php` file manually.
