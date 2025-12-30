
#!/bin/sh
#
# stupid script to start multiple qemus on a single box

SUDO=/usr/bin/sudo
USER=jrecords
MODE=ENABLE

usage() {
		echo "usage: $0 [-h harnesstype] [-d]" 1>&2
		exit 2
}

start() {
	echo MODE: $MODE
	if [ $MODE = "DISABLE" ]; then
		echo DeConfiguring ${HARNESS} Harness
        echo DeConfiguring tun0:
        echo ${SUDO} ifconfig tun0 destroy
        ${SUDO} ifconfig tun0 destroy
        echo DeConfiguring tun1:
        echo ${SUDO} ifconfig tun1 destroy
        ${SUDO} ifconfig tun1 destroy
        echo DeConfiguring tun2:
        echo ${SUDO} ifconfig tun2 destroy
        ${SUDO} ifconfig tun2 destroy
	else
		# make sure a tun interface is available
		echo Configuring ${HARNESS} Harness 
    	echo Configuring tun0:
		echo ${SUDO} ifconfig tun0 192.168.254.254 link0
		${SUDO} ifconfig tun0 192.168.254.254 link0			
		echo Configuring tun1:
		echo ${SUDO} ifconfig tun1 192.168.1.254 link0
		${SUDO} ifconfig tun1 192.168.1.254 link0
		echo Configuring tun2:
		echo ${SUDO} ifconfig tun2 192.168.253.254 link0
		${SUDO} ifconfig tun2 192.168.253.254 link0
	fi
}

args=`getopt d $*`

if [ $? -ne 0 ]; then
	usage
fi
	set -- $args
	while [ $# -gt 0 ]; do
		case "$1" in
		-d) MODE="DISABLE"
		echo "Disable Mode"
		;;
	    --) shift;
		break
		;;
		esac
		shift
	done

		if [ $# -ne 1 ]; then
			usage
		fi

start $1
