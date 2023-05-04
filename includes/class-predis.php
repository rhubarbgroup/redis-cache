<?php
/**
 * Predis client class.
 *
 * @package Rhubarb\RedisCache
 */

namespace Rhubarb\RedisCache;

use Exception;

defined( '\\ABSPATH' ) || exit;

class Predis {
    /**
     * Connection parameters.
     *
     * @var array
     */
    protected $parameters;

    /**
     * The Redis client.
     *
     * @var mixed
     */
    protected $redis;

    /**
     * Instantiate the Predis class.
     *
     * @param array $parameters Connection parameters.
     */
    public function __construct( $parameters ) {
        $this->parameters = $parameters;
    }

    /**
     * Connect to Redis.
     *
     * @return void
     */
    public function connect() {
        // Load bundled Predis library.
        if ( ! class_exists( '\Predis\Client' ) ) {
            require_once WP_REDIS_PLUGIN_PATH . '/dependencies/predis/predis/autoload.php';
        }

        $servers = false;
        $options = [];

        if ( defined( 'WP_REDIS_SHARDS' ) ) {
            $servers = WP_REDIS_SHARDS;
            $this->parameters['shards'] = $servers;
        } elseif ( defined( 'WP_REDIS_SENTINEL' ) ) {
            $servers = WP_REDIS_SERVERS;
            $this->parameters['servers'] = $servers;
            $options['replication'] = 'sentinel';
            $options['service'] = WP_REDIS_SENTINEL;
        } elseif ( defined( 'WP_REDIS_SERVERS' ) ) {
            $servers = WP_REDIS_SERVERS;
            $this->parameters['servers'] = $servers;
            $options['replication'] = 'predis';
        } elseif ( defined( 'WP_REDIS_CLUSTER' ) ) {
            $servers = $this->build_cluster_connection_array();
            $this->parameters['cluster'] = $servers;
            $options['cluster'] = 'redis';
        }

        if ( strcasecmp( 'unix', $this->parameters['scheme'] ) === 0 ) {
            unset( $this->parameters['host'], $this->parameters['port'] );
        }

        if ( isset( $this->parameters['read_timeout'] ) && $this->parameters['read_timeout'] ) {
            $this->parameters['read_write_timeout'] = $this->parameters['read_timeout'];
        }

        foreach ( [ 'WP_REDIS_SERVERS', 'WP_REDIS_SHARDS', 'WP_REDIS_CLUSTER' ] as $constant ) {
            if ( defined( $constant ) ) {
                if ( $this->parameters['database'] ) {
                    $options['parameters']['database'] = $this->parameters['database'];
                }

                if ( isset( $this->parameters['password'] ) ) {
                    if ( is_array( $this->parameters['password'] ) ) {
                        $options['parameters']['username'] = WP_REDIS_PASSWORD[0];
                        $options['parameters']['password'] = WP_REDIS_PASSWORD[1];
                    } else {
                        $options['parameters']['password'] = WP_REDIS_PASSWORD;
                    }
                }
            }
        }

        if ( isset( $this->parameters['password'] ) && defined( 'WP_REDIS_USERNAME' ) ) {
            $this->parameters['username'] = WP_REDIS_USERNAME;
        }

        if ( defined( 'WP_REDIS_SSL_CONTEXT' ) && ! empty( WP_REDIS_SSL_CONTEXT ) ) {
            $this->parameters['ssl'] = WP_REDIS_SSL_CONTEXT;
        }

        $this->redis = new \Predis\Client( $servers ?: $this->parameters, $options );
        $this->redis->connect();
    }

    /**
     * Invalidate all items in the cache.
     *
     * @return bool True on success, false on failure.
     */
    public function flush() {
        if ( is_null( $this->redis ) ) {
            $this->connect();
        }

        if ( defined( 'WP_REDIS_CLUSTER' ) ) {
            try {
                foreach ( $this->redis->_masters() as $master ) {
                    $this->redis->flushdb( $master );
                }
            } catch ( Exception $exception ) {
                return false;
            }
        } else {
            try {
                $this->redis->flushdb();
            } catch ( Exception $exception ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Builds a clean connection array out of redis clusters array.
     *
     * @return  array
     */
    protected function build_cluster_connection_array() {
        $cluster = array_values( WP_REDIS_CLUSTER );

        foreach ( $cluster as $key => $server ) {
            $connection_string = parse_url( $server );

            $cluster[ $key ] = sprintf(
                "%s:%s",
                $connection_string['host'],
                $connection_string['port']
            );
        }

        return $cluster;
    }
}
