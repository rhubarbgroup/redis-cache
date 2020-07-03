<?php

defined( '\\ABSPATH' ) || exit;

?>
<div class="wrap">

    <h1><?php esc_html_e( 'Redis Object Cache', 'redis-cache' ); ?></h1>

    <div class="redis-content-wrap">

        <div id="redis-main-container" class="redis-content-cell">

            <h2 class="nav-tab-wrapper" id="redis-tabs">
                <a class="nav-tab nav-tab-active" data-target="#overview" href="#top#overview">
                    <?php esc_html_e( 'Overview', 'redis-cache' ); ?>
                </a>
                <a class="nav-tab" data-target="#metrics" href="#top#metrics">
                    <?php esc_html_e( 'Metrics', 'redis-cache' ); ?>
                </a>
                <a class="nav-tab" data-target="#diagnostics" href="#top#diagnostics">
                    <?php esc_html_e( 'Diganostics', 'redis-cache' ); ?>
                </a>
            </h2>

            <div class="sections">

                <div id="overview" class="section section-overview active">

                    <?php include 'tabs/overview.php'; ?>

                </div>

                <div id="metrics" class="section section-metrics">

                    <?php include 'tabs/metrics.php'; ?>

                </div>

                <div id="diagnostics" class="section section-diagnostics">

                    <?php include 'tabs/diagnostics.php'; ?>

                </div>

            </div>

        </div>

        <div id="redis-sidebar-container" class="redis-content-cell">

            <div class="section-pro">

                <div class="redis-sidebar__title">
                    Get more out of your Redis server
                </div>

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

</div>
