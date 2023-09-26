# Installing Redis Object Cache

If you manage plugins using Composer, follow [these instructions](#composer-instructions).

## 1. Installing Redis server

This plugin requires Redis as the persistent object cache backend, so first, make sure [Redis Server](https://redis.io) is installed and running. You can [install it yourself](http://redis.io/topics/quickstart), or ask your hosting company for assistance.

## 2. Installing the plugin

Next, install the `Redis Object Cache` plugin via the WordPress Dashboard, or using Composer. For detailed installation instructions, please read the [standard installation procedure for WordPress plugins](https://wordpress.org/documentation/article/manage-plugins/#finding-and-installing-plugins-1).

## 3. Configuring the plugin

After installing and activating the plugin, go to `WordPress -> Settings -> Redis` or `Network Admin -> Settings -> Redis` on Multisite networks. There, enable the cache and check if the plugin can connect automatically.

If not, you must edit the `wp-config.php` file in your `/wp-content` directory. By default the object cache will connect to Redis Server over TCP at `127.0.0.1:6379` and use database `0`,
if you see `Status: Not connected` either ask your hosting provider for assistance, or [configure the connection yourself](https://github.com/rhubarbgroup/redis-cache/#configuration).

A good starting configuration is:

```php
// adjust Redis host and port if necessary 
define( 'WP_REDIS_HOST', '127.0.0.1' );
define( 'WP_REDIS_PORT', 6379 );

// change the prefix and database for each site to avoid cache data collisions
define( 'WP_REDIS_PREFIX', 'my-moms-site' );
define( 'WP_REDIS_DATABASE', 0 ); // 0-15

// reasonable connection and read+write timeouts
define( 'WP_REDIS_TIMEOUT', 1 );
define( 'WP_REDIS_READ_TIMEOUT', 1 );
```

When editing the `wp-config.php` file, it is important that `WP_REDIS_*` constants are defined high up in the file, above these lines:

```php
/* That's all, stop editing! Happy publishing. */
require_once(ABSPATH . 'wp-settings.php');
```

For more connection examples see [Connections](https://github.com/rhubarbgroup/redis-cache/#connections) and [Scaling](https://github.com/rhubarbgroup/redis-cache/#scaling) sections.

## Composer instructions

If you manage plugins using Composer follow these steps:

```bash
# Install the plugin:
composer require wpackagist-plugin/redis-cache

# Next, enable the drop-in:
wp redis enable

# Check the connection:
wp redis status

# Configure the plugin
wp config set WP_REDIS_HOST "127.0.0.1"
wp config set WP_REDIS_PORT "6379"
wp config set WP_REDIS_DATABASE "15"
```

- [Configuration options](https://github.com/rhubarbgroup/redis-cache/#configuration)
- [Connection examples](https://github.com/rhubarbgroup/redis-cache/#connections)
- [Scaling and replication](https://github.com/rhubarbgroup/redis-cache/#scaling)
