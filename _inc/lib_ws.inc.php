<?php
/*  lib_ws.inc.php

	Websocket class.

 	2020.3.28	18:00	begin.
        03.29   23:21*  Communication OK.
        V0.8*   24:00
    2020.4.1	23:50	Add Decode, Encode
        V0.9			Add WsSendAll , Send & Rcv data decode/encode OK.
    2020.4.2
        V1.0*   02:48   Test OK.
        v1.01	12:32	陣列元素字串，改成define , ex. [client'] , define (WS_CLIENT , 'client'); KeyName
        				待測 GetWsClientInfo($client)
        v1.1*	16:27   Todo#4. GetWsClientInfo($client) Test OK
        v1.11	20:14	#6. Log fileName add  date , ex. WSS1_20200402_sys.log
        v1.12	19:16	配合lib_vd2 V2.15 Add ExtClass , 方便Dump時，連擴充類別一起顯示，Ex. WebSocketSvr
    todo:
    5. 進來的json 要\r\n 結尾
	7. ws Heartbeat or auto-reconnect.
	8. ws in 封包會出現錯誤，要判斷封包是否結束，避免封包斷裂。
		寫一個程式，自動發送，記錄封包編號，看封包怎麼斷裂。
		usleep(100) 一樣發生。但是cpu用量從6.5%->2.5%
		考慮有 Inerval Timer, 可能改 50 看看.

		每個client都多一個陣列元素，作為push buffer.

 */

define ('WS_CLIENT' 		,	'client'		) ;
define ('WS_IS_HND_SHKED'   ,	'isHandShaked'	) ;
define ('WS_HOST' 		    ,	'ws_host'     	) ;
define ('WS_VER' 			,	'ws_ver'		) ;
define ('WS_KEY' 			,	'ws_key'		) ;
define ('WS_EXTNS' 		    ,	'Extensions'	) ;//Sec-WebSocket-Extensions:

class WebSocketSvr
{
    public      $Vd
    		,	$WsClients  // Array: 記錄連進來的ws clients
    		,	$LogFile_In
    		,	$LogFile_Out
            ;

