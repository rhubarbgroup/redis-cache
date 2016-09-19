<?php

WP_CLI::add_command( 'redis', 'RedisObjectCache_CLI_Commands' );

class RedisObjectCache_CLI_Commands extends WP_CLI_Command {

	/**
	 * Show the Redis object cache status and (when possible) client.
	 *
	 * ## EXAMPLES
	 *
	 *     wp redis status
	 *
	 */
	public function status() {

		$plugin = $GLOBALS[ 'redisObjectCache' ];
		$status = $plugin->get_status();
		$client = $plugin->get_redis_client_name();


		switch ($status) {
			case __( 'Disabled', 'redis-cache' ):
				$status = WP_CLI::colorize( "%y{$status}%n" );
				break;
			case __( 'Connected', 'redis-cache' ):
				$status = WP_CLI::colorize( "%g{$status}%n" );
				break;
			case __( 'Not Connected', 'redis-cache' ):
				$status = WP_CLI::colorize( "%r{$status}%n" );
				break;
		}

		WP_CLI::line( 'Status: ' . $status );

		if ( ! is_null( $client ) ) {
			WP_CLI::line( 'Client: ' . $client );
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
	 *
	 */
	public function enable() {

		global $wp_filesystem;

		$plugin = $GLOBALS[ 'redisObjectCache' ];

		if ( $plugin->object_cache_dropin_exists() ) {

			if ($plugin->validate_object_cache_dropin()) {
				WP_CLI::line( __( 'Redis object cache already enabled.', 'redis-cache' ) );
			} else {
				WP_CLI::error( __('An unknown object cache drop-in was found. To use Redis run: wp redis update-dropin.', 'redis-cache') );
			}

		} else {

			WP_Filesystem();

			if ($wp_filesystem->copy( WP_PLUGIN_DIR . '/redis-cache/includes/object-cache.php', WP_CONTENT_DIR . '/object-cache.php', true )) {
				WP_CLI::success( __( 'Object Cache enabled.', 'redis-cache' ) );
			} else {
				WP_CLI::error( __( 'Object Cache could not be enabled.', 'redis-cache' ) );
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
	 *
	 */
	public function disable() {

		global $wp_filesystem;

		$plugin = $GLOBALS[ 'redisObjectCache' ];

		if ( ! $plugin->object_cache_dropin_exists() ) {

			WP_CLI::error( __( 'No object cache drop-in found.', 'redis-cache' ) );

		} else {

			if ( ! $plugin->validate_object_cache_dropin() ) {

				WP_CLI::error( __('An unknown object cache drop-in was found. To use Redis run: wp redis update-dropin.', 'redis-cache') );

			} else {

				WP_Filesystem();

				if ($wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' )) {
					WP_CLI::success( __( 'Object Cache disabled.', 'redis-cache' ) );
				} else {
					WP_CLI::error( __( 'Object Cache could not be disabled.', 'redis-cache' ) );
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

		if ($wp_filesystem->copy( WP_PLUGIN_DIR . '/redis-cache/includes/object-cache.php', WP_CONTENT_DIR . '/object-cache.php', true )) {
			WP_CLI::success( __( 'Updated object cache drop-in and enabled Redis object cache.', 'redis-cache' ) );
		} else {
			WP_CLI::error( __( 'Object Cache drop-in could not be updated.', 'redis-cache' ) );
		}

	}

}
