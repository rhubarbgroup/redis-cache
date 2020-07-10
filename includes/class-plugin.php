<?php
/**
 * Main plugin class
 *
 * @package Rhubarb\RedisCache
 */

namespace Rhubarb\RedisCache;

defined( '\\ABSPATH' ) || exit;

class Plugin {

    private $page;

    private $screen = 'settings_page_redis-cache';

    private $actions = array(
        'enable-cache',
        'disable-cache',
        'flush-cache',
        'update-dropin',
    );

    /**
     * Plugin instance property.
     *
     * @var Plugin
     */
    private static $instance;

    private function __construct() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        load_plugin_textdomain( 'redis-cache', false, 'redis-cache/languages' );
        register_activation_hook( WP_REDIS_FILE, 'wp_cache_flush' );

        $this->page = is_multisite() ? 'settings.php?page=redis-cache' : 'options-general.php?page=redis-cache';

        add_action( 'deactivate_plugin', array( $this, 'on_deactivation' ) );
        add_action( 'upgrader_process_complete', array( $this, 'maybe_update_dropin' ), 10, 2 );

        add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', array( $this, 'add_admin_menu_page' ) );

        add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
        add_action( 'network_admin_notices', array( $this, 'show_admin_notices' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_redis_metrics' ) );

        add_action( 'load-' . $this->screen, array( $this, 'do_admin_actions' ) );
        add_action( 'load-' . $this->screen, array( $this, 'add_admin_page_notices' ) );

        add_action( 'wp_dashboard_setup', array( $this, 'setup_dashboard_widget' ) );
        add_action( 'wp_network_dashboard_setup', array( $this, 'setup_dashboard_widget' ) );

        add_action( 'wp_ajax_roc_dismiss_notice', array( $this, 'dismiss_notice' ) );

        $links = sprintf( '%splugin_action_links_%s', is_multisite() ? 'network_admin_' : '', WP_REDIS_BASENAME );
        add_filter( $links, array( $this, 'add_plugin_actions_links' ) );

        add_action( 'wp_head', array( $this, 'register_shutdown_hooks' ) );
        add_action( 'shutdown', array( $this, 'record_metrics' ) );
        add_action( 'rediscache_discard_metrics', array( $this, 'discard_metrics' ) );

        add_filter( 'qm/collectors', array( $this, 'register_qm_collector' ), 25 );
        add_filter( 'qm/outputter/html', array( $this, 'register_qm_output' ) );

        if ( is_admin() && ! wp_next_scheduled( 'rediscache_discard_metrics' ) ) {
            wp_schedule_event( time(), 'hourly', 'rediscache_discard_metrics' );
        }
    }

    /**
     * Plugin instanciation method.
     *
     * @return Plugin
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function add_admin_menu_page() {
        // add sub-page to "Settings"
        add_submenu_page(
            is_multisite() ? 'settings.php' : 'options-general.php',
            __( 'Redis Object Cache', 'redis-cache' ),
            __( 'Redis', 'redis-cache' ),
            is_multisite() ? 'manage_network_options' : 'manage_options',
            'redis-cache',
            array( $this, 'show_admin_page' )
        );
    }

    public function show_admin_page() {
        // request filesystem credentials?
        if ( isset( $_GET['_wpnonce'], $_GET['action'] ) ) {
            $action = $_GET['action'];

            foreach ( $this->actions as $name ) {
                // verify nonce
                if ( $action === $name && wp_verify_nonce( $_GET['_wpnonce'], $action ) ) {
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

        UI::register_tab( 'overview', __( 'Overview', 'redis-cache' ), [ 'default' => true ] );
        UI::register_tab( 'metrics', __( 'Metrics', 'redis-cache' ) );
        UI::register_tab( 'diagnostics', __( 'Diagnostics', 'redis-cache' ) );

        // show admin page
        require_once WP_REDIS_PLUGIN_PATH . '/includes/ui/settings.php';
    }

    public function setup_dashboard_widget() {
        if ( defined( 'WP_REDIS_DISABLE_METRICS' ) && WP_REDIS_DISABLE_METRICS ) {
            return;
        }

        wp_add_dashboard_widget(
            'dashboard_rediscache',
            __( 'Redis Object Cache', 'redis-cache' ),
            array( $this, 'show_dashboard_widget' )
        );
    }

    public function show_dashboard_widget() {
        require_once WP_REDIS_PLUGIN_PATH . '/includes/ui/widget.php';
    }

    public function add_plugin_actions_links( $links ) {
        // add settings link to plugin actions
        return array_merge(
            [ sprintf( '<a href="%s">%s</a>', network_admin_url( $this->page ), esc_html__( 'Settings', 'redis-cache' ) ) ],
            $links
        );
    }

    public function enqueue_admin_styles( $hook_suffix ) {
        if ( in_array( $hook_suffix, array( 'index.php', $this->screen ) ) ) {
            wp_enqueue_style( 'redis-cache', WP_REDIS_DIR . '/assets/css/admin.css', null, WP_REDIS_VERSION );
        }
    }

    public function enqueue_admin_scripts() {
        $screen = get_current_screen();

        if ( ! isset( $screen->id ) ) {
            return;
        }

        $screens = array(
            $this->screen,
            'dashboard',
            'dashboard-network',
            'edit-shop_order',
            'edit-product',
            'woocommerce_page_wc-admin',
        );

        if ( ! in_array( $screen->id, $screens ) ) {
            return;
        }

        wp_enqueue_script(
            'redis-cache',
            plugins_url( 'assets/js/admin.js', WP_REDIS_FILE ),
            array( 'jquery', 'underscore' ),
            WP_REDIS_VERSION
        );

        wp_localize_script(
            'redis-cache',
            'rediscache',
            array(
                'jQuery' => 'jQuery',
                'l10n' => array(
                    'time' => __( 'Time', 'redis-cache' ),
                    'bytes' => __( 'Bytes', 'redis-cache' ),
                    'ratio' => __( 'Ratio', 'redis-cache' ),
                    'calls' => __( 'Calls', 'redis-cache' ),
                    'no_data' => __( 'Not enough data collected, yet.', 'redis-cache' ),
                    'no_cache' => __( 'Enable object cache to collect data.', 'redis-cache' ),
                    'pro' => 'Redis Cache Pro',
                ),
            )
        );
    }

    public function enqueue_redis_metrics() {
        global $wp_object_cache;

        if ( defined( 'WP_REDIS_DISABLE_METRICS' ) && WP_REDIS_DISABLE_METRICS ) {
            return;
        }

        $screen = get_current_screen();

        if ( ! isset( $screen->id ) ) {
            return;
        }

        if ( ! in_array( $screen->id, array( $this->screen, 'dashboard', 'dashboard-network' ) ) ) {
            return;
        }

        wp_enqueue_script(
            'redis-cache-charts',
            plugins_url( 'assets/js/apexcharts.min.js', WP_REDIS_FILE ),
            null,
            WP_REDIS_VERSION
        );

        if ( ! method_exists( $wp_object_cache, 'redis_instance' ) ) {
            return;
        }

        $metrics = $wp_object_cache->redis_instance()->zrangebyscore(
            $wp_object_cache->build_key( 'metrics', 'redis-cache' ),
            time() - ( MINUTE_IN_SECONDS * 30 ),
            time() - MINUTE_IN_SECONDS,
            [ 'withscores' => true ]
        );

        wp_localize_script( 'redis-cache', 'rediscache_metrics', $metrics );
    }

    public function register_qm_collector( array $collectors ) {
        $collectors['cache'] = new QM_Collector();

        return $collectors;
    }

    public function register_qm_output( $output ) {
        $output['cache'] = new QM_Output( \QM_Collectors::get( 'cache' ) );

        return $output;
    }

    public function object_cache_dropin_exists() {
        return file_exists( WP_CONTENT_DIR . '/object-cache.php' );
    }

    public function validate_object_cache_dropin() {
        if ( ! $this->object_cache_dropin_exists() ) {
            return false;
        }

        $dropin = get_plugin_data( WP_CONTENT_DIR . '/object-cache.php' );
        $plugin = get_plugin_data( WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php' );

        return ( strcmp( $dropin['PluginURI'], $plugin['PluginURI'] ) === 0 );
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

        if ( isset( $wp_object_cache->diagnostics[ 'client' ] ) ) {
            return $wp_object_cache->diagnostics[ 'client' ];
        }

        if ( defined( 'WP_REDIS_CLIENT' ) ) {
            return WP_REDIS_CLIENT;
        }
    }

    public function get_diagnostics() {
        global $wp_object_cache;

        if ( $this->validate_object_cache_dropin() && property_exists( $wp_object_cache, 'diagnostics' ) ) {
            return $wp_object_cache->diagnostics;
        }
    }

    public function get_redis_prefix() {
        return defined( 'WP_REDIS_PREFIX' ) ? WP_REDIS_PREFIX : null;
    }

    public function get_redis_maxttl() {
        return defined( 'WP_REDIS_MAXTTL' ) ? WP_REDIS_MAXTTL : null;
    }

    public function show_admin_notices() {
        if ( ! defined( 'WP_REDIS_DISABLE_BANNERS' ) || ! WP_REDIS_DISABLE_BANNERS ) {
            $this->pro_notice();
            $this->wc_pro_notice();
        }

        // only show admin notices to users with the right capability
        if ( ! current_user_can( is_multisite() ? 'manage_network_options' : 'manage_options' ) ) {
            return;
        }

        if ( $this->object_cache_dropin_exists() ) {

            $url = wp_nonce_url( network_admin_url( add_query_arg( 'action', 'update-dropin', $this->page ) ), 'update-dropin' );

            if ( $this->validate_object_cache_dropin() ) {
                $dropin = get_plugin_data( WP_CONTENT_DIR . '/object-cache.php' );
                $plugin = get_plugin_data( WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php' );

                if ( version_compare( $dropin['Version'], $plugin['Version'], '<' ) ) {
                    $message = sprintf( __( 'The Redis object cache drop-in is outdated. Please <a href="%s">update the drop-in</a>.', 'redis-cache' ), $url );
                }
            } else {
                $message = sprintf( __( 'A foreign object cache drop-in was found. To use Redis for object caching, please <a href="%s">enable the drop-in</a>.', 'redis-cache' ), $url );
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
        if ( isset( $_GET['message'] ) ) {
            switch ( $_GET['message'] ) {
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

        if ( isset( $_GET['_wpnonce'], $_GET['action'] ) ) {
            $action = $_GET['action'];

            // verify nonce
            foreach ( $this->actions as $name ) {
                if ( $action === $name && ! wp_verify_nonce( $_GET['_wpnonce'], $action ) ) {
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
                            $result = $wp_filesystem->copy( WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php', WP_CONTENT_DIR . '/object-cache.php', true );
                            do_action( 'redis_object_cache_enable', $result );
                            $message = $result ? 'cache-enabled' : 'enable-cache-failed';
                            break;

                        case 'disable-cache':
                            $result = $wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );
                            do_action( 'redis_object_cache_disable', $result );
                            $message = $result ? 'cache-disabled' : 'disable-cache-failed';
                            break;

                        case 'update-dropin':
                            $result = $wp_filesystem->copy( WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php', WP_CONTENT_DIR . '/object-cache.php', true );
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
            sanitize_key( $_POST['notice'] )
        );

        update_user_meta( get_current_user_id(), $notice, '1' );

        wp_die();
    }

    public function pro_notice() {
        $screen = get_current_screen();

        if ( ! isset( $screen->id ) ) {
            return;
        }

        if ( ! in_array( $screen->id, array( 'dashboard', 'dashboard-network' ) ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
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

    public function register_shutdown_hooks() {
        if ( ! defined( 'WP_REDIS_DISABLE_COMMENT' ) || ! WP_REDIS_DISABLE_COMMENT ) {
            add_action( 'shutdown', array( $this, 'maybe_print_comment' ), 0 );
        }
    }

    public function record_metrics() {
        global $wp_object_cache;

        if ( defined( 'WP_REDIS_DISABLE_METRICS' ) && WP_REDIS_DISABLE_METRICS ) {
            return;
        }

        if ( ! method_exists( $wp_object_cache, 'info' ) || ! method_exists( $wp_object_cache, 'redis_instance' ) ) {
            return;
        }

        $info = $wp_object_cache->info();

        $metrics = [
            'i' => substr( uniqid(), -7 ),
            'h' => $info->hits,
            'm' => $info->misses,
            'r' => $info->ratio,
            'b' => $info->bytes,
            't' => number_format( $info->time, 5 ),
            'c' => $info->calls,
        ];

        $wp_object_cache->redis_instance()->zadd(
            $wp_object_cache->build_key( 'metrics', 'redis-cache' ),
            time(),
            http_build_query( $metrics, null, ';' )
        );
    }

    public function discard_metrics() {
        global $wp_object_cache;

        if ( defined( 'WP_REDIS_DISABLE_METRICS' ) && WP_REDIS_DISABLE_METRICS ) {
            return;
        }

        if ( ! method_exists( $wp_object_cache, 'redis_instance' ) ) {
            return;
        }

        $wp_object_cache->redis_instance()->zremrangebyscore(
            $wp_object_cache->build_key( 'metrics', 'redis-cache' ),
            0,
            time() - HOUR_IN_SECONDS
        );
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
            ! isset( $wp_object_cache->diagnostics ) ||
            ! is_array( $wp_object_cache->cache )
        ) {
            return;
        }

        $message = sprintf(
            __( 'Performance optimized by Redis Object Cache. Learn more: %s', 'redis-cache' ),
            'https://wprediscache.com'
        );

        if ( ! WP_DEBUG ) {
            printf( "\n<!-- %s -->\n", $message );

            return;
        }

        $bytes = strlen( serialize( $wp_object_cache->cache ) );

        $debug = sprintf(
            __( 'Retrieved %1$d objects (%2$s) from Redis using %3$s.', 'redis-cache' ),
            $wp_object_cache->cache_hits,
            function_exists( 'size_format' ) ? size_format( $bytes ) : "{$bytes} bytes",
            $wp_object_cache->diagnostics['client']
        );

        printf( "<!--\n%s\n\n%s\n-->\n", $message, $debug );
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

    public function maybe_update_dropin( $upgrader, $options ) {
        global $wp_filesystem;

        if (
            $options['action'] !== 'update' ||
            $options['type'] !== 'plugin' ||
            ! is_array( $options['plugins'] ) ||
            ! in_array( WP_REDIS_BASENAME, $options['plugins'] )
        ) {
            return;
        }

        if ( ! $this->validate_object_cache_dropin() ) {
            return;
        }

        if ( WP_Filesystem() ) {
            $wp_filesystem->copy(
                WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php',
                WP_CONTENT_DIR . '/object-cache.php',
                true
            );
        }
    }

    public function on_deactivation( $plugin ) {
        global $wp_filesystem;

        ob_start();

        if ( $plugin === WP_REDIS_BASENAME ) {
            if ( $timestamp = wp_next_scheduled( 'rediscache_discard_metrics' ) ) {
                wp_unschedule_event( $timestamp, 'rediscache_discard_metrics' );
            }

            wp_cache_flush();

            if ( $this->validate_object_cache_dropin() && $this->initialize_filesystem( '', true ) ) {
                $wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );
            }
        }

        ob_end_clean();
    }

    public function action_link( $action ) {
        return wp_nonce_url(
            network_admin_url( add_query_arg( 'action', $action, $this->page ) ),
            $action
        );
    }
}
