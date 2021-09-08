#!/usr/bin/env bash
#
# Utility functions
#

_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
BASE_DIR="$( dirname $( dirname "$_DIR" ))"
SRC_DIR="$BASE_DIR"
DOCKER_DIR="$BASE_DIR/docker"

# Shorthand function for docker-compose with the right yml file
compose() {
    docker-compose \
        -f "$BASE_DIR/docker-compose.yml" \
        "${@:1}"
}

# Starts all containers and scales them accordingly
start() {
    compose \
        up -d \
        --scale redis-master="${1:-0}" \
        --scale redis-slave="${2:-0}" \
        --scale redis-sentinel="${3:-0}" \
        "${@:4}"
}

# Stops all containers
stop() {
    compose down "${@}"
}

container_info() {
    docker ps -a --no-trunc \
        --format '{{ .ID }}\t{{ .Names }}\t{{ .State }}\tp:{{ .Label "com.docker.compose.project" }}' \
        | grep 'p:redis-cache' \
        | grep "$1"
}

# Modifies the auto-prepend-file
apf() {
    echo "APF-Constant $@"
    ESCAPE='s/[]\/$*.^[]/\\&/g'
    # --reset
    if [[ '--reset' == "$1" ]]; then
        apf WP_REDIS_HOST --remove
        apf WP_REDIS_CLIENT --remove
        apf WP_REDIS_SERVERS --remove
        apf WP_REDIS_SENTINEL --remove
        apf WP_REDIS_SHARDS --remove
        apf WP_REDIS_CLUSTER --remove
        return
    fi
    FILE="$DOCKER_DIR/apf.php"
    # Test if apf.php exists or if it is empty
    if [[ ! -f "$FILE" || ! -s "$FILE" ]]; then
        cp "$DOCKER_DIR/apf-template.php" "$FILE"
    fi
    TMP1=$(mktemp)
    CONST=$(echo "'$1'" | sed "$ESCAPE")
    sed -n "/$CONST/!p" "$FILE" > "$TMP1"
    if [[ '--remove' != "$2" ]]; then
        TMP2=$(mktemp)
        if [ -n "$3" ]; then
            VALUE="["
            for var in "${@:2}"; do
                VALUE="$VALUE'$var',"
            done
            VALUE="$VALUE]"
        else
            VALUE="'$2'"
        fi
        VALUE=$(echo "$VALUE" | sed "$ESCAPE")
        INSAFTER=$(echo "// constant-definition end" | sed "$ESCAPE")
        # Add constructed line before the insertion indicator end comment
        REPL=$(printf '    %s => %s,' "$CONST" "$VALUE")
        sed 's/^[ ]*'"$INSAFTER"'.*$/'"$REPL"'\'$'\n&/g' \
            "$TMP1" > "$TMP2"
        mv "$TMP2" "$TMP1"
    fi
    mv "$TMP1" "$FILE"
}

# Retrieves the IP of a docker container using its name and optionally its index
dcip() {
    declare -i counter=1
    declare -i max_counter=25
    container_name="$1_${2:-1}"
    while [ $counter -le $max_counter ]; do
        container_state=$(container_info $container_name | awk '{print $3}')
        if [ "running" = $container_state ]; then
            break
        fi
        sleep 0.1s
        ((counter++))
    done
    if [ $counter -lt $max_counter ]; then
        echo $(compose exec --index="${2:-1}" "$1" hostname -i) | tr -d '\r'
    else
        echo "$container_name"
    fi
}

# Restarts apache in the wordpress container
restart_apache() {
    echo "Restarting Apache"
    compose exec wordpress apachectl restart
}
