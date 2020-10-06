<?php
/* lib_core.onc.php

core common library.

2020.5.2 19:12 Create.
               Ap_RunAsyncShellCmd($comando = null);

*/


//===============================================================================
function Ap_uDate($format = 'u', $utimestamp = null)
{
    if (is_null($utimestamp)){
        $utimestamp = microtime(true);
    }
    $timestamp = floor($utimestamp);
    $milliseconds = round(($utimestamp - $timestamp) * 1000000);//改這裡的數值控制毫秒位數
    return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
}
//================================================================
function Ap_RunAsyncShellCmd($comando = null){
    if(!$comando){
        throw new Exception("No command given");
    }
    // If windows, else
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        system($comando." > NUL");
    }else{
        //shell_exec("/usr/bin/nohup ".$comando." >/dev/null 2>&1 &");
        shell_exec("/usr/bin/nohup ".$comando." >/dev/null 2>&1 &");
    }
}
//================================================================


?>