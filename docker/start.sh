#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# Shorthand function for docker-compose with the right yml file
function compose {
    docker-compose \
        -f "$( dirname $DIR )/docker-compose.yml" \
        ${@:1}
}

# Starts all containers and scales them accordingly
function start {
    compose \
        up -d \
        --scale redis-master=${1:-0} \
        --scale redis-slave=${2:-0} \
        --scale redis-sentinel=${3:-0} \
        ${@:4}
}

# Stops all containers
function stop {
    compose down ${@:2}
}

# Modifies the auto-prepend-file
function apf {
    echo "APF-Constant $@"
    ESCAPE='s/[]\/$*.^[]/\\&/g'
    # --reset
    if [[ '--reset' == $1 ]]; then
        apf WP_REDIS_HOST --remove
        apf WP_REDIS_CLIENT --remove
        apf WP_REDIS_SERVERS --remove
        apf WP_REDIS_SENTINEL --remove
        apf WP_REDIS_SHARDS --remove
        apf WP_REDIS_CLUSTER --remove
        return
    fi
    FILE="$DIR/apf.php"
    # Test if apf.php exists or if it is empty
    if [[ ! -f "$FILE" || ! -s "$FILE" ]]; then
        cp "$DIR/apf-template.php" "$FILE"
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
function dcip {
    echo $(compose exec --index="${2:-1}" "$1" hostname -i) | tr -d '\r'
}

# Restarts apache in the wordpress container
function restart_apache {
    echo "Restarting Apache"
    compose exec wordpress apachectl restart
}

# Get command arguments
# Reference https://stackoverflow.com/questions/7069682/how-to-get-arguments-with-flags-in-bash
declare -A arguments=();
declare -A variables=();
declare options=();
declare -i index=1;

variables["-c"]="client";
variables["--client"]="client";

for i in "$@"; do
  arguments[$index]=$i;
  prev_index="$(expr $index - 1)";

  # this if block does something akin to "where $i contains ="
  # "%=*" here strips out everything from the = to the end of the argument leaving only the label
  if [[ $i == *"="* ]]
    then argument_label=${i%=*}
    else argument_label=${arguments[$prev_index]}
  fi

  if [[ -n $argument_label ]] ; then
    # this if block only evaluates to true if the argument label exists in the variables array
    if [[ -n ${variables[$argument_label]} ]] ; then
      # dynamically creating variables names using declare
      # "#$argument_label=" here strips out the label leaving only the value
      if [[ $i == *"="* ]]
        then declare ${variables[$argument_label]}=${i#$argument_label=} 
        else declare ${variables[$argument_label]}=${arguments[$index]}
      fi
    else
      # if the argument was not found store it in order
      options+=(${arguments[$index]})
    fi
  fi

  index=index+1;
done;

case ${options[0]} in

    ""|"-"|"simple"|"default"|"up"|"start")
        start 1 0 0 ${options[@]:1}
        apf --reset
        apf WP_REDIS_CLIENT "${client-phpredis}"
        apf WP_REDIS_HOST redis-master
        restart_apache
        ;;

    "replication"|"repl")
        start 1 3 0 ${options[@]:1}
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
        start 1 5 3 ${options[@]:1}
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
        start 3 0 0 ${options[@]:1}
        apf --reset
        apf WP_REDIS_CLIENT "${client-predis}"
        apf WP_REDIS_SHARDS \
            tcp://$(dcip redis-master 1):6379?alias=shard-01 \
            tcp://$(dcip redis-master 2):6379?alias=shard-02 \
            tcp://$(dcip redis-master 3):6379?alias=shard-03
        restart_apache
        ;;

    "cluster"|"clustering")
        start 3 0 0 ${options[@]:1}
        apf WP_REDIS_CLIENT "${client-predis}"
        apf WP_REDIS_CLUSTER \
            tcp://$(dcip redis-master 1):6379?alias=node-01 \
            tcp://$(dcip redis-master 2):6379?alias=node-02 \
            tcp://$(dcip redis-master 3):6379?alias=node-03
        restart_apache
        ;;

    "stop"|"down")
        stop ${options[@]:0}
        apf --reset
        apf WP_REDIS_CLIENT "${client-phpredis}"
        apf WP_REDIS_HOST redis-master
        ;;

    *) echo "unrecognized command ${options[@]:1}"
        exit
        ;;

esac
