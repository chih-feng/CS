<?php
/* lib_Vd2.inc.php

	Vxd class library .

	2020.3.11	19:00 Begin
				20:30 V0.8 Done 完成Svr移轉，可以用類別執行
				23:00 call-back done.
	2020.3.12	01:16 finish todo #3,4.
				02:00 set fwrite retry ,but miss 1, try socket_sendto...
				10:30 add 'knck', add other protocol
				17:44 resolve broken socket / re-send. re-asign 'knck'  to constant
				18:25 Log system.
				21:11 V1.0 cadidate
		V1.0	23:20 V1.0
				23:40 修改LogFile移除底線條
	2020.3.13	00:48 refine ..
		V1.01	15:00 define LOG_SYS,LOG_IN,LOG_OUT
		V1.1   	20:54 add timer function in main loop
		V1.11	21:09 Fix bug
	2020.3.14
		V1.12* 	21:45 Move stream_socket_serve() to constructor.
				Release 1.
	2020.3.15
		V1.13 	00:05 modify.
	------
	2020.3.29
		V2.01	02:12 add draft,主要修改建構介面。
				11:00 	#7.VD end -> "\r\n" v2.01
						#8. call-back add: v2.01
							call_user_func( $this->FncOnRcvElse , [$client , $strLine] ) ;
							call_user_func 檢查是不是空字串。
						#9. 修改 __condtruct 的參數。
						#10. Add OnClientDisConnect Event handler.
		V2.1* 	24:00 WS connected.
	2020.4.2	16:50 Todo#11.	11. 陣列元素字串，改成define , ex.AryMap[ 'WSS1' ]['port'] , define (KN_PORT , 'port'); KeyName
		V2.11	17:00 usleep(100); 測試是否可以避免封包斷裂。
		V2.12*	20:20 #12.Log fileName add  date , ex. WSS1_20200402_sys.log

	2020.4.07	17:30	連續兩天測試，ws的收封包會有斷裂，思考修改結構，先存2.12
		V2.13*	17:34	修改 Run, 抽出成為函式，使易讀
						New Call-back ，處理不同協定, Ex.WS multi-line protocol
				20:03	Done
		V2.14	21:00	add VDcommand: DBUG, DMFI
	2020.4.10 	19:16	V2.15 Add ExtClass , 方便Dump時，連擴充類別一起顯示，Ex. WebSocketSvr
		 4.11	10:50	V2.16 cfg增加參數。 MSLEEP,IsDebug
	2020.4.12	03:12	V2.17 Add TimeZone
	2020.4.17	15:10	V2.18 修改 Main Sleep 位置。
						add AddListenClient(),DropListenClient();
		*V2.18	23:00	將client 加入Listen 的清單中，用在polling聽回復封包
	2020.4.28	18:47	add STDIN command 'self' for send packet to self.
		 V2.19	19:24	done.
	2020.5.02	21:08
		 v2.20			Add cfg not found msg
	2020.5.03   20:16	Exten STDIN command for background debug.
		 V2.21	20:35   done
		 V2.22  21:21   Log before send,for make sure send ok.add re-send interval.
	2020.5.01 	01:48	V2.23 VDSend() dbg_print
	     5.11   01:53   V2.24 add $code_info.
	     		02:30		測試無效，都是dbg的line_print
				11:00   V2.25 stream_socket_sendto
				18:00   fwrite 比stream_socket_sendto 穩，Svr斷線只失落一次。
						增加模式切換 scls0 , scls1 set $IsVDSendClose
	V2.25* 5.12 00:29	connect ,disconnect 只有在Dubug on 才紀錄。
	V2.26  6.24 00:05   Add error Check 'VD_MAPS.cfg' .

	todo://---- V2.x

		13. exten call-back STDINcmmd.Help 提示，做成屬性，可以extClass來增加。-- Done, Use '!?'
		14. 封包切割，push bufger. pop add to next packet.
		=================================================================
		`VD1-REV1-SRC1-cCmmd
		`VD1-REV1-SRC1--[Device Datas]  : "u12.3"   ||  "s12.1"
		`VD1-REV1-PTL1-128 cfnd

		ws -> io-box -> buy -> io-box .so -> * [2X2 PTW]
			-> 3D scale
			->

*/

