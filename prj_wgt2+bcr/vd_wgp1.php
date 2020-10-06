<?php
/*
	vd_wgp1.php

	WGP neans useing POLLing/Response protocol, diff with WGT1

		2020.5.13 16:00 Adept fromv  dev: vd_nprt1.php
		2020.6.10 11:13 Start..
V0.9	2020.6.15 00:05

wgt protocol

COMMAND:
20050026<CR><LF>

RESPONSE:
81050026: 100 kg G<CR><LF>

*/
require_once ("../_inc/lib_vd2.inc.php");
require_once ("../_inc/lib_timer.inc.php"); //timer
require_once ("../_inc/lib_tcp_cts.inc.php");

class clsApp //
{
	public    $Vd
			, $RcvBuffer
			, $DatHeader	// : "81050026:"
			, $DatTail		// : "\r\n"
			, $DatLength	// : 20
			, $StrPosBgn	// ":"
			, $StrPosEnd	// "kg"
			;

	function __construct($Ap_Vd)
	{
		$this->Vd	= &$Ap_Vd;
		$Ap_Vd->ExtClass['Ap']  = &$this  ;

        //Load Config
        $vd_file            = "./VD_" . $this->Vd->VD_ID . ".cfg" ;
        $sJason             = file_get_contents($vd_file );//將整個檔案內容讀入到一個字串中 VD_OOOO.cfg
        $cfg                = json_decode($sJason, TRUE);

        $this->DatHeader	= $cfg['DatHeader'] ;
        $this->DatTail		= $cfg['DatTail'] ;
        $this->DatLength	= $cfg['DatLength'] ;

        $this->StrPosBgn	= $cfg['StrPosBgn'] ;
        $this->StrPosEnd	= $cfg['StrPosEnd'] ;


	}
}


$Ap_Vd 		= new VxD('WGP1', 'OnRcvVdMsg');
$Vd_Timer	= new VD_Timer ($Ap_Vd );

//$Ap_Vd->FncOnRcvElse			= 'OnRcvElse' ; // after parse VD ,Per Line data.
$Ap_Vd->FncOnMainLoop			= 'OnVdMainLoop'	;//for $Vd_Timer->RunTimer()
//$Ap_Vd->FncOnClientDisConnect	= 'OnWsClientDisConnect'	;
$Ap_Vd->FncOnReadEachClient		= 'ProcTcpMsg';//when just Read socket, Before pase VD

$Ap_TCP_cts 	= new VD_TCP_cts($Ap_Vd);
$Ap 		 	= new clsApp($Ap_Vd );

