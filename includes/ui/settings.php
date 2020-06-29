
<div class="wrap">

    <h1><?php esc_html_e( 'Redis Object Cache', 'redis-cache' ); ?></h1>

    <div class="sections">

        <div class="section-overview">

            <h2 class="title"><?php esc_html_e( 'Overview', 'redis-cache' ); ?></h2>

            <table class="form-table">

                <?php $redisClient = $this->get_redis_client_name(); ?>
                <?php $redisDropin = $this->validate_object_cache_dropin(); ?>
                <?php $redisPrefix = $this->get_redis_prefix(); ?>
                <?php $redisMaxTTL = $this->get_redis_maxttl(); ?>

                <?php if ( ! is_null( $redisClient ) ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Client:', 'redis-cache' ); ?></th>
                        <td>
                            <code><?php echo esc_html( $redisClient ); ?></code>

                            <?php if ( stripos( (string) $redisClient, 'predis' ) === 0 ) : ?>
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
                        <code><?php echo esc_html( $redisDropin ? esc_html_e( 'Valid', 'redis-cache' ) : esc_html_e( 'Invalid', 'redis-cache' ) ); ?></code>
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

                <?php if ( ! is_null( $redisPrefix ) && trim( $redisPrefix ) !== '' ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Key Prefix:', 'redis-cache' ); ?></th>
                        <td>
                            <code><?php echo esc_html( $redisPrefix ); ?></code>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if ( ! is_null( $redisMaxTTL ) ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Max. TTL:', 'redis-cache' ); ?></th>
                        <td>
                            <code><?php echo esc_html( $redisMaxTTL ); ?></code>

                            <?php if ( ! is_int( $redisMaxTTL ) && ! ctype_digit( $redisMaxTTL ) ) : ?>
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

                <?php $diagnostics = $this->get_diagnostics(); ?>

                <tr>
                    <th><?php esc_html_e( 'Status:', 'redis-cache' ); ?></th>
                    <td><code><?php echo $this->get_status(); ?></code></td>
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
                    <td><code><?php echo $this->get_redis_version() ?: esc_html_e( 'Unknown', 'redis-cache' ); ?></code></td>
                </tr>

            </table>

            <p class="submit">

                <?php if ( $this->get_redis_status() ) : ?>
                    <a href="<?php echo wp_nonce_url( network_admin_url( add_query_arg( 'action', 'flush-cache', $this->page ) ), 'flush-cache' ); ?>" class="button button-primary button-large"><?php esc_html_e( 'Flush Cache', 'redis-cache' ); ?></a> &nbsp;
                <?php endif; ?>

                <?php if ( $this->validate_object_cache_dropin() ) : ?>
                    <a href="<?php echo wp_nonce_url( network_admin_url( add_query_arg( 'action', 'disable-cache', $this->page ) ), 'disable-cache' ); ?>" class="button button-secondary button-large"><?php esc_html_e( 'Disable Object Cache', 'redis-cache' ); ?></a>
                <?php else : ?>
                    <a href="<?php echo wp_nonce_url( network_admin_url( add_query_arg( 'action', 'enable-cache', $this->page ) ), 'enable-cache' ); ?>" class="button button-primary button-large"><?php esc_html_e( 'Enable Object Cache', 'redis-cache' ); ?></a>
                <?php endif; ?>

            </p>

        </div>

        <div class="section-metrics">

            <h2 class="title">
                <?php esc_html_e( 'Metrics', 'redis-cache' ); ?>
            </h2>

            <div id="widget-redis-stats" class="card">

                <ul>
                    <li>
                        <a class="active" href="#" data-chart="time">Time</a>
                    </li>
                    <li>
                        <a href="#" data-chart="bytes">Bytes</a>
                    </li>
                    <li>
                        <a href="#" data-chart="ratio">Ratio</a>
                    </li>
                    <li>
                        <a href="#" data-chart="calls">Calls</a>
                    </li>
                </ul>

                <div id="redis-stats-chart"></div>

            </div>

        </div>

        <div class="section-diagnostics">

            <h2 class="title">
                <?php esc_html_e( 'Diagnostics', 'redis-cache' ); ?>
            </h2>

            <?php if ( isset( $_GET['diagnostics'] ) ) : ?>

                <textarea class="large-text readonly" rows="20" readonly><?php include dirname( __FILE__ ) . '/diagnostics.php'; ?></textarea>

            <?php else : ?>

                <p>
                    <a class="button button-secondary" href="<?php echo network_admin_url( add_query_arg( 'diagnostics', '1', $this->page ) ); ?>">
                        <?php esc_html_e( 'Show Diagnostics', 'redis-cache' ); ?>
                    </a>
                </p>

            <?php endif; ?>

        </div>


        <div class="section-pro">

            <div class="card">
                <h2 class="title">
                    Redis Cache Pro
                </h2>
                <p>
                    <b>A business class object cache backend.</b> Truly reliable, highly-optimized and fully customizable, with a <u>dedicated engineer</u> when you most need it.
                </p>
                <ul>
                    <li>Rewritten for raw performance</li>
                    <li>100% WordPress API compliant</li>
                    <li>Faster serialization and compression</li>
                    <li>Easy debugging &amp; logging</li>
                    <li>Cache analytics and preloading</li>
                    <li>Fully unit tested (100% code coverage)</li>
                    <li>Secure connections with TLS</li>
                    <li>Health checks via WordPress &amp; WP CLI</li>
                    <li>Optimized for WooCommerce, Jetpack &amp; Yoast SEO</li>
                </ul>
                <p>
                    <a class="button button-primary" target="_blank" rel="noopener" href="https://wprediscache.com/?utm_source=wp-plugin&amp;utm_medium=settings">
                        <?php esc_html_e( 'Learn more', 'redis-cache' ); ?>
                    </a>
                </p>
            </div>

            <?php $isPhp7 = version_compare( phpversion(), '7.0', '>=' ); ?>
            <?php $isPhpRedis311 = version_compare( phpversion( 'redis' ), '3.1.1', '>=' ); ?>
            <?php $phpRedisInstalled = (bool) phpversion( 'redis' ); ?>

            <?php if ( $isPhp7 && $isPhpRedis311 ) : ?>

                <p class="compatiblity">
                    <span class="dashicons dashicons-yes"></span>
                    <span><?php esc_html_e( 'Your site meets the system requirements for the Pro version.', 'redis-cache' ); ?></span>
                </p>

            <?php else : ?>

                <p class="compatiblity">
                    <span class="dashicons dashicons-no"></span>
                    <span><?php echo wp_kses_post( __( 'Your site <i>does not</i> meet the system requirements for the Pro version:', 'redis-cache' ) ); ?></span>
                </p>

                <ul>
                    <?php if ( ! $isPhp7 ) : ?>
                        <li>
                            <?php printf( esc_html__( 'The current version of PHP (%s) is too old. PHP 7.0 or newer is required.', 'redis-cache' ), phpversion() ); ?>
                        </li>
                    <?php endif; ?>

                    <?php if ( ! $phpRedisInstalled ) : ?>
                        <li>
                            <?php printf( esc_html__( 'The PhpRedis extension is not installed.', 'redis-cache' ), phpversion() ); ?>
                        </li>
                    <?php elseif ( ! $isPhpRedis311 ) : ?>
                        <li>
                            <?php printf( esc_html__( 'The current version of the PhpRedis extension (%s) is too old. PhpRedis 3.1.1 or newer is required.', 'redis-cache' ), phpversion( 'redis' ) ); ?>
                        </li>
                    <?php endif; ?>
                </ul>

            <?php endif; ?>

        </div>

    </div>

</div>
