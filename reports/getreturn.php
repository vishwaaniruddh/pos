<?php 
// 	require('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

	
	$cust_id=$_GET['cust_id'];
	$frmdate=$_GET['frmdate'];
	$todate=$_GET['todate'];
	$i=0;
	$sumamt=0;
	$sumqty=0;
	//echo "select `bill_id` from `approval` where cust_id='$cust_id' and `bill_id` in (select `bill_id` from `return_qty` where `return_date` between STR_TO_DATE('".$frmdate."','%d/%m/%Y') and STR_TO_DATE('".$todate."','%d/%m/%Y') ) order by bill_id DESC";
	$qryapp=mysqli_query($con,"select `bill_id` from `approval` where cust_id='$cust_id' and `bill_id` in (select `bill_id` from `return_qty` where `return_date` between STR_TO_DATE('".$frmdate."','%d/%m/%Y') and STR_TO_DATE('".$todate."','%d/%m/%Y') ) and status<>'S' order by bill_id DESC");
	echo "<table border='1' width='50%'>";
			echo "<tr><th>Sr No</th><th>Bill_id</th><th>Category</th><th>Item Id</th><th>Return Qty</th><th> Rate</th><th>Return Amt</th><th>Return Date</th></tr>";
			

	while($resapp=mysqli_fetch_row($qryapp)){
		//echo "hii";
			$qryrtn=mysqli_query($con,"select `bill_id`,`item_code`,sum(`qty`),`return_date` from `return_qty` where `bill_id`='$resapp[0]' and (`return_date` between STR_TO_DATE('".$frmdate."','%d/%m/%Y') and STR_TO_DATE('".$todate."','%d/%m/%Y') ) group by `bill_id`,`item_code`,`return_date` order by return_date DESC");
			while($resrtn=mysqli_fetch_row($qryrtn))
			{					//echo "SELECT `qty`,`amount` FROM `approval_detail` WHERE `bill_id`='$resapp[0]' and `item_id`='$qryrtn[1]'";
								$qryprice=mysqli_fetch_row(mysqli_query($con,"SELECT `qty`,`amount` FROM `approval_detail` WHERE `bill_id`='$resapp[0]' and `item_id`='$resrtn[1]' and amount<>'0'"));
								$amt=round($qryprice[1]/$qryprice[0]);

$getcategoryr=mysqli_fetch_row(mysqli_query($con,"SELECT category from phppos_items where name='".$resrtn[1]."'"));

								echo "<tr align='center'><td >".++$i."</td><td>".$resrtn[0]." </td><td >".$getcategoryr[0]."</td><td >".$resrtn[1]."</td><td > ".$resrtn[2]/*QTY*/." </td><td align='right'>".$amt/*AMT*/." </td><td align='right'>".$amt*$resrtn[2]." </td><td>".date('d/m/Y',strtotime($resrtn[3]))." </td></tr>";
								$sumamt+=$amt*$resrtn[2];$sumqty+=$resrtn[2];
								}
								
			
		}
		echo "<tr><td colspan='3' align='right'> Total <td align='center'>$sumqty</td><td></td><td align='right'>$sumamt</td><td></td></tr>";
	echo "</table>";
	CloseCon($con);
?>