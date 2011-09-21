#!/bin/sh

# source the pf.subr file
. ./pf.subr

# static rule, for testing
# add_pfrule action N pass in on em0 proto tcp from 10.0.0.0/8 to any port 80

# tested the following with the following /var/run/nssh_pf.conf
#   nat N match out log on vlan160 from <globalnat> to !<nonat> nat-to 172.17.176.28
#   binat N match log on vlan160 from 192.168.2.169 to any binat-to 172.17.191.229
#   filter N pass in on $int inet proto udp from any to 209.253.22.184 port 1194
#   filter N pass out on $ext inet proto udp from any to 209.253.22.184 port 1194
#   filter N pass in on $int inet proto tcp from 192.168.9.39 to any port 25
#   filter N pass out on $ext inet proto tcp from 192.168.9.39 to any port 25 
#   filter 1 pass in log on $ext inet proto tcp from any to <ssh_servers> port 22
#   filter 1 pass in log on $ext inet6 proto tcp from any to <ssh_servers> port 22
#   filter 2 pass out log on $int inet proto tcp from any to <ssh_servers> port 22
#   filter 2 pass out log on $int inet6 proto tcp from any to <ssh_servers> port 22

gen_pffile
chk_pffile
