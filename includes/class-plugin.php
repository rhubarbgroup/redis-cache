<?php
/**
 * Main plugin class
 *
 * @package Rhubarb\RedisCache
 */

namespace Rhubarb\RedisCache;

use WP_Error;

defined( '\\ABSPATH' ) || exit;

/**
 * Main plugin class definition
 */
class Plugin {

    /**
     * Settings page uri
     *
     * @var string $page
     */
    private $page = '';

    /**
     * Settings page slug
     *
     * @var string $screen
     */
    private $screen = '';

    /**
     * Allowed setting page actions
     *
     * @var string[] $actions
     */
    private $actions = [
        'enable-cache',
        'disable-cache',
        'flush-cache',
        'update-dropin',
    ];

    /**
     * Plugin instance property
     *
     * @var Plugin
     */
    private static $instance;

    /**
     * Plugin instanciation method
     *
     * @return Plugin
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        register_activation_hook( WP_REDIS_FILE, 'wp_cache_flush' );

        if ( is_multisite() ) {
            $this->page = 'settings.php?page=redis-cache';
            $this->screen = 'settings_page_redis-cache-network';
        } else {
            $this->page = 'options-general.php?page=redis-cache';
            $this->screen = 'settings_page_redis-cache';
        }

        Metrics::init();

        $this->add_actions_and_filters();
    }

    /**
     * Adds all necessary hooks
     *
     * @return void
     */
    public function add_actions_and_filters() {
        add_action( 'deactivate_plugin', [ $this, 'on_deactivation' ] );
        add_action( 'admin_init', [ $this, 'maybe_update_dropin' ] );
        add_action( 'init', [ $this, 'init' ] );

        add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', [ $this, 'add_admin_menu_page' ] );

        add_action( 'admin_notices', [ $this, 'show_admin_notices' ] );
        add_action( 'network_admin_notices', [ $this, 'show_admin_notices' ] );

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_redis_metrics' ] );

        add_action( 'load-settings_page_redis-cache', [ $this, 'do_admin_actions' ] );

        add_action( 'wp_dashboard_setup', [ $this, 'setup_dashboard_widget' ] );
        add_action( 'wp_network_dashboard_setup', [ $this, 'setup_dashboard_widget' ] );

        add_action( 'wp_ajax_roc_dismiss_notice', [ $this, 'dismiss_notice' ] );

        $links = sprintf( '%splugin_action_links_%s', is_multisite() ? 'network_admin_' : '', WP_REDIS_BASENAME );
        add_filter( $links, [ $this, 'add_plugin_actions_links' ] );

        add_action( 'wp_head', [ $this, 'register_shutdown_hooks' ] );

        add_filter( 'qm/collectors', [ $this, 'register_qm_collector' ], 25 );
        add_filter( 'qm/outputter/html', [ $this, 'register_qm_output' ] );
    }

    /**
     * Callback of the `init` hook.
     *
     * @return void
     */
    public function init() {
        load_plugin_textdomain( 'redis-cache', false, 'redis-cache/languages' );

        if ( is_admin() && ! wp_next_scheduled( 'rediscache_discard_metrics' ) ) {
            wp_schedule_event( time(), 'hourly', 'rediscache_discard_metrics' );
        }
    }

    /**
     * Adds a submenu page to "Settings"
     *
     * @return void
     */
    public function add_admin_menu_page() {
        add_submenu_page(
            is_multisite() ? 'settings.php' : 'options-general.php',
            __( 'Redis Object Cache', 'redis-cache' ),
            __( 'Redis', 'redis-cache' ),
            is_multisite() ? 'manage_network_options' : 'manage_options',
            'redis-cache',
            [ $this, 'show_admin_page' ]
        );
    }

