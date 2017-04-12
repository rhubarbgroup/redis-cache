<?php

global $wp_object_cache;

$info = $plugins = $dropins = array();
$dropin = $this->validate_object_cache_dropin();

$info[ 'Status' ] = $this->get_status();
$info[ 'Client' ] = $this->get_redis_client_name();

$info[ 'Drop-in' ] = $dropin ? 'Valid' : 'Invalid';

if ( $dropin ) {
	try {
		$cache = new WP_Object_Cache( false );
		$info[ 'Ping' ] = $cache->redis_instance()->ping();
	} catch ( Exception $exception ) {
		$info[ 'Connection Exception' ] = sprintf( '%s (%s)', $exception->getMessage(), get_class( $exception ) );
	}
}

$info[ 'Redis Extension' ] = class_exists( 'Redis' ) ? phpversion( 'redis' ) : 'Not Found';
$info[ 'Predis Client' ] = class_exists( 'Predis\Client' ) ? Predis\Client::VERSION : 'Not Found';

if ( defined( 'PHP_VERSION' ) ) {
    $info[ 'PHP Version' ] = PHP_VERSION;
}

if ( defined( 'HHVM_VERSION' ) ) {
    $info[ 'HHVM Version' ] = HHVM_VERSION;
}

$info[ 'Multisite' ] = is_multisite() ? 'Yes' : 'No';

if ( $dropin ) {
	$info[ 'Global Prefix' ] = json_encode( $wp_object_cache->global_prefix );
    $info[ 'Blog Prefix' ] = json_encode( $wp_object_cache->blog_prefix );
}

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
    'WP_REDIS_SHARDS',
    'WP_REDIS_SENTINEL',
    'WP_REDIS_MAXTTL',
    'WP_CACHE_KEY_SALT',
	'WP_REDIS_GLOBAL_GROUPS',
	'WP_REDIS_IGNORED_GROUPS',
);

foreach ( $constants as $constant ) {
    if ( defined( $constant ) ) {
        $info[ $constant ] = json_encode( constant( $constant ) );
    }
}

if ( defined( 'WP_REDIS_PASSWORD' ) ) {
    $info[ 'WP_REDIS_PASSWORD' ] = json_encode( empty( WP_REDIS_PASSWORD ) ? null : str_repeat( '*', strlen( WP_REDIS_PASSWORD ) ) );
}

if ( $dropin ) {
    $info[ 'Global Groups' ] = json_encode( $wp_object_cache->global_groups );
    $info[ 'Ignored Groups' ] = json_encode( $wp_object_cache->ignored_groups );
}

foreach ( $info as $name => $value ) {
    echo "{$name}: {$value}\r\n";
}

foreach ( get_dropins() as $file => $details ) {
	$dropins[ $file ] = sprintf(
		' - %s v%s by %s',
		$details[ 'Name' ],
		$details[ 'Version' ],
		$details[ 'Author' ]
	);
}

if ( ! empty( $dropins ) ) {
	echo "Dropins: \r\n", implode( "\r\n", $dropins ), "\r\n";
}

foreach ( get_plugins() as $file => $details ) {
	$plugins[] = sprintf(
		' - %s v%s by %s (%s%s)',
		$details[ 'Name' ],
		$details[ 'Version' ],
		$details[ 'Author' ],
		is_plugin_active( $file ) ? 'Active' : 'Inactive',
		is_multisite() ? ( is_plugin_active_for_network( $file ) ? ' network-wide' : '' ) : ''
	);
}

if ( ! empty( $plugins ) ) {
	echo "Plugins: \r\n", implode( "\r\n", $plugins ), "\r\n";
}
