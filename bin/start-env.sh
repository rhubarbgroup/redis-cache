#!/usr/bin/env bash
#
# Utility script to manage the docker dev environments
#
# Documentation:
# https://github.com/rhubarbgroup/redis-cache/wiki/Docker-Development
#

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# Argument definitions
. "$DIR/includes/flags-declares.sh"
variables["-m"]="mode";
variables["--mode"]="mode";
variables["-c"]="client";
variables["--client"]="client";

# Load libraries
. "$DIR/includes/functions.sh"
. "$DIR/includes/flags-arguments.sh"

case ${mode-default} in

    "default"|"-"|"simple"|"up"|"start")
        start 1 0 0 "${options[@]}"
        apf --reset
        apf WP_REDIS_CLIENT "${client-phpredis}"
        apf WP_REDIS_HOST redis-master
        restart_apache
        ;;

    "replication"|"repl")
        start 1 3 0 "${options[@]}"
        apf --reset
        apf WP_REDIS_CLIENT "${client-phpredis}"
        apf WP_REDIS_SERVERS \
            tcp://$(dcip redis-master):6379?alias=master \
            tcp://$(dcip redis-slave 1):6379?alias=slave-01 \
            tcp://$(dcip redis-slave 2):6379?alias=slave-02 \
            tcp://$(dcip redis-slave 3):6379?alias=slave-03
        restart_apache
        ;;

    "sentinel"|"sent")
        start 1 5 3 "${options[@]}"
        apf --reset
        apf WP_REDIS_CLIENT "${client-predis}"
        apf WP_REDIS_SENTINEL master
        apf WP_REDIS_SERVERS \
            tcp://$(dcip redis-sentinel 1):26379 \
            tcp://$(dcip redis-sentinel 2):26379 \
            tcp://$(dcip redis-sentinel 3):26379
        restart_apache
        ;;

    "shard"|"sharding")
        start 3 0 0 "${options[@]}"
        apf --reset
        apf WP_REDIS_CLIENT "${client-predis}"
        apf WP_REDIS_SHARDS \
            tcp://$(dcip redis-master 1):6379?alias=shard-01 \
            tcp://$(dcip redis-master 2):6379?alias=shard-02 \
            tcp://$(dcip redis-master 3):6379?alias=shard-03
        restart_apache
        ;;

    "cluster"|"clustering")
        start 3 0 0 "${options[@]}"
        apf WP_REDIS_CLIENT "${client-predis}"
        apf WP_REDIS_CLUSTER \
            tcp://$(dcip redis-master 1):6379?alias=node-01 \
            tcp://$(dcip redis-master 2):6379?alias=node-02 \
            tcp://$(dcip redis-master 3):6379?alias=node-03
        restart_apache
        ;;

    "stop"|"down")
        stop "${options[@]}"
        apf --reset
        apf WP_REDIS_CLIENT "${client-phpredis}"
        apf WP_REDIS_HOST redis-master
        ;;

    *) echo "unrecognized command $mode"
        exit
        ;;

esac
