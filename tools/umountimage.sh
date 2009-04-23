#!/bin/sh
#
# $Id: umountimage.sh,v 1.1.1.1 2008/08/01 07:56:20 root Exp $

BASE=`pwd`
MOUNTPOINT=/mnt
DEVICE=svnd0
SUDO=sudo

echo "Umounting device and mountpoint..."
${SUDO} umount $MOUNTPOINT 
${SUDO} vnconfig -u $DEVICE

