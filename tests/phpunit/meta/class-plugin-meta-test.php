<?php
/**
 * Plugin meta information correctness testing
 *
 * @package RhubarbGroup/RedisCache
 */

declare(strict_types=1);

/**
 * Plugin Meta test case
 *
 * @coversNothing
 */
class Plugin_Meta_Test extends ROC_Unit_Test_Case {

    const PLUGIN_PATH = 'redis-cache.php';
    const README_PATH = 'readme.txt';
    const DROPIN_PATH = 'includes/object-cache.php';

    public function testMainPluginFileIncluded() : void {
        self::assertNotNull( self::pluginHeaders(), 'Main plugin file not found' );
    }

    public function testReadmeIncluded() : void {
        self::assertNotNull( self::readmeHeaders(), 'Readme not found' );
    }

    public function testDropInIncluded() : void {
        self::assertNotNull( self::dropinHeaders(), 'Drop-in not found' );
    }

    /**
     * @depends testReadmeIncluded
     * @depends testMainPluginFileIncluded
     */
	public function testReadmeStableTagIsUpToDate() : void {
        $readme_data = self::readmeHeaders();
		$plugin_data = self::pluginHeaders();

		self::assertEquals(
            $readme_data['StableTag'],
            $plugin_data['Version'],
            'Version information between main plugin file and readme is not up to date'
        );
	}

    /**
     * @depends testDropInIncluded
     * @depends testMainPluginFileIncluded
     */
    public function testDropInVersionIsUpToDate() : void {
        $dropin_data = self::dropinHeaders();
        $dropin_data['Name'] = trim( preg_replace( '/Drop-?In/i', '', $dropin_data['Name'] ) );
		$plugin_data = self::pluginHeaders();

        self::assertArraySubset(
            $dropin_data,
            $plugin_data,
            true,
            'Dropin file headers do not match main plugin file headers'
        );
    }

	private static function readmeHeaders() : ?array {
        return self::fileHeaders(
            self::basepath( self::README_PATH ),            
            [
                'TestedUpTo' => 'Tested up to',
                'StableTag'  => 'Stable tag',
            ]
        );
	}

    private function dropinHeaders() : ?array {
        return self::fileHeaders(
            self::basepath( self::DROPIN_PATH ),
            [
                'Name'        => 'Plugin Name',
                'PluginURI'   => 'Plugin URI',
                'Version'     => 'Version',
                'Description' => 'Description',
                'Author'      => 'Author',
                'AuthorURI'   => 'Author URI',
                'License'     => 'License',
                'LicenseURI'  => 'License URI',
            ]
        );
    }

    private function pluginHeaders() : ?array {
        return self::fileHeaders(
            self::basepath( self::PLUGIN_PATH ),
            [
                'Name'        => 'Plugin Name',
                'PluginURI'   => 'Plugin URI',
                'Version'     => 'Version',
                'Description' => 'Description',
                'Author'      => 'Author',
                'AuthorURI'   => 'Author URI',
                'License'     => 'License',
                'LicenseURI'  => 'License URI',
            ]
        );
    }

}
