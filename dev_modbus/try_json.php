<?php


$sJason 	= file_get_contents('./VD_MBS1-TST.cfg' );//將整個檔案內容讀入到一個字串中 VD_OOOO.cfg

$var 		= json_decode($sJason, TRUE);

 
print_r($var);

		if(json_last_error() !=JSON_ERROR_NONE){
			echo  "Fatal>  $vd_file Json Error :". json_last_error_msg();
			die("\n\n");
		}
echo "----\n";
echo '['.count($var["AO_Map"]).']';

echo "\n====\n";
/*
$arr = array (
	'A' => 1 
,	'B' => 2

, 	'C'=> array(
				'C1' => array( "c1-1" => 1,2,3)
			,	'C2' => array( 4,5,6)
		  )
);

$var 		= json_encode($arr);
print_r($var)


===================================
{
	  "A":1
	, "B":2
	, "C": 
	{
		  "C1":[1,2,3]
		, "C2":[4,5,6]
	}
}


{
"A":1
,"B":2
,"C":{
		 "C1":{		"c1-1":1
				,	"0":2
				,	"1":3
				}
		,"C2":[4,5,6]
	}


}



*/



?>