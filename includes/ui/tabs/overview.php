<?php

defined( '\\ABSPATH' ) || exit;

$plugin_status = redis_object_cache()->get_status();

$redis_client = redis_object_cache()->get_redis_client_name();
$redis_dropin = redis_object_cache()->validate_object_cache_dropin();
$redis_prefix = redis_object_cache()->get_redis_prefix();
$redis_maxttl = redis_object_cache()->get_redis_maxttl();
$redis_version = redis_object_cache()->get_redis_version();
$redis_status = redis_object_cache()->get_redis_status();

$dropin_validation = redis_object_cache()->validate_object_cache_dropin();

$diagnostics = redis_object_cache()->get_diagnostics();

?>
<table class="form-table">

    <?php if ( ! is_null( $redis_client ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Client:', 'redis-cache' ); ?></th>
            <td>
                <code><?php echo esc_html( $redis_client ); ?></code>

                <?php if ( stripos( (string) $redis_client, 'predis' ) === 0 ) : ?>
                    <?php if ( version_compare( phpversion(), '7.2', '<' ) ) : ?>
                        <p class="description is-notice">
                            <?php esc_html_e( 'The Predis library is no longer maintained. Consider switching over to Credis or PhpRedis to avoid compatiblity issues in the future.', 'redis-cache' ); ?>
                        </p>
                    <?php else : ?>
                        <p class="description is-notice">
                            <?php esc_html_e( 'The Predis library is not reliable on PHP 7.2 and newer. Consider switching over to Credis or PhpRedis to avoid compatiblity issues.', 'redis-cache' ); ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>

    <tr>
        <th><?php esc_html_e( 'Drop-in:', 'redis-cache' ); ?></th>
        <td>
            <code><?php echo esc_html( $redis_dropin ? esc_html_e( 'Valid', 'redis-cache' ) : esc_html_e( 'Invalid', 'redis-cache' ) ); ?></code>
        </td>
    </tr>

    <?php if ( defined( 'WP_REDIS_DISABLED' ) && WP_REDIS_DISABLED ) : ?>
        <tr>
            <th><?php esc_html_e( 'Disabled:', 'redis-cache' ); ?></th>
            <td>
                <code><?php esc_html_e( 'Yes', 'redis-cache' ); ?></code>
            </td>
        </tr>
    <?php endif; ?>

    <?php if ( ! is_null( $redis_prefix ) && trim( $redis_prefix ) !== '' ) : ?>
        <tr>
            <th><?php esc_html_e( 'Key Prefix:', 'redis-cache' ); ?></th>
            <td>
                <code><?php echo esc_html( $redis_prefix ); ?></code>
            </td>
        </tr>
    <?php endif; ?>

    <?php if ( ! is_null( $redis_maxttl ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Max. TTL:', 'redis-cache' ); ?></th>
            <td>
                <code><?php echo esc_html( $redis_maxttl ); ?></code>

                <?php if ( ! is_int( $redis_maxttl ) && ! ctype_digit( $redis_maxttl ) ) : ?>
                    <p class="description is-notice">
                        <?php esc_html_e( 'This doesn’t appear to be a valid number.', 'redis-cache' ); ?>
                    </p>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>

</table>

<h2 class="title"><?php esc_html_e( 'Connection', 'redis-cache' ); ?></h2>

<table class="form-table">

    <tr>
        <th><?php esc_html_e( 'Status:', 'redis-cache' ); ?></th>
        <td><code><?php echo $plugin_status ?></code></td>
    </tr>

    <?php if ( ! empty( $diagnostics['host'] ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Host:', 'redis-cache' ); ?></th>
            <td><code><?php echo esc_html( $diagnostics['host'] ); ?></code></td>
        </tr>
    <?php endif; ?>

    <?php if ( isset( $diagnostics['cluster'] ) && is_array( $diagnostics['cluster'] ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Cluster:', 'redis-cache' ); ?></th>
            <td>
                <ul>
                    <?php foreach ( $diagnostics['cluster'] as $node ) : ?>
                        <li><code><?php echo esc_html( $node ); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </td>
        </tr>
    <?php endif; ?>

    <?php if ( isset( $diagnostics['shards'] ) && is_array( $diagnostics['shards'] ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Shards:', 'redis-cache' ); ?></th>
            <td>
                <ul>
                    <?php foreach ( $diagnostics['shards'] as $node ) : ?>
                        <li><code><?php echo esc_html( $node ); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </td>
        </tr>
    <?php endif; ?>

    <?php if ( isset( $diagnostics['servers'] ) && is_array( $diagnostics['servers'] ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Servers:', 'redis-cache' ); ?></th>
            <td>
                <ul>
                    <?php foreach ( $diagnostics['servers'] as $node ) : ?>
                        <li><code><?php echo esc_html( $node ); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </td>
        </tr>
    <?php endif; ?>

    <?php if ( ! empty( $diagnostics['port'] ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Port:', 'redis-cache' ); ?></th>
            <td><code><?php echo esc_html( $diagnostics['port'] ); ?></code></td>
        </tr>
    <?php endif; ?>

    <?php if ( isset( $diagnostics['password'] ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Password:', 'redis-cache' ); ?></th>
            <td><code><?php echo str_repeat( '&#8226;', 8 ); ?></code></td>
        </tr>
    <?php endif; ?>

    <?php if ( isset( $diagnostics['database'] ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Database:', 'redis-cache' ); ?></th>
            <td><code><?php echo esc_html( $diagnostics['database'] ); ?></code></td>
        </tr>
    <?php endif; ?>

    <?php if ( isset( $diagnostics['timeout'] ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Connection Timeout:', 'redis-cache' ); ?></th>
            <td><code><?php echo sprintf( esc_html__( '%ss', 'redis-cache' ), $diagnostics['timeout'] ); ?></code></td>
        </tr>
    <?php endif; ?>

    <?php if ( isset( $diagnostics['read_timeout'] ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Read Timeout:', 'redis-cache' ); ?></th>
            <td><code><?php echo sprintf( esc_html__( '%ss', 'redis-cache' ), $diagnostics['read_timeout'] ); ?></code></td>
        </tr>
    <?php endif; ?>

    <?php if ( isset( $diagnostics['retry_interval'] ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Retry Interval:', 'redis-cache' ); ?></th>
            <td><code><?php echo sprintf( esc_html__( '%sms', 'redis-cache' ), $diagnostics['retry_interval'] ); ?></code></td>
        </tr>
    <?php endif; ?>

    <tr>
        <th><?php esc_html_e( 'Redis Version:', 'redis-cache' ); ?></th>
        <td><code><?php echo $redis_version ?: esc_html_e( 'Unknown', 'redis-cache' ); ?></code></td>
    </tr>

</table>

<p class="submit">

    <?php if ( $redis_status ) : ?>
        <a href="<?php echo redis_object_cache()->action_link( 'flush-cache' ); ?>" class="button button-primary button-large"><?php esc_html_e( 'Flush Cache', 'redis-cache' ); ?></a> &nbsp;
    <?php endif; ?>

    <?php if ( $dropin_validation ) : ?>
        <a href="<?php echo redis_object_cache()->action_link( 'disable-cache' ); ?>" class="button button-secondary button-large"><?php esc_html_e( 'Disable Object Cache', 'redis-cache' ); ?></a>
    <?php else : ?>
        <a href="<?php echo redis_object_cache()->action_link( 'enable-cache' ); ?>" class="button button-primary button-large"><?php esc_html_e( 'Enable Object Cache', 'redis-cache' ); ?></a>
    <?php endif; ?>

</p>
