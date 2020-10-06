/*	web_ptw2.js
*
*	2020.5.27 29:30
	ToDo:
		Show DI 
*/

$(document).ready(function () {
	//debugger;
    VD_Init( "WEB1" , "ws://127.0.0.1:16140");
	obj = document.getElementById("divShort");
	//obj.disabled = true ;

	document.getElementById("divShort").style.visibility = 'hidden';
	//"display:none
	document.getElementById("btnTry").style.visibility = 'hidden';//this button for debug

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
	document.getElementById("divShort").style.visibility = 'visible';
}

function btnSortEnd_Click()
{
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
	VDSend("WSS1" , "SHRT" + document.getElementById('edtShort').value );   

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
var aryLites=[  "lite_A1" ,	"lite_A2" ,	"lite_A3" ,	"lite_A4" ,	"lite_A5" ,
				"lite_B1" ,	"lite_B2" ,	"lite_B3" ,	"lite_B4" ,	"lite_B5" ,
				"lite_C1" , "lite_C2" , "lite_C3" , "lite_C4" , "lite_C5" ,
				"lite_D1" ,	"lite_D2" ,	"lite_D3" ,	"lite_D4" ,	"lite_D5" 
			 ];

var 	imgLiteOn 	= "../_img/lite_on.png"
	,	imgLiteOff 	= "../_img/lite_off.png"
	,	imgRedOn 	= "../_img/Red_on.png"
	,	imgRedOff 	= "../_img/Red_off.png"
	,	imgDigi 	= "../_img/Digital-"		//.gif
	;

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
	alert(imgDigi);
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

			var sNbr= strRead.substr(6,2) ;
			SetDigiNumber(sNbr); //
			//short
			document.getElementById('edtShort').value = sNbr;
			g_iShort = parseInt(sNbr);
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
			return;
		case "+" :
			imgSrc = imgRedOn ;
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

