/*	web_ptw1.js
*
*	2020.4.09 00:25	 
*	2020.4.09 23:16	V0.8 Done
*	2020.4.10 14:43 V0.9 只差讀條碼後無法判斷 Enter 自動送出 
		.4.11 12:00 V1.0 #1.送出條碼後，自動全選，方便下次輸入。
	ToDo:
		Show DI 
*/

$(document).ready(function () {
	//debugger;
    VD_Init( "WEB1" , "ws://127.0.0.1:16140");
    //捕捉條碼的Edter
	/*
	$(document).on 	('keypress', '#edtBarcode', function(e) {
													if (e.which == 13) {
														//alert("V");
														Send2Wss( );
														return false;
													}
												}
				)
	*/

});
/* this OK
$(document).on('keypress', '#edtBarcode', function(e) {
 		if (e.which == 13) {
 			console.log('test');
  			//action below 
  			SendBarcode2Wss( ); 
  			return false;
 		}
});
*/
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

/*

$("#edtBarcode").keypress(function(e){

  code = (e.keyCode ? e.keyCode : e.which);

  if (code == 13)

  {
      //targetForm是表單的ID
      $("targetForm").submit();
  }

});
*/
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
  	document.getElementById("txtRecvVD").value = strVD;

  	ProcVdMsg(strVD);
}

function btnTestIO_Click()
{
	VDSend("WSS1" , "-TST"  );   
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
			SetDigiNumber(strRead.substr(6,2)); //
			break;

		case "LTA" : //LTA+  , LTA-
			SetAllLite(strRead.substr(3,1)); 
			break;
		case "NBR" : 	
			SetDigiNumber(strRead.substr(3,2)); //	NBR12
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
// 
//===========================================
function btnBeginClick()
{
	VDSend("WSS1" , "-CMDBegin"   );   
	 
	const input = document.getElementById('edtBarcode');
	input.focus();
	input.select();    	
}
//===========================================
function btnDoneClick()
{
	VDSend("WSS1" , "-CMDDone"   );    
}
