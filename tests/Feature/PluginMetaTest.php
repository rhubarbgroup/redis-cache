<?php

declare(strict_types=1);

namespace Tests\Feature;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class PluginMetaTest extends TestCase
{
    use ArraySubsetAsserts;

    public const PLUGIN_PATH = 'redis-cache.php';
    public const README_PATH = 'readme.txt';
    public const DROPIN_PATH = 'includes/object-cache.php';

    public function testMainPluginFileIncluded(): void
    {
        $this->assertNotNull($this->pluginHeaders(), 'Main plugin file not found');
    }

    public function testReadmeIncluded(): void
    {
        $this->assertNotNull($this->readmeHeaders(), 'Readme not found');
    }

    public function testDropInIncluded(): void
    {
        $this->assertNotNull($this->dropinHeaders(), 'Drop-in not found');
    }

    /**
     * @depends testReadmeIncluded
     * @depends testMainPluginFileIncluded
     */
    public function testReadmeStableTagIsUpToDate(): void
    {
        $readme_data = $this->readmeHeaders();
        $plugin_data = $this->pluginHeaders();

        $this->assertEquals(
            $readme_data['StableTag'],
            $plugin_data['Version'],
            'Version information between main plugin file and readme is not up to date'
        );
    }

    /**
     * @depends testDropInIncluded
     * @depends testMainPluginFileIncluded
     */
    public function testDropInVersionIsUpToDate(): void
    {
        $dropin_data = $this->dropinHeaders();
        $dropin_data['Name'] = trim(preg_replace('/Drop-?In/i', '', $dropin_data['Name']));
        $plugin_data = $this->pluginHeaders();

        $this->assertArraySubset(
            $dropin_data,
            $plugin_data,
            true,
            'Dropin file headers do not match main plugin file headers'
        );
    }

    /**
     * Retrieves an absolute path from the plugin's base directory.
     *
     * @param string $relative_path The relative path to change to an absolute one
     */
    protected function basepath(string $relative_path): ?string
    {
        $basepath = dirname(dirname(__DIR__));
        $path = realpath(trailingslashit($basepath).$relative_path);

        return $path ?: null;
    }

    /**
     * Retrieves file data (headers) from a specific file if the file is readable.
     *
     * @param string               $path    The path of the file
     * @param array<string,string> $headers Headers to find. Format: ['key_index'=>'Header in File']
     *
     * @return null|array<string,string>
     */
    protected function fileHeaders(string $path, array $headers): ?array
    {
        static $data;
        if (!isset($data[$path])) {
            $data[$path] = is_readable($path)
                ? $this->fileData($path, $headers)
                : null;
        }

        return $data[$path];
    }

    /**
     * Retrieves file data (headers) from a specific file.
     *
     * @param string               $path    The path of the file
     * @param array<string,string> $headers Headers to find. Format: ['key_index'=>'Header in File']
     *
     * @return array<string,string>
     */
    protected function fileData(string $path, array $headers): array
    {
        static $data;
        if (!isset($data[$path])) {
            $data[$path] = get_file_data($path, $headers);
        }

        return $data[$path];
    }

    protected function readmeHeaders(): ?array
    {
        return $this->fileHeaders(
            $this->basepath(self::README_PATH),
            [
                'TestedUpTo' => 'Tested up to',
                'StableTag' => 'Stable tag',
            ]
        );
    }

    protected function dropinHeaders(): ?array
    {
        return $this->fileHeaders(
            $this->basepath(self::DROPIN_PATH),
            [
                'Name' => 'Plugin Name',
                'PluginURI' => 'Plugin URI',
                'Version' => 'Version',
                'Description' => 'Description',
                'Author' => 'Author',
                'AuthorURI' => 'Author URI',
                'License' => 'License',
                'LicenseURI' => 'License URI',
            ]
        );
    }

    protected function pluginHeaders(): ?array
    {
        return $this->fileHeaders(
            $this->basepath(self::PLUGIN_PATH),
            [
                'Name' => 'Plugin Name',
                'PluginURI' => 'Plugin URI',
                'Version' => 'Version',
                'Description' => 'Description',
                'Author' => 'Author',
                'AuthorURI' => 'Author URI',
                'License' => 'License',
                'LicenseURI' => 'License URI',
            ]
        );
    }
}
