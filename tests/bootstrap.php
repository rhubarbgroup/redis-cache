<?php

error_reporting(E_ALL);

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

if (getenv('GH_REDIS_CLUSTER')) {
    define('WP_REDIS_CLUSTER', [
        'tcp://127.0.0.1:6379',
        'tcp://127.0.0.1:6380',
        'tcp://127.0.0.1:6381',
        'tcp://127.0.0.1:6382',
        'tcp://127.0.0.1:6383',
        'tcp://127.0.0.1:6384',
    ]);
}

require_once __DIR__.'/../vendor/autoload.php';

use Yoast\WPTestUtils\WPIntegration;

if (false !== getenv('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', getenv('WP_PLUGIN_DIR'));
}

$GLOBALS['wp_tests_options'] = [
    'active_plugins' => ['redis-cache/redis-cache.php'],
];

require_once dirname(__DIR__).'/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

/*
 * Bootstrap WordPress. This will also load the Composer autoload file, the PHPUnit Polyfills
 * and the custom autoloader for the TestCase and the mock object classes.
 */
WPIntegration\bootstrap_it();

if (!defined('WP_PLUGIN_DIR') || false === file_exists(WP_PLUGIN_DIR.'/redis-cache/redis-cache.php')) {
    echo PHP_EOL, 'ERROR: Please check whether the WP_PLUGIN_DIR environment variable is set and set to the correct value. The integration test suite won\'t be able to run without it.', PHP_EOL;

    exit(1);
}

require_once __DIR__.'/../redis-cache.php';
