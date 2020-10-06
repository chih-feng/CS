<?php
/*
	vd_ptw1.php

	Put to wall VD

	2020.4.9 01:20 Begin
	2020.5.4 14:56 restart after Modbus done
	*	.5.7 12:37 PTW_Mode 1st round test OK.
	2020.7.27 10.00 Connect MariaDB,uj
					Re-design PTW_MODE
					Set Blink on/off when continue same Loc_id of barcode.
V1*	2020.07.30 17:37 PTW_mode 1 done.
	2020.08.03 24:00 recovery.
	2020.08.05 10:46 Refine.. Plus Alert sound
V1.1			11:43 Done.
V1.11			16:46 Fix PTM 3 bug
V1.2	08.13		  Enabled WSS2						
	
todo: 'ToWSS2'

*/

define('PTW_MODE_TST' 		, 0);// Just test IO.No DB connection.
define('PTW_MODE_1_JUST' 	, 1);// 1 scan put 1 just .each sku each put. qty_toput always 1 . DB Table translate to each 1 qty each row. 
define('PTW_MODE_MANY' 		, 2);// 1 san put many ,same sku put one times allow more than 1 .
define('PTW_MODE_COUNT' 	, 3);// 1 scan put 1 to count in one trigger at same loc

define('DB_HOST' 			, 'localhost:3306');
define('DB_USER'			, 'pi');
define('DB_PSWD'			, '6tfc') ;

//define('STATUS_SHORT'		, '-1') ;


require_once "../_inc/lib_vd2.inc.php";

class clsApp //Master VD
{
	public    $Vd
			, $DI_MapX							//分成X,Y軸，方便後面處理。
			, $DI_MapY
			, $DO_Map
			, $IOVD 							// Array
			, $PTW_Mode			= PTW_MODE_TST 	//one by one | multi-sku in once

			, $Put_sid							//for update table
			, $Loc_ID							//= DO_name
			, $CurBarcode
			, $Qty_ToPut
			, $Qty_Done
			
			, $Prev_loc_id		= ''
			, $isDO_blink		= 0
			, $Db_conn			= 0					// db connect
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

		$this->Db_conn 	= $this->ConnectDB(DB_HOST, DB_USER , DB_PSWD);

	}
	public function ConnectDB($dbhost, $dbuser, $dbpass)
	{
		$Db_conn = mysqli_connect($dbhost, $dbuser, $dbpass) ;
		if(!$Db_conn)
		{
			$this->Vd->LogFileView( Fatal> LOG_SYS , "Err> Database connect failed.".mysqli_connect_error());
 			$this->Vd->VDSend("WSS1" , '-' . "ERR: 資料庫連線失敗." );
		}
		$ret = mysqli_select_db( $Db_conn, 'ptw03' );
		if(!$ret){
			$this->Vd->LogFileView( LOG_SYS , "Err> Database select DB:ptw03 failed.(".mysqli_error($Db_conn).')' );
 			$this->Vd->VDSend("WSS1" , '-' . "ERR: 選擇資料庫失敗 DB:ptw03." );
		}
		$this->Vd->LogFileView( LOG_SYS , "Database connected.(localhost:ptw03)");
		return $Db_conn ;
    }

}

$Ap_Vd 	= new VxD('PTW1', 'OnRcvVdMsg');
$Ap 	= new clsApp($Ap_Vd );

