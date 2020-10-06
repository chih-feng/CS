<?php
/*
	vd_ptw1.php

	Put to wall VD

	2020.4.9 01:20 Begin
	2020.5.4 14:56 restart after Modbus done
	*	.5.7 12:37 PTW_Mode 1st round test OK.

*/

define('PTW_MODE_IO' 		, 0);// Just test IO.
define('PTW_MODE_ONCE' 		, 1);// same sku one times.
define('PTW_MODE_COUNT' 	, 2);// each sku each put.

require_once "../_inc/lib_vd2.inc.php";

class clsApp //Master VD
{
	public    $Vd
			, $DI_MapX							//分成X,Y軸，方便後面處理。
			, $DI_MapY
			, $DO_Map
			, $IOVD 							// Array
			, $PTW_Mode		= PTW_MODE_IO 		//one by one | multi-sku in once

			, $Loc_ID							//= DO_name
			, $CurBarcode
			, $Qty_ToPut
			, $Qty_HadPut
			;
	function __construct($Ap_Vd)
	{
		$this->Vd	= $Ap_Vd;
		$Ap_Vd->ExtClass['Ap']  = &$this  ;

		$vd_file	= "./VD_" . $Ap_Vd->VD_ID . ".cfg" ;
		$sJason 	= file_get_contents($vd_file );//將整個檔案內容讀入到一個字串中 VD_OOOO.cfg
		$cfg 		= json_decode($sJason, TRUE);

		$this->DI_MapX	= $cfg['DI_MapX'];
		$this->DI_MapY	= $cfg['DI_MapY'];
		$this->DO_Map	= $cfg['DO_Map'];
		$this->PTW_Mode	= $cfg['PTW_Mode'];
		//group by IOVD, 找出 IOVD 的個別 VD_ID

##ToDo
	}
}

$Ap_Vd 	= new VxD('PTW1', 'OnRcvVdMsg');
$Ap 	= new clsApp($Ap_Vd );

//$Ap_Vd->FncOnRcvElse			= 'OnRcvElse' ;// after parse VD ,Per Line data.
//$Ap_Vd->FncOnMainLoop			= ''	;
//$Ap_Vd->FncOnClientDisConnect	= ''	;
//$Ap_Vd->FncOnReadEachClient		= '';//'ProcWsMsg';//when just Read socket, Before pase VD

