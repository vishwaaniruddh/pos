<?php

// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$cid=$_POST['id'];
$amt=$_POST['amt'];

$qt=$_POST['qty1'];


// var_dump($_POST); die;
mysqli_query($con,"BEGIN;");


// $result1 = mysqli_query($con,"update `phppos_rent` set status='S',booking_status='Returned' where bill_id='$cid'");	

 $result2 = mysqli_query($con,"SELECT * FROM  `order_detail` where bill_id='$cid'");
 while($row2=mysqli_fetch_row($result2)){
// $row2 = mysqli_fetch_row($result2);
  
 $q=$row2[9];
 echo $q."<br>";
 $name = $row2[1];
echo $name."<br>";
// $res1=mysqli_query($con,"update `order_detail` set return_qty='".$q."' where bill_id='$cid' and item_id='$row2[1]'");

$sql = mysqli_query($con,"select quantity from `phppos_items` where name='".$name."' ");
$sql_res = mysqli_fetch_row($sql);
$quantity = $sql_res[0];
$totalquantity = $quantity + $q;


echo $totalquantity;
// $res2=mysqli_query($con,"update `phppos_items` set quantity=quantity+$q  WHERE name='$row2[1]'");
	
 }
 
 die;
if($result1 && $res1 && $res2){
	mysqli_query($con,"COMMIT");
header('location:rent_return.php');
}
else{
	mysqli_query($con,"ROLLBACK");
	echo "Transaction is rolled back, keeping the data secure. Please try again.";
	}
	
CloseCon($con);

?>