<?php
/**
 * Tenant registry and shared-database detection.
 *
 * @package Rhubarb\RedisCache
 */

namespace Rhubarb\RedisCache;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Detects whether the Redis database is shared between multiple installations.
 *
 * Two independent signals are combined:
 *
 * 1. A self-healing tenant registry. Every installation announces itself in a
 *    single Redis hash stored at {@see Tenants::REGISTRY_KEY}. The key lives
 *    outside the `WP_REDIS_PREFIX` namespace on purpose, so a *selective* flush
 *    never touches it. A full `FLUSHDB` does wipe it, but the heartbeat is
 *    re-asserted on the next request, so the registry is eventually consistent.
 *
 * 2. A keyspace probe. A single `SCAN` batch is sampled and the leading key
 *    namespace (everything before the first colon) is compared against the
 *    configured prefix. This catches tenants that don't run this plugin and
 *    therefore never write to the registry.
 */
class Tenants {

    /**
     * Registry hash key.
     *
     * The `{...}` hash tag keeps every field in a single slot under Redis
     * Cluster. It is intentionally stored outside the `WP_REDIS_PREFIX`
     * namespace so selective flushes leave it untouched.
     *
     * @var string
     */
    const REGISTRY_KEY = '{rediscache}:tenants';

    /**
     * Throttle transient name for the heartbeat.
     *
     * Stored through the object cache, so any flush clears it and the next
     * request re-announces this installation.
     *
     * @var string
     */
    const HEARTBEAT_TRANSIENT = 'redis_cache_tenant_heartbeat';

    /**
     * Minimum number of seconds between heartbeats.
     *
     * @var int
     */
    const HEARTBEAT_INTERVAL = 300;

    /**
     * Registry entries older than this are considered stale and pruned.
     *
     * @var int
     */
    const STALE_AFTER = DAY_IN_SECONDS;

    /**
     * Number of keys to sample per keyspace probe.
     *
     * @var int
     */
    const PROBE_SAMPLE = 1000;

    /**
     * Registers the heartbeat.
     *
     * @return void
     */
    public static function init() {
        if ( ! self::is_enabled() ) {
            return;
        }

        add_action( 'shutdown', [ self::class, 'ping' ] );
    }

    /**
     * Whether the tenant registry is enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        return ! defined( 'WP_REDIS_DISABLE_TENANT_REGISTRY' )
            || ! WP_REDIS_DISABLE_TENANT_REGISTRY;
    }

    /**
     * Whether the registry can talk to Redis.
     *
     * @return bool
     */
    public static function is_active() {
        global $wp_object_cache;

        return self::is_enabled()
            && Plugin::instance()->get_redis_status()
            && is_object( $wp_object_cache )
            && method_exists( $wp_object_cache, 'redis_instance' );
    }

