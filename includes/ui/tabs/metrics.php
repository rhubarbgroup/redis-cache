<?php
/**
 * Metrics tab template
 *
 * @package Rhubarb\RedisCache
 */

defined( '\\ABSPATH' ) || exit;

?>

<div id="widget-redis-stats" class="card">

    <ul>
        <li>
            <a href="#" class="active" data-chart="time">
                <?php esc_html_e( 'Time', 'redis-cache' ); ?>
            </a>
        </li>
        <li>
            <a href="#" data-chart="bytes">
                <?php esc_html_e( 'Bytes', 'redis-cache' ); ?>
            </a>
        </li>
        <li>
            <a href="#" data-chart="ratio">
                <?php esc_html_e( 'Ratio', 'redis-cache' ); ?>
            </a>
        </li>
        <li>
            <a href="#" data-chart="calls">
                <?php esc_html_e( 'Calls', 'redis-cache' ); ?>
            </a>
        </li>
    </ul>

    <div id="redis-stats-chart"></div>

</div>