//$Ap_Vd->FncOnRcvElse			= 'OnRcvElse' ;// after parse VD ,Per Line data.
//$Ap_Vd->FncOnMainLoop			= ''	;
//$Ap_Vd->FncOnClientDisConnect	= ''	;
//$Ap_Vd->FncOnReadEachClient	= '';//'ProcWsMsg';//when just Read socket, Before pase VD

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
	$Mesg_len	= strlen($Mesg);
	$head	= substr($Mesg , 0, 3);
	switch(	$head ){
		case "DI="  :
			//Get DI, Check is X-Y axis match
			Do_GetDI(substr($Mesg , 3, 1) , substr($Mesg, 4 ,1 )   );// 'A' , '+'
			break;
		case "PTM" ://Set PTW_Mode, "PTM0"  , "PTM1" , "PTW2" , "PTW3"
			$Ap->PTW_Mode = substr($Mesg ,  3);
			break;
		case "SDO" ://SDOA1-
			SendDO_to_IOVD(  substr($Mesg , 3,2) ,  substr($Mesg , 5,1)      );
			break;
		case "TST" ://2020.5.62335
			//TST+		:	Test all lites.set all lite on.
			//TST-		:	Test all lites.set all lite off.
			$stat = substr($Mesg , 3, 1);
			if($stat == '+'){
				//$Ap_Vd->VDSend("WSS1", "-LTA+" ); send when ack
				//$Ap_Vd->VDSend("IOB1", "-LTA+" );
				//echo "case: TST, This must send to diff IOB  ";
				SendVDCmd_to_IOVD( "LTA+" );

				$Ap_Vd->VDSend("WSS1", "-ALR+" );
				$Ap_Vd->VDSend("WSS1", "-NBR88" );
				$Ap_Vd->VDSend("WSS2", "-ALR+" );
				$Ap_Vd->VDSend("WSS2", "-NBR88" );
				//ToWSS2
			}else{
				//$Ap_Vd->VDSend("WSS1", "-LTA-??" ); //
				//$Ap_Vd->VDSend("IOB1", "-LTA-" );
				SendVDCmd_to_IOVD( "LTA-");

				$Ap_Vd->VDSend("WSS1", "-ALR-" );
				$Ap_Vd->VDSend("WSS1", "-NBR  " );
				$Ap_Vd->VDSend("WSS2", "-ALR-" );
				$Ap_Vd->VDSend("WSS2", "-NBR  " );
				//ToWSS2
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
		case "ACK" ://[ACKSDOA1+]  ACK SAO AO2 00123  , ACK SDO A1+, DO,AO name 變動長度
			$io_cmd 	= substr($Mesg , 3, 3);
			if( $io_cmd== "SDO" ){
				$DO_name 	= substr($Mesg , 6, $Mesg_len -7 );//ACK SDO A1 +
				$stat		= substr($Mesg , $Mesg_len -1 );

				$Ap_Vd->VDSend("WSS1", "-LTE".$stat.$DO_name );
				$Ap->DO_Map[$DO_name]["Stat"] = $stat ;
			}else if( $io_cmd== "SAO" ){ // ACK STAO AO1 00123
				//$AO_name 	= substr($Mesg , 7, $Mesg_len -12 );
				//$vlaue		= substr($Mesg , $Mesg_len -5 );
				$Ap_Vd->VDSend("WSS1", "-AO=".substr($Mesg , 7) );
			}
			break;
		case "ERR" :
			## ToDo LogFileView , "ERRNAC# ....", NAC : None Ack
			## Emgercy Stop !!??
			## 是否用另一個 ioBox 緊急停止 >> also send to another VD , and the VD send to iob engercy stop
			$Ap_Vd->VDSend("WSS1", "-ERR".substr($Mesg , 7) );
			//ToWSS2  ALR*
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
				.	"ERR   : Send ERR msg to WSS1 n\n"
				.	"-----\n"
				;
	}
}

