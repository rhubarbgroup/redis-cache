
<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">

	<h1><?php _e( 'Redis Object Cache', 'redis-cache' ); ?></h1>

	<h2 class="title"><?php _e( 'Overview', 'redis-cache' ); ?></h2>

	<table class="form-table">

		<tr>
			<th><?php _e( 'Status:', 'redis-cache' ); ?></th>
			<td><code><?php echo $this->get_status(); ?></code></td>
		</tr>

		<?php if ( ! is_null( $this->get_redis_client_name() ) ) : ?>
			<tr>
				<th><?php _e( 'Client:', 'redis-cache' ); ?></th>
				<td><code><?php echo esc_html( $this->get_redis_client_name() ); ?></code></td>
			</tr>
		<?php endif; ?>

		<?php if ( ! is_null( $this->get_redis_cachekey_prefix() ) && trim( $this->get_redis_cachekey_prefix() ) !== '' ) : ?>
			<tr>
				<th><?php _e( 'Key Prefix:', 'redis-cache' ); ?></th>
				<td><code><?php echo esc_html( $this->get_redis_cachekey_prefix() ); ?></code></td>
			</tr>
		<?php endif; ?>

		<?php if ( ! is_null( $this->get_redis_maxttl() ) ) : ?>
			<tr>
				<th><?php _e( 'Max. TTL:', 'redis-cache' ); ?></th>
				<td><code><?php echo esc_html( $this->get_redis_maxttl() ); ?></code></td>
			</tr>
		<?php endif; ?>

	</table>

	<p class="submit">

		<?php if ( $this->get_redis_status() ) : ?>
			<a href="<?php echo wp_nonce_url( network_admin_url( add_query_arg( 'action', 'flush-cache', $this->page ) ), 'flush-cache' ); ?>" class="button button-primary button-large"><?php _e( 'Flush Cache', 'redis-cache' ); ?></a> &nbsp;
		<?php endif; ?>

		<?php if ( ! $this->object_cache_dropin_exists() ) : ?>
			<a href="<?php echo wp_nonce_url( network_admin_url( add_query_arg( 'action', 'enable-cache', $this->page ) ), 'enable-cache' ); ?>" class="button button-primary button-large"><?php _e( 'Enable Object Cache', 'redis-cache' ); ?></a>
		<?php elseif ( $this->validate_object_cache_dropin() ) : ?>
			<a href="<?php echo wp_nonce_url( network_admin_url( add_query_arg( 'action', 'disable-cache', $this->page ) ), 'disable-cache' ); ?>" class="button button-secondary button-large delete"><?php _e( 'Disable Object Cache', 'redis-cache' ); ?></a>
		<?php endif; ?>

	</p>

	<h2 class="title"><?php _e( 'Servers', 'redis-cache' ); ?></h2>

	<?php $this->show_servers_list(); ?>

    <?php if ( isset( $_GET[ 'diagnostics' ] ) ) : ?>

		<h2 class="title"><?php _e( 'Diagnostics', 'redis-cache' ); ?></h2>

	    <textarea class="large-text readonly" rows="20" readonly><?php include dirname( __FILE__ ) . '/diagnostics.php'; ?></textarea>

	<?php else : ?>

		<p><a href="<?php echo network_admin_url( add_query_arg( 'diagnostics', '1', $this->page ) ); ?>"><?php _e( 'Show Diagnostics', 'redis-cache' ); ?></a></p>

	<?php endif; ?>

</div>
