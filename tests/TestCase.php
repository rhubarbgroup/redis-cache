<?php

declare(strict_types=1);

namespace Tests;

/**
 * @internal
 * @coversNothing
 */
class TestCase extends \Yoast\WPTestUtils\WPIntegration\TestCase
{
    protected $redis_cache;

    public function set_up()
    {
        parent::set_up();

        // Your own additional setup.
        $this->cache = $this->init_cache();
    }

    public function tear_down()
    {
        // Your own additional tear down.
        parent::tear_down();
    }

    protected function init_cache()
    {
        global $wp_object_cache;

        $cache_class = get_class($wp_object_cache);
        $cache = new $cache_class();
        $cache->add_global_groups([
            'global-cache-test',
            'users',
            'userlogins',
            'usermeta',
            'user_meta',
            'useremail',
            'userslugs',
            'site-transient',
            'site-options',
            'blog-lookup',
            'blog-details',
            'rss',
            'global-posts',
            'blog-id-cache',
            'networks',
            'sites',
            'site-details',
        ]);

        return $cache;
    }
}
