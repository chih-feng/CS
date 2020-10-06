<?php

$buffer = "1234567" ;


echo "\n====================\n";
echo strstr($buffer ,  '');





echo "\n====================\n";
$str = "12345\r\n67890\r\n";

echo '\nstrlen("12345\\r\\n67890\\r\\n")=['.  strlen($str) ."]\n" ;
$s2 = substr($str , 4,4);
echo "[\$s2]=substr(\$str , 4,4)=[$s2]\n";


echo "\n==========check explode 是否轉完後還包含切割字元=結果不含======\n";
$s = "12345678\r\n12345678\r\n12345678\r\n12345678\r\n12345678\r\n12345678\r\n";

$ary = explode("\r\n" ,$s  );

print_r($ary);

echo "len=[".strlen($ary[0])."]-------------------------------------------\n";

echo "cnt \\r\\n[".count("\r\n")."\n";



?>