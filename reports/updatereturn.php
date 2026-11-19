<?php
ini_set( "display_errors", 0);

// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$prz=$_POST['qty'];
$cid=$_POST['id'];
$aid=$_POST['aid'];
$it=$_POST['it'];
$amt=$_POST['amt'];
$bill_date=$_POST['bill_date'];
$a=count($it);
$d=count($aid);
///echo "update approval set return_date=STR_TO_DATE('".$bill_date."','%d/%m/%Y'),paid_amount=paid_amount+$amt where bill_id='$cid'";
if($amt=="" || $amt=="0"){}
else{
mysqli_query($con,"insert into `paid_amount`(bill_id,amount,return_date) values('$cid','$amt',STR_TO_DATE('".$bill_date."','%d/%m/%Y'))"); 
}

if($bill_date==""){
 mysqli_query($con,"update approval set paid_amount=paid_amount+$amt where bill_id='$cid'");
 }else{
 
 mysqli_query($con,"update approval set `return_date` = STR_TO_DATE('".$bill_date."','%d/%m/%Y') where bill_id='$cid'"); 
  mysqli_query($con,"update approval set paid_amount=paid_amount+$amt where bill_id='$cid'");
 }

if(isset($_POST['Submit'])){

 
 
 for($i=0;$i<$d;$i++)
{
$sql1=mysqli_query($con,"select * from  approval_detail where aid='".$aid[$i]."'");
$row=mysqli_fetch_row($sql1);

if(($row[2]-$row[4])>=$prz[$i]){
//echo $it[$i].">".$prz[$i]."<br/>";
if($prz[$i]=="" || $prz[$i]==0){
 
}else {

 mysqli_query($con,"insert into `return_qty`(bill_id,qty,return_date,item_code) values('$cid','$prz[$i]',STR_TO_DATE('".$bill_date."','%d/%m/%Y'),'$it[$i]')");
 
 } 
 
 mysqli_query($con,"update approval_detail set return_qty=return_qty+$prz[$i] where aid='".$aid[$i]."'");
mysqli_query($con,"update `phppos_items` set quantity=quantity+$prz[$i] WHERE name='".$it[$i]."'");

}else{
//echo $aid[$i]." / ".$prz[$i]."<br/>";

?>
<p>The entered amount is Greater than Bill Amount.<br/>
<a href="/app_detail.php?id=<?php echo $cid; ?>">Back</a>
 <?php
 break;
 }
}
header('location:/pos/home_dashboard.php');
}
else{
//echo "hi";
  for($i=0;$i<$d;$i++)
{
$sql1=mysqli_query($con,"select * from  approval_detail where aid='".$aid[$i]."'");
$row=mysqli_fetch_row($sql1);

if(($row[2]-$row[4])>=$prz[$i]){

if($prz[$i]=="" || $prz[$i]==0){
 } else{
 

  mysqli_query($con,"insert into `return_qty`(bill_id,qty,return_date,item_code) values('$cid','$prz[$i]',STR_TO_DATE('".$bill_date."','%d/%m/%Y'),'$it[$i]')");
 
 }
 
 mysqli_query($con,"update approval_detail set return_qty=return_qty+$prz[$i] where aid='".$aid[$i]."'");
 mysqli_query($con,"update `phppos_items` set quantity=quantity+$prz[$i] WHERE name='".$it[$i]."'");
 mysqli_query($con,"update approval set status='S' where bill_id='$cid'");
 }else{
//echo $aid[$i]." / ".$prz[$i]."<br/>";

?>
<?php CloseCon($con);?>
<p>The entered amount is Greater than Bill Amount.<br/>
<a href="../../../application/views/reports/app_detail.php?id=<?php echo $cid; ?>">Back</a>
 <?php
 break;
 }
}
header('location:../../../index.php/reports');
 }

?>