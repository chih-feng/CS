<?php

$ary = [
    'BIT_MSK_0' => 1 << 0  ,
    'BIT_MSK_1' => 1 << 1  ,
    'BIT_MSK_2' => 1 << 2  ,
    'BIT_MSK_3' => 1 << 3  ,
    'BIT_MSK_4' => 1 << 4  ,
    'BIT_MSK_5' => 1 << 5  ,
    'BIT_MSK_6' => 1 << 6  ,
    'BIT_MSK_7' => 1 << 7

];


$strcmd = 'print_r($ary);' ;
eval($strcmd);

$str2 = '$ary[] =123;' ;

eval($str2 . $strcmd);



echo "\n===Pack array===\n";
$a = array( 0, 66, 67 );

$packed = pack("c*", ...$a);
print_r($packed);

echo "\nbinary [$packed]\n";
echo "\nbin2hex[" .bin2hex($packed)."]\n";

echo '{{=====$tcp_header = sprintf("%04x"."000000"."%2x", $tx_id , length );======'."\n";
$tx_id = 3200;
echo $tcp_header = sprintf("%04x"."000000"."%02x", $tx_id , 6 );
echo "\n";
echo dechex(hexdec($tcp_header));
echo "\n";
$ary =  [ "sprintf('%04x'._" =>  $tcp_header ,
           strlen($tcp_header ),
          'dechex(hexdec($tcp_header))' =>  dechex(hexdec($tcp_header)),
           strlen( dechex(hexdec($tcp_header)))
        ];

print_r($ary);





echo "\n".'}}=====$tcp_header = sprintf("%04x"."000000"."%2x", $tx_id , length );======'."\n";

echo "\n{{===Try Pack====\n";

$i = 300 ;
echo "[". dechex(pack('S',41) ). "]";

$p = pack("CCxxxC", 1234 , 6);
print_r(dexhex($p));
echo "\n}}===Try Pack====\n"


?>