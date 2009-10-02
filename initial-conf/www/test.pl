#!/usr/bin/perl

use File::Find;
$dir = "/home/jrecords/project/nswall/initial-conf/www";

find(\&Kibo, $dir);

sub Kibo
	{
	    /\.php$/ or return;
	    open(ARTICLE, "+>", $_) or return;
	    my @lines = <ARTICLE>;
	
	    for my $line (@lines)
	    {
		if ($line =~ s/(plus.gif|e.gif|x.gif|down.gif)/images\/$1/) {
	     	   print "$line\n";
	    	}
	    }
	}