$Ap_Vd->Run();
//==================================================
//Timer sample.
function ProcTcpMsg( $client ,$strRead )
{
	global $Ap , $Ap_Vd ,$Ap_TCP_cts  ;

	if($client == $Ap_TCP_cts->Cts_Client  )
	{
		echo basename(__FILE__) .":". __LINE__ . "> Read from TCP Svr[$strRead]\n";
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
	global $Ap , $Ap_Vd ,$Ap_TCP_cts  ;

	//echo basename(__FILE__) .":". __LINE__ . "> \$Ap->RcvBuffer[". $Ap->RcvBuffer ."]\n";

	//=== Start parse ===
	/*
	RESPONSE:
	81050026: 100 kg G<CR><LF>

	*/

	$buffer 	= $Ap->RcvBuffer ;
	if( $Ap->DatHeader ){
		$buffer 	= strstr($buffer , $Ap->DatHeader );//Header: 81050026:
		//Drop garbage before DAT_HEADER, get [81050026: 100 kg G<CR><LF>]
	}
	$len_buffer	= strlen($buffer) ;
	if( $len_buffer <  $Ap->DatLength ){
		//if too short, return.Parse Next time.
		$Ap->RcvBuffer = $buffer ;
		return;
	}
	/* --------------------------------------------
	切開封包explod , (切割字串會移除)。
	檢查封包長度（完整性）
		長度正確，進行資料判斷，如WGT 取'ST,'開頭
	*/
	$aryLines	= explode( $Ap->DatTail , $buffer );// 分割完不包含 $Ap->DatTail "\r\n"
	$aryCount	= count($aryLines);
	$data_len	= $Ap->DatLength - strlen($Ap->DatTail) ; // - length of $Ap->DatTail , binary_len = (strlen(bin2hex($data)) / 2)

	for($i = 0 ; $i < $aryCount ; $i++){
		//先檢查封包長度是否正確
		$len	= strlen($aryLines[$i]);//without "\r\n" 
		if(  $len != $data_len ){//
			if($i == ($aryCount-1)){//last packet, not complete yet.
				$Ap->RcvBuffer = $aryLines[$i] ; //save last packet which unfinished
				goto exit_forloop;
			}else{
				continue; // too short ,skip it.
			}
		}
		// "81050026: 100 kg G\r\n"
		$sLine		= $aryLines[$i];
		$iPosBgn	= strpos($sLine , $Ap->StrPosBgn) +1 ;
		$iPosEnd	= strpos($sLine , $Ap->StrPosEnd)  ;
		$iLen		= $iPosEnd - $iPosBgn;
		$wgt_data	= substr($sLine , $iPosBgn , $iLen) ;

		$Ap_Vd->VDSend("WSS1" , "-WGP=". $wgt_data);//+1234.56
	}//for($i = 0 ; $i < $aryCount ; $i++)
	exit_forloop :
}
//==================================================
//call back function ：處理送進來的vd封包
function OnRcvVdMsg($aryPara)
{

	global $Ap , $Ap_Vd ,$Ap_TCP_cts ;
	//VD standard packet
	$DstVD		= $aryPara[ VDMSG_DstVD	] 	;
	$SrcVD		= $aryPara[ VDMSG_SrcVD	] 	;
	$Type		= $aryPara[ VDMSG_Type	] 	;	// 'c' cmmd, '-' VD-Data
	$Mesg		= $aryPara[ VDMSG_MESG	] 	;	//

	if( $Ap_Vd->IsDebug ){ print_r($aryPara); }
	//===============================================
	$cmd 		= substr($Mesg , 0, 4);
	switch ($cmd) {
		case 'WRTS'://write string to svr
			$data 	= substr($Mesg ,  4);
			$ret 	= fwrite($Ap_TCP_cts->Cts_Client , $data);
			//echo  __FILE__ ." # ". __LINE__ . ".\n\$Ret=$ret.[$data]\n";

			$Ap_Vd->dbg_print("Ret=$ret",$data);

			break;
		case 'WRTN'://write string + \r\n to svr
			$data 	= substr($Mesg ,  4). "\r\n";
			$ret 	= fwrite($Ap_TCP_cts->Cts_Client , $data);
			//echo  __FILE__ ." # ". __LINE__ . ".\n\$Ret=$ret.[$data]\n";

			$Ap_Vd->dbg_print("Ret=$ret",$data);

			break;
		case "POL+": // begin poll
			SetPollingON();
			break;
		case "POL-": // stop poll
			SetPollingOff();
			break;
		case '?'://use '!?' show hint when debugging
			echo "-----\n"
				."WRTS  : send to RS232\n"
				."WRTN  : send to RS232 add CR LF\n"
				."POL+  : set Polling ON\n"
				."POL-  : set Polling Off\n"
				,"-----\n"
				;
			break;
		default:
			echo "Ext Cmd error.\n";
			break;
	}

}
//=================================================================
function OnVdMainLoop()
{
	global $Ap , $Ap_Vd, $Vd_Timer;
	$Vd_Timer->RunTimer()	;
}

function PollWgt()//function setting in VD_xxxx.cfg "Interval"
{
	global $Ap , $Ap_Vd, $Vd_Timer ,$Ap_TCP_cts ;

	//COMMAND:
	//20050026<CR><LF>

	$data 	= "20050026"."\x0d\x0a" ;
	$ret 	= fwrite($Ap_TCP_cts->Cts_Client , $data);
	$Ap_Vd->dbg_print("Ret=$ret",$data);

}

function SetPollingON()//
{
	global $Ap , $Ap_Vd, $Vd_Timer ;

	$Idx 	= $Vd_Timer->FindIntvlTableIdx("PollWgt");
	if ($Idx != -1) {
		$Vd_Timer->IntvlTable[$Idx]["Enable"] = 1 ;
	}
}
function SetPollingOff()//
{
	global $Ap , $Ap_Vd, $Vd_Timer ;

	$Idx 	= $Vd_Timer->FindIntvlTableIdx("PollWgt");
	if ($Idx != -1) {
		$Vd_Timer->IntvlTable[$Idx]["Enable"] = 0 ;
	}
}



?>