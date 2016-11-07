=== Redis Object Cache ===
Contributors: tillkruess
Donate link: https://www.paypal.me/tillkruss
Tags: redis, predis, hhvm, pecl, caching, cache, object cache, wp object cache, server, performance, optimize, speed, load, replication, clustering
Requires at least: 3.3
Tested up to: 4.7
Stable tag: 1.3.5
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A persistent object cache backend powered by Redis. Supports Predis, PhpRedis, HHVM, replication, clustering and WP-CLI.


== Description ==

A persistent object cache backend powered by Redis. Supports [Predis](https://github.com/nrk/predis/), [PhpRedis (PECL)](https://github.com/phpredis/phpredis), [HHVM](https://github.com/facebook/hhvm/tree/master/hphp/system/php/redis), replication, clustering and [WP-CLI](http://wp-cli.org/).

To adjust the connection parameters, prefix cache keys or configure replication/clustering, please see [Other Notes](http://wordpress.org/extend/plugins/redis-cache/other_notes/).

Forked from Eric Mann's and Erick Hitter's [Redis Object Cache](https://github.com/ericmann/Redis-Object-Cache).


== Installation ==

For detailed installation instructions, please read the [standard installation procedure for WordPress plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

1. Make sure [Redis is installed and running](http://redis.io/topics/quickstart).
2. Install and activate plugin.
3. Enable the object cache under _Settings -> Redis_.
4. If necessary, adjust [connection parameters](http://wordpress.org/extend/plugins/redis-cache/other_notes/).

If your server doesn't support the [WordPress Filesystem API](https://codex.wordpress.org/Filesystem_API), you have to manually copy the `object-cache.php` file from the `/plugins/redis-cache/includes/` directory to the `/wp-content/` directory.


== Connection Parameters ==

By default the object cache drop-in will connect to Redis over TCP at `127.0.0.1:6379` and select database `0`.

To adjust the connection parameters, define any of the following constants in your `wp-config.php` file.

  * `WP_REDIS_CLIENT` (default: _not set_)

      Specifies the client used to communicate with Redis. Supports `hhvm`, `pecl` and `predis`.

  * `WP_REDIS_SCHEME` (default: `tcp`)

      Specifies the protocol used to communicate with an instance of Redis. Internally the client uses the connection class associated to the specified connection scheme. Supports `tcp` (TCP/IP), `unix` (UNIX domain sockets), `tls` (transport layer security) or `http` (HTTP protocol through Webdis).

  * `WP_REDIS_HOST` (default: `127.0.0.1`)

      IP or hostname of the target server. This is ignored when connecting to Redis using UNIX domain sockets.

  * `WP_REDIS_PORT` (default: `6379`)

      TCP/IP port of the target server. This is ignored when connecting to Redis using UNIX domain sockets.

  * `WP_REDIS_PATH` (default: _not set_)

      Path of the UNIX domain socket file used when connecting to Redis using UNIX domain sockets.

  * `WP_REDIS_DATABASE` (default: `0`)

      Accepts a numeric value that is used to automatically select a logical database with the `SELECT` command.

  * `WP_REDIS_PASSWORD` (default: _not set_)

      Accepts a value used to authenticate with a Redis server protected by password with the `AUTH` command.


== Configuration Parameters ==

To adjust the configuration, define any of the following constants in your `wp-config.php` file.

  * `WP_CACHE_KEY_SALT` (default: _not set_)

    Set the prefix for all cache keys. Useful in setups where multiple installs share a common `wp-config.php` or `$table_prefix`, to guarantee uniqueness of cache keys.

  * `WP_REDIS_MAXTTL` (default: _not set_)

    Set maximum time-to-live (in seconds) for cache keys with an expiration time of `0`.

  * `WP_REDIS_GLOBAL_GROUPS` (default: `['blog-details', 'blog-id-cache', 'blog-lookup', 'global-posts', 'networks', 'rss', 'sites', 'site-details', 'site-lookup', 'site-options', 'site-transient', 'users', 'useremail', 'userlogins', 'usermeta', 'user_meta', 'userslugs']`)

    Set the list of network-wide cache groups that should not be prefixed with the blog-id _(Multisite only)_.

  * `WP_REDIS_IGNORED_GROUPS` (default: `['counts', 'plugins']`)

    Set the cache groups that should not be cached in Redis.

  * `WP_REDIS_DISABLED` (default: _not set_)

    Set to `true` to disable the object cache at runtime.


== Replication & Clustering ==

To use Replication and Clustering, make sure your server is running PHP7, your setup is using Predis to connect to Redis and you consulted the [Predis documentation](https://github.com/nrk/predis).

For replication use the `WP_REDIS_SERVERS` constant and for clustering the `WP_REDIS_CLUSTER` constant. You can use a named array or an URI string to specify the parameters.

For authentication use the `WP_REDIS_PASSWORD` constant.

__Master-Slave Replication Example:__

    define( 'WP_REDIS_SERVERS', [
        'tcp://127.0.0.1:6379?database=15&alias=master',
        'tcp://127.0.0.2:6379?database=15&alias=slave-01',
    ] );


__Clustering via Client-side Sharding Example:__

    define( 'WP_REDIS_CLUSTER', [
        'tcp://127.0.0.1:6379?database=15&alias=node-01',
        'tcp://127.0.0.2:6379?database=15&alias=node-02',
    ] );


== WP-CLI Commands ==

To use the WP-CLI commands, make sure the plugin is activated:

    wp plugin activate redis-cache

The following commands are supported:

  * `wp redis status`

    Show the Redis object cache status and (when possible) client.

  * `wp redis enable`

    Enables the Redis object cache. Default behavior is to create the object cache drop-in, unless an unknown object cache drop-in is present.

  * `wp redis disable`

    Disables the Redis object cache. Default behavior is to delete the object cache drop-in, unless an unknown object cache drop-in is present.

  * `wp redis update-dropin`

    Updates the Redis object cache drop-in. Default behavior is to overwrite any existing object cache drop-in.


== Screenshots ==

1. Plugin settings, connected to a single Redis server.

2. Plugin settings, not connected to a Redis cluster.


== Changelog ==

= 1.3.5 =

  * Added basic diagnostics to admin interface
  * Added `WP_REDIS_DISABLED` constant to disable cache at runtime
  * Prevent "Invalid plugin header" error
  * Return integer from `increment()` and `decrement()` methods
  * Prevent object cache from being instantiated more than once
  * Always separate cache key `prefix` and `group` by semicolon
  * Improved performance of `build_key()`
  * Only apply `redis_object_cache_get` filter if callbacks have been registered
  * Fixed `add_or_replace()` to only set cache key if it doesn't exist
  * Added `redis_object_cache_flush` action
  * Added `redis_object_cache_enable` action
  * Added `redis_object_cache_disable` action
  * Added `redis_object_cache_update_dropin` action

= 1.3.4 =

  * Added WP-CLI support
  * Show host and port unless scheme is unix
  * Updated default global and ignored groups
  * Do a cache flush when activating, deactivating and uninstalling

= 1.3.3 =

  * Updated Predis to `v1.1.1`
  * Added `redis_instance()` method
  * Added `incr()` method alias for Batcache compatibility
  * Added `WP_REDIS_GLOBAL_GROUPS` and `WP_REDIS_IGNORED_GROUPS` constant
  * Added `redis_object_cache_delete` action
  * Use `WP_PLUGIN_DIR` with `WP_CONTENT_DIR` as fallback
  * Set password when using a cluster or replication
  * Show Redis client in `stats()`
  * Change visibility of `$cache` to public
  * Use old array syntax, just in case

= 1.3.2 =

  * Make sure `$result` is not `false` in `WP_Object_Cache::get()`

= 1.3.1 =

  * Fixed connection issue

= 1.3 =

  * New admin interface
  * Added support for `wp_cache_get()`'s `$force` and `$found` parameter
  * Added support for clustering and replication with Predis

= 1.2.3 =

  * UI improvements

= 1.2.2 =

  * Added `redis_object_cache_set` action
  * Added `redis_object_cache_get` action and filter
  * Prevented duplicated admin status messages
  * Load bundled Predis library only if necessary
  * Load bundled Predis library using `WP_CONTENT_DIR` constant
  * Updated `stats()` method output to be uniform with WordPress

= 1.2.1 =

  * Added `composer.json`
  * Added deactivation and uninstall hooks to delete `object-cache.php`
  * Added local serialization functions for better `advanced-cache.php` support
  * Updated bundled Predis version to `1.0.3`
  * Updated heading structure to be semantic

= 1.2 =

  * Added Multisite support
  * Moved admin menu under _Settings_ menu
  * Fixed PHP notice in `get_redis_client_name()`

= 1.1.1 =

  * Call `select()` and optionally `auth()` if HHVM extension is used

= 1.1 =

  * Added support for HHVM's Redis extension
  * Added support for PECL Redis extension
  * Added `WP_REDIS_CLIENT` constant, to set preferred Redis client
  * Added `WP_REDIS_MAXTTL` constant, to force expiration of cache keys
  * Improved `add_or_replace()`, `get()`, `set()` and `delete()` methods
  * Improved admin screen styles
  * Removed all internationalization/localization from drop-in

= 1.0.2 =

  * Added "Flush Cache" button
  * Added support for UNIX domain sockets
  * Improved cache object retrieval performance significantly
  * Updated bundled Predis library to version `1.0.1`

= 1.0.1 =

  * Load plugin translations
  * Hide global admin notices from non-admin users
  * Prevent direct file access to `redis-cache.php` and `admin-page.php`
  * Colorize "Disable Object Cache" button
  * Call `Predis\Client->connect()` to avoid potential uncaught `Predis\Connection\ConnectionException`

= 1.0 =

  * Initial release


== Upgrade Notice ==

= 1.3.5 =

This update contains various changes, including performance improvements and better Batcache compatibility.

= 1.3.4 =

This update contains several improvements, including WP CLI and WordPress 4.6 support.

= 1.3.3 =

This update contains several improvements.

= 1.3.2 =

This update includes a critical fix for PhpRedis.

= 1.3.1 =

This update includes a critical connection issue fix.

= 1.3 =

This update includes a new admin interface and support for clustering and replication with Predis.

= 1.2.3 =

This updated includes several UI improvements.

= 1.2.2 =

This updated includes several bug fixes and improvements.

= 1.2.1 =

This update includes several improvements and compatibility fixes.

= 1.1.1 =

This update fixes critical bugs with the HHVM extension

= 1.1 =

This update includes bug fixes and adds supports for HHVM/PECL Redis extensions.

= 1.0.2 =

This update includes significant speed improvements and support for UNIX domain sockets.

= 1.0.1 =

This update includes several security, user interface and general code improvements.
