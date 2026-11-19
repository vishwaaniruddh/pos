<?php

include('config.php');
$cid=$_POST['id'];
$amt=$_POST['amt'];

$qt=$_POST['qty1'];
mysql_query("BEGIN;");


$result1 = mysql_query("update `phppos_rent` set status='S',booking_status='Returned' where bill_id='$cid'");	

 $result2 = mysql_query("SELECT * FROM  `order_detail` where bill_id='$cid'");
 while($row2=mysql_fetch_row($result2)){
// $row2 = mysql_fetch_row($result2);
 
 $q=$row2[9];

$res1=mysql_query("update `order_detail` set return_qty='$q' where bill_id='$cid' and item_id='$row2[1]'");

$res2=mysql_query("update `phppos_items` set quantity=quantity+$q  WHERE name='$row2[1]'");
	
 }
if($result1 && $res1 && $res2){
	mysql_query("COMMIT;");
header('location:../../../application/views/reports/rent_return.php');
}
else{
	mysql_query("ROLLBACK");
	echo "Transaction is rolled back, keeping the data secure. Please try again.";
	}
?>