<style>
table #tbl{	
	color:#33F;
	font-style:oblique;}
</style>
<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$cust_id=$_GET['cust_id'];


$frmdate=$_GET['frmdate'];
$todate=$_GET['todate'];

$frmdate=str_replace('/','-',$frmdate);
$todate=str_replace('/','-',$todate);
$year1=date('Y',strtotime($frmdate));
$year2=date('Y',strtotime($todate));
$frmdate=date('Y-m-d',strtotime($frmdate));
$todate=date('Y-m-d',strtotime($todate));


//echo "select Year(`bill_date`) from `approval` where `cust_id` ='$cust_id' and `bill_date` >= '".$frmdate."' and `bill_date` <= '".$todate."' group by Year(`bill_date`)";

$qryyr=mysqli_query($con,"select Year(`bill_date`) from `approval` where `cust_id` ='$cust_id' and `bill_date` >= '".$frmdate."' and `bill_date` <= '".
$todate."' group by Year(`bill_date`)");

$arr=array();
$i=0;
while($res=mysqli_fetch_row($qryyr)){
	$arr[$i]=$res[0];
	$i++;
	}
	//print_r($arr);
	echo "<table width='75%' bgcolor='#999999' border='1' id='tbl'><tr><td colspan='2' align='center' > <strong>Year Wise Sales Report</strong></td></tr><tr><td align='center'>Year</td><td align='center'>Total Sale</td></tr>";
	for($j=0;$j<count($arr);$j++)
	{
	//echo "SELECT sum(amount) FROM `approval_detail` where `bill_id`in( select `bill_id` from `approval` where `cust_id`='$cust_id' and Year(`bill_date`)='$arr[$j]')";
	$qrybal=mysqli_query($con,"SELECT sum(amount) FROM `approval_detail` where `bill_id`in( select `bill_id` from `approval` where `cust_id`='$cust_id' and Year(`bill_date`)='$arr[$j]')");
	$row=mysqli_fetch_row($qrybal);
	echo "<tr><td width='60%' align='center'>".$arr[$j].":</td><td align='right'> ".$row[0]."<br></td></tr>";
	}
	echo "</table>";
	
	CloseCon($con);
?>