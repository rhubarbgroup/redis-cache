# Installing Redis Object Cache

If you manage plugins using Composer, follow [these instructions](#composer-instructions).

## 1. Installing Redis server

This plugin requires Redis as the persistent object cache backend, so first, make sure [Redis Server](https://redis.io) is installed and running. You can [install it yourself](http://redis.io/topics/quickstart), or ask your hosting company for assistance.

## 2. Installing the plugin

Next, install the `Redis Object Cache` plugin via the WordPress Dashboard, or using Composer. For detailed installation instructions, please read the [standard installation procedure for WordPress plugins](https://wordpress.org/documentation/article/manage-plugins/#finding-and-installing-plugins-1).

## 3. Configuring the plugin

After installing and activating the plugin, go to `WordPress -> Settings -> Redis` or `Network Admin -> Settings -> Redis` on Multisite networks.

Next, enable the cache and check if the plugin can connect automatically, or if you need to configure the connection to Redis. By default the object cache will connect to Redis over TCP at `127.0.0.1:6379` and use database `0`.

To adjust the connection parameters and configuration options please see the [README](https://github.com/rhubarbgroup/redis-cache/blob/develop/README.md).

A good starting configuration is:

```php
// adjust Redis host and port if necessary 
define( 'WP_REDIS_HOST', '127.0.0.1' );
define( 'WP_REDIS_PORT', 6379 );

// TODO: mention socket connections...

// change the database (0-15) for each site to avoid cache data collisions
define( 'WP_REDIS_DATABASE', 0 );

// reasonable connection and read+write timeouts
define( 'WP_REDIS_TIMEOUT', 1 );
define( 'WP_REDIS_READ_TIMEOUT', 1 );
```

## 4. Common pitfalls

1. Using the same Redis database for multiple WordPress installations. Data will conflict. Use `WP_REDIS_DATABASE` or `WP_REDIS_PREFIX`.
2. Defining `WP_REDIS_*` constants too late:

Find the line:

```php
/* That's all, stop editing! Happy publishing. */

require_once(ABSPATH . 'wp-settings.php');
```

## Composer instructions

If you manage plugins using Composer follow these s

```bash
# Install the plugin:
composer require wpackagist-plugin/redis-cache

# Next, enable the drop-in:
wp redis enable

# Check the connection:
wp redis status
```

TODO: configuration....