    /**
     * Displays the settings page
     *
     * @return void
     */
    public function show_admin_page() {
        // Request filesystem credentials?
        if ( isset( $_GET['_wpnonce'], $_GET['action'] ) ) {
            $action = sanitize_key( $_GET['action'] );
            $nonce = sanitize_key( $_GET['_wpnonce'] );

            foreach ( $this->actions as $name ) {
                // Nonce verification.
                if ( $action === $name && wp_verify_nonce( $nonce, $action ) ) {
                    $url = $this->action_link( $action );

                    if ( $this->initialize_filesystem( $url ) === false ) {
                        return; // Request filesystem credentials.
                    }
                }
            }
        }

        if ( wp_next_scheduled( 'redis_gather_metrics' ) ) {
            wp_clear_scheduled_hook( 'redis_gather_metrics' );
        }

        UI::register_tab(
            'overview',
            __( 'Overview', 'redis-cache' ),
            [ 'default' => true ]
        );

        UI::register_tab(
            'metrics',
            __( 'Metrics', 'redis-cache' ),
            [ 'disabled' => defined( 'WP_REDIS_DISABLE_METRICS' ) && WP_REDIS_DISABLE_METRICS ]
        );

        UI::register_tab(
            'diagnostics',
            __( 'Diagnostics', 'redis-cache' )
        );

        // Show the admin page.
        require_once WP_REDIS_PLUGIN_PATH . '/includes/ui/settings.php';
    }

    /**
     * Adds the dashboard metrics widget
     *
     * @return void
     */
    public function setup_dashboard_widget() {
        if ( defined( 'WP_REDIS_DISABLE_METRICS' ) && WP_REDIS_DISABLE_METRICS ) {
            return;
        }

        wp_add_dashboard_widget(
            'dashboard_rediscache',
            __( 'Redis Object Cache', 'redis-cache' ),
            [ $this, 'show_dashboard_widget' ]
        );
    }

    /**
     * Displays the dashboard widget
     *
     * @return void
     */
    public function show_dashboard_widget() {
        require_once WP_REDIS_PLUGIN_PATH . '/includes/ui/widget.php';
    }

    /**
     * Adds the settings page to the plugin action links on the plugin page
     *
     * @param string[] $links The current plugin action links.
     * @return string[]
     */
    public function add_plugin_actions_links( $links ) {
        return array_merge(
            [ sprintf( '<a href="%s">%s</a>', network_admin_url( $this->page ), esc_html__( 'Settings', 'redis-cache' ) ) ],
            $links
        );
    }

    /**
     * Enqueues admin style resources
     *
     * @return void
     */
    public function enqueue_admin_styles() {
        $screen = get_current_screen();

        if ( ! isset( $screen->id ) ) {
            return;
        }

        $screens = [
            $this->screen,
            'dashboard',
            'dashboard-network',
        ];

        if ( ! in_array( $screen->id, $screens, true ) ) {
            return;
        }

        wp_enqueue_style( 'redis-cache', WP_REDIS_DIR . '/assets/css/admin.css', null, WP_REDIS_VERSION );
    }

    /**
     * Enqueues admin script resources
     *
     * @return void
     */
    public function enqueue_admin_scripts() {
        $screen = get_current_screen();

        if ( ! isset( $screen->id ) ) {
            return;
        }

        $screens = [
            $this->screen,
            'dashboard',
            'dashboard-network',
            'edit-shop_order',
            'edit-product',
            'woocommerce_page_wc-admin',
        ];

        if ( ! in_array( $screen->id, $screens, true ) ) {
            return;
        }

        wp_enqueue_script(
            'redis-cache',
            plugins_url( 'assets/js/admin.js', WP_REDIS_FILE ),
            [ 'jquery', 'underscore' ],
            WP_REDIS_VERSION,
            true
        );

        wp_localize_script(
            'redis-cache',
            'rediscache',
            [
                'jQuery' => 'jQuery',
                'disable_pro' => $screen->id !== $this->screen,
                'disable_banners' => defined( 'WP_REDIS_DISABLE_BANNERS' ) && WP_REDIS_DISABLE_BANNERS,
                'l10n' => [
                    'time' => __( 'Time', 'redis-cache' ),
                    'bytes' => __( 'Bytes', 'redis-cache' ),
                    'ratio' => __( 'Ratio', 'redis-cache' ),
                    'calls' => __( 'Calls', 'redis-cache' ),
                    'no_data' => __( 'Not enough data collected, yet.', 'redis-cache' ),
                    'no_cache' => __( 'Enable object cache to collect data.', 'redis-cache' ),
                    'pro' => 'Object Cache Pro',
                ],
            ]
        );
    }

