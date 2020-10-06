<?php
/*
	vd_MBS1.php

	Templete of VD

	2020.4.18 11:20

	本例中，顯示 Interval 和 RunAfter (啟動interval)
*/

require_once "../_inc/lib_vd2.inc.php";

require_once "../_inc/lib_timer.inc.php"; //timer
require_once "../_inc/lib_modbus.inc.php"; //modbus

$Ap = 0;

$Ap_Vd 		= new VxD		('IOB1', 'OnRcvVdMsg');

$Ap_Vd->FncOnRcvElse			= 'OnRcvElse' ;
$Ap_Vd->FncOnMainLoop			= 'OnVdMainLoop'	;
$Ap_Vd->FncOnClientDisConnect	= ''	;
$Ap_Vd->FncOnReadEachClient		= 'OnReadEachClient' ;//'ProcWsMsg'

$Vd_Timer	= new VD_Timer 	($Ap_Vd );
$Vd_Mbs		= new VD_Modbus ($Ap_Vd , "ioBox" );

$Vd_Mbs->FncOnDI_Change			= 'OnDI_Change' ;


$Ap_Vd->Run();

//==================================================
//Modbus section.
//==================================================
function OnReadEachClient( $client ,$strRead)
{
	global $Ap , $Ap_Vd, $Vd_Mbs	;
	if($client == $Vd_Mbs->MbsClient  )
	{
		$Vd_Mbs->Proc_Rsp($strRead);
		return 1;//Must Return 1 ,IF processed.;
	}
	return 0;//Did not processed.
}
// 依據不同程式改變封包
function OnDI_Change( $di_name , $on_off /* '+':on , '-' :off     */)
{
	global $Ap , $Ap_Vd, $Vd_Mbs	;

	#如果要設定控制ioBox邏輯, 在這裡設定。


	# 依據不同程式改變封包
	$Ap_Vd->VDSend("PTW1" , "DI=" .  $di_name . $on_off);
	$Ap_Vd->VDSend("WSS1" , "DI=" .  $di_name . $on_off);
	// PTW1 . IOB1 . DI= A + ; DI= 的封包定義和 WEB1 一致。


}




//==================================================
//Timer section.
//==================================================
function OnVdMainLoop()
{
	global $Ap , $Ap_Vd, $Vd_Timer, $Vd_Mbs	;

	$Vd_Timer->RunTimer()	;//Must for Timer.

}


 
function Tmr_MbsPoll()//call back function setting in VD_xxxx.cfg "Interval"
{
	$Vd_Mbs->DefaultPolling();
}
function Tmr_AlartBlink()//function setting in VD_xxxx.cfg "Interval"
{
	global $Ap , $Ap_Vd, $Vd_Timer ;
	static $flag="-";

	$flag = $flag == "-" ? "+" : "-" ;
	echo microtime(true) . " $flag\n";

}
function Tmr_SetIntervalON()//call back function setting in VD_xxxx.cfg "Interval"
{
	global $Ap , $Ap_Vd, $Vd_Timer ;

	$Idx 	= $Vd_Timer->FindIntvlTableIdx("Mbs_poll");
	if ($Idx != -1) {
		$Vd_Timer->IntvlTable[$Idx]["Enable"] = 1 ;
	}
}
function Tmr_SetIntervalOff()//call backfunction setting in VD_xxxx.cfg "Interval"
{
	global $Ap , $Ap_Vd, $Vd_Timer ;

	$Idx 	= $Vd_Timer->FindIntvlTableIdx("Mbs_poll");
	if ($Idx != -1) {
		$Vd_Timer->IntvlTable[$Idx]["Enable"] = 0 ;
	}
}
//==================================================
//==================================================
//call back function ：處理送進來的vd封包
function OnRcvVdMsg($aryPara)
{

	global $Ap , $Ap_Vd  ,$Vd_Timer, $Vd_Mbs ;
	//VD standard packet
	$DstVD		= $aryPara[ VDMSG_DstVD	] 	;
	$SrcVD		= $aryPara[ VDMSG_SrcVD	] 	;
	$Type		= $aryPara[ VDMSG_Type	] 	;	// 'c' cmmd, '-' VD-Data
	$Mesg		= $aryPara[ VDMSG_MESG	] 	;	//

	if( $Ap_Vd->IsDebug ){ print_r($aryPara); }
	//===============================================
	$cmd		= substr($Mesg, 0,4); 
	switch ($cmd) {//
		case 'POLL':
			$Vd_Mbs->DefaultPolling();
			break;
		case 'STDO'://Set DO STDOA5+
			$DO_name 	= substr($Mesg, 4,2); // 'B5'
			$DO_stat 	= substr($Mesg, 6,1); // '+'  or  '-'

			$Vd_Mbs->Set_DO($DO_name, $DO_stat );
			break;
		case 'fc05-':
			$Vd_Mbs->Set_DO("A5" , 1);
			break;
		case 'set-':

			$Idx 	= $Vd_Timer->FindIntvlTableIdx("Blink");
			if ($Idx != -1) {
				$Vd_Timer->IntvlTable[$Idx]["Enable"] = 0 ;
			}
			break;
		default:
			break;
	}

}
//
//This a template.非vd封包
function OnRcvElse($aryPara)
{

	global $Ap , $Ap_Vd  ;

	if( $Ap_Vd->IsDebug ){ echo "OnRcvElse \n"; print_r($aryPara) ;}

	$client 	= $aryPara[0] ;
	$strLine 	= $aryPara[1] ;
	//==================================



}
//=================================================================
// 以下基本不變
//=================================================================
///This a template.
function OnWsClientDisConnect($client)
{
	global $Ap , $Ap_Vd  ;
	//This cdase NO WS.
	//$Ap_WS->WhenWsClientDisConnect($client);
}


?>