	function __construct( $argVD )
	{
		$this->Vd 	= &$argVD ;
		$strToday	= date("Ymd");//V2.12
		$this->LogFile_In	= '../VD_LOG/' . $argVD->VD_ID . "_$strToday"."_ws_i.log" ;
		$this->LogFile_Out	= '../VD_LOG/' . $argVD->VD_ID . "_$strToday"."_ws_o.log" ;
		$argVD->ExtClass["Ap_Ws"] = &$this ;
	}
	//Init & Handshake
	public function InitWS($client , $strLine )
	{
		//===============================
		if(	//strpos($strLine , 'ET' ) // 'GET / HTTP/1.1'
			substr($strLine, 0,3) == 'GET'
			&& strpos($strLine , 'HTTP/1.1')
		){
			//Add New Client record
			$this->WsClients[(int)$client ] = array(
													 WS_CLIENT 			=> $client
													,WS_IS_HND_SHKED	=> 0
                                			        ,WS_HOST 			=> ''
													,WS_VER 			=> ''
													,WS_KEY 			=> ''
													,WS_EXTNS 			=> ''
													); // [client_id , isHandShaked, ws_ver, ws_key]
			return;
		}

		$idx = $this->SearchClientIdx($client);
        if(!$idx) return;

		if(preg_match("/Host: (.*)/" ,$strLine , $match)){  //   Host: localhost:16104
			$this->WsClients[$idx][WS_HOST] = $match[1];
        }else
        if(preg_match("/Sec-WebSocket-Version: (.*)/" ,$strLine , $match)){  //  ws_ver
			$this->WsClients[$idx][WS_VER] = $match[1];
		}else
		if(preg_match("/Sec-WebSocket-Key: (.*)/" ,$strLine , $match)){
			$this->WsClients[$idx][WS_KEY] = $match[1];
		}else
		if(preg_match("/Sec-WebSocket-Extensions:(.*)/" ,$strLine , $match)){
			$this->WsClients[$idx][WS_EXTNS] = $match[1];
		}

		//===Handshake===============================================
        $ary_WsClient	= $this->GetWsClientInfo($client);
        $ws_client		= $ary_WsClient[WS_CLIENT 		];
        $isHandShaked	= $ary_WsClient[WS_IS_HND_SHKED	];
        $ws_host    	= $ary_WsClient[WS_HOST 		];
        $ws_ver         = $ary_WsClient[WS_VER 			];
        $ws_key         = $ary_WsClient[WS_KEY 			];
        $ws_extns       = $ary_WsClient[WS_EXTNS		];

		if(		$ws_client
			&&	$isHandShaked 	== 0
			&&	$ws_host 		!= ''
			&&	$ws_ver    		!= ''
			&&	$ws_key    		!= ''
			&&	$ws_extns 		!= ''
		) {//Check 所有要素到齊再來回應，免得多收封包來處理

			//==== Begin Handshake

			$hash_data  = base64_encode(sha1($ws_key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', 	true));

			$response   =	"HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
							"Upgrade: websocket\r\n" .
							"Connection: Upgrade\r\n" .
							"WebSocket-Origin: " . strstr($ws_host, ':',TRUE)  . "\r\n" .
							"WebSocket-Location: ws://" . $ws_host   . "/\r\n" .
							"Sec-WebSocket-Accept:" . $hash_data . "\r\n\r\n";

			$this->LogFileView (LOG_OUT , "\n".$response);

            $ret = stream_socket_sendto ( $client  , $response  );
			$this->Vd->dbg_print( "Write Ws Handshake" , $ret   )  ;
            if(!$ret){ //Error
                $this->Vd->LogFileView (LOG_SYS , "Error> WS [$client] HandShake Fail!! \n");
            }else{
                $idx = $this->SearchClientIdx($client);
                if(	!$idx ){ return ; } // Not Ws client
                $this->WsClients[$idx][ WS_IS_HND_SHKED ] = 1;
                $this->Vd->LogFileView (LOG_SYS , "WS [$client] HandShaked. \n");
            }

		}
	}
	//Send to All client V0.9
	public function WsSendAll( $strMsg  )
	{
		if(!empty($this->WsClients)){
			foreach ($this->WsClients as $client) {
				$this->WsSend( $client[ WS_CLIENT ] , $strMsg  );
			}
		}
	}
	//
	public function WsSend($client , $strMsg  )
	{
		global $Ap , $Ap_Vd, $Ap_WS ;
		//
		$ret = stream_socket_sendto ( $client  , $this->Encode($strMsg )  );
		//$this->Vd->dbg_print( "Write Ws Handshake" , $ret   )  ;
        if(!$ret){  //Error
            $this->Vd->LogFileView (     LOG_SYS
                                    ,    "Error> WS [$client] stream_socket_sendto() Fail!! \n"
                                        ."$strMsg\n\n"
                                    );
            $status = 'X' ;//
        }else{
			$status = ' ' ;// Successed
		}
		$strLog = $status . 'ResID['. intval($client)  . "][$strMsg]" ;
		if( $Ap_Vd->IsDebug ){
			$this->LogFileView(LOG_OUT , $strLog);
		}else{
			$this->LogFile(LOG_OUT , $strLog);
		}
	}
	//
	public function OnWsClientDisConnect($client)
	{
		$idx = $this->SearchClientIdx($client);
		if(	!$idx ){ return ; } // Not Ws client

		$ary_WsClient	= $this->GetWsClientInfo($client);
        $ws_client		= $ary_WsClient[WS_CLIENT 		];
        $isHandShaked	= $ary_WsClient[WS_IS_HND_SHKED	];
        $ws_host    	= $ary_WsClient[WS_HOST 		];
        $ws_ver         = $ary_WsClient[WS_VER 			];
        $ws_key         = $ary_WsClient[WS_KEY 			];
        $ws_extns       = $ary_WsClient[WS_EXTNS		];

		$this->Vd->LogFileView(LOG_SYS , "WsClient disconnected.\n"
										."	client:		[$ws_client]\n "
										."	isHandShaked:   [$isHandShaked]\n"
                                        ."	WS_Host:	[$ws_host]\n"
										."	Ws_Ver:		[$ws_ver]\n"
										."	Ws_key:		[$ws_key]\n"
										."	ws_extns:	[$ws_extns]."
								);
		unset($this->WsClients[$idx]);
	}
	//list($ws_client, $isHandShaked, $ws_ver, $ws_key)	= $Ap_WS->GetWsClientInfo($client);
	public function GetWsClientInfo($client)
	{
   		//v1.0 php 的函式似乎有問題，總是return 0
   		//$idx = array_search(  $client  , array_column($this->WsClients , 'client')  );

        $idx = $this->SearchClientIdx($client);
        if($idx ){
            return [
                        WS_CLIENT 		=>  $this->WsClients[$idx][ WS_CLIENT 		]
                    ,	WS_IS_HND_SHKED	=>  $this->WsClients[$idx][ WS_IS_HND_SHKED	]
                    ,	WS_HOST 		=>  $this->WsClients[$idx][ WS_HOST 		]
                    ,	WS_VER 			=>  $this->WsClients[$idx][ WS_VER 			]
                    ,	WS_KEY 			=>  $this->WsClients[$idx][ WS_KEY 			]
                    ,	WS_EXTNS 		=>	$this->WsClients[$idx][ WS_EXTNS		]
                    ];
        }else{
        	return  [   WS_CLIENT 		=> NULL
					,   WS_IS_HND_SHKED	=> 0
					,   WS_HOST 		=> ''
					,   WS_VER 			=> ''
					,   WS_KEY 			=> ''
					,   WS_EXTNS		=> ''
					];
        }
	}
	//=======================================
    //2020.4.2 02:24
	protected function SearchClientIdx($client)
    {
        if( $this->WsClients  ){
            foreach( $this->WsClients as $key => $ws_client ){
                if( $this->WsClients[$key][ WS_CLIENT ] == $client){
                    return $key ;
                }
            }
        }
        return NULL;
    }
	//
	public function LogFileView($log_io_type , $arg_str)
	{
		$str	= 		"MSG> " .	$arg_str ."\n" ;
		echo $str ;
		$this->LogFile	( $log_io_type , $arg_str	);
	}
	//
	public function LogFile($log_io_type , $arg_str ) // LOG_IN |  LOG_OUT
	{
		$logfile 		=  LOG_IN == $log_io_type ? $this->LogFile_In : $this->LogFile_Out ;

		$strSeparator	= ' ';
		$strLog	= substr( 	Ap_uDate("ymd-His.u"),1,16) // 00312-171618.654
							//date("ymd-His.u") // 100310.171618.654
						. 	$strSeparator	 .	$arg_str   //remove under score
						.	PHP_EOL
						;
		$iCount = file_put_contents($logfile , $strLog , FILE_APPEND | LOCK_EX );

	}
	//=======================================
	public function Encode($M)
    {
		// inspiration for Encode() method : http://stackoverflow.com/questions/8125507/how-can-i-send-and-receive-websocket-messages-on-the-server-side
		$L = strlen($M);

		$bHead = [];
		$bHead[0] = 129; // 0x1 text frame (FIN + opcode)

		if ($L <= 125) {
            $bHead[1] = $L;
		} else if ($L >= 126 && $L <= 65535) {
            $bHead[1] = 126;
            $bHead[2] = ( $L >> 8 ) & 255;
            $bHead[3] = ( $L      ) & 255;
		} else {
            $bHead[1] = 127;
            $bHead[2] = ( $L >> 56 ) & 255;
            $bHead[3] = ( $L >> 48 ) & 255;
            $bHead[4] = ( $L >> 40 ) & 255;
            $bHead[5] = ( $L >> 32 ) & 255;
            $bHead[6] = ( $L >> 24 ) & 255;
            $bHead[7] = ( $L >> 16 ) & 255;
            $bHead[8] = ( $L >>  8 ) & 255;
            $bHead[9] = ( $L	   ) & 255;
		}

		return (implode(array_map("chr", $bHead)) . $M);
	}
	//============================================
	public function Decode($M){
		$M = array_map("ord", str_split($M));
		$L = $M[1] AND 127;

		if ($L == 126)
			$iFM = 4;
		else if ($L == 127)
			$iFM = 10;
		else
			$iFM = 2;

		$Masks = array_slice($M, $iFM, 4);

		$Out = "";
		for ($i = $iFM + 4, $j = 0; $i < count($M); $i++, $j++ ) {
			$Out .= chr($M[$i] ^ $Masks[$j % 4]);
		}
		return $Out;
	}
}


?>