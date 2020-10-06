/*
	Rename to the [web_VDID.js]

	2020.4.08 22:00 V0.01 constructed

	2020.4.10 17:16 V0.02 add 

	ToDo:
		#1.set auto reconnect/heart beat.Note 04/11 11:34

*/

$(document).ready(function () {
    VD_Init( "WEB1" , "ws://127.0.0.1:16140");//WSS1

    //ToDo: #2.set auto reconnect/heart beat.Note 04/11 11:34

   	//捕捉輸入欄位Endter自動觸發 V0.02
	//$(document).on('keypress', '#edtSend2VD', TrigerEnter(e));
});


//必要函式
function Ws_onMsg(event){
	console.log(  event.data );
  	//$("#div_recv").text(  event.data); 	

  	strVD	= event.data;	  	
  	//Dispaly to watch for debug
  	document.getElementById("edtRecvVD").value = strVD;

  	ProcVdMsg(strVD);//main process
}

function btnTestVDClck()
{
	obj 		= document.getElementById("edtTestVD");
	strVD		= obj.value;
	ProcVdMsg(strVD);//main process
}
//Process the VD message,Main process
function ProcVdMsg(strRead)
{
	// main process.
	strCmd = strRead.substr(0,3); //Get cmmd 3 byte.
	switch(strCmd){
		case "WGT" : //  WGT1+12345.67
			iWgt	= strRead.substr(4);
			document.getElementById("edtWeight").value = iWgt ;			
			document.getElementById("edtsWgt").value = iWgt +"\n"  + document.getElementById("edtsWgt").value ;
			break;

		case "BCR" : // 
			strBarcode 	=  strRead.substr(4); 
			document.getElementById("edtBarcode").value = strBarcode ;
			document.getElementById("edtsBcr").value = strBarcode +"\n" + document.getElementById("edtsBcr").value ;
			break;
	}


}

function Send2Wss( )//for test
{	 
	var strBarcode	= document.getElementById("edtSend2VD").value;
	VDSend("WSS1" , "-BC=" + strBarcode );    
	
	//選取Text, 方便下次輸入
	const input = document.getElementById('edtSend2VD');
	input.focus();
	input.select();      
}

//===========================================
// 客製函式
//===========================================
function btnSendVD()
{
  VDSend("WSS1" , "-BC=12345678" );        
}