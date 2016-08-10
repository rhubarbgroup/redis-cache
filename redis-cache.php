<?php
/*
Plugin Name: Redis Object Cache
Plugin URI: http://wordpress.org/plugins/redis-cache/
Description: A persistent object cache backend powered by Redis. Supports Predis, PhpRedis, HHVM, replication and clustering.
Version: 1.3.2
Text Domain: redis-cache
Domain Path: /languages
Author: Till Krüss
Author URI: https://till.im/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit;

class RedisObjectCache {

	private $page;
	private $screen = 'settings_page_redis-cache';
	private $actions = array( 'enable-cache', 'disable-cache', 'flush-cache', 'update-dropin', 'flush-site-cache' );

	public function __construct() {

		load_plugin_textdomain( 'redis-cache', false, 'redis-cache/languages' );

		register_deactivation_hook(__FILE__, array( $this, 'on_deactivation' ) );

		$this->page = is_multisite() ? 'settings.php?page=redis-cache' : 'options-general.php?page=redis-cache';

		add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', array( $this, 'add_admin_menu_page' ) );
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'load-' . $this->screen, array( $this, 'do_admin_actions' ) );
		add_action( 'load-' . $this->screen, array( $this, 'add_admin_page_notices' ) );
		if ( is_multisite() ) {
			add_filter( 'manage_sites_action_links', array( $this, 'add_redis_action' ), null, 2 );
			add_action( 'network_admin_notices', array( $this, 'network_admin_notices' ) );
		}

		add_filter( sprintf(
			'%splugin_action_links_%s',
			is_multisite() ? 'network_admin_' : '',
			plugin_basename( __FILE__ )
		), array( $this, 'add_plugin_actions_links' ) );

	}

	public function add_redis_action( $actions, $blog_id ) {

		// add "Flush Cache" action to each site list row
		$actions[ 'flush' ] = '<a href="' . wp_nonce_url( network_admin_url( add_query_arg( array( 'action' => 'flush-site-cache', 'blog_id' => $blog_id ), $this->page ) ), 'flush-site-cache' ) . '">Flush Cache</a>';
		return $actions;

	}

	public function network_admin_notices() {

		if ( !isset( $_GET[ 'flush' ] ) ) {
			return false;
		} else {
			if ( $_GET[ 'flush' ] === 'success' ) {
				echo '<div class="notice-success notice is-dismissible"><p>Flush succeed!</p></div>';
			} else if ( $_GET[ 'flush' ] === 'error' ) {
				echo '<div class="notice-error  notice is-dismissible"><p>Flush failed! There is an error or nothing to flush.</p></div>';
			} else if ( $_GET[ 'flush' ] === 'down' ) {
				echo '<div class="notice-error  notice is-dismissible"><p>Flush failed! Redis server is down or Object Cache is disabled.</p></div>';
			}

			$_SERVER[ 'REQUEST_URI' ] = remove_query_arg( 'flush', $_SERVER[ 'REQUEST_URI' ] );
		}

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
			$plugin = get_plugin_data( __FILE__ );
			wp_enqueue_style( 'redis-cache', plugin_dir_url( __FILE__ ) . 'includes/admin-page.css', null, $plugin[ 'Version' ] );
		}

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

		if ( ! $this->object_cache_dropin_exists() ) {
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

		if ( $this->validate_object_cache_dropin() ) {
			return $wp_object_cache->redis_status();
		}

		return;

	}

	public function get_redis_client_name() {

		global $wp_object_cache;

		if ( isset( $wp_object_cache->redis_client ) ) {
			return $wp_object_cache->redis_client;
		}

		if ( defined( 'WP_REDIS_CLIENT' ) ) {
			return WP_REDIS_CLIENT;
		}

		return null;

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

				$message = sprintf( __( 'Another object cache drop-in was found. To use Redis, <a href="%s">please replace it now</a>.', 'redis-cache' ), $url );

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
					$message = __( 'Object Cache enabled.', 'redis-cache' );
					break;
				case 'enable-cache-failed':
					$error = __( 'Object Cache could not be enabled.', 'redis-cache' );
					break;
				case 'cache-disabled':
					$message = __( 'Object Cache disabled.', 'redis-cache' );
					break;
				case 'disable-cache-failed':
					$error = __( 'Object Cache could not be disabled.', 'redis-cache' );
					break;
				case 'cache-flushed':
					$message = __( 'Object Cache flushed.', 'redis-cache' );
					break;
				case 'flush-cache-failed':
					$error = __( 'Object Cache could not be flushed.', 'redis-cache' );
					break;
				case 'dropin-updated':
					$message = __( 'Drop-in updated.', 'redis-cache' );
					break;
				case 'update-dropin-failed':
					$error = __( 'Drop-in could not be updated.', 'redis-cache' );
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

				if ( $action === 'flush-site-cache' && isset( $_GET[ 'blog_id' ] ) ) {

					if ( !$this->validate_object_cache_dropin() ) {
						wp_safe_redirect( network_admin_url( add_query_arg( 'flush', 'down', 'sites.php' ) ) );
						exit;
					}

					if ( wp_cache_delete_branch( $_GET[ 'blog_id' ] ) ) {
						wp_safe_redirect( network_admin_url( add_query_arg( 'flush', 'success', 'sites.php' ) ) );
					} else {
						wp_safe_redirect( network_admin_url( add_query_arg( 'flush', 'error', 'sites.php' ) ) );
					}
					exit;

				}

				$url = wp_nonce_url( network_admin_url( add_query_arg( 'action', $action, $this->page ) ), $action );

				if ( $action === 'flush-cache' ) {
					$message = wp_cache_flush() ? 'cache-flushed' : 'flush-cache-failed';
				}

				// do we have filesystem credentials?
				if ( $this->initialize_filesystem( $url, true ) ) {

					switch ( $action ) {

						case 'enable-cache':
							$result = $wp_filesystem->copy( plugin_dir_path( __FILE__ ) . '/includes/object-cache.php', WP_CONTENT_DIR . '/object-cache.php', true );
							$message = $result ? 'cache-enabled' : 'enable-cache-failed';
							break;

						case 'disable-cache':
							$result = $wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );
							$message = $result ? 'cache-disabled' : 'disable-cache-failed';
							break;

						case 'update-dropin':
							$result = $wp_filesystem->copy( plugin_dir_path( __FILE__ ) . '/includes/object-cache.php', WP_CONTENT_DIR . '/object-cache.php', true );
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

	public function on_deactivation() {

		global $wp_filesystem;

		if ( $this->validate_object_cache_dropin() && $this->initialize_filesystem( '', true ) ) {
			$wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );
		}

	}

}

new RedisObjectCache;
