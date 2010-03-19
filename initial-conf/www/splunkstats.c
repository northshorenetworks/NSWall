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
	 #include <syslog.h>

int
 main(int argc, char *argv[]) 
     {
         struct pfioc_rule pr;	
         int pf_dev;  
 		 char *p;

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
				if (ioctl(pf_dev, DIOCGETRULE, &pr)) {
               		err(1, "DIOCGETRULE");
    				return (-1);	
    			}
	 			p = &pr.anchor_call[0];
				syslog (LOG_INFO,"rule:%s|%llu|%llu|%llu|%u|",p,
				   (unsigned long long)pr.rule.evaluations,
				   (unsigned long long) (pr.rule.packets[0] +
				   pr.rule.packets[1]),
				   (unsigned long long)(pr.rule.bytes[0] +
                   pr.rule.bytes[1]), pr.rule.states_cur); 
	     }
	     return(0);
      }
