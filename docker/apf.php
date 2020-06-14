<?php

$constants = [
    'WP_REDIS_HOST' => 'redis-master',
];

foreach ( $constants as $constant => $value ) {
    if ( ! defined( $constant ) ) {
        define( $constant, $value );
    }
}
