<?php
/*
	vd_MBS1.php

	Templete of VD

	2020.4.18 11:20
	2020.5.3 V1.0 OK
	2020.5.30 23:40 #1,#2 Done.

	本例中，顯示 Interval 和 RunAfter (啟動interval)

	##ToDo
	#1. Blink DO
	#2. Blink ALR (2DO)

*/

require_once ("../_inc/lib_vd2.inc.php");
require_once ("../_inc/lib_timer.inc.php"); //timer
require_once ("../_inc/lib_modbus.inc.php"); //modbus

class clsAP
{
	public $Blink_List ;//array{ "do_name"=> stat}

	function __construct($Ap_Vd)
	{
		$Ap_Vd->ExtClass['Ap']  = &$this  ;
	}
}

$Ap_Vd 		= new VxD ('IOI1', 'OnRcvVdMsg');

$Ap 		= new clsAP($Ap_Vd);
$Vd_Timer	= new VD_Timer 	($Ap_Vd );
$Vd_Mbs		= new VD_Modbus ($Ap_Vd , "ioB_DI" );

$Ap_Vd->FncOnRcvElse			= 'OnRcvElse' ;// after parse VD ,Per Line data.
$Ap_Vd->FncOnMainLoop			= 'OnVdMainLoop'	;
$Ap_Vd->FncOnClientDisConnect	= ''	;
$Ap_Vd->FncOnReadEachClient		= 'OnReadEachClient' ;//'ProcWsMsg'//when just Read socket, Before pase VD

$Vd_Mbs->FncOnDI_Change			= 'OnDI_Change' ;
$Vd_Mbs->FncOnAI_Change			= 'OnAI_Change'	;
$Vd_Mbs->FncOnMbsACK			= 'OnMbsACK'	; //V1.2
 
 
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
	//處理過封包，return 1, 否則 return 0 留給預設程式處理。
	return 0;//Did not processed.
}
// 依據不同程式改變封包
function OnDI_Change( $di_name , $on_off /* '+':on , '-' :off     */)
{
	global $Ap , $Ap_Vd, $Vd_Mbs	;

	#如果要設定控制ioBox邏輯, 在這裡設定。


	# 依據不同程式改變封包
	$Ap_Vd->VDSend("PTW1" , "-DI=" .  $di_name . $on_off);
	//$Ap_Vd->VDSend("WSS1" , "-DI=" .  $di_name . $on_off);
	// PTW1 . IOB1 . DI= A + ; DI= 的封包定義和 WEB1 一致。
}
// 依據不同程式改變封包
function OnAI_Change( $aryAI_value)
{
	global $Ap , $Ap_Vd, $Vd_Mbs	;


	list($ai_name,$value)	=  $aryAI_value;

	# 依據不同程式改變封包
	$Ap_Vd->VDSend("PTW1" , "-" .  $ai_name . '=' . $value );
	//$Ap_Vd->VDSend("WSS1" , "-" .  $ai_name . '=' . $value );
	//PTW1 . IOB1 . DI= A + ; DI= 的封包定義和 WEB1 一致。
}

function OnMbsACK($VDCmd)
{
	global $Ap , $Ap_Vd, $Vd_Timer, $Vd_Mbs	;

	$Ap_Vd->VDSend("PTW1" , "-" . "ACK$VDCmd" );
}
//==================================================
//Timer section.
//==================================================
function OnVdMainLoop()
{
	global $Ap , $Ap_Vd, $Vd_Timer, $Vd_Mbs	;

	$Vd_Timer->RunTimer()	;// Polling Here.
	//lib_modbus V2.0
	//Check time out and re-send > MacResend_times.
	if($msg_err = $Vd_Mbs->Mbs_tmr_chk_timeout() ){
		$Ap_Vd->VDSend(  "PTW1"
                        , '-ERR' .$msg_err
							//   $msg_err =  "NAC# Re-send [$tx_id][". $this->Mbs_Cmd_Queue[$tx_id]["VDCmd" ]  ."] more than ". $this->MaxRetry_times  . " times." ;
							//				 "NAC# Re-send [101][STDOA+] more than 3 times." ;
							//  NAC : None Ack
                        );
		//是否用另一個 ioBox 緊急停止 >> also send to another VD , and the VD send to iob engercy stop
              
	}
}
//
function Tmr_MbsPolling()//call back function setting in VD_xxxx.cfg "Interval"
{
	global $Ap , $Ap_Vd, $Vd_Timer, $Vd_Mbs ;
	$Vd_Mbs->Polling_IO(); //PLDI, PLAI
}

function Tmr_MbsPoll_DI()//call back function setting in VD_xxxx.cfg "Interval"
{
	global $Ap , $Ap_Vd, $Vd_Timer, $Vd_Mbs ;
	$Vd_Mbs->Get_DI_All();
}

