#!/usr/bin/env bash
#
# Utility script to run phpunit unit tests
#

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# Argument definitions
. "$DIR/includes/flags-declares.sh"
variables["-m"]="mode";
variables["--mode"]="mode";
variables["-f"]="force";
variables["--force"]="force";

# Load libraries
. "$DIR/includes/functions.sh"
. "$DIR/includes/flags-arguments.sh"

TESTSUITE=${mode-all}
ARG_TESTSUITE=""
CONTAINER_ID=$(container_info wordpress | awk '{print $1}')
XDEBUG_FILTER_PATH="/tmp/xdebug-filter.php"

[ "all" != $TESTSUITE ] && ARG_TESTSUITE="--testsuite $TESTSUITE"

# Create reports directory if missing.
[ ! -d "$BASE_DIR/reports" ] && mkdir -p "$BASE_DIR/reports"

# Try to create xdebug filter file.
docker-compose exec wordpress test -f "$XDEBUG_FILTER_PATH"
if [ $? -eq 1 ] || [ ! -n $force ]; then
    compose exec wordpress rm -rf "$XDEBUG_FILTER_PATH"
    compose exec wordpress phpunit -c "/redis-cache/phpunit.xml.dist" --dump-xdebug-filter "$XDEBUG_FILTER_PATH"
fi

# Use to provided object-cache.php instead of the one in `wp-content`
if compose exec wordpress test -f "/opt/bitnami/wordpress/wp-content/object-cache.php"; then
    echo "Object cache already installed - saving object-cache.php"
    compose exec wordpress cp "/opt/bitnami/wordpress/wp-content/object-cache.php" "/opt/bitnami/wordpress/wp-content/_object-cache.php"
fi
echo "Forcing WordPress to use the object-cache.php in the plugin's include directory"
compose exec wordpress bash -c 'echo "<?php require '\''/redis-cache/includes/object-cache.php'\'';" > "/opt/bitnami/wordpress/wp-content/object-cache.php"'

# Execute phpunit tests in the container.
compose exec wordpress \
    phpunit -c "/redis-cache/phpunit.xml.dist" --prepend "$XDEBUG_FILTER_PATH" $ARG_TESTSUITE

# Retrieve code coverage report.
docker cp $CONTAINER_ID:/tmp/codecov "$BASE_DIR/reports"

# Reset to the object-cache.php found before
if compose exec wordpress test -f "/opt/bitnami/wordpress/wp-content/_object-cache.php"; then
    echo "Resetting to installed object-cache.php"
    compose exec wordpress cp "/opt/bitnami/wordpress/wp-content/_object-cache.php" "/opt/bitnami/wordpress/wp-content/object-cache.php"
    compose exec wordpress rm "/opt/bitnami/wordpress/wp-content/_object-cache.php"
else
    echo "Resetting using object-cache.php"
    compose exec wordpress rm "/opt/bitnami/wordpress/wp-content/object-cache.php"
fi