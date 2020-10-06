<?php
/*
 *
 *
 * https://dywang.csie.cyut.edu.tw/dywang/php+mariadb/
 *
 */
$dbhost = 'localhost:3306';
$dbuser = 'pi';
$dbpass = '6tfc';
$conn = mysqli_connect($dbhost, $dbuser, $dbpass)
        or die(mysqli_connect_error().PHP_EOL);
echo 'Connected successfully'.PHP_EOL;

mysqli_select_db( $conn, 'ptw03' )
	or die('Error: '.mysqli_error($conn).PHP_EOL);
echo "Use Database ptw03\n";
//select table 
$sql = "select * from put_list";
$retval = mysqli_query( $conn, $sql )
	or die('Error: '.mysqli_error($conn).PHP_EOL);
while($row = mysqli_fetch_array($retval, MYSQLI_ASSOC)) {
    print_r($row);
}
mysqli_free_result($retval);


echo "Fetched data successfully\n";





//
mysqli_close($conn); 




















?>