#!/bin/sh

. /usr/bin/pf.subr

del_pfrule $@

/usr/bin/pfreload
