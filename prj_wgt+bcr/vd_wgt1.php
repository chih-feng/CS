<?php
/*
	vd_wgt1.php

		2020.5.13 16:00 Adept fromv  dev: vd_nprt1.php
V1.0	2020.5.15 12:31 Test 1st round OK.
V1.01	2020.5.16 10:37 use $Ap->DatTail explode $buffer
wgt protocol
http://www.casscale.co.nz/wp-content/uploads/2014/04/ED-H-Weighing-Scale-Owner-Manual.pdf

01 02 03 04 05 06 07 08 09 10 11 12 13 14 15 16 17 18 19 20
HEAD1  , HEAD2  ,  +  1  2  3  4  5  .  6 [  Unit   ] 0D 0A
 S  T  ,  G  S  ,  +  1  2  3  4  .  5  6     k  g    0D 0A

"ST,GS,+1234.56 kg \r\n"

HEAD1 (2 BYTES)
	OL-OverLoad,
	ST-Display is STable
	US-Display is UnStable

HEAD2 (2BYTES)
	GS-Gross Weight
	NT-NET Mode

DATA (8BYTES)
	2D (HEX) =”-” (MINUS)
	20 (HEX) =” ” (SPACE)
	2E (HEX) =”.” (DECIMALPOINT)

UNIT (4BYTE):
	g -20 ( HEX ) ; 67 ( HEX ) ; 20 ( HEX ) ; 20 ( HEX )
	Ib-20 ( HEX ) ; 6C ( HEX ) ; 62 ( HEX ) ; 20 ( HEX )
	kg-20 ( HEX ) ; 6B ( HEX ) ; 67 ( HEX ) ; 20 ( HEX )
	oz-20 ( HEX ) ; 6F ( HEX ) ; 7A ( HEX ) ; 20 ( HEX )

*/

require_once ("../_inc/lib_vd2.inc.php");
require_once ("../_inc/lib_nport.inc.php");

class clsApp //Master VD
{
	public    $Vd
			, $RcvBuffer
			, $DatHeader		// "ST"
			, $DatTail			// "0x0D0A"
			, $DatLength		// "20"

			, $DatDataBgn
			, $DatDataEnd
			, $DatUnitBgn
			, $DatUnitEnd

			;
	function __construct($Ap_Vd)
	{
		$this->Vd	= $Ap_Vd;
		$Ap_Vd->ExtClass['Ap']  = &$this  ;

        //Load Config
        $vd_file            = "./VD_" . $this->Vd->VD_ID . ".cfg" ;
        $sJason             = file_get_contents($vd_file );//將整個檔案內容讀入到一個字串中 VD_OOOO.cfg
        $cfg                = json_decode($sJason, TRUE);

        $this->DatHeader	= $cfg['DatHeader'] ;
        $this->DatTail		= $cfg['DatTail'] ;
        $this->DatLength	= $cfg['DatLength'] ;
        $this->DatDataBgn	= $cfg['DatDataBgn'] ;
        $this->DatDataEnd	= $cfg['DatDataEnd'] ;
        $this->DatUnitBgn	= $cfg['DatUnitBgn'] ;
        $this->DatUnitEnd	= $cfg['DatUnitEnd'] ;
	}
}


$Ap_Vd 		= new VxD('WGT1', 'OnRcvVdMsg');
$Ap_Nport 	= new VD_NPort($Ap_Vd);
$Ap 		= new clsApp  ($Ap_Vd );

//$Ap_Vd->FncOnRcvElse			= 'OnRcvElse' ; // after parse VD ,Per Line data.
//$Ap_Vd->FncOnMainLoop			= 'OnVdMainLoop'	;
//$Ap_Vd->FncOnClientDisConnect	= 'OnWsClientDisConnect'	;
$Ap_Vd->FncOnReadEachClient		= 'ProcNPortMsg';//when just Read socket, Before pase VD

