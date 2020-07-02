<?php

global $wp_object_cache;

$info = $plugins = $dropins = array();
$dropin = $this->validate_object_cache_dropin();
$disabled = defined( 'WP_REDIS_DISABLED' ) && WP_REDIS_DISABLED;

$info[ 'Status' ] = $this->get_status();
$info[ 'Client' ] = $this->get_redis_client_name();
$info[ 'Drop-in' ] = $dropin ? 'Valid' : 'Invalid';
$info[ 'Disabled' ] = $disabled ? 'Yes' : 'No';

if ( $dropin && ! $disabled ) {
    $info[ 'Ping' ] = $wp_object_cache->diagnostics[ 'ping' ];

    try {
        $cache = new WP_Object_Cache( false );
    } catch ( Exception $exception ) {
        $info[ 'Connection Exception' ] = sprintf( '%s (%s)', $exception->getMessage(), get_class( $exception ) );
    }

    $info[ 'Errors' ] = json_encode(
        array_values( $wp_object_cache->errors ),
        JSON_PRETTY_PRINT
    );
}

$info['Redis Extension'] = class_exists( 'Redis' ) ? phpversion( 'redis' ) : 'Not Found';
$info['Predis Client'] = class_exists( 'Predis\Client' ) ? Predis\Client::VERSION : 'Not Found';

if ( defined( 'PHP_VERSION' ) ) {
    $info['PHP Version'] = PHP_VERSION;
}

if ( defined( 'HHVM_VERSION' ) ) {
    $info['HHVM Version'] = HHVM_VERSION;
}

$info['Redis Version'] = $this->get_redis_version() ?: 'Unknown';

$info['Multisite'] = is_multisite() ? 'Yes' : 'No';

if ( $dropin ) {
    $info[ 'Global Prefix' ] = json_encode( $wp_object_cache->global_prefix );
    $info[ 'Blog Prefix' ] = json_encode( $wp_object_cache->blog_prefix );
}

$constants = array(
    'WP_REDIS_DISABLED',
    'WP_REDIS_CLIENT',
    'WP_REDIS_SCHEME',
    'WP_REDIS_PATH',
    'WP_REDIS_HOST',
    'WP_REDIS_PORT',
    'WP_REDIS_DATABASE',
    'WP_REDIS_TIMEOUT',
    'WP_REDIS_READ_TIMEOUT',
    'WP_REDIS_RETRY_INTERVAL',
    'WP_REDIS_SERVERS',
    'WP_REDIS_CLUSTER',
    'WP_REDIS_SHARDS',
    'WP_REDIS_SENTINEL',
    'WP_REDIS_IGBINARY',
    'WP_REDIS_MAXTTL',
    'WP_REDIS_PREFIX',
    'WP_CACHE_KEY_SALT',
    'WP_REDIS_GLOBAL_GROUPS',
    'WP_REDIS_IGNORED_GROUPS',
    'WP_REDIS_UNFLUSHABLE_GROUPS',
);

foreach ( $constants as $constant ) {
    if ( defined( $constant ) ) {
        $info[ $constant ] = json_encode( constant( $constant ) );
    }
}

if ( defined( 'WP_REDIS_PASSWORD' ) ) {
    $password = WP_REDIS_PASSWORD;

    if ( is_array( $password ) ) {
        if ( isset( $password[1] ) && ! is_null( $password[1] ) && $password[1] !== '' ) {
            $password[1] = str_repeat( '•', 8 );
        }

        $info[ 'WP_REDIS_PASSWORD' ] = json_encode( $password );
    } elseif ( ! is_null( $password ) && $password !== '' ) {
        $info[ 'WP_REDIS_PASSWORD' ] = str_repeat( '•', 8 );
    }
}

if ( $dropin ) {
    $info[ 'Global Groups' ] = json_encode(
        array_values( $wp_object_cache->global_groups ),
        JSON_PRETTY_PRINT
    );

    $info[ 'Ignored Groups' ] = json_encode(
        array_values( $wp_object_cache->ignored_groups ),
        JSON_PRETTY_PRINT
    );

    $info[ 'Unflushable Groups' ] = json_encode(
        array_values( $wp_object_cache->unflushable_groups ),
        JSON_PRETTY_PRINT
    );
}

foreach ( $info as $name => $value ) {
    echo "{$name}: {$value}\r\n";
}

foreach ( get_dropins() as $file => $details ) {
    $dropins[ $file ] = sprintf(
        ' - %s v%s by %s',
        $details['Name'],
        $details['Version'],
        $details['Author']
    );
}

if ( ! empty( $dropins ) ) {
    echo "Drop-ins: \r\n", implode( "\r\n", $dropins ), "\r\n";
}
