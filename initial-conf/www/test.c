#include <sys/types.h>
#include <sys/socket.h>
#include <net/if.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <sys/time.h>
#include <sys/dkstat.h>
#include <sys/ioctl.h>
#include <sys/param.h>
#include <sys/sysctl.h>

void if_stats(char *name){
        static struct if_data data;
        int sd;
        struct timeval tv;
        double uusec;
        struct if_data *get_if_data(char *name) {
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

int main(int argc, char *argv[]) {

        char                            *cl, *rm;

        printf("%s\n",cl);
        //printf("Content-Type: text/plain\n\n");

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

        if_stats(cl);

        return 0;
}
