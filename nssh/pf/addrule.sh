#!/bin/sh

. /usr/bin/pf.subr

add_pfrule $@

gen_pffile
