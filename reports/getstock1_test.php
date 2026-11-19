<?php
// 	include('config.php');
	include('../db_connection.php') ;
$con=OpenSrishringarrCon();

	
	$agent=$_GET['agent'];
	
// 	echo $agent; die;
	$item=array();
	$bill=array();
	$tem1=array();
	$union=array();
	$sumap=0;$sumap2=0;
			$sumap1=0;		
		$sold=0;
		$approval=0;	
		
		$sql="SELECT distinct(item_id) FROM `approval_detail` WHERE `bill_id` in (SELECT bill_id  FROM  `approval` WHERE  `cust_id` ='$agent')group by item_id";
		//echo $sql;
		$result=mysqli_query($con,$sql);
	while($row = mysqli_fetch_row($result)) {
	$item[]=$row[0];
	
	}
	     
	$sql22="SELECT distinct(item_id) FROM `order_detail`  WHERE `bill_id` in (SELECT bill_id FROM  `phppos_rent` WHERE  `cust_id` ='$agent' and (`booking_status`='Picked' or booking_status=''))group by item_id";
	$result22=mysqli_query($con,$sql22);
	while($row22 = mysqli_fetch_row($result22)){
	
	$item1[]=$row22[0];
		
   }
	///print_r($item);
	////print_r($item1);
	if($item1==""){
	$union=$item;
	
	}else{
	
$union = array_unique(array_merge($item, $item1));
}
//print_r($union);

	$y=1;
	?>
    	<table  border="1" cellpadding="4" cellspacing="0" width="1109" align="left">
 <tr>
 <th width='68' height="34"><U>Sr.No.</U></th>
    <th width='68' height="34"><U>Item Code</U></th>
    <th width='116'><u>Item Category</u></th>
    <th width='74'><u>Cost Price</u></th>
    <th width='87'><U>MRP</U></th>
    <!--<th width='87'><u>Total Qty</u></th>-->
     <th width='79'><U>Approval Qty</U></th>
    <th width='58'><U>Sold Qty</U></th>
      <th width='58'><U>Rent Qty</U></th>
	<!--<th width="97"><u>Balance Qty</u></th>-->
    <th width='113'><U>Total Sold Qty Amount</U></th>
       <th width='127'><U>Total Approval Qty Amount</U></th>
    <th width="91"><U>Total Rent Amount</U></th>
    <th width="91"><U>Total Qty</U></th>

  </tr>
	<?php
