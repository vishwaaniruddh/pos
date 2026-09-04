<?php
include('config.php');
include('../purchase/items_showing_calculate.php');
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
	$result3 = mysql_query("SELECT * FROM  `order_detail` where bill_id='$cid'");
	while($row2=mysql_fetch_row($result3)){
		if(strripos($row2[1],'-')>1)
		update_items_showing(str_replace(strrchr($row2[1], '-'),'',$row2[1]));
	}
header('location:../../../application/views/reports/rent_return.php');
}
else{
	mysql_query("ROLLBACK");
	echo "Transaction is rolled back, keeping the data secure. Please try again.";
	}
?>