<?php 
ini_set('max_execution_time', -1);
ini_set('memory_limit', -1);

$mycounter = 1 ; 
// include('config.php');

ini_set( "display_errors", 0);

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include('../db_connection.php') ;
$con=OpenSrishringarrCon();


		function gettotalPurchase($item_id){
		    global $con;
		    
		    
		    $sql = mysqli_query($con,"select SUM(qty) as totalQty from phppos_purchase_details where item_id='".$item_id."'");
		    $sql_result = mysqli_fetch_assoc($sql);
		    return $sql_result['totalQty'] ; 
		    
		}
		
    $id1=$_REQUEST['itemcode'];
    $id2=$_REQUEST['categ'];
    $id3=$_REQUEST['barcode'];
    $id4=$_REQUEST['agent'];
    
    if ($id1=="" && $id3=="" && $id4==""){
        $qry="SELECT * FROM  `phppos_items` where category like ('".$id2."%') order by name ASC ";
    }else if($id3=="" && $id2=="" && $id4==""){
        $qry="SELECT * FROM  `phppos_items` where name like ('".$id1."%') order by name ASC ";
    }else if($id1=="" && $id2=="" && $id4==""){
        $qry="SELECT * FROM  `phppos_items` where item_number like ('".$id3."%') order by name ASC ";
    }
    // echo $qry ;
    // $qry .= " LIMIT 1001,1500" ;
    echo $qry ; 
    // return ; 
        
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
$sumap=0;$sumap2=0;
			$sumap1=0;		
		$sold=0;
		$approval=0;	 
		
		
?>
<table  border="1" cellpadding="4" cellspacing="0" width="1032" align="left">
 <tr>
    <th width='68' height="34"><U>Sr.No.</U></th>
    <th width='68' height="34"><U>Item Code</U></th>
    <th width='116'><u>Item Category</u></th>
    <th width='74'><u>Cost Price</u></th>
    <th width='74'><u>Total Cost Price</u></th>
    <th width='87'><U>MRP</U></th>
    <th width='87'><U>Total MRP Amount</U></th>
    <th width='87'><u>Total Purchase</u></th>
    <th width='87'><u>Total Qty</u></th>
    <th width='79'><U>Approval Qty</U></th>
    <th width='58'><U>Sold Qty</U></th>
    <th width='58'><U>Difference Qty</U></th>
    <th width='58'><U>Rent Qty</U></th>
	<th width="97"><u>Balance Qty</u></th>
    <th width="97"><u>Missing Qty</u></th>
	<th width="56"><U>Total Balance Cost Price</U></th>
    <th width='113'><U>Total Sold Qty Amount</U></th>
    <th width='127'><U>Total Approval Qty Amount</U></th>
    <th width="91"><U>Total Rent Amount</U></th>
    <th width="56"><U>Total Balance Amount</U></th>
  </tr>
