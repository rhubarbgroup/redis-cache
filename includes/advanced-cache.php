<?php
/*
Plugin Name: Redis Page Cache Drop-in
Plugin URI: http://wordpress.org/plugins/redis-cache/
Description: A persistent page and object cache backend powered by Redis. Supports Predis, PhpRedis, HHVM, replication, clustering and WP-CLI.
Version: 2.0.0-beta1
Author: Till Krüss
Author URI: https://till.im/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Based on Automattic's Batcache:
https://github.com/Automattic/batcache
*/

class Redis_Page_Cache {

    /**
     * Only cache a page after it is accessed this many times.
     *
     * @var integer
     */
    public $times = 2;

	/**
	 * Only cache a page if it is accessed `$times` in this many seconds.
	 * Set to zero to ignore this and use cache immediately.
	 *
	 * @var integer
	 */
	public $seconds = 120;

	/**
	 * Expire cache items aged this many seconds.
	 * Set to zero to disable cache.
	 *
	 * @var integer
	 */
	public $max_age = 300;

	/**
	 * Name of object cache group used for page cache.
	 *
	 * @var string
	 */
	public $group = 'redis-cache';

	/**
	 * If you conditionally serve different content, put the variable values here
	 * using the `add_variant()` method.
	 *
	 * @var array
	 */
	public $unique = array();

	/**
	 * Array of functions for `create_function()`.
	 *
	 * @var array
	 */
	public $vary = array();

    /**
     * Add headers here as `name => value` or `name => array( values )`.
     * These will be sent with every response from the cache.
     *
     * @var array
     */
    public $headers = array();

    /**
     * These headers will never be cached. (Use lower case only!)
     *
     * @var array
     */
    public $uncached_headers = array(
        'transfer-encoding'
    );

	/**
	 * Set to `false` to disable `Last-Modified` and `Cache-Control` headers.
	 *
	 * @var boolean
	 */
    public $cache_control = true;

	/**
	 * Set to `true` to disable the output buffer.
	 *
	 * @var boolean
	 */
	public $cancel = false;

	/**
	 * Is it ok to return stale cached response when updating the cache?
	 *
	 * @var boolean
	 */
	public $use_stale = true;

	/**
	 * Names of cookies - if they exist and the cache would normally be bypassed, don't bypass it.
	 *
	 * @var array
	 */
	public $noskip_cookies = array(
        'wordpress_test_cookie'
    );

    public $keys = array();
    public $url_key;
    public $url_version;
    public $key;
    public $req_key;
    public $status_header;
    public $status_code;

    public function __construct() {

        if ( defined( 'WP_REDIS_TIMES' ) && WP_REDIS_TIMES ) {
            $this->times = WP_REDIS_TIMES;
        }

        if ( defined( 'WP_REDIS_SECONDS' ) && WP_REDIS_SECONDS ) {
            $this->seconds = WP_REDIS_SECONDS;
        }

        if ( defined( 'WP_REDIS_MAXAGE' ) && WP_REDIS_MAXAGE ) {
            $this->max_age = WP_REDIS_MAXAGE;
        }

        if ( defined( 'WP_REDIS_GROUP' ) && WP_REDIS_GROUP ) {
            $this->group = WP_REDIS_GROUP;
        }

        if ( defined( 'WP_REDIS_UNIQUE' ) && WP_REDIS_UNIQUE ) {
            $this->unique = WP_REDIS_UNIQUE;
        }

        if ( defined( 'WP_REDIS_HEADERS' ) && WP_REDIS_HEADERS ) {
            $this->headers = WP_REDIS_HEADERS;
        }

        if ( defined( 'WP_REDIS_UNCACHED_HEADERS' ) && WP_REDIS_UNCACHED_HEADERS ) {
            $this->uncached_headers = WP_REDIS_UNCACHED_HEADERS;
        }

        if ( defined( 'WP_REDIS_CACHE_CONTROL' ) && WP_REDIS_CACHE_CONTROL ) {
            $this->cache_control = WP_REDIS_CACHE_CONTROL;
        }

        if ( defined( 'WP_REDIS_USE_STALE' ) && WP_REDIS_USE_STALE ) {
            $this->use_stale = WP_REDIS_USE_STALE;
        }

        if ( defined( 'WP_REDIS_NOSKIP_COOKIES' ) && WP_REDIS_NOSKIP_COOKIES ) {
            $this->noskip_cookies = WP_REDIS_NOSKIP_COOKIES;
        }

    }

