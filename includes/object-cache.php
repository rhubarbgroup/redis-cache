<?php
/*
Plugin Name: Redis Object Cache
Plugin URI: http://wordpress.org/plugins/redis-cache/
Description: A Redis backend for the WordPress Object Cache based on the Predis client library for PHP.
Version: 1.0.2
Author: Till Krüss
Author URI: http://till.kruss.me/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Based on Eric Mann's and Erick Hitter's Redis Object Cache:
https://github.com/ericmann/Redis-Object-Cache
*/

/**
 * Adds a value to cache.
 *
 * If the specified key already exists, the value is not stored and the function
 * returns false.
 *
 * @param string $key        The key under which to store the value.
 * @param mixed  $value      The value to store.
 * @param string $group      The group value appended to the $key.
 * @param int    $expiration The expiration time, defaults to 0.
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return bool              Returns TRUE on success or FALSE on failure.
 */
function wp_cache_add( $key, $value, $group = '', $expiration = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->add( $key, $value, $group, $expiration );
}

/**
 * Closes the cache.
 *
 * This function has ceased to do anything since WordPress 2.5. The
 * functionality was removed along with the rest of the persistent cache. This
 * does not mean that plugins can't implement this function when they need to
 * make sure that the cache is cleaned up after WordPress no longer needs it.
 *
 * @return  bool    Always returns True
 */
function wp_cache_close() {
	return true;
}

/**
 * Decrement a numeric item's value.
 *
 * @param string $key    The key under which to store the value.
 * @param int    $offset The amount by which to decrement the item's value.
 * @param string $group  The group value appended to the $key.
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return int|bool      Returns item's new value on success or FALSE on failure.
 */
function wp_cache_decr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->decrement( $key, $offset, $group );
}

/**
 * Remove the item from the cache.
 *
 * @param string $key    The key under which to store the value.
 * @param string $group  The group value appended to the $key.
 * @param int    $time   The amount of time the server will wait to delete the item in seconds.
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return bool           Returns TRUE on success or FALSE on failure.
 */
function wp_cache_delete( $key, $group = '', $time = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->delete( $key, $group, $time );
}

/**
 * Invalidate all items in the cache.
 *
 * @param int $delay  Number of seconds to wait before invalidating the items.
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return bool             Returns TRUE on success or FALSE on failure.
 */
function wp_cache_flush( $delay = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->flush( $delay );
}

/**
 * Retrieve object from cache.
 *
 * Gets an object from cache based on $key and $group.
 *
 * @param string      $key        The key under which to store the value.
 * @param string      $group      The group value appended to the $key.
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return bool|mixed             Cached object value.
 */
function wp_cache_get( $key, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->get( $key, $group );
}

/**
 * Retrieve multiple values from cache.
 *
 * Gets multiple values from cache, including across multiple groups
 *
 * Usage: array( 'group0' => array( 'key0', 'key1', 'key2', ), 'group1' => array( 'key0' ) )
 *
 * Mirrors the Memcached Object Cache plugin's argument and return-value formats
 *
 * @param   array       $groups  Array of groups and keys to retrieve
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return  bool|mixed           Array of cached values, keys in the format $group:$key. Non-existent keys false
 */
function wp_cache_get_multi( $groups ) {
	global $wp_object_cache;
	return $wp_object_cache->get_multi( $groups );
}

/**
 * Increment a numeric item's value.
 *
 * @param string $key    The key under which to store the value.
 * @param int    $offset The amount by which to increment the item's value.
 * @param string $group  The group value appended to the $key.
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return int|bool      Returns item's new value on success or FALSE on failure.
 */
function wp_cache_incr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->increment( $key, $offset, $group );
}

/**
 * Sets up Object Cache Global and assigns it.
 *
 * @global  WP_Object_Cache $wp_object_cache    WordPress Object Cache
 *
 * @return  void
 */
function wp_cache_init() {
	global $wp_object_cache;
	$wp_object_cache = new WP_Object_Cache();
}

