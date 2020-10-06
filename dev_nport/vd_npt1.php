<?php
/*
	vd_npt1.php

	Templete of VD

	2020.5.10 23:50 Start

	2020.5.13 01:00 Function ready
		V1.0 But , receive data will cut string.
			ex. send from port 1, VD receive from port.
				port 1 send'1234567890123456789'
				port 2 VD received 2~3 packet => '1234' , '567890' , '123456789'

				So should reslove depend on protocol,
				ex. wgt protocol
					"ST,GS,123456.78\r\n"    [ CR ,LF ]

	Pass to vd_WGT1.php


*/

require_once ("../_inc/lib_vd2.inc.php");
require_once ("../_inc/lib_nport.inc.php");

class clsApp //Master VD
{
	public    $Vd
			, $RcvBuffer
			;
	function __construct($Ap_Vd)
	{
		$this->Vd	= &$Ap_Vd;
		$Ap_Vd->ExtClass['Ap']  = &$this  ;
	}
}


$Ap_Vd =	new VxD('NPT1', 'OnRcvVdMsg');

//$Ap_Vd->FncOnRcvElse			= 'OnRcvElse' ; // after parse VD ,Per Line data.
//$Ap_Vd->FncOnMainLoop			= 'OnVdMainLoop'	;
//$Ap_Vd->FncOnClientDisConnect	= 'OnWsClientDisConnect'	;
$Ap_Vd->FncOnReadEachClient		= 'ProcNPortMsg';//when just Read socket, Before pase VD

$Ap_Nport 	= new VD_NPort($Ap_Vd);
$Ap 		= new clsApp($Ap_Vd );

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
	/*
	去除前置垃圾（沒有Header)
		如果太短，直接返回，下次在說。
	*/
	$buffer 	= $Ap->RcvBuffer ;
	$buffer 	= strstr($buffer , $Ap->DatHeader );//Drop garbage before DAT_HEADER
	if(	strlen($buffer)  <  $Ap->DatLength ){
		//if too short, return.Parse Next time.
		$Ap->DatHeader = $buffer ;
		return;
	}
	/* --------------------------------------------
	切開封包explod , (切割字串會移除)。
	檢查封包長度（完整性）
		長度正確，進行資料判斷，如WGT 取'ST,'開頭
	*/
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
			$ret 	= fwrite($Ap_Nport->NPort_Client , $data);
			//echo  __FILE__ ." # ". __LINE__ . ".\n\$Ret=$ret.[$data]\n";

			$Ap_Vd->dbg_print("Ret=$ret",$data);

			break;
		case 'WRTN'://write string + \r\n to nport
			$data 	= substr($Mesg ,  4). "\r\n";
			$ret 	= fwrite($Ap_Nport->NPort_Client , $data);
			//echo  __FILE__ ." # ". __LINE__ . ".\n\$Ret=$ret.[$data]\n";

			$Ap_Vd->dbg_print("Ret=$ret",$data);

			break;
		case '?'://use '!?' show hint when debugging
			echo "-----\n"
				."WRTS  : send to RS232\n"
				."WRTN  : send to RS232 add CR LF\n"
				//."  :\n"
				//."  :\n"
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