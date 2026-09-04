<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$bank_id=$_GET['bank_id'];
$payment=0;
$income=0;
$qrybal=mysqli_query($con,"SELECT * FROM `bank_transaction` where `bank_id`='$bank_id'");
while($resbal=mysqli_fetch_row($qrybal))
{
	if($resbal[2]=="payment" || $resbal[2]=="banktrans")
	{
		$payment+=$resbal[3];	
	}
	else if($resbal[2]=="receit")
	{
		$income+=$resbal[3];	
	}
}
$balance=$income-$payment;
echo $balance;

CloseCon($con);
?>