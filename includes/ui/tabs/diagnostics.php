<?php
/**
 * Diagnostics tab template
 *
 * @package Rhubarb\RedisCache
 */

if ( defined( '\\ABSPATH' ) ) {
    exit;
}

?>

<p>
    <textarea class="large-text readonly" rows="20" readonly><?php require dirname( __DIR__ ) . '/diagnostics.php'; ?></textarea>
</p>
