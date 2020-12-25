# Redis Object Cache for WordPress

A persistent object cache backend powered by Redis. Supports [Predis](https://github.com/nrk/predis/), [PhpRedis (PECL)](https://github.com/phpredis/phpredis), [Credis](https://github.com/colinmollenhour/credis), [HHVM](https://github.com/facebook/hhvm/tree/master/hphp/system/php/redis), replication, clustering and [WP-CLI](http://wp-cli.org/).

[![Redis Object Cache screenshots](/.wordpress-org/collage-sm.jpg?raw=true)](/.wordpress-org/collage.png?raw=true)

## Object Cache Pro

A **business class** Redis object cache backend. Truly reliable, highly optimized, fully customizable and with a dedicated engineer when you most need it.

* Rewritten for raw performance
* 100% WordPress API compliant
* Faster serialization and compression
* Easy debugging & logging
* Cache prefetching and analytics
* Fully unit tested (100% code coverage)
* Secure connections with TLS
* Health checks via WordPress & WP CLI
* Optimized for WooCommerce, Jetpack & Yoast SEO

Learn more about [Object Cache Pro](https://objectcache.pro/?utm_source=wp-plugin&amp;utm_medium=readme).

## Installation

For detailed installation instructions, please see the [installation wiki page](https://github.com/rhubarbgroup/redis-cache/wiki/Installation).

## Connection Parameters

By default the object cache drop-in will connect to Redis over TCP at `127.0.0.1:6379` and select database `0`.

To adjust the connection parameters, client, timeouts and intervals, please see the [connection parameters wiki page](https://github.com/rhubarbgroup/redis-cache/wiki/Connection-Parameters).

## Configuration Options

The plugin comes with quite a few configuration options, such as key prefixes, a maximum time-to-live for keys, ignored group and many more.

Please see the [configuration options wiki page](https://github.com/rhubarbgroup/redis-cache/wiki/Configuration-Options) for a full list.

## Replication & Clustering

To use Replication, Sharding or Clustering, make sure your server is running PHP7 or higher (HHVM is not supported) and you consulted the [Predis](https://github.com/nrk/predis) or [PhpRedis](https://github.com/phpredis/phpredis) documentation.

Please see the [replication & clustering wiki page](https://github.com/rhubarbgroup/redis-cache/wiki/Replication-&-Clustering) for more information.

### WP-CLI Commands

To see a list of all available WP-CLI commands, please see the [WP CLI commands wiki page](https://github.com/rhubarbgroup/redis-cache/wiki/WP-CLI-Commands).

### Development

Head over to the [Docker Development wiki page](https://github.com/rhubarbgroup/redis-cache/wiki/Docker-Development) to spin up various Redis setups.
