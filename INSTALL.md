# Installing Redis Object Cache

## 1. Installing Redis Server

This plugin requires [Redis Server](https://redis.io) to be installed and running.
You can [install it yourself](http://redis.io/topics/quickstart), or ask your hosting company for assistance.

## 2. Installing the plugin

Next, install the `Redis Object Cache` plugin via the WordPress Dashboard, or using Composer.
For detailed installation instructions, please read the [standard installation procedure for WordPress plugins]([http://codex.wordpress.org/Managing_Plugins#Installing_Plugins](https://wordpress.org/documentation/article/manage-plugins/#finding-and-installing-plugins-1)).

If you manage plugins using Composer, run: `composer require wpackagist-plugin/redis-cache`

## 3. Configuring the plugin

After installing and activating the plugin, go to `WordPress -> Settings -> Redis` and check if the plu
> _Network Admin -> Settings -> Redis_
> >or use the WP CLI `wp redis enable` command.

By default _Redis Object Cache_ will connect 

>If necessary, adjust [connection parameters](Connection-Parameters).

>If your server doesn't support the [WordPress Filesystem API](https://codex.wordpress.org/Filesystem_API), you have to manually copy the `object-cache.php` file from the `/plugins/redis-cache/includes/` directory to the `/wp-content/` directory.
