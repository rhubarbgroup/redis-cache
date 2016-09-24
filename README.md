# Redis Object Cache for WordPress

A persistent object cache backend powered by Redis. Supports [Predis](https://github.com/nrk/predis/), [PhpRedis (PECL)](https://github.com/phpredis/phpredis), [HHVM](https://github.com/facebook/hhvm/tree/master/hphp/system/php/redis), replication, clustering and [WP-CLI](http://wp-cli.org/).

Forked from Eric Mann's and Erick Hitter's [Redis Object Cache](https://github.com/ericmann/Redis-Object-Cache).


## Installation

For detailed installation instructions, please read the [standard installation procedure for WordPress plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

1. Make sure [Redis is installed and running](http://redis.io/topics/quickstart).
2. Install and activate plugin.
3. Enable the object cache under _Settings -> Redis_.
4. If necessary, adjust [connection parameters](http://wordpress.org/extend/plugins/redis-cache/other_notes/).

If your server doesn't support the [WordPress Filesystem API](https://codex.wordpress.org/Filesystem_API), you have to manually copy the `object-cache.php` file from the `/plugins/redis-cache/includes/` directory to the `/wp-content/` directory.


## Connection Parameters

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


## Configuration Parameters

To adjust the configuration, define any of the following constants in your `wp-config.php` file.

* `WP_CACHE_KEY_SALT` (default: not set)

  Set the prefix for all cache keys. Useful in setups where multiple installs share a common `wp-config.php` or `$table_prefix`, to guarantee uniqueness of cache keys.

* `WP_REDIS_MAXTTL` (default: _not set_)

  Set maximum time-to-live (in seconds) for cache keys with an expiration time of `0`.

* `WP_REDIS_GLOBAL_GROUPS` (default: `['blog-details', 'blog-id-cache', 'blog-lookup', 'global-posts', 'networks', 'rss', 'sites', 'site-details', 'site-lookup', 'site-options', 'site-transient', 'users', 'useremail', 'userlogins', 'usermeta', 'user_meta', 'userslugs']`)

  Set the list of network-wide cache groups that should not be prefixed with the blog-id _(Multisite only)_.

* `WP_REDIS_IGNORED_GROUPS` (default: `['counts', 'plugins']`)

  Set the cache groups that should not be cached in Redis.


## Replication & Clustering

To use Replication and Clustering, make sure your server is running PHP7, your setup is using Predis to connect to Redis and you consulted the [Predis documentation](https://github.com/nrk/predis).

For replication use the `WP_REDIS_SERVERS` constant and for clustering the `WP_REDIS_CLUSTER` constant. You can use a named array or an URI string to specify the parameters.

For authentication use the `WP_REDIS_PASSWORD` constant.

### Master-Slave Replication

```php
define( 'WP_REDIS_SERVERS', [
    'tcp://127.0.0.1:6379?database=15&alias=master',
    'tcp://127.0.0.2:6379?database=15&alias=slave-01',
] );
```

### Clustering via Client-side Sharding

```php
define( 'WP_REDIS_CLUSTER', [
    'tcp://127.0.0.1:6379?database=15&alias=node-01',
    'tcp://127.0.0.2:6379?database=15&alias=node-02',
] );
```

### WP-CLI Commands

To use the WP-CLI commands, make sure the plugin is activated:

```
wp plugin activate redis-cache
```

The following commands are supported:

* `wp redis status`

  Show the Redis object cache status and (when possible) client.

* `wp redis enable`

  Enables the Redis object cache. Default behavior is to create the object cache drop-in, unless an unknown object cache drop-in is present.

* `wp redis disable`

  Disables the Redis object cache. Default behavior is to delete the object cache drop-in, unless an unknown object cache drop-in is present.

* `wp redis update-dropin`

  Updates the Redis object cache drop-in. Default behavior is to overwrite any existing object cache drop-in.
