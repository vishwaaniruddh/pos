<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$frmdate=$_GET['frmdate'];
$todate=$_GET['todate'];
$frmdate=str_replace('/','-',$frmdate);
$todate=str_replace('/','-',$todate);
if($frmdate=="")
$frmdate=date('Y-m-d',strtotime('today'));
else
$frmdate=date('Y-m-d',strtotime($frmdate));
if($todate=="")
$todate=date('Y-m-d',strtotime('today'));
else
$todate=date('Y-m-d',strtotime($todate));

//echo "select `cust_id`  from `approval` where `bill_date` between '".$frmdate."' and '".$todate."'  group by `cust_id` DESC";

 //$qryapp=mysqli_query($con,"select `cust_id`  from `approval` where `bill_date` between '".$frmdate."' and '".$todate."'  group by `cust_id` DESC");
	 
	// echo "select * from phppos_people where person_id in (select distinct(`cust_id`) from `phppos_rent` where `bill_date` between '".$frmdate."' and '".$todate."' ) order by last_name";
	
	$stry="select * from phppos_people where person_id in ";
	
	if($_GET["invno"]!="")
	{
	     $stry.="(select distinct(`cust_id`) from `phppos_rent` where (bill_id='".$_GET["invno"]."' or new_bill_number='".$_GET["invno"]."'))  order by last_name";
	  
	}
	else
	{
	    
	    $stry.="(select distinct(`cust_id`) from `phppos_rent` where  `bill_date` between '".$frmdate."' and '".$todate."')  order by last_name";
	}
	
// 	echo $stry;
// return ; 
  $qryapp = mysqli_query($con,$stry); ?>
     
     <table border="1" id="tbl">
     <tr><th colspan="7" align="center"> Rent Report Customer Wise</th></tr>
     <tr><th>Sr. No.</th><th>Customer Name</th><th>Last Name</th><th>Contact No.</th><th>Total Rent</th></tr>
     <?php $i=1; $appttl=0;$soldttl=0;
	 while($app=mysqli_fetch_row($qryapp)){
	  //echo "SELECT * FROM  `phppos_people` where person_id='$app[0]' order by `first_name` ASC ";
		// $result = mysqli_query($con,"SELECT * FROM  `phppos_people` where person_id='$app[0]'");
	  //$row1 = mysqli_fetch_row($result);
	  if($app[0]=="")
	  continue;
	 ?>
     <tr><td><?php echo $i;?></td>
     <td><?php echo $app[0]."   ". $app[1]; ?> </td><td><?php echo  $app[1]; ?> </td><td ><?php echo $app[2]?></td><td align="right"><?php
     
     	if($_GET["invno"]=="")
	{
     $qrybal=mysqli_query($con,"SELECT sum(amount) FROM `phppos_rent` where `cust_id`='$app[11]'  and ( `bill_date` between '".$frmdate."' and '".$todate."')");
	}else
	{
	   $qrybal=mysqli_query($con,"SELECT sum(amount) FROM `phppos_rent` where `cust_id`='$app[11]'  and bill_id='".$_GET["invno"]."'"); 
	    
	}
$row=mysqli_fetch_row($qrybal);
$appd=$row[0];
/*while($row=mysqli_fetch_row($qrybal)){
$appd+=($row[0]-$row[1])*$row[2]/$row[0];
}*/
$appttl+=$appd;
echo $appd; ?></td>
</tr>
       <?php $i++;}
	   echo "<tr><td colspan='3' align='right'> <font color='RED'></font></td><td align='right'><font color='RED'>Total :&nbsp;&nbsp;</font></td><td align='right'><font color='RED'>".$appttl."</font></td></tr>";
	    ?>
           </table>
       <?php CloseCon($con);?>
    
           