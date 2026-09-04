<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$id=$_POST['bill_id'];

mysqli_query($con,"update approval set status='S' where bill_id='$id'");
CloseCon($con);
header('location:app_return.php');
  
?>