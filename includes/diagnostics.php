<?php

$info = array();

if ( defined( 'PHP_VERSION' ) ) {
    $info[ 'PHP Version' ] = PHP_VERSION;
}

if ( defined( 'HHVM_VERSION' ) ) {
    $info[ 'HHVM Version' ] = HHVM_VERSION;
}

$info[ __( 'Multisite', 'redis-cache' ) ] = is_multisite() ? __( 'Yes', 'redis-cache' ) : __( 'No', 'redis-cache' );

$info[ __( 'Redis', 'redis-cache' ) ] = class_exists( 'Redis' ) ? phpversion( 'redis' ) : __( 'Not Found', 'redis-cache' );
$info[ __( 'Predis', 'redis-cache' ) ] = class_exists( 'Predis\Client' ) ? Predis\Client::VERSION : __( 'Not Found', 'redis-cache' );

$info[ __( 'Status', 'redis-cache' ) ] = $this->get_status();
$info[ __( 'Client', 'redis-cache' ) ] = $this->get_redis_client_name();

$constants = array(
    'WP_REDIS_DISABLED',
    'WP_REDIS_CLIENT',
    'WP_REDIS_SCHEME',
    'WP_REDIS_HOST',
    'WP_REDIS_PORT',
    'WP_REDIS_PATH',
    'WP_REDIS_DATABASE',
    'WP_REDIS_SERVERS',
    'WP_REDIS_CLUSTER',
    'WP_REDIS_MAXTTL',
    'WP_REDIS_GLOBAL_GROUPS',
    'WP_REDIS_IGNORED_GROUPS',
    'WP_CACHE_KEY_SALT',
);

foreach ( $constants as $constant ) {
    if ( defined( $constant ) ) {
        $info[$constant] = json_encode( constant( $constant ) );
    }
}

if ( defined( 'WP_REDIS_PASSWORD' ) ) {
    $info[ 'WP_REDIS_PASSWORD' ] = json_encode( empty( WP_REDIS_PASSWORD ) ? null : str_repeat( '*', strlen( WP_REDIS_PASSWORD ) ) );
}

if ( $this->validate_object_cache_dropin() ) {
    $info[ __( 'Drop-in', 'redis-cache' ) ] = __( 'Valid', 'redis-cache' );
    $info[ __( 'Global Prefix', 'redis-cache' ) ] = json_encode( $GLOBALS[ 'wp_object_cache' ]->global_prefix );
    $info[ __( 'Blog Prefix', 'redis-cache' ) ] = json_encode( $GLOBALS[ 'wp_object_cache' ]->blog_prefix );
    $info[ __( 'Global Groups', 'redis-cache' ) ] = json_encode( $GLOBALS[ 'wp_object_cache' ]->global_groups );
    $info[ __( 'Ignored Groups', 'redis-cache' ) ] = json_encode( $GLOBALS[ 'wp_object_cache' ]->ignored_groups );
} else {
    $info[ __( 'Drop-in', 'redis-cache' ) ] = __( 'Invalid', 'redis-cache' );
}

foreach ($info as $name => $value) {
    echo "{$name}: {$value}\r\n";
}
