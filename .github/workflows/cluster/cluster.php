<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

define('ABSPATH', __DIR__);
define('WP_REDIS_PLUGIN_PATH', __DIR__ . '/../../' );

define('WP_REDIS_CLIENT', $argv[1]);

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

function apply_filters($filter, ...$args) { }
function do_action($action, ...$args) { }
function _doing_it_wrong($action, ...$args) { }

printf('Loading cache...' . PHP_EOL);

require __DIR__ . '/../../includes/object-cache.php';

printf('Initialize cache...' . PHP_EOL);

wp_cache_init();

printf('Initialize cache...' . PHP_EOL);

var_dump(
    wp_cache_set('foo', 'bar', 'test')
);

sleep(1);

var_dump(
    wp_cache_get('foo', 'test')
);
