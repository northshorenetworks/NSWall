#!/bin/sh
# $nsh: save-ro.sh,v 1.3 2003/06/20 16:44:31 chris Exp $
#
# This script is called by NSH when it wants to permanently save the
# configuration to disk/flash.  This script can be modified by the user
# to do more if necessary.
#

if [ ! -f "$1" ]; then
  echo save.sh: not found: $1
  exit
fi

umount -f /conf
mount -w -o noatime /conf
cp $1 /conf/nssh.conf
#cp /var/run/pf.conf /etc/pf.conf
sync
umount -f /conf
mount -r /conf 
