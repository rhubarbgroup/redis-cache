![Build Status](https://github.com/colinmollenhour/credis/actions/workflows/ci.yml/badge.svg)

# Credis

Credis is a lightweight interface to the [Redis](http://redis.io/) key-value store which wraps the [phpredis](https://github.com/nicolasff/phpredis)
library when available for better performance. This project was forked from one of the many redisent forks.

## Getting Started

Credis_Client uses methods named the same as Redis commands, and translates return values to the appropriate
PHP equivalents.

```php
require 'Credis/Client.php';
$redis = new Credis_Client('localhost');
$redis->set('awesome', 'absolutely');
echo sprintf('Is Credis awesome? %s.\n', $redis->get('awesome'));

// When arrays are given as arguments they are flattened automatically
$redis->rpush('particles', array('proton','electron','neutron'));
$particles = $redis->lrange('particles', 0, -1);
```
Redis error responses will be wrapped in a CredisException class and thrown.

Credis_Client also supports transparent command renaming. Write code using the original command names and the
client will send the aliased commands to the server transparently. Specify the renamed commands using a prefix
for md5, a callable function, individual aliases, or an array map of aliases. See "Redis Security":http://redis.io/topics/security for more info.

## Contributing

Please be sure to add tests to cover and new or changed functionality and run the PHP-CS-Fixer to format the code.

```shell
composer require "friendsofphp/php-cs-fixer:^3.13" --dev --no-update -n
composer format
```

## Supported connection string formats

```php
$redis = new Credis_Client(/* connection string */);
```

### Unix socket connection string

`unix:///path/to/redis.sock` 

### TCP connection string

`tcp://host[:port][/persistence_identifier]` 

### TLS connection string

`tls://host[:port][/persistence_identifier]` 

or 

`tlsv1.2://host[:port][/persistence_identifier]`

Before php 7.2, `tls://` only supports TLSv1.0, either `ssl://` or `tlsv1.2` can be used to force TLSv1.2 support.

Recent versions of redis do not support the protocols/cyphers that older versions of php default to, which may result in cryptic connection failures.

#### Enable transport level security (TLS)

Use TLS connection string `tls://127.0.0.1:6379` instead of TCP connection `tcp://127.0.0.1:6379` string in order to enable transport level security.

```php
require 'Credis/Client.php';
$redis = new Credis_Client('tls://127.0.0.1:6379');
$redis->set('awesome', 'absolutely');
echo sprintf('Is Credis awesome? %s.\n', $redis->get('awesome'));

// When arrays are given as arguments they are flattened automatically
$redis->rpush('particles', array('proton','electron','neutron'));
$particles = $redis->lrange('particles', 0, -1);
```

## Clustering your servers

Credis also includes a way for developers to fully utilize the [scalability of Redis cluster](https://redis.io/docs/latest/operate/oss_and_stack/management/scaling/) by using Credis_Cluster which is an adapter for the RedisCluster class from [the Redis extension for PHP](https://github.com/phpredis/phpredis). This also works on [AWS ElastiCatch clusters](https://docs.aws.amazon.com/AmazonElastiCache/latest/dg/Clusters.html).
This feature requires the PHP extension for its functionality. Here is an example how to set up a cluster:

### Basic clustering example
```php
<?php
require 'Credis/Client.php';
require 'Credis/Cluster.php';

$cluster = new Credis_Cluster(
    null, // $clusterName // Optional. Name from redis.ini. See https://github.com/phpredis/phpredis/blob/develop/cluster.md 
    ['redis-node-1:6379', 'redis-node-2:6379', 'redis-node-3:6379'], // $clusterSeeds // don't need all nodes, as it pulls that info from one randomly
    null, // $timeout
    null, // $readTimeout
    false, //$persistentBool
    'TopSecretPassword', // $password
    null, //$username
    null //$tlsOptions
);
$cluster->set('key','value');
echo "Get: ".$cluster->get('key').PHP_EOL;
```
The Credis_Cluster constructor can either take a cluster name (from redis.ini) or a seed of cluster nodes (An array of strings which can be hostnames or IP address, followed by ports). RedisCluster gets cluster information from one of the seeds at random, so we don't need to pass it all the nodes, and don't need to worry if new nodes are added to cluster. 
Many methods of Credis_Cluster are compatible with Credis_Client, but there are some differences.

### Differences between the Credis_Client and Credis_Cluster classes

* RedisCluster currently has limitations like not supporting pipeline or multi. This may be added in the future. See [here](https://github.com/phpredis/phpredis/blob/develop/cluster.md) for details.
* Many methods require an additional parameter to specify which node to run on, and only run on that node, such as saveForNode(), flushDbForNode(), and pingForNode().  To specify the node, the first argument will either be a key which maps to a slot which maps to a node; or it can be an array of ['host': port] for a node.
* Redis clusters do not support select(), as they only have a single database.
* RedisCluster currently has buggy/broken behaviour for pSubscribe and script. This appears to be a bug and hopefully will be fixed in the future.

### Note about tlsOptions for Credis_Cluster
Because of weirdness in the behaviour of the $tlsOptions parameter of Credis_Cluster, when a seed is defined with a URL that starts with tls:// or ssl://, if $tlsOptions is null, then it will still try to connect without TLS, and it will fail.  This odd behaviour is because the connections to the nodes are gotten from the CLUSTER SLOTS command and those hostnames or IP address do not get prefixed with tls:// or ssl://, and it uses the existance of $tlsOptions array for determining which type of connection to make.  If you need TLS connection, the $tlsOptions value MUST be either an empty array, or an array with values.  If you want the connections to be made without TLS, then the $tlsOptions array MUST be null.

&copy; 2011 [Colin Mollenhour](http://colin.mollenhour.com)
&copy; 2009 [Justin Poliey](http://justinpoliey.com)
