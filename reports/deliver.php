<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon(); ?>

<html>
    <head>
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>        
    </head>
    <body>

<?php
$bid=$_GET['bid'];
$qry=mysqli_query($con,"select * from order_detail where bill_id='$bid'");
mysqli_query($con,"update `phppos_rent` set booking_status='Picked' where bill_id='$bid'");
while($row=mysqli_fetch_row($qry)){

mysqli_query($con,"update `phppos_items` set quantity=quantity-$row[9] WHERE name='".$row[1]."'");
mysqli_query($con,"update `order_detail` set is_status=1 where bill_id='$bid'");
}

CloseCon($con);

?>
<script>
  swal("Great!", "Booking Status Changed to Picked !", "success");

           setTimeout(function(){ 
               window.location.href="../home_dashboard.php";
           }, 3000);
</script>

</body>
</html>