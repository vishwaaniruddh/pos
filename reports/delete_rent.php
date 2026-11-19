<?php
ini_set( "display_errors", 0);

// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$id=$_GET['id'];

 
    mysqli_query($con,"DELETE FROM `order_detail` WHERE `bill_id`='$id'");
    mysqli_query($con,"DELETE FROM `phppos_rent` WHERE `bill_id`='$id'");

    mysqli_query($con,"update flyrob_commission set status='Removed' where purchase_id='".$id."'");
?>
<script>
    alert('Deleted !')
    window.location.href="https://srishringarr.com/pos/reports/rent_return_new.php"
</script>