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
. /opt/bitnami/scripts/libapache.sh
# Load Apache environment
. /opt/bitnami/scripts/apache-env.sh

# Additional custom actions
PLUGIN_SOURCE_DIR="/redis-cache"
PLUGIN_TARGET_DIR="/opt/bitnami/wordpress/wp-content/plugins/redis-cache"
PHP_INI_PATH="/opt/bitnami/php/etc/php.ini"
APF_FILE_PATH="/redis-cache/docker/apf.php"

## Symlink generation
info "Creating plugin symlink"
if [ ! -L "$PLUGIN_TARGET_DIR" ]; then
    ln -s "$PLUGIN_SOURCE_DIR" "$PLUGIN_TARGET_DIR"
fi

## Set APF file
if [ -f "$APF_FILE_PATH" ]; then
    cp "$PHP_INI_PATH" "$PHP_INI_PATH-original"
    TF=$(mktemp)
    awk '{gsub(/auto_prepend_file ?=.*$/,"auto_prepend_file = '"$APF_FILE_PATH"'",$0)}1' "$PHP_INI_PATH" \
        > "$TF" \
        && mv "$TF" "$PHP_INI_PATH"
    info "Set PHP auto prepend file"
else
    error "Unable to set PHP auto prepend file"
    ls -lah $(dirname "$APF_FILE_PATH") | grep $(basename "$APF_FILE_PATH")
fi

## Create phpinfo file
info "Creating info.php file displaying phpinfo"
echo "<?php phpinfo();" > "/$WP_DIR/info.php"

## Set development constants
info "Setting wp-config.php development constants"
chmod +w "/$WP_DIR/wp-config.php"

wp config set WP_DEBUG true --raw
wp config set SCRIPT_DEBUG true --raw
wp config set WP_ENVIRONMENT_TYPE "local"

wp config set DISALLOW_FILE_EDIT true --raw
wp config set CONCATENATE_SCRIPTS false --raw

chmod -w "/$WP_DIR/wp-config.php"

## Activates the newly copied plugin
info "Activating plugin and enabling dropin"
wp plugin activate redis-cache
wp redis update-dropin
wp redis enable

# fixes httpd already running error
if is_apache_running; then
    apache_stop
fi
# Needed for bitnami image - needs to be the last command!
echo ""
exec "$@"