    /**
     * Enqueues scripts to display recorded metrics
     *
     * @return void
     */
    public function enqueue_redis_metrics() {
        if ( ! Metrics::is_active() ) {
            return;
        }

        $screen = get_current_screen();

        if ( ! isset( $screen->id ) ) {
            return;
        }

        if ( ! in_array( $screen->id, [ $this->screen, 'dashboard', 'dashboard-network' ], true ) ) {
            return;
        }

        wp_enqueue_script(
            'redis-cache-charts',
            plugins_url( 'assets/js/apexcharts.min.js', WP_REDIS_FILE ),
            null,
            WP_REDIS_VERSION,
            true
        );

        $min_time = $screen->id === $this->screen
            ? Metrics::max_time()
            : MINUTE_IN_SECONDS * 30;

        $metrics = Metrics::get( $min_time );

        wp_localize_script( 'redis-cache', 'rediscache_metrics', $metrics );
    }

    /**
     * Registers a new cache collector for the Query Monitor plugin
     *
     * @param array $collectors Array of currently registered collectors.
     * @return array
     */
    public function register_qm_collector( array $collectors ) {
        $collectors['cache'] = new QM_Collector();

        return $collectors;
    }

    /**
     * Registers a new cache output using our collector for the Query Monitor plugin
     *
     * @param array $output Array of current QM_Output handlers.
     * @return array
     */
    public function register_qm_output( $output ) {
        $collector = \QM_Collectors::get( 'cache' );

        if (
            $collector instanceof QM_Collector &&
            method_exists( 'QM_Output_Html', 'before_non_tabular_output' )
        ) {
            $output['cache'] = new QM_Output( $collector );
        }

        return $output;
    }

    /**
     * Checks if the `object-cache.php` drop-in exists
     *
     * @return bool
     */
    public function object_cache_dropin_exists() {
        return file_exists( WP_CONTENT_DIR . '/object-cache.php' );
    }