///print_r($item);
	
	///echo count($union);
 for($i=0;$i<=count($union);$i++){
 $stat4=0;
 $rentamt=0;
 $a=0;
 $a1=0;
 $stat="";
 $a2=0;
 $a4=0;
 if($union[$i]==""){}else{
	
	//echo $union[$i]."<br/>";
	$ct=0;
	$amt=0;
	$ct1=0;
	$amt1=0;
/////echo "SELECT * FROM `approval_detail` WHERE `bill_id` in (SELECT bill_id  FROM  `approval` WHERE  `cust_id` ='$agent' and `status`='A') and item_id= '$union[$i]'<br/>";
$sql1="SELECT * FROM `approval_detail` WHERE `bill_id` in (SELECT bill_id  FROM  `approval` WHERE  `cust_id` ='$agent' and `status`='A') and item_id= '$union[$i]'";
$result1=mysqli_query($con,$sql1);
		
	while($row1 = mysqli_fetch_row($result1)){
	$ab=$row1[2]-$row1[4];
		
	if($item[$i]==$row1[1]){
	
	$ct=$ct+$ab;
	$amt=$amt+$row1[7];
	}else{
	$ct=$ab;
	$amt=$row1[7];
	}
		
	}

////////////////////sold qty
$sql3="SELECT * FROM `approval_detail` WHERE `bill_id` in (SELECT bill_id  FROM  `approval` WHERE  `cust_id` ='$agent' and `status`='S') and item_id ='$union[$i]'";
		$result3=mysqli_query($con,$sql3);
		
	while($row3 = mysqli_fetch_row($result3)){
	$ab1=$row3[2]-$row3[4];
		if($item[$i]==$row3[1]){
	
	$ct1=$ct1+$ab1;
	$am1t=$am1t+$row3[7];
	}else{
	$ct1=$ab1;
	$amt1=$row1[7];
	}
	
	
	}	
///////////////rent qty
$sq21="SELECT  SUM(rent),sum(qty),sum(return_qty),sum(discount),sum(commission_amt) FROM  `order_detail` WHERE `bill_id` in (SELECT bill_id  FROM `phppos_rent` WHERE `cust_id`='$agent') and item_id ='$union[$i]'";
///////////// echo "SELECT  SUM(rent),sum(qty),sum(return_qty),sum(discount),sum(commission_amt) FROM  `order_detail` WHERE `bill_id` in (SELECT bill_id  FROM `phppos_rent` WHERE `cust_id`='$agent') and item_id ='$union[$i]'<br/>";
$res42 = mysqli_query($con,$sq21);
$row42=mysqli_fetch_row($res42);
$rentqty=$row42[1]-$row42[2];
if($row42[2]==0){

$rentamt=round($row42[4]);
///echo $row4[4]."<br/>";

}else{
$rentamt=round(($row42[4]/$row42[1])*$row42[2]);
}

//////total amount of sold qty
 $sq4="SELECT * FROM `approval_detail` WHERE `item_id`='$union[$i]' and bill_id in (SELECT bill_id  FROM  `approval` WHERE  `cust_id` ='$agent' and `status`='S')";
$res24 = mysqli_query($con,$sq4);

while($row14=mysqli_fetch_array($res24))
{

 $sq34=mysqli_query($con,"SELECT sum(paid_amount) FROM  `approval` WHERE  `bill_id`='$row14[0]' and `status`='S'");
$res34 = mysqli_fetch_row($sq34);
//$stat=$res3[8];
$qt4=$row14[2]-$row14[4];
//$qt14+=$row14[2];
/////echo $union[$i]."/".$row14[2]."-".$row14[4]."=".$qt4."<br/>";

$a2=round(($row14[7]/$row14[2])*$qt4);
$a4+=$a2;
$stat4=$a1-$res34[0];
//echo $row[0]."/".$row1[7]."/".$row1[2]."*".$qt."=".$a."Final<br/>";
}
$sq="SELECT * FROM `approval_detail` WHERE `item_id`='$union[$i]' and bill_id in (SELECT bill_id  FROM  `approval` WHERE  `cust_id` ='$agent' and `status`='A')";
$res21 = mysqli_query($con,$sq);

while($row11=mysqli_fetch_array($res21))
{

 $sq3=mysqli_query($con,"SELECT sum(paid_amount) FROM  `approval` WHERE  `bill_id`='$row11[0]' and `status`='A'");
$res3 = mysqli_fetch_row($sq3);
//$stat=$res3[8];
$qt=$row11[2]-$row11[4];
$qt1+=$row11[2];
//echo $row[0]."/".$row1[2]."-".$row1[4]."=".$qt."sum=".$qt1."<br/>";

$a=round(($row11[7]/$row11[2])*$qt);
$a1+=$a;
$stat=$a1-$res3[0];
/////echo $row[0]."/".$row1[7]."/".$row1[2]."*".$qt."=".$a."Final<br/>";
}
//echo $a1."/".$res[0]."/".$stat."<br/>";
///end of approval amount
 
//////item name,category,mrp,cost proce	
$qry2=mysqli_query($con,"SELECT * FROM  `phppos_items` where name='$union[$i]' ");
$row2 = mysqli_fetch_row($qry2);

$ttl=$row2[7]+$ct+$ct1+$rentqty;
// $tct=$ct+$ct1;  // original
 $tct=$ct-$ct1;

/*$qry3=mysqli_query($con,"SELECT SUM( qty - return_qty ) FROM  `approval_detail` WHERE item_id =  '$union[$i]' AND bill_id IN (SELECT bill_id FROM  `approval` WHERE  `cust_id` =  '$agent' AND  `status` =  'A')");
$row3 = mysqli_fetch_row($qry3);

$qry4=mysqli_query($con,"SELECT SUM( qty - return_qty ) FROM  `approval_detail` WHERE item_id =  '$union[$i]' AND bill_id IN (SELECT bill_id FROM  `approval` WHERE  `cust_id` =  '$agent' AND  `status` =  'S')");
$row4 = mysqli_fetch_row($qry4);
//echo $row3[0];*/


?>				   
				   
<tr>
<td width="68"><?php echo $y; ?></td>
<td width="68"><?php echo $union[$i]; ?></td>
<td width="116" align="center"><?php echo $row2[1]; ?></td>
<td width="74"> <?php echo "Rs.".$row2[5]; ?></td>
<td width="87"><?php echo "Rs.".$row2[6] ?></td>
<!--<td width="87"><?php// echo $ttl; ?></td>-->
<td  align="left" width="79"><?php echo $ct; $sumap+=$ct; ?></td>
<td  align="left" width="58"><?php echo $ct1;  $sumap1+=$ct1;?></td>
<td width='58'><?php echo $rentqty; $sumap2+=$rentqty;?></td>
 
<!--  <td align="left"><?php //echo $row2[7]; ?></td>-->
<td align="left" width="113"><?php echo "Rs.".$a4;	$sold+=$a4; ?></td>
<td align="left" width="127"><?php echo  "Rs.".$a1; $approval+=$a1; ?></td>
<td align="left"><?php echo "Rs.".$rentamt; ?></td>
<td align="left"><?php echo "Rs".$tct; ?></td>

</tr>
				
			<?php 	
			$sum=$sum+$row2[5];
			$sum1=$sum1+$row2[6];
			$sum2=$sum2+$row[7];
			$sum3=$sum3+$ttl;
			$sum4=$sum4+$rentamt;
			
			$y++; } }?>
			 <tr><td colspan="3" align="right"><b>Total</b> </td><td><?php echo "Rs.".$sum; ?></td>
            <td><?php echo "Rs.".$sum1; ?></td>
            <!--<td><?php //echo $sum3; ?></td>-->
            <td><?php echo $sumap; ?></td>
            <td><?php echo $sumap1; ?></td>
            <td><?php echo $sumap2; ?></td>
            <!--<td><?php //echo $sum2; ?></td>-->
            <td><?php echo "Rs.".$sold; ?></td>
            <td><?php echo  "Rs.".$approval; ?></td>
            <td ><?php echo "Rs.".$sum4; ?></td></tr>
</table> 
<?php CloseCon($con);?>	
	