/**
 * Replaces a value in cache.
 *
 * This method is similar to "add"; however, is does not successfully set a value if
 * the object's key is not already set in cache.
 *
 * @param string $key        The key under which to store the value.
 * @param mixed  $value      The value to store.
 * @param string $group      The group value appended to the $key.
 * @param int    $expiration The expiration time, defaults to 0.
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return bool              Returns TRUE on success or FALSE on failure.
 */
function wp_cache_replace( $key, $value, $group = '', $expiration = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->replace( $key, $value, $group, $expiration );
}

/**
 * Sets a value in cache.
 *
 * The value is set whether or not this key already exists in Redis.
 *
 * @param string $key        The key under which to store the value.
 * @param mixed  $value      The value to store.
 * @param string $group      The group value appended to the $key.
 * @param int    $expiration The expiration time, defaults to 0.
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return bool              Returns TRUE on success or FALSE on failure.
 */
function wp_cache_set( $key, $value, $group = '', $expiration = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->set( $key, $value, $group, $expiration );
}

/**
 * Switch the interal blog id.
 *
 * This changes the blog id used to create keys in blog specific groups.
 *
 * @param  int $_blog_id Blog ID
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return bool
 */
function wp_cache_switch_to_blog( $_blog_id ) {
	global $wp_object_cache;
	return $wp_object_cache->switch_to_blog( $_blog_id );
}

/**
 * Adds a group or set of groups to the list of Redis groups.
 *
 * @param   string|array $groups     A group or an array of groups to add.
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return  void
 */
function wp_cache_add_global_groups( $groups ) {
	global $wp_object_cache;
	$wp_object_cache->add_global_groups( $groups );
}

/**
 * Adds a group or set of groups to the list of non-Redis groups.
 *
 * @param   string|array $groups     A group or an array of groups to add.
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return  void
 */
function wp_cache_add_non_persistent_groups( $groups ) {
	global $wp_object_cache;
	$wp_object_cache->add_non_persistent_groups( $groups );
}

class WP_Object_Cache {

	/**
	 * Holds the Redis client.
	 *
	 * @var Predis\Client
	 */
	private $redis;

	/**
	 * Track if Redis is available
	 *
	 * @var bool
	 */
	private $redis_connected = false;

	/**
	 * Holds the non-Redis objects.
	 *
	 * @var array
	 */
	private $cache = array();

	/**
	 * List of global groups.
	 *
	 * @var array
	 */
	public $global_groups = array( 'users', 'userlogins', 'usermeta', 'site-options', 'site-lookup', 'blog-lookup', 'blog-details', 'rss' );

	/**
	 * List of groups not saved to Redis.
	 *
	 * @var array
	 */
	public $no_redis_groups = array( 'comment', 'counts' );

	/**
	 * Prefix used for global groups.
	 *
	 * @var string
	 */
	public $global_prefix = '';

	/**
	 * Prefix used for non-global groups.
	 *
	 * @var string
	 */
	public $blog_prefix = '';

	/**
	 * Track how many requests were found in cache
	 *
	 * @var int
	 */
	public $cache_hits = 0;

	/**
	 * Track how may requests were not cached
	 *
	 * @var int
	 */
	public $cache_misses = 0;

