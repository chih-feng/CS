<?php
/* lib_modbus.inc.php

	Modbus library .

	2020.04.19	18:46 Begin
    2020.04.25  21:35 Base function &
                V0.1  FC1 send & Response process codding done, UT not yet.
    2020.04.27  01:29 V0.4 Finish FC1, FC5
          4.27  14:00 6#  # Rsp 會有兩個以上封包同時進入<需要逐一 依據FC消化, get length from tcp_header [PDU_length]
         *V0.6  20:20 FC1, 5 Done.(get DIs, set DO)

V0.9    05.04 01:00 FC 04,06 Done
V9.1    05.04 14:30 rewrite Get_DI_All, Get_AI_All for polling
V1.0    05.04 16:00 V1.0 done, begin to use in project 'prj_ptw01'
V1.1*   05.07 00:05 add Set_DO_All() 應用很少，先用迴圈處理, FC-15 先不用
        05.28 16:00 Fix Req_Stored_Heap memory leaking..
v1.11*              10# $this->Unset_Req_Stored_Heap();
V2.0    06.24 12:00 FC15 , Send_Queue, ACK . Begin...
        07.17 22:19 Test DI,AI,DO,AO Pass 1 OK.
V2.1    07.20 15:00 Refine: Polling_IO -> Polling_DI, Polling_AI (AI,DI polling interval not all the same)
V2.2    07.21 00:53      Refine:IO Command set to 3 byte,
                            STDO -> SDO  
                            GTDI -> GDI
                            STAO -> SAO
                            GTAI -> GAI
                            
                            read/write range, Address => 3 Byte, Value => 5 Byte(0~65535)
                            RDI : RDI 123 456  => Read grom 123 to 456
                            RAI
                            WDO 應該用不到
                            WAO

Mbs_tmr_chk_timeout()
    處理超時重送
    重送三次，則傳回 'ERR' ,讓VD Master 可以處理警示。
    如果情況必要，則由VD Master 緊急停止指令給另外一個 ioBox , 進行緊急停止。

ToDo:
1# ?是否用 decbin() 用 '11100111' 存放？ => use array.
2# InData 要什麼資料結構？ Done, => Array.
3# ToDo : Log_ "MBS1_20200425_mbs_o.log" . Done. 20202.4.27

4# Timer do polling  Send_Req($this->PollingDI_Data ); DONE
5#: Exception of fwrite( $this->MbsClient , $bin_data); !?
7# Test Rsp Error status GetModbus_ErrorMsg()

8# change default polling to DI_polling , AI_polling, add member "$PollingAI_Data"
9# if wana read parts of AI,
    unset heap should move to parse 'FC_04_Rsp' finished.
    and should note send out packet to get which AI
10# unset  Req_Stored_Heap when more than 100,Done V1.11
11# Queue Send msg, sotred forward send data , wait resp, check timeout, porcess resp.

AI,AO data range 0~65535, Use 5 char
    STAOaoname65535
    
    
    程式會計算長度 ＝總長度減去 9 ('STAO' ..... '65535' or '00123'



*/


define ("REQ_HEAP_MAX"  , 300       );
define ("REQ_HEAP_KEEP" , 30        );
define ("LEN_MBS_IO_CMD"    , 3         );//V2.1 SDO , GDI
class VD_Modbus {
    public        $Vd
                , $Mbs_Name
                , $Mbs_IP
                , $Mbs_port
                , $MbsClient
                , $FncOnDI_Change   = ''    //裡面 Send DI to Master VD, 依據專案改封包格式
                , $FncOnAI_Change   = ''    //裡面 Send DI to Master VD, 依據專案改封包格式
                , $FncOnMbsACK      = ''    //ACK 回送
                , $DI_Map
                , $DO_Map
                , $AI_Map                   //AI,AO keep value in this array.
                , $AO_Map
                , $ioTimeout        = 0.05
                , $MaxRetry_times   = 3        //V2.0
                ;

    private       $Buffer_DO                // Byte Array
                , $Buffer_DI                // Byte Array, Keep Last DI info.


                /*  Buffer用陣列，每個Byte獨立放置，避免字串遇到 ＼0
                    DO 在送出時，用Hex2bin 再 chr(65).chr(66) 連起來 'A'.'B'
                    或是pack
                    $data = pack("C*",23,17,208);//連接程三個Byte buffer.

                    $arr = unpack("C*",$data);//拆成陣列
                    foreach ($arr as $key => $value) {
                         echo "\$arr[$key] = $value\n";
                    }

                    producing the following output:

                    $arr[1] = 23
                    $arr[2] = 17
                    $arr[3] = 208

                    ＊＊＊
                    Buffer 不同專案，Byte數不同，用字串產生 $cmd = "pack('C*'," ...Buffer成員... " ,);"
                    再用eval($cmd) ;

                    pack 可以包成Hex , 高低Byte.

                    ==== array
                    $a = array( 65, 66, 67 );
                    $packed = pack("c*", ...$a);

                    n   unsigned short (always 16 bit, big endian byte order)
                    v   unsigned short (always 16 bit, little endian byte order)

                */
                , $TxID             = 1     // Tcp header
                , $Log_out          = ''
                ;
    //V2.0 Move bak for dump view.            
    public
                  $PollingDI_Data   = 0     //設定預設Polling資料，省去重複計算
                    //定時polling的資料範圍應該都一樣。預設全抓
                    //特殊專案再另外處理，或者開另一個VD抓不同區段DI
                , $PollingAI_Data   = 0
        
                , $Mbs_Cmd_Queue = array()
                    /*
                      tx_id => array(
                            "send_times"    =>  0               // +1 when send.
                          , "VDCmd"         =>  $arg_VDCmd 
                          , "packed_data"   =>  $ary_bin_data  
                      );
                     
                     */               //}}V2.0
                , $Mbs_Timer    = array(
                                             "isOnTheWay"       => 0
                                            ,"TimeOfTimeout"    => 1.0168   // utime timestamp
                                            ,"Timeout_Interval" => 0.3      // sec
                                        )

                ;

