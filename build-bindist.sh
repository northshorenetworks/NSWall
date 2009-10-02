#!/bin/sh

PROG_NAME=$(basename $0)
DEBUG_FLAG=FALSE

while getopts dm:p: OPTION
do
    case ${OPTION} in
        d) DEBUG_FLAG=TRUE;;
        m) MODULE=${OPTARG};;
        p) PLATFORM=${OPTARG};;
      \?) print -u2 "Usage: ${PROG_NAME} [ -d -m module_name -p platform_name ]"
           exit 2;;
    esac
done

DISTNAME=$PLATFORM
KERNCONF=PLATFORM/$DISTNAME/$DISTNAME
export TTYSPEED=19200
sudo chmod 777 flash-dist/etc/
sudo echo $DISTNAME > flash-dist/etc/platform
cp $KERNCONF $DISTNAME

if [ $DEBUG_FLAG = TRUE ]
then 
	export DEBUG_FLAG=TRUE
	export MODULE=$MODULE
	echo "Running build-kernel.sh $PLATFORM with debug on"
	./build-kernel.sh $PLATFORM TRUE 
	
else
	export DEBUG_FLAG=FALSE
	export MODULE=$MODULE
	echo "Running build-kernel.sh $PLATFORM"
	./build-kernel.sh $PLATFORM FALSE
fi
rm $DISTNAME

./build-diskimage.sh $DISTNAME 
gzip $DISTNAME 
DATETIME=`date "+%Y-%m-%d-%H:%M:%S"`
mv -f $DISTNAME.update ../../public_html/$DISTNAME.update.$DATETIME
mv -f $DISTNAME.gz ../../public_html/$DISTNAME.image.$DATETIME
