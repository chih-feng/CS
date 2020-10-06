/*	vd_web.inc.js
*
*	2020.4.9 	00:25	V1.0 Done
		 4.11	14:16	V1.1 add Auto reopen WS. 
		 4.12	03:20 	V1.2 修正reopen時，等server太久，會產生多個連線

*
*
*/

var 	VD_id 	= ""
	,	VD_url	= ""
	,	VD_wsocket = 0
	,	ReOpen_IntervalID = 0
	,	iReOpenInterval   = 1500 // mini-sec
	;

function VD_Init( thisVD_id , url ) {
	VD_id 		= thisVD_id;
	VD_url		= url ;
	ws_socket 	= new WebSocket(url); 
	VD_wsocket 	= ws_socket;

	ws_socket.onopen 		= this.Ws_onOpen ; 
	ws_socket.onmessage		= this.Ws_onMsg ;
	ws_socket.onclose 		= this.Ws_onClose ;
}
//=====================================
// code in html
//function Ws_onMsg(event){
//} 
//=====================================
function VDSend(dst_vd_id , str_msg )
{
	strSend 		= "`VD1" ;
	strSend 		= strSend + dst_vd_id ;
	strSend 		= strSend + VD_id  ;
	strSend 		= strSend + str_msg +  "\n" ;

	VD_wsocket.send(strSend );  	
}

function Ws_onOpen(event){
	$("#Status").text( "OPEN");	
	//document.getElementById("td_status").style.backgroundColor = "#00ff00";
	document.getElementById("Status").style.backgroundColor = "#99ff99";
	//v1.1 auto reopn
	if(ReOpen_IntervalID){
		window.clearInterval(ReOpen_IntervalID);
	}
	ReOpen_IntervalID	= 0;	
}      

function Ws_onClose(event){
	//v1.1 auto reopn
	if(!ReOpen_IntervalID){
		$("#Status").text( "Close");
		document.getElementById("Status").style.backgroundColor = "#bbbbbb";
		ReOpen_IntervalID = window.setInterval(Ws_ReOpen, iReOpenInterval );			
	}	
}

var cReOpenMark = "*" ;
function Ws_ReOpen()//v1.1 auto reopn
{
	if(!ReOpen_IntervalID) return ;
	if( VD_wsocket.readyState < 2 ) return;
	/* v1.2
		0 :   "CONNECTING" ;
        1 :   "OPEN" ;
        2 :   "CLOSING" ;
        3 :   "CLOSE" ;
	*/
	cReOpenMark = (cReOpenMark == "*" )? "-" : "*";

	$("#Status").text( "Close "+cReOpenMark);	

	VD_Init( VD_id , VD_url ) ;
}










	