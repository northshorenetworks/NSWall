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
         struct pfioc_rule pr;	
         int pf_dev;  
	     struct timeval tv;
         double uusec; 
  
         pf_dev = open("/dev/pf", O_RDONLY);
         if (pf_dev == -1)
     	    err(1, "open(\"/dev/pf\")");
	     
		 memset(&pr, 0, sizeof(pr));
		 if (ioctl(pf_dev, DIOCGETRULES, &pr)) {
         	err(1, "DIOCGETRULES");
     		return (-1);
	     }    

	     u_int32_t nr, mnr;
	     mnr = pr.nr;
	     for (nr = 0; nr < mnr; ++nr) {
            pr.nr = nr;
            if (nr == atoi(argv[1])) {
				if (ioctl(pf_dev, DIOCGETRULE, &pr)) {
               		err(1, "DIOCGETRULE");
    				return (-1);	
    			}
 				gettimeofday(&tv, NULL);
                uusec = (double)tv.tv_sec + (double)tv.tv_usec / 1000000.0;
        		printf("Content-Type: text/plain\n\n");
	    		printf("%lf|%llu|0\n", uusec, (unsigned long long) (pr.rule.bytes[0] + pr.rule.bytes[1]));
	     		return(0);
  			}
	     }
	     return(0);
      }
