<?php
/**
 * WP CLI command class
 *
 * @package Rhubarb\RedisCache
 */

namespace Rhubarb\RedisCache\CLI;

use WP_CLI;
use WP_CLI_Command;
use Exception;

use Rhubarb\RedisCache\Plugin;
use Rhubarb\RedisCache\Predis;

defined( '\\ABSPATH' ) || exit;

/**
 * Enables, disabled, flushes, and checks the status of the object cache.
 *
 * @package wp-cli
 */
class Commands extends WP_CLI_Command {

    /**
     * Show the Redis object cache status and (when possible) client.
     *
     * ## EXAMPLES
     *
     *     wp redis status
     */
    public function status() {
        $roc = Plugin::instance();

        require_once __DIR__ . '/../diagnostics.php';
    }

    /**
     * Enables the Redis object cache.
     *
     * Default behavior is to create the object cache drop-in,
     * unless an unknown object cache drop-in is present.
     *
     * ## EXAMPLES
     *
     *     wp redis enable
     */
    public function enable() {

        global $wp_filesystem;

        $plugin = Plugin::instance();

        if ( $plugin->object_cache_dropin_exists() ) {

            if ( $plugin->validate_object_cache_dropin() ) {
                WP_CLI::line( __( 'Redis object cache already enabled.', 'redis-cache' ) );
            } else {
                WP_CLI::error( __( 'A foreign object cache drop-in was found. To use Redis for object caching, run: `wp redis update-dropin`.', 'redis-cache' ) );
            }
        } else {
            $flush_redis = $this->flush_redis();

            if ( is_string( $flush_redis ) ) {
                WP_CLI::error( __( 'Object cache could not be enabled as Redis is unreachable:', 'redis-cache' ) . ' ' . $flush_redis );
            }

            WP_Filesystem();

            $copy = $wp_filesystem->copy(
                WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php',
                WP_CONTENT_DIR . '/object-cache.php',
                true,
                FS_CHMOD_FILE
            );

            if ( $copy ) {
                WP_CLI::success( __( 'Object cache enabled.', 'redis-cache' ) );
            } else {
                WP_CLI::error( __( 'Object cache could not be enabled.', 'redis-cache' ) );
            }
        }

    }

    /**
     * Disables the Redis object cache.
     *
     * Default behavior is to delete the object cache drop-in,
     * unless an unknown object cache drop-in is present.
     *
     * ## EXAMPLES
     *
     *     wp redis disable
     */
    public function disable() {

        global $wp_filesystem;

        $plugin = Plugin::instance();

        if ( ! $plugin->object_cache_dropin_exists() ) {

            WP_CLI::error( __( 'No object cache drop-in found.', 'redis-cache' ) );

        } else {

            if ( ! $plugin->validate_object_cache_dropin() ) {

                WP_CLI::error( __( 'A foreign object cache drop-in was found. To use Redis for object caching, run: `wp redis update-dropin`.', 'redis-cache' ) );

            } else {
                $this->flush_redis();

                WP_Filesystem();

                if ( $wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' ) ) {
                    WP_CLI::success( __( 'Object cache disabled.', 'redis-cache' ) );
                } else {
                    WP_CLI::error( __( 'Object cache could not be disabled.', 'redis-cache' ) );
                }
            }
        }

    }

    /**
     * Updates the Redis object cache drop-in.
     *
     * Default behavior is to overwrite any existing object cache drop-in.
     *
     * ## EXAMPLES
     *
     *     wp redis update-dropin
     *
     * @subcommand update-dropin
     */
    public function update_dropin() {

        global $wp_filesystem;

        WP_Filesystem();

        $copy = $wp_filesystem->copy(
            WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php',
            WP_CONTENT_DIR . '/object-cache.php',
            true,
            FS_CHMOD_FILE
        );

        if ( $copy ) {
            WP_CLI::success( __( 'Updated object cache drop-in and enabled Redis object cache.', 'redis-cache' ) );
        } else {
            WP_CLI::error( __( 'Object cache drop-in could not be updated.', 'redis-cache' ) );
        }

    }

    /**
     * Flush the Redis cache via Predis.
     *
     * @return bool|string
     */
    protected function flush_redis() {
        try {
            $predis = new Predis();
            $predis->connect();
        } catch ( Exception $exception ) {
            return $exception->getMessage();
        }

        try {
            $predis->flush();
        } catch ( Exception $exception ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( $exception );
        }

        return true;
    }

}
