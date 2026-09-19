<?php
/**
 * Predis client class.
 *
 * @package Rhubarb\RedisCache
 */

namespace Rhubarb\RedisCache;

use Exception;

defined( 'ABSPATH' ) || exit;

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

        if ( array_key_exists( 'password', $parameters ) ) {
            $password = $parameters['password'];

            $is_empty = is_array( $password )
                ? $password === []
                : ! is_scalar( $password ) || (string) $password === '';

            if ( $is_empty ) {
                unset( $parameters['password'] );
            }
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
            if ( $servers ) {
                $options['parameters']['ssl'] = WP_REDIS_SSL_CONTEXT;
            } else {
                $parameters['ssl'] = WP_REDIS_SSL_CONTEXT;
            }
        }

        $this->redis = new \Predis\Client( $servers ?: $parameters, $options );
        $this->redis->connect();
    }

    /**
     * Flushes the entire Redis database using the `WP_REDIS_FLUSH_TIMEOUT`.
     *
     * When a key prefix is configured and `WP_REDIS_SELECTIVE_FLUSH` is enabled,
     * only the keys carrying that prefix are deleted, just like
     * `WP_Object_Cache::flush()` does.
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

        $prefix = $this->flush_prefix();

        if ( defined( 'WP_REDIS_CLUSTER' ) ) {
            try {
                foreach ( $this->redis->getIterator() as $master ) {
                    if ( is_null( $prefix ) ) {
                        $master->flushdb();
                    } else {
                        $this->delete_by_prefix( $master, $prefix );
                    }
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
            if ( is_null( $prefix ) ) {
                $this->redis->flushdb();
            } else {
                $this->delete_by_prefix( $this->redis, $prefix );
            }
        } catch ( Exception $exception ) {
            if ( $throw_exception ) {
                throw $exception;
            }

            return false;
        }

        return true;
    }

    /**
     * Returns the key prefix to flush selectively, or `null` to flush everything.
     *
     * The prefix is resolved the same way `wp_cache_init()` resolves it, because
     * the drop-in is not necessarily loaded when this class is used.
     *
     * @return string|null
     */
    protected function flush_prefix() {
        $selective = defined( 'WP_REDIS_SELECTIVE_FLUSH' )
            ? WP_REDIS_SELECTIVE_FLUSH
            : (bool) getenv( 'WP_REDIS_SELECTIVE_FLUSH' );

        if ( ! $selective ) {
            return null;
        }

        if ( defined( 'WP_REDIS_PREFIX' ) ) {
            $prefix = WP_REDIS_PREFIX;
        } elseif ( getenv( 'WP_REDIS_PREFIX' ) ) {
            $prefix = getenv( 'WP_REDIS_PREFIX' );
        } elseif ( defined( 'WP_CACHE_KEY_SALT' ) ) {
            $prefix = WP_CACHE_KEY_SALT;
        } elseif ( isset( $_SERVER['cw_allowed_ip'] ) ) {
            $prefix = getenv( 'HTTP_X_APP_USER' );
        } else {
            return null;
        }

        $prefix = trim( (string) $prefix );

        return $prefix === '' ? null : $prefix;
    }

    /**
     * Deletes every key carrying the given prefix, using `SCAN`.
     *
     * Unlike `WP_Object_Cache::flush()` this does not use a Lua script: this class
     * holds no `redis_version` to decide whether the script needs a
     * `redis.replicate_commands()` call, and `EVAL` is unavailable on some hosts.
     *
     * @param mixed  $client The connection to delete the keys on.
     * @param string $prefix The key prefix to delete.
     * @return void
     */
    protected function delete_by_prefix( $client, $prefix ) {
        $pattern = $this->escape_pattern( $prefix ) . '*';
        $cursor = 0;

        do {
            $response = $client->scan( $cursor, [ 'MATCH' => $pattern, 'COUNT' => 1000 ] );

            $cursor = (int) $response[0];
            $keys = $response[1];

            if ( ! empty( $keys ) ) {
                $client->del( $keys );
            }
        } while ( $cursor !== 0 );
    }

    /**
     * Escapes a string for literal use inside a `SCAN` match pattern.
     *
     * @param string $string The string to escape.
     * @return string
     */
    protected function escape_pattern( $string ) {
        return addcslashes( $string, '\\*?[]' );
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
            // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
            $components = parse_url( $server );

            if ( ! empty( $components['scheme'] ) ) {
                $scheme = $components['scheme'];
            } elseif ( defined( 'WP_REDIS_SCHEME' ) ) {
                $scheme = WP_REDIS_SCHEME;
            } else {
                $scheme = null;
            }

            if ( isset( $scheme ) ) {
                $cluster[ $key ] = sprintf(
                    '%s://%s:%d',
                    $scheme,
                    $components['host'],
                    $components['port']
                );
            } else {
                $cluster[ $key ] = sprintf(
                    '%s:%d',
                    $components['host'],
                    $components['port']
                );
            }
        }

        return $cluster;
    }
}
