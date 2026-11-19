<script type="text/javascript" src="datepicker/datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="datepicker/date_css.css"  />
<script>
function details()
{
	var mode=document.getElementById('mode').value;
	var amt=document.getElementById('amt').value;
	if(mode==0)
	{
		alert("Please Select payment mode. ");
		document.getElementById('mode').focus();
		return false;
		}
	if(acc==0)
	{
		alert("Please Select an account. ");
		document.getElementById('acc').focus();
		return false;
		}	
		if(amt==0 ||amt=="")
	{
		alert("Please Enter Amount. ");
		document.getElementById('amt').focus();
		return false;
		}
		var sure = confirm("Are You Really Want to make payment ? ");
		if(sure==false)
		return false;

}
</script>
<body onLoad="">

<div style="text-align: center;">
<a href="/pos/home_dashboard.php">Back</a>
<table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
<tr>
<td align="center"> 
<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$result5=mysqli_query($con,"select * from `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($result5,10);
$row7=mysqli_fetch_array($result5);
$bill=$_POST['payment'];
$payamt=$_POST['payamt'];
$supp_id=$_POST['supp_id'];
$bill1=implode(',',$bill);
?>

<img src="../reports/bill.png" width="408" height="165"/><br/><br/> 
<b>***Payment Supplier***</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      
      
     <form id="purchse" action="processpaymentsupp.php" method="post" onSubmit="return details();">  
     <hr>
     <?php $qrysupp=mysqli_query($con,"select *  from `phppos_suppliers` where `person_id`='$supp_id'");
	 $suppname=mysqli_fetch_row($qrysupp);
	 $qryitem=mysqli_query($con,"select bank_name, bank_id from banks");
	  ?>
     	<table width="50%"><tr><td>Supplier Name :</td><td><?php echo $suppname[1]; ?></td></tr>
        <input type="hidden" name="supp" value="<?php echo $suppname[1]; ?>">
        <tr><td>payment Mode</td><td><select id="mode" name='mode'><option value="0">Select Mode</option><option value="cash">CASH</option><option value="cheque">CHEQUE</option></select></td> </tr>
        
        <tr><td>From Account</td><td><select id="acc" name='acc'><option value="0">Select </option><?php  while($row=mysqli_fetch_row($qryitem))
		 {
				echo "<option value='".$row[1]."'>".$row[0]."</option>";
			}?></select></td> </tr>
                 <tr><td>DATE</td><td><input type="text" name="paydate" id="paydate" onClick="displayDatePicker('paydate');"></td></tr>
        
        <tr><td>Amount</td><td><input type="text" name="amt" id="amt" value="<?php echo $payamt; ?>"></td>
        </tr>
        <tr><td colspan="2" align="center"><input type="submit" name="submit" value="PAY"/><a href="view_bills.php"><input type="button" name="back" value="GO BACK"/></a><input type="hidden" name="bill" value="<?php echo $bill1?>"></td></tr></table>
      </form>
      
</center>
</td>
</tr>
</table>
</div>
<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>