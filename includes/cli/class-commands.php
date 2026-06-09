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
use Rhubarb\RedisCache\Tenants;

defined( 'ABSPATH' ) || exit;

/**
 * Enables, disabled and checks the status of the object cache.
 * To flush call `wp cache flush`.
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
     * Checks whether the Redis database is shared with other installations.
     *
     * Reads the tenant registry written by the object cache and samples the
     * keyspace for key prefixes that don't belong to this site. A shared
     * database means a non-selective flush wipes every site that shares it.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Render the discovered tenants in a specific format.
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - yaml
     *   - count
     * ---
     *
     * ## EXAMPLES
     *
     *     wp redis is-shared
     *
     * @subcommand is-shared
     *
     * @param array $args       Positional arguments. Unused.
     * @param array $assoc_args Associative arguments.
     */
    public function is_shared( $args, $assoc_args ) {
        if ( ! Plugin::instance()->get_redis_status() ) {
            WP_CLI::error( __( 'The Redis object cache is not connected.', 'redis-cache' ) );
        }

        $format = $assoc_args['format'] ?? 'table';
        $report = Tenants::report();

        $rows = [];

        foreach ( $report['tenants'] as $tenant ) {
            $rows[] = [
                'url'       => $tenant['url'] ?? '(unknown)',
                'multisite' => empty( $tenant['multisite'] ) ? 'no' : 'yes',
                'prefix'    => empty( $tenant['prefix'] ) ? 'empty' : 'set',
                'version'   => $tenant['version'] ?? '',
                'last seen' => empty( $tenant['seen'] ) ? '' : sprintf(
                    /* translators: %s: human readable time difference. */
                    __( '%s ago', 'redis-cache' ),
                    human_time_diff( $tenant['seen'] )
                ),
            ];
        }

        \WP_CLI\Utils\format_items( $format, $rows, [ 'url', 'multisite', 'prefix', 'version', 'last seen' ] );

        if ( 'table' !== $format ) {
            return;
        }

        if ( ! empty( $report['foreign'] ) ) {
            $message = sprintf(
                /* translators: 1: number of prefixes, 2: comma separated list of prefixes. */
                __( 'Sampled %1$d unrecognized key prefix(es) that do not match this site: %2$s', 'redis-cache' ),
                count( $report['foreign'] ),
                implode( ', ', array_slice( $report['foreign'], 0, 10 ) )
            );

            WP_CLI::log( '' );
            WP_CLI::warning( $message );
        }

        WP_CLI::log( '' );

        if ( true === $report['shared'] ) {
            WP_CLI::warning( __( 'This Redis database is shared with other installations.', 'redis-cache' ) );

            $recommendation = $report['salt_set']
                ? __( 'Assign a dedicated `WP_REDIS_DATABASE` index per site.', 'redis-cache' )
                : __( 'Set a unique `WP_REDIS_PREFIX` per site, or assign a dedicated `WP_REDIS_DATABASE` index.', 'redis-cache' );

            WP_CLI::log( $recommendation );
        } elseif ( false === $report['shared'] ) {
            WP_CLI::success( __( 'This Redis database does not appear to be shared.', 'redis-cache' ) );
        } else {
            WP_CLI::warning( __( 'Unable to determine whether the Redis database is shared. Set a unique `WP_REDIS_PREFIX` to enable detection.', 'redis-cache' ) );
        }
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
            $flush = $this->flush_redis();

            if ( is_string( $flush ) ) {
                // translators: %s = The Redis connection error message.
                WP_CLI::error( sprintf( __( "Object cache could not be enabled. Redis server is unreachable: %s", 'redis-cache' ), $flush ) );
            }

            WP_Filesystem();

            $copy = $wp_filesystem->copy(
                WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php',
                WP_CONTENT_DIR . '/object-cache.php',
                true,
                FS_CHMOD_FILE
            );

            /**
             * Fires on cache enable event
             *
             * @since 1.3.5
             * @param bool $result Whether the filesystem event (copy of the `object-cache.php` file) was successful.
             */
            do_action( 'redis_object_cache_enable', $copy );

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

                WP_Filesystem();

                $result = $wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );

                /**
                 * Fires on cache disable event
                 *
                 * @param bool $result Whether the deletion of the `object-cache.php` drop-in was successful.
                 * @since 1.3.5
                 */
                do_action( 'redis_object_cache_disable', $result );

                if ( $result ) {
                    $this->flush_redis();

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

        /**
         * Fires on cache update-dropin event
         *
         * @param bool $result Whether the `object-cache.php` drop-in was updated successful.
         * @since 1.3.5
         */
        do_action( 'redis_object_cache_update_dropin', $copy );

        if ( $copy ) {
            $flush = $this->flush_redis();

            if ( is_string( $flush ) ) {
                // translators: %s = The Redis connection error message.
                WP_CLI::error( sprintf( __( "Object cache drop-in could not be updated. Redis server is unreachable: %s", 'redis-cache' ), $flush ) );
            }

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
            return (new Predis)->flushOrFail();
        } catch ( Exception $exception ) {
            error_log( $exception ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

            return $exception->getMessage();
        }
    }
}
