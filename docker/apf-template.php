<?php

$constants = [
    // constant-definition start
    'WP_REDIS_HOST' => 'redis-master',
    // constant-definition end
];

foreach ( $constants as $constant => $value ) {
    if ( ! defined( $constant ) ) {
        define( $constant, $value );
    }
}
