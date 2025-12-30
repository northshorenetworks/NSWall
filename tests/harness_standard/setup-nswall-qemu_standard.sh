
#!/bin/sh
#
# stupid script to start multiple qemus on a single box

SUDO=/usr/bin/sudo
USER=jrecords

# qemu args
IMAGE=$1
MEMORY=128
FLAGS=" -daemonize -nographic -serial telnet:127.0.0.1:$((1010 + $2)),server,nowait -no-fd-bootchk"

NICFLAGS="-net nic,vlan=\$id,macaddr=\$mac -net tap,vlan=\$id,ifname=\$id,fd=\$fd"

getmac() {
	mac="00:bd:`printf %02x $(($RANDOM % 256))`:"
	mac="$mac`printf %02x $(($RANDOM % 256))`:"
	mac="$mac`printf %02x $(($1 % 256))`:`printf %02x $(($2 % 255 + 4))`"
}

start() {
        for id in 0 1 2; do
                fd=$(($id + 3))
                tun=tun$(($id + 10 + $2))
				getmac 3 id
                eval "nics=\"$nics $NICFLAGS\""
                fds="$fds $fd<> /dev/$tun"

        done
		

        cmd="${SUDO} -C 5 qemu -m ${MEMORY} -hda ${IMAGE}${FLAGS}$nics$fds"
	    echo Running: ${SUDO} sh -c "$cmd"
		${SUDO} sh -c "$cmd"
}

start $1 $2

telnet localhost $((1010 + $2))
