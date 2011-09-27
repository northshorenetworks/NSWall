#!/bin/sh

. /usr/bin/pf.subr

gen_pffile

/sbin/pfctl -f /var/run/pf.conf
