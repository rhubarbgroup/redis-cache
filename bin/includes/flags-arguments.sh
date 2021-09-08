#/usr/bin/env bash
#
# Get command arguments
# Reference: https://stackoverflow.com/questions/7069682/how-to-get-arguments-with-flags-in-bash
#

for i in ${@}; do
    arguments[$index]=$i;
    prev_index="$(expr $index - 1)";

    # this if block does something akin to "where $i contains ="
    # "%=*" here strips out everything from the = to the end of the argument leaving only the label
    if [[ $i == *"="* ]]; then
        argument_label=${i%=*}
    else
        argument_label=${arguments[$prev_index]}
    fi

    # first argument and no label detected: must be mode then
    if [[ 1 == $index && -z $argument_label ]]; then
        argument_label="-m"
    fi

    if [[ -n $argument_label ]]; then
        # this if block only evaluates to true if the argument label exists in the variables array
        if [[ -n ${variables[$argument_label]} ]]; then
            # dynamically creating variables names using declare
            # "#$argument_label=" here strips out the label leaving only the value
            if [[ $i == *"="* ]]; then
                declare ${variables[$argument_label]}=${i#$argument_label=} 
            else
                declare ${variables[$argument_label]}=${arguments[$index]}
            fi
        else
        # if the argument was not found store it in order
        options+=(${arguments[$index]})
        fi
    else
    echo "unrecognized $1"
    fi

    index=index+1;
done;