    function __construct( $argVD , $Modbus_name)
    {
		$this->Vd 	                    = &$argVD ;
		$argVD->ExtClass[$Modbus_name]  = &$this  ;
        $this->Mbs_Name                 = $Modbus_name ;

        //Load Config
        $vd_file			= "./VD_" . $this->Vd->VD_ID . ".cfg" ;
		$sJason 			= file_get_contents($vd_file );//將整個檔案內容讀入到一個字串中 VD_OOOO.cfg
		$cfg 				= json_decode($sJason, TRUE);

        $this->Mbs_IP       = $cfg['Mbs_IP'] ;
        $this->Mbs_port     = $cfg['Mbs_port'] ;

        //Init class
        
        //V2.0 Open in function
        $this->OpenMbsSocket();
        
        //================================
        if( $cfg['IO_Map_File']  == '*'){//點數太多時<另開檔案設定參數
            $ip_map =  $cfg;
        }else{
            # Load From another JSON file.
            $sJason 	= file_get_contents( $cfg['IO_Map_File'] );//將整個檔案內容讀入到一個字串中 VD_OOOO.cfg
            $ip_map 	= json_decode($sJason, TRUE);
        }
        //Load DI/O mapping.
        $this->DI_Map   = $ip_map['DI_Map'] ;
        $this->DO_Map   = $ip_map['DO_Map'] ;
        //AI, AO
        $this->AI_Map   = $ip_map['AI_Map'] ;
        $this->AO_Map   = $ip_map['AO_Map'] ;
        //
        $DI_points  = count( $this->DI_Map );
        $DO_points  = count( $this->DO_Map );
        //Create DI Bytes Buffer
        $DI_Bytes   = $DI_points / 8 ;
        $DI_Bytes   = (int)$DI_Bytes + ( $DI_points % 8 ? 1 : 0 );
        for ($i=0; $i < $DI_Bytes ; $i++) {
            $this->Buffer_DI [] = 0; // ex.[  0,0,0,0,0,0,0,0 ]  //8 Bytes , 8*8 64 DO
        }
        //Create DO Bytes Buffer
        $DO_Bytes   = $DO_points / 8 ;
        $DO_Bytes   = (int)$DO_Bytes + ( $DO_points % 8 ? 1 : 0 );
        for ($i=0; $i < $DO_Bytes ; $i++) {
            $this->Buffer_DO [] = 0; // ex.[  0,0,0,0,0,0,0,0 ]  //8 Bytes , 8*8 64 DO
        }
        //Deafult Polling Range
        $PollRange_Start        = $cfg['PollRange_Start'] ;
        $PollRange_Length       = $cfg['PollRange_Length'] ;
        if ($PollRange_Length == "*") {
            $PollRange_Length     = $DI_points;
        }
        //V2.0
        if(!empty($cfg['ioTimeout'] )){
            $this->ioTimeout        = $cfg['ioTimeout'] ;
        }
        if(!empty($cfg['MaxRetry_times'] )){
            $this->MaxRetry_times   = $cfg['MaxRetry_times'] ;
        }
        
        //Make Rugular Polling Packet.=================================
        //不包含 前面的 TxID, 先做好資料提高效率.
        //---DI---
        $this->PollingDI_Data  = pack(    "C*"
                                        , 0,0,0                 // always 0, 3 times
                                        , 6                     // PDU length
                                        //PDU
                                        , 1     //addr
                                        , 2     // FC
                                        //$start_addr $length 自己Shift決定Hi-Byte 位置
                                        , $PollRange_Start  >> 8  &  0xFF
                                        , $PollRange_Start        &  0xFF
                                        , $PollRange_Length >> 8  &  0xFF
                                        , $PollRange_Length       &  0xFF
                                    );

        $str    = print_r(  unpack("C*" , $this->PollingDI_Data ) , TRUE);
        $argVD->LogFile(LOG_SYS , "  VD_Modbus->PollingDI_Data (Not include TxID)\n". $str);//Log for debug.
        //---AI---
        $cnt    = count($this->AI_Map);
        $this->PollingAI_Data  = pack(    "C*"
                                        , 0,0,0                 // always 0, 3 times
                                        , 6                     // PDU length
                                        //PDU
                                        , 1     //addr
                                        , 4     // FC
                                        //$start_addr $length 自己Shift決定Hi-Byte 位置
                                        , 0                     //start address
                                        , 0
                                        , $cnt >> 8  &  0xFF    //read length
                                        , $cnt       &  0xFF
                                    );

        $str    = print_r(  unpack("C*" , $this->PollingAI_Data ) , TRUE);
        $argVD->LogFile(LOG_SYS , "  VD_Modbus->PollingAI_Data (No TxID)\n". $str);//Log for debug.

        //$Log_Out
        $strToday		= date("Ymd");//V2.12
        $this->Log_out	= $cfg['LOG_PATH'] . $this->Vd->VD_ID . "_$strToday"."_io_out.log";
    }
    //=======================================================
    //Create on V2.0
    public function OpenMbsSocket()
    {
        //open modbus soecket
        $url 		= "tcp://" . $this->Mbs_IP .":" . $this->Mbs_port ;

        $socket 	= stream_socket_client (  $url
                                            , $errno
                                            , $errstr
                                            //, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
                                            );
		if (false === $socket ) {
            
			//echo "$errstr($errno)\n";
			$this->Vd->LogFileView(LOG_SYS , "Fatal> Create Modbus socket failed.[". $errstr($errno). "]" );
			exit();
		}
        $this->MbsClient  = $socket;
        //add to VD listen
        $this->Vd-> AddListenClient($socket);       
    }

