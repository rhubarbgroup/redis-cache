<?php

$constants = [
    // constant-definition start
    'WP_REDIS_SERVERS' => ['tcp://192.168.16.2:6379?alias=master','tcp://192.168.16.7:6379?alias=slave-01','tcp://192.168.16.5:6379?alias=slave-02','tcp://192.168.16.6:6379?alias=slave-03',],
    // constant-definition end
];

foreach ( $constants as $constant => $value ) {
    if ( ! defined( $constant ) ) {
        define( $constant, $value );
    }
}
