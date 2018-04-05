<?php

require_once __DIR__ . '/../includes/object-cache.php';

class ObjectCacheTest extends PHPUnit_Framework_TestCase
{
    public function testRedisInstance()
    {
        $wpObjectCache = new WP_Object_Cache(false);
        $this->assertNotEmpty($wpObjectCache->redis_instance());
    }

    public function testRedisClusterInstance()
    {
        // TODO: remove if Travis supports Redis Cluster
       //  $this->expectException(RedisClusterException::class);

        define('WP_REDIS_CLUSTER', '127.0.0.1');
        define('WP_REDIS_HOST', '127.0.0.1');
        $wpObjectCache = new WP_Object_Cache(false);
        $this->assertNotEmpty($wpObjectCache->redis_instance());
    }
}
