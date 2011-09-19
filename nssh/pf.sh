#!/bin/sh

######
##
## I want to rework this, after thinking a little more about it.
## These checks should be called as individual functions, so...
## we need to do something like...
##
## if test "x$TYPE" == "x"; then
##     echo "Type not specified. Exiting."
##     exit 1
## else
##     RULE="$RULE`process_type $TYPE`
## fi
##
## and then have the next part in its own function, process_type:
##
## process_type() {
##     if [ "$TYPE" = "pass" ]; then
##         echo " pass"
##     elif [ "$TYPE" = "block" ]; then
##         echo " block"
##     else
##         echo "Rule type unknown. Exiting." > /dev/stderr
##         exit 1
##     fi
##     return 0
## }
##
## of course, the following is untested and may need modification
## in order to work
##
######

add_passblock() {
    TYPE=$1
    DIRECTION=$2
    LOG=$3
    QUICK=$4
    INTERFACE=$5
    IPVER=$6
    PROTO=$7
    SRC=$8
    DST=$9
    PORT=$10

    if test "x$TYPE" == "x"; then
        echo "Type not specified. Exiting."
        exit 1
    else
        if [ "$TYPE" = "pass" ]; then
            RULE="pass"
        elif [ "$TYPE" = "block" ]; then
            RULE="block"
        else
            echo "Rule type unknown. Exiting."
            exit 1
        fi
    fi

    if test "x$DIRECTION" == "x"; then
        echo "Direction not specified. Exiting."
        exit 1
    else
        if [ "$DIRECTION" = "in" ]; then
            RULE="$RULE in"
        elif [ "$DIRECTION" = "out" ]; then
            RULE="$RULE out"
        elif [ "$DIRECTION" = "both" ]; then
            # we don't need to add anything here
            # but ksh demands something be here
            test 1-1
        else
            echo "Rule type unknown. Exiting."
            exit 1
        fi
    fi

    if test "x$LOG" == "x"; then
        echo "Log bit not specified. Exiting."
        exit 1
    else
        if [ $LOG == 1 ]; then
            RULE="$RULE log"
        elif [ $LOG == 0 ]; then
            test 1-1
        else
            echo "Log bit malformed. Exiting."
            exit 1
        fi
    fi

    if test "x$QUICK" == "x"; then
        echo "Quick bit not specified. Exiting."
        exit 1
    else
        if [ $QUICK == 1 ]; then
            RULE="$RULE quick"
        elif [ $QUICK == 0 ]; then
            test 1-1
        else
            echo "Quick bit malformed. Exiting."
            exit 1
        fi
    fi

    if test "x$INTERFACE" == "x"; then
        echo "Interface not specified. Exiting."
        exit 1
    else
        # we need to check if the interface is set to any
        if [ "$INTERFACE" = "any" ]; then
            test 1-1
        else
            # we need to test if the interface actually exists
            /sbin/ifconfig $INTERFACE > /dev/null
            if [ $? == 0 ]; then
                RULE="$RULE on $INTERFACE"
            else 
                echo "Invalid interface specified. Exiting."
                exit 1
            fi
        fi
    fi

    if test "x$IPVER" == "x"; then
        echo "IP version not specified. Exiting."
        exit 1
    else
        if [ $IPVER == 4 ]; then
            RULE="$RULE inet"
        elif [ $QUICK == 6 ]; then
            RULE="$RULE inet6"
        else
            echo "IP version invalid. Exiting."
            exit 1
        fi
    fi

    if test "x$PROTO" == "x"; then
        echo "Protocol not specified. Exiting."
        exit 1
    else
        if [ "$PROTO" == "tcp" ]; then
            RULE="$RULE proto tcp"
        elif [ "$PROTO" == "udp" ]; then
            RULE="$RULE proto udp"
        else
            echo "Protocol must be tcp or udp. Exiting."
            exit 1
        fi
    fi


    echo $RULE
    return 0
}

add_passblock pass in 1 1 any 4 tcp 10.0.0.0/8 any 80
