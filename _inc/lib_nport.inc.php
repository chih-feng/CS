<?php
/*  lib_nport.inc.php

v0.00 2020.5.10 23:08 Start.
V1.0  2020.5013 01:00

copy to lib_tcp_client.inc.php, and going..
here keep compatiable


*/

class VD_NPort
{
    public    $Vd
            , $NPort_IP
            , $NPort_Port
            , $NPort_Client
            ;

    function __construct( $argVD  )
    {
        $this->Vd                   = &$argVD ;
        $argVD->ExtClass["NPort"]   = &$this  ;

        //Load Config
        $vd_file            = "./VD_" . $this->Vd->VD_ID . ".cfg" ;
        $sJason             = file_get_contents($vd_file );//將整個檔案內容讀入到一個字串中 VD_OOOO.cfg
        $cfg                = json_decode($sJason, TRUE);

        $this->NPort_IP     = $cfg['NPort_IP'] ;
        $this->NPort_Port   = $cfg['NPort_Port'] ;
        //Init class
        //open NPort soecket
        $url        = "tcp://" . $this->NPort_IP .":" . $this->NPort_Port ;

        $socket     = stream_socket_client (  $url
                                            , $errno
                                            , $errstr
                                            , STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
        if (false === $socket ) {
            //echo "$errstr($errno)\n";
            $argVD->LogFileView(LOG_SYS , "Fatal> Create Nport socket failed.[". $errstr($errno). "]" );
            exit();
        }
        $this->NPort_Client  = $socket;
        //add to VD listen
        $argVD-> AddListenClient($socket);

        //================================
        $argVD->LogFileView(LOG_SYS , "NPort ". $this->NPort_IP .":" . $this->NPort_Port ." Connected.(client:$socket)\n");
    }

}


?>