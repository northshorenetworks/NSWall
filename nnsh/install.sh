#!/bin/sh

# (c) 2011 James Shupe <jshupe@osre.org>

install nsh /usr/local/bin/nsh

grep ^/usr/local/bin/nsh$ /etc/shells > /dev/null
if [ $? != 0 ]; then
    echo "/usr/local/bin/nsh" >> /etc/shells
fi

echo "############################################"
echo "## Your install is NOT done. Please read: ##"
echo "############################################"
echo "If /etc is read-only and /var/run is read-write, you need to use the save-ro.sh script:"
echo "    cp save-ro.sh /usr/local/bin/save.sh"
echo
echo "If /etc and /var/run are read-write, you need to use the save-rw.sh script:"
echo "    cp save-rw.sh /usr/local/bin/save.sh"
echo

echo "For now, you need to manually do the following:"
echo
echo "Comment out the following lines in /etc/rc:"
echo "############################################"
echo "# set hostname, turn on network"
echo "echo 'starting network'"
echo ". /etc/netstart"
echo 
echo "if [ "X${pf}" != X"NO" ]; then"
echo "        if [ -f ${pf_rules} ]; then"
echo "                pfctl -f ${pf_rules}"
echo "        fi"
echo "fi"
echo "############################################"
echo
echo "And add the following after (cd /var/authpf && rm -rf -- *):"
echo "    nsh -i /etc/nshrc | tee /var/run/nsh.out"
