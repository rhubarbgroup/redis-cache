=== Redis Object Cache ===
Contributors: tillkruess
Donate link: https://github.com/sponsors/tillkruss
Tags: redis, predis, phpredis, hhvm, pecl, caching, cache, object cache, performance, replication, clustering, keydb
Requires at least: 3.3
Tested up to: 5.4
Requires PHP: 5.4
Stable tag: 1.6.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A persistent object cache backend powered by Redis. Supports Predis, PhpRedis, HHVM, replication, clustering and WP-CLI.


== Description ==

A persistent object cache backend powered by Redis. Supports [Predis](https://github.com/nrk/predis/), [PhpRedis (PECL)](https://github.com/phpredis/phpredis), [HHVM](https://github.com/facebook/hhvm/tree/master/hphp/system/php/redis), replication, clustering and [WP-CLI](http://wp-cli.org/).

To adjust the connection parameters, prefix cache keys or configure replication/clustering, please see [Other Notes](http://wordpress.org/extend/plugins/redis-cache/other_notes/).

Forked from Eric Mann's and Erick Hitter's [Redis Object Cache](https://github.com/ericmann/Redis-Object-Cache).

= Redis Cache Pro =

A **business class** Redis object cache backend. Truly reliable, highly optimized, fully customizable and with a dedicated engineer when you most need it.

* Rewritten for raw performance
* WordPress object cache API compliant
* Easy debugging & logging
* Cache analytics and preloading
* Fully unit tested (100% code coverage)
* Secure connections with TLS
* Health checks via WordPress, WP CLI & Debug Bar
* Optimized for WooCommerce, Jetpack & Yoast SEO

Learn more about [Redis Cache Pro](https://wprediscache.com/?utm_source=wp-plugin&amp;utm_medium=readme).


== Installation ==

For detailed installation instructions, please read the [standard installation procedure for WordPress plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

1. Make sure [Redis is installed and running](http://redis.io/topics/quickstart).
2. Install and activate plugin.
3. Enable the object cache under _Settings -> Redis_, or in Multisite setups under _Network Admin -> Settings -> Redis_.
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

  * `WP_REDIS_TIMEOUT` (default: `5`)

	  Amount of time in seconds (fractions of a second allowed) to attempt initial connection to Redis server before failing.

  * `WP_REDIS_READ_TIMEOUT` (default: `5`)

	  Amount of time in seconds (fractions of a second allowed) to attempt a read from the Redis server before failing.

  * `WP_REDIS_RETRY_INTERVAL` (default: _not set_)

	  Amount of time in milliseconds to retry a failed connection attempt.


== Configuration Options ==

To adjust the configuration, define any of the following constants in your `wp-config.php` file.

  * `WP_CACHE_KEY_SALT` (default: _not set_)

    Set the prefix for all cache keys. Useful in setups where multiple installs share a common `wp-config.php` or `$table_prefix` to guarantee uniqueness of cache keys.

  * `WP_REDIS_SELECTIVE_FLUSH` (default: _not set_)

    If set to `true`, flushing the cache will only delete keys that are prefixed with `WP_CACHE_KEY_SALT` (instead of emptying the entire Redis database). The selective flush is an atomic `O(n)` operation.

  * `WP_REDIS_MAXTTL` (default: _not set_)

    Set maximum time-to-live (in seconds) for cache keys with an expiration time of `0`.

  * `WP_REDIS_GLOBAL_GROUPS` (default: `['blog-details', 'blog-id-cache', 'blog-lookup', 'global-posts', 'networks', 'rss', 'sites', 'site-details', 'site-lookup', 'site-options', 'site-transient', 'users', 'useremail', 'userlogins', 'usermeta', 'user_meta', 'userslugs']`)

    Set the list of network-wide cache groups that should not be prefixed with the blog-id _(Multisite only)_.

  * `WP_REDIS_IGNORED_GROUPS` (default: `['counts', 'plugins']`)

    Set the cache groups that should not be cached in Redis.

  * `WP_REDIS_UNFLUSHABLE_GROUPS` (default: _not set_)

    Set groups not being flushed during a selective cache flush.

  * `WP_REDIS_DISABLED` (default: _not set_)

    Set to `true` to disable the object cache at runtime.

  * `WP_REDIS_GRACEFUL` (default: _not set_)

    Set to `false` to disable graceful failures and throw exceptions.

  * `WP_REDIS_SERIALIZER` (default: _not set_)

    Use PhpRedis’ built-in serializers. Supported values are `Redis::SERIALIZER_PHP` and `Redis::SERIALIZER_IGBINARY`.

  * `WP_REDIS_IGBINARY` (default: _not set_)

    Set to `true` to enable the [igbinary](https://github.com/igbinary/igbinary) serializer. Ignored when `WP_REDIS_SERIALIZER` is set.

  * `WP_REDIS_DISABLE_BANNERS` (default: _not set_)

    Set to `true` to disable promotions for [Redis Cache Pro](https://wprediscache.com/).

  * `WP_REDIS_DISABLE_COMMENT` (default: _not set_)

    Set to `true` to disable the HTML footer comment and it's optional debugging information when `WP_DEBUG` is enabled.

== Replication & Clustering ==

To use Replication, Sharding or Clustering, make sure your server is running PHP7 or higher (HHVM is not supported) and you consulted the [Predis](https://github.com/nrk/predis) or [PhpRedis](https://github.com/phpredis/phpredis) documentation.

For replication use the `WP_REDIS_SERVERS` constant, for sharding the `WP_REDIS_SHARDS` constant and for clustering the `WP_REDIS_CLUSTER` constant.

For authentication use the `WP_REDIS_PASSWORD` constant.

__Replication (Master-Slave):__

    define( 'WP_REDIS_SERVERS', [
        'tcp://127.0.0.1:6379?database=5&alias=master',
        'tcp://127.0.0.2:6379?database=5&alias=slave-01',
    ] );

__Replication (Redis Sentinel):__

    define( 'WP_REDIS_CLIENT', 'predis' );
    define( 'WP_REDIS_SENTINEL', 'mymaster' );
    define( 'WP_REDIS_SERVERS', [
        'tcp://127.0.0.1:5380',
        'tcp://127.0.0.2:5381',
        'tcp://127.0.0.3:5382',
    ] );

__Sharding:__

    define( 'WP_REDIS_SHARDS', [
        'tcp://127.0.0.1:6379?database=10&alias=shard-01',
        'tcp://127.0.0.2:6379?database=10&alias=shard-02',
        'tcp://127.0.0.3:6379?database=10&alias=shard-03',
    ] );

__Clustering (Redis 3.0+):__

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

= 1.6.1 =

- Fixed issue with footer comment showing during AJAX requests

= 1.6.0 =

- Improved group name sanitization (thanks @naxvog)
- Prevent fatal error when replacing foreign dropin
- Added HTML footer comment with optional debug information
- Removed prefix suggestions

_The HTML footer comment only prints debug information when `WP_DEBUG` is enabled. To disable the comment entirely, set the `WP_REDIS_DISABLE_COMMENT` constant to `true`._

= 1.5.9 =

- Fixed missing `$info` variable assignment in constructor
- Fixed MaxTTL warning condition
- Switched to using default button styles

= 1.5.8 =

- Added warning message about invalid MaxTTL
- Added warning about unmaintained Predis library
- Added suggestion about shorter, human-readable prefixes
- Added Redis Cache Pro compatibility to settings
- Fixed flushing the cache when the prefix contains special characters
- Fixed calling Redis `INFO` when using clusters
- Cleaned up the settings a little bit

= 1.5.7 =

- Added support for PhpRedis TLS connections
- Added support for timeout, read timeout and password when using PhpRedis cluster
- Fixed issue with `INFO` command
- Fixed object cloning when setting cache keys

= 1.5.6 =

- Added object cloning to in-memory cache
- Fixed PHP notice related to `read_timeout` parameter

= 1.5.5 =

Please flush the object cache after updating the drop to v1.5.5 to avoid dead keys filling up Redis memory.

  * Removed lowercasing keys
  * Remove scheduled metrics event
  * Fixed Redis version call when using replication

= 1.5.4 =

  * Removed metrics

= 1.5.3 =

  * Fixed: Call to undefined function `get_plugin_data()`
  * Fixed: Call to undefined method `WP_Object_Cache::redis_version()`

= 1.5.2 =

  * Added Redis version to diagnostics
  * Added `WP_REDIS_DISABLE_BANNERS` constant to disable promotions
  * Fixed an issue with `redis.replicate_commands()`

= 1.5.1 =

This plugin turned 5 years today (Nov 14th) and its only fitting to release the business edition today as well.
[Redis Cache Pro](https://wprediscache.com/) is a truly reliable, highly optimized and easy to debug rewrite of this plugin for SMBs.

  * Added execution times to actions
  * Added `WP_REDIS_VERSION` constant
  * Fixed PhpRedis v3 compatibility
  * Fixed an issue with selective flushing
  * Fixed an issue with `mb_*` functions not existing
  * Replaced Email Address Encoder card with Redis Cache Pro card
  * Gather version metrics for better decision making

= 1.5.0 =

Since Predis isn't maintained any longer, it's highly recommended to switch over to PhpRedis (the Redis PECL extension).

  * Improved Redis key name builder
  * Added support for PhpRedis serializers
  * Added `redis_object_cache_error` action
  * Added timeout, read-timeout and retry configuration
  * Added unflushable groups (defaults to `['userlogins']`)
  * Fixed passwords not showing in server list

= 1.4.3 =

  * Require PHP 5.4 or newer
  * Use pretty print in diagnostics
  * Throw exception if Redis library is missing
  * Fixed cache not flushing for some users
  * Fixed admin issues when `WP_REDIS_DISABLED` is `false`

= 1.4.2 =

  * Added graceful Redis failures and `WP_REDIS_GRACEFUL` constant
  * Improved cluster support
  * Added `redis_cache_expiration` filter
  * Renamed `redis_object_cache_get` filter to `redis_object_cache_get_value`

= 1.4.1 =

  * Fixed potential fatal error related to `wp_suspend_cache_addition()`

= 1.4.0 =

  * Added support for igbinary
  * Added support for `wp_suspend_cache_addition()`

= 1.3.9 =

  * Fixed `WP_REDIS_SHARDS` not showing up in server list
  * Fixed `WP_REDIS_SHARDS` not working when using PECL extension
  * Removed `WP_REDIS_SCHEME` and `WP_REDIS_PATH` leftovers

= 1.3.8 =

  * Switched from single file Predis version to full library

= 1.3.7 =

  * Revert back to single file Predis version

= 1.3.6 =

  * Added support for Redis Sentinel
  * Added support for sharing
  * Switched to PHAR version of Predis
  * Improved diagnostics
  * Added `WP_REDIS_SELECTIVE_FLUSH`
  * Added `$fail_gracefully` parameter to `WP_Object_Cache::__construct()`
  * Always enforce `WP_REDIS_MAXTTL`
  * Pass `$selective` and `$salt` to `redis_object_cache_flush` action
  * Don’t set `WP_CACHE_KEY_SALT` constant

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

= 1.4.2 =

This update renames the `redis_object_cache_get` filter to avoid conflicts. Update your code if necessary.

= 1.4.0 =

This update adds support for igbinary and `wp_suspend_cache_addition()`.

= 1.3.9 =

This update contains fixes for sharding.

= 1.3.8 =

This update contains a critical fix for Predis.

= 1.3.7 =

This update fixes an issue with Predis in some environments.

= 1.3.6 =

This update contains various improvements.

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