	/**
	 * Instantiate the Redis class.
	 *
	 * Instantiates the Redis class.
	 *
	 * @param   null $persistent_id      To create an instance that persists between requests, use persistent_id to specify a unique ID for the instance.
	 */
	public function __construct() {
		global $blog_id, $table_prefix;

		// General Redis settings
		$redis = array(
			'scheme' => 'tcp',
			'host' => '127.0.0.1',
			'port' => 6379
		);

		if ( defined( 'WP_REDIS_SCHEME' ) ) {
			$redis[ 'scheme' ] = WP_REDIS_SCHEME;
		}

		if ( defined( 'WP_REDIS_HOST' ) ) {
			$redis[ 'host' ] = WP_REDIS_HOST;
		}

		if ( defined( 'WP_REDIS_PORT' ) ) {
			$redis[ 'port' ] = WP_REDIS_PORT;
		}

		if ( defined( 'WP_REDIS_PATH' ) ) {
			$redis[ 'path' ] = WP_REDIS_PATH;
		}

		if ( defined( 'WP_REDIS_PASSWORD' ) ) {
			$redis[ 'password' ] = WP_REDIS_PASSWORD;
		}

		if ( defined( 'WP_REDIS_DATABASE' ) ) {
			$redis[ 'database' ] = WP_REDIS_DATABASE;
		}

		try {

			// is HHVM's built-in Redis class available?
			if ( defined( 'HHVM_VERSION' ) && class_exists( 'Redis' ) ) {

				$this->redis = new Redis();

				// adjust host and port, if the scheme is `unix`
				if ( strcasecmp( 'unix', $redis[ 'scheme' ] ) === 0 ) {
					$redis[ 'host' ] = 'unix://' . $redis[ 'path' ];
					$redis[ 'port' ] = 0;
				}

				if ( ! $this->redis->connect( $redis[ 'host' ], $redis[ 'port' ] ) ) {
					throw new Exception;
				}

				$this->redis_connected = true;

			} else {

				// require PHP 5.4 or greater
				if ( version_compare( PHP_VERSION, '5.4.0', '<' ) ) {
					throw new Exception;
				}

				// check if bundled Predis library exists
				if ( ! realpath( dirname( __FILE__ ) . '/plugins/redis-cache/includes/predis.php' ) ) {
					throw new Exception;
				}

				require_once dirname( __FILE__ ) . '/plugins/redis-cache/includes/predis.php';

				Predis\Autoloader::register();

				$this->redis = new Predis\Client( $redis );
				$this->redis->connect();
				$this->redis_connected = true;

			}

		} catch ( Exception $exception ) {

			// When Redis is unavailable, fall back to the internal back by forcing all groups to be "no redis" groups
			$this->no_redis_groups = array_unique( array_merge( $this->no_redis_groups, $this->global_groups ) );

			$this->redis_connected = false;

		}

		/**
		 * This approach is borrowed from Sivel and Boren. Use the salt for easy cache invalidation and for
		 * multi single WP installs on the same server.
		 */
		if ( ! defined( 'WP_CACHE_KEY_SALT' ) ) {
			define( 'WP_CACHE_KEY_SALT', '' );
		}

		// Assign global and blog prefixes for use with keys
		if ( function_exists( 'is_multisite' ) ) {
			$this->global_prefix = ( is_multisite() || defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' ) ) ? '' : $table_prefix;
			$this->blog_prefix = ( is_multisite() ? $blog_id : $table_prefix ) . ':';
		}
	}

	/**
	 * Is Redis available?
	 *
	 * @return bool
	 */
	public function redis_status() {
		return $this->redis_connected;
	}

	/**
	 * Adds a value to cache.
	 *
	 * If the specified key already exists, the value is not stored and the function
	 * returns false.
	 *
	 * @param   string $key            The key under which to store the value.
	 * @param   mixed  $value          The value to store.
	 * @param   string $group          The group value appended to the $key.
	 * @param   int    $expiration     The expiration time, defaults to 0.
	 * @return  bool                   Returns TRUE on success or FALSE on failure.
	 */
	public function add( $key, $value, $group = 'default', $expiration = 0 ) {
		return $this->add_or_replace( true, $key, $value, $group, $expiration );
	}

	/**
	 * Replace a value in the cache.
	 *
	 * If the specified key doesn't exist, the value is not stored and the function
	 * returns false.
	 *
	 * @param   string $key            The key under which to store the value.
	 * @param   mixed  $value          The value to store.
	 * @param   string $group          The group value appended to the $key.
	 * @param   int    $expiration     The expiration time, defaults to 0.
	 * @return  bool                   Returns TRUE on success or FALSE on failure.
	 */
	public function replace( $key, $value, $group = 'default', $expiration = 0 ) {
		return $this->add_or_replace( false, $key, $value, $group, $expiration );
	}

	/**
	 * Add or replace a value in the cache.
	 *
	 * Add does not set the value if the key exists; replace does not replace if the value doesn't exist.
	 *
	 * @param   bool   $add            True if should only add if value doesn't exist, false to only add when value already exists
	 * @param   string $key            The key under which to store the value.
	 * @param   mixed  $value          The value to store.
	 * @param   string $group          The group value appended to the $key.
	 * @param   int    $expiration     The expiration time, defaults to 0.
	 * @return  bool                   Returns TRUE on success or FALSE on failure.
	 */
	protected function add_or_replace( $add, $key, $value, $group = 'default', $expiration = 0 ) {
		$derived_key = $this->build_key( $key, $group );

		// If group is a non-Redis group, save to internal cache, not Redis
		if ( in_array( $group, $this->no_redis_groups ) || ! $this->redis_status() ) {

			// Check if conditions are right to continue
			if (
				( $add   &&   isset( $this->cache[ $derived_key ] ) ) ||
				( ! $add && ! isset( $this->cache[ $derived_key ] ) )
			) {
				return false;
			}

			$this->add_to_internal_cache( $derived_key, $value );

			return true;
		}

		// Check if conditions are right to continue
		if (
			( $add   &&   $this->redis->exists( $derived_key ) ) ||
			( ! $add && ! $this->redis->exists( $derived_key ) )
		) {
			return false;
		}

		// Save to Redis
		$expiration = abs( intval( $expiration ) );
		if ( $expiration ) {
			$result = $this->parse_predis_response( $this->redis->setex( $derived_key, $expiration, maybe_serialize( $value ) ) );
		} else {
			$result = $this->parse_predis_response( $this->redis->set( $derived_key, maybe_serialize( $value ) ) );
		}

		return $result;
	}

	/**
	 * Remove the item from the cache.
	 *
	 * @param   string $key        The key under which to store the value.
	 * @param   string $group      The group value appended to the $key.
	 * @return  bool               Returns TRUE on success or FALSE on failure.
	 */
	public function delete( $key, $group = 'default' ) {
		$derived_key = $this->build_key( $key, $group );

		// Remove from no_redis_groups array
		if ( in_array( $group, $this->no_redis_groups ) || ! $this->redis_status() ) {
			if ( isset( $this->cache[ $derived_key ] ) ) {
				unset( $this->cache[ $derived_key ] );

				return true;
			} else {
				return false;
			}
		}

		$result = $this->parse_predis_response( $this->redis->del( $derived_key ) );

		unset( $this->cache[ $derived_key ] );

		return $result;
	}

	/**
	 * Invalidate all items in the cache.
	 *
	 * @param   int $delay      Number of seconds to wait before invalidating the items.
	 * @return  bool            Returns TRUE on success or FALSE on failure.
	 */
	public function flush( $delay = 0 ) {
		$delay = abs( intval( $delay ) );
		if ( $delay ) {
			sleep( $delay );
		}

		$this->cache = array();

		if ( $this->redis_status() ) {
			$result = $this->parse_predis_response( $this->redis->flushdb() );
		}

		return $result;
	}

	/**
	 * Retrieve object from cache.
	 *
	 * Gets an object from cache based on $key and $group.
	 *
	 * @param   string        $key        The key under which to store the value.
	 * @param   string        $group      The group value appended to the $key.
	 * @return  bool|mixed                Cached object value.
	 */
	public function get( $key, $group = 'default' ) {
		$derived_key = $this->build_key( $key, $group );

		if ( isset( $this->cache[ $derived_key ] ) ) {
			$this->cache_hits++;
			return is_object( $this->cache[ $derived_key ] ) ? clone $this->cache[ $derived_key ] : $this->cache[ $derived_key ];
		} elseif ( in_array( $group, $this->no_redis_groups ) || ! $this->redis_status() ) {
			$this->cache_misses++;
			return false;
		}

		$result = $this->redis->get( $derived_key );
		if ($result == NULL) {
			$this->cache_misses++;
			return false;
		} else {
			$this->cache_hits++;
			$value = maybe_unserialize($result);
		}

		$this->add_to_internal_cache( $derived_key, $value );

		return is_object( $value ) ? clone $value : $value;
	}

	/**
	 * Retrieve multiple values from cache.
	 *
	 * Gets multiple values from cache, including across multiple groups
	 *
	 * Usage: array( 'group0' => array( 'key0', 'key1', 'key2', ), 'group1' => array( 'key0' ) )
	 *
	 * Mirrors the Memcached Object Cache plugin's argument and return-value formats
	 *
	 * @param   array                           $groups  Array of groups and keys to retrieve
	 * @uses    this::filter_redis_get_multi()
	 * @return  bool|mixed                               Array of cached values, keys in the format $group:$key. Non-existent keys null.
	 */
	public function get_multi( $groups ) {
		if ( empty( $groups ) || ! is_array( $groups ) ) {
			return false;
		}

		// Retrieve requested caches and reformat results to mimic Memcached Object Cache's output
		$cache = array();

		foreach ( $groups as $group => $keys ) {
			if ( in_array( $group, $this->no_redis_groups ) || ! $this->redis_status() ) {
				foreach ( $keys as $key ) {
					$cache[ $this->build_key( $key, $group ) ] = $this->get( $key, $group );
				}
			} else {
				// Reformat arguments as expected by Redis
				$derived_keys = array();
				foreach ( $keys as $key ) {
					$derived_keys[] = $this->build_key( $key, $group );
				}

				// Retrieve from cache in a single request
				$group_cache = $this->redis->mget( $derived_keys );

				// Build an array of values looked up, keyed by the derived cache key
				$group_cache = array_combine( $derived_keys, $group_cache );

				// Restores cached data to its original data type
				$group_cache = array_map( array( $this, 'maybe_unserialize' ), $group_cache );

				// Redis returns null for values not found in cache, but expected return value is false in this instance
				$group_cache = array_map( array( $this, 'filter_redis_get_multi' ), $group_cache );

				$cache = array_merge( $cache, $group_cache );
			}
		}

		// Add to the internal cache the found values from Redis
		foreach ( $cache as $key => $value ) {
			if ( $value ) {
				$this->cache_hits++;
				$this->add_to_internal_cache( $key, $value );
			} else {
				$this->cache_misses++;
			}
		}

		return $cache;
	}

	/**
	 * Sets a value in cache.
	 *
	 * The value is set whether or not this key already exists in Redis.
	 *
	 * @param   string $key        The key under which to store the value.
	 * @param   mixed  $value      The value to store.
	 * @param   string $group      The group value appended to the $key.
	 * @param   int    $expiration The expiration time, defaults to 0.
	 * @return  bool               Returns TRUE on success or FALSE on failure.
	 */
	public function set( $key, $value, $group = 'default', $expiration = 0 ) {
		$derived_key = $this->build_key( $key, $group );

		// If group is a non-Redis group, save to internal cache, not Redis
		if ( in_array( $group, $this->no_redis_groups ) || ! $this->redis_status() ) {
			$this->add_to_internal_cache( $derived_key, $value );

			return true;
		}

		// Save to Redis
		$expiration = abs( intval( $expiration ) );
		if ( $expiration ) {
			$result = $this->parse_predis_response( $this->redis->setex( $derived_key, $expiration, maybe_serialize( $value ) ) );
		} else {
			$result = $this->parse_predis_response( $this->redis->set( $derived_key, maybe_serialize( $value ) ) );
		}

		return $result;
	}

	/**
	 * Increment a Redis counter by the amount specified
	 *
	 * @param  string $key
	 * @param  int    $offset
	 * @param  string $group
	 * @return bool
	 */
	public function increment( $key, $offset = 1, $group = 'default' ) {
		$derived_key = $this->build_key( $key, $group );
		$offset = (int) $offset;

		// If group is a non-Redis group, save to internal cache, not Redis
		if ( in_array( $group, $this->no_redis_groups ) || ! $this->redis_status() ) {
			$value = $this->get_from_internal_cache( $derived_key, $group );
			$value += $offset;
			$this->add_to_internal_cache( $derived_key, $value );

			return true;
		}

		// Save to Redis
		$result = $this->parse_predis_response( $this->redis->incrBy( $derived_key, $offset ) );

		$this->add_to_internal_cache( $derived_key, (int) $this->redis->get( $derived_key ) );

		return $result;
	}

	/**
	 * Decrement a Redis counter by the amount specified
	 *
	 * @param  string $key
	 * @param  int    $offset
	 * @param  string $group
	 * @return bool
	 */
	public function decrement( $key, $offset = 1, $group = 'default' ) {
		$derived_key = $this->build_key( $key, $group );
		$offset = (int) $offset;

		// If group is a non-Redis group, save to internal cache, not Redis
		if ( in_array( $group, $this->no_redis_groups ) || ! $this->redis_status() ) {
			$value = $this->get_from_internal_cache( $derived_key, $group );
			$value -= $offset;
			$this->add_to_internal_cache( $derived_key, $value );

			return true;
		}

		// Save to Redis
		$result = $this->parse_predis_response( $this->redis->decrBy( $derived_key, $offset ) );

		$this->add_to_internal_cache( $derived_key, (int) $this->redis->get( $derived_key ) );

		return $result;
	}

	/**
	 * Render data about current cache requests
	 *
	 * @return string
	 */
	public function stats() { ?>

		<p>
			<strong><?php $this->_i18n( '_e', 'Cache Status:' ); ?></strong> <?php echo $this->redis_status() ? $this->_i18n( '__', 'Connected' ) : $this->_i18n( '__', 'Not connected' ); ?><br />
			<strong><?php $this->_i18n( '_e', 'Cache Hits:' ); ?></strong> <?php echo $this->_i18n( 'number_format_i18n', $this->cache_hits, false ); ?><br />
			<strong><?php $this->_i18n( '_e', 'Cache Misses:' ); ?></strong> <?php echo $this->_i18n( 'number_format_i18n', $this->cache_misses, false ); ?>
		</p>

		<p><strong><?php $this->_i18n( '_e',  'Caches Retrieved:' ); ?></strong></p>

		<ul>
			<li><em><?php $this->_i18n( '_e',  'prefix:group:key - size in kilobytes' ); ?></em></li>
			<?php foreach ( $this->cache as $group => $cache ) : ?>
				<li><?php printf( $this->_i18n( '__', '%s - %s %s' ), $this->_esc_html( $group, false ), $this->_i18n( 'number_format_i18n', strlen( serialize( $cache ) ) / 1024, false, 2 ), $this->_i18n( '__', 'kb' ) ); ?></li>
			<?php endforeach; ?>
		</ul><?php

	}

	/**
	 * Builds a key for the cached object using the blog_id, key, and group values.
	 *
	 * @author  Ryan Boren   This function is inspired by the original WP Memcached Object cache.
	 * @link    http://wordpress.org/extend/plugins/memcached/
	 *
	 * @param   string $key        The key under which to store the value.
	 * @param   string $group      The group value appended to the $key.
	 *
	 * @return  string
	 */
	public function build_key( $key, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		if ( false !== array_search( $group, $this->global_groups ) ) {
			$prefix = $this->global_prefix;
		} else {
			$prefix = $this->blog_prefix;
		}

		return preg_replace( '/\s+/', '', WP_CACHE_KEY_SALT . "$prefix$group:$key" );
	}

	/**
	 * Convert data types when using Redis MGET
	 *
	 * When requesting multiple keys, those not found in cache are assigned the value null upon return.
	 * Expected value in this case is false, so we convert
	 *
	 * @param   string  $value  Value to possibly convert
	 * @return  string          Converted value
	 */
	protected function filter_redis_get_multi( $value ) {
		if ( is_null( $value ) ) {
			$value = false;
		}

		return $value;
	}

	/**
	 * Convert the response fro Predis into something meaningful
	 *
	 * @param mixed $response
	 * @return mixed
	 */
	protected function parse_predis_response( $response ) {
		if ( is_bool( $response ) ) {
			return $response;
		}

		if ( is_numeric( $response ) ) {
			return (bool) $response;
		}

		if ( is_object( $response ) && method_exists( $response, 'getPayload' ) ) {
			return 'OK' === $response->getPayload();
		}

		return false;
	}

	/**
	 * Simple wrapper for saving object to the internal cache.
	 *
	 * @param   string $derived_key    Key to save value under.
	 * @param   mixed  $value          Object value.
	 */
	public function add_to_internal_cache( $derived_key, $value ) {
		$this->cache[ $derived_key ] = $value;
	}

	/**
	 * Get a value specifically from the internal, run-time cache, not Redis.
	 *
	 * @param   int|string $key        Key value.
	 * @param   int|string $group      Group that the value belongs to.
	 *
	 * @return  bool|mixed              Value on success; false on failure.
	 */
	public function get_from_internal_cache( $key, $group ) {
		$derived_key = $this->build_key( $key, $group );

		if ( isset( $this->cache[ $derived_key ] ) ) {
			return $this->cache[ $derived_key ];
		}

		return false;
	}

	/**
	 * In multisite, switch blog prefix when switching blogs
	 *
	 * @param int $_blog_id
	 * @return bool
	 */
	public function switch_to_blog( $_blog_id ) {
		if ( ! function_exists( 'is_multisite' ) || ! is_multisite() ) {
			return false;
		}

		$this->blog_prefix = $_blog_id . ':';
		return true;
	}

	/**
	 * Sets the list of global groups.
	 *
	 * @param array $groups List of groups that are global.
	 */
	public function add_global_groups( $groups ) {
		$groups = (array) $groups;

		if ( $this->redis_status() ) {
			$this->global_groups = array_unique( array_merge( $this->global_groups, $groups ) );
		} else {
			$this->no_redis_groups = array_unique( array_merge( $this->no_redis_groups, $groups ) );
		}
	}

	/**
	 * Sets the list of groups not to be cached by Redis.
	 *
	 * @param array $groups List of groups that are to be ignored.
	 */
	public function add_non_persistent_groups( $groups ) {
		$groups = (array) $groups;

		$this->no_redis_groups = array_unique( array_merge( $this->no_redis_groups, $groups ) );
	}

	/**
	 * Run a value through an i18n WP function if it exists. Otherwise, just rpass through.
	 *
	 * Since this class may run befor the i18n methods are loaded in WP, we'll make sure they
	 * exist before using them. Most require a text domain, some don't, so the second param allows
	 * specifiying which type is being called.
	 *
	 * @param  string $method The WP method to pass the string through if it exists.
	 * @param  string $string The string to internationalize.
	 * @param  bool   $domain Whether or not to pass the text domain to the method as well.
	 * @param  mixed  $params Any extra param or array of params to send to the method.
	 * @return string         The maybe internationalaized string.
	 */
	protected function _i18n( $method, $string, $domain = true, $params = array() ) {
		// Pass through if the method doesn't exist.
		if ( ! function_exists( $method ) ) {
			return $string;
		}
		// Allow non-array single extra values
		if ( ! is_array( $params ) ) {
			$params = array( $params );
		}
		// Add domain param if needed.
		if ( (bool) $domain ) {
			array_unshift( $params, 'redis-cache' );
		}
		// Add the string
		array_unshift( $params, $string );

		return call_user_func_array( $method, $params );
	}

	/**
	 * Try to escape any HTML from output, if not available, strip tags.
	 *
	 * This helper ensures invalid HTML output is escaped with esc_html if possible. If not,
	 * it will use the native strip_tags instead to simply remove them. This is needed since
	 * in some circumstances this may be loaded before esc_html is available.
	 *
	 * @param  string $string The string to escape or strip.
	 * @return string        The safe string for output.
	 */
	public function _esc_html( $string ) {
		if ( function_exists( 'esc_html' ) ) {
			return esc_html( $string );
		} else {
			return strip_tags( $string );
		}
	}

}
