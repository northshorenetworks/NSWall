#!/bin/sh
# incBuild.sh

[ ! -e initial-conf/BUILD_STAMP ] && touch initial-conf/BUILD_STAMP
x=`cat initial-conf/BUILD_STAMP`
[ "$x" = "" ] && x=1000
x=`expr $x + 1`
echo $x > initial-conf/BUILD_STAMP
echo $x
