# From OpenBSD distrib/miniroot/

#	$OpenBSD: runlist.sh,v 1.3 1997/05/14 20:44:32 deraadt Exp $
#	$NetBSD: runlist.sh,v 1.1 1995/12/18 22:47:38 pk Exp $

SUBSTRING=`expr "$1" : '.*\(list.debug\)'`


if [ "${SUBSTRING}" = "list.debug" ]; then
	if [ "$DEBUG_FLAG" = "FALSE" ]; then	
		exit
	fi
fi

if [ "${SUBSTRING}" = "list.dnsserver" ]; then
        if [ "$MODULE" != "DNS-SERVER" ]; then
                exit
        fi
fi

if [ "X$1" = "X-d" ]; then
	SHELLCMD=cat
	shift
else
	SHELLCMD="sh -e"
fi

( while [ "X$1" != "X" ]; do
	cat $1
	shift
done ) | awk -f ${UTILS:-${CURDIR}}/list2sh.awk | ${SHELLCMD}