    public function setup_request() {
        if ( isset( $_SERVER[ 'HTTP_HOST' ] ) ) {
            $this->keys[ 'host' ] = $_SERVER[ 'HTTP_HOST' ];
        }

        if ( isset( $_SERVER[ 'REQUEST_METHOD' ] ) ) {
            $this->keys[ 'method' ] = $_SERVER[ 'REQUEST_METHOD' ];
        }

        if ( isset( $_SERVER[ 'QUERY_STRING' ] ) ) {
            parse_str( $_SERVER[ 'QUERY_STRING' ], $query_string );
            $this->keys[ 'query' ] = $query_string;
        }

        if ( isset( $_SERVER[ 'REQUEST_URI' ] ) ) {
            if ( ( $pos = strpos( $_SERVER[ 'REQUEST_URI' ], '?' ) ) !== false ) {
                $this->keys[ 'path' ] = substr( $_SERVER[ 'REQUEST_URI' ], 0, $pos );
            } else {
                $this->keys[ 'path' ] = $_SERVER[ 'REQUEST_URI' ];
            }
        }

        $this->keys[ 'ssl' ] = $this->is_secure();

        $this->keys[ 'extra' ] = $this->unique;

        $this->url_key = md5( sprintf(
            '%s://%s%s',
            $this->keys[ 'ssl' ] ? 'http' : 'https',
            $this->keys[ 'host' ],
            $this->keys[ 'path' ]
        ) );

        $this->url_version = (int) wp_cache_get( "{$this->url_key}_version", $this->group );
    }

    public function is_secure() {
        if ( isset( $_SERVER[ 'HTTPS' ] ) && ( strtolower( $_SERVER[ 'HTTPS' ] ) === 'on' || $_SERVER[ 'HTTPS' ] == '1' ) ) {
            return true;
        }

        if ( isset( $_SERVER[ 'SERVER_PORT' ] ) && ( $_SERVER['SERVER_PORT'] == '443' ) ) {
            return true;
        }

        if ( isset( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ) && $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] === 'https' ) {
            return true;
        }

        return false;
    }

    function add_variant( $function ) {
		$this->vary[ md5( $function ) ] = $function;
	}

    /**
     * This function is called without arguments early in the page load,
     * then with arguments during the output buffer handler.
     *
     * @param  boolean $dimensions
     */
    function do_variants( $dimensions = false ) {
        if ( $dimensions === false ) {
            $dimensions = wp_cache_get( "{$this->url_key}_vary", $this->group );
        } else {
            wp_cache_set( "{$this->url_key}_vary", $dimensions, $this->group, $this->max_age + 10 );
        }

        if ( is_array( $dimensions ) ) {
            ksort( $dimensions );

            foreach ( $dimensions as $key => $function ) {
                $fun = create_function( '', $function );
                $value = $fun();
                $this->keys[ $key ] = $value;
            }
        }
    }

    function generate_keys() {
        $this->key = md5( serialize( $this->keys ) );
        $this->req_key = "{$this->key}_reqs";
    }

    function status_header( $status_header, $status_code ) {
		$this->status_header = $status_header;
		$this->status_code = $status_code;

		return $status_header;
	}

	/**
	 * Merge the arrays of headers into one and send them.
	 *
	 * @param  array  $headers1
	 * @param  array  $headers2
	 */
	function do_headers( $headers1, $headers2 = array() ) {
		$headers = array();
		$keys = array_unique( array_merge( array_keys( $headers1 ), array_keys( $headers2 ) ) );

		foreach ( $keys as $k ) {
			$headers[ $k ] = array();

			if ( isset( $headers1[ $k ] ) && isset( $headers2[ $k ] ) ) {
				$headers[ $k ] = array_merge( (array) $headers2[ $k ], (array) $headers1[ $k ] );
            } else if ( isset( $headers2[ $k ] ) ) {
				$headers[ $k ] = (array) $headers2[ $k ];
			} else {
				$headers[ $k ] = (array) $headers1[ $k ];
            }

			$headers[ $k ] = array_unique( $headers[ $k ] );
		}

		// These headers take precedence over any previously sent with the same names
		foreach ( $headers as $k => $values ) {
			$clobber = true;

			foreach ( $values as $v ) {
				header( "$k: $v", $clobber );
				$clobber = false;
			}
		}
	}

    function output_callback( $output ) {
        $output = trim( $output );

		if ( $this->cancel !== false ) {
			wp_cache_delete( "{$this->url_key}_genlock", $this->group );
			header( 'X-Redis-Cache-Status: BYPASS', true );

			return $output;
		}

		// Do not cache 5xx responses
		if ( isset( $this->status_code ) && intval( $this->status_code / 100 ) === 5 ) {
			wp_cache_delete( "{$this->url_key}_genlock", $this->group );
			header( 'X-Redis-Cache-Status: BYPASS', true );

			return $output;
		}

		$this->do_variants( $this->vary );
		$this->generate_keys();

		$cache = array(
            'version' => $this->url_version,
			'time' => isset( $_SERVER[ 'REQUEST_TIME' ] ) ? $_SERVER[ 'REQUEST_TIME' ] : time(),
            'status_header' => $this->status_header,
			'headers' => array(),
            'output' => $output,
		);

		foreach ( headers_list() as $header ) {
			list( $k, $v ) = array_map( 'trim', explode( ':', $header, 2 ) );
			$cache[ 'headers' ][ $k ][ ] = $v;
		}

		if ( ! empty( $cache[ 'headers' ] ) && ! empty( $this->uncached_headers ) ) {
			foreach ( $this->uncached_headers as $header ) {
				unset( $cache[ 'headers' ][ $header ] );
            }
		}

		foreach ( $cache[ 'headers' ] as $header => $values ) {
			// Don't cache if cookies were set
			if ( strtolower( $header ) === 'set-cookie' ) {
				wp_cache_delete( "{$this->url_key}_genlock", $this->group );
				header( 'X-Redis-Cache-Status: BYPASS', true );

				return $output;
			}

			foreach ( (array) $values as $value ) {
				if ( preg_match( '/^Cache-Control:.*max-?age=(\d+)/i', "{$header}: {$value}", $matches ) ) {
					$this->max_age = intval( $matches[ 1 ] );
                }
            }
		}

		$cache[ 'max_age' ] = $this->max_age;

		wp_cache_set( $this->key, $cache, $this->group, $this->max_age + $this->seconds + 30 );

		wp_cache_delete( "{$this->url_key}_genlock", $this->group );

		if ( $this->cache_control ) {
			// Don't clobber `Last-Modified` header if already set
			if ( ! isset( $cache[ 'headers' ][ 'Last-Modified' ] ) ) {
				header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $cache[ 'time' ] ) . ' GMT', true );
            }

			if ( ! isset( $cache[ 'headers' ][ 'Cache-Control' ] ) ) {
				header( "Cache-Control: max-age={$this->max_age}, must-revalidate", false );
            }
		}

		$this->do_headers( $this->headers );

		return $cache[ 'output' ];
	}

}

