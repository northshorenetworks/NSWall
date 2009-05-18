
#
# $Id: build-diskimage.sh,v 1.27 2009/03/02 21:36:54 jrecords Exp $

BASE=`pwd`
SRCDIR=${BSDSRCDIR:-/usr/src}
DESTDIR=${DESTDIR:-${BASE}/flash-dist}
KERNELFILE=${KERNELFILE:-${BASE}/obj/bsd.gz}
SUDO=sudo
DEVICE=svnd0
MOUNTPOINT=/mnt
TEMPFILE=/tmp/build-diskimage.tmp.$$

# drive geometry information -- get the right one for your flash!!

# 128 MB cards
totalsize=250880       # "total sectors:"
bytessec=512           # "bytes/sector:"
sectorstrack=32        # "sectors/track:"
sectorscylinder=256    # "sectors/cylinder:"
trackscylinder=16       # "tracks/cylinder:"
cylinders=980          # "cylinders:"

# Don't start without a imagefile as a parameter
if [ "$1" = "" ]; then
  echo "usage: $0 imagefile"
  exit 1	
fi

IMAGEFILE=$1

# Does the kernel exist at all
if [ ! -r $KERNELFILE ]; then
  echo "ERROR! $KERNELFILE does not exist or is not readable."
  exit 1
fi

echo "Cleanup if something failed the last time... (ignore any not currently mounted and Device not configured warnings)"
${SUDO} umount $MOUNTPOINT
${SUDO} vnconfig -u $DEVICE

echo ""
echo "Creating an image file, if one doesn't exist..."
if [ ! -f $IMAGEFILE ] ; then
  dd if=/dev/zero of=$IMAGEFILE bs=$bytessec count=$totalsize
fi

echo ""
echo "Mounting the imagefile as a device..."
${SUDO} vnconfig -c $DEVICE $IMAGEFILE

echo ""
echo "Running fdisk... (Ignore any sysctl(machdep.bios.diskinfo): Device not configured warnings)"
${SUDO} fdisk -c $cylinders -h $trackscylinder -s $sectorstrack -f ${DESTDIR}/usr/mdec/mbr -e $DEVICE << __EOC >/dev/null
reinit
update
write
quit
__EOC

let asize=$totalsize-$sectorstrack

echo "type: SCSI" >> $TEMPFILE
echo "disk: vnd device" >> $TEMPFILE
echo "label: fictitious" >> $TEMPFILE
echo "flags:" >> $TEMPFILE
echo "bytes/sector: ${bytessec}" >> $TEMPFILE
echo "sectors/track: ${sectorstrack}" >> $TEMPFILE
echo "tracks/cylinder: ${trackscylinder}" >> $TEMPFILE
echo "sectors/cylinder: ${sectorscylinder}" >> $TEMPFILE
echo "cylinders: ${cylinders}" >> $TEMPFILE
echo "total sectors: ${totalsize}" >> $TEMPFILE
echo "rpm: 3600" >> $TEMPFILE
echo "interleave: 1" >> $TEMPFILE
echo "trackskew: 0" >> $TEMPFILE
echo "cylinderskew: 0" >> $TEMPFILE
echo "headswitch: 0           " >> $TEMPFILE
echo "track-to-track seek: 0  " >> $TEMPFILE
echo "drivedata: 0 " >> $TEMPFILE
echo "" >> $TEMPFILE
echo "16 partitions:" >> $TEMPFILE
echo "a:        31715   $sectorstrack   4.2BSD  2048    16384   16" >> $TEMPFILE
echo "c:        $totalsize      0       unused  0       0" >> $TEMPFILE
echo "d:        11000   32000           4.2BSD  2048    16384   16" >> $TEMPFILE
echo "e:        17200   43500           4.2BSD  2048    16384   16" >> $TEMPFILE
#echo "f:        430000  52500           4.2BSD  2048    16384   16" >> $TEMPFILE
echo ""
echo "Installing disklabel..."
${SUDO} disklabel -v -R $DEVICE $TEMPFILE
rm $TEMPFILE

echo ""
echo "Creating new filesystem..."
${SUDO} newfs -q /dev/r${DEVICE}a
${SUDO} newfs -q /dev/r${DEVICE}d
${SUDO} newfs -q /dev/r${DEVICE}e

echo ""
echo "Mounting destination to ${MOUNTPOINT}..."
if ! ${SUDO} mount -o async /dev/${DEVICE}a ${MOUNTPOINT}; then
  echo Mount failed..
  exit
fi
echo ""
echo "Copying bsd kernel, and boot blocks..."
${SUDO} cp ${DESTDIR}/usr/mdec/boot ${MOUNTPOINT}/boot
${SUDO} cp ${KERNELFILE} ${MOUNTPOINT}/bsd
${SUDO} mkdir ${MOUNTPOINT}/etc

if [ ! $1 = "VMWARE" ]; then
${SUDO} chmod 777 /mnt/etc/ 
${SUDO} echo "stty com0 9600" >> ${MOUNTPOINT}/etc/boot.conf
${SUDO} echo "set tty com0" >> ${MOUNTPOINT}/etc/boot.conf
fi
echo ""
echo "Installing boot blocks..."
${SUDO} /usr/mdec/installboot ${MOUNTPOINT}/boot ${DESTDIR}/usr/mdec/biosboot ${DEVICE}

echo ""
echo "Saving System Image file as $IMAGEFILE.update"
${SUDO} umount $MOUNTPOINT
${SUDO} dd if=/dev/${DEVICE}a of=${BASE}/$IMAGEFILE.tmp
${SUDO} gzip -c9 -S \"\" ${BASE}/$IMAGEFILE.tmp > ${BASE}/$IMAGEFILE.update
${SUDO} rm ${BASE}/$IMAGEFILE.tmp
echo ""
echo "Signing System Image file"
${SUDO} gzsig sign /${BASE}/misc/id_rsa ${BASE}/$IMAGEFILE.update
echo ""
echo "Mounting Config Partition to ${MOUNTPOINT}..."
if ! ${SUDO} mount -o async /dev/${DEVICE}d ${MOUNTPOINT}; then
  echo Mount failed..
  exit
fi
echo ""
echo "Copying default config from platform to conf partition..."
# Here is where you add your own packages and configuration to the flash...
${SUDO} touch ${MOUNTPOINT}/set_wizard_initial
${SUDO} cp ${BASE}/initial-conf/config.xml ${MOUNTPOINT}/config.xml
${SUDO} mkdir ${MOUNTPOINT}/ssh

echo ""
echo "Unmounting and Config Partition..."
${SUDO} umount $MOUNTPOINT
${SUDO} vnconfig -u $DEVICE

echo ""
echo "And we are done..."
echo "Run \"mountimage.sh $IMAGEFILE\" to add configuration and packages."
echo "When you are done with the configuration, gzip the imagefile and move"
echo "it to the system with a flashwriter."
echo "Use \"gunzip -c image.gz | dd of=/dev/sd0c\" on unix to write to flash"
echo "On Windows you can use http://m0n0.ch/wall/physdiskwrite.php"
echo "Both these utilities allow the gzipped image to be used directly."
