<?php
ini_set( "display_errors", 0);

// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$id=$_GET['id'];

$sql=mysqli_query($con,"SELECT * FROM `approval_detail` WHERE `bill_id`='$id'");

 while($row=mysqli_fetch_row($sql)){
 
 mysqli_query($con,"update `phppos_items` set quantity=quantity+$row[2] WHERE name='".$row[1]."'");
 
 }
 
  mysqli_query($con,"DELETE FROM `approval_detail` WHERE `bill_id`='$id'");
   mysqli_query($con,"DELETE FROM `approval` WHERE `bill_id`='$id'");

header('location:../../../application/views/reports/app_return.php');
    CloseCon($con);

?>