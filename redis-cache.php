<?php
/*
Plugin Name: Redis Object Cache
Plugin URI: https://wordpress.org/plugins/redis-cache/
Description: A persistent object cache backend powered by Redis. Supports Predis, PhpRedis, HHVM, replication, clustering and WP-CLI.
Version: 1.6.3
Text Domain: redis-cache
Domain Path: /languages
Author: Till Krüss
Author URI: https://till.im/
GitHub Plugin URI: https://github.com/tillkruss/redis-cache
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_REDIS_VERSION', '1.6.2' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once dirname( __FILE__ ) . '/includes/wp-cli-commands.php';
}

class RedisObjectCache {

    private $page;
    private $screen = 'settings_page_redis-cache';
    private $actions = array( 'enable-cache', 'disable-cache', 'flush-cache', 'update-dropin' );

    public function __construct() {

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        load_plugin_textdomain( 'redis-cache', false, 'redis-cache/languages' );

        register_activation_hook( __FILE__, 'wp_cache_flush' );

        $this->page = is_multisite() ? 'settings.php?page=redis-cache' : 'options-general.php?page=redis-cache';

        add_action( 'deactivate_plugin', array( $this, 'on_deactivation' ) );

        add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', array( $this, 'add_admin_menu_page' ) );
        add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
        add_action( 'admin_notices', array( $this, 'pro_notice' ) );
        add_filter( 'admin_notices', array( $this, 'wc_pro_notice' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'load-' . $this->screen, array( $this, 'do_admin_actions' ) );
        add_action( 'load-' . $this->screen, array( $this, 'add_admin_page_notices' ) );
        add_action( 'wp_head', array( $this, 'register_shutdown_hooks' ) );
        add_action( 'wp_ajax_roc_dismiss_notice', array( $this, 'dismiss_notice' ) );

        add_filter( sprintf(
            '%splugin_action_links_%s',
            is_multisite() ? 'network_admin_' : '',
            plugin_basename( __FILE__ )
        ), array( $this, 'add_plugin_actions_links' ) );

    }

    public function add_admin_menu_page() {

        // add sub-page to "Settings"
        add_submenu_page(
            is_multisite() ? 'settings.php' : 'options-general.php',
            __( 'Redis Object Cache', 'redis-cache'),
            __( 'Redis', 'redis-cache'),
            is_multisite() ? 'manage_network_options' : 'manage_options',
            'redis-cache',
            array( $this, 'show_admin_page' )
        );

    }

    public function show_admin_page() {

        // request filesystem credentials?
        if ( isset( $_GET[ '_wpnonce' ], $_GET[ 'action' ] ) ) {

            $action = $_GET[ 'action' ];

            foreach ( $this->actions as $name ) {

                // verify nonce
                if ( $action === $name && wp_verify_nonce( $_GET[ '_wpnonce' ], $action ) ) {

                    $url = wp_nonce_url( network_admin_url( add_query_arg( 'action', $action, $this->page ) ), $action );

                    if ( $this->initialize_filesystem( $url ) === false ) {
                        return; // request filesystem credentials
                    }

                }

            }

        }

        if ( wp_next_scheduled( 'redis_gather_metrics' ) ) {
            wp_clear_scheduled_hook( 'redis_gather_metrics' );
        }

        // show admin page
        require_once plugin_dir_path( __FILE__ ) . '/includes/admin-page.php';

    }

    public function show_servers_list() {

        require_once plugin_dir_path( __FILE__ ) . '/includes/servers-list.php';

        $table = new Servers_List;
        $table->prepare_items();
        $table->display();

    }

    public function add_plugin_actions_links( $links ) {

        // add settings link to plugin actions
        return array_merge(
            array( sprintf( '<a href="%s">Settings</a>', network_admin_url( $this->page ) ) ),
            $links
        );

    }

    public function enqueue_admin_styles( $hook_suffix ) {

        if ( $hook_suffix === $this->screen ) {
            wp_enqueue_style( 'redis-cache', plugin_dir_url( __FILE__ ) . 'includes/admin-page.css', null, WP_REDIS_VERSION );
        }

    }

    public function enqueue_admin_scripts() {
        $screen = get_current_screen();

        if ( ! isset( $screen->id ) ) {
            return;
        }

        if ( ! in_array( $screen->id, array(
            'dashboard',
            'edit-shop_order',
            'edit-product',
            'woocommerce_page_wc-admin',
            $this->screen
        ) ) ) {
            return;
        }

        wp_enqueue_script(
            'roc-dismissible-notices',
            plugins_url( 'includes/admin-page.js', __FILE__ ),
            array( 'jquery' ),
            WP_REDIS_VERSION
        );
    }

    public function object_cache_dropin_exists() {
        return file_exists( WP_CONTENT_DIR . '/object-cache.php' );
    }

    public function validate_object_cache_dropin() {

        if ( ! $this->object_cache_dropin_exists() ) {
            return false;
        }

        $dropin = get_plugin_data( WP_CONTENT_DIR . '/object-cache.php' );
        $plugin = get_plugin_data( plugin_dir_path( __FILE__ ) . '/includes/object-cache.php' );

        if ( strcmp( $dropin[ 'PluginURI' ], $plugin[ 'PluginURI' ] ) !== 0 ) {
            return false;
        }

        return true;

    }

    public function get_status() {

        if (
            ! $this->object_cache_dropin_exists() ||
            ( defined( 'WP_REDIS_DISABLED' ) && WP_REDIS_DISABLED )
        ) {
            return __( 'Disabled', 'redis-cache' );
        }

        if ( $this->validate_object_cache_dropin() ) {
            if ( $this->get_redis_status() ) {
                return __( 'Connected', 'redis-cache' );
            }

            if ( $this->get_redis_status() === false ) {
                return __( 'Not Connected', 'redis-cache' );
            }
        }

        return __( 'Unknown', 'redis-cache' );

    }

    public function get_redis_status() {

        global $wp_object_cache;

        if ( defined( 'WP_REDIS_DISABLED' ) && WP_REDIS_DISABLED ) {
            return;
        }

        if ( $this->validate_object_cache_dropin() && method_exists( $wp_object_cache, 'redis_status' ) ) {
            return $wp_object_cache->redis_status();
        }

        return;

    }

    public function get_redis_version() {

        global $wp_object_cache;

        if ( defined( 'WP_REDIS_DISABLED' ) && WP_REDIS_DISABLED ) {
            return;
        }

        if ( $this->validate_object_cache_dropin() && method_exists( $wp_object_cache, 'redis_version' ) ) {
            return $wp_object_cache->redis_version();
        }

    }

    public function get_redis_client_name() {

        global $wp_object_cache;

        if ( isset( $wp_object_cache->redis_client ) ) {
            return $wp_object_cache->redis_client;
        }

        if ( defined( 'WP_REDIS_CLIENT' ) ) {
            return WP_REDIS_CLIENT;
        }

    }

    public function get_redis_cachekey_prefix() {
        return defined( 'WP_CACHE_KEY_SALT' ) ? WP_CACHE_KEY_SALT : null;
    }

    public function get_redis_maxttl() {
        return defined( 'WP_REDIS_MAXTTL' ) ? WP_REDIS_MAXTTL : null;
    }

    public function show_admin_notices() {

        // only show admin notices to users with the right capability
        if ( ! current_user_can( is_multisite() ? 'manage_network_options' : 'manage_options' ) ) {
            return;
        }

        if ( $this->object_cache_dropin_exists() ) {

            $url = wp_nonce_url( network_admin_url( add_query_arg( 'action', 'update-dropin', $this->page ) ), 'update-dropin' );

            if ( $this->validate_object_cache_dropin() ) {

                $dropin = get_plugin_data( WP_CONTENT_DIR . '/object-cache.php' );
                $plugin = get_plugin_data( plugin_dir_path( __FILE__ ) . '/includes/object-cache.php' );

                if ( version_compare( $dropin[ 'Version' ], $plugin[ 'Version' ], '<' ) ) {
                    $message = sprintf( __( 'The Redis object cache drop-in is outdated. Please <a href="%s">update it now</a>.', 'redis-cache' ), $url );
                }

            } else {

                $message = sprintf( __( 'An unknown object cache drop-in was found. To use Redis, <a href="%s">please replace it now</a>.', 'redis-cache' ), $url );

            }

            if ( isset( $message ) ) {
                printf( '<div class="update-nag">%s</div>', $message );
            }

        }

    }

    public function add_admin_page_notices() {

        // show PHP version warning
        if ( version_compare( PHP_VERSION, '5.4.0', '<' ) ) {
            add_settings_error( '', 'redis-cache', __( 'This plugin requires PHP 5.4 or greater.', 'redis-cache' ) );
        }

        // show action success/failure messages
        if ( isset( $_GET[ 'message' ] ) ) {

            switch ( $_GET[ 'message' ] ) {

                case 'cache-enabled':
                    $message = __( 'Object cache enabled.', 'redis-cache' );
                    break;
                case 'enable-cache-failed':
                    $error = __( 'Object cache could not be enabled.', 'redis-cache' );
                    break;
                case 'cache-disabled':
                    $message = __( 'Object cache disabled.', 'redis-cache' );
                    break;
                case 'disable-cache-failed':
                    $error = __( 'Object cache could not be disabled.', 'redis-cache' );
                    break;
                case 'cache-flushed':
                    $message = __( 'Object cache flushed.', 'redis-cache' );
                    break;
                case 'flush-cache-failed':
                    $error = __( 'Object cache could not be flushed.', 'redis-cache' );
                    break;
                case 'dropin-updated':
                    $message = __( 'Updated object cache drop-in and enabled Redis object cache.', 'redis-cache' );
                    break;
                case 'update-dropin-failed':
                    $error = __( 'Object cache drop-in could not be updated.', 'redis-cache' );
                    break;

            }

            add_settings_error( '', 'redis-cache', isset( $message ) ? $message : $error, isset( $message ) ? 'updated' : 'error' );

        }

    }

    public function do_admin_actions() {

        global $wp_filesystem;

        if ( isset( $_GET[ '_wpnonce' ], $_GET[ 'action' ] ) ) {

            $action = $_GET[ 'action' ];

            // verify nonce
            foreach ( $this->actions as $name ) {
                if ( $action === $name && ! wp_verify_nonce( $_GET[ '_wpnonce' ], $action ) ) {
                    return;
                }
            }

            if ( in_array( $action, $this->actions ) ) {

                $url = wp_nonce_url( network_admin_url( add_query_arg( 'action', $action, $this->page ) ), $action );

                if ( $action === 'flush-cache' ) {
                    $message = wp_cache_flush() ? 'cache-flushed' : 'flush-cache-failed';
                }

                // do we have filesystem credentials?
                if ( $this->initialize_filesystem( $url, true ) ) {

                    switch ( $action ) {

                        case 'enable-cache':
                            $result = $wp_filesystem->copy( plugin_dir_path( __FILE__ ) . '/includes/object-cache.php', WP_CONTENT_DIR . '/object-cache.php', true );
                            do_action( 'redis_object_cache_enable', $result );
                            $message = $result ? 'cache-enabled' : 'enable-cache-failed';
                            break;

                        case 'disable-cache':
                            $result = $wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );
                            do_action( 'redis_object_cache_disable', $result );
                            $message = $result ? 'cache-disabled' : 'disable-cache-failed';
                            break;

                        case 'update-dropin':
                            $result = $wp_filesystem->copy( plugin_dir_path( __FILE__ ) . '/includes/object-cache.php', WP_CONTENT_DIR . '/object-cache.php', true );
                            do_action( 'redis_object_cache_update_dropin', $result );
                            $message = $result ? 'dropin-updated' : 'update-dropin-failed';
                            break;

                    }

                }

                // redirect if status `$message` was set
                if ( isset( $message ) ) {
                    wp_safe_redirect( network_admin_url( add_query_arg( 'message', $message, $this->page ) ) );
                    exit;
                }

            }

        }

    }

    public function dismiss_notice() {
        $notice = sprintf(
            'roc_dismissed_%s',
            sanitize_key( $_POST[ 'notice' ] )
        );

        update_user_meta( get_current_user_id(), $notice, '1' );

        wp_die();
    }

    public function pro_notice() {
        $screen = get_current_screen();

        if ( ! isset( $screen->id ) ) {
            return;
        }

        if ( ! in_array( $screen->id, array( 'dashboard' ) ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( defined( 'WP_REDIS_DISABLE_BANNERS' ) && WP_REDIS_DISABLE_BANNERS ) {
            return;
        }

        if ( get_user_meta( get_current_user_id(), 'roc_dismissed_pro_release_notice', true ) == '1' ) {
            return;
        }

        printf(
            '<div class="notice notice-info is-dismissible" data-dismissible="pro_release_notice"><p><strong>%s</strong> %s</p></div>',
            __( 'Redis Cache Pro is out!', 'redis-cache' ),
            sprintf(
                __( 'A <u>business class</u> object cache backend. Truly reliable, highly-optimized and fully customizable, with a <u>dedicated engineer</u> when you most need it. <a href="%1$s">Learn more »</a>', 'redis-cache' ),
                network_admin_url( $this->page )
            )
        );
    }

    public function wc_pro_notice() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        $screen = get_current_screen();

        if ( ! isset( $screen->id ) ) {
            return;
        }

        if ( ! in_array( $screen->id, array( 'edit-shop_order', 'edit-product', 'woocommerce_page_wc-admin' ) ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( defined( 'WP_REDIS_DISABLE_BANNERS' ) && WP_REDIS_DISABLE_BANNERS ) {
            return;
        }

        if ( get_user_meta( get_current_user_id(), 'roc_dismissed_wc_pro_notice', true ) == '1' ) {
            return;
        }

        printf(
            '<div class="notice woocommerce-message woocommerce-admin-promo-messages is-dismissible" data-dismissible="wc_pro_notice"><p><strong>%s</strong></p><p>%s</p></div>',
            __( 'Redis Cache Pro + WooCommerce = ❤️', 'redis-cache' ),
            sprintf(
                __( 'Redis Cache Pro is a <u>business class</u> object cache that’s highly-optimized for WooCommerce to provide true reliability, peace of mind and faster load times for your store. <a style="color: #bb77ae;" href="%1$s">Learn more »</a>', 'redis-cache' ),
                network_admin_url( $this->page )
            )
        );
    }

    public function register_shutdown_hooks()
    {
        if ( ! defined( 'WP_REDIS_DISABLE_COMMENT' ) || ! WP_REDIS_DISABLE_COMMENT ) {
            add_action( 'shutdown', array( $this, 'maybe_print_comment' ), 0 );
        }
    }

    public function maybe_print_comment() {
        global $wp_object_cache;

        if (
            ( defined( 'DOING_CRON' ) && DOING_CRON ) ||
            ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
            ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
            ( defined( 'JSON_REQUEST' ) && JSON_REQUEST ) ||
            ( defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) ||
            ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ||
            ( defined( 'WC_API_REQUEST' ) && WC_API_REQUEST )
        ) {
            return;
        }

        if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
            return;
        }

        if (
            ! isset( $wp_object_cache->cache_hits ) ||
            ! isset( $wp_object_cache->redis_client ) ||
            ! is_array( $wp_object_cache->cache )
        ) {
            return;
        }

        $message = sprintf(
            __( 'Performance optimized by Redis Object Cache. Learn more: %s', 'redis-cache' ),
            'https://wprediscache.com'
        );

        if (! WP_DEBUG) {
            printf("\n<!-- %s -->\n", $message);

            return;
        }

        $bytes = strlen(serialize($wp_object_cache->cache));

        $debug = sprintf(
            __( 'Retrieved %d objects (%s) from Redis using %s.', 'redis-cache' ),
            $wp_object_cache->cache_hits,
            function_exists( 'size_format' ) ? size_format($bytes) : "{$bytes} bytes",
            $wp_object_cache->redis_client
        );

        printf("<!--\n%s\n\n%s\n-->\n", $message, $debug);
    }

    public function initialize_filesystem( $url, $silent = false ) {

        if ( $silent ) {
            ob_start();
        }

        if ( ( $credentials = request_filesystem_credentials( $url ) ) === false ) {

            if ( $silent ) {
                ob_end_clean();
            }

            return false;

        }

        if ( ! WP_Filesystem( $credentials ) ) {

            request_filesystem_credentials( $url );

            if ( $silent ) {
                ob_end_clean();
            }

            return false;

        }

        return true;

    }

    public function on_deactivation( $plugin ) {

        global $wp_filesystem;

        if ( $plugin === plugin_basename( __FILE__ ) ) {

            wp_cache_flush();

            if ( $this->validate_object_cache_dropin() && $this->initialize_filesystem( '', true ) ) {
                $wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );
            }

        }

    }

}

$GLOBALS[ 'redisObjectCache' ] = new RedisObjectCache;
