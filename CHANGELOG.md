# Changelog

## [Unreleased]

- Fixed logic of prefix suggestion

## 1.5.9

- Fixed missing `$info` variable assignment in constructor
- Fixed MaxTTL warning condition
- Switched to using default button styles

## 1.5.8

- Added warning message about invalid MaxTTL
- Added warning about unmaintained Predis library
- Added suggestion about shorter, human-readable prefixes
- Added Redis Cache Pro compatibility to settings
- Fixed flushing the cache when the prefix contains special characters
- Fixed calling Redis `INFO` when using clusters
- Cleaned up the settings a little bit

## 1.5.7

- Added support for PhpRedis TLS connections
- Added support for timeout, read timeout and password when using PhpRedis cluster
- Fixed issue with `INFO` command
- Fixed object cloning when setting cache keys

## 1.5.6

- Added object cloning to in-memory cache
- Fixed PHP notice related to `read_timeout` parameter

## 1.5.5

Please flush the object cache after updating the drop to v1.5.5 to avoid dead keys filling up Redis memory.

- Removed lowercasing keys
- Remove scheduled metrics event
- Fixed Redis version call when using replication

## 1.5.4
- Removed metrics

## 1.5.3

- Fixed: Call to undefined function `get_plugin_data()`
- Fixed: Call to undefined method `WP_Object_Cache::redis_version()`

## 1.5.2

* Added Redis version to diagnostics
* Added `WP_REDIS_DISABLE_BANNERS` constant to disable promotions
* Fixed an issue with `redis.replicate_commands()`

## 1.5.1

This plugin turned 5 years today (Nov 14th) and its only fitting to release the business edition today as well. [Redis Cache Pro](https://wprediscache.com/) is a truly reliable, highly optimized and easy to debug rewrite of this plugin for SMBs.

* Added execution times to actions
* Added `WP_REDIS_VERSION` constant
* Fixed PhpRedis v3 compatibility
* Fixed an issue with selective flushing
* Fixed an issue with `mb_*` functions not existing
* Replaced Email Address Encoder card with Redis Cache Pro card
* Gather version metrics for better decision making

## 1.5.0

Since Predis isn't maintained any longer, it's highly recommended to switch over to PhpRedis (the Redis PECL extension).

* Improved Redis key name builder
* Added support for PhpRedis serializers
* Added `redis_object_cache_error` action
* Added timeout, read-timeout and retry configuration
* Added unflushable groups (defaults to `['userlogins']`)
* Fixed passwords not showing in server list

## 1.4.3

* Require PHP 5.4 or newer
* Use pretty print in diagnostics
* Throw exception if Redis library is missing
* Fixed cache not flushing for some users
* Fixed admin issues when `WP_REDIS_DISABLED` is `false`

## 1.4.2

* Added graceful Redis failures and `WP_REDIS_GRACEFUL` constant
* Improved cluster support
* Added `redis_cache_expiration` filter
* Renamed `redis_object_cache_get` filter to `redis_object_cache_get_value`

## 1.4.1

* Fixed potential fatal error related to `wp_suspend_cache_addition()`

## 1.4.0

* Added support for igbinary
* Added support for `wp_suspend_cache_addition()`

## 1.3.9

* Fixed `WP_REDIS_SHARDS` not showing up in server list
* Fixed `WP_REDIS_SHARDS` not working when using PECL extension
* Removed `WP_REDIS_SCHEME` and `WP_REDIS_PATH` leftovers

## 1.3.8

* Switched from single file Predis version to full library

## 1.3.7

* Revert back to single file Predis version

## 1.3.6

* Added support for Redis Sentinel
* Added support for sharing
* Switched to PHAR version of Predis
* Improved diagnostics
* Added `WP_REDIS_SELECTIVE_FLUSH`
* Added `$fail_gracefully` parameter to `WP_Object_Cache::__construct()`
* Always enforce `WP_REDIS_MAXTTL`
* Pass `$selective` and `$salt` to `redis_object_cache_flush` action
* Don’t set `WP_CACHE_KEY_SALT` constant


## 1.3.5

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


## 1.3.4

* Added WP-CLI support
* Show host and port unless scheme is unix
* Updated default global and ignored groups
* Do a cache flush when activating, deactivating and uninstalling


## 1.3.3

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


## 1.3.2

* Make sure `$result` is not `false` in `WP_Object_Cache::get()`


## 1.3.1

* Fixed connection issue


## 1.3

* New admin interface
* Added support for `wp_cache_get()`'s `$force` and `$found` parameter
* Added support for clustering and replication with Predis


## 1.2.3

* UI improvements


## 1.2.2

* Added `redis_object_cache_set` action
* Added `redis_object_cache_get` action and filter
* Prevented duplicated admin status messages
* Load bundled Predis library only if necessary
* Load bundled Predis library using `WP_CONTENT_DIR` constant
* Updated `stats()` method output to be uniform with WordPress


## 1.2.1

* Added `composer.json`
* Added deactivation and uninstall hooks to delete `object-cache.php`
* Added local serialization functions for better `advanced-cache.php` support
* Updated bundled Predis version to `1.0.3`
* Updated heading structure to be semantic


## 1.2

* Added Multisite support
* Moved admin menu under _Settings_ menu
* Fixed PHP notice in `get_redis_client_name()`


## 1.1.1

* Call `select()` and optionally `auth()` if HHVM extension is used


## 1.1

* Added support for HHVM's Redis extension
* Added support for PECL Redis extension
* Added `WP_REDIS_CLIENT` constant, to set preferred Redis client
* Added `WP_REDIS_MAXTTL` constant, to force expiration of cache keys
* Improved `add_or_replace()`, `get()`, `set()` and `delete()` methods
* Improved admin screen styles
* Removed all internationalization/localization from drop-in


## 1.0.2

* Added "Flush Cache" button
* Added support for UNIX domain sockets
* Improved cache object retrieval performance significantly
* Updated bundled Predis library to version `1.0.1`


## 1.0.1

* Load plugin translations
* Hide global admin notices from non-admin users
* Prevent direct file access to `redis-cache.php` and `admin-page.php`
* Colorize "Disable Object Cache" button
* Call `Predis\Client->connect()` to avoid potential uncaught `Predis\Connection\ConnectionException`


## 1.0

* Initial release
