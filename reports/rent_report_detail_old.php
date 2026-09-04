<?php
//ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$id=$_GET['id'];

// $ss_con = mysqli_connect("198.38.84.10", "srishrin_juser", "juser123","srishrin_jewels");
$ss_con = OpenNewSrishringarrCon();

$result1 = mysqli_query($con,"SELECT * FROM  `phppos_rent` where bill_id='$id'");
$rowordno = mysqli_fetch_array($result1);

$is_online = $rowordno['is_online'];

if($is_online!=1){ 
    
    $row = mysqli_fetch_row($result);


$sql2=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$rowordno[1]'");
$row2=mysqli_fetch_row($sql2); 


$result2 = mysqli_query($con,"SELECT * FROM  `phppos_rent` where bill_id='$id'");	


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
           divToPrint.style.fontSize = "12px";
           var popupWin = window.open('', '_blank', 'width=800,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
           popupWin.document.close();
                }
</script>

<body>

<div id="bill" style="font-size:12px;">

<table width="825" border="0" align="center">
<tr>
    <td width="819" height="42">
    
    <table width="100%" >
       <tr>
        <td colspan="3" align="center">
          <font size="2">
            <B><U> CONFIRMATION MEMO </U></B></font></td>
         </tr>            
  <tr>
  <td width="382" align="left" valign="top"><b><font size="-1" >MANUFACTURERS AND RETAILERS</font> <font size="-1">OF BRIDAL SETS</font>,<br />
      <font size="-1">HAIR ACCESSORIES AND  BROOCHES,</font><br/>
      <font size="-1">BRIDAL DUPATTAS</font>,<br />
      <font size="-1">CHANIYA CHOLI,<br/>
      &amp; ALL KINDS OF ACCESSORIES.</font></b><br /></td>
    
    <td width="425" height="42" colspan="2" align="left" valign="bottom" style="padding-left:10px;"><img src="bill.PNG" width="408" height="165"/></td>
    </tr>
    
  <tr>
    <td colspan="2" ></td>
    </tr>
   <tr>
    <td height="70" colspan="2" >
    
    <table width="100%"> 
  <tr>
    <td width="269" height="43" >M/s.&nbsp;:&nbsp;&nbsp;<b>
        <?php echo $row2[0] . " ".$row2[1]; ?></b></td>
    
    <td width="216">Through Name: <b><?php echo $row2[0]." ".$row2[1]; ?></b><br/>
     Through Contact No: <b><?php echo $rowordno[18]; ?></b></td>
      <td width="94" rowspan="5">Bill. No. <b><?php echo $rowordno[0]; ?></b><br/>Date: <b><?php if(isset($rowordno[2]) and $rowordno[2]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[2])); ?></b></td>
    
    </tr>
    
  <tr> <td>Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $row2[2]; ?></b></td>
    
    <td width="216">Pick-Up. :&nbsp;<b><?php echo $rowordno[6]; ?></b></td>
    </tr>
    <tr>
     <td height="45">Address.: &nbsp;&nbsp;&nbsp; <?php echo $row2[4]; ?><br/>
        <?php echo $row[6]; ?><?php echo $row[8]; ?> <?php echo $row[9]; ?></td>
      <td>Delivery :<b><?php echo $rowordno[7]; ?></b></td></tr>
       <tr>
      <td>2nd Person Name.: &nbsp;&nbsp;&nbsp; <b><?php echo $rowordno[15]; ?></b></td>
    

      <td>Pick-Up Date :<b>
	  <?php if(isset($rowordno[11]) and $rowordno[11]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[11]));
 ?></b></td>

<!-- <td width="141">Commission : <?php //echo $rowordno[17]; ?><?php //echo $rowordno[18]; ?></td>-->
 </tr>
       <tr>
      <td>2nd Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $rowordno[16]; ?></b></td>
      
                
      <td>Delivery Date :<b><?php if(isset($rowordno[12]) and $rowordno[12]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[12])); ?></b></td>
      
    <!--  <td>Total Commission : <?php //echo $rowordno[19]; ?></td>-->
      </tr>
    </table>
    
    </td>
    </tr>
  </table>
  
  <font size="2" >
  
  <table width="100%" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
  <th width="104"><font size="2">SR.NO.</font></th>
    <th width="104"><font size="2">ITEM CODE</font></th>
    <th width="161"><font size="2">PARTICULARS</font></th>
    <th width="86"><font size="2">PRICE</font></th>
    <th width="86"><font size="2">QTY</font></th>
    <th width="93"><font size="2">RENT</font></th>
       
    <th width="113"><font size="2">DEPOSIT</font></th>
   <!-- <th width="114"><font size="2">C0MMISSION</font></th>-->
    <th width="76"><font size="2">TOTAL RENT</font></th>
  </tr>
  