$redis_cache = new Redis_Page_Cache;

header( 'X-Redis-Cache-Status: MISS' );

// Don't cache interactive scripts or API endpoints
if ( in_array( basename( $_SERVER[ 'SCRIPT_FILENAME' ] ), array(
    'wp-cron.php',
    'xmlrpc.php',
) ) ) {
    header( 'X-Redis-Cache-Status: BYPASS', true );

    return;
}

// Don't cache javascript generators
if ( strpos( $_SERVER[ 'SCRIPT_FILENAME' ], 'wp-includes/js' ) !== false ) {
    header( 'X-Redis-Cache-Status: BYPASS', true );

	return;
}

// Only cache HEAD and GET requests
if ( isset( $_SERVER[ 'REQUEST_METHOD' ] ) && ! in_array( $_SERVER[ 'REQUEST_METHOD' ], array( 'GET', 'HEAD' ) ) ) {
    header( 'X-Redis-Cache-Status: BYPASS', true );

	return;
}

// Don't cache when cookies indicate a cache-exempt visitor
if ( is_array( $_COOKIE ) && ! empty( $_COOKIE ) ) {
	foreach ( array_keys( $_COOKIE ) as $cookie ) {
		if ( in_array( $cookie, $redis_cache->noskip_cookies ) ) {
            continue;
        }

        if (
            strpos( $cookie, 'wp' ) === 0 ||
            strpos( $cookie, 'wordpress' ) === 0 ||
            strpos( $cookie, 'comment_author' ) === 0
        ) {
            header( 'X-Redis-Cache-Status: BYPASS', true );
            return;
        }
	}
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
    header( 'X-Redis-Cache-Status: DOWN', true );

	return;
}

if ( ! require_once( WP_CONTENT_DIR . '/object-cache.php' ) ) {
    header( 'X-Redis-Cache-Status: DOWN', true );

    return;
}

wp_cache_init();

if ( ! is_object( $wp_object_cache ) ) {
    header( 'X-Redis-Cache-Status: DOWN', true );

	return;
}

// Cache is disabled
if ( $redis_cache->max_age < 1 ) {
    header( 'X-Redis-Cache-Status: BYPASS', true );

	return;
}

// Necessary to prevent clients using cached version after login cookies set
if ( defined( 'WP_REDIS_VARY_COOKIE' ) && WP_REDIS_VARY_COOKIE ) {
    header( 'Vary: Cookie', false );
}

if ( function_exists( 'wp_cache_add_global_groups' ) ) {
    wp_cache_add_global_groups( array( $redis_cache->group ) );
}

$redis_cache->setup_request();
$redis_cache->do_variants();
$redis_cache->generate_keys();

