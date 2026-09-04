<?php 
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


ini_set( "display_errors", 0);

	$id1=$_REQUEST['itemcode'];
        $id2=$_REQUEST['categ'];
        $id3=$_REQUEST['barcode'];
		$id4=$_REQUEST['agent'];
        
        if ($id1=="" && $id3=="" && $id4==""){
        	$qry="SELECT * FROM  `phppos_items` where category like ('".$id2."%') order by name ASC ";
        }
        else if($id3=="" && $id2=="" && $id4==""){
        	$qry="SELECT * FROM  `phppos_items` where name like ('".$id1."%') order by name ASC ";
        }
        else if($id1=="" && $id2=="" && $id4==""){
        	$qry="SELECT * FROM  `phppos_items` where item_number like ('".$id3."%') order by name ASC ";
        }
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
    <th width='87'><u>Total Qty</u></th>
    <th width='79'><U>Approval Qty</U></th>
    <th width='58'><U>Sold Qty</U></th>
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
$qt=$row1[2]-$row1[4];
$qt1+=$row1[2];

$a=round(($row1[7]/$row1[2])*$qt);
$a1+=$a;
$stat=$a1-$res3[0];
}

 $sq4="SELECT * FROM `approval_detail` WHERE `item_id`='$row[0]' and bill_id in (SELECT bill_id FROM `approval` WHERE `status`='S')";
$res24 = mysqli_query($con,$sq4);

while($row14=mysqli_fetch_array($res24))
{

$sq34=mysqli_query($con,"SELECT sum(paid_amount) FROM  `approval` WHERE  `bill_id`='$row14[0]' and `status`='S'");
$res34 = mysqli_fetch_row($sq34);

$qt4=$row14[2]-$row14[4];

$a2=round(($row14[7]/$row14[2])*$qt4);
$a4+=$a2;
$stat4=$a1-$res34[0];
}

$sq1="SELECT sum(qty),SUM(amount),SUM(RETURN_QTY) FROM `approval_detail` WHERE `item_id`='$row[0]' and bill_id in (SELECT bill_id FROM `approval` WHERE `status`='A')";
$res21 = mysqli_query($con,$sq1);
$row11=mysqli_fetch_row($res21);
	$a=$row11[0];
	$ap=$row11[1];
	$apqt=$row11[0]-$row11[2];
	
	echo $apqt."<br>";
	
	
$sq12="SELECT sum(qty),SUM(amount),SUM(RETURN_QTY) FROM `approval_detail` WHERE `item_id`='$row[0]' and bill_id in (SELECT bill_id FROM `approval` WHERE `status`='S')";
$res22 = mysqli_query($con,$sq12);
$row12=mysqli_fetch_row($res22);
	$a=$row12[0];
	$ap=$row12[1];
	$apqt2=$row12[0]-$row12[2];
	
	echo $apqt2."<br>";
 
$sq2="SELECT * FROM  `approval_detail` WHERE return_qty =0 and `item_id`='$row[0]'";
$res22 = mysqli_query($con,$sq2);
$row12=mysqli_fetch_row($res22);

$sq1="SELECT SUM( qty ) , SUM( amount ) FROM  `approval_detail` WHERE return_qty =0 and `item_id`='$row[0]'";
$res21 = mysqli_query($con,$sq1);
$row11=mysqli_fetch_row($res21);

$sq1="SELECT  SUM(rent),sum(qty),sum(return_qty),sum(discount),sum(commission_amt) FROM  `order_detail` WHERE `item_id` ='$row[0]' and bill_id in (SELECT bill_id FROM `phppos_rent` WHERE `booking_status`='Picked' or booking_status='' or booking_status='Returned')";
$res4 = mysqli_query($con,$sq1);
$row4=mysqli_fetch_row($res4);
$rentqty=$row4[1]-$row4[2];

echo $rentqty."<br>";

if($row4[2]==0){

$rentamt=round($row4[4]);


}else
{
$rentamt=round(($row4[4]/$row4[1])*$row4[2]);
}

 $ttl=$row[7]+$apqt+$apqt2+$rentqty;
 

 
 $ttl1=$row[6]*$row[7];
  $tcost=$row[5]*$ttl;
  $tmrp=$row[6]*$ttl;
   $tbal=$row[5]*$row[7];
   
 //  if()
 
 if($ttl1==0)
 {
     
//   echo  $row[0]."<br>";
 }
   
?>				   
				   
<tr>
<td width="68"><?php echo $yy; ?></td>
<td width="68"><?php echo $row[0]; ?></td>
<td width="116" align="center"><?php echo $row[1]; ?></td>
<td width="74"> <?php echo "Rs.".$row[5]; ?></td>
<td width="74"> <?php echo "Rs.".$tcost; ?></td>
<td width="87"><?php echo "Rs.".$row[6] ?></td>
<td width="74"> <?php echo "Rs.".$tmrp; ?></td>
<td width="87"><?php echo $ttl; ?></td>
<td align="left" width="79"><?php echo $apqt; $sumap+=$apqt; ?></td>
<td align="left" width="58"><?php echo $apqt2;  $sumap1+=$apqt2;?></td>
<td width='58'><?php echo $rentqty; $sumap2+=$rentqty;?></td>
<td align="left"><?php echo $row[7]; ?></td>

<?php 
$y=mysqli_query($con,"select max(id) from audit where item_id='".$row[0]."' and status like 'yes' ");
$ty=mysqli_fetch_row($y);
$misqty=mysqli_query($con,"select qty from audit where id='".$ty[0]."' and status like 'yes' ");
$misqty=mysqli_fetch_row($misqty);

?>
<td align="left"><?php echo $tmissing= $row[7]- $misqty[0]; ?></td>

<td align="left"><?php echo $tbal; ?></td>
<td align="left" width="113"><?php echo "Rs.".$a4;	$sold+=$a4; ?></td>
<td align="left" width="127"><?php echo  "Rs.".$a1;$approval+=$a1; ?></td>
<td align="left"><?php echo "Rs.".$row4[4]; ?></td>
<td align="left"><?php echo "Rs.".$ttl1; ?></td>
</tr>
				
			<?php 	
			$sum=$sum+$row[5];
			$sum1=$sum1+$row[6];
			$sum2=$sum2+$row[7];
			$sum3=$sum3+$ttl;
			$sum4=$sum4+$row4[4];
			$sum5=$sum5+$ttl1;
			$tcostsum=$tcostsum+$tcost;
			$tmrpsum=$tmrpsum+$tmrp;
			$balsum=$balsum+$tbal;
			$missqty_sum=$missqty_sum+$tmissing;
			
			$yy++; } ?>
            <tr>
            <td colspan="3" align="right"><b>Total</b> </td>
            <td><?php echo "Rs.".$sum; ?></td>
            <td><?php echo "Rs.".$tcostsum; ?></td>
            <td><?php echo "Rs.".$sum1; ?></td>
            <td><?php echo "Rs.".$tmrpsum; ?></td>
            <td><?php echo $sum3; ?></td>
            <td><?php echo $sumap; ?></td>
            <td><?php echo $sumap1; ?></td>
            <td><?php echo $sumap2; ?></td>
            <td><?php echo $sum2; ?></td>
            
            <td><?php echo $missqty_sum; ?></td>
            
             <td><?php echo "Rs.".$balsum; ?></td>
            <td><?php echo "Rs.".$sold; ?></td>
            <td><?php echo  "Rs.".$approval; ?></td>
            <td><?php echo "Rs.".$sum4; ?></td>
            <td><?php echo "Rs.".$sum5; ?></td>
            </tr>
</table> 
<?php CloseCon($con);?>