<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();



$cat=$_GET['cate'];
$frmdate=$_GET['frmdate'];
$todate=$_GET['todate'];

//echo "SELECT * FROM `phppos_items` WHERE `category`='$cat'";
$qryitems=mysqli_query($con,"SELECT * FROM `phppos_items` WHERE `category`='$cat'");	

echo "<table align='center' width='60%' border='1'><tr><th colspan='5' align='center'> Category Name : $cat </th></tr><tr><th>SrNo</th><th>Item Name</th><th>Sold Qty</th><th>Amount</th><th>Option</th></tr>";
$i=0;
$cnt=0;
$qty=0;
while($resitems=mysqli_fetch_row($qryitems))
{	
//echo $cnt++;
	if($frmdate!="")
	{
	$qryapp=mysqli_query($con,"SELECT sum(amount*(qty-return_qty)/qty),sum(qty-return_qty) FROM `approval_detail` WHERE `item_id`='$resitems[0]' and `bill_id` in (select bill_id from approval where status='s' and(`bill_date` between STR_TO_DATE('".$frmdate."','%d/%m/%Y') and STR_TO_DATE('".$todate."','%d/%m/%Y')))");
	//echo "qwerty";
	}
	else
	{
		//echo "SELECT sum(amount),sum(qty) FROM `approval_detail` WHERE `item_id`='$resitems[0]' and `bill_id` in (select bill_id from approval where status='s'<br>";
	$qryapp=mysqli_query($con,"SELECT sum(amount*(qty-return_qty)/qty),sum(qty-return_qty) FROM `approval_detail` WHERE `item_id`='$resitems[0]' and `bill_id` in (select bill_id from approval where status='s')");	
	}
	
	$resapp=mysqli_fetch_row($qryapp);
	if($resapp[1]>0){
	echo "<tr><td align='center'>".++$i."</td><td align='center'>".$resitems[0]."</td><td align='right'>".$resapp[1]."</td><td align='right'>".$resapp[0]."</td><td>";?><input type='button' value='View Purchase Detail' onclick="popup('<?php echo $resitems[0]; ?>','<?php echo $frmdate; ?>','<?php echo $todate; ?>');"></td><?php 
	$qty+=$resapp[1];$cnt+=$resapp[0];
	
	}
}
echo "<tr><td colspan='2'> Total : </td><td align='right'>$qty</td><td align='right'>$cnt</td><td></td></tr></table>";
CloseCon($con);
?>