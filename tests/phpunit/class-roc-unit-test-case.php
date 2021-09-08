<?php
/**
 * Test case definition
 *
 * @package RhubarbGroup/RedisCache
 */

declare(strict_types=1);

use Rhubarb\RedisCache\Plugin;

abstract class ROC_Unit_Test_Case extends WP_UnitTestCase {

    private $redis_cache;

    /**
     * Retrieves the plugin's instance
     *
     * @return null|Rhubarb\RedisCache\Plugin
     */
    public function redis_cache() : ?Plugin {
        if ( ! isset( $this->redis_cache ) ) {
            $this->redis_cache = redis_object_cache();
        }
        return $this->redis_cache;
    }

    /**
     * Retrieves an absolute path from the plugin's base directory
     *
     * @param string $relative_path The relative path to change to an absolute one
     * @return null|string
     */
	public static function basepath( string $relative_path ) : ?string {
        $basepath = dirname( dirname( __DIR__ ) );
        $path     = realpath( trailingslashit( $basepath ) . $relative_path );
        return $path ?: null;
    }

    /**
     * Retreives file data (headers) from a specific file
     *
     * @param string               $path    The path of the file
     * @param array<string,string> $headers Headers to find. Format: ['key_index'=>'Header in File']
     * @return array<string,string>
     */
    protected static function fileData( string $path, array $headers ) : array {
        static $data;
        if ( ! isset( $data[ $path ] ) ) {
            $data[ $path ] = get_file_data( $path, $headers );
        }
        return $data[ $path ];
    }

    /**
     * Retrieves file data (headers) from a specific file if the file is readable
     *
     * @param string               $path    The path of the file
     * @param array<string,string> $headers Headers to find. Format: ['key_index'=>'Header in File']
     * @return null|array<string,string>
     */
    protected static function fileHeaders( string $path, array $headers ) : ?array {
        static $data;
		if ( ! isset( $data[ $path ] ) ) {
            $data[ $path ] = is_readable( $path )
                ? self::fileData( $path, $headers )
                : null;
		}

		return $data[ $path ];
    }

}
