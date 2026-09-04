<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$bank_id=$_GET['bank_id'];
$frmdate=$_GET['frmdate'];
$todate=$_GET['todate'];
//echo $todate;
//$todate= date(strtotime($todate."+1 days"));
$balance=0;

$payment=0;
$income=0;

$qrybank=mysqli_query($con,"select `bank_name` from `banks` where `bank_id`='$bank_id'");
$bankname=mysqli_fetch_row($qrybank);
if($frmdate==""&&$todate=="")
	{	$date=date('Y-m-d');
		$qrytrans="select * from `bank_transaction` where  `trans_date`='$date' and `bank_id`='$bank_id'";
		$qrybal=mysqli_query($con,"SELECT * FROM `bank_transaction` where `trans_date`<'$date' and `bank_id`='$bank_id'");
	}
	else
{

  $qrytrans="select * from `bank_transaction` where bank_id='$bank_id' and (`trans_date` between STR_TO_DATE('".$frmdate."','%d/%m/%Y') AND  STR_TO_DATE('".$todate."','%d/%m/%Y'))";
//echo "SELECT * FROM `bank_transaction` where `bank_id`='$bank_id' and `trans_date`<STR_TO_DATE('".$frmdate."','%d/%m/%Y')";
 $qrybal=mysqli_query($con,"SELECT * FROM `bank_transaction` where `bank_id`='$bank_id' and `trans_date`<STR_TO_DATE('".$frmdate."','%d/%m/%Y')");
//echo "SELECT * FROM `bank_transaction` where `bank_id`='$bank_id' and `trans_date`<=STR_TO_DATE('".$frmdate."','%d/%m/%Y')";
}
	
while($resbal=mysqli_fetch_row($qrybal))
{
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
//echo $balance;

$i=0;
$trans=mysqli_query($qrytrans);

CloseCon($con);
?>
<table width="70%" border="1">
<tr><th width="7%">Sr. No</th><th width="12%">TransID</th><th width="13%">Date</th><th width="17%">Credit</th><th width="18%">Debit</th><th width="25%">Memo</th><th width="8%">Check for Reconcile </th></tr>
<tr><td colspan="3"align="right" > Balance Brought Forword</td><td align="right"><?php //if($balance<0) echo $balance?></td><td align="right"><?php  echo $balance?></td></tr>
<?php
$cu_pay=0; $cu_income=0;
while($restrans=mysqli_fetch_array($trans))
{
	?>
    <tr><td align="center"><?php echo $i+1; ?></td>
    <td align="center"><input type="hidden" name="trans_id[]" value="<?php echo $restrans[0]; ?>" class="trans_id"><?php echo $restrans[0]; ?></td>
    <td align="center"><?php echo date('d/m/Y',strtotime($restrans[4]))?></td>
    <td align="right"><?php if($restrans[2]=="payment" || $restrans[2]=="banktrans"){echo $restrans[3];  $cu_pay+=$restrans[3];} ?></td>
    <td align="right"><?php if($restrans[2]=="receit"){echo $restrans[3]; $cu_income+=$restrans[3];	} ?></td>
    <td align="justify"><?php echo $restrans[5];?></td>
    <td><?php if($restrans[6]!='Yes'){?><input type="checkbox" name="concil[<?php echo $i; ?>]" value="<?php echo $restrans[0]; ?>" class="concil"><?php } else {?> <input type="checkbox" name="concil[]" value="<?php echo $restrans[0]; ?>" checked="checked" class="concil"><?php }?></td></tr>
    
	
<?php $i++; }
$outstand=$income-$payment+$cu_income-$cu_pay;
if($cu_income>0 || $cu_pay>0)
{
?>
<tr><td colspan="3" align="right"> Total</td><td align="right"><?php echo $cu_pay;?></td><td align="right"><?php echo $cu_income;?></td><td></td></tr>
<?php }?>
<tr><td colspan="3" align="right">Balance : </td><td colspan="2" align="right"><?php echo $outstand;?></td></tr>
<tr><td colspan="7" align="center"> <input type="submit" name="submit" value="Reconcile "/><input type="reset" name="reset" value="Reset All"></td></tr>
</table>
<input type="hidden" name="count" value="<?php echo $i;?>" />
