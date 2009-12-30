     #include <sys/types.h>
     #include <sys/socket.h>
     #include <sys/ioctl.h>
     #include <sys/fcntl.h>
     #include <net/if.h>
     #include <netinet/in.h>
     #include <net/pfvar.h>
     #include <err.h>
     #include <stdio.h>
     #include <stdlib.h>

int
 main(int argc, char *argv[]) 
     {
        struct pfioc_altq    pa;
		struct pfioc_qstats  pq; 
		int pf_dev;  
	    struct timeval tv;
        double uusec; 
  
         pf_dev = open("/dev/pf", O_RDONLY);
         if (pf_dev == -1)
     	    err(1, "open(\"/dev/pf\")");
	     
		 memset(&pa, 0, sizeof(pa));
		 memset(&pq, 0, sizeof(pq));
		 if (ioctl(pf_dev, DIOCGETALTQS, &pa)) {
         	err(1, "DIOCGETALTQS");
     		return (-1);
	     }    

	     u_int32_t nr, mnr;
	     mnr = pa.nr;
	     for (nr = 0; nr < mnr; ++nr) {
            pa.nr = nr;
            if (nr == atoi(argv[1])) {
				if (ioctl(pf_dev, DIOCGETALTQ, &pa)) {
               		err(1, "DIOCGETALTQ");
    				return (-1);	
    			}
 				gettimeofday(&tv, NULL);
                uusec = (double)tv.tv_sec + (double)tv.tv_usec / 1000000.0;
        		printf("Content-Type: text/plain\n\n");
	    		printf("%lf|%llu|0\n", uusec, (int) pq.nbytes);
	     		return(0);
  			}
	     }
	     return(0);
      }
