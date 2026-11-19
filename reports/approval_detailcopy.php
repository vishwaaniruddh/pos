<?php
ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();




$qty1=0;
$cid=$_POST['cid'];
$by=$_POST['by'];
$design=$_POST['design'];
$cname=$_POST['cname'];
$acc=$_POST['acc'];

$amt=$_POST['amt'];


	$resultname = mysqli_query($con,"SELECT * FROM  `phppos_people` where person_id='$cid'");
	$rowname = mysqli_fetch_row($resultname);
	
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
$res2 = mysqli_query($con,$sq);
$det = mysqli_fetch_row($res2);

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
mysqli_query($con,"BEGIN;");
	if($myflag){
	    
	    
	    
	    $cardperc="0";
	    if(isset($_POST['pay_By']))
	    {
	        
	        
	        if($_POST['pay_By']=="Card")
	        {
	            
	           $cardperc="2"; 
	        }
	    }
	    
	$t1=mysqli_query($con,"insert into `approval`(cust_id,bill_date,status,paid_amount,discount_status,discount_wise,discount_per,bill_by,pay_by,note,typ,statetyp,card_perc,card_amt) values('$cid',STR_TO_DATE('".$odate."','%d/%m/%Y'),'$bs','$amt','$dis_st','$wise','$disper','$by','$pay_by','$note',1,'".$_POST['statetyp']."','".$cardperc."','".$_POST['cardpercamt']."')");
 	
	$result1 = mysqli_query($con,"SELECT max(bill_id) FROM  `approval` where cust_id='$cid'");  
	$rowordno = mysqli_fetch_row($result1);
	
		if($amt=="" || $amt=="0"){$t2=true;}
		else{	
		$t2=mysqli_query($con,"insert into `paid_amount`(bill_id,amount,return_date,payment_by) values('$cid','$amt',STR_TO_DATE('".$odate."','%d/%m/%Y'),'$pay_by')");
		
		if($t2){
		$t3=mysqli_query($con,"INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','".$acc."','receit','".$amt."',STR_TO_DATE('".$odate."','%d/%m/%Y'),'payment from customer $rowname[0] $rowname[1]','NO',now())");	
			}
		
		}
		
 
 $result = mysqli_query($con,"SELECT * FROM  `phppos_people` where person_id='$cid'");
	$row = mysqli_fetch_row($result);
	$sum2=0;
	$total22=0;
 for($j=0;$j<$d;$j++)
{

$sq22="SELECT * FROM phppos_items WHERE name='$design[$j]'";
$res22 = mysqli_query($con,$sq22);
$num22=mysqli_num_rows($res22);
$row12=mysqli_fetch_row($res22);

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
        function PrintDiv(dvid) { 
           // alert(dvid);
           var divToPrint = document.getElementById(dvid);
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
    <?php if($_POST['statetyp']=="1"){  ?><th width="112" ><font size="2" >GST %</font></th><?php } ?>
    <?php if($_POST['statetyp']=="1"){  ?><th width="112" ><font size="2" >SGST AMT</font></th><?php }  ?>
    <th width="112"  style="display:none;"><font size="2" >CGST %</font></th>
    <?php if($_POST['statetyp']=="1"){ ?><th width="112"  ><font size="2" >CGST AMT</font></th><?php }  ?>
    <?php if($_POST['statetyp']=="2"){ ?><th width="112" ><font size="2" >IGST %</font></th><?php } ?>
    <?php if($_POST['statetyp']=="2"){ ?><th width="112" ><font size="2" >IGST AMT</font></th> <?php } ?>
    
    
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
$res2 = mysqli_query($con,$sq);
$num2=mysqli_num_rows($res2);
$row1=mysqli_fetch_row($res2);
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

$t3=mysqli_query($con,"update `phppos_items` set quantity=quantity-$a WHERE name='".$design[$i]."'");
 
if($dis_st=="no"){
    $ds=0; 
    }else{    if($wise=="Item Wise"){

$ds=$dis1[$i];
 }else{ if($disper=="Rs"){
 
  } else{ $ds=$dis; } } }
 
 

///echo $ds."/ ".$design[$i]." / ".$prz[$i]." / ".$p."<br/>";


$t4=mysqli_query($con,"insert into approval_detail(bill_id,item_id,qty,discount,dis_amount,amount,item_per,`gstpec`, `sgstamt`, `cgstamt`, `igstamt`, `typ`,sgstperc,cgstperc,igstperc,price,taxable_price,final_amount) values('$rowordno[0]','$design[$i]','$prz[$i]','$ds','$p','".$sum."','$itemper','".$_POST['gstrate'][$i]."','".$_POST['sgstamt'][$i]."','".$_POST['cgstamt'][$i]."','".$_POST['igstamt'][$i]."',1,'".$_POST['sgstrate'][$i]."','".$_POST['cgstrate'][$i]."','".$_POST['igstrate'][$i]."','".$_POST['prz'][$i]."','".$_POST['przf'][$i]."','".$_POST['amtwithgsttot'][$i]."')");


    $s1=$row1[6]*$prz[$i];
 // echo $p." / ". $sum."<br/>";
  ?>
  <tr>
  <td><font size="2" ><?php echo $j++; ?></font></td>
    <td align="center"><font size="2" ><?php echo $row1[0]; ?></font></td>
    <!--<td align="center"><?php //echo $row1[1]; ?></td>-->
    <td align="center"><font size="2" ><?php echo $row1[6]; ?></font></td>
    <td align="center"><font size="2" ><?php echo $prz[$i]; $qty1+= $prz[$i]; ?></font></td>
      <td align="center"><font size="2" ><?php echo $s1-$_POST['cgstamt'][$i]-$_POST['sgstamt'][$i]-$_POST['igstamt'][$i]; $s2+=$s1; ?></font></td>
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
   
    <?php if($_POST['statetyp']=="1"){ ?><td align="center"><font size="2" ><?php echo $_POST['gstrate'][$i]; ?></font></td><?php } ?>
    <?php if($_POST['statetyp']=="1"){ ?><td align="center" ><font size="2" ><?php echo $_POST['sgstamt'][$i]; ?></font></td><?php } ?>
    <td align="center" style="display:none;" ><font size="2" ><?php echo $_POST['cgstrate'][$i]; ?></font></td>
    <?php if($_POST['statetyp']=="1"){ ?> <td align="center" ><font size="2" ><?php echo $_POST['cgstamt'][$i]; ?></font></td><?php } ?>
    <?php if($_POST['statetyp']=="2"){ ?><td align="center" ><font size="2" ><?php echo $_POST['igstrate'][$i]; ?></font></td><?php } ?>
    <?php if($_POST['statetyp']=="2"){ ?><td align="center" ><font size="2" ><?php echo $_POST['igstamt'][$i]; ?></font></td><?php } ?>
    
   
   
     <td align="center"><font size="2" ><?php echo $_POST['amtwithgsttot'][$i]; ?></font></td>
  </tr>
  <?php $total+=$_POST['amtwithgsttot'][$i];  } ?>
  
  
<?php
/*
$getgstdets=mysqli_query($con,"select distinct(gstpec) from approval_detail where bill_id='".$rowordno[0]."' and status=1 and gstpec>0"); 

while($gstrws=mysqli_fetch_array($getgstdets))
{
    
    $getgstdets2=mysqli_query($con,"select sum(sgstamt) from approval_detail where bill_id='".$rowordno[0]."' and status=1 and gstpec='".$gstrws[0]."'");
    $gstrws2=mysqli_fetch_array($getgstdets2);
    
    $getgstdets3=mysqli_query($con,"select sum(igstamt) from approval_detail where bill_id='".$rowordno[0]."' and status=1 and gstpec='".$gstrws[0]."'");
    $gstrws3=mysqli_fetch_array($getgstdets3);
    $gsttyp="";
    if($gstrws2[0]!=NULL && $gstrws2[0]>0)
    {
       $gsttyp="GST"; 
        
    }else
    {
       $gsttyp="IGST"; 
        
    }
?>
  <tr>
      
  <td colspan="8" align="right"><font size="2" ><?php echo $gsttyp." ".$gstrws[0]."% : ".($gstrws2[0]+$gstrws2[0]);  ?> <b></b></font></td>
  
  </tr>
 
 <?php }
 
 */
 
 
 ?>
  <tr>
  <td colspan="4" align="right"><font size="2" ><b>Total Qty : <?php echo $qty1; ?></b></font></td>
    <td colspan="3" align="right"><font size="2" ><b>Gross Total: <?php echo "Rs.". $s2; ?> </b></font></td>
    
    
    <?php $colspn=0;
    
    if($_POST['statetyp']=="1"){
    
        $colspn="3";
        
    }else
    {
        
        $colspn="2";
    }
    
    ?>
  
  
   <td colspan="<?php echo $colspn;?>" align="right"><font size="2" ><b><?php 
   
   if($_POST['cardpercamt']>0)
  {
      
      
       $colspn1=0;
    
    if($statetype=="1"){
    
        $colspn1="6";
        
    }else
    {
        
        $colspn1="5";
    }
    

      
  echo "Total"; }else{ echo "Net Payable"; }  ?></b></font></td>
  
  

  
  <td colspan="" align="right"><font size="2" ><b> <?php echo "Rs.". $total; ?> </b></font></td>
  </tr>
  
  
  <?php if($_POST['cardpercamt']>0)
  {
      
      $total=$total+$_POST['cardpercamt'];
      ?>
  
    <tr>
  <td colspan="4" align="right"><font size="2" ></font></td>
    <td colspan="<?php echo $colspn1;?>" align="right"><font size="2" ><b>Card 2%</b></font></td>
  <td colspan="" align="right"><font size="2" ><b><?php echo $_POST['cardpercamt'] ; ?></b></font></td></tr>
  
  
  <tr>
  <td colspan="4" align="right"><font size="2" ></font></td>
    <td colspan="<?php echo $colspn1;?>" align="right"><font size="2" ><b>Net Payable: </b></font></td>
  <td colspan="4" align="right"><font size="2" ><b><?php echo "Rs.". $total; ?> </b></font></td></tr>
  
  
  <?php } ?>
   <tr>
       
       
       
     <td colspan="2" align="left"><font size="2" ><b>Date: <?php echo $odate; ?></b></font> <td colspan="2" align="left"><font size="2" ><b>Amount Paid : <?php echo "Rs.". $amt; ?> </b></font></td>
     <?php $aa=$total-$amt; 
    // echo $aa;
    ?>
     <td colspan="7" align="center"><font size="2" ><b>Balance : <?php echo "Rs.".$aa; ?> </b></font></td></tr>
     <tr><td colspan="11"> <font size="2" ><b>Payment By : <?php echo $pay_by; ?></b></font></td></tr>
	 <tr><td colspan="11"><font size="2" ><b>Note : <?php echo $note; ?></b></font></td></tr>
</table></font>

    
    </td>
    </tr>
     <tr><td>
  <hr/>
  <table width="784" border="0">
      <tr>
    <td width="419" valign="top"><ul>
      <li ><font size="2">GST NO</font> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><font size="2">27ADRPP988P1ZW</font></b></li>
     </ul></td>

   
  </tr>
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


<div id="bill2" style="font-size:12px;">
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
    <td height="21" colspan="2"><font size="3" >M/s.&nbsp;:&nbsp;&nbsp;<b><?PHP echo $row[0] . " ".$row[1]; ?></b></font></td><td width="128"><font size="2" >Date: </font><b><?php echo $odate; ?></b></td>
    </tr>
    
  <tr>
    <td colspan="2" height="23"><font size="3" >Address</font><font size="2" >.: &nbsp;&nbsp;&nbsp;
      <b><?php echo $row[4]; ?><br/><?php echo $row[6]; ?><?php echo $row[8]; ?>
      <?php echo $row[9]; ?></b></font></td>
    <td><font size="3" >Bill No:</font><b><?php echo $rowordno[0]; ?></b></td></tr>
    <tr>
    <td><font size="3" >Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $row[2]; ?></b></font></td><td width="290"></td></tr>
  </table>
  
  <table width="780" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
  <th width="49"><font size="2" >Sr. No.</font></th>
    <th width="135"><font size="2" >ITEM CODE</font></th>
    <th width="112"><font size="2" >PRICE</font></th>
    <th width="75"><font size="2" >QTY</font></th>
    <th width="112"><font size="2" >DISCOUNT</font></th>
	<th width="112"><font size="2" >Discount per Item</font></th>
    <?php if($_POST['statetyp']=="1"){  ?><th width="112" ><font size="2" >GST %</font></th><?php } ?>
    <?php if($_POST['statetyp']=="2"){ ?><th width="112" ><font size="2" >IGST %</font></th><?php } ?>
    
    
    <th width="123"><font size="2" >Total Amount</font></th>
    
  </tr>
  <?php
  $j=1;
  $total=0;
  $s2=0;
 
 $getdetsqrsmnq=mysqli_query($con,"Select * from approval where bill_id='".$rowordno[0]."'");
 $frtmn=mysqli_fetch_array($getdetsqrsmnq);
 
  $getdetsqrs=mysqli_query($con,"Select * from approval_detail where bill_id='".$rowordno[0]."'");
  $nor=1;
  $qty2=0;
  while($frt=mysqli_fetch_array($getdetsqrs))
{

$totmy=$frt["dis_amount"]+$frt["amount"];
$perprice=$totmy/$frt["qty"];

$disperf=$frt["item_per"];
$disdsp=0;
if($frt["item_per"]=="Rs")
{
    
   $disdsp= "Rs ".$frt["discount"];
}


if($frt["item_per"]=="%")
{
    
   $disdsp=$frt["discount"]."%";
}
  ?>
  <tr>
  <td><font size="2" ><?php echo $nor++; ?></font></td>
    <td align="center"><font size="2" ><?php echo $frt["item_id"]; ?></font></td>
    <td align="center"><font size="2" ><?php echo $frt["price"]; ?></font></td>
    <td align="center"><font size="2" ><?php echo $frt["qty"]; $qty2=$qty2+$frt["qty"]; ?></font></td>
      <td align="center"><font size="2" ><?php echo $disdsp; ?></font></td>
   
     <!--========== edit by anand==============-->
     <!--<td align="center"><?php echo $frt["dis_amount"]; ?></td>-->
      <td align="center"><font size="2" ><?php echo $dd=round($frt["dis_amount"]/$frt["qty"]); ?></font></td>
      
     <?php echo  $as=$frt["price"]*$frt["qty"];
     $a=$as-$frt["dis_amount"];
     ?>
     <!--======================================-->
      
    <?php if($frt['typ']=="1"){ ?><td align="center"><font size="2" ><?php echo $frt['gstpec']; ?></font></td><?php } ?>
        <?php if($frt['typ']=="2"){ ?><td align="center" ><font size="2" ><?php echo $frt['igstperc']; ?></font></td><?php } ?>
    
   
    <!-- <td align="center"><font size="2" ><?php echo $frt['taxable_price']; ?></font></td>-->
  <td align="center"><font size="2" ><?php echo $a; ?></font></td>
  
  </tr>
  <?php $total2=$total2+$frt['taxable_price'];  } ?>
  
  <tr>
  <td colspan="4" align="right"><font size="2" ><b>Total Qty : <?php echo $qty2; ?></b></font></td>
    <td colspan="3" align="right"><font size="2" ><b>Total:</b></font></td>
    
 <!-- <td colspan="4" align="right"><font size="2" ><b><?php echo  $total2; ?> </b></font></td>-->
 <td colspan="4" align="right"><font size="2" ><b><?php echo  $a; ?> </b></font></td>
  </tr>



   <?php
$b="0";  
  if($_POST['statetyp']=="1")
        {
         
       
  $getgstdets=mysqli_query($con,"select distinct(gstpec) from approval_detail where bill_id='".$rowordno[0]."' and typ=1 and gstpec>0 order by gstpec asc"); 

while($gstrwsd=mysqli_fetch_array($getgstdets))
{
        //echo $gstr[$b];
         
        $gtsm=mysqli_query($con,"select sum(sgstamt),sum(cgstamt) from approval_detail where gstpec='".$gstrwsd[0]."' and bill_id='".$rowordno[0]."'");
        $getsmfr=mysqli_fetch_array($gtsm);
        if($getsmfr[0]!=NULL)
        {
        ?>
        <tr>
        <td colspan="7" align="right"><font size="2" ><b>SGST <?php echo ($gstrwsd[0]/2)."%"; ?></b></font></td>
        <td  align="right"><font size="2" ><b><?php echo $getsmfr[0]; ?></b></font></td>
        </tr>
        <tr>
        <td colspan="7" align="right"><font size="2" ><b>CGST <?php echo ($gstrwsd[0]/2)."%"; ?></b></font></td>
        <td  align="right"><font size="2" ><b><?php echo $getsmfr[1];
        $total2=$total2+$getsmfr[0]+$getsmfr[1];
        $b=$a+$getsmfr[0]+$getsmfr[1];
        ?></b></font></td>
        </tr>
 <?php
        
        }
    }
    
    }
    
   
     if($_POST['statetyp']=="2")
        {
         
        
  $getgstdets=mysqli_query($con,"select distinct(igstperc) from approval_detail where bill_id='".$rowordno[0]."' and typ=1 and igstperc>0 order by igstperc asc"); 

while($gstrwsd=mysqli_fetch_array($getgstdets))
{
        //echo $gstr[$b];
         
        $gtsm=mysqli_query($con,"select sum(igstamt) from approval_detail where gstpec='".$gstrwsd[0]."' and bill_id='".$rowordno[0]."'");
        $getsmfr=mysqli_fetch_array($gtsm);
        if($getsmfr[0]!=NULL)
        {
        ?>
        <tr>
        <td colspan="7" align="right"><font size="2" ><b>IGST <?php echo $gstrwsd[0]."%"; ?></b></font></td>
        <td align="right"><font size="2" ><b><?php echo $getsmfr[0]; $total2=$total2+$getsmfr[0];?></b></font></td>
        </tr>
 <?php
        
        }
    }
    
    }
    
    ?>
    
    
 <tr>
  <td colspan="4" align="right"></td>
  
  
   <td colspan="3" align="right"><font size="2" ><b><?php 
   
   if($frtmn['card_amt']>0)
  {
  echo "Total Amount "; }else{ echo "Net Payable"; }  ?></b></font></td>
  
  
  
  <!--<td colspan="4" align="right"><font size="2" ><b> <?php echo  $total2; ?> </b></font></td>-->
  <td colspan="4" align="right"><font size="2" ><b> <?php echo  $b; ?> </b></font></td>
  
  </tr>
  
  <?php if($frtmn['card_amt']>0)
  {
      
      $total2=$total2+$frtmn['card_amt'];
      ?>
      
      <tr>
  <td colspan="4" align="right"><font size="2" ></font></td>
    <td colspan="3" align="right"><font size="2" ><b>Card 2%</b></font></td>
  <td colspan="4" align="right"><font size="2" ><b><?php echo $frtmn['card_amt'] ; ?></b></font></td></tr>
  
  
  <tr>
  <td colspan="4" align="right"><font size="2" ></font></td>
    <td colspan="3" align="right"><font size="2" ><b>Net Payable:</b></font></td>
  <td colspan="4" align="right"><font size="2" ><b> <?php echo "Rs.". $total2; ?> </b></font></td></tr>
  
  
  <?php } ?>
  
  

   <tr>
     <td colspan="2" align="left"><font size="2" ><b>Date: <?php echo $odate; ?></b></font> <td colspan="2" align="left"><font size="2" ><b>Amount Paid : <?php echo "Rs.". $frtmn["paid_amount"]; ?> </b></font></td>
    
     <td colspan="7" align="center"><font size="2" >
         
          <?php 
     
    // echo $total2."--".$frtmn["paid_amount"];
     $bl=$total2-$frtmn["paid_amount"];
     
     $bb=$b-$frtmn["paid_amount"];
     
    ?>
         <!--<b>Balance : <?php echo "Rs.".$bl; ?> </b>--> <b>Balance : <?php echo "Rs.".$bb; ?> </b>   </font></td></tr>
     <tr><td colspan="11"> <font size="2" ><b>Payment By : <?php echo $pay_by; ?></b></font></td></tr>
	 <tr><td colspan="11"><font size="2" ><b>Note : <?php echo $note; ?></b></font></td></tr>
</table></font>

    
    </td>
    </tr>
     <tr><td>
  <hr/>
  <table width="784" border="0">
      
        <tr>
    <td width="419" valign="top"><ul>
      <li ><font size="2">GST NO</font> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><font size="2">27ADRPP988P1ZW</font></b></li>
     </ul></td>

   
  </tr>
      
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

</div><br/><br/><div id="pageNavPosition2"></div>
<center><a href="#" onclick='PrintDiv("bill");'>Print bill 1</a>
<a href="#" onclick='PrintDiv("bill2");'>Print bill 2</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="http://sarmicrosystems.in/srishringarr/application/views/reports/approvalnew.php">Back</a></center>



</body>
</html><?php  //echo $t1."-".$t2."-".$t3."-".$t4; 
}
if($d>0){
if($t1 && $t2 && $t3 && $t4){
	mysqli_query($con,"COMMIT;");
	}
	else{
	      
		mysqli_query($con,"ROLLBACK;");
		echo "<script> rollback(); </script>";
		} 
	}
	else
	mysqli_query($con,"COMMIT;");
	
	CloseCon($con);

	?>