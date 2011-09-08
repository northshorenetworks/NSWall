#!/bin/sh

# (c) 2011 James Shupe <jshupe@osre.org>

# 
## make a hard to reverse, terribly obfuscated password from the 
## first mac address found in ifconfig output
#
break_pass=`ifconfig | grep lladdr | head -1 | cut -d ' ' -f 2 | sha1 | rev | sha1 | md5 | rev | sha1 | md5 | md5 | sha1 | sha1 | sed 's/a/z/g' | sed 's/b/ya/g' | sed 's/c/xb/g' | sed 's/d/wc/g' | sed 's/e/vd/g' | sed 's/f/ue/g' | sed 's/g/tf/g' | sed 's/h/sg/g' | sed 's/i/rh/g' | sha1 | awk '{ print substr($0,10,30) }' | rev | awk '{ print substr($0,4,36) }' | md5 | sha1`

#
## the following several lines rewrite commands.c with the proper define statement
## sed on OpenBSD sucks, Gnu sed could do this with two lines... oh well
#
break_pass_line=`grep -n "^#define EPASS" commands.c | cut -d : -f 1` # get line with #define EPASS...
sed '/^#define #PASS/d' commands.c > $$.TMP		# delete the existing #define EPASS...
f_len=`wc $$.TMP | awk '{ print $1 }'`			# get the file length
h_len=`expr $break_pass_line - 1`			# get the top of the file
t_len=`expr $f_len - $break_pass_line`			# get the bottom of the file
head -n $h_len $$.TMP > $$-1.TMP			# export the top to tempfile
tail -n $t_len $$.TMP > $$-2.TMP			# export the bottom to tempfile
echo "#define EPASS \"$break_pass\"" >> $$-1.TMP 	# add our break_pass to top tempfile
cat $$-2.TMP >> $$-1.TMP				# add our bottom after
rm $$.TMP $$-2.TMP					# remove the temp files
mv $$-1.TMP commands.c					# move the new file into place

echo "Break pass updated in commands.c."
exit 0
