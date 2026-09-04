<?php
// include("config.php");
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$name=$_GET['qu'];

//echo "select username from login where 1";
$sql=mysqli_query($con,"select name from phppos_items where name='".$name."' ");
if(mysqli_num_rows($sql) > 0)
{
echo "taken";
}
else{echo "ok";};
CloseCon($con);

?>