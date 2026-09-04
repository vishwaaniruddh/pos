<?php
ini_set( "display_errors", 0);
include('config.php');
include('../purchase/items_showing_calculate.php');
$qty1=0;
$cid=$_POST['cid'];
$by=$_POST['by'];
$design=$_POST['design'];
$cname=$_POST['cname'];
$acc=$_POST['acc'];

$amt=$_POST['amt'];


	$resultname = mysql_query("SELECT * FROM  `phppos_people` where person_id='$cid'");
	$rowname = mysql_fetch_row($resultname);
	
if(isset($_POST['dyes'])){
	$dis_st=$_POST['dyes'];
}else{
	$dis_st=$_POST['dno'];
}
if(isset($_POST['itemwise'])){
$wise=$_POST['itemwise'];	
}else{
$wise=$_POST['totalwise'];	
}
$prz=$_POST['qty'];
$d=count($design);
$dis=$_POST['dis'];
$disper=$_POST['disper'];
$pay_by=$_POST['pay_By'];
$note=$_POST['note'];

$odate=$_POST['bill_date'];
if($by=="QUOTATION")
{
$bs='S';
}else{
$bs='A';
}
$myflag=true;$dname="";$dqty=0;$bqty=0;
for($i=0;$i<$d;$i++)
{
$a=$prz[$i].".00";
$sq="SELECT quantity FROM phppos_items WHERE name='$design[$i]'";
$res2 = mysql_query($sq);
$det = mysql_fetch_row($res2);

if($a>$det[0])
{
 $dname=$design[$i];
 $dqty=$a;
 $bqty=$det[0];
 $myflag=false;
 break;
	}
}

