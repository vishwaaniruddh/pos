<?php

include('config.php');
$cid=$_POST['id'];
$amt=$_POST['amt'];

 $result1 = mysql_query("update `scheme` set status='S',paid_amount=paid_amount+$amt where bill_id='$cid'");
	
	
	
	
header('location:../../../application/views/reports/rent_return1.php');

?>