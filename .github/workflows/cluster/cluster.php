<?php
/**
 * Standalone CLI smoke test for Redis cluster connections.
 *
 * @package Rhubarb\RedisCache
 */

// phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed -- WordPress is not bootstrapped in this CLI test.
ini_set( 'display_errors', 1 );
// phpcs:ignore WordPress.PHP.IniSet.Risky -- Show startup failures in CI.
ini_set( 'display_startup_errors', 1 );

// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- Report all errors in this CLI test.
error_reporting( E_ALL );

define( 'ABSPATH', __DIR__ );
define( 'WP_REDIS_PLUGIN_PATH', __DIR__ . '/../../' );

define( 'WP_REDIS_CLIENT', $argv[1] );

define( 'WP_REDIS_PASSWORD', 'secret' );
define( 'WP_REDIS_GRACEFUL', false );

define(
    'WP_REDIS_CLUSTER',
    [
        'tcp://127.0.0.1:7000',
        'tcp://127.0.0.1:7001',
        'tcp://127.0.0.1:7002',
        'tcp://127.0.0.1:7003',
        'tcp://127.0.0.1:7004',
        'tcp://127.0.0.1:7005',
    ]
);

/**
 * Stub WordPress filters for the standalone test.
 *
 * @param string $filter Filter name.
 * @param mixed  ...$args Filter arguments.
 * @return void
 */
function apply_filters( $filter, ...$args ) { } // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Match the WordPress API.

/**
 * Stub WordPress actions for the standalone test.
 *
 * @param string $action Action name.
 * @param mixed  ...$args Action arguments.
 * @return void
 */
function do_action( $action, ...$args ) { } // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Match the WordPress API.

/**
 * Stub WordPress usage notices for the standalone test.
 *
 * @param string $action Function name.
 * @param mixed  ...$args Notice arguments.
 * @return void
 */
function _doing_it_wrong( $action, ...$args ) { } // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Match the WordPress API.

printf( 'Loading cache...' . PHP_EOL );

require __DIR__ . '/../../includes/object-cache.php';

printf( 'Initialize cache...' . PHP_EOL );

wp_cache_init();

printf( 'Initialize cache...' . PHP_EOL );

// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump -- Display the write result in CI.
var_dump(
    wp_cache_set( 'foo', 'bar', 'test' )
);

sleep( 1 );

// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump -- Display the read result in CI.
var_dump(
    wp_cache_get( 'foo', 'test' )
);
