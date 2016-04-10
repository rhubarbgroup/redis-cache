# Changelog

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
* Added `WP_REDIS_CLIENT` constant, to set prefered Redis client
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
