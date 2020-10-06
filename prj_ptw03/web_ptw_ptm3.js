/*	web_ptw2.js
*
*	2020.5.27 29:30
	2020.08.03  SoundALR();
    2020.08.05  V1.1
        16:37   V1.2 音效檔獨立
*/

msgConfirmFinished='確定批次撿貨結束？\n\n確認結束批次會將還沒完成的品項列入缺貨。';
msgStart="啟動 (撿貨模式 3): 累計模式。";
msgConfirmFinished='Finished this wave？';
msgStart="Start picking mode 3";
document.title = 'NextCS PUT TO WALL (Mode 3)';
document.getElementById("title_PTW").innerHTML =document.title ;
txtSystemCheck = 'System check';
document.getElementById("SystemCheck").innerHTML = txtSystemCheck;
txtCheckDone = 'Check Done';
document.getElementById("CheckDone").innerHTML = txtCheckDone;
txtChangeWave = 'Change Wave';
document.getElementById("ChangeWave").innerHTML = txtChangeWave;
txtPTW_Start = 'Start';
document.getElementById("PTW_Start").innerHTML = txtPTW_Start;
txtPTW_Done = 'Done';
document.getElementById("PTW_Done").innerHTML = txtPTW_Done;
txtPTW_Short = 'Short';
document.getElementById("PTW_Short").innerHTML = txtPTW_Short;
txtShortConfirm = 'Short Confirm';
document.getElementById("ShortConfirm").innerHTML = txtShortConfirm;

$(document).ready(function () {
	//debugger;
	ws="ws://"+ window.location.hostname +":16140";
    VD_Init( "WEB1" ,ws);

	obj = document.getElementById("divShort");
	//obj.disabled = true ;

	document.getElementById("divShort").style.visibility = 'hidden';
	document.getElementById("btnTry").style.visibility   = 'hidden';
	//document.getElementById("btnShort").style.visibility = 'hidden';
		//"display:none

	document.getElementById("di_1x").style.visibility = 'hidden';
	document.getElementById("di_2x").style.visibility = 'hidden';
	document.getElementById("di_3x").style.visibility = 'hidden';
	document.getElementById("di_4x").style.visibility = 'hidden';
	//document.getElementById("di_5x").style.visibility = 'hidden';
	document.getElementById("di_Ax").style.visibility = 'hidden';
	document.getElementById("di_Bx").style.visibility = 'hidden';
	document.getElementById("di_Cx").style.visibility = 'hidden';
	//document.getElementById("di_Dx").style.visibility = 'hidden';

	document.getElementById("di_1y").style.visibility = 'hidden';
	document.getElementById("di_2y").style.visibility = 'hidden';
	document.getElementById("di_3y").style.visibility = 'hidden';
	document.getElementById("di_4y").style.visibility = 'hidden';
	//document.getElementById("di_5y").style.visibility = 'hidden';
	document.getElementById("di_Ay").style.visibility = 'hidden';
	document.getElementById("di_By").style.visibility = 'hidden';
	document.getElementById("di_Cy").style.visibility = 'hidden';
	//document.getElementById("di_Dy").style.visibility = 'hidden';

});
//{{ Set ENTER to send out.
$(document).on('keypress', '#edtBarcode',function(e) { TrigerEnter(e) });

function TrigerEnter(e) {
	if (e.which == 13) {
		//console.log('TrigerEnter');
		//alert("V");
		Send2Wss( );
		return false;
	}
}
//}}

function btnTestIO_Click()
{
	VDSend("WSS1" , "-TST+"  );
    VDSend("WSS1" , "-PTM0"  );//V1.1
}


function  btnTestEnd_Click()
{
	VDSend("WSS1" , "-TST-"   );

	document.getElementById("btnTestIO").disabled = true;
	document.getElementById("btnTestEnd").disabled = true;

	document.getElementById("btnChgWave").disabled = false;
	document.getElementById("btnSortBgn").disabled = false;
	document.getElementById("btnSortEnd").disabled = false;
}

function btnChgWave_Click()
{

}

function btnSortBgn_Click()
{
	VDSend("WSS1" , "-PTWBGN"   );
	document.getElementById("btnSortBgn").disabled = true;
	document.getElementById("btnChgWave").disabled = true;
	document.getElementById("btnSortEnd").disabled = false;
	document.getElementById("edtBarcode").focus();
	//document.getElementById("divShort").style.visibility = 'visible';

	VDSend("WSS1" , "-PTM3"   );
	alert(msgStart);
}

