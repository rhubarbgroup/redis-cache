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
     * The Redis client.
     *
     * @var mixed
     */
    protected $redis;

    /**
     * Connect to Redis.
     *
     * @param int|null $read_timeout The read timeout in seconds.
     * @return void
     */
    public function connect( $read_timeout = null ) {
        if ( ! function_exists( 'stream_socket_client' ) ) {
            return;
        }

        // Load bundled Predis library.
        if ( ! class_exists( '\Predis\Client' ) ) {
            require_once WP_REDIS_PLUGIN_PATH . '/dependencies/predis/predis/autoload.php';
        }

        $servers = false;
        $options = [];

        $parameters = [
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
            'timeout' => 1,
            'read_timeout' => $read_timeout ?? 1,
        ];

        $settings = [
            'scheme',
            'host',
            'port',
            'path',
            'password',
            'database',
            'timeout',
            'read_timeout',
        ];

        foreach ( $settings as $setting ) {
            $constant = sprintf( 'WP_REDIS_%s', strtoupper( $setting ) );

            if ( defined( $constant ) ) {
                $parameters[ $setting ] = constant( $constant );
            }
        }

        if ( isset( $parameters['password'] ) && $parameters['password'] === '' ) {
            unset( $parameters['password'] );
        }

        if ( defined( 'WP_REDIS_SHARDS' ) ) {
            $servers = WP_REDIS_SHARDS;
            $parameters['shards'] = $servers;
        } elseif ( defined( 'WP_REDIS_SENTINEL' ) ) {
            $servers = WP_REDIS_SERVERS;
            $parameters['servers'] = $servers;
            $options['replication'] = 'sentinel';
            $options['service'] = WP_REDIS_SENTINEL;
        } elseif ( defined( 'WP_REDIS_SERVERS' ) ) {
            $servers = WP_REDIS_SERVERS;
            $parameters['servers'] = $servers;
            $options['replication'] = 'predis';
        } elseif ( defined( 'WP_REDIS_CLUSTER' ) ) {
            $servers = $this->build_cluster_connection_array();
            $parameters['cluster'] = $servers;
            $options['cluster'] = 'redis';
        }

        if ( strcasecmp( 'unix', $parameters['scheme'] ) === 0 ) {
            unset( $parameters['host'], $parameters['port'] );
        }

        if ( isset( $parameters['read_timeout'] ) && $parameters['read_timeout'] ) {
            $parameters['read_write_timeout'] = $parameters['read_timeout'];
        }

        foreach ( [ 'WP_REDIS_SERVERS', 'WP_REDIS_SHARDS', 'WP_REDIS_CLUSTER' ] as $constant ) {
            if ( defined( $constant ) ) {
                if ( $parameters['database'] ) {
                    $options['parameters']['database'] = $parameters['database'];
                }

                if ( isset( $parameters['password'] ) ) {
                    if ( is_array( $parameters['password'] ) ) {
                        $options['parameters']['username'] = WP_REDIS_PASSWORD[0];
                        $options['parameters']['password'] = WP_REDIS_PASSWORD[1];
                    } else {
                        $options['parameters']['password'] = WP_REDIS_PASSWORD;
                    }
                }
            }
        }

        if ( isset( $parameters['password'] ) ) {
            if ( is_array( $parameters['password'] ) ) {
                $parameters['username'] = array_shift( $parameters['password'] );
                $parameters['password'] = implode( '', $parameters['password'] );
            }

            if ( defined( 'WP_REDIS_USERNAME' ) ) {
                $parameters['username'] = WP_REDIS_USERNAME;
            }
        }

        if ( defined( 'WP_REDIS_SSL_CONTEXT' ) && ! empty( WP_REDIS_SSL_CONTEXT ) ) {
            $parameters['ssl'] = WP_REDIS_SSL_CONTEXT;
        }

        $this->redis = new \Predis\Client( $servers ?: $parameters, $options );
        $this->redis->connect();
    }

    /**
     * Flushes the entire Redis database using the `WP_REDIS_FLUSH_TIMEOUT`.
     *
     * @param bool $throw_exception Whether to throw exception on error.
     * @return bool
     */
    public function flush( $throw_exception = false ) {
        if ( is_null( $this->redis ) ) {
            $flush_timeout = defined( 'WP_REDIS_FLUSH_TIMEOUT' )
                ? intval( WP_REDIS_FLUSH_TIMEOUT )
                : 5;
            try {
                $this->connect( $flush_timeout );
            } catch ( Exception $exception ) {
                if ( $throw_exception ) {
                    throw $exception;
                }

                return false;
            }

            if ( is_null( $this->redis ) ) {
                return false;
            }
        }

        if ( defined( 'WP_REDIS_CLUSTER' ) ) {
            try {
                foreach ( $this->redis->getIterator() as $master ) {
                    $master->flushdb();
                }
            } catch ( Exception $exception ) {
                if ( $throw_exception ) {
                    throw $exception;
                }

                return false;
            }

            return true;
        }

        try {
            $this->redis->flushdb();
        } catch ( Exception $exception ) {
            if ( $throw_exception ) {
                throw $exception;
            }

            return false;
        }

        return true;
    }

    /**
     * Flushes the entire Redis database using the `WP_REDIS_FLUSH_TIMEOUT`
     * and will throw an exception if anything goes wrong.
     *
     * @return bool
     */
    public function flushOrFail() {
        return $this->flush( true );
    }

    /**
     * Builds a clean connection array out of redis clusters array.
     *
     * @return array
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
