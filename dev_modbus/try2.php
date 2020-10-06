<?php
// tcp header
$tcp_header  = pack("C*", 12345>>8 & 0xFF  , 12345 & 0xFF  ,0,0,0, 6);

$start_addr = 1;
$length = 8;

$s   = pack(    "C*"
                        , 0     //addr
            , 1     // FC
                //自己Shift決定Hi-Byte
            , $start_addr >> 8  &  0xFF
            , $start_addr       &  0xFF
            , $length >> 8   &  0xFF
            , $length        &  0xFF
         );


$s  = $tcp_header  .$s ;

print_r(
    $ary = unpack("C*", $s, 0)
);
/*
Array
(
    [1] => 48
    [2] => 57
    [3] => 0
    [4] => 0
    [5] => 0
    [6] => 6
    [7] => 0 addr
    [8] => 1
    [9] => 0
    [10] => 1
    [11] => 0
    [12] => 8
)
*/

echo "\n";
print_r(
    dechex(12345)
);
/*

3039
*/

echo "\n";

printf("(48<<8)+57=%d\n", (48<<8) +57 );///0x4857=12345

printf("48=0x%x,57=0x%x\n", 48 , 57);//48=0x30,57=0x39
printf("0x3039=%d\n", 0x3039 );///0x4857=12345


printf("substr=%d\n", $ary[6] );

echo "\n";
echo "\n";
echo "\n";
echo "\n";


    //=======================================================
    //return packed binary string
    public function Pack_Hex_to_Binary($str_hex)//return string,"0A00000006" 發送時，再pack
    {
        //每兩個char pack 成一個Byte
        $len            = strlen($str_hex);

        for ($i=0 ; $i < $len ; $i+=2) {
            # code...
            $packet_bin  = $packet_bin  . pack("C", hexdec( substr($str_hex, $i , 2) ));
        }

        return $packet_bin ;
    }



?>