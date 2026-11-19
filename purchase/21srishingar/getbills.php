<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$supp_id=$_GET['supp_id'];
$frmdate=$_GET['frmdate'];
$todate=$_GET['todate'];
$ld=$_GET['ld'];
//echo $todate;
$qrytrans='';
$qrysupp=mysqli_query($con,"select `company_name` from `phppos_suppliers` where `person_id`='$supp_id'");
$suppname=mysqli_fetch_row($qrysupp);
if($frmdate==""&&$todate=="")
	{	//$date=date('Y-m-d');
		$qrytrans.="SELECT * FROM `phppos_purchase` WHERE 1 ";
		
	}
	else
      {

   $qrytrans.="SELECT * FROM `phppos_purchase` WHERE 1 and (`date` between STR_TO_DATE('".$frmdate."','%d/%m/%Y') AND  STR_TO_DATE('".$todate."','%d/%m/%Y'))";

      }

	if($supp_id=='-1')
	{
	
	}
	elseif($supp_id!='-1')
	{
	$qrytrans.=	" and `supp_id`='$supp_id' ";	
	}
   if(isset($_GET['type']) && $_GET['type']!='' )
   {
	   if($_GET['type']=='0')
	   $qrytrans.=	" and outstanding <> 0";
	   elseif($_GET['type']=='1')
	   $qrytrans.=	" and outstanding =0";
	   else
	   $qrytrans.=	" ";
   }
 //$qrytrans.=	" group by supp_id";
$i=1;
//echo $qrytrans;
$trans=mysqli_query($con,$qrytrans);
if(mysqli_num_rows($trans)>0)
{
?>
<br/>
<table width="80%" border="1">
<?php //if($supp_id=='-1'){echo "<tr><td colspan='9'></td><tr>"; } else {?> 
<tr id="disname"><td colspan="10" align="center"> <strong>Supplier Name : <?php echo $suppname[0];?></strong></td></tr>
<?php //}?>
<tr><th>Check to Select</th>
<th width="7%">Sr. No</th>
<?php if($supp_id=='-1'){ ?><th width="7%">Supplier ID</th><?php } ?>
<th width="11%">Bill ID</th>
<th width="13%">Supplier's Bill No</th>
<th width="15%">Total Quantity</th>
<th width="13%">Total Amount</th>
<th width="13%">Discount</th>
<th width="13%">Net Amount</th>
<th width="13%">Outstanding</th>

<th width="18%">Bill Date</th>
<th width="23%">Options</th></tr>

<?php
$counter=0;
$ttlpur=0;
$tnetamt=0;
$ttoutstanding=0;
while($restrans=mysqli_fetch_row($trans))
{
	?>
    <tr><td><?php if($restrans[6]!=0){?><input type="checkbox" name="payment[]" class="payment" id="payment" onchange="total();" value="<?php echo $restrans[0];?>"><?php } ?></td>
    <td align="center"><?php echo $i; $i++;?></td>
    <?php if($supp_id=='-1'){ ?>
    <th width="7%"><?php echo $restrans[2]; ?></th><?php } ?><!--Supplier ID-->
    <td align="center"><?php echo $restrans[0];?></td><!--Bill ID-->
    <td align="center"><?php echo $restrans[1]; ?></td><!--Supplier's Bill No-->
    <td align="right"><?php echo $restrans[4]; ?><input type="hidden" name="ttl[]" class='ttl' id="ttl" value="<?php echo $restrans[6]; ?>"></td><!--Total Quantity-->
    
    <td align="right"><?php echo $restrans[5]; $ttlpur+=$restrans[5]; ?> </td><!--Total Amount-->
    <td align="right"><?php echo $restrans[7]; 
	         if($restrans[9]=="percentage")
			 echo " % ";
			 else{echo " Rs ";}
	 ?></td>                 <!--Discount-->
     
    <td align="right"><?php echo $restrans[8]; $tnetamt+=$restrans[8]; ?></td> <!--Paid Amoun*--> 
    
    <td align="right"><?php echo $restrans[6]; $ttoutstanding+=$restrans[6]; ?></td> <!--Outstanding-->
    
    <td align="center"><?php echo date('d/m/Y',strtotime($restrans[3]));?></td>
    <td><a href="view_bill.php?bill_id=<?php echo $restrans[0];?>">View Bill</a></td>
    </tr>
    
	
<?php
$counter=$counter+1;
 }


?>
<tr><td colspan="<?php if($supp_id == '-1')
{ echo "6"; }else echo "5"; ?>" align="right"> Total</td><td align="right"><?php echo $ttlpur;?></td> <td align="right">   </td><td align="right"><?php echo $tnetamt;?> </td><td align="right"> <?php echo $ttoutstanding;?>  </td><td colspan="2"></td></tr>




<tr id="hide"><td colspan="5" align="center"> Amt : </td><td align="right"><input type="text" name="payamt" readonly="readonly" id="payamt" align="right"></td><td colspan="5"><input type="submit" value="Pay Amount" name="paybtn" id="paybtn"></td>
</table>
<?php }

else 
echo "<br/>No Bills";
CloseCon($con);
?>
