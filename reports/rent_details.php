<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();




$dt=date("Y/m/d");


$qry="SELECT * FROM  `phppos_rent` where  bill_date ='".$dt."'";
//echo $qry;



$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
$data=array();
$i=1;
while($row = mysqli_fetch_row($res)) 
 {
$sql1=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$row[1]'");
$row1=mysqli_fetch_row($sql1);
 
 $qry2="SELECT sum(bal_amount) FROM  `phppos_rent` where bill_id ='$row[0]'";
$res2=mysqli_query($con,$qry2);                
$num2=mysqli_num_rows($res2);
$row2=mysqli_fetch_row($res2);
			
$qry3="SELECT sum(`total_amount`) FROM `order_detail` WHERE bill_id ='$row[0]'";
$res3=mysqli_query($con,$qry3);
$row3=mysqli_fetch_row($res3);
$s=$row3[0]+$row[34]+$row[25]+$row[27]+$row[29];

			$ba+=$s;
			$billno=$row[0];
			$CustomerName=$row1[0]." " .$row1[1];
			$BillDate= date('d/m/Y',strtotime($row[2]));
			$RentAmount=$s;
			 $data[]=['billno'=>$billno,'CustomerName'=>$CustomerName,'dt'=>$BillDate,'RentAmount'=>$RentAmount];
		
 }
 	 echo json_encode($data);
CloseCon($con);
 
	?>				
	