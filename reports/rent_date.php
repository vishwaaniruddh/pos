<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$dt=date("Y/m/d");

$from=$_GET['fDate'];
$dat = str_replace('/', '-', $from);
$fromdt = date("Y-m-d", strtotime($dat));



$to=$_GET['tDate'];

$dat_to = str_replace('/', '-', $to);
$date_to = date("Y-m-d", strtotime($dat_to));



$type=$_GET['type'];

if($from!="" && $to!=="" && $type=="Rent")
{
 $qry="SELECT * FROM  `phppos_rent` where bill_date BETWEEN '$fromdt' and '$date_to'" ; 
 //echo "ram".$qry;


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
			$rentSearch = "Rent";
		
			 $data[]=['billno'=>$billno,'CustomerName'=>$CustomerName,'dt'=>$BillDate,'TypeAmount'=>$RentAmount,'searchType'=>$rentSearch];
		
 }
 	 echo json_encode($data);

}
else if($from!="" && $to!=="" && $type=="Sale")
{
    
    
     $res=mysqli_query($con,"SELECT * FROM  `approval` where  bill_date BETWEEN '$fromdt' and '$date_to' ");
    // echo "anand"."SELECT * FROM  `approval` where  bill_date BETWEEN '$fromdt' and '$date_to' ";
     
         $num=mysqli_num_rows($res);



$i=1;
$data=array();
while($row = mysqli_fetch_row($res)) 
 {
 $s1=0;			
$pd=0;
$ba=0;
$na=0;	
$ra=0;
$sql1=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$row[1]'");
$row1=mysqli_fetch_row($sql1);
 
 /* $qry41="SELECT sum(amount) FROM `paid_amount` WHERE `bill_id`='$id' and amt_of='S'";
$res41=mysqli_query($con,$qry41);
$num411=mysqli_num_rows($res41);
$row41=mysqli_fetch_row($res41);*/
///echo $id."/".$num411."<br/>";

 $qry42="SELECT SUM( paid_amount ) FROM  `approval` WHERE  `cust_id` ='$id'";
$res42=mysqli_query($con,$qry42);
$row42=mysqli_fetch_row($res42);

$s=$row3[0]-$row2[0];
$a=0;
$a1=0;

$gstott=0;
$qry4="SELECT *  FROM `approval_detail` WHERE bill_id ='$row[0]'";
$res4=mysqli_query($con,$qry4);

while($row4=mysqli_fetch_row($res4)){

$a=round(($row4[7]/$row4[2])*$row4[4]);
$a1+=$a;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba+=$row4[7];


$gstott=$gstott+$row4[11]+$row4[13]+$row4[15];
}
$pd=$row[4];
$na=$ba;
$s1=$ba-$a1;

$s1+=$gstott;//add gst amounts

$s1+=$row[15];//add card amount
//echo "cust=".$row[11]."paidamt=".$pd."bal amt=".$na."return".$a1."net amt=".$s1."<br/>";
while($row_new = mysqli_fetch_row($res_new)) 
{
$qry10="SELECT *  FROM `approval_detail` WHERE bill_id ='$row_new[0]'";
$res10=mysqli_query($con,$qry10);

while($row10=mysqli_fetch_row($res10)){

$a10=round(($row10[7]/$row10[2])*$row10[4]);
$a11+=$a10;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba10+=$row10[7];
}

$s10=$ba10-$a11;
$app_amount=null;
$app_amount+=$s10;
}
     $sum+=$s1;
    
    
 $billno=$row[0];
 $custfullName=$row1[0]." " .$row1[1];
 $dt=$row[2];
 $BalanceAmount=$s1;
 $saleSearch = "Sale";



 $data[]=['billno'=>$billno,'CustomerName'=>$custfullName,'dt'=>$dt,'TypeAmount'=>$BalanceAmount,'searchType'=>$saleSearch];
 
 
 
}
echo json_encode($data);
    
}CloseCon($con);

	?>		