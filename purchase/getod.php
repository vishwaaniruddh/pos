<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$bank_id=$_GET['bank_id'];
$qrybal=mysqli_query($con,"SELECT * FROM `banks` where `bank_id`='$bank_id'");
$row=mysqli_fetch_row($qrybal);
echo $row[6];

CloseCon($con);
?>