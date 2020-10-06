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
	public $Blink_List =array()  ;//  array{ "do_name"=> stat}  

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
$Vd_Mbs->FncOnMbsACK			= 'OnMbsACK'	; //lib_modbus V2.0
 
 
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

	list($ai_name,$value)	=  $aryAI_value;// [ ai_name, value ] 

	# 依據不同程式改變封包
	$strVDData = sprintf("-AI=%s%05d", $ai_name , $value) ; //keep 3 Byte, AI= AI3 00123
	$Ap_Vd->VDSend("PTW1" , $strVDData );
	//$Ap_Vd->VDSend("WSS1" , "-" .  $ai_name . '=' . $value );
	//PTW1 . IOB1 . DI= A + ; DI= 的封包定義和 WEB1 一致。
}
//lib_modbus V2.0
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
	// 
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
	$Vd_Mbs->Polling_DI(); //PLDI, PLAI
	
}

function Tmr_MbsPoll_DI()//call back function setting in VD_xxxx.cfg "Interval"
{
	global $Ap , $Ap_Vd, $Vd_Timer, $Vd_Mbs ;
	$Vd_Mbs->Polling_DI();
}

function Tmr_MbsPoll_AI()//call back function setting in VD_xxxx.cfg "Interval"
{
	global $Ap , $Ap_Vd, $Vd_Timer, $Vd_Mbs ;
	//必須拆開<不能連續發出
	$Vd_Mbs->Polling_AI();
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
	$cmd		= substr($Mesg, 0,3);
	switch ($cmd) {//
		case 'POL':
			$Vd_Mbs->Polling_DIAI();
			break;
		case 'SDO'://Set DO SDOA5+, * ->  $Ap->Blink_List
			
			$len_Mesg	= strlen($Mesg);
			$DO_name 	= substr($Mesg, LEN_MBS_IO_CMD , $len_Mesg - 1 - LEN_MBS_IO_CMD); // 'A2"
			$DO_stat 	= substr($Mesg, $len_Mesg -1 ,1); // '+'  or  '-' or '*'

			$Ap_Vd->dbg_print( "vd_iob1.php::Set_DO" ,[ 'DO_name'=>$DO_name, 'DO_stat'=> $DO_stat ]);
			$Vd_Mbs->Set_DO($DO_name, $DO_stat );

			break;
		case 'GAL':

			$Vd_Mbs->Polling_AI();
			break;
		case 'GAI'://Reserve, 不方便得知取值位置in rsp
			echo "reserve";
			break;
		case 'SAO'://SAOAO200123 ,Set AO 'AO2' value '00123'
			$len_Mesg 	= strlen($Mesg) ;
			$ao_name	= substr($Mesg, LEN_MBS_IO_CMD , $len_Mesg - LEN_MBS_IO_CMD - 5); // length = 'SAO' , '00123'
			$ao_name_len= strlen($ao_name );
			$value		= substr($Mesg, $len_Mesg  - 5  ); // 
			$Vd_Mbs->Set_AO($ao_name , $value);
			break;
		//---- Application ----
		case 'LTA'://Set all DO on
			$stat = substr($Mesg,3);
			$Vd_Mbs->Set_DO_All($stat);
			break;

		case 'BLK': //Set DO Blink, ex."BLNKA5" set DO A5 blink
			//Blink function in vd_ioo1.ph
			break;
		case 'RDI' : // 
			break;
		case 'RAI' : //' 
			break;
		case 'WDO' : // 
			break;
		case 'WAO' : //
			break;
		case 'STS' :
			
			break;
		case '?':
			echo "-----\n"
				."POL : Get DI all\n"
				."SDO : SDOA1+ , SDO A1+ , SDOA1* : Blink\n"
				."PAI : Get AI All\n"
				."//GAI : \n"
				."SAO : SAOAO100016, SAO AO1 00016\n"
				."//BLKA5+ : set DO A5 blink\n"
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