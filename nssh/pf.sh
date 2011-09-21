#!/bin/sh

add_pfrule() {

    # example:
    # add_pfrule action N pass in on em0 proto tcp from \ 
    #     10.0.0.0/8 to any port 80

    count=0

    #
    # iterate through the arguments, assume $0 is the type
    # and $1 the order number
    #
    # type, in this case, is a generalization of what it is
    # for bigger-picture ordering
    #    * defaults - state options, scrub, etc
    #    * skip - skip rules
    #    * binat - binat rules
    #    * nat - nat rules, including port forwards and global
    #    * action - pass/ block rules
    #
    # the order number is a figure for ordering within types
    # the order number must be numeric or "N", where N means
    # that order does not matter
    #
    # multiple numbers with the same order number will be
    # grouped together in the order that they occur in 
    # nssh_pf.conf
    #
    for i in "$@"; do
        if [ $count == 0 ]; then
            nsrule="$i"
        elif [ $count == 1 ]; then
            checknum=`echo $i | tr -dc '[:digit:]'`
            if [[ $i == $checknum || $i == "N" ]]; then 
                nsrule="$nsrule $i"
            else
                echo "Invalid order number. Exiting."
                exit 1
            fi
        else
            pfrule="$pfrule $i"
        fi
        count=`expr "$count" + 1`
    done

    # strip $pfrule of the leading space then echo to a tmpfile
    pfrule=`echo $pfrule | sed 's/^\ //'`
    echo $pfrule > /tmp/$$.pfconf

    # check the syntax of the rule and add it to nssh_pf.conf
    # if it's valid
    /sbin/pfctl -nf /tmp/$$.pfconf
    if [ $? == 0 ]; then
        echo "$nsrule $pfrule" >> /var/run/nssh_pf.conf
    fi
    rm -f /tmp/$$.pfconf
}
