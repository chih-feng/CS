<?php
require_once ("../_inc/lib_vd2.inc.php");


system("cd ../prj_ptw01/;");


Ap_RunAsyncShellCmd("cd ../prj_ptw01/;php vd_ptw1.php");//php ../prj_ptw01/vd_ptw1.php

Ap_RunAsyncShellCmd("cd ../prj_ptw01/;php vd_wss1.php");


?>