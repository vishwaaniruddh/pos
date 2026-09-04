<?php
ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


 $total2=0;     
$alph=$_POST['alph'];

if(isset($_POST['submit']))
{

$qry="SELECT * FROM `phppos_people`";


if($alph!="")
{
$qry.=" where first_name like '".$alph."%'";
}

$qry.=" ORDER BY `phppos_people`.`first_name` ASC";

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
Customer Balance Amount Report(Rent)<br/>
<br/>

Search Name Starting with:<select name="alph" id="alph">
<?php 
foreach (range('A', 'Z') as $char) {
    echo $char . "\n";
?>
<option value="<?php echo $char;?>" <?php if($_REQUEST['alph']==$char){ echo 'selected'; } ?>><?php echo $char;?></option>
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
    <th style="white-space:nowrap;"> Invoice - Amount </th>
    <th width='217'><U>Net Amount</U></th>

  </tr>
<?php 
$i=1;
 $ab=0;
while($row = mysqli_fetch_row($res)) 
 {

// echo "SELECT SUM( rent_amount),SUM(sgstamt),SUM(cgstamt),SUM( igstamt),SUM(card_amt)  FROM `phppos_rent`  WHERE `cust_id`='$row[11]'" ; 
$sql4=mysqli_query($con,"SELECT SUM( rent_amount),SUM(sgstamt),SUM(cgstamt),SUM( igstamt),SUM(card_amt)  FROM `phppos_rent`  WHERE `cust_id`='$row[11]'");
$row4=mysqli_fetch_row($sql4);

// echo "SELECT SUM( amount ) FROM  `rent_amount` WHERE  `cust_id`='$row[11]'" ; 
$sql5=mysqli_query($con,"SELECT SUM( amount ) FROM  `rent_amount` WHERE  `cust_id`='$row[11]'");
$row5=mysqli_fetch_row($sql5);

$sm=$row4[0]+$row4[1]+$row4[2]+$row4[3]+$row4[4];
$ab=round($sm-$row5[0],2);

if($ab==0){
    
}else{
?>				   
<tr>

<td width="43"><?php echo $i; ?></td>
<td style="white-space:nowrap;" width="208" align="left"><?php echo $row[0]." " .$row[1]; ?></td>
<td width="73"> <?php echo $row[2]; ?></td>

<td style="white-space:nowrap; text-align:center;">
    <?php 
    $entries = []; // Initialize an empty array to store entries
    
    $getbillsql = mysqli_query($con, "SELECT * FROM `phppos_rent` WHERE `cust_id`='$row[11]'");
    while ($getbillsql_result = mysqli_fetch_assoc($getbillsql)) {
        $rent_amount = $getbillsql_result['rent_amount'];
        $billID = $getbillsql_result['bill_id'];
        $pstatus = $getbillsql_result['pstatus'];
        $bal_amount = $getbillsql_result['bal_amount'];
        $balanceAmount = $rent_amount - $bal_amount;
        $billIDLink = "<a href='./rent_report_detail.php?id=$billID' target='_blank'>$billID</a>";
        $entries[] = $billIDLink . ' - ' . number_format($balanceAmount, 2) . '-' . "<a href='rent_payment.php?cid=$row[11]&amt=$balanceAmount&bill_id=$billID'><b>Payment</b></a>";
    }
    
    // Join the entries with a separator (e.g., a comma and space)
    echo implode('<hr /> ', $entries);
    ?>
</td>


<td width='217'><?php echo "Rs. ".$ab."/-";  ?></td>

 </tr>
<?php $i++; $total2+=$ab; } } ?>
<tr>
<td width="43"></td>
<td width="208" align="center" colspan="3"></td>
<td width='60'  align="right"><b>Total Amount: <?php echo "Rs. ".$total2."/-"; ?></b></td>

 </tr>			
</table>
		  
   
   </td></tr></table>  </div>
   <?php CloseCon($con);?>
   
   </form></body>