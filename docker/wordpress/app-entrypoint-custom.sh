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
plugin_source_dir="/redis-cache"
plugin_target_dir="/opt/bitnami/wordpress/wp-content/plugins/redis-cache"
php_ini_path="/opt/bitnami/php/etc/php.ini"

## Symlink generation
ln -s "$plugin_source_dir" "$plugin_target_dir"

## Set APF file
cp "$php_ini_path" "$php_ini_path-original"
temp_file=$(mktemp)
awk '{gsub(/^auto_prepend_file =/,"& \"/apf.php\"",$0)}1' "$php_ini_path" \
    > "$temp_file" \
    && mv "$temp_file" "$php_ini_path"
info "Set PHP auto prepend file"

## Activates the newly copied plugin
info "Activating plugin and enabling dropin"
wp plugin activate redis-cache
wp redis update-dropin
wp redis enable

# Needed for bitnami image - needs to be the last command!
exec tini -- "$@"
