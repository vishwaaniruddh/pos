<?php
ini_set( "display_errors", 0);
// include('config.php');

include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$alph=$_POST['alph'];



 $total2=0;     
if(isset($_POST['submit']))
{
$qry="SELECT * FROM `phppos_people` WHERE `person_id` not in (SELECT `person_id` FROM `phppos_suppliers` )  ";

if($alph!="")
{
$qry.=" and first_name like '".$alph."%'";
}

$qry.=" ORDER BY `phppos_people`.`first_name` ASC";

//echo $qry;
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);

}				 				 
?>
<script type="text/javascript">     
        function PrintDiv() {    
           var divToPrint = document.getElementById('bill');
           divToPrint.style.fontSize = "10px";
           var popupWin = window.open('', '_blank', 'width=800,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
            popupWin.document.close();
                }
</script>
<body>
<form method="post">
  <font size="+1"><center>
<a href="/pos/home_dashboard.php" style="font-size:18px;font-weight:bold;">Back</a>&nbsp;&nbsp;&nbsp;<a href="#" style="font-size:18px;font-weight:bold;" onclick='PrintDiv();'>Print</a></center></font>
<div style="text-align: center;" id="bill">

<table align="center"><tr><td width="853" align="center">
<img src="bill.PNG" width="408" height="165"/><br><br>
Customer Balance Amount Report(Approval & Sales)<br/>
<br/>
Search Name Starting with:<select name="alph" id="alph">
<?php 
foreach (range('A', 'Z') as $char) {
    echo $char . "\n";
?>
<option value="<?php echo $char;?>"><?php echo $char;?></option>
<?php
}
?>
</select>
<input type="submit" name="submit" value="Search">
</td></tr>
<tr><td>
<table  border="1" cellpadding="4" cellspacing="0" width="881" align="left" id="bill">
 <tr>
 <th width='43' height="34"><U>Sr.No.</U></th>
    
    <th width='208'><u>Customer Name</u></th>
    <th width='73'><u>Mobile No.</u></th>
   
    <th width='217'><U>Net Amount</U></th>
     <th width='217'><U>Payment</U></th>
  </tr>
<?php 
$i=1;
while($row = mysqli_fetch_row($res)) 
 {
 ///echo $row[11]."<br/>";
 $sum=0;

$qry1="SELECT * FROM  `approval` where cust_id='$row[11]' ";
$res1=mysqli_query($con,$qry1);                
$num1=mysqli_num_rows($res1);

$gsttot=0;
while($row1=mysqli_fetch_row($res1))
{

$pd=0;
 $s1=0;			

$ba=0;
$na=0;	
$ra=0;

$qry2="SELECT sum(paid_amount) FROM  `approval` where bill_id ='$row1[0]'";
$res2=mysqli_query($con,$qry2);                
$num2=mysqli_num_rows($res2);
$row2=mysqli_fetch_row($res2);
			
$qry3="SELECT sum(`amount`) FROM `approval_detail` WHERE bill_id ='$row1[0]'";
$res3=mysqli_query($con,$qry3);
$row3=mysqli_fetch_row($res3);
$a=0;
$a1=0;
$qry4="SELECT *  FROM `approval_detail` WHERE bill_id ='$row1[0]'";
$res4=mysqli_query($con,$qry4);
$gsttotiv=0;	
while($row4=mysqli_fetch_row($res4)){

$a=round(($row4[7]/$row4[2])*$row4[4]);
$a1+=$a;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba+=$row4[7];

}


$gsttotqr=mysqli_query($con,"select sum(sgstamt),sum(cgstamt),sum(igstamt) from approval_detail  WHERE bill_id ='$row1[0]'");
$gsttotrws=mysqli_fetch_array($gsttotqr);
$gsttot=$gsttot+$gsttotrws[0]+$gsttotrws[1]+$gsttotrws[2];


$na=$ba;
$s1=($ba-$a1);



//echo "cust=".$row[11]." amt=".$ba." bal amt=".$na." return".$a1." net amt=".$s1."<br/>";

$s=$row3[0]-$row2[0];
 $sum+=$s1;  



}




//echo "paid=".$pd."<br/>";

 $qry41="SELECT sum(amount) FROM `paid_amount` WHERE `bill_id`='$row[11]'";
$res41=mysqli_query($con,$qry41);
$num411=mysqli_num_rows($res41);
$row41=mysqli_fetch_row($res41);
///echo $id."/".$num411."<br/>";

 $qry42="SELECT SUM( paid_amount ),sum(card_amt) FROM  `approval` WHERE  `cust_id` ='$row[11]'";
$res42=mysqli_query($con,$qry42);
$row42=mysqli_fetch_row($res42);

if($num41==0 || $num41=="") 
{ 
  //  echo "ok1";
    $pd11=$row42[0]; 
    
}else{ 
    
   // echo "ok2";
    $pd11=$row41[0]; 
    
    
}
//echo "Cust=".$row[11]."/".$sum."-".$pd11."<br/>";
$sum+=$gsttot+$row42[1];
$ab=round($sum-$pd11,2);



//$ab+=$gsttot;
if($ab==0){
}else{
?>				   
<tr>

<td width="43"><?php echo $i; ?></td>

<td width="208" align="left"><?php echo $row[0]." " .$row[1]."(".$row[11].")"; ?></td>
<td width="73"> <?php echo $row[2]; ?></td>

<td width='217'><?php echo "Rs. ".round($ab,2)."/-";  ?></td>
<td width='60'  align="right"><a href="Approval_payment.php?cid=<?php echo $row[11]; ?>&amt=<?php echo $ab; ?>"><b>Payment</b></a></td>
 </tr>
<?php $i++; $total2+=$ab; }  } ?>
<tr>
<td width="43"></td>
<td width="208" align="center" colspan="2"></td>
<td width='60'  align="right"><b>Total Amount: <?php echo "Rs. ".round($total2,2)."/-"; ?></b></td>

 </tr>			
</table>
		  
   
   </td></tr></table>  </div>  
   <?php CloseCon($con);?>
</form>
</body>        