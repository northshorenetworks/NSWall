
#!/bin/sh
#
# stupid script to start multiple qemus on a single box

SUDO=/usr/bin/sudo
USER=jrecords
MODE=ENABLE

usage() {
		echo "usage: $0 [-d]" 1>&2
		exit 2
}

start() {
	echo MODE: $MODE
	if [ $MODE = "DISABLE" ]; then
		echo DeConfiguring ${HARNESS} Harness
        echo DeConfiguring tun10:
        echo ${SUDO} ifconfig tun10 destroy
        ${SUDO} ifconfig tun10 destroy
        echo DeConfiguring tun11:
        echo ${SUDO} ifconfig tun11 destroy
        ${SUDO} ifconfig tun11 destroy
        echo DeConfiguring tun12:
        echo ${SUDO} ifconfig tun12 destroy
        ${SUDO} ifconfig tun12 destroy
		echo DeConfiguring tun13:
        echo ${SUDO} ifconfig tun13 destroy
        ${SUDO} ifconfig tun13 destroy
		echo DeConfiguring tun14:
        echo ${SUDO} ifconfig tun14 destroy
        ${SUDO} ifconfig tun14 destroy
		echo DeConfiguring tun15:
        echo ${SUDO} ifconfig tun15 destroy
        ${SUDO} ifconfig tun15 destroy
		echo DeConfiguring bridge0:
        echo ${SUDO} ifconfig bridge0 destroy
        ${SUDO} ifconfig bridge0 destroy
		echo DeConfiguring bridge1:
        echo ${SUDO} ifconfig bridge1 destroy
        ${SUDO} ifconfig bridge1 destroy
		echo DeConfiguring bridge2:
        echo ${SUDO} ifconfig bridge2 destroy
        ${SUDO} ifconfig bridge2 destroy
		echo Killing DHCPD process
		${SUDO} pkill dhcpd
	else
		# make sure a tun interface is available
		echo Configuring ${HARNESS} Harness 
    	echo Configuring tun10:
		echo ${SUDO} ifconfig tun10 192.168.254.254 link0
		${SUDO} ifconfig tun10 192.168.1.254 link0			
		echo Configuring tun11:
		echo ${SUDO} ifconfig tun11 192.168.1.254 link0
		${SUDO} ifconfig tun11 192.168.254.254 link0
		echo Configuring tun12:
		echo ${SUDO} ifconfig tun12 192.168.253.254 link0
		${SUDO} ifconfig tun12 192.168.253.254 link0
		echo Configuring tun13:
		echo ${SUDO} ifconfig tun13 link0
		${SUDO} ifconfig tun13 link0 up
		echo Configuring bridge0:
        echo ${SUDO} ifconfig bridge0 create
		${SUDO} ifconfig bridge0 create
		echo ${SUDO} brconfig bridge0 add tun10 add tun13 up
		${SUDO} brconfig bridge0 add tun10 add tun13 up 
		echo Configuring tun14:
		echo ${SUDO} ifconfig tun14 link0
		${SUDO} ifconfig tun14 link0 up
		echo Configuring bridge1:
        echo ${SUDO} ifconfig bridge1 create
        ${SUDO} ifconfig bridge1 create
        echo ${SUDO} brconfig bridge1 add tun11 add tun14 up
        ${SUDO} brconfig bridge1 add tun11 add tun14 up
		echo Configuring tun15:
        echo ${SUDO} ifconfig tun15 link0	
		${SUDO} ifconfig tun15 link0 up
		echo Configuring bridge2:
        echo ${SUDO} ifconfig bridge2 create
        ${SUDO} ifconfig bridge2 create
        echo ${SUDO} brconfig bridge2 add tun12 add tun15 up
        ${SUDO} brconfig bridge2 add tun12 add tun15 up
		echo Starting DHCPD
		${SUDO} /usr/sbin/dhcpd
	fi
}

args=`getopt d $*`

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

start $1