function Tmr_MbsPoll_AI()//call back function setting in VD_xxxx.cfg "Interval"
{
	global $Ap , $Ap_Vd, $Vd_Timer, $Vd_Mbs ;
	//必須拆開<不能連續發出
	$Vd_Mbs->Get_AI_All();
}
//Reserve in thus case, this case is send to WSS2
function Tmr_Blink_Alr()//function setting in VD_xxxx.cfg "Interval"
{
	global $Ap , $Ap_Vd, $Vd_Timer, $Vd_Mbs ;

	static $stat='-';

	$stat = $stat == "+" ? "-" : "+" ;
	$Vd_Mbs->Set_DO(  $do_name  , $stat );

	//echo microtime(true) . " $flag\n";

}
function Tmr_Blink_DO()//function setting in VD_xxxx.cfg "Interval"
{
	global $Ap , $Ap_Vd, $Vd_Timer, $Vd_Mbs ;
	/*
	static $stat='-';

	$stat = $stat == "+" ? "-" : "+" ;
	*/

	foreach( $Ap->Blink_List as $do_name => $stat){//## [ 'A2' => '+' ]
		$stat = $stat == "-" ? "+" : "-" ;
		$Vd_Mbs->Set_DO(  $do_name  , $stat );
	}

	//echo microtime(true) . " $flag\n";

}
function Tmr_SetIntervalON()//call back function setting in VD_IOB1.cfg "RunAfterStart"
{//定時啟動
	global $Ap , $Ap_Vd, $Vd_Timer ;
	/*
	$Idx 	= $Vd_Timer->FindIntvlTableIdx("Mbs_poll");
	if ($Idx != -1) {
		$Vd_Timer->IntvlTable[$Idx]["Enable"] = 1 ;
	}
	*/
}
function Tmr_SetIntervalOff()//call backfunction setting in VD_IOB1.cfg "RunAfterStart"
{//定時停止
	global $Ap , $Ap_Vd, $Vd_Timer ;
	/*
	$Idx 	= $Vd_Timer->FindIntvlTableIdx("Mbs_poll");
	if ($Idx != -1) {
		$Vd_Timer->IntvlTable[$Idx]["Enable"] = 0 ;
	}
	*/
}
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

	$Ap_Vd->dbg_print( "CallBack: OnRcvVdMsg" , $aryPara );
	//===============================================
	$cmd		= substr($Mesg, 0,4);
	switch ($cmd) {//
		case 'POLL':// PLDI,PLAI
			//$Vd_Mbs->Get_DI_All();
			$Vd_Mbs->Polling_IO();
			break;
		case 'STDO'://Set DO ex: "STDOA5+"
			$DO_name 	= substr($Mesg, 4,2); // 'A2"
			$DO_stat 	= substr($Mesg, 6,1); // '+'  or  '-'

			$Ap_Vd->dbg_print( "vd_iob1.php::Set_DO" ,[ 'DO_name'=>$DO_name, 'DO_stat'=> $DO_stat ]);
			$Vd_Mbs->Set_DO($DO_name, $DO_stat );
			break;
		case 'GTAL'://Get AI All
			$start 		= substr($Mesg, 4,2); // 'A2"
			$lengths	= substr($Mesg, 6,1); // '+'  or  '-'

			$Vd_Mbs->Get_AI_All();
			break;
		case 'GTAI'://Reserve, 不方便得知取值位置in rsp
			echo "reserve";
			break;
		case 'STAO'://STAOAO200123 ,Set AO 'AO2' value '00123'
			$len 	= strlen($Mesg) ;
			$ao_name	= substr($Mesg, 4, $len - 9); // length = 'STAO' , '00123'
			$ao_name_len= strlen($ao_name );
			$value		= substr($Mesg, 4+$ao_name_len ); // 
			$Vd_Mbs->Set_AO($ao_name , $value);
			break;
		//---- Application ----
		case 'LTA+'://Set all DO on
			$Vd_Mbs->Set_DO_All("+");
			break;
		case 'LTA-'://Set all DO off
			$Vd_Mbs->Set_DO_All("-");
			break;
		case 'BLK+': //Set DO Blink, ex."BLNKA5" set DO A5 blink
			$Do_name = substr($Mesg, 4); //GET Blink DO name, ex. "A5" "AR" AR for Alart92 DO)
			$Ap->Blink_List[$Do_name] = '-'; //[ "A5" => '-' ]
			$Idx 	= $Vd_Timer->FindIntvlTableIdx("Blink_DO");
			if ($Idx != -1) {
				$Vd_Timer->IntvlTable[$Idx]["Enable"] = 1 ;
			}
			break;
		case 'BLK-': //Set DO Blink, ex."BLNKA5" set DO A5 blink
			$Do_name = substr($Mesg, 4); //GET Blink DO name, ex. "A5" "AR" AR for Alart92 DO)
			unset($Ap->Blink_List[$Do_name] ); //[ "A5" => '-' ]
			$Vd_Mbs->Set_DO($Do_name, '-' );

			if(count($Ap->Blink_List)==0){//if empty, set blink timer off
				$Idx 	= $Vd_Timer->FindIntvlTableIdx("Blink_DO");
				if ($Idx != -1) {
					$Vd_Timer->IntvlTable[$Idx]["Enable"] = 0 ;
				}
			}
			break;
		case 'GDIS' : //'GDIS123456' : Get DIs, from 123, length 456
			break;
		case 'GAIS' : //'GAIS123456' : Get AIs, from 123, length 456
			break;
		case 'SDOS' : //'SDOS123456' : Set DOs, from 123, length 456
			break;
		case 'SAOS' : //'SAOS123456' : Set AOs, from 123, length 456
			break;
		case '?':
			echo "-----\n"
				."POLL : Get DI all\n"
				."STDO : STDOA1+ , STDO A1+\n"
				."GTAL : Get AI All\n"
				."STAO : STAOAO1=16\n"
				."BLK+A5: set DO A5 blink\n"
				."LTA+ : Set all DO on\n"
				."LTA- : Set all DO off\n"
				."BLK-A5: set DO A5 blink off, and set A5 '-'\n"
				,"-----\n"
				;
			break;
		default:
			echo "Ext Cmd error.\n";
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

?>