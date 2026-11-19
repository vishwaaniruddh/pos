<?php
ini_set( "display_errors", 0);

include('config.php');

$id=$_GET['id'];

$sql=mysql_query("SELECT * FROM `approval_detail` WHERE `bill_id`='$id'");

 while($row=mysql_fetch_row($sql)){
 
 mysql_query("update `phppos_items` set quantity=quantity+$row[2] WHERE name='".$row[1]."'");
 
 }
 
  mysql_query("DELETE FROM `approval_detail` WHERE `bill_id`='$id'");
   mysql_query("DELETE FROM `approval` WHERE `bill_id`='$id'");




header('location:../../../application/views/reports/app_return.php');


?>