$Ap_Vd->Run();
//call back function ：處理送進來的vd封包
function OnRcvVdMsg($aryPara)
{

	global $Ap , $Ap_Vd  ;
	//VD standard packet
	$DstVD		= $aryPara[ VDMSG_DstVD	] 	;
	$SrcVD		= $aryPara[ VDMSG_SrcVD	] 	;
	$Type		= $aryPara[ VDMSG_Type	] 	;	// 'c' cmmd, '-' VD-Data
	$Mesg		= $aryPara[ VDMSG_MESG	] 	;	//

	//$Ap_Vd->dbg_print( "CallBack: OnRcvVdMsg" , $aryPara );
	//===============================================
	$Ap_Vd->dbg_print( "\$Mesg" , $Mesg );

	$head	= substr($Mesg , 0, 3);
	switch(	$head ){
		case  "DI="  :
			Do_GetDI(substr($Mesg , 3, 1) , substr($Mesg, 4 ,1 )   );// 'A' , '+'
			break;
		case "PTM" ://Set PTW_Mode
			$Ap->PTW_Mode = substr($Mesg ,  3);
			break;
		case "TST" ://2020.5.62335
			//TST+		:	Test all lites.set all lite on.
			//TST-		:	Test all lites.set all lite off.
			$stat = substr($Mesg , 3, 1);
			if($stat == '+'){
				$Ap_Vd->VDSend("WSS1", "-LTA+" );
				//$Ap_Vd->VDSend("IOB1", "-LTA+" );
				//echo "case: TST, This must send to diff IOB  ";
				SendVDCmd_to_IOVD( "-LTA+" );

				$Ap_Vd->VDSend("WSS1", "-ALR+" );
				$Ap_Vd->VDSend("WSS1", "-NBR88" );
			}else{
				$Ap_Vd->VDSend("WSS1", "-LTA-" );
				//$Ap_Vd->VDSend("IOB1", "-LTA-" );
				SendVDCmd_to_IOVD( "-LTA-");

				$Ap_Vd->VDSend("WSS1", "-ALR-" );
				$Ap_Vd->VDSend("WSS1", "-NBR  " );
			}
			break;
		case $Mesg == "PTWBGN" :// PTWBGN : begin PTW work
			PTW_Begin();
			break;
		case $Mesg == "PTWEND" :// PTWEND : end PTW work
			PTW_End();
			break;
		case "BC=" : //BC=  Barcode=123456789
			GetBarcode(substr($Mesg ,  3));
			break;
		case "SHT" : //SHTnn   Short nn
			DoShort(substr($Mesg , 3)) ;
			break;
		case "ACK" ://ACK STAO AO2 00123  , ACK STDO A1+, DO,AO name 變動長度
			$Mesg_len	= strlen($Mesg);
			
			$io_cmd 	= substr($Mesg , 3, 4);
			if( $io_cmd== "STDO" ){
				$DO_name 	= substr($Mesg , 7, $Mesg_len -8 );
				$stat		= substr($Mesg , $Mesg_len -1 );
				$Ap_Vd->VDSend("WSS1", "-LTE".$stat.$DO_name );
			}else if( $io_cmd== "STAO" ){ // ACK STAO AO1 00123
				//$AO_name 	= substr($Mesg , 7, $Mesg_len -12 );
				//$vlaue		= substr($Mesg , $Mesg_len -5 );
				$Ap_Vd->VDSend("WSS1", "-AO=".substr($Mesg , 7) );
			}
			break;
		case "ERR" :
			## ToDo LogFileView , "ERRNAC# ....", NAC : None Ack
			## Emgercy Stop !!??
			## 是否用另一個 ioBox 緊急停止 >> also send to another VD , and the VD send to iob engercy stop
			break;
		case "?" :
		default:
			echo	"-----\n"
				.	"DI=A+ : 'DI=' 'A' '+'\n"
				.	"PTM1  : PTM set PTW_Mode, 0:Test IO,1:Put same sku in once,2:one by one\n"
				.	"TST   : LTA+ for Test\n"
				.	"PTWBGN: PTW work Begin\n"
				.	"PTWEND: PTW work end\n"
				.	"BC=   : Barcode BC=123456\n"
				.	"SHTnn : Short n\n"
				.	"-----\n"
				;
	}
}
function Do_GetDI($di_name , $stat) // 'A'  , '+'
{
	global $Ap , $Ap_Vd  ;

	//Do Get DI should do
	$Ap_Vd->VDSend('WSS1' ,  "-DI=". $di_name . $stat);
	$do_name = '';
	//Y axis
	if( strpos("-ABCD" ,$di_name )  ){
		//Remember the DI stat
		$Ap->DI_MapY[$di_name]['Stat']	= $stat ;

		if($stat == '+'){
			//Looking for X axis had '+'
			foreach($Ap->DI_MapX as $key => $value){
				if($value['Stat']=='+'){
					$do_name = $di_name.$key ;//Ex "A2"
					break;
				}
			}
		} else {//if($stat == '-')
			return;
		}
	}else if( strpos("-12345" ,$di_name )  )//X axis
	{
		//Remember the DI stat
		$Ap->DI_MapX[$di_name]['Stat']	= $stat ;
		if($stat == '+'){
			//Looking for Y axis had '+'
			foreach($Ap->DI_MapY as $key => $value){
				if($value['Stat']=='+'){
					$do_name = $key.$di_name;//Ex "A2"
					break;
				}
			}
		}else{
			return;
		}
	}else{
		//DI not X,Y axis, Log Error and return
		$Ap_Vd->LogFileView( LOG_SYS , "Warning > vd_ptw1 ::Do_GetDI: DO_name[$do_name] not found." );
		return;
	}
	//Got X+Y axis , set DO lite off..
	# do location put
	if($do_name){
		PTW_LocPutin($do_name);//'A3', DO==Loc_ID
	}
}
function PTW_LocPutin($loc_id) // DO_name == Loc_id
{
	global $Ap , $Ap_Vd  ;


	if($Ap->PTW_Mode==PTW_MODE_IO) {
		// turn the lite Off
		$Ap_Vd->VDSend("WSS1" ,"-LTE-$loc_id" );// "LTE-A3"
		SendDO_to_IOVD($loc_id , '-');
		return;
	}
	//if($loc == $Ap->Loc_I)

	// PTW mode:  one by one | multi-sku in once
	/*
		PTW_MODE_ONCE 		, 1);// one sku one times.
		PTW_MODE_COUNT

			, $Ap->PTW_Mode		= PTW_MODE_ONCE ;	//one by one | multi-sku in once
			, $Ap->Loc_ID							//= DO_name
			, $Qty_ToPut
			, $Qty_Cur


			check is put into right loc ($Ap->Loc_ID)
				if error , show error(ALR*)
				if right, turn off the lite, it matched X-Y ! Turn DO Lite off
				if $Ap->PTW_Mode == 1 ,single piece
					turn next location on :: function SetNextPut();
					?? short?
					turn ALR off
				if $Ap->PTW_Mode == 2 .(Number dispaly always 1)
					if $Qty_ToPut> $Qty_Cur //Not complete
						 toggle loc lite to bilnk.(between blink / on)
					else
						:: function SetNextPut();
					turn ALR off

					SendDO2IoB($do_name, '-');//select IOBX to send.and set DO_Map stat to '-'
					*$Ap_Vd->VDSend("WSS1" , "-DI=" . $di_name. $stat );
					turn next location on , show $Qty_ToPut

					==========================
					instruction digit:
  1 digit: display LED qty
  2 digit: confirmation qty multiple

PTW_Mode == 1
1 single sku scan/ single put： ==> confirm all qty at once
   1. scan sku,
   2. light 1 slot,
   3. optionally display qty of 1 in LED,
   4. block slot light to confirm
   5. loop back to step 1



PTW_Mode == 2, one by one confirm
	scan cku  (select)
	lite Loc, display LED Qty

 	confirm 1 qty at once	,display Qty-- (update db qty --)
 	no scan until Qty == 0

 	next scan sku





scenario 2：
   1. scan sku,
   2. light 1 slot,
   3a. optionally display qty of 1 in LED
     4a. block slot light to confirm one unit at a time
   3b. display qty required in slot in LED,
     4a. block slot light to confirm one unit at a time, LED qty decrement
     4b. block slot light to confirm all qty at once
   5. loop back to step 1




scenario 3:
   1. scan sku,
   2. light 1 slot,
   3a. optionally display qty of 1 in LED
     4a. block slot light to confirm one unit at a time
   3b. display qty required in slot in LED,
     4a. block slot light to confirm one unit at a time, LED qty decrement
     4b. block slot light to confirm all qty at once
   5. loop back to step 2 to dispaly next slot



	*/
	//
	//
}
//-----------------------
function PTW_Begin()// WSS1 -> PTWBGN
{
	global $Ap , $Ap_Vd  ;
	//select 1st wave


	//Init DIs , DOs
	$Ap_Vd->VDSend("WSS1", "-LTA-" );
	//$Ap_Vd->VDSend("IOB1", "-LTA-" );
	SendVDCmd_to_IOVD("-LTA-");

	$Ap_Vd->VDSend("WSS1", "-ALR-" );
	$Ap_Vd->VDSend("WSS1", "-NBR  " );
}
function PTW_End()// WSS1 -> PTWEND
{
	global $Ap , $Ap_Vd  ;
	//Check is all done, or short

}
function GetBarcode($arg_barcode)// "123456789" , WSS=>BC=123456789
{
	global $Ap , $Ap_Vd  ;

	#select * from tb where barcode = '$arg_barcode';
	#
	$Ap->CurBarcode = $arg_barcode;
	$Ap->Qty_ToPut  = $ToPut ;
	$Ap->Qty_HadPut = 0;
	$Ap->Loc_ID 	= $loc_id;


	$Ap_Vd->VDSend("WSS1", "-LTE+" . $Ap->Loc_ID . $Ap->Qty_ToPut );//"LTE+A302"
	$Ap_Vd->VDSend("WSS2", "-NBR"  . $Ap->Qty_ToPut );//"LTE+A302"
	//$Ap_Vd->VDSend("IOB1", "-LTE+" . $Ap->Loc_ID   );
	SendDO_to_IOVD($Ap->Loc_ID  , '+');
}
function DoShort($iShort) //缺貨後數量  SHTnn
{
	global $Ap , $Ap_Vd  ;
}
//-----------------------
function SendDO_to_IOVD($do_name , $stat)
{
	global $Ap , $Ap_Vd  ;

	$vd_iob		= $Ap->DO_Map[$do_name]["IOVD"] ;// iobox vdid
	if(!$vd_iob) {
		$Ap_Vd->LogFileView(LOG_SYS , "Error> PTW1::SendDO2IoB: [$do_name] not found in \$Ap->DO_Map.");
		return;
	}
	$Ap->DO_Map[$do_name]["Stat"] = $stat ;
    $Ap_Vd->VDSend($vd_iob , '-STDO' . $do_name . $stat );//Set DO ex: "STDOA5+"
}
//-----------------------
function SendVDCmd_to_IOVD( $VdCmd)
{
	global $Ap , $Ap_Vd  ;

	foreach($Ap->$IOVD as $key => $value )
	{
		$Ap_Vd->VDSend($vd_iob , '-LTA' . $stat );//Set DO ex: "STDOA5+"
	}
	foreach($Ap->DO_Map as $key => $value ){
		$Ap->DO_Map[$do_name]["Stat"] = $stat ;
	}
}
//====Framework===================================
//This a template.非vd封包
function OnRcvElse($aryPara)
{

	global $Ap , $Ap_Vd  ;

}
?>
