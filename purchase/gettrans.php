<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$bank_id=$_GET['bank_id'];
$frmdate=$_GET['frmdate'];
$todate=$_GET['todate'];
$ld=$_GET['ld'];
//echo $todate;
//$todate= date(strtotime($todate."+1 days"));
$balance=0;
$payment=0;
$income=0;

$qrybank=mysqli_query($con,"select `bank_name` from `banks` where `bank_id`='$bank_id'");
$bankname=mysqli_fetch_row($qrybank);
if($ld=="ld")

{	
   
    $date=date('Y-m-d');
	 $qrytrans="select * from `bank_transaction` where  `trans_date`='$date'";
	//$frmdate=$date;
	//echo "SELECT * FROM `bank_transaction` where `trans_date`<'$date'";
        $qrybal=mysqli_query($con,"SELECT * FROM `bank_transaction` where `trans_date`<'$date'");
	}
	else
	if($frmdate=="" && $todate=="")
	{
	    $date=date('Y-m-d');
	     
		$qrytrans="select * from `bank_transaction` where  `trans_date`='$date' and `bank_id`='$bank_id'";
		//echo $qrytrans
		$qrybal=mysqli_query($con,"SELECT * FROM `bank_transaction` where  `bank_id`='$bank_id'");
	}
	else
{
    
  //echo "select * from `bank_transaction` where bank_id='$bank_id' and (`trans_date` between STR_TO_DATE('".$frmdate."','%d/%m/%Y') AND  STR_TO_DATE('".$todate."','%d/%m/%Y'))";
  $qrytrans="select * from `bank_transaction` where bank_id='$bank_id' and (`trans_date` between STR_TO_DATE('".$frmdate."','%d/%m/%Y') AND  STR_TO_DATE('".$todate."','%d/%m/%Y'))";
//echo "SELECT * FROM `bank_transaction` where `bank_id`='$bank_id' and `trans_date`<STR_TO_DATE('".$frmdate."','%d/%m/%Y')";
 $qrybal=mysqli_query($con,"SELECT * FROM `bank_transaction` where `bank_id`='$bank_id' and `trans_date`<STR_TO_DATE('".$frmdate."','%d/%m/%Y')");
//echo "SELECT * FROM `bank_transaction` where `bank_id`='$bank_id' and `trans_date`<=STR_TO_DATE('".$frmdate."','%d/%m/%Y')";
}
	
while($resbal=mysqli_fetch_row($qrybal))
{
  //echo $resbal[2]."</br>";
    
	if($resbal[2]=="payment" || $resbal[2]=="banktrans")
	{
		$payment+=$resbal[3];	
	}
	else if($resbal[2]=="receit")
	{
		$income+=$resbal[3];	
	}
}
$balance=$income-$payment;
//echo "$balance=$income-$payment"; 

$i=1;

// echo $qrytrans ; 
$trans=mysqli_query($con,$qrytrans);

?>
<table width="70%" border="1">
<tr><th width="9%">Sr. No</th><th width="20%">Transaction ID</th><th width="15%">Date</th><th width="18%">Debit </th><th width="15%">Credit</th><th width="23%">Memo</th></tr>
<tr><td colspan="3"align="right" > Balance Brought Forward</td><td align="right"><?php //if($balance<0) echo $balance?></td><td align="right"><?php  echo $balance?></td></tr>
<?php
$cu_pay=0; $cu_income=0;
while($restrans=mysqli_fetch_row($trans))
{
	?>
    <tr>
    <td align="center"><?php echo $i; ?></td>
    <td align="center"><?php echo $restrans[0]; ?></td>
    <td align="center"><?php echo date('d/m/Y',strtotime($restrans[4]))?></td>
    <td align="right"><?php if($restrans[2]=="payment" || $restrans[2]=="banktrans"){echo $restrans[3];  $cu_pay+=$restrans[3];} ?></td>
    <td align="right"><?php if($restrans[2]=="receit"){echo $restrans[3]; $cu_income+=$restrans[3];} ?></td>
    <td align="justify">
    	<div id="showrem<?php echo $i; ?>1" style="display:block">
    		<?php echo $restrans[5];?>
    	</div>
    </td>
    <td align="justify">
    	<input type="button" onclick="showrem('showrem<?php echo $i; ?>')" value="Update" style="background:#FFFF99" />
	<div id="showrem<?php echo $i; ?>" style="display:none">	
	<textarea id="rem<?php echo $i; ?>"><?php echo $restrans[5];?></textarea>
	<input type="button" onClick="edit_memo('<?php echo $i; ?>','<?php echo $restrans[0] ?>')" value="Edit">
	
	</div>
	<form action="delete_transac.php" method="post" onsubmit="return typealert();" >
	<input type="hidden" name="trans_id" value="<?php echo $restrans[0] ?>"/>
	<input type="submit" value="Delete"/>
	</form>
    </td>
   </tr>
    
	
<?php 
$i++;
}
$outstand=$income+$cu_income-$payment-$cu_pay;

//echo "$outstand=$income+$cu_income-$payment-$cu_pay";
if($cu_income>0 || $cu_pay>0)
{
?>
<tr><td colspan="3" align="right"> Total</td><td align="right"><?php echo $cu_pay;?></td><td align="right"><?php echo $cu_income;?></td><td></td></tr>
<?php }?>
<tr><td colspan="3" align="right">Balance Amount : </td><td colspan="2" align="right"><?php echo $outstand;?></td></tr>
</table>