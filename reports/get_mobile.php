<?php

// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


       $id=$_GET['cid'];
	   $qry="SELECT * FROM  `phppos_people` where person_id='$id'";
$res=mysqli_query($con,$qry);                
$row=mysqli_fetch_row($res);
echo $row[2];

CloseCon($con);
?>