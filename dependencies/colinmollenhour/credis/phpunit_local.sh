#!/usr/bin/env bash

# This script runs unit tests locally in environment similar to Travis-CI
# It runs tests in different PHP versions with suitable PHPUnit version.
#
# You can see results of unit tests execution in console.
# Also all execution logs are saved to files phpunit_<date-time>.log
#
# Prerequisites for running unit tests on local machine:
#  - docker (modern version with compose built-in)
#
# You can find definition of all test environments in folder testenv/
# This folder is not automatically synced with .travis.yml
# If you add new PHP version to .travis.yml then you'll need to adjust files in testenv/

cd testenv

# build containers and run tests
docker compose build && docker compose up php-74 && docker compose up php-83

# save logs to log file
docker compose logs --no-color --timestamps | sort >"../phpunit_$(date '+%Y%m%d-%H%M%S').log"

# remove containers
docker compose rm -f
