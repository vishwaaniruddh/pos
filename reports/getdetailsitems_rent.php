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
$frmdate=str_replace('/','-',$frmdate);
$todate=str_replace('/','-',$todate);
$status=$_GET['status'];

$checkItem = 0; 
if($frmdate==""){
    $frmdate=date('Y-m-d',strtotime('today'));    
    $checkItem=1 ;
}
else{
    $frmdate=date('Y-m-d',strtotime($frmdate));    
}

if($todate=="")
$todate=date('Y-m-d',strtotime('today'));
else
$todate=date('Y-m-d',strtotime($todate));

/*echo "select * from phppos_people where person_id in (select distinct(`cust_id`) from `phppos_rent` where `bill_date` between '".$frmdate."' and '".$todate."' and (booking_status='Picked' or booking_status='Returned')) order by last_name";*/
//echo $status;
if($frmdate!="" && $checkItem==0)
{	
	
	echo "select * from phppos_people where person_id in (select distinct(`cust_id`) from `phppos_rent` where `bill_date` between '".$frmdate."' and '".$todate."' and (booking_status='Picked' or booking_status='Returned')) order by last_name" ;
	$qryyr=mysqli_query($con,"select * from phppos_people where person_id in (select distinct(`cust_id`) from `phppos_rent` where `bill_date` between '".$frmdate."' and '".$todate."' and (booking_status='Picked' or booking_status='Returned')) order by last_name");
	}else if($item_id!=''){

echo "SELECT `bill_id` FROM `phppos_rent` WHERE `bill_id` in(select `bill_id` from `approval_detail` where `item_id` ='$item_id') ";
$qryyr=mysqli_query($con,"SELECT `bill_id` FROM `phppos_rent` WHERE `bill_id` in(select `bill_id` from `approval_detail` where `item_id` ='$item_id') ");	    
	}
else
{
// echo "select `bill_id` from `order_detail` where `item_id` ='$item_id'";
$qryyr=mysqli_query($con,"SELECT `bill_id` FROM `phppos_rent` WHERE `bill_id` in(select `bill_id` from `order_detail` where `item_id` ='$item_id') ");
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
	$qrybal=mysqli_query($con,"SELECT sum(qty), sum(amount),sum(return_qty) FROM `approval_detail` where `bill_id`in( select `bill_id` from `approval` where `status`='$status' and `cust_id`='$arr[$j]' and `item_id`='$item_id')");}
	$row=mysqli_fetch_row($qrybal);
	$amt[$j]=$row[1];
	$qty[$j]=$row[0];
	$ret[$j]=$row[2];
	//echo "<td align='right'> ".$row[0]."</td>";
	//echo "</tr>";
	}
	//print_r($custname);
	//print_r($qty);
	/*
	for($i=0;$i<count($qty);$i++)
	{
		for($j=0;$j<(count($qty)-1);$j++)
		{
			if($qty[$j]<$qty[$j+1])
			{
				$temp1=$qty[$j];$temp=$custname[$j];$temp2=$amt[$j];
				$qty[$j]=$qty[$j+1];$custname[$j]=$custname[$j+1]; $amt[$j]=$amt[$j+1];	
				$qty[$j+1]=$temp1; $custname[$j+1]=$temp;$amt[$j+1]=$temp2;
			}	
		}	
	}*/
	echo "<br/>";
	//print_r($custname);
	//print_r($qty);
	echo "<table width='100%' bgcolor='#CCCCCC' border='1' id='tbl'><tr><td colspan='4' align='center' > <strong>  **Rent Report**<br>Item Name : $item_id</strong></td></tr><tr><th align='center'>Sr No</th><th align='center'>Customer Name</th><th align='center'>Quantity</th><th align='center'>Amount</th></tr>";
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