<?php
$sum=0;
$yy=1;
$sum1=0;
$sum2=0;
$sum3=0;
$sum4=0;
$sum5=0;
$qt1=0;
while($row = mysqli_fetch_row($res)) 
 {
     
     $item_id = $row[9]; 

$a=0;
$a1=0;
$stat="";
$a2=0;
$a4=0;
$stat4=0;
$rentamt=0;
$sq="SELECT * FROM `approval_detail` WHERE `item_id`='$row[0]' and bill_id in (SELECT bill_id FROM `approval` WHERE `status`='A')";
$res2 = mysqli_query($con,$sq);

while($row1=mysqli_fetch_array($res2))
{

$sq3=mysqli_query($con,"SELECT sum(paid_amount) FROM  `approval` WHERE  `bill_id`='$row1[0]' and `status`='A'");
$res3 = mysqli_fetch_row($sq3);
$qt=(float)$row1[2]-(float)$row1[4];
$qt1+=$row1[2];

// $a=round(((float)$row1[7]/(float)$row1[2])*(float)$qt);
if ((float)$row1[2] != 0) {

    $a = round(((float)$row1[7] / (float)$row1[2]) * (float)$qt);
} else {

    $a = 0; 
}



$a1 = (float)$a1 + (float)$a;
$stat=(float)$a1-(float)$res3[0];
}

$sq4="SELECT * FROM `approval_detail` WHERE `item_id`='$row[0]' and bill_id in (SELECT bill_id FROM `approval` WHERE `status`='S')";
$res24 = mysqli_query($con,$sq4);

while($row14=mysqli_fetch_array($res24))
{

$sq34=mysqli_query($con,"SELECT sum(paid_amount) FROM  `approval` WHERE  `bill_id`='$row14[0]' and `status`='S'");
$res34 = mysqli_fetch_row($sq34);

$qt4=(float)$row14[2]-(float)$row14[4];

// $a2=round(((float)$row14[7]/(float)$row14[2])*(float)$qt4);

if ((float)$row14[2] != 0) {
    $a2 = round(((float)$row14[7] / (float)$row14[2]) * (float)$qt4);
} else {
    // Handle the case where $row14[2] is zero, e.g., by setting $a2 to 0 or some other value
    $a2 = 0; // or any other appropriate value or error handling
}




$a4 = (float)$a4 + (float)$a2;
$stat4=(float)$a1-(float)$res34[0];
}

$sq1="SELECT sum(qty),SUM(amount),SUM(RETURN_QTY) FROM `approval_detail` WHERE `item_id`='$row[0]' and bill_id in (SELECT bill_id FROM `approval` WHERE `status`='A')";
$res21 = mysqli_query($con,$sq1);
$row11=mysqli_fetch_row($res21);
	$a=$row11[0];
	$ap=$row11[1];
	$apqt=(float)$row11[0]-(float)$row11[2];
	
	
	
$sq12="SELECT sum(qty),SUM(amount),SUM(RETURN_QTY) FROM `approval_detail` WHERE `item_id`='$row[0]' and bill_id in (SELECT bill_id FROM `approval` WHERE `status`='S')";
$res22 = mysqli_query($con,$sq12);
$row12=mysqli_fetch_row($res22);
	$a=$row12[0];
	$ap=$row12[1];
	$apqt2=(float)$row12[0]-(float)$row12[2];
 
$sq2="SELECT * FROM  `approval_detail` WHERE return_qty =0 and `item_id`='$row[0]'";
$res22 = mysqli_query($con,$sq2);
$row12=mysqli_fetch_row($res22);

$sq1="SELECT SUM( qty ) , SUM( amount ) FROM  `approval_detail` WHERE return_qty =0 and `item_id`='$row[0]'";
$res21 = mysqli_query($con,$sq1);
$row11=mysqli_fetch_row($res21);

$sq1="SELECT  SUM(rent),sum(qty),sum(return_qty),sum(discount),sum(commission_amt*qty) FROM  `order_detail` WHERE `item_id` ='$row[0]' and bill_id in (SELECT bill_id FROM `phppos_rent` WHERE `booking_status`='Picked' or booking_status='' or booking_status='Returned')";
$res4 = mysqli_query($con,$sq1);
$row4=mysqli_fetch_row($res4);
$rentqty=(float)$row4[1]-(float)$row4[2];

if($row4[2]==0){

$rentamt=round($row4[4]);


}else
{
$rentamt=round(((float)$row4[4]/(float)$row4[1])*(float)$row4[2]);
}





$ttl = (float)$row[7]+(float)$apqt+(float)$apqt2+(float)$rentqty;
$itemPurchased = gettotalPurchase($item_id); 

if($itemPurchased){
    $showthisQty = $itemPurchased ;
    $isPurchase=1;
}else{
    $showthisQty = $ttl ;
    $isPurchase=0;
}

/*total balance amount old calculation */ 
//  $ttl1=$row[6]*$row[7];  

  $tcost=(float)$row[5]*(float)$ttl;
  $tmrp=(float)$row[6]*(float)$ttl;
   $tbal=(float)$row[5]*(float)$row[7];
   
   /* total balance amount = total mrp - total rent amount */
   $ttl1=(float)$tmrp-(float)$row4[4];
   
 //  if()
 
 if($ttl1==0)
 {
     
//   echo  $row[0]."<br>";
 }



if($apqt2 > $showthisQty){
    


?>				   
				   
<tr>
<td width="68"><?php echo $yy; ?></td>
<td width="68"><?php echo $row[0]; ?></td>
<td width="116" align="center"><?php echo $row[1]; ?></td>
<td width="74"> <?php echo "Rs.".$row[5]; ?></td>
<td width="74"> <?php echo "Rs.".$tcost; ?></td>
<td width="87"><?php echo "Rs.".$row[6] ?></td>
<td width="74"> <?php echo "Rs.".$tmrp; ?></td>


<td width="87">
    <?php  if($isPurchase==1){
        echo 'this '.$showthisQty ;
    }
    $totalItemPurchased = (float)$totalItemPurchased + (float)$showthisQty ;  ?>
</td>

<td width="87"><?php echo $showthisQty; ?></td>

<td align="left" width="79"><?php echo $apqt;
$sumap = (float)$sumap + (float)$apqt; ?>
</td>




<td align="left" class="Sold_Qty" width="58">
<?php echo  $apqt2;
    $sumap1 = (float)$sumap1 + (float)$apqt2;?>
</td>




<td align="left" width="58"><?php echo abs((float)$apqt2-(float)$showthisQty) ; ?></td>
<td width='58'><?php echo $rentqty; $sumap2 = (float)$sumap2 + (float)$rentqty;?></td>





<td align="left"><?php echo  $row[7]; ?></td>






<?php 
$y=mysqli_query($con,"select max(id) from audit where item_id='".$row[0]."' and status like 'yes' ");
$ty=mysqli_fetch_row($y);
$misqty=mysqli_query($con,"select qty from audit where id='".$ty[0]."' and status like 'yes' ");
$misqty=mysqli_fetch_row($misqty);

?>
<td align="left"><?php echo $tmissing= $row[7]- $misqty[0]; ?></td>

<td align="left"><?php echo "Rs.". $tbal; ?></td>
<td align="left" width="113"><?php echo "Rs.".$a4;	$sold = (float)$sold + (float)$a4; ?></td>
<td align="left" width="127"><?php echo  "Rs.".$a1;$approval = (float)$approval + (float)$a1; ?></td>
<td align="left"><?php echo "Rs.".$row4[4]; ?></td>
<td align="left"><?php echo "Rs.".$ttl1; ?></td>
</tr>
				
			<?php 
			
			$mycounter++ ; 
			
			if($mycounter==20){
			    return ; 
			}
			
}
			$sum=(float)$sum+(float)$row[5];
			$sum1=(float)$sum1+(float)$row[6];
			$sum2=(float)$sum2+(float)$row[7];
			$sum3=(float)$sum3+(float)$ttl;
// 			$showthisQty
			$sum4=(float)$sum4+(float)$row4[4];
			$sum5=(float)$sum5+(float)$ttl1;
			$tcostsum=(float)$tcostsum+(float)$tcost;
			$tmrpsum=(float)$tmrpsum+(float)$tmrp;
			$balsum=(float)$balsum+(float)$tbal;
			$missqty_sum=(float)$missqty_sum+(float)$tmissing;
			
			$yy++; } ?>
            
</table> 
<?php CloseCon($con);?>