#!/bin/sh
#
# $Id: build-kernel.sh,v 1.4 2009/03/02 21:36:54 jrecords Exp $

BASE=`pwd`
SRCDIR=${BSDSRCDIR:-/usr/src}
DESTDIR=${DESTDIR:-${BASE}/flash-dist}
SUDO=sudo
DISKTAB=disktab.20mb
NBLKS=40960
export DEBUG_FLAG=$2

export SRCDIR DESTDIR BINDIR SUDO DEBUG_FLAG

# Don't start without a kernel as a parameter
if [ "$1" = "" ]; then
  echo "usage: $0 kernel"
  exit 1
fi

# Does the kernel exist at all
if [ ! -r $1 ]; then
  echo "ERROR! $1 does not exist or is not readable."
  exit 1
fi

# Create dir if not there
mkdir -p obj

# Which kernel to use?
export KERNEL=$1

# Create the kernelfile (with increased MINIROOTSIZE)

# Cleanup just in case the previous build failed
${SUDO} umount /mnt/ 
${SUDO} vnconfig -u vnd0
make KCONF=${KERNEL} clean

# Make kernel
make KCONF=${KERNEL} DEBUG_FLAG=${DEBUG_FLAG} $3 $4

# Done
echo "Your kernel is stored here ${BASE}/obj/"
