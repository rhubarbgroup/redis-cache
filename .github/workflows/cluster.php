<?php

define('WP_REDIS_CLIENT', 'predis');
// define('WP_REDIS_CLIENT', 'phpredis');

define('WP_REDIS_PASSWORD', 'secret');
define('WP_REDIS_GRACEFUL', false);

define('WP_REDIS_CLUSTER', [
    'tcp://127.0.0.1:7000',
    'tcp://127.0.0.1:7001',
    'tcp://127.0.0.1:7002',
    'tcp://127.0.0.1:7003',
    'tcp://127.0.0.1:7004',
    'tcp://127.0.0.1:7005',
]);

require __DIR__ . '/../../includes/object-cache.php';

wp_cache_init();

var_dump(
    wp_cache_get('foo', 'bar')
);