function btnSortEnd_Click()
{


	var yes = confirm(msgConfirmFinished);
	
	if (! yes) {
	    //alert('你按了取消按鈕');
	    return;
	}


	VDSend("WSS1" , "-PTWEND"   );
	document.getElementById("btnSortBgn").disabled = false;
	document.getElementById("btnChgWave").disabled = false;
	document.getElementById("btnSortEnd").disabled = true;

}


//var iShort = document.getElementById('edtShort').value;
var g_iShort = 0;
function buttonAdd1() {
    document.getElementById('edtShort').value = ++g_iShort;
}

function buttonSubtract1() {
    var qtyEl = document.getElementById('edtShort');
    if (qtyEl.value > 0) qtyEl.value = --g_iShort;
}

function btnShort_Click()
{

	var sNbr = zeroPad(document.getElementById('edtShort').value,10);

	if( confirm("確定缺貨？\n要將撿貨數量改成 "+ sNbr +" 嗎？")){
		SetDigiNumber(sNbr);
		VDSend("WSS1" , "-SHT" + document.getElementById('edtShort').value );
	}
}



function Send2Wss( )//for test
{
	var strBarcode	= document.getElementById("edtBarcode").value;
	VDSend("WSS1" , "-BC=" + strBarcode );

	//選取Text, 方便下次輸入
	const input = document.getElementById('edtBarcode');
	input.focus();
	input.select();
}
//必要函式

var iBlinkInterval = 400 	;
var iBlinkState    = 0 		;
var intervalId_Blink		;
var aryLites=[  "lite_A1" ,	"lite_A2" ,	"lite_A3" ,	"lite_A4" ,
				"lite_B1" ,	"lite_B2" ,	"lite_B3" ,	"lite_B4" ,
				"lite_C1" , "lite_C2" , "lite_C3" , "lite_C4" 
			 ];

var 	imgLiteOn 	= "../_img/lite_on.png"
	,	imgLiteOff 	= "../_img/lite_off.png"
	,	imgRedOn 	= "../_img/Red_on.png"
	,	imgRedOff 	= "../_img/Red_off.png"
	,	imgDigi 	= "../_img/Digital-"		//.gif
	;
//V1.2
var audio = document.createElement("audio");
	audio.src = "../_img/beep-1.wav";

function Ws_onMsg(event){
	console.log(  event.data );
  	//$("#div_recv").text(  event.data);

  	strVD	= event.data;
  	//document.getElementById("txtRecvVD").value = strVD;

  	ProcVdMsg(strVD);
}
//===========================
function btnTry_click()
{

	SoundALR();

}
function SoundALR()
{
	audio.currentTime = 0;
	audio.play();

	//方式2
	//var audio = new Audio("hangge.mp3");
	//audio.play();

}

function btnTestVDClck()
{
	obj 		= document.getElementById("txtTestVD");
	strVD		= obj.value;
	ProcVdMsg(strVD);
}

function SendBarcode2Wss( )
{
	var strBarcode	= document.getElementById("edtBarcode").value;
	VDSend("WSS1" , "-BC=" + strBarcode );

	const input = document.getElementById('edtBarcode');
	input.focus();
	input.select();
}

