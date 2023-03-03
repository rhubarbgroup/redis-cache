# FAQ & Troubleshooting

When in doubt try flushing the cache, you'd be surprised how often this resolves issues. Welcome to WordPress ✌️

<details>
<summary>Plugin is incompatible with OtherPlugin</summary>

Unfortunately many plugin authors don't bother testing their plugins with a persistent object cache. If you’re experiencing a compatibility issue with another plugin in combination with Redis Object Cache, please contact the support team of the **other plugin** regarding the issue.

This plugin is **not the issue**, it's just providing WordPress with `wp_cache_*()` functions for persistent caching.
</details>

<details>
<summary>Status: <code>Not connected</code></summary>

This means that either [Redis Server](https://redis.io) is not installed and running, or the plugin is not configured correctly. 
    
First, make sure you followed the [installation instructions](https://github.com/rhubarbgroup/redis-cache/blob/develop/INSTALL.md) and that Redis Server is up and running:

```bash
redis-cli PING

# or specify a custom host/port
redis-cli -h 127.0.0.1 -p 6379 PING
```

If Redis Server is not installed and running, follow the installation instructions, or ask your hosting company for assistance.

Next, make sure confirm the `wp-config.php` file contains the correct `WP_REDIS_*` constants and configuration constants are defined high up in the `wp-config.php` **above the lines**:

```php
/* That's all, stop editing! Happy publishing. */
require_once(ABSPATH . 'wp-settings.php');
```

If you moved all constants above those lines and the plugin still shows `Not Connected`, double check your [connection options](https://github.com/rhubarbgroup/redis-cache#connections), or ask your hosting provider for assistance.
</details>

<details>
<summary><code>connection timed out</code> and <code>read error on connection</code></summary>
If the error occurs rarely, ignore it, Redis Server is having a hiccup. If it persists, read the answer to "Status: <code>Not connected</code>".
</details>

<details>
<summary>My site is getting redirected another domain</summary>

That happens when the same `WP_REDIS_DATABASE` index is used for multiple WordPress installations. You **MUST** set a separate `WP_REDIS_DATABASE` and `WP_REDIS_PREFIX` for each domain to avoid data collision.

Once your site is being redirected, you **MUST** flush the entire Redis Server using the `FLUSHALL` command to recover from this error:

```bash
redis-cli -h 127.0.01 -p 6379 FLUSHALL
```
</details>

<details>
<summary>I'm getting <code>404</code> errors</summary>

This may be an issue with WordPress 6.1's [query caching](https://make.wordpress.org/core/2022/10/07/improvements-to-wp_query-performance-in-6-1/) feature, which you can disable by creating your own [Must Use Plugin](https://wordpress.org/documentation/article/must-use-plugins/) containing this snippet:

```php
add_action( 'parse_query', function ( $wp_query ) {
    $wp_query->query_vars[ 'cache_results' ] = false;
} );
```

</details>

<details>
<summary>WordPress is slower with Redis Object Cache enabled</summary>

This should never always means **something is broken**.

1. Does the plugin show "Connected"?
2. Is Redis Server responding too slowly? Run `redis-cli --latency-history` to find out.
3. Is Redis Server maxing out it's RAM or CPU?
</details>

<details>
<summary><code>NOAUTH Authentication required</code></summary>

You either need to add the `WP_REDIS_PASSWORD` constant to your `wp-config.php` file, or move the constant above higher up in your `wp-config.php` file, above these lines:

```php
/* That's all, stop editing! Happy publishing. */
require_once(ABSPATH . 'wp-settings.php');
```
</details>

<details>
<summary><code>Allowed memory size of 1337 bytes exhausted</code></summary>

This can happen when using a persistent object cache. Increase PHP's memory limit.

- <https://wordpress.org/documentation/article/common-wordpress-errors/#allowed-memory-size-exhausted>
- <https://woocommerce.com/document/increasing-the-wordpress-memory-limit/>
</details>

<details>
<summary><code>OOM command not allowed</code></summary>

This can happen when Redis Server runs out of memory and no `maxmemory-policy` was set in the `redis.conf`.

- <https://aws.amazon.com/premiumsupport/knowledge-center/oom-command-not-allowed-redis/>

Alternatively, you can set the `WP_REDIS_MAXTTL` constant to something relatively low (like `3600` seconds) and flush the cache.
</details>

<details>
<summary>Unable to flush the cache</summary>

If your site is unreachable, you can flush the cache without access to the WordPress dashboard. 

Try running `wp cache flush`, or using `redis-cli` directly:

```bash
redis-cli -h 127.0.01 -p 6379 FLUSHALL
```

Alternatively, you can use a desktop client like [Medis](https://getmedis.com) or [RedisInsight](https://redis.com/redis-enterprise/redis-insight/) to connect to your Redis Server and flush it by executing `FLUSHALL`.
</details>

<details>
<summary>Cache is flushed constantly</summary>

If you don't see metrics building up, or your site is not getting faster, you might have an active plugin that flushes the object cache frequently. To diagnose this issue you can use the following snippet to find the source of the cache flush:

```php
add_action(
    'redis_object_cache_flush',
    function( $results, $delay, $selective, $salt, $execute_time ) {
        ob_start();
        echo date( 'c' ) . PHP_EOL;
        debug_print_backtrace();
        var_dump( func_get_args() );
        error_log( ABSPATH . '/redis-cache-flush.log', 3, ob_get_clean() );
    }, 10, 5
);
```

Once you found the plugin responsible by checking `redis-cache-flush.log`, you can contact the plugin author(s) and reporting the issue.
</details>
