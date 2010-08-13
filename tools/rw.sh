#!/bin/sh
#
# $Id: rw.sh,v 1.1.1.1 2008/08/01 07:56:20 root Exp $

umount -f /conf
mount -w -o noatime /conf
