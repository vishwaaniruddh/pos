<?php 
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$fcm_token = $_POST["fcm_token"];

$result = mysqli_query($con,"SELECT * FROM fcm_info WHERE fcm_token='$fcm_token'");
       if(mysqli_num_rows($result)> 0)
       {
        //echo "Token exist";
        $frws=mysqli_fetch_array($result);
        
        $sql = "update fcm_info set fcm_token='".$fcm_token."' where fcm_token='".$frws["fcm_token"]."' ";
                 mysqli_query($con,$sql);
                 mysqli_close($connection);
        
       }
         else
            {
                $sql = "insert into fcm_info values ('".$fcm_token."');";
                 mysqli_query($con,$sql);
                 mysqli_close($connection);
            }
            CloseCon($con);
?>