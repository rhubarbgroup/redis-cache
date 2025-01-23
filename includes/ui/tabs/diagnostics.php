<?php
/**
 * Diagnostics tab template
 *
 * @package Rhubarb\RedisCache
 */

defined( '\\ABSPATH' ) || exit;

?>

<div class="card">
    <pre id="redis-cache-diagnostics"><?php require __DIR__ . '/../../diagnostics.php'; ?></pre>
</div>

<p id="redis-cache-copy-button">
    <span class="copy-button-wrapper">
        <button type="button" class="button copy-button" data-clipboard-target="#redis-cache-diagnostics">
            <?php esc_html_e( 'Copy diagnostics to clipboard', 'redis-cache' ); ?>
        </button>
        <span class="success hidden" aria-hidden="true"><?php esc_html_e( 'Copied!', 'redis-cache' ); ?></span>
    </span>
</p>