<?php
  $total=0;
  $total1=0;
  $i=1;
$sql2=mysqli_query($con,"SELECT * FROM  `order_detail` where bill_id='$id'");
while($row2=mysqli_fetch_row($sql2)){ 
//echo "SELECT * FROM phppos_items WHERE name=".$design[$i];

$sq="SELECT * FROM phppos_items WHERE name='$row2[1]'";
$res2 = mysqli_query($con,$sq);
$num2=mysqli_num_rows($res2);
$row1=mysqli_fetch_row($res2);
?>
   <tr>
   <td align="center"><?php echo $i; ?></td>
   <td align="center"><?php echo $row1[0]; ?></td>
   <td align="center"><?php echo $row1[1]; ?></td>
   <td align="center"><?php echo $row1[6] ?></td>
   <td align="center"><?php echo $row2[9] ?></td>
   <td align="center"><?php echo $row2[2] ?></td>
   
   
   
   <td align="center"><?php echo $row2[3]; ?></td>
  <!-- <td align="center"><?php //echo $row2[5]; ?></td>-->
   <td align="center"><?php echo $row2[6]; ?></td>
   </tr>
   
  <?php 
  $total+=$row2[2]+$row2[3];
  $total1+=$row2[6]; 
  $i++; 
   $totalq+=$row2[9]; 
  } 
  ?>
 
 <?php if($rowordno['card_perc']>0)
 {
     ?>
     <tr>
      <td colspan="6" align="right"></td>
      <td colspan="" align="right"><b> Card <?php echo $rowordno['card_perc']?>%</b></td>
 <td colspan="" align="right"> <b><?php echo $rowordno['card_amt'];$total1+=$rowordno['card_amt']; ?></b></td>
 </tr>
<?php
 }
 
 
 ?>
 
 
 <tr> 
 <td colspan="5" align="right"><b> Total Quantity: </b><?php echo $totalq; ?></td>

<?php

$ap= $total1-$rowordno[10];
  
?>
  <td align="right" colspan="9">
   <b> Total Rent :</b>&nbsp;&nbsp;<?php echo $total1; ?> 
   </td></tr>
  
  
</table>
</font>

    
    </td>
    </tr>
     <tr>
	
    <td  align="right"><font size="2" >&nbsp;&nbsp;<?php 
  
    ?></font></td>
  </tr>
  <tr>
	
    <td height="31" align="center"><font size="3" >
    
  
  
   Balance :<b>&nbsp;&nbsp;<?php echo  "Rs.".$ap;   ?>
   <!--Balance :<b>&nbsp;&nbsp;<?php //echo  "Rs.".$rowordno[10];   ?>-->
   
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    Deposit&nbsp; :&nbsp;<b><?php echo $rowordno[13]; ?>&nbsp;&nbsp;<br/>
    Paid Amount <?php echo "Rs.".$rowordno[10];
  
    ?></b></font></td>
  </tr>
   <tr>
	
    <td height="31" align="center"><p><b>Onces an order is booked,it will not be changed and its money will not be returned.
      <br/>
      The full amount of Rent is to be given on the day of booking</b></p></td>
  </tr>
  <tr><td>
  <hr/>
  <table width="752" border="0">
  <tr>
    <td width="381"><ul>
    <li ><font>Subject to Mumbai jurisdiction</font> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>E. & O . E</b>  </li>
    <li><font>Deposit necessary</font></li>
    <li><font>Rent basis available fo 3 days only</font></li>
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
<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="/rentReport.php">Back</a></center>

</body>
</html>

