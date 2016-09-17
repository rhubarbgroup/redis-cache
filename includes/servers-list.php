<?php

class Servers_List extends WP_List_Table {

    public function __construct() {

        parent::__construct( array(
            'singular' => __( 'Server', 'redis-cache' ),
            'plural' => __( 'Servers', 'redis-cache' ),
            'ajax' => false
        ) );

    }

    public function get_columns() {

        return array(
            'alias' => 'Alias',
            'scheme' => 'Protocol',
            'host' => 'Host',
            'port' => 'Port',
            'path' => 'Path',
            'database' => 'Database',
            'password' => 'Password',
        );

    }

    public function get_hidden_columns() {

        $hidden = array( 'host', 'port', 'path' );

        array_walk_recursive( $this->items, function ( $value, $key ) use ( &$hidden ) {
            if ( $key == 'scheme' ) {
                if ( strcasecmp( 'unix', $value ) === 0 ) {
                    $hidden = array_diff( $hidden, array( 'path' ) );
                } else {
                    $hidden = array_diff( $hidden, array( 'host', 'port' ) );
                }
            }
        } );

        return $hidden;

    }

    public function prepare_items() {

        if ( ! class_exists( 'Predis\Client' ) ) {
            require_once dirname(__FILE__) . '/predis.php';
            Predis\Autoloader::register();
        }

        $this->items = $this->get_servers();

        $this->_column_headers = array($this->get_columns(), $this->get_hidden_columns(), array());

    }

    public function column_default( $item, $column_name ) {

        switch ( $column_name ) {

            case 'scheme':
                return isset( $item[ 'scheme' ] ) ? strtoupper( $item[ 'scheme' ] ) : 'TCP';

            case 'host':
                return isset( $item[ 'host' ] ) ? $item[ 'host' ] : '127.0.0.1';

            case 'port':
                return isset( $item[ 'port' ] ) ? $item[ 'port' ] : '6379';

            case 'database':
                return isset( $item[ 'database' ] ) ? $item[ 'database' ] : '0';

            case 'password':
                return isset( $item[ 'password' ] ) ? __( 'Yes', 'redis-cache' ) : __( 'No', 'redis-cache' );

            default:
                return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
        }

    }

    protected function display_tablenav($which)
    {
        // hide table navigation
    }

    protected function get_servers() {

        $server = array(
            'alias' => 'Master',
            'scheme' => 'tcp',
        );

        foreach ( [ 'scheme', 'host', 'port', 'path', 'password', 'database' ] as $setting ) {
            $constant = sprintf( 'WP_REDIS_%s', strtoupper( $setting ) );

            if ( defined( $constant ) ) {
    			$server[ $setting ] = constant( $constant );
    		}
        }

        if ( defined( 'WP_REDIS_CLUSTER' ) ) {
            $servers = WP_REDIS_CLUSTER;
        }

        if ( defined( 'WP_REDIS_SERVERS' ) ) {
            $servers = WP_REDIS_SERVERS;
        }

        if ( ! isset( $servers ) ) {
            $servers = array( $server );
        }

        return array_map(function($parameters) {
            return is_string($parameters) ? Predis\Connection\Parameters::parse($parameters) : $parameters;
        }, $servers);

    }

}
