#!/bin/sh

NS_PKG_PATH="/home/jrecords/projects/NSWall/packages/"

for i in `ls $NS_PKG_PATH`; do
   NS_PKG_F_NAME=`echo $i | sed 's/\.tgz//'` 			 # full name
   NS_PKG_A_NAME=`echo $NS_PKG_F_NAME | sed 's/-[0-9]\.[0-9]//'` # actual name
   NS_PKG_VER=`echo $NS_PKG_F_NAME | sed "s/$NS_PKG_A_NAME//"`
   NS_PKG_VER=`echo $NS_PKG_VER | sed 's/-//'`			 # version

   echo "Creating package for: $NS_PKG_A_NAME version $NS_PKG_VER"

   echo "Creating staging area at /usr/tmp/$NS_PKG_F_NAME to analyze distfile..."
   rm -rf /usr/tmp/$NS_PKG_F_NAME
   mkdir -p /usr/tmp/$NS_PKG_F_NAME

   echo "Copying source file $NS_PKG_PATH$NS_PKG_F_NAME.tgz to /usr/tmp/$NS_PKG_F_NAME/"
   cp $NS_PKG_PATH$NS_PKG_F_NAME.tgz /usr/tmp/$NS_PKG_F_NAME/

   echo "Extracting /usr/tmp/$NS_PKG_F_NAME/$NS_PKG_F_NAME.tgz"
   cd /usr/tmp/$NS_PKG_F_NAME
   tar -zxf $NS_PKG_F_NAME.tgz

   echo "Removing source file /usr/tmp/$NS_PKG_F_NAME/$NS_PKG_F_NAME.tgz"
   rm -f /usr/tmp/$NS_PKG_F_NAME/$NS_PKG_F_NAME.tgz

   echo "Preparing a list of subdirs in /usr/tmp/$NS_PKG_F_NAME"
   DIRLIST=`find . -type d | sed 's/^\.\///' | sed '/^\./d' | tr '\n' ' '`

   echo "Removing staging area /usr/tmp/$NS_PKG_F_NAME/"
   rm -rf /usr/tmp/$NS_PKG_F_NAME

   cd /usr/ports/nss

   echo "Removing old project directory: $NS_PKG_A_NAME"
   rm -rf $NS_PKG_A_NAME
   echo "Creating project directory: $NS_PKG_A_NAME"
   mkdir $NS_PKG_A_NAME

   echo "Creating $NS_PKG_A_NAME/Makefile"
   # this can't be indented

cat >> $NS_PKG_A_NAME/Makefile << EOF
COMMENT=		$NS_PKG_A_NAME
DISTNAME=		$NS_PKG_F_NAME
REVISON=		$NS_PKG_VER
CATEGORIES=		nss
HOMEPAGE=		http://www.northshoresoftware.com
MAINTAINER=		James Shupe <j@jamesshupe.com>

PREFIX=			

MASTER_SITES=		http://www.jamesshupe.com/openbsd/ports/
EXTRACT_SUFX=		.tgz

PERMIT_PACKAGE_CDROM=	NO
PERMIT_PACKAGE_FTP=	NO
PERMIT_DISTFILES_CDROM= NO
PERMIT_DISTFILES_FTP=	NO

DIRLIST=		$DIRLIST

NO_BUILD=		YES
NO_REGRESS=		YES
PKG_ARCH=		*

do-install:
	touch /tmp/.empty-ignoreme
.for i in \${DIRLIST}
	\${INSTALL_DATA_DIR} \${PREFIX}/\$i
	\${INSTALL_SCRIPT} \`find \${WRKDIST}/../\$i -maxdepth 1 -type f && echo /tmp/.empty-ignoreme\` \${PREFIX}/\$i
	rm -f \${PREFIX}/\$i/.empty-ignoreme
.endfor

.include <bsd.port.mk>
EOF

   echo "Creating $NS_PKG_A_NAME/pkg"
   mkdir $NS_PKG_A_NAME/pkg
   
   echo "Creating $NS_PKG_A_NAME/pkg/DESCR"
   echo $NS_PKG_A_NAME > $NS_PKG_A_NAME/pkg/DESCR

   if [ -e /usr/ports/distfiles/$NS_PKG_F_NAME.tgz ]; then
       echo "Removing old distfile: /usr/ports/distfiles/$NS_PKG_F_NAME.tgz"
       rm -f /usr/ports/distfiles/$NS_PKG_F_NAME.tgz
   fi
   echo "Copying distfile $NS_PKG_PATH$NS_PKG_F_NAME.tgz to /usr/ports/distfiles"
   cp $NS_PKG_PATH$NS_PKG_F_NAME.tgz /usr/ports/distfiles/$NS_PKG_F_NAME.tgz
   
   cd $NS_PKG_A_NAME
   echo "Creating $NS_PKG_A_NAME/distinfo"
   make makesum

   echo "Creating $NS_PKG_A_NAME/pkg/PLIST"
   make plist

   echo "Creating package for $NS_PKG_A_NAME"
   make PREFIX=/ package

   echo "Copying package to /usr/packages"
   if [ ! -d /usr/packages ]; then
       mkdir /usr/packages
   fi
   cp /usr/ports/packages/no-arch/$i /usr/packages/

   cd ..
   echo
done
