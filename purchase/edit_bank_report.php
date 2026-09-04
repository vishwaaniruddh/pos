<?php
// include('config.php');

include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$qry=mysqli_query($con,"update bank_transaction set trans_memo='".addslashes($_REQUEST['rem'])."' where trans_id='".$_REQUEST['id']."'");
if($qry)
echo "1";
else
echo "0";

CloseCon($con);
?>