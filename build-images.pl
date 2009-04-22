#!/usr/bin/perl

@platforms = ('FBIII', 'GENERIC', 'COMMELL-LE564', 'PCENGINES', 'SOEKRIS4501', 'SOEKRIS4521', 'SOEKRIS4801', 'SOEKRIS5501', 'WRAP12', 'VMWARE' );

foreach ( @platforms ) {
	system("sh /home/jrecords/Waffle/build-bindist.sh $_");
}
