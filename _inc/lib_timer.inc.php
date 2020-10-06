<?php
/*	lib_timer.inc.php

co-work with VD , do the Timer Jobs 

$IntvlTable = 	[ "Name" 		: "Blink"					
				, "Enable"		: 	1
				, "interval"	: 0.4					//以秒為單位
				, "act_time"	: 1587145871.0168		
				, "func_name" 	: "AlartBlink"			//call-back function name
				]  
$RunAfterStart = //Run function after ap startup, Just run once.
				[ 
           		  "Name" 		: "StartMbusReq" 	
           		, "Enable"		: 0						// 
           		, "After"		: 10					//Run after program start ,seconds 
           		, "act_time"	: 0 					//Note the running time
           		, "func_name" 	: "StartMbusReq" 		//call-back function name
           		] 
 

2020.04.17 23:20
V1.0 04.18 02:40 完成InterVal 
V1.1	   03:00 add FindIntvlTableIdx($name);	
		   14:00 add RunAfterStart()
V1.2	   16:30 Done
		   17:00 add log

*/


class VD_Timer 
{
	public 	 	$Vd 
			,	$IntvlTable
			,	$Start_Time					//Not start time for run clock
			,	$RunAfterTable
			,	$isRunAfterDone 	= 0		//Note for RunAfter all Done
			;

	function __construct( $argVD ) 
	{    
		$this->Vd 						= &$argVD ;
		$argVD->ExtClass["VD_Timer"] 	= &$this ;

		//Load Config
		$vd_file				= "./VD_" . $this->Vd->VD_ID . ".cfg" ;	   	
		$sJason 				= file_get_contents($vd_file );//將整個檔案內容讀入到一個字串中 VD_OOOO.cfg
		$var 					= json_decode($sJason, TRUE);

		$this->IntvlTable		= $var['Interval'] ; 
		$this->RunAfterTable	= $var['RunAfterStart'] ; 

		//Note AP start time.
		$this->Start_Time		= microtime(true)  ;


	}
	//=========================================
	public function RunTimer()
	{
		$this->RunInterval();

		if (! $this->isRunAfterDone) {//如果都跑過就Skip,提高效率
			$this->RunAfterStart();
		}
	}
	//=========================================
	public function RunInterval()
	{
		foreach ($this->IntvlTable as $key => $value) 
		{
			if(! $this->IntvlTable[$key]["Enable"]){
				continue;
			}	
			$utimestamp =  microtime(true)  ;//小數點以下為秒
			
			if( $utimestamp >= $this->IntvlTable[$key]["act_time"])	
			{
				//call_back  
				call_user_func( $this->IntvlTable[$key]["func_name"] ) ;
				
				//$next_act =   $this->IntvlTable[$key]["interval"] + $this->IntvlTable[$key]["act_time"];
				$next_act =   $this->IntvlTable[$key]["interval"] + $utimestamp;//
				$this->IntvlTable[$key]["act_time"]	= $next_act;
			}
		}//foreach ($this->IntvlTable as $key => $value) 

	}
	//=========================================
	public function RunAfterStart()
	{
		
		foreach ($this->RunAfterTable as $key => $value) 
		{
			if(		! $this->RunAfterTable[$key]["Enable"] 
				|| 	$this->RunAfterTable[$key]["act_time"] )
			{
				continue;
			}	
			$utimestamp 	= microtime(true)  ;//Now
			$RunningTime 	= $this->RunAfterTable[$key]["After"] + $this->Start_Time;
			
			if( $utimestamp >= $RunningTime )	
			{
				//call_back  
				call_user_func( $this->RunAfterTable[$key]["func_name"] ) ;
				
				$this->RunAfterTable[$key]["act_time"]	= $utimestamp;
				$this->Vd->LogFileView( LOG_SYS 
										, 'Timer.RunAfter Run: '.$this->RunAfterTable[$key]["func_name"]
										  .'().'
										  ."Running on $utimestamp."
									  );
			}
		}//foreach ($this->RunAfterTable as $key => $value) 

		$isAllDone = 1;
		foreach ($this->RunAfterTable as $key => $value) 
		{
			$isEnable 	= 	$this->RunAfterTable[$key]["Enable"] ;
			$act_time	= 	$this->RunAfterTable[$key]["act_time"];
			if(	$isEnable  && $act_time == 0 )
			{//找還沒執行過的
				$isAllDone = 0;
			}
		}
		$this->isRunAfterDone	=  $isAllDone ;
		if($isAllDone)
			$this->Vd->LogFileView( LOG_SYS , 'Timer->isRunAfterDone = 1');
	}
	//=========================================
	//V1.1 
	public function FindIntvlTableIdx($name) // find "Blink"
	{
		foreach ($this->IntvlTable as $key => $value) 
		{
			if($this->IntvlTable[$key]["Name"] == $name){
				return $key;
			}	
		}//foreach ($this->IntvlTable as $key => $value) 
		return -1 ;
	}
	//=========================================

}


?>
