<?php
/*
Plugin Name: Redis Object Cache Drop-In
Plugin URI: http://wordpress.org/plugins/redis-cache/
Description: A persistent object cache backend powered by Redis. Supports Predis, PhpRedis, HHVM, replication, clustering and WP-CLI.
Version: 1.6.3
Author: Till Krüss
Author URI: https://till.im/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Based on Eric Mann's and Erick Hitter's Redis Object Cache:
https://github.com/ericmann/Redis-Object-Cache
*/

if ( defined( 'WP_REDIS_DISABLED' ) && WP_REDIS_DISABLED ) {
    return;
}

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
 * Invalidate all items in the cache. If `WP_REDIS_SELECTIVE_FLUSH` is `true`,
 * only keys prefixed with the `WP_CACHE_KEY_SALT` are flushed.
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
 * @param string $key        The key under which to store the value.
 * @param string $group      The group value appended to the $key.
 * @param bool   $force      Optional. Whether to force an update of the local cache from the persistent
 *                           cache. Default false.
 * @param bool   &$found     Optional. Whether the key was found in the cache. Disambiguates a return of false,
 *                           a storable value. Passed by reference. Default null.
 *
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return bool|mixed             Cached object value.
 */
function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
    global $wp_object_cache;

    return $wp_object_cache->get( $key, $group, $force, $found );
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
 * @param   array $groups  Array of groups and keys to retrieve
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

    if ( ! ( $wp_object_cache instanceof Rhubarb\RedisCache\WP_Object_Cache ) ) {
        $fail_gracefully = ! defined( 'WP_REDIS_GRACEFUL' ) || WP_REDIS_GRACEFUL;

        $plugin_dir = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';
        $file_path  = "{$plugin_dir}/redis-cache/includes/class-wp-object-cache.php";

        if ( is_readable( $file_path ) ) {
            require_once $file_path;
            // phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
            $wp_object_cache = new Rhubarb\RedisCache\WP_Object_Cache( $fail_gracefully );
            // phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
        }
    }
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
 * Switch the internal blog id.
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
