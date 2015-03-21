
<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">

	<h2><?php _e( 'Redis Object Cache', 'redis-cache' ); ?></h2>

	<?php settings_errors(); ?>

	<table class="form-table">

		<tr valign="top">
			<th scope="row"><?php _e( 'Redis Status', 'redis-cache' ); ?></th>
			<td><code><?php echo $this->get_redis_status(); ?></code></td>
		</tr>

		<tr valign="top">
			<th scope="row"><?php _e( 'Configuration', 'redis-cache' ); ?></th>
			<td>
				<table>
					<?php if ( ! is_null( $this->get_redis_client_name() ) ) : ?>
						<tr>
							<td><?php _e( 'Client:', 'redis-cache' ); ?></td>
							<td><code><?php echo $this->get_redis_client_name(); ?></code></td>
						</tr>
					<?php endif; ?>
					<tr>
						<td><?php _e( 'Protocol:', 'redis-cache' ); ?></td>
						<td><code><?php echo strtoupper( esc_html( $this->get_redis_scheme() ) ); ?></code></td>
					</tr>
					<?php if ( strcasecmp( 'tcp', $this->get_redis_scheme() ) === 0 ) : ?>
						<tr>
							<td><?php _e( 'Host:', 'redis-cache' ); ?></td>
							<td><code><?php echo esc_html( $this->get_redis_host() ); ?></code></td>
						</tr>

						<tr>
							<td><?php _e( 'Port:', 'redis-cache' ); ?></td>
							<td><code><?php echo esc_html( $this->get_redis_port() ); ?></code></td>
						</tr>
					<?php endif; ?>
					<?php if ( strcasecmp( 'unix', $this->get_redis_scheme() ) === 0 ) : ?>
						<tr>
							<td><?php _e( 'Path:', 'redis-cache' ); ?></td>
							<td><code><?php echo esc_html( $this->get_redis_path() ); ?></code></td>
						</tr>
					<?php endif; ?>
					<tr>
						<td><?php _e( 'Database:', 'redis-cache' ); ?></td>
						<td> <code><?php echo esc_html( $this->get_redis_database() ); ?></code></td>
					</tr>
					<?php if ( ! is_null( $this->get_redis_password() ) ) : ?>
						<tr>
							<td><?php _e( 'Password:', 'redis-cache' ); ?></td>
							<td><code><?php echo str_repeat( '*', strlen( $this->get_redis_password() ) ); ?></code></td>
						</tr>
					<?php endif; ?>
					<?php if ( ! is_null( $this->get_redis_cachekey_prefix() ) && trim( $this->get_redis_cachekey_prefix() ) !== '' ) : ?>
						<tr>
							<td><?php _e( 'Key Prefix:', 'redis-cache' ); ?></td>
							<td><code><?php echo esc_html( $this->get_redis_cachekey_prefix() ); ?></code></td>
						</tr>
					<?php endif; ?>
					<?php if ( ! is_null( $this->get_redis_maxttl() ) ) : ?>
						<tr>
							<td><?php _e( 'Max. TTL:', 'redis-cache' ); ?></td>
							<td><code><?php echo esc_html( $this->get_redis_maxttl() ); ?></code></td>
						</tr>
					<?php endif; ?>
				</table>
			</td>
		</tr>

	</table>

	<p class="submit">

		<?php if ( strcasecmp( 'connected', $this->get_redis_status() ) === 0 ) : ?>
			<a href="<?php echo wp_nonce_url( admin_url( add_query_arg( 'action', 'flush-cache', $this->admin_page ) ), 'flush-cache' ); ?>" class="button button-primary button-large"><?php _e( 'Flush Cache', 'redis-cache' ); ?></a>
			&nbsp;
		<?php endif; ?>

		<?php if ( ! $this->object_cache_dropin_exists() ) : ?>
			<a href="<?php echo wp_nonce_url( admin_url( add_query_arg( 'action', 'enable-cache', $this->admin_page ) ), 'enable-cache' ); ?>" class="button button-primary button-large"><?php _e( 'Enable Object Cache', 'redis-cache' ); ?></a>
		<?php elseif ( $this->validate_object_cache_dropin() ) : ?>
			<a href="<?php echo wp_nonce_url( admin_url( add_query_arg( 'action', 'disable-cache', $this->admin_page ) ), 'disable-cache' ); ?>" class="button button-secondary button-large delete"><?php _e( 'Disable Object Cache', 'redis-cache' ); ?></a>
		<?php endif; ?>

	</p>

</div>