    /**
     * Validates the `object-cache.php` drop-in
     *
     * @return bool
     */
    public function validate_object_cache_dropin() {
        if ( ! $this->object_cache_dropin_exists() ) {
            return false;
        }

        $dropin = get_plugin_data( WP_CONTENT_DIR . '/object-cache.php' );
        $plugin = get_plugin_data( WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php' );

        return $dropin['PluginURI'] === $plugin['PluginURI'];
    }

    /**
     * Checks if the `object-cache.php` drop-in is outdated
     *
     * @return bool
     */
    public function object_cache_dropin_outdated() {
        if ( ! $this->object_cache_dropin_exists() ) {
            return false;
        }

        $dropin = get_plugin_data( WP_CONTENT_DIR . '/object-cache.php' );
        $plugin = get_plugin_data( WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php' );

        if ( $dropin['PluginURI'] === $plugin['PluginURI'] ) {
            return version_compare( $dropin['Version'], $plugin['Version'], '<' );
        }

        return false;
    }

    /**
     * Retrieves the current human-readable status
     *
     * @return string
     */
    public function get_status() {
        global $wp_object_cache;

        if ( defined( 'WP_REDIS_DISABLED' ) && WP_REDIS_DISABLED ) {
            return __( 'Disabled', 'redis-cache' );
        }

        if ( ! $this->object_cache_dropin_exists() ) {
            return __( 'Drop-in not installed', 'redis-cache' );
        }

        if ( ! $this->validate_object_cache_dropin() ) {
            return __( 'Drop-in is invalid', 'redis-cache' );
        }

        if ( method_exists( $wp_object_cache, 'redis_status' ) ) {
            return $wp_object_cache->redis_status()
                ? __( 'Connected', 'redis-cache' )
                : __( 'Not connected', 'redis-cache' );
        }

        return __( 'Unknown', 'redis-cache' );
    }

    /**
     * Retrieves the Redis connection status
     *
     * @return bool|null Boolean Redis connection status if available, null otherwise.
     */
    public function get_redis_status() {
        global $wp_object_cache;

        if ( defined( 'WP_REDIS_DISABLED' ) && WP_REDIS_DISABLED ) {
            return;
        }

        if ( ! $this->validate_object_cache_dropin() ) {
            return;
        }

        if ( ! method_exists( $wp_object_cache, 'redis_status' ) ) {
            return;
        }

        return $wp_object_cache->redis_status();
    }

    /**
     * Returns the redis version if possible
     *
     * @see WP_Object_Cache::redis_version()
     * @return null|string
     */
    public function get_redis_version() {
        global $wp_object_cache;

        if ( defined( 'WP_REDIS_DISABLED' ) && WP_REDIS_DISABLED ) {
            return;
        }

        if ( $this->validate_object_cache_dropin() && method_exists( $wp_object_cache, 'redis_version' ) ) {
            return $wp_object_cache->redis_version();
        }
    }

    /**
     * Returns the currently used redis client (if any)
     *
     * @return null|string
     */
    public function get_redis_client_name() {
        global $wp_object_cache;

        if ( isset( $wp_object_cache->diagnostics[ 'client' ] ) ) {
            return $wp_object_cache->diagnostics[ 'client' ];
        }

        if ( defined( 'WP_REDIS_CLIENT' ) ) {
            return WP_REDIS_CLIENT;
        }
    }

    /**
     * Fetches the redis diagnostics data
     *
     * @return null|array
     */
    public function get_diagnostics() {
        global $wp_object_cache;

        if ( $this->validate_object_cache_dropin() && property_exists( $wp_object_cache, 'diagnostics' ) ) {
            return $wp_object_cache->diagnostics;
        }
    }

    /**
     * Retrieves the redis prefix
     *
     * @return null|mixed
     */
    public function get_redis_prefix() {
        return defined( 'WP_REDIS_PREFIX' ) ? WP_REDIS_PREFIX : null;
    }

    /**
     * Retrieves the redis maximum time to live
     *
     * @return null|mixed
     */
    public function get_redis_maxttl() {
        return defined( 'WP_REDIS_MAXTTL' ) ? WP_REDIS_MAXTTL : null;
    }

    /**
     * Displays admin notices
     *
     * @return void
     */
    public function show_admin_notices() {
        if ( ! defined( 'WP_REDIS_DISABLE_BANNERS' ) || ! WP_REDIS_DISABLE_BANNERS ) {
            $this->pro_notice();
            $this->wc_pro_notice();
        }

        // Only show admin notices to users with the right capability.
        if ( ! current_user_can( is_multisite() ? 'manage_network_options' : 'manage_options' ) ) {
            return;
        }

        if ( $this->object_cache_dropin_exists() ) {
            $url = $this->action_link( 'update-dropin' );

            if ( $this->validate_object_cache_dropin() ) {
                if ( $this->object_cache_dropin_outdated() ) {
                    // translators: %s = Action link to update the drop-in.
                    $message = sprintf( __( 'The Redis object cache drop-in is outdated. Please <a href="%s">update the drop-in</a>.', 'redis-cache' ), $url );
                }
            } else {
                // translators: %s = Action link to update the drop-in.
                $message = sprintf( __( 'A foreign object cache drop-in was found. To use Redis for object caching, please <a href="%s">enable the drop-in</a>.', 'redis-cache' ), $url );
            }

            if ( isset( $message ) ) {
                printf( '<div class="update-nag">%s</div>', wp_kses_post( $message ) );
            }
        }
    }

    /**
     * Executes admin actions
     *
     * @return void
     */
    public function do_admin_actions() {
        global $wp_filesystem;

        if ( isset( $_GET['_wpnonce'], $_GET['action'] ) ) {
            $action = sanitize_key( $_GET['action'] );
            $nonce = sanitize_key( $_GET['_wpnonce'] );

            // Nonce verification.
            foreach ( $this->actions as $name ) {
                if ( $action === $name && ! wp_verify_nonce( $nonce, $action ) ) {
                    return;
                }
            }

            if ( in_array( $action, $this->actions, true ) ) {
                $url = $this->action_link( $action );

                if ( $action === 'flush-cache' ) {
                    wp_cache_flush()
                        ? add_settings_error(
                            'redis-cache',
                            'flush',
                            __( 'Object cache flushed.', 'redis-cache' ),
                            'updated'
                        )
                        : add_settings_error(
                            'redis-cache',
                            'flush',
                            __( 'Object cache could not be flushed.', 'redis-cache' ),
                            'error'
                        );
                }

                // do we have filesystem credentials?
                if ( $this->initialize_filesystem( $url, true ) ) {

                    if ( $action === 'enable-cache' ) {
                        $result = $wp_filesystem->copy(
                            WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php',
                            WP_CONTENT_DIR . '/object-cache.php',
                            true,
                            FS_CHMOD_FILE
                        );

                        /**
                         * Fires on cache enable event
                         *
                         * @since 1.3.5
                         * @param bool $result Whether the filesystem event (copy of the `object-cache.php` file) was successfull.
                         */
                        do_action( 'redis_object_cache_enable', $result );

                        $result
                            ? add_settings_error(
                                'redis-cache',
                                'dropin',
                                __( 'Object cache enabled.', 'redis-cache' ),
                                'updated'
                            )
                            : add_settings_error(
                                'redis-cache',
                                'dropin',
                                __( 'Object cache could not be enabled.', 'redis-cache' ),
                                'error'
                            );
                    }

                    if ( $action === 'disable-cache' ) {
                        $result = $wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );

                        /**
                         * Fires on cache enable event
                         *
                         * @since 1.3.5
                         * @param bool $result Whether the filesystem event (deletion of the `object-cache.php` file) was successfull.
                         */
                        do_action( 'redis_object_cache_disable', $result );

                        $result
                            ? add_settings_error(
                                'redis-cache',
                                'dropin',
                                __( 'Object cache disabled.', 'redis-cache' ),
                                'updated'
                            )
                            : add_settings_error(
                                'redis-cache',
                                'dropin',
                                __( 'Object cache could not be disabled.', 'redis-cache' ),
                                'error'
                            );
                    }

                    if ( $action === 'update-dropin' ) {
                        $result = $wp_filesystem->copy(
                            WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php',
                            WP_CONTENT_DIR . '/object-cache.php',
                            true,
                            FS_CHMOD_FILE
                        );

                        /**
                         * Fires on cache enable event
                         *
                         * @since 1.3.5
                         * @param bool $result Whether the filesystem event (copy of the `object-cache.php` file) was successfull.
                         */
                        do_action( 'redis_object_cache_update_dropin', $result );

                        $result
                            ? add_settings_error(
                                'redis-cache',
                                'dropin',
                                __( 'Updated object cache drop-in and enabled Redis object cache.', 'redis-cache' ),
                                'updated'
                            )
                            : add_settings_error(
                                'redis-cache',
                                'dropin',
                                __( 'Object cache drop-in could not be updated.', 'redis-cache' ),
                                'error'
                            );
                    }
                }

                $messages = get_settings_errors( 'redis-cache' );

                if ( ! empty( $messages ) ) {
                    set_transient( 'settings_errors', $messages, 30 );

                    wp_safe_redirect(
                        network_admin_url( add_query_arg( 'settings-updated', 1, $this->page ) )
                    );
                    exit;
                }
            }
        }
    }

    /**
     * Dismisses the admin notice for the current user
     *
     * @return void
     */
    public function dismiss_notice() {
        if ( isset( $_POST['notice'] ) ) {
            check_ajax_referer( 'roc_dismiss_notice' );

            $notice = sprintf(
                'roc_dismissed_%s',
                sanitize_key( $_POST['notice'] )
            );

            update_user_meta( get_current_user_id(), $notice, '1' );
        }

        wp_die();
    }

    /**
     * Displays a redis cache pro admin notice
     *
     * @return void
     */
    public function pro_notice() {
        $screen = get_current_screen();

        if ( ! isset( $screen->id ) ) {
            return;
        }

        if ( ! in_array( $screen->id, [ 'dashboard', 'dashboard-network' ], true ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( 1 === intval( get_user_meta( get_current_user_id(), 'roc_dismissed_pro_release_notice', true ) ) ) {
            return;
        }

        printf(
            '<div class="notice notice-info is-dismissible" data-dismissible="pro_release_notice" data-nonce="%s"><p><strong>%s</strong> %s</p></div>',
            esc_attr( wp_create_nonce( 'roc_dismiss_notice' ) ),
            esc_html__( 'Object Cache Pro is out!', 'redis-cache' ),
            sprintf(
                // translators: %s = Link to the plugin setting screen.
                wp_kses_post( __( 'A <u>business class</u> object cache backend. Truly reliable, highly-optimized and fully customizable, with a <u>dedicated engineer</u> when you most need it. <a href="%s">Learn more »</a>', 'redis-cache' ) ),
                esc_url( network_admin_url( $this->page ) )
            )
        );
    }

    /**
     * Displays a redis cache pro admin notice specifically for WooCommerce
     *
     * @return void
     */
    public function wc_pro_notice() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        $screen = get_current_screen();

        if ( ! isset( $screen->id ) ) {
            return;
        }

        if ( ! in_array( $screen->id, [ 'edit-shop_order', 'edit-product', 'woocommerce_page_wc-admin' ], true ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( 1 === intval( get_user_meta( get_current_user_id(), 'roc_dismissed_wc_pro_notice', true ) ) ) {
            return;
        }

        printf(
            '<div class="notice woocommerce-message woocommerce-admin-promo-messages is-dismissible" data-dismissible="wc_pro_notice" data-nonce="%s"><p><strong>%s</strong></p><p>%s</p></div>',
            esc_attr( wp_create_nonce( 'roc_dismiss_notice' ) ),
            esc_html__( 'Object Cache Pro + WooCommerce = ❤️', 'redis-cache' ),
            sprintf(
                // translators: %s = Link to the plugin's settings screen.
                wp_kses_post( __( 'Object Cache Pro is a <u>business class</u> object cache that’s highly-optimized for WooCommerce to provide true reliability, peace of mind and faster load times for your store. <a style="color: #bb77ae;" href="%s">Learn more »</a>', 'redis-cache' ) ),
                esc_url( network_admin_url( $this->page ) )
            )
        );
    }

    /**
     * Registers all hooks associated with the shutdown hook
     *
     * @return void
     */
    public function register_shutdown_hooks() {
        if ( ! defined( 'WP_REDIS_DISABLE_COMMENT' ) || ! WP_REDIS_DISABLE_COMMENT ) {
            add_action( 'shutdown', [ $this, 'maybe_print_comment' ], 0 );
        }
    }

    /**
     * Displays the redis cache html comment
     *
     * @return void
     */
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
            'Performance optimized by Redis Object Cache. Learn more: %s',
            'https://wprediscache.com'
        );

        if ( ! WP_DEBUG ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            printf( "\n<!-- %s -->\n", $message );

            return;
        }

        $bytes = strlen( serialize( $wp_object_cache->cache ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize

        $debug = sprintf(
            // translators: %1$d = number of objects. %2$s = human-readable size of cache. %3$s = name of the used client.
            __( 'Retrieved %1$d objects (%2$s) from Redis using %3$s.', 'redis-cache' ),
            $wp_object_cache->cache_hits,
            function_exists( 'size_format' ) ? size_format( $bytes ) : "{$bytes} bytes",
            $wp_object_cache->diagnostics['client']
        );

        printf(
            "<!--\n%s\n\n%s\n-->\n",
            $message, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            esc_html( $debug )
        );
    }

    /**
     * Initializes the WP filesystem API to be ready for use
     *
     * @param string $url    The URL to post the form to.
     * @param bool   $silent Wheather to ask the user for credentials if necessary or not.
     * @return bool
     */
    public function initialize_filesystem( $url, $silent = false ) {
        if ( $silent ) {
            ob_start();
        }

        $credentials = request_filesystem_credentials( $url );

        if ( false === $credentials ) {
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

    /**
     * Test if we can write in the WP_CONTENT_DIR and modify the `object-cache.php` drop-in
     *
     * @return true|WP_Error
     */
    public function test_filesystem_writing() {
        global $wp_filesystem;

        if ( ! $this->initialize_filesystem( '', true ) ) {
            return new WP_Error( 'fs', __( 'Could not initialize filesystem.', 'redis-cache' ) );
        }

        $cachefile = WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php';
        $testfile = WP_CONTENT_DIR . '/.redis-write-test.tmp';

        if ( ! $wp_filesystem->exists( $cachefile ) ) {
            return new WP_Error( 'exists', __( 'Object cache file doesn’t exist.', 'redis-cache' ) );
        }

        if ( $wp_filesystem->exists( $testfile ) ) {
            if ( ! $wp_filesystem->delete( $testfile ) ) {
                return new WP_Error( 'delete', __( 'Test file exists, but couldn’t be deleted.', 'redis-cache' ) );
            }
        }

        if ( ! $wp_filesystem->copy( $cachefile, $testfile, true, FS_CHMOD_FILE ) ) {
            return new WP_Error( 'copy', __( 'Failed to copy test file.', 'redis-cache' ) );
        }

        if ( ! $wp_filesystem->exists( $testfile ) ) {
            return new WP_Error( 'exists', __( 'Copied test file doesn’t exist.', 'redis-cache' ) );
        }

        $meta = get_file_data( $testfile, [ 'Version' => 'Version' ] );

        if ( $meta['Version'] !== WP_REDIS_VERSION ) {
            return new WP_Error( 'version', __( 'Couldn’t verify test file contents.', 'redis-cache' ) );
        }

        if ( ! $wp_filesystem->delete( $testfile ) ) {
            return new WP_Error( 'delete', __( 'Copied test file couldn’t be deleted.', 'redis-cache' ) );
        }

        return true;
    }

    /**
     * Calls the drop-in update method if necessary
     *
     * @return void
     */
    public function maybe_update_dropin() {
        if ( defined( 'WP_REDIS_DISABLE_DROPIN_AUTOUPDATE' ) && WP_REDIS_DISABLE_DROPIN_AUTOUPDATE ) {
            return;
        }

        if ( $this->object_cache_dropin_outdated() ) {
            add_action( 'shutdown', [ $this, 'update_dropin' ] );
        }
    }

    /**
     * Updates the `object-cache.php` drop-in
     *
     * @return void
     */
    public function update_dropin() {
        global $wp_filesystem;

        if ( ! $this->validate_object_cache_dropin() ) {
            return;
        }

        if ( $this->initialize_filesystem( '', true ) ) {
            $result = $wp_filesystem->copy(
                WP_REDIS_PLUGIN_PATH . '/includes/object-cache.php',
                WP_CONTENT_DIR . '/object-cache.php',
                true,
                FS_CHMOD_FILE
            );

            /**
             * Fires on cache enable event
             *
             * @since 1.3.5
             * @param bool $result Whether the filesystem event (copy of the `object-cache.php` file) was successfull.
             */
            do_action( 'redis_object_cache_update_dropin', $result );
        }
    }

    /**
     * Plugin deactivation hook
     *
     * @param string $plugin Plugin basename.
     * @return void
     */
    public function on_deactivation( $plugin ) {
        global $wp_filesystem;

        ob_start();

        if ( $plugin === WP_REDIS_BASENAME ) {
            $timestamp = wp_next_scheduled( 'rediscache_discard_metrics' );

            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, 'rediscache_discard_metrics' );
            }

            wp_cache_flush();

            if ( $this->validate_object_cache_dropin() && $this->initialize_filesystem( '', true ) ) {
                $wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );
            }
        }

        ob_end_clean();
    }

    /**
     * Helper method to retrieve a nonced plugin action link
     *
     * @param string $action The action to be executed once the link is followed.
     * @return string
     */
    public function action_link( $action ) {
        if ( ! in_array( $action, $this->actions, true ) ) {
            return '';
        }

        return wp_nonce_url(
            network_admin_url( add_query_arg( 'action', $action, $this->page ) ),
            $action
        );
    }
}
