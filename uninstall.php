<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wp_filesystem;

ob_start();

if (
	file_exists( WP_CONTENT_DIR . '/object-cache.php' ) &&
    ( $object_cache = file_get_contents( WP_CONTENT_DIR . '/object-cache.php' ) ) &&
    strpos( $object_cache, 'http://wordpress.org/plugins/redis-cache/' ) !== false
) {

	wp_cache_flush();

	if ( WP_Filesystem( request_filesystem_credentials( '' ) ) ) {
		$wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );
	}

}

ob_end_clean();