<? }else{ 

//-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//   Sri Shringaar DB Functions Starts  ONLINE PAYMENTS BILL
//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------



function get_shippingaddress($id){
    global $ss_con;
    

$order_sql = mysqli_query($ss_con,"select * from Order_ent where id='".$id."'") ; 
$order_sql_result = mysqli_fetch_assoc($order_sql);

$del_id = $order_sql_result['delivery_add'];

    $sql = mysqli_query($ss_con,"select * from shippingInfo where id='".$del_id."'");
    $sql_result = mysqli_fetch_assoc($sql);
    
    $person_name = $sql_result['person_name'];
    $person_contact = $sql_result['person_contact'];
    $address = $sql_result['address'];
    $landmark = $sql_result['landmark'];
    $city = $sql_result['city'];
    $state = $sql_result['state'];
    $pincode = $sql_result['pincode'];
    $country = $sql_result['country'];
    
    return 
    $address . ', ' . $landmark . ', ' . $city . ', ' . $state . ', ' . $pincode .', ' .$country ;    
    ;
}



function get_order_ent($id,$parameter){
        global $ss_con;
        
        $sql = mysqli_query($ss_con,"select $parameter from Order_ent where id='".$id."'");
        $sql_result = mysqli_fetch_assoc($sql);
        
        return $sql_result[$parameter];

}

function get_order_details_arr($id,$parameter){
        global $ss_con;
        $sql = mysqli_query($ss_con,"select $parameter from order_details where order_id='".$id."'");
        $sql_result = mysqli_fetch_assoc($sql);
        return $sql_result;
}

//-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//   Sri Shringaar DB Functions End 
//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    
$row = mysqli_fetch_row($result);


$sql2=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$rowordno[1]'");
$row2=mysqli_fetch_row($sql2); 


$result2 = mysqli_query($con,"SELECT * FROM  `phppos_rent` where bill_id='$id'");	
$ss_rowordno = mysqli_fetch_assoc($result2);



$ss_orderid = $ss_rowordno['order_id'];

if($ss_orderid > 0 ){

    $ss_sql = mysqli_query($ss_con, "SELECT * FROM `Registration` where registration_id = '$rowordno[1]'");
    $ss_sql_result = mysqli_fetch_assoc($ss_sql);
    $row[1] = $ss_sql_result['Firstname'] .' '. $ss_sql_result['Lastname'];
    $row[2] = $ss_sql_result['Mobile'];
}

$address = get_shippingaddress($ss_orderid);


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
           divToPrint.style.fontSize = "12px";
           var popupWin = window.open('', '_blank', 'width=800,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
           popupWin.document.close();
                }
</script>

<body>

<div id="bill" style="font-size:12px;">

<table width="825" border="0" align="center">
<tr>
    <td width="819" height="42">
    
    <table width="100%" >
       <tr>
        <td colspan="3" align="center">
          <font size="2">
            <B><U> CONFIRMATION MEMO </U></B></font></td>
         </tr>            
  <tr>
  <td width="382" align="left" valign="top"><b><font size="-1" >MANUFACTURERS AND RETAILERS</font> <font size="-1">OF BRIDAL SETS</font>,<br />
      <font size="-1">HAIR ACCESSORIES AND  BROOCHES,</font><br/>
      <font size="-1">BRIDAL DUPATTAS</font>,<br />
      <font size="-1">CHANIYA CHOLI,<br/>
      &amp; ALL KINDS OF ACCESSORIES.</font></b><br /></td>
    
    <td width="425" height="42" colspan="2" align="left" valign="bottom" style="padding-left:10px;"><img src="bill.PNG" width="408" height="165"/></td>
    </tr>
    
  <tr>
    <td colspan="2" ></td>
    </tr>
   <tr>
    <td height="70" colspan="2" >
    
    <table width="100%"> 
  <tr>
    <td width="269" height="43" >M/s.&nbsp;:&nbsp;&nbsp;<b>
        <?php echo $row2[0] . " ".$row2[1]; ?></b></td>
    
    <td width="216">Through Name: <b><?php echo $row2[0]." ".$row2[1]; ?></b><br/>
     Through Contact No: <b><?php echo $rowordno[14]; ?></b></td>
      <td width="94" rowspan="5">Bill. No. <b><?php echo $rowordno[0]; ?></b><br/>Date: <b><?php if(isset($rowordno[2]) and $rowordno[2]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[2])); ?></b></td>
    
    </tr>
    
  <tr> <td>Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $row2[2]; ?></b></td>
    
    <td width="216">Pick-Up. :&nbsp;<b><?php echo $rowordno[6]; ?></b></td>
    </tr>
    <tr>
     <td height="45">Address.: &nbsp;&nbsp;&nbsp; <?php echo $address ;  ?><br/>
        <?php echo $row[6]; ?><?php echo $row[8]; ?> <?php echo $row[9]; ?></td>
      <td>Delivery :<b><?php echo $rowordno[7]; ?></b></td></tr>
       <tr>
      <td>2nd Person Name.: &nbsp;&nbsp;&nbsp; <b><?php echo $rowordno[15]; ?></b></td>
    
              <? if($is_online!=1){ ?>
      <td>Pick-Up Date :<b>
	  <?php if(isset($rowordno[11]) and $rowordno[11]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[11]));
 ?></b></td>
 <? } ?>
