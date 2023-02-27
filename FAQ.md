# FAQ & Troubleshooting

Answers to common questions and troubleshooting of common errors.

<details>
<summary><h3>Status: <code>Not connected</code></h3></summary>

Did you follow the [installation instructions](https://github.com/rhubarbgroup/redis-cache/blob/develop/INSTALL.md)?

1. Confirm Redis Server installed and running using `redis-cli` 
2. Confirm your `wp-config.php` file contains the correct `WP_REDIS_*` configuration
3. Confirm your `WP_REDIS_*` constants are defined high up in your `wp-config.php` above the lines `/* That's all, stop editing! Happy publishing. */` and `require_once(ABSPATH . 'wp-settings.php');`
</details>

<details>
<summary><h3><code>read error on connection</code> and <code>connection timed out</code></h3></summary>

1. Confirm Redis Server installed and running using `redis-cli` 
2. Confirm your `wp-config.php` file contains the correct `WP_REDIS_*` configuration
3. Confirm your `WP_REDIS_*` constants are defined high up in your `wp-config.php` above the lines `/* That's all, stop editing! Happy publishing. */` and `require_once(ABSPATH . 'wp-settings.php');`
</details>

<details>
<summary><h3><code>NOAUTH Authentication required</code></h3></summary>

You either need to add the `WP_REDIS_PASSWORD` constant to your `wp-config.php` file, or move the constant above higher up in your `wp-config.php` file, above these lines:

```php
/* That's all, stop editing! Happy publishing. */
require_once(ABSPATH . 'wp-settings.php');
```
</details>

<details>
<summary><code>Allowed memory size of ??? bytes exhausted</code></summary>

This can happen when using a persistent object cache. Increase PHP's memory limit.

- https://wordpress.org/documentation/article/common-wordpress-errors/#allowed-memory-size-exhausted
- https://woocommerce.com/document/increasing-the-wordpress-memory-limit/
</details>

<details>
<summary><h3>Cache is flushed constantly<</h3>/summary>

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
