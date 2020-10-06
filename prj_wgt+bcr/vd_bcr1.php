<?php
/*
	vd_bcr1.php

		2020.5.15   Adept fromv  dev: vd_nprt1.php
V1.00	2020.5.16 00:40 1st round test OK.
V1.01	2020.5.16 10:37 use $Ap->DatTail explode $buffer, for different tail: "\r\n" ,"\n" , "\r"
				  11:19 DatLeast ok


*/

require_once ("../_inc/lib_vd2.inc.php");
require_once ("../_inc/lib_nport.inc.php");


class clsApp //Master VD
{
	public    $Vd
			, $RcvBuffer
			, $DatHeader		// ""
			, $DatTail			// "\r\n"
			, $DatLength		// "20"
			, $DatLeast			//Dat must >= DatLeast

			, $DatDataBgn
			, $DatDataEnd
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
		$this->DatLeast		= $cfg['DatLeast'] ;
	}
}



$Ap_Vd 		= new VxD('BCR1', 'OnRcvVdMsg');
$Ap_Nport 	= new VD_NPort($Ap_Vd);
$Ap 		= new clsApp($Ap_Vd );

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
		/*
		封包會分成幾次進來
		1234567890

		1234
		567890

		should be use read buffer.
		*/
		$Ap->RcvBuffer	= $Ap->RcvBuffer . $strRead;
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
	if( $len_buffer <  max ( $Ap->DatLeast , $Ap->DatLength ) ) {
		//if too short, return.Parse Next time.
		$Ap->RcvBuffer = $buffer ;
		return;
	}
	//{{V1.01
	$aryLines	= explode( $Ap->DatTail , $buffer );//分割完不包含 $Ap->DatTail "\r\n"
	$aryCount	= count($aryLines);
	$data_len	= $Ap->DatLength - strlen($Ap->DatTail) ; // - length of "\n" , binary_len = (strlen(bin2hex($data)) / 2)
	$data_len	= max( $Ap->DatLeast , $data_len ); // - length of "\n" , binary_len = (strlen(bin2hex($data)) / 2)

	//}}1.01
	for($i = 0 ; $i < $aryCount ; $i++){
		//先檢查封包長度是否正確
		$len	= strlen($aryLines[$i]);
		if(  $len < $data_len ){
			if($i == ($aryCount-1)){//last packet, not complete yet.
				$Ap->RcvBuffer = $aryLines[$i] ; //save last packet which unfinished
				goto exit_forloop;
			}else{
				continue; // too short ,skip it.
			}
		}
		//只處理ST資料
		$wgt_data	= $aryLines[$i];
		if($Ap->DatLength){
			$iBgn		= $Ap->DatDataBgn  	;
			$iLen		= $Ap->DatDataEnd - $Ap->DatDataBgn	+1 ;
			$wgt_data	= substr( $aryLines[$i] , $iBgn, $iLen  ) ;// get data
		}
		$Ap_Vd->VDSend("WSS1" , "-". $wgt_data);//+1234.56

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
		case 'WRTS'://write string to nport, for test WGT1
			$data 	= substr($Mesg ,  4);
			$ret 	= fwrite($Ap_Nport->NPort_Client , $data);//寫入RS232//寫入RS232
			//echo  __FILE__ ." # ". __LINE__ . ".\n\$Ret=$ret.[$data]\n";

			$Ap_Vd->dbg_print("Ret=$ret",$data);

			break;
		case 'WRTN'://write string + \r\n to nport, for test WGT1
			$data 	= substr($Mesg ,  4). "\r\n";
			$ret 	= fwrite($Ap_Nport->NPort_Client , $data);//寫入RS232
			//echo  __FILE__ ." # ". __LINE__ . ".\n\$Ret=$ret.[$data]\n";

			$Ap_Vd->dbg_print("Ret=$ret",$data);

			break;
		case 'TST1':
			$Ap->RcvBuffer	.= "1234\r\n345678\r\nAbcdefg\r\n" ;
			//進行封包處理
			ParseRcvBuffer();			break;
		case '?'://use '!?' show hint when debugging
			echo "-----\n"
				."WRTS  : send to RS232 (for test WGT1)\n"
				."WRTN  : send to RS232 add CR LF (for test WGT1)\n"
				,"-(Test BCR)-\n"
				."TST1  : Test packet".  '1234\r\n345678\r\nAbcdefg\r\n'  ."\n"
				."  :\n"
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