$Ap_Vd->Run();
//==================================================
//Timer sample.
function ProcNPortMsg( $client ,$strRead )
{
	global $Ap , $Ap_Vd ,$Ap_Nport  ;

	if($client == $Ap_Nport->NPort_Client  )
	{
		echo basename(__FILE__) .":". __LINE__ . "> Read from Nport[$strRead]\n";
		//封包會分成幾次進來，先放入buffer集結
		$Ap->RcvBuffer	= $Ap->RcvBuffer . $strRead;
		//進行封包處理
		ParseRcvBuffer();

		return 1;//Must Return 1 ,IF processed.;
	}
	//處理過封包，return 1, 否則 return 0 留給預設程式處理。
	return 0;//Did not processed.
}
//==================================================
function ParseRcvBuffer()
{
	global $Ap , $Ap_Vd ,$Ap_Nport  ;

	echo basename(__FILE__) .":". __LINE__ . "> \$Ap->RcvBuffer[". $Ap->RcvBuffer ."]\n";

	//=== Start parse ===
	$buffer 	= $Ap->RcvBuffer ;
	if( $Ap->DatHeader ){
		$buffer 	= strstr($buffer , $Ap->DatHeader );//Drop garbage before DAT_HEADER
	}
	$len_buffer	= strlen($buffer) ;
	if( $len_buffer <  $Ap->DatLength ){
		//if too short, return.Parse Next time.
		$Ap->RcvBuffer = $buffer ;
		return;
	}

	$aryLines	= explode( $Ap->DatTail , $buffer );//V1.01 分割完不包含 $Ap->DatTail "\r\n"
	$aryCount	= count($aryLines);
	$data_len	= $Ap->DatLength - strlen($Ap->DatTail) ; // - length of $Ap->DatTail , binary_len = (strlen(bin2hex($data)) / 2)

	for($i = 0 ; $i < $aryCount ; $i++){
		//先檢查封包長度是否正確
		$len	= strlen($aryLines[$i]);
		if(  $len != $data_len ){//分割完不包含 $Ap->DatTail "\r\n"
			if($i == ($aryCount-1)){//last packet, not complete yet.
				$Ap->RcvBuffer = $aryLines[$i] ; //save last packet which unfinished
				goto exit_forloop;
			}else{
				continue; // too short ,skip it.
			}
		}
		//只處理ST資料
		if( substr($aryLines[$i], 0 ,3 ) == "ST,"  ){//only get "STable Data"
			$iBgn		= $Ap->DatDataBgn  	;
			$iLen		= $Ap->DatDataEnd - $Ap->DatDataBgn	+1 ;
			$wgt_data	= substr( $aryLines[$i] , $iBgn, $iLen  ) ;// get data
			$Ap_Vd->VDSend("WSS1" , "-". $wgt_data);//+1234.56
		}
	}//for($i = 0 ; $i < $aryCount ; $i++)
	exit_forloop :
}
//==================================================
//call back function ：處理送進來的vd封包
function OnRcvVdMsg($aryPara)
{

	global $Ap , $Ap_Vd ,$Ap_Nport ;
	//VD standard packet
	$DstVD		= $aryPara[ VDMSG_DstVD	] 	;
	$SrcVD		= $aryPara[ VDMSG_SrcVD	] 	;
	$Type		= $aryPara[ VDMSG_Type	] 	;	// 'c' cmmd, '-' VD-Data
	$Mesg		= $aryPara[ VDMSG_MESG	] 	;	//

	if( $Ap_Vd->IsDebug ){ print_r($aryPara); }
	//===============================================
	$cmd 		= substr($Mesg , 0, 4);
	switch ($cmd) {
		case 'WRTS'://write string to nport
			$data 	= substr($Mesg ,  4);
			$ret 	= fwrite($Ap_Nport->NPort_Client , $data);//寫入RS232
			//echo  __FILE__ ." # ". __LINE__ . ".\n\$Ret=$ret.[$data]\n";
			$Ap_Vd->dbg_print("Ret=$ret",$data);
			break;
		case 'WRTN'://write string + \r\n to nport
			$data 	= substr($Mesg ,  4). "\r\n";
			$ret 	= fwrite($Ap_Nport->NPort_Client , $data);//寫入RS232
			//echo  __FILE__ ." # ". __LINE__ . ".\n\$Ret=$ret.[$data]\n";
			$Ap_Vd->dbg_print("Ret=$ret",$data);
			break;
		case 'TST1'://write string + \r\n to nport
			$Ap->RcvBuffer	.= "3789\r\nST,GS,+1234.56 kg \r\nST,GS,+1234.";
			//進行封包處理
			ParseRcvBuffer();
			break;
		case 'TST2'://write string + \r\n to nport
			$Ap->RcvBuffer	.= "2234ST,GS,+9876.23 kg \r\nST,GS,+2467.ST,GS,+67896.23 kg \r\n";
			//進行封包處理
			ParseRcvBuffer();
			break;
		case  '':

			break;
		case '?'://use '!?' show hint when debugging
			echo "-----\n"
				."WRTS  : send to RS232\n"
				."WRTN  : send to RS232 add CR LF\n"
				."TST1  : send test packet for test.\"1234\\r\\nST,GS,+1234.56 kg ST,GS,+1234.\"\n"
				."TST2  : ". '2234ST,GS,+9876.23 kg \r\nST,GS,+2467.ST,GS,+67896.23 kg \r\n' ."\n"
				,"-(Test BCR)-\n"
				,"-----\n"
				;
			break;
		default:
			echo "Ext Cmd error.\n";
			break;
	}

}
//=================================================================

?>