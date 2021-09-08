#!/usr/bin/env bash
set -e

#
# Initial part copied from
# https://github.com/bitnami/bitnami-docker-wordpress/blob/a0affeb00b7087bcfb81e85e1982a9419ad401c9/5/debian-10/rootfs/opt/bitnami/scripts/wordpress/entrypoint.sh
#

# shellcheck disable=SC1091

set -o errexit
set -o nounset
set -o pipefail
# set -o xtrace # Uncomment this line for debugging purpose

# Load WordPress environment
. /opt/bitnami/scripts/wordpress-env.sh

# Load libraries
. /opt/bitnami/scripts/libbitnami.sh
. /opt/bitnami/scripts/liblog.sh
. /opt/bitnami/scripts/libwebserver.sh

print_welcome_page

if [[ "$1" = "/opt/bitnami/scripts/$(web_server_type)/run.sh" || "$1" = "/opt/bitnami/scripts/nginx-php-fpm/run.sh" ]]; then
    info "** Starting WordPress setup **"
    /opt/bitnami/scripts/"$(web_server_type)"/setup.sh
    /opt/bitnami/scripts/php/setup.sh
    /opt/bitnami/scripts/mysql-client/setup.sh
    /opt/bitnami/scripts/wordpress/setup.sh
    /post-init.sh
    info "** WordPress setup finished! **"
fi

###
# Custom actions
###

# Load libraries
. /opt/bitnami/scripts/libfs.sh
. /opt/bitnami/scripts/libphp.sh
. /opt/bitnami/scripts/libapache.sh
. /opt/bitnami/scripts/libwordpress.sh
# Load environments
. /opt/bitnami/scripts/php-env.sh
. /opt/bitnami/scripts/apache-env.sh

PLUGIN_SOURCE_DIR="/redis-cache"
PLUGIN_TARGET_DIR="/opt/bitnami/wordpress/wp-content/plugins/redis-cache"
APF_FILE_PATH="/redis-cache/docker/apf.php"
WP_TESTS_DIR="$WORDPRESS_BASE_DIR/tests-lib"
WP_VERSION=$(wp core version)

# Reference: `install-wp-tests.sh`
if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+\-(beta|RC)[0-9]+$ ]]; then
	WP_BRANCH=${WP_VERSION%\-*}
	WP_TESTS_TAG="branches/$WP_BRANCH"
elif [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
	WP_TESTS_TAG="branches/$WP_VERSION"
elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0-9]+ ]]; then
	if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0] ]]; then
		# version x.x.0 means the first release of the major version, so strip off the .0 and download version x.x
		WP_TESTS_TAG="tags/${WP_VERSION%??}"
	else
		WP_TESTS_TAG="tags/$WP_VERSION"
	fi
elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
	WP_TESTS_TAG="trunk"
fi

## Symlink generation
info "Creating plugin symlink"
if [ ! -L "$PLUGIN_TARGET_DIR" ]; then
    ln -s "$PLUGIN_SOURCE_DIR" "$PLUGIN_TARGET_DIR"
fi

## Set APF file
if [ -f "$APF_FILE_PATH" ]; then
    php_conf_set auto_prepend_file "$APF_FILE_PATH" "$PHP_DEV_CONF_FILE"
    info "Set PHP auto prepend file ($PHP_DEV_CONF_FILE)"
else
    error "Unable to set PHP auto prepend file"
    ls -lah $(dirname "$APF_FILE_PATH") | grep $(basename "$APF_FILE_PATH")
fi

## Set xdebug config
php_conf_set xdebug.mode coverage "$PHP_DEV_CONF_FILE"

## Download WP test suite
## Reference: https://github.com/wp-cli/scaffold-command/blob/402542fada9c17d45ffa644da1f82661c07643fd/templates/install-wp-tests.sh
if [ ! -d "$WP_TESTS_DIR" ]; then
    info "Downloading WP testing suite using svn"
    mkdir -p "$WP_TESTS_DIR"
    svn co --quiet "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/" "$WP_TESTS_DIR/includes"
    svn co --quiet "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/" "$WP_TESTS_DIR/data"
fi

if [ ! -f "$WP_TESTS_DIR/wp-tests-config.php" ]; then
    info "Creating test config"
    curl --silent "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php" > "$WP_TESTS_DIR/wp-tests-config.php"
    # remove all forward slashes in the end
    WP_CORE_DIR=$(echo "$WORDPRESS_BASE_DIR" | sed "s:/\+$::")
    sed -i "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s/youremptytestdbnamehere/$WORDPRESS_DATABASE_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s/yourusernamehere/$WORDPRESS_DATABASE_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s/yourpasswordhere//" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s|localhost|${WORDPRESS_DATABASE_HOST}|" "$WP_TESTS_DIR/wp-tests-config.php"
fi

## Create phpinfo file
info "Creating info.php file displaying phpinfo"
echo "<?php phpinfo();" > "$WORDPRESS_BASE_DIR/info.php"

## Set development constants
info "Setting wp-config.php development constants"
is_file_writable "$WORDPRESS_CONF_FILE" || chmod +w "$WORDPRESS_CONF_FILE"

wordpress_conf_set WP_DEBUG true yes
wordpress_conf_set SCRIPT_DEBUG true yes
wordpress_conf_set WP_ENVIRONMENT_TYPE "local"

wordpress_conf_set DISALLOW_FILE_EDIT true yes
wordpress_conf_set CONCATENATE_SCRIPTS false yes

is_file_writable "$WORDPRESS_CONF_FILE" && chmod -w "$WORDPRESS_CONF_FILE"

## Activates the newly copied plugin
info "Activating plugin and enabling dropin"
wp_execute plugin activate redis-cache
wp_execute redis update-dropin
wp_execute redis enable

# fixes httpd already running error
apache_stop
# Needed for bitnami image - needs to be the last command!
echo ""
exec "$@"