if(!$myflag){
            echo "<br><br><br><center>You don't have enough quantity for ".$dname.", required  ".$dqty.", in Stock  (".$bqty;
            echo "). Go back and try again</center>";
            
}
mysql_query("BEGIN;");
	if($myflag){
	$t1=mysql_query("insert into `approval`(cust_id,bill_date,status,paid_amount,discount_status,discount_wise,discount_per,bill_by,pay_by,note) values('$cid',STR_TO_DATE('".$odate."','%d/%m/%Y'),'$bs','$amt','$dis_st','$wise','$disper','$by','$pay_by','$note')");
 	
	$result1 = mysql_query("SELECT max(bill_id) FROM  `approval` where cust_id='$cid'");  
	$rowordno = mysql_fetch_row($result1);
	
		if($amt=="" || $amt=="0"){$t2=true;}
		else{	
		$t2=mysql_query("insert into `paid_amount`(bill_id,amount,return_date,payment_by) values('$cid','$amt',STR_TO_DATE('".$odate."','%d/%m/%Y'),'$pay_by')");
		
		if($t2){
		$t3=mysql_query("INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','".$acc."','receit','".$amt."',STR_TO_DATE('".$odate."','%d/%m/%Y'),'payment from customer $rowname[0] $rowname[1]','NO',now())");	
			}
		
		}
		
 
 $result = mysql_query("SELECT * FROM  `phppos_people` where person_id='$cid'");
	$row = mysql_fetch_row($result);
	$sum2=0;
	$total22=0;
 for($j=0;$j<$d;$j++)
{

$sq22="SELECT * FROM phppos_items WHERE name='$design[$j]'";
$res22 = mysql_query($sq22);
$num22=mysql_num_rows($res22);
$row12=mysql_fetch_row($res22);

$total22=$row12[6]*$prz[$j];

$sum2+=$total22;
}	

///echo $sum2;
$c=$dis/$sum2;
$b=$c*100
//echo $sum."/".$b."<br/>";
	
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
    
    <td height="42" colspan="2" align="right" style="padding-left:10px;" valign="bottom"><img src="bill.png" width="408" height="165"/></td>
    </tr>
    
  <tr>
    <td colspan="2" ></td>
    </tr>
    
  <tr>
    <td height="21" colspan="2"><font size="3" >M/s.&nbsp;:&nbsp;&nbsp;<b><?PHP echo $row[0] . " ".$row[1]; ?></b></font></td><td width="128"><font size="2" >Date: </font><b><?php echo $odate; ?></b></td>
    </tr>
    
  <tr>
    <td colspan="2" height="23"><font size="3" >Address</font><font size="2" >.: &nbsp;&nbsp;&nbsp;
      <b><?php echo $row[4]; ?><br/><?php echo $row[6]; ?><?php echo $row[8]; ?>
      <?php echo $row[9]; ?></b></font></td>
    <td><font size="3" >Bill No:</font><b><?php echo $rowordno[0]; ?></b></td></tr>
    <tr>
    <td><font size="3" >Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $row[2]; ?></b></font></td><td width="290"></td></tr>
  </table><font size="2" >
    <table width="780" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
  <th width="49"><font size="2" >Sr. No.</font></th>
    <th width="135"><font size="2" >ITEM CODE</font></th>
    <!--<th width="130">PARTICULARS</th>-->

    <th width="112"><font size="2" >PRICE</font></th>
    <th width="75"><font size="2" >QTY</font></th>
    <th width="102"><font size="2" >AMOUNT</font></th>
     <th width="112"><font size="2" >DISCOUNT</font></th>
	    <th width="112"><font size="2" >Discount per Item</font></th>
        <th width="123"><font size="2" >Total Amount</font></th>
    
  </tr>
  <?php
  $j=1;
  $total=0;
  $s2=0;
  ///$ds=$dis/$d;
  for($i=0;$i<$d;$i++)
{
$total1==0;

$a=$prz[$i].".00";
$sq="SELECT * FROM phppos_items WHERE name='$design[$i]'";
$res2 = mysql_query($sq);
$num2=mysql_num_rows($res2);
$row1=mysql_fetch_row($res2);
/////discount no
if($dis_st=="no"){
$dis="0";
$wise="";
$disper="";
$dis="";
$itemper="";
$total1=$row1[6]*$prz[$i];
$sum=$total1-$p;
//echo $dis_st."/".$wise."/".$total1;
/////////////////////////discount Yes
}else{

//echo $dis_st."/".$wise;
/////////////////////////discount item wise
if($wise=="Item Wise"){
$dis1=$_POST['dis1'];



$desq=$design[$i];

$itemper=$_POST[$desq];
///echo $desq."/".$itemper."<br/>";

if($itemper=="%"){
//echo $row1[6]."*".$prz[$i]."*".($dis1[$i]/100.0)."<br/>";
$p=round($row1[6]*$prz[$i]*($dis1[$i]/100.0));

$total1=$row1[6]*$prz[$i];
$sum=$total1-$p;
}else{

$p=$dis1[$i];

$total1=$row1[6]*$prz[$i];
$sum=$total1-$p;
	
}
//echo $p."/".$sum."<br/>";
/////////////////////////discount total  wise
}else{

/////////////////////////discount total wise in Rs
if($disper=="Rs"){
$ds=$b;
$p=round($row1[6]*$prz[$i]*($b/100.0));

$total1=$row1[6]*$prz[$i];
$sum=$total1-$p;
$itemper="";
//echo $p."/".$sum."<br/>";
/////////////////////////discount total wise in %
}else{
//echo $row1[6]."*".$prz[$i]."*".($dis/100.0)."<br/>";
$p=round($row1[6]*$prz[$i]*($dis/100.0));
//echo $p."<br/>";
$total1=$row1[6]*$prz[$i];
$sum=$total1-$p;
$itemper="";
}

}




}


//echo "update  phppos_items  set quantity=quantity-$a WHERE name=".$design[$i];

$t3=mysql_query("update `phppos_items` set quantity=quantity-$a WHERE name='".$design[$i]."'");
 
if($dis_st=="no"){
    $ds=0; 
    }else{    if($wise=="Item Wise"){

$ds=$dis1[$i];
 }else{ if($disper=="Rs"){
 
  } else{ $ds=$dis; } } }
 
 

///echo $ds."/ ".$design[$i]." / ".$prz[$i]." / ".$p."<br/>";



$t4=mysql_query("insert into approval_detail(bill_id,item_id,qty,discount,dis_amount,amount,item_per) values('$rowordno[0]','$design[$i]','$prz[$i]','$ds','$p','$sum','$itemper')");


    $s1=$row1[6]*$prz[$i];
 // echo $p." / ". $sum."<br/>";
  ?>
  <tr>
  <td><font size="2" ><?php echo $j++; ?></font></td>
    <td align="center"><font size="2" ><?php echo $row1[0]; ?></font></td>
    <!--<td align="center"><?php //echo $row1[1]; ?></td>-->
    <td align="center"><font size="2" ><?php echo $row1[6] ?></font></td>
    <td align="center"><font size="2" ><?php echo $prz[$i]; $qty1+= $prz[$i]; ?></font></td>
      <td align="center"><font size="2" ><?php echo $s1; $s2+=$s1; ?></font></td>
    <td align="center"><font size="2" ><?php 
   if($dis_st=="no"){
    echo "0%"; }else{
    if($wise=="Item Wise"){
		if($itemper=="%"){
		
		echo $dis1[$i]."%";
		}else {
		echo "Rs.".$dis1[$i];
		}
 }else{ if($disper=="Rs"){
 echo "Rs.".$p; } else{ echo $dis."%";} } }?></font></td>
 <td align="center"><?php echo round($p/$prz[$i]); ?></td>
     <td align="center"><font size="2" ><?php echo $sum; ?></font></td>
  </tr>
  <?php $total+=$sum;  } ?>
  <tr>
  <td colspan="4" align="right"><font size="2" ><b>Total Qty : <?php echo $qty1; ?></b></font></td>
    <td colspan="3" align="right"><font size="2" ><b>Gross Total: <?php echo "Rs.". $s2; ?> </b></font></td>
  <td colspan="2" align="right"><font size="2" ><b>Net Payable: <?php echo "Rs.". $total; ?> </b></font></td></tr>
   <tr>
     <td colspan="2" align="left"><font size="2" ><b>Date: <?php echo $odate; ?></b></font> <td colspan="2" align="left"><font size="2" ><b>Amount Paid : <?php echo "Rs.". $amt; ?> </b></font></td>
     <?php $aa=$total-$amt; 
    // echo $aa;
    ?>
     <td colspan="4" align="center"><font size="2" ><b>Balance : <?php echo "Rs.".$aa; ?> </b></font></td></tr>
     <tr><td colspan="8"> <font size="2" ><b>Payment By : <?php echo $pay_by; ?></b></font></td></tr>
	 <tr><td colspan="8"><font size="2" ><b>Note : <?php echo $note; ?></b></font></td></tr>
</table></font>

    
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
<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="http://sarmicrosystems.in/shringaar/application/views/reports/approval.php">Back</a></center>

</body>
</html><?php  //echo $t1."-".$t2."-".$t3."-".$t4; 
}
if($d>0){
if($t1 && $t2 && $t3 && $t4){
	mysql_query("COMMIT;");
	for($i=0;$i<$d;$i++)
	{
		if(strripos($design[$i],'-')>1)
		update_items_showing(str_replace(strrchr($design[$i], '-'),'',$design[$i]));
	}
	}
	else{
	      
		mysql_query("ROLLBACK;");
		echo "<script> rollback(); </script>";
		} 
	}
	else
	mysql_query("COMMIT;");
	?>