function GetBarcode($arg_barcode)// "123456789" , WSS=>BC=123456789
{
	global $Ap , $Ap_Vd  ;

	#select * from tb where barcode = '$arg_barcode';
	#
	//Make test data.
	if(substr($arg_barcode, 0,2) == '~~' )
	{
		//Test Data , Get LOC and NBR
		$loc_id 	= substr($arg_barcode, 2,2);
		$qty_ToPut 	= substr($arg_barcode, 4,2);
		$qty_done	= 0;
	}else{//Get data from DB
 
		$sql 	= "select sid , barcode  ,loc_id  , qty_toput- qty_done as qty_toput ,qty_done ,status 
					from put_list
					where barcode = '$arg_barcode' and status < 9";
		$retval = mysqli_query( $Ap->Db_conn , $sql ) ; 
		$row 	= mysqli_fetch_array($retval, MYSQLI_ASSOC) ;
		if(!$row ){
			//not foune
			$msg = "無效條碼[$arg_barcode]。(該品項已經撿過 或 不在此撿貨批次中)";
			$Ap_Vd->VDSend("WSS1", "-ERR: $msg" );
			$Ap_Vd->LogFileView(LOG_SYS, $msg);
			return;
		}
		//print_r($row);
		$Ap->Put_sid 		= $row['sid'];

		$loc_id 			= $row['loc_id'];
		$qty_ToPut 			= $row['qty_toput'];
		$qty_done			= $row['qty_done'];;
		
		mysqli_free_result($retval);
	}
	//
	$Ap->CurBarcode = $arg_barcode;
	$Ap->Qty_ToPut  = sprintf("%02d", $qty_ToPut); ;
	$Ap->Qty_Done	= $qty_done;

	//Turn off the lite, before set
	if($Ap->Loc_ID){
		SendDO_to_IOVD($Ap->Loc_ID  , '-' );
	}
	$Ap->Loc_ID 	= $loc_id;
	//Set Lites
	//Reset Lites.

	//Lite switch on ACK, $Ap_Vd->VDSend("WSS1", "-LTE".$sign . $Ap->Loc_ID   );//"LTE+A302"
	
	$Ap_Vd->VDSend("WSS1",    sprintf( "-NBR%02d"  , $Ap->Qty_ToPut   ) );//"LTE+A302"
	$Ap_Vd->VDSend("WSS1" , "-ALR-") ;
	$Ap_Vd->VDSend("WSS2",    sprintf( "-NBR%02d"  , $Ap->Qty_ToPut   ) );//"LTE+A302"
	$Ap_Vd->VDSend("WSS2" , "-ALR-") ;
	//ToWSS2
	//ToWSS2 $Ap_Vd->VDSend("WSS1", "-NBR"  . $Ap->Qty_ToPut );/
	
	SendDO_to_IOVD($Ap->Loc_ID  , '+');
	if( $Ap->Prev_loc_id == $Ap->Loc_ID ){
		if(! $Ap->isDO_blink){
			SendVDCmd_to_IOVD("BLK$loc_id+" );
			$Ap->isDO_blink = 1;
		}else{
			SendVDCmd_to_IOVD("BLK$loc_id-" );
			$Ap->isDO_blink = 0;
		} 
	}else{
		$Ap->isDO_blink = 0;
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

	switch($Ap->PTW_Mode) {
		case PTW_MODE_TST :
			// turn the lite Off
			//$Ap_Vd->VDSend("WSS1" ,"-LTE-$loc_id" );// "LTE-A3"
			SendDO_to_IOVD($loc_id , '-');
			break;
		case PTW_MODE_1_JUST ://scan 1 put 1 just
			DoPtwMode_put_1_just($loc_id);
			break;
		case PTW_MODE_MANY ://scan 1 put many in one trigger.
			DoPtwMode_put_many($loc_id);
			break;
		case PTW_MODE_COUNT ://scan 1 put 1 to count in one trigger at same loc
			DoPtwMode_put_count($loc_id);
			break;
	}

}
//-----------------------
function 		DoPtwMode_put_1_just($loc_id)// PTW_MODE_1_JUST, scan 1, put 1 just
{
	global $Ap , $Ap_Vd  ;
	
	if($Ap->Loc_ID != $loc_id){  //put to wrong location
		$Ap_Vd->VDSend("WSS1" , "-ALR+") ;
		$Ap_Vd->VDSend("WSS2" , "-ALR+") ;
		//ToWSS2
		return;
	}
	/*
	 *	if loc_id YES
			Update DB, set qty_done	, timemark
			LAT-      //ALR-
			NBR -
			
			tmr_blink off
			Prev_loc_id << 
		else loc_id error
			ALR+	
	 *--------------------
	 *		, $Ap->Loc_ID							//= DO_name
			, $Ap->CurBarcode
			, $Ap->Qty_ToPut
			, $Ap->Qty_HadPut

			, $Ap->rev_loc_id	= ''
	 **/
	//if($Ap->Loc_ID == $loc_id){ //right loc_id
		
	$sql 	= "update put_list set
				  qty_done = qty_done + 1
				, status = case when qty_done = qty_toput then 9 else 3 end
				, timemark = now() 
				where sid = " . $Ap->Put_sid ;
	$retval = mysqli_query( $Ap->Db_conn , $sql ) ;
	if($retval !== TRUE){//update failed.
		$Ap_Vd->LogFileView(LOG_SYS , "Uadate table failed.[$sql]");
		$Ap_Vd->VDSend("WSS1", "-ERR: 資料表寫入失敗。" );
	}
	//ALR-
	//$Ap->Qty_ToPut  = sprintf("%02d", $qty_ToPut); ;

	$qty_toput = $Ap->Qty_ToPut -1 ;
	$Ap_Vd->VDSend("WSS1" ,sprintf( "-NBR%02d" , $qty_toput) ) ;
	$Ap_Vd->VDSend("WSS1" , "-ALR-") ;
	$Ap_Vd->VDSend("WSS2" ,sprintf( "-NBR%02d" , $qty_toput) ) ;
	$Ap_Vd->VDSend("WSS2" , "-ALR-") ;
	//ToWSS2
	
	//tmr_blink off
	if( $Ap->isDO_blink ){
		SendVDCmd_to_IOVD("BLK$loc_id-" );
		//$Ap->isDO_blink = 0; keep statue for next
	}
	SendDO_to_IOVD(  $loc_id , '-' );
	
	$Ap->Prev_loc_id = $loc_id ;
	$Ap->Loc_ID = ''; 
}
//-----------------------
function DoPtwMode_put_many($loc_id)//Put one times, many pieces
{
	global $Ap , $Ap_Vd  ;
 
	if($Ap->Loc_ID != $loc_id){  //put to wrong location
		$Ap_Vd->VDSend("WSS1" , "-ALR+") ;
		$Ap_Vd->VDSend("WSS2" , "-ALR+") ;
		//ToWSS2
		return;
	}
	
	//if($Ap->Loc_ID == $loc_id){ //right loc_id
		
	$sql 	= "update put_list set
				  qty_done = qty_done + ". $Ap->Qty_ToPut. " 
				, status = case when qty_done = qty_toput then 9 else 3 end
				, timemark = now() 
				where sid = " . $Ap->Put_sid ;
	$retval = mysqli_query( $Ap->Db_conn , $sql ) ;
	if($retval !== TRUE){//update failed.
		$Ap_Vd->LogFileView(LOG_SYS , "Uadate table failed.[$sql]");
		$Ap_Vd->VDSend("WSS1", "-ERR: 資料表寫入失敗。" );
	}
	//ALR-
	//$Ap->Qty_ToPut  = sprintf("%02d", $qty_ToPut); ;
	$sql 	= "select sid , barcode  ,loc_id  , qty_toput- qty_done as qty_toput ,qty_done ,status 
				from put_list
				where sid = " . $Ap->Put_sid ;
	$retval = mysqli_query( $Ap->Db_conn , $sql ) ; 
	$row 	= mysqli_fetch_array($retval, MYSQLI_ASSOC) ;
	
	//$loc_id 			= $row['loc_id'];
	$Ap->Qty_ToPut		= $row['qty_toput']; 
	$Ap->Qty_Done		= $row['qty_done'];;
	$Ap_Vd->VDSend("WSS1" ,sprintf( "-NBR%02d" ,  $Ap->Qty_ToPut ) ) ;
	//$Ap_Vd->VDSend("WSS1" ,sprintf( "-NBR%02d" , $Ap->Qty_ToPut - $Ap->Qty_Done - 1 ) ) ;

	$Ap_Vd->VDSend("WSS1" , "-ALR-") ;
	$Ap_Vd->VDSend("WSS2" , "-ALR-") ;
	//425q5we3
	
	//tmr_blink off
	if( $Ap->isDO_blink ){
		SendVDCmd_to_IOVD("BLK$loc_id-" );
		//$Ap->isDO_blink = 0; keep statue for next
	}
	SendDO_to_IOVD(  $loc_id , '-' );
	
	$Ap->Prev_loc_id = $loc_id ;
	$Ap->Loc_ID = ''; 
}
//-----------------------
function DoPtwMode_put_count($loc_id)
{
	
	global $Ap , $Ap_Vd  ;
 
 	if($Ap->Loc_ID != $loc_id){ //Wrong loc_id
		$Ap_Vd->VDSend("WSS1" , "-ALR+") ;
		$Ap_Vd->VDSend("WSS2" , "-ALR+") ;
		//21	q
		return;
	} 
	//if($Ap->Loc_ID == $loc_id){ //right loc_id
	if($Ap->Qty_ToPut==0){
		$Ap_Vd->VDSend("WSS1" , "-ALR+") ;
		$Ap_Vd->VDSend("WSS2" , "-ALR+") ;
		//ToWSS2
		return;
	}

	$sql 	= "update put_list set
				  qty_done = qty_done + 1
				, status = case when qty_done = qty_toput then 9 else 3 end
				, timemark = now() 
				where sid = " . $Ap->Put_sid ;
	$retval = mysqli_query( $Ap->Db_conn , $sql ) ;
	if($retval !== TRUE){//update failed.
		$Ap_Vd->LogFileView(LOG_SYS , "Uadate table failed.[$sql]");
		$Ap_Vd->VDSend("WSS1", "-ERR: 資料表寫入失敗。" );
	}
	
	$sql 	= "select sid , barcode  ,loc_id  , qty_toput- qty_done as qty_toput ,qty_done ,status 
				from put_list
				where sid = " . $Ap->Put_sid ;
	$retval = mysqli_query( $Ap->Db_conn , $sql ) ; 
	$row 	= mysqli_fetch_array($retval, MYSQLI_ASSOC) ;
	
	//$loc_id 			= $row['loc_id'];
	$Ap->Qty_ToPut		= $row['qty_toput']; 
	$Ap->Qty_Done		= $row['qty_done'];;
	$Ap_Vd->VDSend("WSS1" ,sprintf( "-NBR%02d" ,  $Ap->Qty_ToPut ) ) ;
	if($Ap->Qty_ToPut){//還有待撿品項
		$Ap_Vd->VDSend("WSS1" , "-ALR-") ;
		$Ap_Vd->VDSend("WSS2" , "-ALR-") ;
		//ToWSS2
		SendDO_to_IOVD(  $loc_id , '-' );
		SendDO_to_IOVD(  $loc_id , '+' );
		$Ap->Prev_loc_id = $loc_id ;
	}else{
		//tmr_blink off
		if( $Ap->isDO_blink ){
			SendVDCmd_to_IOVD("BLK$loc_id-" );
			//$Ap->isDO_blink = 0; keep statue for next
		}
		SendDO_to_IOVD(  $loc_id , '-' );
		$Ap_Vd->VDSend("WSS1" , "-ALR-") ;
	}
	$Ap->Prev_loc_id = $loc_id ;
}
//-----------------------
function PTW_Begin()// WSS1 -> PTWBGN
{
	global $Ap , $Ap_Vd  ;
	//select 1st wave


	//Init DIs , DOs
	$Ap_Vd->VDSend("WSS1", "-LTA-" );
	//$Ap_Vd->VDSend("IOB1", "-LTA-" );
	SendVDCmd_to_IOVD("LTA-");

	$Ap_Vd->VDSend("WSS1", "-ALR-" );
	$Ap_Vd->VDSend("WSS1", "-NBR  " );
	$Ap_Vd->VDSend("WSS2", "-ALR-" );
	$Ap_Vd->VDSend("WSS2", "-NBR  " );
	//ToWSS2
}
function PTW_End()// WSS1 -> PTWEND
{
	global $Ap , $Ap_Vd  ;
	//Check is all done, or short
	
	$sql 	= "Update put_list set status = 9 , timemark = now()
				where status <> 9;";
	$retval = mysqli_query( $Ap->Db_conn , $sql ) ;
	if($retval !== TRUE){//update failed.
		$Ap_Vd->LogFileView(LOG_SYS , "Uadate table failed.[$sql]");
		$Ap_Vd->VDSend("WSS1", "-ERR: 資料表寫入失敗[$sql]" );
		## stop or go ?
	}

}
function DoShort($iShort) //缺貨後數量  SHTnn, 實際投入數量
{
	global $Ap , $Ap_Vd  ;
	
	$Ap->Qty_ToPut = intval($iShort);
	
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
	//ACK  :: $Ap->DO_Map[$do_name]["Stat"] = $stat ;
	//
    $Ap_Vd->VDSend($vd_iob , '-SDO' . $do_name . $stat );//Set DO ex: "STDOA5+"
}
//-----------------------
function SendVDCmd_to_IOVD( $VdCmd)// -LTA+
{
	global $Ap , $Ap_Vd  ;

	$Ap_Vd->VDSend("IOO1" , '-' .$VdCmd );//
}
//====Framework===================================
//This a template.非vd封包
function OnRcvElse($aryPara)
{

	global $Ap , $Ap_Vd  ;

}
?>
