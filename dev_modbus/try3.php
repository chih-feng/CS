<?php

$ary_bin_data	= [ 1=> 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,6,19,20
				   ,21,22,23,24,25,26,27,28,29,6,31,32,33,34,35,36,37,38,39,40
		  ];

print_r($ary_bin_data);


	
        $array_size     = count($ary_bin_data);
		while($array_size > 6 ){
			$first_pdu_size = $ary_bin_data[6];
			$iPacket_size   = ($first_pdu_size + 6 );
			if($array_size > $iPacket_size )//more than one packet from device(io-Box)
			{
				//make new packet
				for($i = 1 ; $i <= $iPacket_size ; $i++){
					$ary_1_packet [$i]  = $ary_bin_data[$i];
					unset( $ary_bin_data[$i]);
				}
				$ary_bin_data_new = [];
				for( $j=1;   $i <= $array_size ; $i++, $j++)
				{
					$ary_bin_data_new [$j]  = $ary_bin_data[$i];
				}
			}
			echo "\n-----------------------------\n";
			//
			
			Proc_Rsp_by_Packet($ary_1_packet);
			//
			echo '$ary_bin_data_new' . "\n";
			print_r($ary_bin_data_new);
			echo "\n==============================\n";
			
			
			$ary_bin_data   = $ary_bin_data_new ;
			
			$array_size     = count($ary_bin_data);
			
			
			echo "\n";
			echo '$ary_bin_data';
			print_r($ary_bin_data);
			echo "\n";
			echo '$array_size';
			print_r($array_size);
			
		}

    //=======================================================
	function Proc_Rsp_by_Packet($bin_data)//2020.4.25 17:45
    {    
		static $iii=0;
		
		print_r($bin_data);
		
		if($iii++ > 2) die;
	}



?>