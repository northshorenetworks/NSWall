#!/bin/sh
#
# $Id: build-bindist.sh,v 1.5 2009/03/03 01:30:06 jrecords Exp $

DISTNAME=$1
KERNCONF=PLATFORM/$DISTNAME/$DISTNAME
export TTYSPEED=19200
sudo chmod 777 flash-dist/etc/
sudo echo $DISTNAME > flash-dist/etc/platform
cp $KERNCONF $DISTNAME
./build-kernel.sh ${DISTNAME}
rm $DISTNAME
./build-diskimage.sh $DISTNAME 
gzip $DISTNAME 
mv -f $DISTNAME.update Output/$DISTNAME.update
mv -f $DISTNAME.gz Output/$DISTNAME.image
