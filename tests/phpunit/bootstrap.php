<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Redis_Cache
 */

$GLOBALS['wp_tests_options'] = [
    'active_plugins' => [
        'redis-cache/redis-cache.php',
    ],
];

$_tests_dir = getenv('WP_TESTS_DIR');

if ( ! getenv('WP_TESTS_DIR') ) {
    $_tests_dir = '/opt/bitnami/wordpress/tests-lib';
}

$base_dir   = dirname( dirname( __DIR__ ) );

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
tests_add_filter(
    'muplugins_loaded',
    function() use ( $base_dir ) {
        require $base_dir . '/redis-cache.php';
    }
);

// Start up the WP testing environment.
require_once $_tests_dir . '/includes/bootstrap.php';

// Adds composer.
require_once $base_dir . '/vendor/autoload.php';

require_once __DIR__ . '/class-roc-unit-test-case.php';

// Installs object cache
copy(
    ROC_Unit_Test_Case::basepath( 'includes/object-cache.php' ),
    WP_CONTENT_DIR . '/object-cache.php'
);
