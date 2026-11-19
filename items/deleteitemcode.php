<?php
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$itemid = $_GET['id'];

$sqldelete = mysqli_query($con,"delete from phppos_items where item_id='".$itemid."' ");
if($sqldelete)
{ ?>
   <script>
        alert('Deleted Successfully');
        window.location.href="itemcode_search.php";
   </script>
<? } else { ?>
    <script>
        alert('Error Deleting Data');
        window.location.href="itemcode_search.php";
   </script>
<? }
CloseCon($con);
?>