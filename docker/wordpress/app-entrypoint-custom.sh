#!/bin/bash -e

. /opt/bitnami/base/functions
. /opt/bitnami/base/helpers

print_welcome_page

if [[ "$1" == "nami" && "$2" == "start" ]] || [[ "$1" == "httpd" ]]; then
    . /apache-init.sh
    . /wordpress-init.sh
    nami_initialize apache mysql-client wordpress
    info "Starting gosu... "
    . /post-init.sh
fi

# Additional custom actions
PLUGIN_SOURCE_DIR="/redis-cache"
PLUGIN_TARGET_DIR="/opt/bitnami/wordpress/wp-content/plugins/redis-cache"
PHP_INI_PATH="/opt/bitnami/php/etc/php.ini"

## Symlink generation
if [ ! -L "$PLUGIN_TARGET_DIR" ]; then
    ln -s "$PLUGIN_SOURCE_DIR" "$PLUGIN_TARGET_DIR"
fi

## Set APF file
if [ -f "/redis-cache/docker/apf.php" ]; then
    cp "$PHP_INI_PATH" "$PHP_INI_PATH-original"
    TF=$(mktemp)
    awk '{gsub(/^(auto_prepend_file\s*=\s*).+/,"& \"/redis-cache/docker/apf.php\"",$1)}1' "$PHP_INI_PATH" \
        > "$TF" \
        && mv "$TF" "$PHP_INI_PATH"
    info "Set PHP auto prepend file"
else
    error "Unable to set PHP auto prepend file"
    ls -lah /redis-cache/docker | grep 'apf.php'
fi

## Activates the newly copied plugin
info "Activating plugin and enabling dropin"
wp plugin install --activate \
    query-monitor
wp plugin activate redis-cache
wp redis update-dropin
wp redis enable

# Needed for bitnami image - needs to be the last command!
exec tini -- "$@"