    public function Make_Tcp_Header($PDU_length = 6 )//return binary
    {
        $tx_id          = $this->TxID++ ;

        $tcp_header     = pack(   "C*"
                                , $tx_id >> 8   &  0xFF
                                , $tx_id        &  0xFF
                                , 0,0,0                 //always 0, 3 times
                                , (int) $PDU_length
                               );
        return $tcp_header;
    }
    //=======================================================
    //操作碼 01 讀取多個 Di ( Di relay的起始暫存器位址為 00001)  MOXA not support FC01 , use FC02
    //在 Constructor 也會要Pack, 所以獨立一個Pack Function
    //return bin buffer
    public function Pack_FC_01_Req($start_addr , $length)//for Read Coils
    {
        //PDU: protocol data unit
        $pdu  =  pack(    "C*"
                        , 1     //addr
                        , 1     // FC
                          //$start_addr $length 自己Shift決定Hi-Byte 位置
                        , $start_addr >> 8  &  0xFF
                        , $start_addr       &  0xFF
                        , $length >> 8   &  0xFF
                        , $length        &  0xFF
                     );

        $tcp_header = $this->Make_Tcp_Header(6)   ;
        //
        return $tcp_header . $pdu;
    }
    //操作碼 02 讀取多個 Di ( Di relay的起始暫存器位址為 00001)
    //在 Constructor 也會要Pack, 所以獨立一個Pack Function
    //return bin buffer   
    public function Pack_FC_02_Req($start_addr , $length)//for Read Coils
    {
        //PDU: protocol data unit
        $pdu  =  pack(    "C*"
                        , 1     //addr
                        , 2     // FC
                          //$start_addr $length 自己Shift決定Hi-Byte 位置
                        , $start_addr >> 8  &  0xFF
                        , $start_addr       &  0xFF
                        , $length >> 8   &  0xFF
                        , $length        &  0xFF
                     );

        $tcp_header = $this->Make_Tcp_Header(6)   ;
        //
        return $tcp_header . $pdu;
    }
    //=======================================================
    //Create on V2.0
    public function Polling_DIAI()
    {        
        //--- Poll DI ---------------------
        $this->Polling_DI();

        //--- P{oll AI --------------------
        if( ! count($this->AI_Map)){//No AI setting, return
            return;    
        }
        $this->Polling_AI();
    }
    //=======================================================
    //Create on V2.1
    public function Polling_DI()
    {
        $isExist = 0 ;
        // Check is Poll DI is in the queue.
        foreach( $this->Mbs_Cmd_Queue as $tx_id => $value){
            if( $this->Mbs_Cmd_Queue[$tx_id]["req_mode" ] == 1){
                $isExist = 1 ;
                break;     
            }
        }
        if( ! $isExist ){
            $tx_id      = $this->TxID++ ;
            $str_tx_id  = pack(   "C*"
                                , $tx_id >> 8   &  0xFF
                                , $tx_id        &  0xFF);
            //$this->Send_Req($str_tx_id . $this->PollingDI_Data   );
            $this->Mbs_Send( "PDI" , $str_tx_id . $this->PollingDI_Data ,  1 );//$req_mode = 1 , poll DI
        }       
    }
    //=======================================================
    //Create on V2.1
    public function Polling_AI()
    {
        // Check is Poll AI is in the queue.        
        $isExist = 0 ;
        foreach( $this->Mbs_Cmd_Queue as $tx_id => $value){
            if( $this->Mbs_Cmd_Queue[$tx_id]["req_mode" ] == 2){
                $isExist = 1 ;
                break;     
            }
        }
        if( ! $isExist ){
            $tx_id      = $this->TxID++ ;
            $str_tx_id  = pack(   "C*"
                                , $tx_id >> 8   &  0xFF
                                , $tx_id        &  0xFF);
            //$this->Send_Req($str_tx_id . $this->PollingDI_Data   );          
            $this->Mbs_Send( "PAI" , $str_tx_id . $this->PollingAI_Data  ,  2 );//$req_mode = 2 , poll AI
        }           
    }
    //=======================================================
    public function Get_DI_All()//return binary,"0A00000006" 發送時，再pack,// V0.91
    {
        $tx_id      = $this->TxID++ ;
        $str_tx_id  = pack(   "C*"
                            , $tx_id >> 8   &  0xFF
                            , $tx_id        &  0xFF);
        $this->Mbs_Send( "PAI" , $str_tx_id . $this->PollingAI_Data  ,  0 );//
        //$this->Send_Req($str_tx_id . $this->PollingDI_Data   );
    }
    //=======================================================
    //FC 02, 06.25 MOXA reply useing FC2 to get DIs
    public function Get_DIs($start_addr , $length)//Aliase of  FC_01_Req
    {
        $bin_data   = $this->Pack_FC_02_Req($start_addr , $length);
        $VDCmds     = sprintf("RDI%03d%03d" , $start_addr , $length) ;
        $this->Mbs_Send( $VDCmds , $str_tx_id . $this->PollingAI_Data  ,  0 );//Read DIs, RDI123456 , from 123, length 456
        //$this->Send_Req($bin_data);
    }
    //=======================================================
    //讀取單個 Ai
    public function Get_AI($AI_name  )//2020.4.27 FC04
    {
        //Not Yet
        //Find AI address.
        //GAI
    }
    //=======================================================
    //讀取多個 Ai
    public function Get_AI_All()//2020.4.27 FC04, // V0.91
    {
        //use Polling_AI, reserve here
        
        $tx_id      = $this->TxID++ ;
        $str_tx_id  = pack(   "C*"
                            , $tx_id >> 8   &  0xFF
                            , $tx_id        &  0xFF);
        //$this->Send_Req($str_tx_id . $this->PollingAI_Data   );
        
        //$this->Mbs_Send( sprintf("RAI%3d%d",  ) //V2.0  ToDo : confirm test ??? How get VDCmd
        //                ,  $bin_data  ,  0 );//
    }
    //=======================================================
    //Use FC 05
    public function Set_DO ($DO_Name , $stat ) //FC_05 ex: Set_Do ("A" , '+')
    {
        /*
        "DO_Map"  :
        {
           "A1"   : { "Byte_pos": 0     , "Bit_pos": 0  , "Mask": 1   }
        ,  "A2"   : { "Byte_pos": 0     , "Bit_pos": 1  , "Mask": 2   }
        }
        FC 5 Request:
            Function code   :1 Byte     :   0x05
            Output Address  :2 Bytes    :   0x0000 to 0xFFFF
            Output Value    :2 Bytes    :   0x0000 or 0xFF00
        FC 5 Response:
            Function code   :1 Byte     :   0x05
            Output Address  :2 Bytes    :   0x0000 to 0xFFFF
            Output Value    :2 Bytes    :   0x0000 or 0xFF00,  Low or Hi
        */
        if(empty($this->DO_Map[$DO_Name])){
            $this->Vd->LogFileView  (   LOG_SYS
                            , "Err> Set_DO()::DO_name[$DO_Name] Error. \n"
                             //."Error Msg:[".$err_code ."]$err_msg"
                        );
            return;
        }
        $byte_pos   = $this->DO_Map[$DO_Name]["Byte_pos"] ;
        $mask       = $this->DO_Map[$DO_Name]["Mask"] ;

        $coil_pos   = $byte_pos*8 +  $this->DO_Map[$DO_Name]["Bit_pos"]  ;
        $out_value  = $stat == '+' ?  0xFF00    :   0 ;

        //Make PDU data
        $pdu        = pack(   "C*"
                            , 1     //addr
                            , 5     // FC
                            //$start_addr $length 自己Shift決定Hi-Byte 位置
                            , $coil_pos  >> 8  &  0xFF //Output Address
                            , $coil_pos        &  0xFF //Output Address
                            , $out_value >> 8  &  0xFF //Output Value
                            , $out_value       &  0xFF //Output Value
                     );

        $tcp_header = $this->Make_Tcp_Header(6)   ;

        $bin_data   = $tcp_header . $pdu;
        
        //$this->Send_Req($bin_data);
        $this->Mbs_Send( "SDO".$DO_Name .$stat  ,  $bin_data  ,  0 );//

    }
    //=======================================================
    public function Set_DO_All($stat)//V1.1 應用很少，先用迴圈處理
    {
        foreach( $this->DO_Map  as $key => $value){
            $this->Set_DO ($key , $stat ) ;
        }
    }
    //Write single AO
    public function Set_AO( $AO_name , $value)//2020.5.4 00:39   FC06
    {
        if(empty($this->AO_Map[$AO_name])){
            $this->Vd->LogFileView  (   LOG_SYS
                                        , "Err> Set_AO()::AO_name[$AO_name] Error. \n"
                                         //."Error Msg:[".$err_code ."]$err_msg"
                                    );
            return;            
        }   
        $pos    = $this->AO_Map[$AO_name]['Pos'];        
        $this->FC_06_Req(  $AO_name ,$pos , (int) $value );
    }
    //======================================================
    //操作碼 04: 讀取多個 Ai
    public function FC_04_Req( $start_addr , $length)//2020.4.27 FC04
    {
         $pdu  =  pack(   "C*"
                        , 1     //addr
                        , 4     // FC
                          //$start_addr $length 自己Shift決定Hi-Byte 位置
                        , $start_addr >> 8  &  0xFF
                        , $start_addr       &  0xFF
                        , $length >> 8   &  0xFF
                        , $length        &  0xFF
                     );
        $tcp_header = $this->Make_Tcp_Header(6)   ;

        //$this->Send_Req( $tcp_header . $pdu);
        $this->Mbs_Send( sprintf("RAI%3d%3d", $start_addr , $length) // "GTAI123456" from 123~456
                        ,  $bin_data  ,  0 );//
        
    }
    //=======================================================
    //操作碼 06: write single AO
    public function FC_06_Req($AO_name , $start_addr , $arg_value)//2020.5.3
    {
        $value = intval( $arg_value );
        
        $pdu  =  pack(   "C*"
                        , 1     //addr
                        , 6     // FC
                          //$start_addr $length 自己Shift決定Hi-Byte 位置
                        , $start_addr >> 8  &  0xFF
                        , $start_addr       &  0xFF
                        , $value      >> 8  &  0xFF
                        , $value            &  0xFF
                     );
        $tcp_header = $this->Make_Tcp_Header(6)   ;
        $bin_data   = $tcp_header . $pdu;
        //$this->Send_Req( $tcp_header . $pdu);
        $this->Mbs_Send( sprintf("SAO%s%05d" , $AO_name , $arg_value) //V2.0  ToDo : confirm check
                        ,  $bin_data  ,  0 );//
    }
    //=======================================================
    public function LogFile( $type , $arg_str ) //ex: "A", DI_Map ["A"]
    {
        #$type 目前只有一種 LOG_OUT

 		$strLog	= substr( 	Ap_uDate("ymd-His.u"),1,16) // 00312-171618.654
							//date("ymd-His.u") // 100310.171618.654
						. 	'> ' . $arg_str   //remove under score
						.	PHP_EOL
						;
		$iCount = file_put_contents($this->Log_out , $strLog , FILE_APPEND | LOCK_EX );

    }
    //=======================================================
    //V2.0 add
    public function Mbs_Send($VDCmd , $bin_data, $req_mode = 0 )
    {
       
        # put to Queue --------------------------------
        $ary_bin_data = unpack("C*", $bin_data);
        //轉出的陣列，idx 從 1 起算
        //put tp heap, store by array type。
        //V2.0
        $tx_id = $ary_bin_data[1] << 8 | $ary_bin_data[2] ;
        $this->Mbs_Cmd_Queue[$tx_id] = array(
                                            "send_times"    =>  0               // +1 when send.
                                          , "req_mode"      =>  $req_mode       // 0: Normal,  1: Poll DI , 2: Poll AI
                                          , "VDCmd"         =>  $VDCmd          // ex. 'STDOA1+'
                                          , "packed_data"   =>  $bin_data       // Binary data for re-send.
                                      );
        
        # chk is sending on the way -------------------------------
        if($this->Mbs_Timer["isOnTheWay"] ){ //packet on way, wait ack.
            return;
        }
        # -------------------------------                               
        
        $this->Send_to_mbs_dev(0);
        
    }
    //=======================================================
    //V2.0 add
    public function Send_to_mbs_dev( $isTimeoutResend )
    {
        //check Queue is empty ...      
        $tx_id = $this->Get_1st_Cmd_Queue_idx() ;
        if( $tx_id  == 0 ){
            return;
        }        
        
        // send first Queue element.====================================
        $this->Mbs_Cmd_Queue[$tx_id]["send_times" ] += 1;
        $bin_data = $this->Mbs_Cmd_Queue[$tx_id]["packed_data" ] ;
 
            /*
            $this->Mbs_Cmd_Queue [$tx_id]["send_times" ]   =>  0               // +1 when send.
            $this->Mbs_Cmd_Queue [$tx_id]["VDCmd" ]        =>  $VDCmd          // ex. 'STDOA1+'
            $this->Mbs_Cmd_Queue [$tx_id]["packed_data"]   =>  $bin_data       // Binary data for re-send.
            */
     
        //Send and re-try once ========================================
        $ret    = fwrite( $this->MbsClient , $bin_data);
        
        //Try once, if fail again, re-send on timeout
        if( $ret === false) {
            $this->Vd->LogFileView  (   LOG_SYS
                                        , "Msg> Send to ioBox Error. TxID[$tx_id]\n"
                                         //."Error Msg:[".$err_code ."]$err_msg"
                                    );
            
            fclose($this->MbsClient );
            $this->Vd->DropListenClient($this->MbsClient);

            //re-open modbus soecket
            $this->OpenMbsSocket();
            $ret    = fwrite( $this->MbsClient , $bin_data);
            if( $ret === false) {
                $this->Vd->LogFileView  (   LOG_SYS
                                        , "Msg> Send to ioBox Error ,2nd times. TxID[$tx_id]\n"
                                         //."Error Msg:[".$err_code ."]$err_msg"
                                        );           
            }
        }
        // enable Timer ==================================================
        $this->Mbs_Timer["isOnTheWay"]      = 1;
        $utimestamp =  microtime(true)  ;//小數點以下為秒
        $next_act =  $this->ioTimeout + $utimestamp;//
        $this->Mbs_Timer["TimeOfTimeout"]    = $next_act;// utime timestamp
             
        //Log packet but poll command  ===================================
        if($isTimeoutResend){//if in timeout re-send, do not log.
            return;
        }
        //--------------------------
        $ary_bin_data = unpack("C*", $this->Mbs_Cmd_Queue [$tx_id]["packed_data"] );        
        $fc_code    = $ary_bin_data[8] ;
        $ary_re_send_fc = array(5,6,15,16,23);
        if (  in_array ( $fc_code , $ary_re_send_fc ) )
        {
            #ToDo 3# : Log_ "MBS1_20200425_mbs_o.log"
            $msg = implode ( ',' ,  $ary_bin_data );
            $this->LogFile( LOG_OUT , $msg );
        }
    
    }
    //=============================================
    //V2.0
    public function Get_1st_Cmd_Queue_idx() {
        if( count( $this->Mbs_Cmd_Queue ) == 0 ){
            return 0;
        }        
        foreach($this->Mbs_Cmd_Queue as $tx_id => $value ){
            return  $tx_id;
            /*
            $this->Mbs_Cmd_Queue [$tx_id]["send_times" ]   =>  0               // +1 when send.
            $this->Mbs_Cmd_Queue [$tx_id]["VDCmd" ]        =>  $VDCmd          // ex. 'STDOA1+'
            $this->Mbs_Cmd_Queue [$tx_id]["packed_data"]   =>  $bin_data       // Binary data for re-send.
            */

        }   
    }
    //=======================================================
    //V2.0
    //Called in vd: OnVdMainLoop()
    public function Mbs_tmr_chk_timeout()//2020.7.11
    {
        if( $this->Mbs_Timer["isOnTheWay"] == 0){
            return;
        }
        //if not timeout ,return =================
        $utimestamp =  microtime(true)  ;//小數點以下為秒
        if( $utimestamp < $this->Mbs_Timer["TimeOfTimeout"] ){
            return;
        }
        //== if >  $this->MaxRetry_times ============================
        //走到這裡表示 Timeout 了
        $tx_id  = $this->Get_1st_Cmd_Queue_idx();
        if( $this->Mbs_Cmd_Queue[$tx_id]["send_times" ]  >  $this->MaxRetry_times ){
            
            /*
             * if re-send times > $this->MaxRetry_times
             * log all queued VDCmd , VDSend to web console ,
             * abort APP flow.
             * 考慮用另一個 ioBox 緊急停止 >> also send to another VD , and the VD send to iob engercy stop
             *
             */
            // Dump Queue --------------------------------
            $str_dump_queue = print_r($this->Mbs_Cmd_Queue , TRUE);
            $msg_err =  "Retry Err# Re-send [$tx_id][". $this->Mbs_Cmd_Queue[$tx_id]["VDCmd" ]  ."] more than ". $this->MaxRetry_times  . " times." ;
            //NAC : None ACK
            $this->Vd->LogFileView  (   LOG_SYS
                                    ,   "Error !! $msg_err\n"
                                        . "Mbs_Cmd_Queu ::\n"
                                        . $str_dump_queue
                                    ); 
            //
            return $msg_err;
            /* Let VD-IOB send back to Master VD.
            * $this->Vd->VDSend(  "PTW1"
                                , '-ERR' .$msg_err
                                );
            */
                 
        }    
        //====== log re-send info ====================================
        //走到這裡表示 Timeout 了
        $this->Vd->LogFileView  (   LOG_SYS
                                    , "Err> Timeout Re-Send ". $this->Mbs_Cmd_Queue[$tx_id]["send_times" ]
                                        ." times. TxID[$tx_id],VDCmd[".   $this->Mbs_Cmd_Queue[$tx_id]["VDCmd" ]  ."]\n"
                                );      
      
        $this->Send_to_mbs_dev(1);  // 1 : re-send.
        return '' ;

    } 
    //=======================================================
    public function Send_Req($bin_data)//2020.4.25 17:00
    {
        $ary_bin_data = unpack("C*", $bin_data);
        //轉出的陣列，idx 從 1 起算
        //put tp heap, store by array type。
        //V2.0
        $tx_id = $ary_bin_data[1] << 8 | $ary_bin_data[2] ;
        $this->Req_Stored_Heap[$tx_id]  = $ary_bin_data ; //
        $timeout                        =  microtime(true)  + $this->ioTimeout ;//Now
        $this->Req_Stored_VDCmd[$tx_id] =  array(
                                                  "send_times"  =>  0               // +1 when send.
                                                //, "time_next"   =>  $timeout        // time of timeout to re-send, write on send
                                                , "VDCmd"       =>  $arg_VDCmd 
                                                , "packed_data" =>  $ary_bin_data  
                                                );
                                        
        //V2.0 to for set key for  $this->Req_Stored_VDCmd  
        
        $ret                     = fwrite( $this->MbsClient , $bin_data);
        //{{V2.0
        if( $ret === false) {
            $this->Vd->LogFileView  (   LOG_SYS
                                        , "Msg> Send to ioBox Error. TxID[$TxID]\n"
                                         //."Error Msg:[".$err_code ."]$err_msg"
                                    );            
            fclose($this->MbsClient );
            //re-open modbus soecket
            $this->OpenMbsSocket();
            $ret    = fwrite( $this->MbsClient , $bin_data);
            if( $ret === false) {
                $this->Vd->LogFileView  (   LOG_SYS
                                        , "Msg> Send to ioBox Error ,2nd times. TxID[$TxID]\n"
                                         //."Error Msg:[".$err_code ."]$err_msg"
                                        );           
            }
        }
        //}}V2.0
        #ToDo 5#: Exception of fwrite( $this->MbsClient , $bin_data); !?
        $fc_code    = $ary_bin_data[8] ;
        $ary_re_send_fc = array(5,6,15,16,23);
        if (  in_array ( $fc_code , $ary_re_send_fc ) )
        {
            #ToDo 3# : Log_ "MBS1_20200425_mbs_o.log"
            $msg = implode ( ',' ,  $ary_bin_data );
            $this->LogFile( LOG_OUT , $msg );
        }
    }
    //=======================================================
    public function Proc_Rsp($bin_data)//2020.4.25 17:45, 4.27 14:00
    {
        $ary_bin_data = unpack("C*",$bin_data  );
        //Idx 從 1 起算
        /*
            Array
            (
                [1] => 48   TxID1
                [2] => 57   TxID2
                [3] => 0
                [4] => 0
                [5] => 0
                [6] => 6   * PDU Length
                [7] => 01   addr
                [8] => 1    FC1
                [9] => ...  Data or Error code..... 依據不同FC 定義
            )
        */
        //---------------------------------------
        //--cut packet 6#
        $array_size     = count($ary_bin_data);
		$first_pdu_size = $ary_bin_data[6];         //
		$iPacket_size   = $first_pdu_size + 6 ;     //6 : tcp_header length.

		while($array_size >= $iPacket_size  ){
            $ary_bin_data_new = [];//init array

			if($array_size > $iPacket_size )//more than one packet from device(io-Box)
			{
				//move to new packet
				for($i = 1 ; $i <= $iPacket_size ; $i++){
					$ary_1st_packet[$i]  = $ary_bin_data[$i];
					unset( $ary_bin_data[$i]);//had Moved , unset source.
				}

                //Re-sort idx by move
				for( $j=1 ;   $i <= $array_size ; $i++, $j++)
				{
					$ary_bin_data_new [$j]  = $ary_bin_data[$i];
				}
			}else{
                $ary_1st_packet = $ary_bin_data;
            }

			$this->Proc_Rsp_by_Packet( $ary_1st_packet );
			//
			$ary_bin_data   = $ary_bin_data_new ;

			$array_size     = count($ary_bin_data);
            if($array_size  < 9 ) break;
            $first_pdu_size = $ary_bin_data[6];
            $iPacket_size   = $first_pdu_size + 6 ;    //6 : tcp_header length.
		}  //while($array_size > 6 ){
    }
    //=======================================================
    public function Proc_Rsp_by_Packet($ary_bin_data)//2020.4.25 17:45
    {
        
        // V2.0 disable Timer--------------------------
        $this->Mbs_Timer["isOnTheWay"]      = 0;
        //------------------------------------------
        $is_need_re_send    = 0 ;
        $fc_code            = $ary_bin_data[8];
        $err_code           = $ary_bin_data[9] ;
        $is_error           = $fc_code   & 0x80 ;
        //$i                  = $ary_bin_data[1] << 8  ;
        //$j                  = $ary_bin_data[2] ;
        $tx_id              = ($ary_bin_data[1] << 8 )  + ($ary_bin_data[2]) ;
        //$tx_id              = $this->Get_1st_Cmd_Queue_idx();
            /*
            $this->Mbs_Cmd_Queue [$tx_id]["send_times" ]   =>  0               // +1 when send.
            $this->Mbs_Cmd_Queue [$tx_id]["VDCmd" ]        =>  $VDCmd          // ex. 'STDOA1+'
            $this->Mbs_Cmd_Queue [$tx_id]["packed_data"]   =>  $bin_data       // Binary data for re-send.
            */
        if( empty( $this->Mbs_Cmd_Queue[$tx_id] )  ){
              $this->Vd->LogFileView( LOG_SYS ,  "Note: Proc_Rsp_by_Packet(): tx_id[$tx_id] Not in  Mbs_Cmd_Queue.");
              return;
        }
              
        if($is_error ){
            // $ary_bin_data[9] is Error Msg_id
            $err_msg        = $this->GetModbus_ErrorMsg( $err_code);
            $strRsp         = implode ( ',' , $ary_bin_data );
            $ary_Snd        = unpack(  "C*" , $this->Mbs_Cmd_Queue [$tx_id]["packed_data"]); 
            $strSnd         = implode ( ',' , $ary_Snd  );
            $this->Vd->LogFileView  (   LOG_SYS
                                        , "Error> Rsp Pack TxID return Error.[$tx_id]\n"
                                         ."Error Msg:[".$err_code ."]$err_msg\n"
                                         ."Snd:[$strSnd]\n"
                                         ."Rsp:[$strRsp]\n"
                                          
                                    );
            $ary_re_send_fc = array(5,6,15,16,23);
            if ( ($err_code==6 || $err_code == 8)//Error code
                 && in_array ( $fc_code , $ary_re_send_fc )
            ) {
                // Must Resend
                $is_need_re_send = 1 ;
            }else{
                return ;
            }
        }
        //-------------------------------------------------
        #search & drop $this->Req_Stored_Heap[]
        /*
        $idx = -1 ;
        foreach ($this->Req_Stored_Heap as $key => $value) {
            if(     $this->Req_Stored_Heap[$key][1] == $ary_bin_data[1]
                &&  $this->Req_Stored_Heap[$key][2] == $ary_bin_data[2]   )
            {
                $idx    = $key ;
                break;
            }
        }
        */
 
        if( $tx_id ){
            /*
            if($is_need_re_send ){// re-send ,if necessary.
                #re send
                $tx_id          = $this->TxID++ ;

                $this->Req_Stored_Heap[$idx][1] = $tx_id >> 8   &  0xFF ;
                $this->Req_Stored_Heap[$idx][2] = $tx_id        &  0xFF ;
                $ary        = $this->Req_Stored_Heap[$idx];
                $bin_data   = pack("C*", ...$ary  );
                $this->Send_Req($bin_data);
            }
            unset($this->Req_Stored_Heap [$idx]) ;      //release send buffer
            unset($this->Req_Stored_VDCmd [$idx]) ;     //V2.0
            */ 
            if($is_need_re_send ){// re-send ,if necessary.
                ##    
                $this->Send_to_mbs_dev( 1 );
                return;
            }
            
        }else{
            //didnt found TxID send theis packet.
            //$TxID = $ary_bin_data[1] << 8 + $ary_bin_data[2] ;Move to above.
            $this->Vd->LogFileView(LOG_SYS , "Error> Rsp Pack TxID not found.[$TxID]");
            return;
        }
        //---------------------------------------------
        #call
        switch($fc_code){
            case  1 :   //操作碼 01 讀取多個 Di ( Di relay的起始暫存器位址為 00001)
                $this->FC_01_Rsp( $ary_bin_data );
                break;
            case  2 :   //操作碼 01 讀取多個 Di ( Di relay的起始暫存器位址為 00001)
                $this->FC_02_Rsp( $ary_bin_data );
                break;          
            case  4 :   //操作碼 04: 讀取多個 Ai (Ao起始站存器位置為 30001
                $this->FC_04_Rsp( $ary_bin_data );
                break;
            case  5 :   //操作碼 05: 寫入單點 Do (Start = 10001
                $this->FC_05_Rsp( $ary_bin_data );
                break;
            case  6 :   //操作碼 06: 寫入單點 Ao (Start = 40001
                $this->FC_06_Rsp( $ary_bin_data );
                break;
            case  15 :
                $this->FC_15_Rsp( $ary_bin_data );
                break;
            case  23 :
                $this->FC_23_Rsp( $ary_bin_data );
                break;
            default:
                $this->Vd->LogFileView(LOG_SYS , " Warning> Unknow Modbus Rsp FC[$fc_code].");
        } //switch($fc_code){

        //5.3 tobe move here
        //unset($this->Req_Stored_Heap [$idx]);//release send buffer
        
        //V2.0
        //--- remove 
        unset($this->Mbs_Cmd_Queue [$tx_id]); 
        $this->Mbs_Timer["isOnTheWay"]    = 0;
        //--- send next
        $this->Send_to_mbs_dev( 0 );

    }
    //=======================================================
    public function FC_01_Rsp( $aryDI_data )//Read Coils
    {
        /*
            "DI_Map"  :
            {
               "A"    : { "Byte_pos": 0     , "Bit_pos": 0  , "Mask": 1   }
            ,  "B"    : { "Byte_pos": 0     , "Bit_pos": 1  , "Mask": 2   }
            }

            Array  $aryDI_data
            (
                [1] => 48   TxID1
                [2] => 57   TxID2
                [3] => 0
                [4] => 0
                [5] => 0
                [6] => 6    PDU Length
                [7] => 01   addr
                [8] => 1    FC code
                [9] => Byte count
                [10]=> Data..
                [11]=> Data2..
            )
        */

        foreach ($this->DI_Map as $key => $value) {
            $byte_pos   = $value["Byte_pos"] ;
            $mask       = $value["Mask"] ;

            $old        = $this->Buffer_DI[$byte_pos]   & $mask ;
            $new        = $aryDI_data[$byte_pos +10 ]   & $mask ;//$byte_pos +1  是因為unpack轉出陣列的idx是從1起算。

            if ($old != $new) {//有變動
                # send VD to Master VD
                # PTW1 . $this->Vd->VD_ID . $key .  ($new ? '+' : '-' )
                # $this->Vd->VDSwnd("PTW1" , "DI=" .$key .  ($new ? '+' : '-' ) ) ;

                call_user_func($this->FncOnDI_Change , $key , ($new ? '+' : '-' ) );

                $this->Buffer_DI[$byte_pos] = $this->Buffer_DI[$byte_pos] ^ $mask ; //Toggle the bit.
                //v1.11
                //V2.0 $this->Unset_Req_Stored_Heap();
            }
        }//foreach ($this->DI_Map as $key => $value)
              
    }
    public function FC_02_Rsp( $aryDI_data )//Read Coils
    {
        /*
            "DI_Map"  :
            {
               "A"    : { "Byte_pos": 0     , "Bit_pos": 0  , "Mask": 1   }
            ,  "B"    : { "Byte_pos": 0     , "Bit_pos": 1  , "Mask": 2   }
            }

            Array  $aryDI_data
            (
                [1] => 48   TxID1
                [2] => 57   TxID2
                [3] => 0
                [4] => 0
                [5] => 0
                [6] => 6    PDU Length
                [7] => 01   addr
                [8] => 2    FC code
                [9] => Byte count
                [10]=> Data..
                [11]=> Data2..
            )
        */

        foreach ($this->DI_Map as $key => $value) {
            $byte_pos   = $value["Byte_pos"] ;
            $mask       = $value["Mask"] ;

            $old        = $this->Buffer_DI[$byte_pos]   & $mask ;
            $new        = $aryDI_data[$byte_pos +10 ]   & $mask ;//$byte_pos +1  是因為unpack轉出陣列的idx是從1起算。

            if ($old != $new) {//有變動
                # send VD to Master VD
                # PTW1 . $this->Vd->VD_ID . $key .  ($new ? '+' : '-' )
                # $this->Vd->VDSwnd("PTW1" , "DI=" .$key .  ($new ? '+' : '-' ) ) ;

                call_user_func($this->FncOnDI_Change , $key , ($new ? '+' : '-' ) );

                $this->Buffer_DI[$byte_pos] = $this->Buffer_DI[$byte_pos] ^ $mask ; //Toggle the bit.
                //v1.11
                
            }
        }//foreach ($this->DI_Map as $key => $value)
    }
    //=======================================================
    public function FC_04_Rsp($ary_bin_data)//Read ALL AIs
    {
        /*
        	, "AI_Map"  :
            {
               "AI1"    : { "Pos": 0  }
            ,  "AI2"    : { "Pos": 1  }
            }
            Array  $aryAI_data
            (
                [1] => 48   TxID1
                [2] => 57   TxID2
                [3] => 0
                [4] => 0
                [5] => 0
                [6] => 6    PDU Length
                [7] => 01   dev-addr
                [8] => 4    FC code
                [9] => Byte count
                [10]=> Data1 - Hi    00
                [11]=> Data1 - Low   0A  --> Data1 = Hi << 8 + Low = 10
                [12]=> Data2 - Hi
                [13]=> Data2 - Low
            )
        */
 
        
        //V2.0 ToDo: 要根據收到的封包來改 數旗標
        ## Q: Send Buffer 被unset 了 怎麼知道是QUERY 多少個?
        ## V2.0 有紀錄VDCmd

        /* $tx_id  = Get_1st_Cmd_Queue_idx();
         * $this->Mbs_Cmd_Queue [$tx_id]["VDCmd" ]        =>  $VDCmd 
         *
         *
         */
        $data_pos = 10 ;//Packet 資料起算的idx

        foreach ($this->AI_Map as $key => $value) {

            ###$this->AI_Map[$AI_name]['Pos']
            $old    = $this->AI_Map[$key]['Value'] ;

            $data_h = $ary_bin_data[$data_pos];
            $data_l = $ary_bin_data[$data_pos+1];
            $new    = ($data_h <<8)  +  $data_l  ;//怪~不加()會=0

            if ( $old != $new  &&   $this->FncOnAI_Change   ) {//有變動
                # send VD to Master VD
                call_user_func($this->FncOnAI_Change ,  [ $key , $new ]  );
                #
                $this->AI_Map[$key]['Value'] = $new   ; //save the value.
                 //v1.11
                //V2.0 $this->Unset_Req_Stored_Heap();
            }
            $data_pos += 2 ;
        }
    }
    //=======================================================
    public function FC_05_Rsp($ary_bin_data)//Write Single Coil
    {
        /*
        "DO_Map"  :
        {
           "A1"   : { "Byte_pos": 0     , "Bit_pos": 0  , "Mask": 1   }
        ,  "A2"   : { "Byte_pos": 0     , "Bit_pos": 1  , "Mask": 2   }
        }
        FC 5 Request:
            Function code   :1 Byte     :   0x05
            Output Address  :2 Bytes    :   0x0000 to 0xFFFF
            Output Value    :2 Bytes    :   0x0000 or 0xFF00
        FC 5 Response:
            Function code   :1 Byte     :   0x05
            Output Address  :2 Bytes    :   0x0000 to 0xFFFF
            Output Value    :2 Bytes    :   0x0000 or 0xFF00,  Low or Hi
            
         $ary_bin_data  
            (   
                [1] => 0
                [2] => 43
                [3] => 0
                [4] => 0
                [5] => 0
                [6] => 6
                [7] => 1
                [8] => 5    : FC
                [9] => 0    :Output Address  :2 Bytes    :   0x0000 to 0xFFFF
                [10] => 0   :Output Address  :2 Bytes    :   0x0000 to 0xFFFF
                [11] => 0   : Output Value    :2 Bytes    :   0x0000 or 0xFF00,  Low or Hi
                [12] => 0   : Output Value    :2 Bytes    :   0x0000 or 0xFF00,  Low or Hi
            )
   
            
        */
        
        //V2.0 Rsp後，得到Ack才設定變數旗標
        //
        /*        $this->Mbs_Cmd_Queue[$tx_id] = array(
                                            "send_times"    =>  0               // +1 when send.
                                          , "req_mode"      =>  $req_mode       // 0: Normal,  1: Poll DI , 2: Poll AI
                                          , "VDCmd"         =>  $VDCmd          // ex. 'STDOA1+'
                                          , "packed_data"   =>  $bin_data       // Binary data for re-send.
                                      );
        */
        $tx_id      = ($ary_bin_data[1] << 8 )  + ($ary_bin_data[2]) ;
        $len        = strlen($this->Mbs_Cmd_Queue[$tx_id]["VDCmd"]);
        $do_length  = $len - LEN_MBS_IO_CMD - 1 ; // "SDO" ... "+";
        $DO_Name    = substr( $this->Mbs_Cmd_Queue[$tx_id]["VDCmd"], LEN_MBS_IO_CMD , $do_length );//SDOA
        
        $byte_pos   = $this->DO_Map[$DO_Name]["Byte_pos"] ;
        $mask       = $this->DO_Map[$DO_Name]["Mask"] ;
        $stat       = $ary_bin_data[11] | $ary_bin_data[12]  ? '+' : '-' ;
        //V2.0 ToDo: 要根據收到的封包來改 數旗標
        if($stat == '+'){
            //set bit on
            $this->Buffer_DO[$byte_pos] = $this->Buffer_DO[$byte_pos] | $mask ;
        }else{
            //set bit off ###
            /*
            if the data unto unexist array idx, will fatal ,Exit App. .2020.4.28

            PHP Fatal error:  Uncaught Error: Unsupported operand types in /var/www/html/nextcs/_inc/lib_modbus.inc.php:286
            Stack trace:
            #0 /var/www/html/nextcs/dev_modbus/vd_iob1.php(136): VD_Modbus->Set_DO()
            #1 /var/www/html/nextcs/_inc/lib_vd2.inc.php(221): OnRcvVdMsg()
            #2 /var/www/html/nextcs/dev_modbus/vd_iob1.php(32): VxD->Run()
            #3 {main}
              thrown in /var/www/html/nextcs/_inc/lib_modbus.inc.php on line 286
             */
            try {//Catch useless.still die.2020.4.28 22:42
                $byte                       = $this->Buffer_DO[$byte_pos];
                $this->Buffer_DO[$byte_pos] = $byte & (~ $mask)  ;//if idx name not found .throw!
            }catch(Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }
        }
        call_user_func( $this->FncOnMbsACK  , $this->Mbs_Cmd_Queue[$tx_id]["VDCmd"]) ;
    }
    //=======================================================
    public function FC_06_Rsp($ary_bin_data)//Write Single AO STAO
    {
        /*
         "AO_Map"  :
        {
           "AO1"    : { "Pos": 0  , "Value" : 0 }
        ,  "AO2"    : { "Pos": 1  , "Value" : 0 }
        }
        FC 6 Request:
            Function code   :1 Byte     :   0x06
            Output Address  :2 Bytes    :   0x0000 to 0xFFFF
            Output Value    :2 Bytes    :
        FC 6 Response:
            Function code   :1 Byte     :   0x06
            Output Address  :2 Bytes    :   0x0000 to 0xFFFF
            Output Value    :2 Bytes    :
        */
        $tx_id      = ($ary_bin_data[1] << 8 )  + ($ary_bin_data[2]) ;
        $len        = strlen($this->Mbs_Cmd_Queue[$tx_id]["VDCmd"]);
        $ao_length  = $len - LEN_MBS_IO_CMD - 5 ; // "STAOO" ... "65535";
        $AO_Name    = substr( $this->Mbs_Cmd_Queue[$tx_id]["VDCmd"], LEN_MBS_IO_CMD, $ao_length );//STAOAO100123
        //"AO1"    : { "Pos": 0  , "Value" : 0 }
        //$ao_pos     = $this->AO_Map[$AO_Name]["Pos"] ;
        
        //ack ,set to var.
        $this->AO_Map[$AO_Name]["Value"] = ($ary_bin_data[11]<<8) | $ary_bin_data[12] ;
        //Call ack function         
        call_user_func( $this->FncOnMbsACK  , $this->Mbs_Cmd_Queue[$tx_id]["VDCmd"]) ;

    }
    //=======================================================
    public function FC_15_Rsp($ary_bin_data)// (0x0F) Write Multiple Coils
    {
        //pop $Req_store_fw  to check
    }
    //=======================================================
    public function FC_23_Req($buffer)//(0x17) Read/Write Multiple registers
    {
        //$this->Send_Req($bin_data);
        $this->Mbs_Send( sprintf("XA?%3d%d", $start_addr , $value) //V2.0  ToDo : confirm teat
                        ,  $bin_data  ,  0 );//

    }
    //=======================================================
    public function FC_23_Rsp($ary_bin_data)//(0x17) Read/Write Multiple registers
    {
        //pop $Req_store_fw  to check
    }
    //=======================================================
    private function GetModbus_ErrorMsg( $err_code)
    {
        // $ary_bin_data[9] is Error Msg_id
        switch (  $err_code ) {
            case 1 :
                $err_msg    = "ILLEGAL FUNCTION.";
                break;
            case 2 :
                $err_msg    = "ILLEGAL DATA ADDRESS.";
                break;
            case 3 :
                $err_msg    = "ILLEGAL DATA VALUE.";
                break;
            case 4 :
                $err_msg    = "SERVER DEVICE FAILURE.";
                break;
            case 5 :
                $err_msg    = "ACKNOWLEDGE.";
                break;
            case 6 :        //需要重送，如果是DO 需要重送。
                $err_msg    = "SERVER DEVICE BUSY.";
                break;
            case 8 :        //需要重送，如果是DO 需要重送。
                $err_msg    = "MEMORY PARITY ERROR.";
                break;
            case 0x0A :
                $err_msg    = "GATEWAY PATH UNAVAILABLE.";
                break;
            case 0x0B :
                $err_msg    = "GATEWAY TARGET DEVICE FAILED TO RESPOND.";
                break;
            default:
                $err_msg    = "Unkown Error.";
        }
        return $err_msg ;
    }
    //=======================================================
}

?>