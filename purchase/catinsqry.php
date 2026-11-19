<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();
$qryitem=mysqli_query($con,"select distinct category from phppos_items");
	 	$items=array();
		$itemid=array();
		$category=array();
		 while($row=mysqli_fetch_row($qryitem))
		 {
		echo "INSERT INTO `categories` (`category`, `typ`) VALUES('".$row[0]."')";
		$insqr=mysqli_query($con,"INSERT INTO `categories` (`category`, `typ`) VALUES('".$row[0]."','0')");
		
				//$category[]=$row[0];
			}
CloseCon($con);
?>