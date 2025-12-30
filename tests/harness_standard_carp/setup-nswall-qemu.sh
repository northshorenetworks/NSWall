
#!/bin/sh
#
# stupid script to start multiple qemus on a single box

SUDO=/usr/bin/sudo
USER=jrecords

# qemu args
IMAGE=$1
MEMORY=128
FLAGS=" -nographic -serial telnet:127.0.0.1:1010,server,nowait -no-fd-bootchk"

NICFLAGS="-net nic,vlan=\$id -net tap,vlan=\$id,ifname=\$id,fd=\$fd"


start() {
        for id in 0 1 2; do
                fd=$(($id + 3))
                tun=tun$(($id))

                eval "nics=\"$nics $NICFLAGS\""
                fds="$fds $fd<> /dev/$tun"

        done
		

        cmd="${SUDO} -C 5 qemu -m ${MEMORY} -hda ${IMAGE}${FLAGS}$nics$fds"
	    echo Running: ${SUDO} sh -c "$cmd"
		${SUDO} sh -c "$cmd"
}

start $1
