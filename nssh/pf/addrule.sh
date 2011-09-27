#!/bin/sh

. /usr/bin/pf.subr

add_pfrule $@

/usr/bin/pfreload
