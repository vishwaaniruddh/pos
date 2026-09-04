<style>
table #tbl{
	
	color:#33F;
	font-style:oblique;}
</style>
<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();



$item_id=$_GET['item_id'];
$frmdate=$_GET['frmdate'];
$todate=$_GET['todate'];
$status=$_GET['status'];
//echo $status;
if($frmdate!="")
{	

// echo "SELECT `cust_id` FROM `approval` WHERE   `bill_id` in(select `bill_id` from `approval_detail` where `item_id` ='$item_id') and ( `bill_date` between STR_TO_DATE('".$frmdate."','%d/%m/%Y') and STR_TO_DATE('".$todate."','%d/%m/%Y'))  group by `cust_id`" ; 

	$qryyr=mysqli_query($con,"SELECT `cust_id` FROM `approval` WHERE   `bill_id` in(select `bill_id` from `approval_detail` where `item_id` ='$item_id') and ( `bill_date` between STR_TO_DATE('".$frmdate."','%d/%m/%Y') and STR_TO_DATE('".$todate."','%d/%m/%Y'))  group by `cust_id`");
	}
else
{
    // echo "SELECT `cust_id` FROM `approval` WHERE `status`='$status' and`bill_id` in(select `bill_id` from `approval_detail` where `item_id` ='$item_id') group by `cust_id`" ;
    
    $qryyr=mysqli_query($con,"SELECT `cust_id` FROM `approval` WHERE `status`='$status' and`bill_id` in(select `bill_id` from `approval_detail` where `item_id` ='$item_id') group by `cust_id`");
}



$arr=array();
$i=0;
while($res=mysqli_fetch_row($qryyr)){
	$arr[$i]=$res[0];
	$i++;
	}
	$custname=array();
	$qty=array();
	//print_r($arr);
	
	$amt=array();
	for($j=0;$j<count($arr);$j++)
	{
		//echo "<tr><td>";
		$qrycust=mysqli_query($con,"Select * from `phppos_people` where person_id='$arr[$j]'");
		$rescust=mysqli_fetch_row($qrycust);
		//$num=mysqli_num_rows($qrycust);
		
		$custname[$j]=$rescust[0]." ".$rescust[1];
		//echo $rescust[0]." ".$rescust[1]." -- ".$arr[$j]."</td>";
		if($frmdate!="")
		{
			$qrybal=mysqli_query($con,"SELECT sum(qty),sum(amount),sum(return_qty) FROM `approval_detail` where `bill_id`in( select `bill_id` from `approval` where `status`='$status' and `cust_id`='$arr[$j]' and (`bill_date` between STR_TO_DATE('".$frmdate."','%d/%m/%Y') and STR_TO_DATE('".$todate."','%d/%m/%Y'))) and `item_id`='$item_id'");
		}
		else{
	   // echo "SELECT sum(qty), sum(amount),sum(return_qty) FROM `approval_detail` where `bill_id`in( select `bill_id` from `approval` where `status`='$status' and `cust_id`='$arr[$j]' and `item_id`='$item_id')" ; 
		    
	$qrybal=mysqli_query($con,"SELECT sum(qty), sum(amount),sum(return_qty) FROM `approval_detail` where `bill_id`in( select `bill_id` from `approval` where `status`='$status' and `cust_id`='$arr[$j]' and `item_id`='$item_id')");}
	$row=mysqli_fetch_row($qrybal);
	$amt[$j]=$row[1];
	$qty[$j]=$row[0];
	$ret[$j]=$row[2];
	
	}
	
	echo "<br/>";
	//print_r($custname);
	//print_r($qty);
	echo "<table width='100%' bgcolor='#CCCCCC' border='1' id='tbl'><tr><td colspan='4' align='center' > <strong>  **Sales Report**<br>Item Name : $item_id</strong></td></tr><tr><th align='center'>Sr No</th><th align='center'>Customer Name</th><th align='center'>Quantity</th><th align='center'>Amount</th></tr>";
	$k=1;
	for($i=0;$i<count($qty);$i++)
	{
	if($qty[$i]>$ret[$i]){
		echo "<tr><td align='center'>".($k)."</td><td align='center'>".$custname[$i]."</td><td align='right'>".($qty[$i]-$ret[$i])."</td><td align='right'>".($qty[$i]-$ret[$i])*$amt[$i]/$qty[$i]."</td></tr>"	;
		$k++;
		}
	}
	echo "</table>";
	
CloseCon($con);

?>