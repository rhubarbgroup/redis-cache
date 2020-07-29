<?php

$constants = [
    'WP_ENVIRONMENT_TYPE' => 'development',
    'WP_DEBUG' => true,
    'SCRIPT_DEBUG' => true,
    'CONCATENATE_SCRIPTS' => false,
    // constant-definition start
    'WP_REDIS_HOST' => 'redis-master',
    // constant-definition end
];

foreach ( $constants as $constant => $value ) {
    if ( ! defined( $constant ) ) {
        define( $constant, $value );
    }
}
