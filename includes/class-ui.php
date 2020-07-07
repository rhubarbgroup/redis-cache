<?php
/**
 * UI utility class
 *
 * @package Rhubarb\RedisCache
 */

namespace Rhubarb\RedisCache;

defined( '\\ABSPATH' ) || exit;

class UI {

    private static $tabs = [];

    public static function register_tab( $slug, $label, $args = [] ) {
        self::$tabs[ $slug ] = (object) wp_parse_args(
            $args,
            [
                'label' => $label,
                'file' => WP_REDIS_PLUGIN_PATH . "/includes/ui/tabs/{$slug}.php",
                'slug' => $slug,
                'target' => "#{$slug}",
                'default' => false,
            ]
        );
    }

    public static function get_tabs() {
        return self::$tabs;
    }

}
