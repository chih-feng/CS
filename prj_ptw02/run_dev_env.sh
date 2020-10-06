#!/bin/bash
# Program:
#	run_dev_env
# History:
# 	2020.5.4 15:00 
# use: source ./run_test.sh
# /usr/bin/nohup : The VD's  STDIN will disable when RUN by this script.
# if no "nohop" peocess will stop in few seconds later.

myvar="$PWD"

cd ../prj_ptw01
/usr/bin/nohup php vd_ptw1.php  >/dev/null 2>&1 &
#/usr/bin/nohup php vd_wss1.php  >/dev/null 2>&1 &
/usr/bin/nohup php vd_iob1.php   >/dev/null 2>&1 &
/usr/bin/nohup php vd_wss1.php   >/dev/null 2>&1 &

cd "$myvar"

jobs
