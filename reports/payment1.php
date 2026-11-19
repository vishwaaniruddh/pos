<?php
ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();



$result = mysqli_query($con,"SELECT * FROM  `phppos_people` where person_id='$cid'");
	$row = mysqli_fetch_row($result);

$cid=$_POST['cid'];
$amt=$_POST['amt'];
$pay_by=$_POST['pay_By'];
$acc=$_POST['acc'];
$bill_id = $_REQUEST['bill_id'];

$amt_date=$_POST['amt_date'];
 
	/////echo "update approval set paid_amount=paid_amount+$amt where cust_id='$cid'";
///$t1=  mysqli_query($con,"update approval set paid_amount=paid_amount+$amt where cust_id='$cid' LIMIT 1");
$t2=mysqli_query($con,"insert into `rent_amount`(cust_id,amount,return_date,payment_by,bill_id) values('$cid','$amt',STR_TO_DATE('".$amt_date."','%d/%m/%Y'),'$pay_by','".$bill_id."')");

$t3=mysqli_query($con,"INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','".$acc."','receit','".$amt."',STR_TO_DATE('".$amt_date."','%d/%m/%Y'),'payment from customer $row[0] $row[1]','NO',now())");
	
$result = mysqli_query($con,"SELECT * FROM  `phppos_people` where person_id='$cid'");
$row = mysqli_fetch_row($result);

if($bill_id){
    // echo "select * from phppos_rent where bill_id = '".$bill_id."'" ; 
    $getbalanceamount = mysqli_query($con,"select * from phppos_rent where bill_id = '".$bill_id."'");
    if($getbalanceamountResult = mysqli_fetch_assoc($getbalanceamount)){
        $balanceAmount = $getbalanceamountResult['bal_amount'];
        $updatedAmount = $balanceAmount + $amt ; 
        // echo "update phppos_rent set bal_amount= '".$updatedAmount."' where bill_id = '".$bill_id."'" ; 
        mysqli_query($con,"update phppos_rent set bal_amount= '".$updatedAmount."' where bill_id = '".$bill_id."'"); 
    }
}
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SHRINGAAR</title>
</head>
<script type="text/javascript" src="paging.js"></script>
<script type="text/javascript">     
        function PrintDiv() {    
           var divToPrint = document.getElementById('bill');
           divToPrint.style.fontSize = "10px";
           var popupWin = window.open('', '_blank', 'width=800,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
            popupWin.document.close();
                }
		function rollback(){
			document.getElementById("bdy").innerHTML="Transaction is rolled back, keeping the data secure. Please refresh this page to complete the transaction!";
			}
       
     </script>
<body id="bdy">

<div id="bill" style="font-size:12px;">
<table width="787" border="0" align="center">
<tr>
    <td width="781" height="42">
    
    <table width="780" >
       <tr>
        <td colspan="3" align="center" style="padding-left:100px;">
          <font size="2">
            <B><U> <?php echo $by; ?> </U></B></font></td>
         </tr>            
  <tr>
  <td width="346" align="left" valign="top"><b>
    <p><font size="-1" >MANUFACTURERS AND RETAILERS</font>
      <font size="-1">OF BRIDAL SETS</font>,<br />
      <font size="-1">HAIR ACCESSORIES AND  BROOCHES,</font><br/>
      <font size="-1">BRIDAL DUPATTAS</font>,<br />
      <font size="-1">CHANIYA CHOLI,<br/>&amp; ALL KINDS OF ACCESSARIES.</font><br />
    </p></b></td>
    
    <td height="42" colspan="2" align="right" style="padding-left:10px;" valign="bottom"><img src="bill.PNG" width="408" height="165"/></td>
    </tr>
    
  <tr>
    <td colspan="2" ></td>
    </tr>
    
  <tr>
    <td height="21" colspan="2"><font size="3" >M/s.&nbsp;:&nbsp;&nbsp;<b><?PHP echo $row[0] . " ".$row[1]; ?></b></font></td>
    </tr>
    
  <tr>
    <td colspan="2" height="23"><font size="3" >Address</font><font size="2" >.: &nbsp;&nbsp;&nbsp;
      <b><?php echo $row[4]; ?><br/><?php echo $row[6]; ?><?php echo $row[8]; ?>
      <?php echo $row[9]; ?></b></font></td>
   
  </tr>
    <tr>
    <td><font size="3" >Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $row[2]; ?></b></font></td><td width="290"></td></tr>
  </table>
    <font size="2" >
    <table width="780" border="1" cellpadding="4" cellspacing="0" id="results">
   <tr>
     <td colspan="2" align="left"><font size="2" ><b>Payment Date: <?php echo $amt_date; ?></b></font> <td colspan="2" align="left"><font size="2" ><b>Amount Paid : <?php echo "Rs.". $amt; ?> </b></font></td>
     
     <tr><td colspan="8"> <font size="2" ><b>Payment By : <?php echo $pay_by; ?></b></font></td></tr>
	 
</table>
  </font>

    
    </td>
    </tr>
     <tr><td>
  <hr/>
  <table width="784" border="0">
  <tr>
    <td width="419" valign="top"><ul>
      <li ><font size="2">Subject to Mumbai jurisdiction</font> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><font size="2">E. & O . E</font></b></li>
      <li> <font size="2">Time 11 a.m. to 6 p.m.</font></li></ul></td>

    <td width="355" valign="top"align="right">
      <img src="shringaar.png" width="163" height="57"/>
      <br/><br/><br/>
      <font>Auth. Signatory</font>&nbsp;</td>
  </tr>
</table>

  </td></tr>
</table>

</div><br/><br/><div id="pageNavPosition"></div>

<?php CloseCon($con);?>
<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="approval.php">Back</a></center>

</body>
</html>