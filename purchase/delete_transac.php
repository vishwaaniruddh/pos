<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


mysqli_query($con,"BEGIN");
$qry=mysqli_query($con,"INSERT INTO `bank_transaction_hist`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`, `reconcile_date`, `enrty_date`, `delete_date`) SELECT `trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`, `reconcile_date`, `enrty_date`,'".date('Y-m-d H:i:s')."' FROM `bank_transaction` WHERE `trans_id`='".$_REQUEST['trans_id']."'");
$qry1=mysqli_query($con,"DELETE FROM `bank_transaction` WHERE `trans_id`='".$_REQUEST['trans_id']."'");
if($qry && $qry1)
{
	mysqli_query($con,"COMMIT");
	$_SESSION['success']=1;
}
else
{
	mysqli_query($con,"ROLLBACK");
	$_SESSION['success']=0;
}
CloseCon($con);
header('location:bank_report.php');
?>