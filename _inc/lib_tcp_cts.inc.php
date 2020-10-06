<?php
/* lib_tcp_client.inc.php

ren from lib_nport.inc.php
for all TCP client

v0.00 2020.5.10 23:08 Start.
V1.0  2020.5013 01:00
----lib_tcp_client.inc.php--



*/


class VD_TCP_cts //Client to Server
{
    public    $Vd
            , $SVR_IP
            , $SVR_Port
            , $Cts_Client
            ;

    function __construct( $argVD  )
    {
        $this->Vd                   = &$argVD ;
        $argVD->ExtClass["TCP_SVR"] = &$this  ;

        //Load Config
        $vd_file            = "./VD_" . $this->Vd->VD_ID . ".cfg" ;
        $sJason             = file_get_contents($vd_file );//將整個檔案內容讀入到一個字串中 VD_OOOO.cfg
        $cfg                = json_decode($sJason, TRUE);

        $this->SVR_IP     = $cfg['SVR_IP'] ;
        $this->SVR_Port   = $cfg['SVR_Port'] ;
        //Init class
        //open TCP_SVR soecket
        $url        = "tcp://" . $this->SVR_IP .":" . $this->SVR_Port ;

        $socket     = stream_socket_client (  $url
                                            , $errno
                                            , $errstr
                                            , STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
        if (false === $socket ) {
            //echo "$errstr($errno)\n";
            $argVD->LogFileView(LOG_SYS , "Fatal> Create Cts_Client socket failed.[". $errstr($errno). "]" );
            exit();
        }
        $this->Cts_Client  = $socket;
        //add to VD listen
        $argVD-> AddListenClient($socket);

        //================================
        $argVD->LogFileView(LOG_SYS , "TCP_SVR ". $this->SVR_IP .":" . $this->SVR_Port ." Connected.(client:$socket)\n");
    }

}

?>