<?php
//ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();



$cid=$_POST['cid'];
$design=$_POST['design'];
$items=$_POST['items'];
$amountTotal=$_POST['amountTotal'];
$note=$_POST['note'];

$d=count($design);
$tphone=$_POST['tphone'];
$pname=$_POST['pname'];
$m_date=$_POST['m_date'];

//echo $d;
$odate=$_POST['bill_date'];
$pamount=$_POST['pamount'];
$paid=$_POST['paid'];
mysqli_query($con,"BEGIN;");
$t1= mysqli_query($con,"insert into `scheme`(cust_id,bill_date,amount,status,throught,pstatus,paid_amount,m_date,throught_phone,note) 
 values('$cid',STR_TO_DATE('".$odate."','%d/%m/%Y'),'$amountTotal','A','$name','$paid','$pamount',STR_TO_DATE('".$m_date."','%d/%m/%Y'),'$tphone','$note')");
 
 $result1 = mysqli_query($con,"SELECT max(bill_id) FROM `scheme` where cust_id='$cid'");
	$rowordno = mysqli_fetch_row($result1);
	
 $result = mysqli_query($con,"SELECT * FROM  phppos_people where person_id='$cid'");
	$row = mysqli_fetch_row($result);
	
	
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
<table width="826" border="0" align="center">
<tr>
    <td width="820" height="42">
    
    <table width="100%" >
       <tr>
        <td colspan="3" align="center">
          <font size="2">
            <B><U> CONFIRMATION MEMO </U></B></font></td>
         </tr>            
  <tr>
  <td width="361" align="left" valign="top"><b><font size="-1" >MANUFACTURERS AND RETAILERS</font> <font size="-1">OF BRIDAL SETS</font>,<br />
      <font size="-1">HAIR ACCESSORIES AND  BROOCHES,</font><br/>
      <font size="-1">BRIDAL DUPATTAS</font>,<br />
      <font size="-1">CHANIYA CHOLI,<br/>
      &amp; ALL KINDS OF ACCESSARIES.</font></b><br /></td>
    
    <td width="448" height="42" colspan="2" align="left" valign="bottom" style="padding-left:10px;"><img src="bill.PNG" width="408" height="165"/></td>
    </tr>
    
  <tr>
    <td colspan="2" ></td>
    </tr>
   <tr>
    <td height="70" colspan="2" >
    <table width="811" height="117"> 
  <tr>
    <td width="320" height="43" >M/s.&nbsp;:&nbsp;&nbsp;<b><?PHP echo $row[0] . " ".$row[1]; ?></b></td>
    <td width="278">Through Name: <b><?php echo $name; ?></b><br/>
     Through Contact No: <b><?php echo $tphone; ?></b></td>
      <td width="197" rowspan="3">Bill. No. <b><?php echo $rowordno[0]; ?></b><br/>Date: <b><?php echo $odate; ?></b></td>
    </tr>
    
  <tr> <td height="21">Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $row[2]; ?></b></td>
    
    <td width="278">Maturety Date: <?php echo $m_date; ?></td>
    </tr>
    <tr>
     <td height="39">Address.: &nbsp;&nbsp;&nbsp; <?php echo $row[4]; ?><br/>
        <?php echo $row[6]; ?><?php echo $row[8]; ?> <?php echo $row[9]; ?></td>
      <td>&nbsp;</td></tr>
       <tr>
     
       </tr>
       
    </table>
    </td>
    </tr>
  </table><font size="2" >
    <table width="100%" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
    <th width="111"><font size="2">ITEM CODE</font></th>
    <th width="175">PARTICULARS</th>
    <th width="100"><font size="2">PRICE</font></th>
    <th width="176"><font size="2">SCHEME AMOUNT </font></th>
    <th width="90"><font size="2">DISCOUNT</font></th>
    <th width="105"><font size="2">TOTAL</font></th>
    
  </tr>
  <?php
  $total=0;
  $total1=0;
  
  for($i=0;$i<$d;$i++)
{
//echo "SELECT * FROM phppos_items WHERE name=".$design[$i];

$sq="SELECT * FROM phppos_items WHERE name='$design[$i]'";
$res2 = mysqli_query($con,$sq);
$num2=mysqli_num_rows($res2);
$row1=mysqli_fetch_row($res2);

///dicount
$dis1=$_POST['dis1'];
$amount=$_POST['amount'];
$desq=$design[$i];

$itemper=$_POST[$desq];
///echo $desq."/".$itemper."<br/>";

if($itemper=="%"){
//echo $row1[6]."*".$prz[$i]."*".($dis1[$i]/100.0)."<br/>";
$p=round($amount[$i]*($dis1[$i]/100.0));

$total1=$amount[$i];
$sum=$total1-$p;
}else{

$p=$dis1[$i];

$total1=$amount[$i];
$sum=$total1-$p;
}
//echo $rowordno[0]."/ ".$design[$i]." / ".$prz[$i]." / ".$dep[$i]."<br/>";
$t2= mysqli_query($con,"insert into scheme_detail(bill_id,item_id,scheme,discount,discount_type,total_amount,dis_per) values('$rowordno[0]','$design[$i]','$amount[$i]','$p','$itemper','$sum','$dis1[$i]')");

  ?>
  <tr>
    <td align="center"><?php echo $row1[0]; ?></td>
    <td align="center"><?php echo $row1[1]; ?></td>
     <td align="center"><?php echo $row1[6] ?></td>
    <td align="center"><?php echo $amount[$i]; ?></td>
    <td align="center"><?php if($itemper=="%"){
		
		echo $dis1[$i]."%";
		}else {
		echo "Rs.".$dis1[$i];
		} ?></td>
        <td align="center"><?php echo $sum; ?></td>
    
  </tr>
  <?php $total+=$sum;
  
   } 
  
$ap=$total-$pamount;
 ?>
</table></font>

    
    </td>
    </tr>
     <tr>
	
    <td  align="right"><font size="2" >Total :&nbsp;&nbsp;<?php 
    
    echo $total;
    ?></font></td>
  </tr>
  <tr>
	
    <td height="31" align="center"><font size="3" >
    <b>Rent &nbsp; :&nbsp;</b><b><?php echo $paid; ?>&nbsp;&nbsp;<?php echo  "Rs.".$ap; ?></b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><br/>
    Paid Amount <?php echo "Rs.".$pamount;
  
    ?></b></font></td>
  </tr>
  <tr><td><b>Note: <?php echo $note; ?></b></td>
  </tr>
   <tr>
	
    <td height="31" align="center"><p><b>Once an order is booked, it will not be changed and its money will not be returned.
      <br/>
      The full amount of Rent is to be given on the day of booking.</b></p></td>
  </tr>
  <tr><td>
  <hr/>
  <table width="752" border="0">
  <tr>
    <td width="381"><ul>
    <li ><font>Subject to Mumbai jurisdiction</font> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>E. & O . E</b>  </li>
    <li><font>Deposit necessary</font></li>
    <li><font>Rent basis available for 3 days only</font></li>
    <li><font>Any damage done will be deducted from the deposit</font></li>
   <li> <font>Time 11 a.m. to 6 p.m.</font></li></ul></td>
    <td width="138">
    <br/>
    <br/><br/>
    <br/><hr />
    <font>Receiver's Signature</font>&nbsp;</td>
    <td width="219" valign="top"align="right">
    <img src="shringaar.png" width="163" height="57"/>
    <br/><br/><br/>
    <font>Auth. Signatory</font>&nbsp;</td>
  </tr>
</table>

  </td></tr>
</table>

</div><br/><br/><div id="pageNavPosition"></div>
<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="rent.php">Back</a></center>

</body>
</html><?php if($t1 && $t2){
	mysqli_query($con,"COMMIT;");
	}
	else{
		mysqli_query($con,"ROLLBACK;");
		echo "<script> rollback(); </script>";
		}
	 ?>
	 <?php CloseCon($con);?>