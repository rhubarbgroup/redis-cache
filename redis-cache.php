<?php
/*
 * Plugin Name: Redis Object Cache
 * Plugin URI: https://wordpress.org/plugins/redis-cache/
 * Description: A persistent object cache backend powered by Redis. Supports Predis, PhpRedis, HHVM, replication, clustering and WP-CLI.
 * Version: 1.6.3
 * Text Domain: redis-cache
 * Domain Path: /languages
 * Network: true
 * Requires PHP: 5.6
 * Author: Till Krüss
 * Author URI: https://till.im/
 * GitHub Plugin URI: https://github.com/tillkruss/redis-cache
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'WP_REDIS_FILE', __FILE__ );
define( 'WP_REDIS_PLUGIN_PATH', __DIR__ );
define( 'WP_REDIS_BASENAME', plugin_basename( WP_REDIS_FILE ) );
define( 'WP_REDIS_DIR', plugin_dir_url( WP_REDIS_FILE ) );

$meta = get_file_data( WP_REDIS_FILE, [ 'Version' => 'Version' ] );

define( 'WP_REDIS_VERSION', $meta['Version'] );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once dirname( __FILE__ ) . '/includes/wp-cli-commands.php';
}

require_once WP_REDIS_PLUGIN_PATH . '/includes/class-autoloader.php';
$autoloader = new Rhubarb\RedisCache\Autoloader();
$autoloader->register();
$autoloader->add_namespace( 'Rhubarb\RedisCache', WP_REDIS_PLUGIN_PATH . '/includes' );

if ( ! function_exists( 'redis_object_cache' ) ) {
    /**
     * Returns the plugin instance.
     *
     * @return Rhubarb\RedisCache\Plugin
     */
    function redis_object_cache() {
        return Rhubarb\RedisCache\Plugin::instance();
    }
}

Rhubarb\RedisCache\Plugin::instance();
