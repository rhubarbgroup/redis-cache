<?php
/**
 * Plugin testing
 *
 * @package RhubarbGroup/RedisCache
 */

declare(strict_types=1);

use Rhubarb\RedisCache\Plugin;

/**
 * Plugin test case
 *
 * @coversNothing
 */
class Plugin_Test extends ROC_Unit_Test_Case {

    const DROPIN_PATH = 'includes/object-cache.php';

    public function test_global_instantaciation_function() : void {
        self::assertInstanceOf( Plugin::class, redis_object_cache() );
    }

    public function test_object_cache_dropin_exists() : void {
        self::assertTrue( $this->redis_cache()->object_cache_dropin_exists() );
    }

    public function test_object_cache_dropin_exists_failure() : void {
        $target_file = WP_CONTENT_DIR . '/object-cache.php';
        $backup_file = WP_CONTENT_DIR . '/object-cache.foo.php';
        if ( copy( $target_file, $backup_file ) ) {
            unlink( $target_file );
        }
        self::assertFalse( $this->redis_cache()->object_cache_dropin_exists() );
        if ( copy( $backup_file, $target_file ) ) {
            unlink( $backup_file );
        }
    }

    /**
     * @depends test_object_cache_dropin_exists
     */
    public function test_validate_object_cache_dropin() : void {
        self::assertTrue( $this->redis_cache()->validate_object_cache_dropin() );
    }

    /**
     * @depends test_object_cache_dropin_exists
     */
    public function test_validate_object_cache_dropin_filter() : void {
        $test = 0;
        add_filter(
            'redis_cache_validate_dropin',
            function( $is_valid ) use ( &$test ) {
                $test++;
                return $is_valid;
            }
        );
        $this->redis_cache()->validate_object_cache_dropin();
        // Test if the filter was executed exactly one time.
        self::assertEquals( 1, $test );
    }

    /**
     * @depends test_object_cache_dropin_exists
     */
    public function test_object_cache_dropin_outdated() : void {
        self::assertFalse( $this->redis_cache()->object_cache_dropin_outdated() );
    }

}