<!-- <td width="141">Commission : <?php //echo $rowordno[17]; ?><?php //echo $rowordno[18]; ?></td>-->
 </tr>
       <tr>
      <td>2nd Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $rowordno[16]; ?></b></td>
      
                <? if($is_online!=1){ ?>
      <td>Delivery Date :<b><?php if(isset($rowordno[12]) and $rowordno[12]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[12])); ?></b></td>
      
      <? } ?>
    <!--  <td>Total Commission : <?php //echo $rowordno[19]; ?></td>-->
      </tr>
    </table>
    
    </td>
    </tr>
  </table>
  
  <font size="2" >
  
  <table width="100%" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
  <th width="104"><font size="2">SR.NO.</font></th>
    <th width="104"><font size="2">ITEM CODE</font></th>
    <th width="161"><font size="2">PARTICULARS</font></th>
    <th width="86"><font size="2">PRICE</font></th>
    <th width="86"><font size="2">QTY</font></th>
    <th width="93"><font size="2">RENT</font></th>
       <? if($is_online==1){ ?>
       
    <th width="93"><font size="2">PICKUP DATE</font></th>
    <th width="93"><font size="2">DELIVERY DATE</font></th>
    <? } ?>
    <th width="113"><font size="2">DEPOSIT</font></th>
   <!-- <th width="114"><font size="2">C0MMISSION</font></th>-->
    <th width="76"><font size="2">TOTAL RENT</font></th>
  </tr>
  
<?php
  $total=0;
  $total1=0;
  $i=1;
$sql2=mysqli_query($con,"SELECT * FROM  `order_detail` where bill_id='$id'");
while($row2=mysqli_fetch_row($sql2)){ 
//echo "SELECT * FROM phppos_items WHERE name=".$design[$i];

$sq="SELECT * FROM phppos_items WHERE name='$row2[1]'";
$res2 = mysqli_query($con,$sq);
$num2=mysqli_num_rows($res2);
$row1=mysqli_fetch_row($res2);
?>
   <tr>
   <td align="center"><?php echo $i; ?></td>
   <td align="center"><?php echo $row1[0]; ?></td>
   <td align="center"><?php echo $row1[1]; ?></td>
   <td align="center"><?php echo $row1[6] ?></td>
   <td align="center"><?php echo $row2[9] ?></td>
   <td align="center"><?php echo $row2[2] ?></td>
   
   
   
   <? if($is_online==1){ ?>
       <td align="center"><?php echo $row2[11] ?></td>
       <td align="center"><?php echo $row2[12] ?></td>
   
   <? } ?>
 
   
   
   
   <td align="center"><?php echo $row2[3]; ?></td>
  <!-- <td align="center"><?php //echo $row2[5]; ?></td>-->
   <td align="center"><?php echo $row2[6]; ?></td>
   </tr>
   
  <?php 
  $total+=$row2[2]+$row2[3];
  $total1+=$row2[6]; 
  $i++; 
   $totalq+=$row2[9]; 
  } 
  ?>
 
 <?php if($rowordno['card_perc']>0)
 {
     ?>
     <tr>
      <td colspan="6" align="right"></td>
      <td colspan="" align="right"><b> Card <?php echo $rowordno['card_perc']?>%</b></td>
 <td colspan="" align="right"> <b><?php echo $rowordno['card_amt'];$total1+=$rowordno['card_amt']; ?></b></td>
 </tr>
<?php
 }
 
 
 ?>
 
 
 <tr> 
 <td colspan="5" align="right"><b> Total Quantity: </b><?php echo $totalq; ?></td>

<?php

$ap= $total1-$rowordno[10];
  
?>
  <td align="right" colspan="9">
   <b> Total Rent :</b>&nbsp;&nbsp;<?php echo $total1; ?> 
   </td></tr>
  
  
</table>
</font>

    
    </td>
    </tr>
     <tr>
	
    <td  align="right"><font size="2" >&nbsp;&nbsp;<?php 
  
    ?></font></td>
  </tr>
  <tr>
	
    <td height="31" align="center"><font size="3" >
    
  
  
   Balance :<b>&nbsp;&nbsp;<?php echo  "Rs.".$ap;   ?>
   
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    Deposit&nbsp; :&nbsp;<b><?php echo $rowordno[13]; ?>&nbsp;&nbsp;<br/>Paid Amount <?php echo "Rs.".$rowordno[10];
  
    ?></b></font></td>
  </tr>
   <tr>
	
    <td height="31" align="center"><p><b>Onces an order is booked,it will not be changed and its money will not be returned.
      <br/>
      The full amount of Rent is to be given on the day of booking</b></p></td>
  </tr>
  <tr><td>
  <hr/>
  <table width="752" border="0">
  <tr>
    <td width="381"><ul>
    <li ><font>Subject to Mumbai jurisdiction</font> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>E. & O . E</b>  </li>
    <li><font>Deposit necessary</font></li>
    <li><font>Rent basis available fo 3 days only</font></li>
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
<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="http://sarmicrosystems.in/shringaar/application/views/reports/rentReport.php">Back</a></center>

</body>
</html>    
<?  } 
CloseCon($ss_con);
CloseCon($con); ?>
