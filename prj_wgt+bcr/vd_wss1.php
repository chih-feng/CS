<?php
/*
	vd_wss1.php

	2020.5.15 16:00 Start



*/

require_once "../_inc/lib_vd2.inc.php";
require_once "../_inc/lib_ws.inc.php";

$Ap = 0;

$Ap_Vd =	new VxD('WSS1', 'OnRcvVdMsg');
$Ap_WS =	new WebSocketSvr($Ap_Vd );

$Ap_Vd->FncOnRcvElse			= 'OnRcvElse' ;// after parse VD ,Per Line data.
$Ap_Vd->FncOnMainLoop			= ''	;
$Ap_Vd->FncOnClientDisConnect	= 'OnWsClientDisConnect'	;
$Ap_Vd->FncOnReadEachClient		= 'ProcWsMsg';//when just Read socket, Before pase VD

$Ap_Vd->Run();
//call back function
function OnRcvVdMsg($aryPara)
{

	global $Ap , $Ap_Vd, $Ap_WS ;
	//VD standard packet
	$DstVD		= $aryPara[ VDMSG_DstVD	] 	;
	$SrcVD		= $aryPara[ VDMSG_SrcVD	] 	;
	$Type		= $aryPara[ VDMSG_Type	] 	;	// 'c' cmmd, '-' VD-Data
	$Mesg		= $aryPara[ VDMSG_MESG	] 	;	//

	if( $Ap_Vd->IsDebug ){ print_r($aryPara); }
	//===============================================
	$cmmd 	= substr($Mesg, 0, 4);
	switch ($cmmd) {
		case $SrcVD == 'WGT1'://
		case $SrcVD == 'BCR1'://
			$Ap_WS->WsSendAll($SrcVD . $Mesg );//send to all Web
			break;
		case  '2WEB' ://
			$Ap_WS->WsSendAll( substr($Mesg, 4) );//send to all Web
			break;
		case '?'://use '!?' show hint when debugging
			echo "-----\n"
				."2WEB  : send to all Web Browser\n"
				//."  :\n"
				,"-----\n"
				;
			break;		default:
			$Ap_WS->WsSendAll($Mesg );//send to all Web
			//$Ap_WS->WsSend($client , $strMsg  );  //個別發送
			break;
	}
}
//Process Websocket from Web處理web送過來的封包
function ProcWsMsg($client , $strRead )
{
	global $Ap , $Ap_Vd, $Ap_WS ;

	#code..
	//$aryRcv		= json_decode ( $strJson ,TRUE );
	$aryWsClient	= $Ap_WS->GetWsClientInfo($client);
	if( ! $aryWsClient[WS_IS_HND_SHKED ]     ){ return 0; }

	$strLine = $Ap_WS->Decode($strRead);
	$Ap_Vd->LogFileView(LOG_IN, trim($strLine));

	//處理過封包，return 1, 否則 0 留給預設程式處理。
	return 1;	//有處理封包就要return 1, 表示此封包處理過了。

	#$Ap_Vd->VDSend($vd_dst, $vd_msg);
}
//=================================================================
// 以下基本不變
//=================================================================
//This a template. ws 在這裡 Hand Shake.
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
function OnWsClientDisConnect($client)
{
	global $Ap , $Ap_Vd, $Ap_WS ;

	$Ap_WS->OnWsClientDisConnect($client);
}


?>