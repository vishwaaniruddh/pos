<?php
ini_set( "display_errors", 0);
// include('config.php');
include('../../db_connection.php') ;
$con=OpenSrishringarrCon();


       $id=$_GET['cid'];

$sumpd=0;
$bal=0;

/////count total retun
$qry1="SELECT person_id FROM  phppos_people  where phone_number='$id' ";
$res1=mysqli_query($con,$qry1);                
$num1=mysqli_num_rows($res1);
$row1=mysqli_fetch_row($res1);

echo $num1."&&".$row1[0];		
CloseCon($con);
?>