$genlock = false;
$do_cache = false;
$serve_cache = false;
$cache = wp_cache_get( $redis_cache->key, $redis_cache->group );

if ( isset( $cache[ 'version' ] ) && $cache[ 'version' ] != $redis_cache->url_version ) {

	// Refresh the cache if a newer version is available
	header( 'X-Redis-Cache-Status: EXPIRED', true );
	$do_cache = true;

} else if ( $redis_cache->seconds < 1 || $redis_cache->times < 2 ) {

    if ( is_array( $cache ) && time() < $cache[ 'time' ] + $cache[ 'max_age' ] ) {
        $do_cache = false;
        $serve_cache = true;
    } else if ( is_array( $cache ) && $redis_cache->use_stale ) {
        $do_cache = true;
        $serve_cache = true;
    } else {
        $do_cache = true;
    }

} else if ( ! is_array( $cache ) || time() >= $cache[ 'time' ] + $redis_cache->max_age - $redis_cache->seconds ) {

    // No cache item found, or ready to sample traffic again at the end of the cache life

	wp_cache_add( $redis_cache->req_key, 0, $redis_cache->group );
	$requests = wp_cache_incr( $redis_cache->req_key, 1, $redis_cache->group );

    if ( $requests >= $redis_cache->times ) {
        if ( is_array( $cache ) && time() >= $cache[ 'time' ] + $cache[ 'max_age' ] ) {
            header( 'X-Redis-Cache-Status: EXPIRED', true );
        }

        wp_cache_delete( $redis_cache->req_key, $redis_cache->group );
        $do_cache = true;
	} else {
        header( 'X-Redis-Cache-Status: IGNORED', true );
		$do_cache = false;
	}

}

// Obtain cache generation lock
if ( $do_cache ) {
	$genlock = wp_cache_add( "{$redis_cache->url_key}_genlock", 1, $redis_cache->group, 10 );
}

if (
    $serve_cache &&
    isset( $cache[ 'time' ], $cache[ 'max_age' ] ) &&
	time() < $cache[ 'time' ] + $cache[ 'max_age' ]
) {

	// Respect ETags
	$three04 = false;

	if (
        isset( $_SERVER[ 'HTTP_IF_NONE_MATCH' ], $cache[ 'headers' ][ 'ETag' ][ 0 ] ) &&
        $_SERVER[ 'HTTP_IF_NONE_MATCH' ] == $cache[ 'headers' ][ 'ETag' ][ 0 ]
    ) {

        $three04 = true;

    } else if ( $redis_cache->cache_control && isset( $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ] ) ) {

		$client_time = strtotime( $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ] );

		if ( isset( $cache[ 'headers' ][ 'Last-Modified' ][ 0 ] ) ) {
			$cache_time = strtotime( $cache[ 'headers' ][ 'Last-Modified' ][ 0 ] );
        } else {
			$cache_time = $cache[ 'time' ];
        }

		if ( $client_time >= $cache_time ) {
			$three04 = true;
        }

	}

	// Use the cache save time for `Last-Modified` so we can issue "304 Not Modified",
	// but don't clobber a cached `Last-Modified` header.
	if ( $redis_cache->cache_control && ! isset( $cache[ 'headers' ][ 'Last-Modified' ][ 0 ] ) ) {
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $cache[ 'time' ] ) . ' GMT', true );
		header( 'Cache-Control: max-age=' . ( $cache[ 'max_age' ] - time() + $cache[ 'time' ] ) . ', must-revalidate', true );
	}

	$redis_cache->do_headers( $redis_cache->headers, $cache[ 'headers' ] );

	if ( $three04 ) {
		$protocol = $_SERVER[ 'SERVER_PROTOCOL' ];

		if ( ! preg_match( '/^HTTP\/[0-9]{1}.[0-9]{1}$/', $protocol ) ) {
			$protocol = 'HTTP/1.0';
		}

		header( "{$protocol} 304 Not Modified", true, 304 );
        header( 'X-Redis-Cache-Status: HIT', true );
		exit;
	}

	if ( ! empty( $cache[ 'status_header' ] ) ) {
        header( $cache[ 'status_header' ], true );
    }

    header( 'X-Redis-Cache-Status: HIT', true );

    if ( $do_cache && function_exists( 'fastcgi_finish_request' ) ) {

        echo $cache[ 'output' ];
        fastcgi_finish_request();

    } else {
        echo $cache[ 'output' ];
        exit;
    }

}

if ( ! $do_cache || ! $genlock ) {
	return;
}

$wp_filter[ 'status_header' ][ 10 ][ 'redis_cache' ] = array(
    'function' => array( &$redis_cache, 'status_header' ),
    'accepted_args' => 2
);

ob_start( array( &$redis_cache, 'output_callback' ) );