    /**
     * Announces this installation in the registry.
     *
     * Throttled by a transient so at most one write happens per heartbeat
     * interval. Because the transient lives in the (flushable) object cache,
     * any flush re-arms the heartbeat and the entry is rewritten promptly.
     *
     * @param bool $force Bypass the throttle and write immediately.
     * @return void
     */
    public static function ping( $force = false ) {
        if ( ! self::is_active() ) {
            return;
        }

        if ( ! $force && get_transient( self::HEARTBEAT_TRANSIENT ) ) {
            return;
        }

        $identity = self::identity();

        $payload = wp_json_encode(
            [
                'url'       => $identity['url'],
                'prefix'    => $identity['salt'] !== '',
                'multisite' => $identity['multisite'],
                'version'   => $identity['version'],
                'seen'      => time(),
            ]
        );

        try {
            self::redis()->hset( self::REGISTRY_KEY, self::fingerprint( $identity ), $payload );
        } catch ( Exception $exception ) {
            error_log( $exception ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

            return;
        }

        set_transient( self::HEARTBEAT_TRANSIENT, time(), self::HEARTBEAT_INTERVAL );
    }

    /**
     * Reads all known tenants, pruning stale entries on the way.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function tenants() {
        if ( ! self::is_active() ) {
            return [];
        }

        try {
            $raw = self::redis()->hgetall( self::REGISTRY_KEY );
        } catch ( Exception $exception ) {
            error_log( $exception ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

            return [];
        }

        $now = time();
        $tenants = [];
        $stale = [];

        foreach ( (array) $raw as $fingerprint => $json ) {
            $data = json_decode( $json, true );

            if ( ! is_array( $data ) || empty( $data['seen'] ) || ( $now - (int) $data['seen'] ) > self::STALE_AFTER ) {
                $stale[] = $fingerprint;

                continue;
            }

            $data['id'] = $fingerprint;
            $tenants[ $fingerprint ] = $data;
        }

        if ( $stale ) {
            try {
                foreach ( $stale as $fingerprint ) {
                    self::redis()->hdel( self::REGISTRY_KEY, $fingerprint );
                }
            } catch ( Exception $exception ) {
                error_log( $exception ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }

        return $tenants;
    }

    /**
     * Samples a single `SCAN` batch and groups the keys by namespace.
     *
     * The probe is skipped under Redis Cluster, where `SCAN` is per-node and
     * would need to fan out across masters; the registry covers that case.
     *
     * @param int $sample Number of keys to request from Redis.
     * @return array<string, mixed>
     */
    public static function probe( $sample = self::PROBE_SAMPLE ) {
        $result = [
            'supported'  => false,
            'conclusive' => false,
            'sample'     => 0,
            'namespaces' => [],
            'foreign'    => [],
        ];

        if ( defined( 'WP_REDIS_CLUSTER' ) || ! self::is_active() ) {
            return $result;
        }

        $keys = self::scan_sample( $sample );
        $salt = self::salt();
        $classified = self::classify_keys( $keys, $salt );

        $result['supported'] = true;
        $result['conclusive'] = '' !== $salt;
        $result['sample'] = count( $keys );
        $result['namespaces'] = $classified['namespaces'];
        $result['foreign'] = $classified['foreign'];

        return $result;
    }

    /**
     * Groups sampled keys by namespace and flags the foreign ones.
     *
     * The namespace is everything before the first colon of a key. Our own
     * out-of-namespace bookkeeping key is ignored. Foreign namespaces can only
     * be identified when a prefix is configured; with an empty prefix every key
     * looks like it could be ours.
     *
     * @param string[] $keys Sampled keys.
     * @param string   $salt Configured key prefix.
     * @return array{namespaces: array<string, int>, foreign: string[]}
     */
    public static function classify_keys( array $keys, $salt ) {
        $registry_token = strstr( self::REGISTRY_KEY, ':', true );

        $namespaces = [];

        foreach ( $keys as $key ) {
            $token = strstr( (string) $key, ':', true );
            $token = false === $token ? (string) $key : $token;

            if ( $token === $registry_token ) {
                continue;
            }

            $namespaces[ $token ] = ( $namespaces[ $token ] ?? 0 ) + 1;
        }

        $foreign = [];

        if ( '' !== $salt ) {
            foreach ( array_keys( $namespaces ) as $token ) {
                if ( strpos( $token, $salt ) !== 0 ) {
                    $foreign[] = $token;
                }
            }
        }

        return [
            'namespaces' => $namespaces,
            'foreign'    => $foreign,
        ];
    }

    /**
     * Combined shared-database report consumed by Site Health and WP-CLI.
     *
     * @return array<string, mixed>
     */
    public static function report() {
        // Make sure this installation is represented before reading the registry.
        self::ping( true );

        $salt = self::salt();
        $selective = defined( 'WP_REDIS_SELECTIVE_FLUSH' ) && WP_REDIS_SELECTIVE_FLUSH;

        $tenants = self::tenants();
        $probe = self::probe();

        $shared = null;

        if ( count( $tenants ) > 1 ) {
            $shared = true;
        } elseif ( ! empty( $probe['foreign'] ) ) {
            $shared = true;
        } elseif ( $probe['supported'] && $probe['conclusive'] ) {
            $shared = false;
        }

        return [
            'shared'           => $shared,
            'tenants'          => array_values( $tenants ),
            'foreign'          => $probe['foreign'],
            'probe'            => $probe,
            'salt'             => $salt,
            'salt_set'         => '' !== $salt,
            'selective_flush'  => $selective,
        ];
    }

    /**
     * Requests a single `SCAN` batch across the supported clients.
     *
     * Predis returns a `[cursor, keys]` tuple; PhpRedis, Relay and Credis share
     * the by-reference iterator API.
     *
     * @param int $count Hinted number of keys to return.
     * @return string[]
     */
    protected static function scan_sample( $count ) {
        $redis = self::redis();

        try {
            if ( $redis instanceof \Predis\Client ) {
                $response = $redis->scan( 0, [ 'COUNT' => $count ] );

                return isset( $response[1] ) ? (array) $response[1] : [];
            }

            $iterator = null;
            $keys = $redis->scan( $iterator, null, $count );

            return is_array( $keys ) ? $keys : [];
        } catch ( Exception $exception ) {
            error_log( $exception ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

            return [];
        }
    }

    /**
     * Identity of the current installation.
     *
     * The unit is the installation (one drop-in / one database), not the blog:
     * every blog in a multisite network shares the same prefix and flushes the
     * same database, so they register as a single tenant.
     *
     * @return array<string, mixed>
     */
    protected static function identity() {
        return [
            'url'       => is_multisite() && function_exists( 'network_home_url' ) ? network_home_url() : home_url(),
            'salt'      => self::salt(),
            'multisite' => is_multisite(),
            'version'   => defined( 'WP_REDIS_VERSION' ) ? WP_REDIS_VERSION : null,
        ];
    }

    /**
     * Stable fingerprint for an identity.
     *
     * @param array<string, mixed> $identity Identity array.
     * @return string
     */
    protected static function fingerprint( array $identity ) {
        return substr( md5( $identity['url'] . '|' . $identity['salt'] ), 0, 12 );
    }

    /**
     * The configured key prefix.
     *
     * @return string
     */
    protected static function salt() {
        return defined( 'WP_REDIS_PREFIX' ) ? trim( (string) WP_REDIS_PREFIX ) : '';
    }

    /**
     * The raw Redis client.
     *
     * @return mixed
     */
    protected static function redis() {
        global $wp_object_cache;

        return $wp_object_cache->redis_instance();
    }

}
