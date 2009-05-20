/*
	$Id: stats.c,v 1.7 2009/03/11 06:11:35 jrecords Exp $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2004-2005 Manuel Kasper <mk@neon1.net>.
	All rights reserved.
	
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:
	
	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.
	
	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.
	
	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

#include <sys/types.h>
#include <sys/socket.h>
#include <net/if.h>
#include <stdio.h>
#include <stdlib.h>
#include <sys/time.h>
#include <sys/dkstat.h>
#include <sys/ioctl.h>
#include <sys/param.h>
#include <sys/sysctl.h>

void cpu_stats() {

        long cp_time1[CPUSTATES], cp_time2[CPUSTATES];
        long total1, total2;
        size_t len;
        double cpuload;
        int mib[2];
        len = sizeof(cp_time1);

        mib[0] = CTL_KERN;
        mib[1] = KERN_CPTIME;

        if ((sysctl(mib, 2, &cp_time1, &len, NULL, 0) == -1) || !len) {
           warn("kern.cp_time");
           exit(1);
        }

        sleep(1);

        len = sizeof(cp_time2);

        if ((sysctl(mib, 2, &cp_time2, &len, NULL, 0) == -1) || !len) {
           warn("kern.cp_time");
           exit(1);
        }
        total1 = cp_time1[CP_USER] + cp_time1[CP_NICE] + cp_time1[CP_SYS] +
                         cp_time1[CP_INTR] + cp_time1[CP_IDLE];
        total2 = cp_time2[CP_USER] + cp_time2[CP_NICE] + cp_time2[CP_SYS] +
                         cp_time2[CP_INTR] + cp_time2[CP_IDLE];

        cpuload = 1 - ((double)(cp_time2[CP_IDLE] - cp_time1[CP_IDLE]) / (double)(total2 - total1));


        printf("%.0f\n", 100.0*cpuload);
}


void if_stats(char *name){
	static struct if_data data;
	int sd;
	struct timeval tv;
	double uusec;
	static struct if_data *get_if_data(char *name) {
	struct ifreq ifr;
    
	strlcpy(ifr.ifr_name, name, sizeof(ifr.ifr_name));
    	ifr.ifr_data = (caddr_t) &data;
    	if (ioctl(sd, SIOCGIFDATA, (char *) &ifr) < 0) return NULL;
    		return &data;
	}

	sd = socket(AF_INET, SOCK_DGRAM, 0);
        if (sd == -1) err(1, "socket");
        if (get_if_data(name) == NULL) err(1, "ioctl");
        gettimeofday(&tv, NULL);
        uusec = (double)tv.tv_sec + (double)tv.tv_usec / 1000000.0;
        printf("%lf|%llu|%llu\n", uusec, data.ifi_ibytes, data.ifi_obytes);
        exit (1);
}

void top() {
        FILE *inpipe;
        char inbuf[1000];
        int lineno = 0;

        char *command = "/usr/bin/top -o res -n 100";

       inpipe = popen(command, "r");
       if (!inpipe) {
              printf("couldn't open pipe %s\n", command);
              exit (1);
       }
       printf("<pre>\n");
       while (fgets(inbuf, sizeof(inbuf), inpipe)) {
              printf("%s", inbuf);
       }
       printf("</pre>\n");
       pclose(inpipe);

       exit (1);
}

void logs() {
	FILE *inpipe;
    	char inbuf[1000];
        int lineno = 0;

        char *command = "/usr/sbin/syslogc all";

       inpipe = popen(command, "r");
       if (!inpipe) {
              printf("couldn't open pipe %s\n", command);
              exit (1);
       }
       while (fgets(inbuf, sizeof(inbuf), inpipe)) {
              printf("<tr valign=\"top\"><td class=\"listlogr\" nowrap>%s</td></tr>", inbuf);
       }
       pclose(inpipe);

       exit (1);
}

void rules() {
        FILE *inpipe;
        char inbuf[1000];
        int lineno = 0;

        char *command = "/sbin/pfctl -vvs rules";

       inpipe = popen(command, "r");
       if (!inpipe) {
              printf("couldn't open pipe %s\n", command);
              exit (1);
       }
       printf("<pre>\n");
       while (fgets(inbuf, sizeof(inbuf), inpipe)) {
              printf("%s", inbuf);
       }
       printf("</pre>\n");
       pclose(inpipe);

       exit (1);
}

void nat() {
        FILE *inpipe;
        char inbuf[1000];
        int lineno = 0;

        char *command = "/sbin/pfctl -vvs nat";

       inpipe = popen(command, "r");
       if (!inpipe) {
              printf("couldn't open pipe %s\n", command);
              exit (1);
       }
       printf("<pre>\n");
       while (fgets(inbuf, sizeof(inbuf), inpipe)) {
              printf("%s", inbuf);
       }
       printf("</pre>\n");
       pclose(inpipe);

       exit (1);
}

void states() {
        FILE *inpipe;
        char inbuf[1000];
        int lineno = 0;

        char *command = "/sbin/pfctl -vvs states";

       inpipe = popen(command, "r");
       if (!inpipe) {
              printf("couldn't open pipe %s\n", command);
              exit (1);
       }
       printf("<pre>\n");
       while (fgets(inbuf, sizeof(inbuf), inpipe)) {
              printf("%s", inbuf);
       }
       printf("</pre>\n");
       pclose(inpipe);

       exit (1);
}

void queues() {
        FILE *inpipe;
        char inbuf[1000];
        int lineno = 0;

        char *command = "/sbin/pfctl -vs queue";

       inpipe = popen(command, "r");
       if (!inpipe) {
              printf("couldn't open pipe %s\n", command);
              exit (1);
       }
       printf("<pre>\n");
       while (fgets(inbuf, sizeof(inbuf), inpipe)) {
              printf("%s", inbuf);
       }
       printf("</pre>\n");
       pclose(inpipe);

       exit (1);
}

int main(int argc, char *argv[]) {
	
	char				*cl, *rm;

	printf("%s\n",cl);	
	printf("Content-Type: text/plain\n\n");
	
	rm = getenv("REQUEST_METHOD");
	if (rm == NULL)
		exit(1);
	if (strcmp(rm, "GET") != 0)
		exit(1);
		
	cl = getenv("QUERY_STRING");
	if (cl == NULL)
		exit(1);
	
	if (strlen(cl) < 3)
		exit(1);
	
	if (strcmp(cl, "cpu") == 0)
		cpu_stats();
	else if (strncmp(cl, "top", 3) == 0)
                top();
        else if (strncmp(cl, "logs", 4) == 0)
		logs();
	else if (strncmp(cl, "rules", 5) == 0)
                rules();
	else if (strncmp(cl, "nat", 3) == 0)
                nat();
	else if (strncmp(cl, "states", 6) == 0)
                states();
	else if (strncmp(cl, "queues", 6) == 0)
                queues();
	else
		if_stats(cl);
	
	return 0;
}