function ProcVdMsg(strRead)
{
  	var 	isON
		,	imgSrc
		,	HtmlId
		;
	strCmd = strRead.substr(0,3); //Get cmmd 3 byte.
	switch(strCmd){
		case "LTE" : //  LTE+A302  ||  LTE-A302
			isON 	= strRead.substr(3,1);
			if(isON == "+"){
				imgSrc = imgLiteOn ;
			}else{
				imgSrc = imgLiteOff ;
			}
			HtmlId	= "lite_" + strRead.substr(4,2); // lite_A2
			var obj = document.getElementById(HtmlId);
			obj.src = imgSrc;//能设置
			/*
			var sNbr= strRead.substr(6,2) ;
			SetDigiNumber(sNbr); //
			//short
			document.getElementById('edtShort').value = sNbr;
			g_iShort = parseInt(sNbr);
			*/
			break;

		case "LTA" : //LTA+  , LTA-
			SetAllLite(strRead.substr(3,1));
			break;
		case "NBR" :
			var iNbr = strRead.substr(3,2)
			SetDigiNumber(iNbr); //	NBR12
			document.getElementById('edtShort').value = iNbr;
			g_iShort = iNbr;
			break;
		case "ALR" :
			//debugger;
			isON = strRead.substr(3,1) ;
 			SetAlert(isON); //ALR+
			break;
		//case "TST" :  Use LTA+, LTA-
		//	TstAllLite(strRead.substr(3,1)); //+, -
		//	break;
		case "MSG" :
			alert(strRead.substr(3 ,1024));
			break;
		case "PPT" :
			VDSend("WSS1" , "-" + strRead   ); //"PPT10000"
			break;
		case "DI=" : //strRead = 'DI=A+',
			di_name = strRead.substr(3,1)  ;//A
			stat 	= strRead.substr(4,1)  ;//+ , -
			SetDI_Lite(di_name , stat );
			break;
		case "ERR" :
			alert(strRead );
			break;
  }
}
//Set DigiNumber
function SetDigiNumber(strNum)
{

	strDigi_1x 	= strNum.substr(0,1);
	strDigi_x1 	= strNum.substr(1,1);

	if(strDigi_1x == " " || strDigi_1x == ""){
		imgDidi_1x 	= "../_img/Digital-space.gif";
	}else{
		imgDidi_1x 	= imgDigi + strDigi_1x  + ".gif" //   ../_img/Digital-"		//.gif
	}

	if(strDigi_x1 == " " || strDigi_x1 == ""){
		strDigi_x1 	= "../_img/Digital-space.gif";
	}else{
		strDigi_x1 	= imgDigi + strDigi_x1  + ".gif" //   ../_img/Digital-"		//.gif
	}

	obj 		= document.getElementById("Num_1x");
　　	obj.src 	= imgDidi_1x;//

	obj 		= document.getElementById("Num_x1");
　　	obj.src 	= strDigi_x1;//
}

function SetAllLite( isON )//LTA+ , LTA+
{
	if(isON == "+" ){
		imgSrc = imgLiteOn ;
	}else{
		imgSrc = imgLiteOff ;
	}

	for (var i = 0; i < aryLites.length; i++) {
	  	obj 	= document.getElementById( aryLites[i] );
　　		obj.src = imgSrc;
	}
}

function SetAlert( strOnOff )
{
	//debugger;
	switch(strOnOff){
		case "*" :
			if(  !intervalId_Blink) {
				iBlinkState 		= 1; //先點亮
				intervalId_Blink 	= window.setInterval(SetAlertBlink, iBlinkInterval );
			}
			SoundALR();
			return;
		case "+" :
			imgSrc = imgRedOn ;
			SoundALR();
			break;
		case "-" :
			imgSrc = imgRedOff ;
			window.clearInterval(intervalId_Blink);
			intervalId_Blink = 0;
			break;
	}
	obj 	= document.getElementById("imgRed_L");
　　	obj.src = imgSrc;
	obj 	= document.getElementById("imgRed_R");
　　	obj.src = imgSrc;

}

function SetAlertBlink()
{
	//debugger;
	obj = document.getElementById("imgRed_L");
　　
	if(	iBlinkState ){
		imgSrc = imgRedOn ;
	}else{
		imgSrc = imgRedOff ;
	}
	obj 	= document.getElementById("imgRed_L");
　　	obj.src = imgSrc;
	obj 	= document.getElementById("imgRed_R");
　　	obj.src = imgSrc;

	iBlinkState = !iBlinkState ;
}
function SetDI_Lite(di_name , stat )
{
	strDi_x = 'di_' + di_name + 'x' ;
	strDi_y = 'di_' + di_name + 'y' ;

	strVisibility = stat == '+' ? 'visible' : 'hidden' ;
	document.getElementById( strDi_x ).style.visibility =  strVisibility;
	document.getElementById( strDi_y ).style.visibility =  strVisibility;

}
function TstAllLite(strOnOff )// "+", "-"
{
	 SetAllLite( strOnOff );//LTA "+"" , LTA "+"
	 SetAlert( strOnOff );
	 if(strOnOff == "+"){
	 	SetDigiNumber("88");
	 }else{
	 	SetDigiNumber("  ");
	 }
}
//===========================================
function zeroPad(nr,base){
  var  len = (String(base).length - String(nr).length)+1;
  return len > 0? new Array(len).join('0')+nr : nr;
}
/*
zeroPad(1,10);   //=> 01
zeroPad(1,100);  //=> 001
zeroPad(1,1000); //=> 0001
*/
