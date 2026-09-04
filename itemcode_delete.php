<?php
include('db_connection.php') ;
$con=OpenSrishringarrCon();

date_default_timezone_set('Asia/Kolkata');

$id = $_GET['id'];

$deleted_at = date("Y-m-d H:i:s");


$sql = mysqli_query($con,"update phppos_items_test set deleted_at = '".$deleted_at."', is_deleted = '1' where  item_id = '".$id."' ");
if($sql>0)
{ ?>
    <script>
       alert('Deleted');
       window.location.href="itemcode_details_test.php";
    </script> 
<? } else { ?>
    <script>
       alert('SOmething WRong!!');
       window.location.href="itemcode_details_test.php";
    </script>
<? }

CloseCon($con);
?>