#!/bin/bash
# Program:
#	run_sys.sh
# History:
# 	2020.5.16 11:40
# use:  
# /usr/bin/nohup : The VD's  STDIN will disable when RUN by this script.
# if no "nohop" peocess will stop in few seconds later.

# use in console
# $ source run_sys.sh


myvar="$PWD"

#cd ../prj_ptw01

/usr/bin/nohup php vd_bcr1.php   >/dev/null 2>&1 &
/usr/bin/nohup php vd_wgt1.php   >/dev/null 2>&1 &
/usr/bin/nohup php vd_wss1.php   >/dev/null 2>&1 &

/usr/bin/nohup google-chrome "http://127.0.0.1/nextcs/prj_wgt+bcr/web1.html"  >/dev/null 2>&1 &


cd "$myvar"

jobs
