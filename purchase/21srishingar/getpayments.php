<?php
// include('config.php');
include('../../db_connection.php') ;
$con=OpenSrishringarrCon();


$supp_id=$_GET['supp_id'];
$frmdate=$_GET['frmdate'];
$todate=$_GET['todate'];
//$ld=$_GET['ld'];
//echo $todate;
$qrytrans='';
$qrysupp=mysqli_query($con,"select `company_name` from `phppos_suppliers` where `person_id`='$supp_id'");
$suppname=mysqli_fetch_row($qrysupp);
if($frmdate==""&&$todate=="")
	{	//$date=date('Y-m-d');
		$qrytrans.="SELECT * FROM `phppos_purchase_payments` WHERE 1 ";
		
	}
	else
{

  $qrytrans.="SELECT * FROM `phppos_purchase_payments` WHERE 1 and (`paid_date` between STR_TO_DATE('".$frmdate."','%d/%m/%Y') AND  STR_TO_DATE('".$todate."','%d/%m/%Y'))";

}

	if($supp_id=='-1')
	{
	
	}
	elseif($supp_id!='-1')
	{
	$qrytrans.=	" and `bill_no` in (select pur_id from phppos_purchase where supp_id='$supp_id')";	
	}
   
 $qrytrans.=	" order by paid_date";
$i=1;
//echo $qrytrans;
$trans=mysqli_query($con,$qrytrans);
if(mysqli_num_rows($trans)>0)
{
	
	
?>
<br/>
<table width="80%" border="1">
<?php //if($supp_id=='-1'){echo "<tr><td colspan='9'></td><tr>"; } else {?> 
<tr id="disname"><td colspan="9" align="center"> <strong>Supplier Name : <?php echo $suppname[0];?></strong></td></tr>
<?php //}?>
<tr>
<th width="7%">Sr. No</th>
<?php if($supp_id=='-1'){ ?><th width="7%">Supplier Name</th><?php } ?>
<th width="11%">Bill ID</th>
<th width="13%">Paid Amount</th>
<th width="18%">Payment Date</th>
<th width="23%">Mode</th></tr>

<?php
$ttlpur=0;
while($restrans=mysqli_fetch_row($trans))
{
	$qrys=mysqli_query($con,"select `company_name` from `phppos_suppliers` where `person_id`=(select supp_id from phppos_purchase where pur_id ='$restrans[1]')");    
	  $res=mysqli_fetch_row($qrys);
?>
    <tr>
    <td align="center"><?php echo $i; $i++;?></td>
    <?php if($supp_id=='-1'){ 
	//echo "select `company_name` from `phppos_suppliers` where `person_id`=(select supp_id from phppos_purchase where bill_id ='$restrans[1]')";
	  
	  
	?>
    <th width="12%"><?php echo $res[0]; ?></th><?php }?>
    
    <td align="center"><?php echo $restrans[1];?></td>
    <td align="center"><a target="_blank" href="voucher.php?pur_id=<?php echo $restrans[1];?>&sup_name=<?php echo $res[0];?>"><?php echo $restrans[3]; ?> </a></td>
    <td align="right"><?php echo $restrans[4]; ?></td>
    <td align="right"><?php echo $restrans[2]; ?></td>
    
    </tr>
    
	
<?php $ttlpur+=$restrans[3]; }


?>
<tr><td colspan="<?php if($supp_id=='-1') echo '5'; else { echo '4';}?>" align="left"> Total Payment</td> <td align="right"><?php echo $ttlpur;?></td></tr>



<!--
<tr id="hide"><td colspan="3" align="center"> Amt : </td><td align="right"><input type="text" name="payamt" readonly="readonly" id="payamt" align="right"></td>
<td colspan="3"><input type="submit" value="" name="paybtn" id="paybtn"></td></tr> -->
</table>
<?php }

else 
echo "<br/>No Bills";
CloseCon($con);
?>