require_once ("../_inc/lib_core.inc.php");

define('KNOCK_STR'			, 	"`k`"		);
define('FREAD_SIZE'			, 	4096 		);
define('VD_HEADER'			, 	'`VD1'		);
define('VD_TAIL'			, 	"\n"		); //V2.01 add
define('LOG_SYS'			,	0			);
define('LOG_IN'				,	1			);
define('LOG_OUT'			,	2			);
define('VD_IP'				,	'IP'		); //V2.11
define('VD_PORT'			,	'port'		); //V2.11

define('VDMSG_DstVD'		,	'DstVD'		); //v2.14
define('VDMSG_SrcVD'		,	'SrcVD'		);
define('VDMSG_Type' 		,	'Type' 		); //
define('VDMSG_MESG' 		,	'Mesg' 		); //

class VxD
{
	public 	  $VD_ID						= ""
			, $IsDebug 						= 1
			, $RetryTimes_SocketSend		= 3
			, $RetryIntvl_SocketSend		= 200
			, $SocketVD								//Bind Port

			, $FncOnMainLoop		 		= ""	// Loop, 作為 Polling
			, $FncOnRcvVdMsg				= ""	// 收到VD封包的處理
			, $FncOnRcvElse 				= ""	// 其他通信協定
			, $FncOnClientDisConnect		= ""	// Client斷線處理
			, $FncOnReadEachClient			= ""	// v2.13 rueurn TRUE, 就表示處理完畢，否則會繼續給後面封包處理程式處理
													// FncOnClientDisConnect , $client ,$strRead )
													// 處理多行的資料
			, $MainSleep					= 1000	// v2.16
			, $TimeZone						= "Asia/Taipei"
			;

