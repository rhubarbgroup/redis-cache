#! /usr/bin/env sh

set -e

WP_DEV_GIT=https://github.com/WordPress/wordpress-develop
WP_TESTS_DIR=.wp-test

if [ -a config.sh ]
then
    echo " - Sourcing your test environment variables"
    source "config.sh"
fi

GIT_BRANCH=${GIT_BRANCH:=trunk}

if [[ ! -d $WP_TESTS_DIR ]]; then
    echo " - Cloning wordpress develop"
    git clone --depth 1 --quiet --branch $GIT_BRANCH $WP_DEV_GIT $WP_TESTS_DIR
fi


echo " - Creating the config file"
cp $WP_TESTS_DIR/wp-tests-config-sample.php $WP_TESTS_DIR/wp-tests-config.php




# portable in-place argument for both GNU sed and Mac OSX sed
if [[ $(uname -s) == 'Darwin' ]]; then
    ioption='-i.bak'
else
    ioption='-i'
fi

DB_HOST=${DB_HOST:=127.0.0.1}
DB_PASS=${DB_PASS:=""}
DB_NAME=${DB_NAME:="wp_tests"}
DB_USER=${DB_USER:="root"}

echo " - Setting up the database connection values"

sed $ioption "s/youremptytestdbnamehere/${DB_NAME}/" ${WP_TESTS_DIR}/wp-tests-config.php
sed $ioption "s/yourusernamehere/${DB_USER}/" ${WP_TESTS_DIR}/wp-tests-config.php
sed $ioption "s/yourpasswordhere/${DB_PASS}/" ${WP_TESTS_DIR}/wp-tests-config.php
sed $ioption "s|localhost|${DB_HOST}|" ${WP_TESTS_DIR}/wp-tests-config.php


echo " - We are done ✅  \n"
