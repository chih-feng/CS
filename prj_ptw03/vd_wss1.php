<?php
/*
	vd_wss1.php

	WS Server VD.

	2020.4.9 01:26	Begin
	2020.4.12 02:22 add PingPong Test. for test performance.


*/

require_once "../_inc/lib_vd2.inc.php";
require_once "../_inc/lib_ws.inc.php";
require_once "../_inc/lib_timer.inc.php";

$Ap = 0;

$Ap_Vd 		= new VxD('WSS1', 'OnRcvVdMsg');
$Ap_WS 		= new WebSocketSvr($Ap_Vd );
$Vd_Timer	= new VD_Timer ($Ap_Vd );

$Ap_Vd->FncOnRcvElse			= 'OnRcvElse' ;// after parse VD ,Per Line data.
$Ap_Vd->FncOnMainLoop			= 'OnVdMainLoop'	;
$Ap_Vd->FncOnClientDisConnect	= 'WhenWsClientDisConnect'	;
$Ap_Vd->FncOnReadEachClient		= 'ProcWsMsg';//when just Read socket, Before pase VD

$Ap_Vd->Run();
//call back function
function OnVdMainLoop()
{
	global $Ap , $Ap_Vd, $Ap_WS ,$Vd_Timer;
	$Vd_Timer->RunInterval()	;
}

function AlartBlink()
{
	global $Ap , $Ap_Vd, $Ap_WS ;
	static $flag="-";

	$flag = $flag == "-" ? "+" : "-" ;
	$Ap_WS->WsSendAll("ALR" . $flag );

}

function OnRcvVdMsg($aryPara)
{

	global $Ap , $Ap_Vd, $Ap_WS ,$Vd_Timer;
	//VD standard packet `VD1  PTW1   WSS1 - LTE+A302
	$DstVD		= $aryPara[ VDMSG_DstVD	] 	;
	$SrcVD		= $aryPara[ VDMSG_SrcVD	] 	;
	$Type		= $aryPara[ VDMSG_Type	] 	;	// 'c' cmmd, '-' VD-Data
	$Mesg		= $aryPara[ VDMSG_MESG	] 	;	//

	$Ap_Vd->dbg_print( "CallBack: OnRcvVdMsg" , $aryPara );
	//===============================================
	$cmd 	= substr($Mesg, 0, 4);
	switch ($cmd) {
		case 'ERR:' :
			$Ap_WS->WsSendAll($Mesg );
			break;
		case 'LTA+'://2020.5.7 00:18
		case 'LTA-'://2020.5.7 00:18
			$Ap_WS->WsSendAll($cmd );
			break;

		case 'set*':
			$Idx 	= $Vd_Timer->FindIntvlTableIdx("Blink");
			if ($Idx != -1) {
				$Vd_Timer->IntvlTable[$Idx]["Enable"] = 1 ;
			}
			break;
		case 'set-':
			$Idx 	= $Vd_Timer->FindIntvlTableIdx("Blink");
			if ($Idx != -1) {
				$Vd_Timer->IntvlTable[$Idx]["Enable"] = 0 ;
			}
			break;
		case '?':
			echo "\n"
				."\n"
				;
			break;
		default:
			$Ap_WS->WsSendAll($Mesg );
			break;
	}


}
//Process Websocket from Web處理web送過來的封包
function ProcWsMsg($client , $strRead )
{
	global $Ap , $Ap_Vd, $Ap_WS ;

	//$aryRcv		= json_decode ( $strJson ,TRUE );
	$aryWsClient	= $Ap_WS->GetWsClientInfo($client);
	if( ! $aryWsClient[WS_IS_HND_SHKED ]     ){ return 0; }

	$strLine 		= $Ap_WS->Decode($strRead);
	$Ap_Vd->LogFileView(LOG_IN, trim($strLine));//`VD1WSS1WEB1-PPT10

	$strMsgType 	= substr($strLine, 13,3);
	//echo "\n\$strMsgType =$strMsgType\n";
	//echo substr($strLine, 16 );
	switch( $strMsgType ){
		case "BC=" :
			//Send to PTW1
			$Ap_Vd->VDSend("PTW1" , "-".substr($strLine, 13)  );
			break;
		case "TST" ://for PTW_Mode == 0
			//TST+		:	Test all lites.set all lite on.
			//TST-		:	Test all lites.set all lite off.
		case "PTW" : //PTWBGN / PTWEND
		case "SHT" : //Short nn
			$Ap_Vd->VDSend("PTW1" , "-".substr($strLine, 13)  );
			break;
		case "PPT": // PingPong Test
			$iCnt	= intval(substr($strLine, 16 ));
			if ($iCnt) {
				$iCnt--;
				$Ap_WS->WsSend($client , "PPT" . $iCnt)	;
			}
			break;
		default :
			$Ap_Vd->VDSend("PTW1" , "-".substr($strLine, 13)  );
			
	}
	//處理過封包，return 1, 否則 return 0 留給預設程式處理。
	return 1;	//有處理封包就要return 1, 表示此封包處理過了。

	#$Ap_Vd->VDSend($vd_dst, $vd_msg);
}
//=================================================================
// 以下基本不變
//=================================================================
//This a template.以下基本不變
function OnRcvElse($aryPara)
{
	global $Ap , $Ap_Vd, $Ap_WS ;

	if( $Ap_Vd->IsDebug ){ echo "OnRcvElse \n"; print_r($aryPara) ;}

	$client 	= $aryPara[0] ;
	$strLine 	= $aryPara[1] ;

	$Ap_WS->LogFile(LOG_IN , '['. $client  . ']' .$strLine);

	//==================================
	$ary_WsClient	= $Ap_WS->GetWsClientInfo($client);//*
	$ws_client		= $ary_WsClient[WS_CLIENT 		];
	$isHandShaked	= $ary_WsClient[WS_IS_HND_SHKED	];
	if( !$isHandShaked  ) {
		$Ap_WS->InitWS($client , $strLine );
	}

}
///This a template.
function WhenWsClientDisConnect($client)
{
	global $Ap , $Ap_Vd,  $Ap_WS;

	$Ap_WS->OnWsClientDisConnect($client);

}


?>