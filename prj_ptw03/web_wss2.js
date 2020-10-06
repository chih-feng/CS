/*	web_ptw2.js
*
*	2020.5.27 29:30
	2020.08.03  SoundALR();
    2020.08.05  V1.1
        16:37   V1.2 音效檔獨立
*/

$(document).ready(function () {
	//debugger;
	ws="ws://"+ window.location.hostname +":16141";
    VD_Init( "WEB1" ,ws);

	document.getElementById("divDebug").style.visibility = 'hidden';

});


var iBlinkInterval = 400 	;
var iBlinkState    = 0 		;
var intervalId_Blink		;


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



function ProcVdMsg(strRead)
{
  	var 	isON
		,	imgSrc
		,	HtmlId
		;
	strCmd = strRead.substr(0,3); //Get cmmd 3 byte.
	switch(strCmd){



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