	private   $AryMap
			, $VD_MAP
			, $Log_in						= ""
       		, $Log_out						= ""
       		, $Log_sys						= ""
			, $IsKeepRun					= 1
			, $OftenVDID
			, $Clients
			, $IsVDSendClose				= 0 //v2.25 Open before Send, close after send
			;
	public 	  $ExtClass						;		// V2.15
	//=== Public function ====
	function __construct(
						  $arg_vd_id
						, $arg_fnc_on_rcv_vd 	= "OnRcvVdMsg" //必要的函式
						// {{V2.01 marked
						//, $arg_fnc_on_rcv_else 	= 'OnRcvElse'     //"OnRcvElse"
						//, $arg_fnc_on_run		= ""
						// }}V2.01 marked
						)
	{

		global $Ap;

		$this->VD_ID 				= $arg_vd_id ;
		$this->FncOnRcvVdMsg		= $arg_fnc_on_rcv_vd ;

		$this->load_vd_cfg( );
		
		//V2.26 Add 
		if(empty( $this->AryMap[ $this->VD_ID ][VD_IP] )) {
			$this->LogFileView(LOG_SYS , "Fatal> VD_ID:'$arg_vd_id' IP/port undefined. Check 'VD_MAPS.cfg' " );
			exit(-1);
		}
		
		//V1.12 Move from Run() to here,
		$url 		= "tcp://" . $this->AryMap[ $this->VD_ID ][VD_IP] .":" . $this->AryMap[ $this->VD_ID ][VD_PORT] ;

		$socket 	= stream_socket_server (  $url
											, $errno
											, $errstr
											, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
		if (false === $socket ) {
			//echo "$errstr($errno)\n";
			$this->LogFileView(LOG_SYS , "Fatal> Create socket_server failed.[". $errstr($errno). "]" );
			exit();
		}

		$this->Clients = [$socket, STDIN];

		//2020.4.27 01:19 for win
		//stream_set_blocking (STDIN, FALSE ) ;  //useless..

		//echo "...Server ready...(".  $this->AryMap[ $this->VD_ID ][VD_PORT] .")\n";
		$this->LogFile (	LOG_SYS	, "\n\n\t\t\t\t\t\t***\n");
		$this->LogFileView(	LOG_SYS
							, 	" ...Server ready...("
							.  $this->VD_ID .":"
							.  $this->AryMap[ $this->VD_ID ][VD_PORT]
							.  ")\n"
							);
		//$this->LogFileView(	LOG_SYS	, "");
		$this->SocketVD = $socket;
		set_time_limit(0);
	}
	//============================================
	public function Run()
	{
		while($this->IsKeepRun){
			$read = $this->Clients;//wait on here
			$ret = stream_select($read, $w, $ee, 0);
			if(false === $ret){
				//Todo: Show Error !
				break;
			}
			//$conn = false ;
			foreach($read as $client){
				if($client == $this->SocketVD){ //新客户端
					$this->AddNewClient($client);//V2.13

				}elseif ($client == STDIN) {//Consol commmand
					// in Windows, It will into here , run to fread , wait keyin....
					$this->do_STDINcmmd( $client);//V2.13

				}else{//處理其他client 和其他協定連線
					$strRead = fread($client, FREAD_SIZE);//注意，使用$client而不是$conn

					//非正常关闭,清除該連線
					if( $strRead == NULL ){
					//if(false === $strRead){/never  return   false
						/* v2.16 獨立一個funcyion
						   ▊▊▊ 在裡面Call back :: FncOnClientDisConnect ▊▊▊
						*/
						$this->WhenClientDisconnect($client);
						goto lbl_main_loop_end ;
					}
					//==========================
					// 開始處理收到的封包
					//==========================
					//A.處理其他協定的client, ex. ws, multi-line data.
					if($this->FncOnReadEachClient){//{{v2.13
						//return 1 : clear parse。讀下一個 Client
						//return 0 : 這裡沒處理，繼續往下處理單行類型的封包
						if(call_user_func( $this->FncOnReadEachClient , $client ,$strRead )){
							goto lbl_main_loop_end ;
						}
					}//}}v2.13
					//==========================
					//B.處理處理單行類型的封包, ex. VD
					$aryLines = explode("\n" ,  trim($strRead) );
					foreach( $aryLines as $strLine ){
						$strLine = trim($strLine);
						$strHeader = substr($strLine ,0,4 )  ;
						switch ($strHeader ){
							case VD_HEADER :
								try {
									//{{只有紀錄VD封包 v2.01
									if($strLine!='' &&  $strLine != KNOCK_STR){
										$this->LogFile		(	LOG_IN , 	$strLine );
									}
									//}}v2.01
									$aryPara  = $this->VDParse($strLine);
									if($aryPara['Type'] == 'c'){
										$this->do_VDCmd($aryPara);
									}else{
										call_user_func($this->FncOnRcvVdMsg , $aryPara) ;
										//$aryPara = ['DstVD'], ['SrcVD'], ['Type'], ['Data']
									}
								}catch (Exception $e) {
									//echo 'exception VD parse: ',  $e->getMessage(), "\n" ;
									$this->LogFileView(	  LOG_SYS
														, 'exception VD parse: '.  $e->getMessage() 
														);
								}
								break;
							case KNOCK_STR :
							case '' ://do nothing,drop it
								break;
							default:
								if($this->FncOnRcvElse == '' )	break ;

								try {
									//封包紀錄在這個 call_back 內
									call_user_func( $this->FncOnRcvElse ,  [$client , $strLine] ) ;	//v2.01
								}catch (Exception $e) {
									$this->LogFileView(	LOG_SYS
														, 	'exception OnRcvElse : '.  $e->getMessage() 
														);
								}
						}//switch ($strHeader )
					}//foreach($aryLines as $strLine){
				}//else //處理其他client 和其他協定連線
			}//foreach
			lbl_main_loop_end:
			usleep( $this->MainSleep );//2020.3.9 20"09 ,v2.11 chg to 100看能否避免封包的切割。
			if($this->FncOnMainLoop){
				call_user_func($this->FncOnMainLoop  ) ;	//小心使用，會一直觸發
			}
		}//while($this->IsKeepRun)
		fclose($this->SocketVD);
	}
	//V2.16 獨立出來，易讀
	private function WhenClientDisconnect($client)
	{
		//$key = array_search($client, $this->Clients);
		//unset($this->Clients[$key]);
		//V2.18
		$this->DropListenClient($client);

		//{{V2.01
		if($this->FncOnClientDisConnect){
			try {
					call_user_func( $this->FncOnClientDisConnect , $client ) ;
				}catch (Exception $e) {
					$this->LogFileView(	LOG_SYS
										, 	'exception OnClientDisConnect : '.  $e->getMessage()
										);
				}
		}
		//}}V2.01
		//V2.25 mark.為了避免丟是封包，改成每次重連，停止記錄。只有IsVDSendClose ==0 紀錄
		if( $this->IsDebug){
			$this->LogFileView	(	LOG_SYS , "\nfread empty. (unset client [".$client."])" );
		}
	}
	//============================================
	public function VDSend($vd_dst, $vd_msg)
	{
		$iRetryTimes 	= 0 ;

		$strSend 		= VD_HEADER ;
		$strSend 		= $strSend .$vd_dst ;
		$strSend 		= $strSend .$this->VD_ID  ;
		$strSend 		= $strSend .$vd_msg . VD_TAIL ;

		if(!isset($this->AryMap[$vd_dst][VD_IP])) {
			//echo "Error: undefine dstVD_ID [$vd_dst]\n";
			$this->LogFileView(	LOG_SYS , 	"Error: undefine dstVD_ID [$vd_dst]." );
			return;
		}
		//=== Socket connect ====================================
		if($this->AryMap[$vd_dst]["socket"] == 0)
		{
			lbl_reconncet:
			$strUrl =   "tcp://".
						$this->AryMap[$vd_dst][VD_IP] . ":" . //IP
					   	$this->AryMap[$vd_dst][VD_PORT] ;// port:9203";
			$socket =  stream_socket_client( 	$strUrl	, $errno   , $erstr);
			if(!$socket) {
				//echo "$errno : $erstr" ;
				$this->LogFileView(LOG_SYS , "VDSend::  stream_socket_client($strUrl) ErrNo[$errno]:$erstr" );
			}else{
				$this->AryMap[$vd_dst]["socket"] = $socket ;
			}
		}
		//=== Log before send =================================
		if(!$iRetryTimes){
			$this->LogFile  (	LOG_OUT , 	trim($strSend) );//V2.22
		}
		//=== Socket write ====================================
		$ret1 = fwrite( $this->AryMap[$vd_dst]["socket"] , KNOCK_STR . VD_TAIL );//u98n
		$ret2 = fwrite( $this->AryMap[$vd_dst]["socket"] , $strSend);
		//{{V2.25 $this->IsVDSendClose
		//stream_socket_sendto NG, lost more
		//$ret1 = stream_socket_sendto($this->AryMap[$vd_dst]["socket"], KNOCK_STR . VD_TAIL );//
		//$ret2 = stream_socket_sendto($this->AryMap[$vd_dst]["socket"], $strSend, STREAM_OOB);
		if($this->IsVDSendClose) {
			fclose($this->AryMap[$vd_dst]["socket"]);
			$this->AryMap[$vd_dst]["socket"] = 0 ;
		}
		//}}V0.25

		$ary = array($ret1,$ret2);
		$this->dbg_print( "VDSend: fwrite return ($ret1,$ret2)" , trim($strSend));//v2.23
		if(! $ret1  || ! $ret2){
			if($this->RetryTimes_SocketSend >= $iRetryTimes ){
				$iRetryTimes ++ ;
				$this->LogFileView(	LOG_SYS
									, 	"VDSend# Retry fwrite([". $this->AryMap[$vd_dst]['VD_ID'] . "] , [". trim($strSend). "])\n"
										. "[$iRetryTimes] times."
									);
				usleep($this->RetryIntvl_SocketSend);
				$this->LogFile  (	LOG_OUT , 	"~".$iRetryTimes );//V2.22 chg.
				goto lbl_reconncet;
			}
		}else{ // fwrite successed.
			if($iRetryTimes){//Log only had re-send.
				$this->LogFile  (	LOG_OUT , 	"~." );//V2.22 chg.
			}
		}
	}
	//===================================================
	public function LogFileView	( $file_type 		//0: sys, 1:in, 2: out
								, $arg_str
								)
	{
		$str	= 		"MSG> " .	$arg_str ."\n" ;
		echo $str ;
		$this->LogFile	( $file_type , $arg_str	);
	}
	//===================================================
	public function LogFile	( $file_type 		//0: sys, 1:in, 2: out
							, $arg_str
							)
	{
		$strSeparator = ''; //封包開頭 ` 會區隔
		switch($file_type){
			case 0 :
				$logfile = $this->Log_sys ;
				$strSeparator = '>' ;
				break;
			case 1 :
				$logfile = $this->Log_in  ;
				break;
			case 2 :
				$logfile = $this->Log_out ;
				break;
		}
		$strLog	= substr( 	Ap_uDate("ymd-His.u"),1,16) // 00312-171618.654
							//date("ymd-His.u") // 100310.171618.654
						. 	$strSeparator	 .	$arg_str   //remove under score
						.	PHP_EOL
						;
		$iCount = file_put_contents($logfile , $strLog , FILE_APPEND | LOCK_EX );
	}
	//2020.3.12 1228
	public function dbg_print(   $msg
								, $var  //if more than one, use array
										//if >= 100, echo & Log to LOG_SYS
							 )
	{
		if(! $this->IsDebug) return;

		//$code_info = basename(__FILE__) . ". line#"    .__LINE__ . ".\n" ;//V2.24

		if( gettype($var) == 'array'){
			echo "\nDebug> $msg :" ;
			print_r($var);
		}else{
			echo "\nDebug> $msg :[$var]\n" ;
		}
		//echo $code_info;//v2.24
		if($this->IsDebug >= 100){
			if( gettype($var) == 'array'){
				$str	= implode ( $var ) ;
			}else{
				$str	= $var ;
			}
			$str	= " Debug> $str\n" ; //. $code_info;//V2.24
			$this->LogFile	( LOG_SYS  , $str 	)	;
		}

	}
	//===================================================
	public function VDParse($arg_msg)
	{
		//$arg_msg				= trim($arg_msg);
		$aryRet[VDMSG_DstVD] 	= substr($arg_msg ,4,4 	);
		$aryRet[VDMSG_SrcVD] 	= substr($arg_msg ,8,4 	);
		$aryRet[VDMSG_Type ] 	= substr($arg_msg ,12,1 ); // 'c' , '-'
		$aryRet[VDMSG_MESG ] 	= substr($arg_msg ,13 );
		if( $aryRet[VDMSG_DstVD] !=  $this->VD_ID ){//Not send to me,Drop it.
			throw new Exception("Not My VD_ID.". "[$arg_msg]");
		}
		return $aryRet ;
	}
	//===================================================
	//V2.18 將client 加入Listen 的清單中，用在polling聽回復封包
	//與AddNewClient($client)不同，這是將連出去的Client加入server接收清單內。
	//ex在程式內先開client連到modbus server，加入此client
	public function AddListenClient($socketClient)
	{
		$this->Clients[] = $socketClient ;
	}
	//===================================================
	//v2.18 從Listen 的清單中移除client
	public function DropListenClient($client)
	{
		$key = array_search($client, $this->Clients);
		unset($this->Clients[$key]);
	}
	//===================================================
	//        Private
	//===================================================
	private function do_VDCmd($aryPara)
	{
		$strCmd = substr( $aryPara[VDMSG_MESG]  , 0,4);
		switch ($strCmd){
			case 'DOWN' :
				//echo "...Server down...(VD)\n";
				$this->LogFileView (	LOG_SYS
									, 	"...Server down...(VD)\n\n================="
									);
				unset($this->Clients);
				$this->IsKeepRun = 0 ;
				return -1 ;
			case 'SEND' ://用 vd cmd 轉發vd 封包 2020.3.12 01:14
				$vd_dst = substr($aryPara[VDMSG_MESG] , 4, 4);
				$vd_msg = substr($aryPara[VDMSG_MESG] , 8 );
				$this->VDSend($vd_dst, $vd_msg);
				break;
			case 'DMFI' ://V 2.14 Dump var To File
				$str =    "===dump===\n"
						. print_r($this,TRUE )
						. '$this->VD_ID : '. $this->VD_ID."\n"
						. "==========\n";
				$this->LogFileView(	LOG_SYS , 	$str 	);
				break;
			case 'DBUG' :// V 2.14, DBUG0100 set debup level from other VD
				$strDebugLevel = substr( $aryPara[VDMSG_MESG]  , 4);
				$this->IsDebug = intval ($strDebugLevel) ;
				break;

		}//switch($strCmd)
	}
	//===================================================
	private function 	load_vd_cfg()
	{
		//Load VD.cfg ========================
		$vd_file	= "./VD_" . $this->VD_ID . ".cfg" ;

		if(! file_exists($vd_file)){//v2.20
			date_default_timezone_set($this->TimeZone);
			$strToday		= date("Ymd");//V2.12
			$this->Log_sys	= "../VD_LOG/" . $this->VD_ID . "_$strToday"."_sys.log";
			$this->LogFileView(LOG_SYS, " Fatal> " . $this->VD_ID . ".cfg file not found.");
			exit();
		}

		$sJason 	= file_get_contents($vd_file );//將整個檔案內容讀入到一個字串中 VD_OOOO.cfg
		$cfg 		= json_decode($sJason, TRUE);

		if(json_last_error() !=JSON_ERROR_NONE){
			$this->LogFileView(LOG_SYS ,
						"Fatal> load_vd_cfg():: $vd_file Json Error :". json_last_error_msg());
			die();
		}
		$this->MainSleep= $cfg['MSleep'] ;//v2.16
		$this->IsDebug	= $cfg['IsDebug'] ;//v2.16
		$this->RetryTimes_SocketSend= $cfg['RetryTimes_SocketSend'] ;//v2.16
		$this->RetryIntvl_SocketSend= $cfg['RetryIntvl_SocketSend'] ;//V2.22
		$this->TimeZone	= $cfg['TimeZone'] ;//v2.17
		date_default_timezone_set($this->TimeZone);

		$strToday		= date("Ymd");//V2.12
		$this->VD_MAP	= $cfg['VD_MAP'] ;
		$this->Log_in	= $cfg['LOG_PATH'] . $this->VD_ID . "_$strToday"."_in.log";
		$this->Log_out	= $cfg['LOG_PATH'] . $this->VD_ID . "_$strToday"."_out.log";
		$this->Log_sys	= $cfg['LOG_PATH'] . $this->VD_ID . "_$strToday"."_sys.log";

		$this->IsVDSendClose = $cfg['IsVDSendClose'] ;//V2.25

		//Load VD_MAP =========================
		$sJason 		= file_get_contents( $this->VD_MAP  );//將整個檔案內容讀入到一個字串中

		$this->AryMap 	= json_decode($sJason, TRUE);
	}
	//===================================================
	//處理連進server的Client
	private function 	AddNewClient($client)//V2.13 為了易讀寫成function
	{
		$conn = stream_socket_accept($this->SocketVD, -1);
		if (false === $this->SocketVD ) {
			$this->LogFileView (	LOG_SYS
								, 	"Fatal> Svr socket accept ERROR! sys down."
								);
			exit("accept error\n");
		}
		//echo "new Client! fd:".intval($conn)."\n";
		//V2.25
		if( $this->IsDebug ){
			$this->LogFileView	(	LOG_SYS
								, 	"new Client! fd:".intval($conn)
								);
		}
		$this->Clients[] = $conn;
	}
	//===================================================
	//do STDIN command
	private function 	do_STDINcmmd( $client)//V2.13
	{
		# 2020.3.9 11:45
		$strRead = fread($client, FREAD_SIZE);
		$msg = trim($strRead, "\n\r");
		if(!$msg) return; // V1.11 fix bug
		//------------------------------------
		switch($msg){
			case "down" :
				$this->LogFileView (	LOG_SYS
									, 	"...Server down...(VD)\n\n================="
									);
				unset($this->Clients);
				$this->IsKeepRun = 0 ;
				return -1 ;
			case "dump":
				echo "===dump===\n". '$this : ' ;
				//print_r($this->Clients)
				print_r($this);
				echo '$this->VD_ID : '. $this->VD_ID."\n";
				echo "==========\n";
				break;
			case "dmfi": //to screen & log file
				$str =    "===dump===\n"
						. print_r($this,TRUE )
						. '$this->VD_ID : '. $this->VD_ID."\n"
						. "==========\n";
				$this->LogFileView(	LOG_SYS
											, 	$str
											);
				break; //
			case substr($msg ,0, 4) == "setv": //v2.21
				$this->OftenVDID = $vd_dst = substr($msg , 4, 4);;
				break; //
			case "set debug on": //2020.3.9 19:16 set server down
				$this->IsDebug = 1 ;
				break;
			case "set debug 100": //2020.3.12 20:28
				$this->IsDebug = 100 ;
				break;
			case "set debug off": //2020.3.9 19:16 set server down
				$this->IsDebug = 0 ;
				break;
			case substr($msg ,0, 1) == "#": //V2.21
				$vd_msg = substr($msg , 1 );
				$this->VDSend( $this->OftenVDID ,  $vd_msg);
				break;
			case substr($msg ,0, 1) == "!": //2020.4.28 18:47 V2.19  self
				$vd_msg = substr($msg , 1 );
				$this->VDSend( $this->VD_ID , '-' . $vd_msg);
				break;
			case substr($msg ,0, 4) == 'send':
				$vd_dst = substr($msg , 4, 4);
				$vd_msg = substr($msg , 8 );
				$this->VDSend($vd_dst, $vd_msg);
				break;
			case substr($msg ,0, 1) == '*'://V2.21
				$vd_dst = substr($msg , 1, 4);
				$vd_msg = substr($msg , 5 );
				$this->VDSend($vd_dst, $vd_msg);
				break;
			case 'scls0' ://V2.25
				$this->IsVDSendClose = 0 ;
				break;
			case 'scls1' ://V2.25
				$this->IsVDSendClose = 1 ;
				break;
			case 'help' ://2020.3.12 20:28
			case '?'	:
				echo		"=====\n"
						.	"set debug on/off  \n"
						.	"set debug 100 : show & log to file \n"
						.	"dump  : show Vars\n"
						.	"down  : shutdown\n"
						.	"dmfi  : display & dump to file \n"
						.   "scls0 : set \$IsVDSendClose = 0\n" //v2.25
						.   "scls1 : set \$IsVDSendClose = 1\n" //v2.25
						.	"\n"
						.	"send  : sendVDID-xxxx... \n"
						.	"        cDMFI , cDOWN, cSEND ,cDBUG0100\n"
						.	"*     : *VDID-xxxx... , *VDIDcxxxx ,same as 'send'\n"
						.	"!     : !xxxx....  => send '-xxxx...' to self \n"
						.	"\n"
						.	"setv  : set often VDID\n"
						.	"#     : send use often VDID\n"
						.   "        #-xxxx , #cxxxx\n"
						.	"\n"
						.	"?,help: show help.\n---\n"
						.	"!?    : Show Ext command help.\n"
						.	"=====\n"
						;
				break;
			default:
				echo "Bad Cmd.\n";
			//Todo#13. Exten call-back for WS like. to do Dump File
		}//switch

	}
	//===================================================
}//class VxD


?>