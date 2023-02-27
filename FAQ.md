# FAQ & Troubleshooting

This page aims to list potential issues while running the plugin and how to diagnose or even remedy these issues.

## "Not connected"

```
Resource temporarily unavailable
Connection timed out
read error on connection
```

## Cache is flushed constantly

<details>
<summary>Redis Sentinel</summary>

### Symptoms
* Metrics show nothing or only small amounts of hits
* Site is not getting faster with the plugin and a correct configuration

### Diagnosing the issue

You might have a plugin active that flushes the object cache frequently or during inopportune moments. To diagnose this issue you can use the following snippet to find the source of the cache flush (please keep in mind that such code should not be used in production environments as it significantly worsen site performance):

```php
add_action( 'redis_object_cache_flush', function( $results, $delay, $selective, $salt, $execute_time ) {
  ob_start();
  echo date( 'c' ) . PHP_EOL;
  debug_print_backtrace();
  var_dump( func_get_args() );
  error_log( ABSPATH . '/redis-cache-flush.log', 3, ob_get_clean() );
}, 10, 5 );
```

The code will print the callstack on every cache flush to a log file in your root WordPress directory or alternatively do nothing if no cache flushes happened so far.

### Remedy
Sadly if you found the plugin responsible for the cache flushes you can only try to contact the plugin developer reporting the issue.

</details>
