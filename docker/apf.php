<?php

$constants = [
    'WP_REDIS_HOST' => 'redis_master',
];

foreach ( $constants as $constant => $value ) {
    if ( ! defined( $constant ) ) {
        define( $constant, $value );
    }
}
