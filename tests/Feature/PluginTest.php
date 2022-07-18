<?php

declare(strict_types=1);

namespace Tests\Feature;

use Rhubarb\RedisCache\Plugin;
use Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class PluginTest extends TestCase
{
    public const DROPIN_PATH = 'includes/object-cache.php';

    public function set_up()
    {
        parent::set_up();

        copy(
            WP_PLUGIN_DIR.'/redis-cache/includes/object-cache.php',
            WP_CONTENT_DIR.'/object-cache.php'
        );
    }

    public function tear_down()
    {
        unlink(WP_CONTENT_DIR.'/object-cache.php');

        parent::set_up();
    }

    public function testGlobalInstantaciationFunction(): void
    {
        self::assertInstanceOf(Plugin::class, redis_object_cache());
    }

    public function testObjectCacheDropinExists(): void
    {
        self::assertTrue($this->redis_cache()->object_cache_dropin_exists());
    }

    public function testObjectCacheDropinExistsFailure(): void
    {
        $target_file = WP_CONTENT_DIR.'/object-cache.php';
        $backup_file = WP_CONTENT_DIR.'/object-cache.foo.php';

        if (copy($target_file, $backup_file)) {
            unlink($target_file);
        }

        self::assertFalse($this->redis_cache()->object_cache_dropin_exists());

        if (copy($backup_file, $target_file)) {
            unlink($backup_file);
        }
    }

    /**
     * @depends testObjectCacheDropinExists
     */
    public function testValidateObjectCacheDropin(): void
    {
        self::assertTrue($this->redis_cache()->validate_object_cache_dropin());
    }

    /**
     * @depends testObjectCacheDropinExists
     */
    public function testValidateObjectCacheDropinFilter(): void
    {
        $test = 0;

        add_filter(
            'redis_cache_validate_dropin',
            function ($is_valid) use (&$test) {
                ++$test;

                return $is_valid;
            }
        );

        $this->redis_cache()->validate_object_cache_dropin();

        // Test if the filter was executed exactly one time.
        self::assertEquals(1, $test);
    }

    /**
     * @depends testObjectCacheDropinExists
     */
    public function testObjectCacheDropinOutdated(): void
    {
        self::assertFalse($this->redis_cache()->object_cache_dropin_outdated());
    }

    /**
     * Retrieves the plugin's instance.
     *
     * @return null|Rhubarb\RedisCache\Plugin
     */
    protected function redis_cache(): ?Plugin
    {
        return redis_object_cache();
    }
}
