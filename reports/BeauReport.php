<?php
ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


 $total2=0;
 $com_sum=0;     

$qry="SELECT * FROM  `phppos_people` where first_name like 'B %' order by first_name ASC";
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
				 				 
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
  <font size="+1"><center>
<a href="/pos/home_dashboard.php" style="font-size:18px;font-weight:bold;">Back</a>&nbsp;&nbsp;&nbsp;<a href="#" style="font-size:18px;font-weight:bold;" onclick='PrintDiv();'>Print</a></center></font>
<div style="text-align: center;" id="bill">

<table align="center"><tr><td width="808" align="center">
<img src="bill.PNG" width="408" height="165"/><br><br>
Beautician Balance Amount Report<br/>
<br/></td></tr>
<tr><td>
<table  border="1" cellpadding="4" cellspacing="0" width="867" align="left" id="bill">
 <tr>
 <th width='43' height="34"><U>Sr.No.</U></th>
    
    <th width='196'><u>Customer Name</u></th>
    <th width='86'><u>Mobile No.</u></th>
   
    <th width='98'><U>Net Commision Amount</U></th>
   
  </tr>
<?php 
$i=1;
while($row = mysqli_fetch_row($res)) 
 {
 ///echo $row[11]."<br/>";
 $pd=0;
 $s1=0;			

$ba=0;
$na=0;	
$ra=0;

$qry1="SELECT * FROM  `phppos_rent` where throught='$row[11]' ";
$res1=mysqli_query($con,$qry1);                
$num1=mysqli_num_rows($res1);
while($row1=mysqli_fetch_row($res1)){
//echo $row1[19];
$ra+=$row1[19];

}
 $qry2=mysqli_query($con,"SELECT sum(amount) FROM  `commission_paid` where name='$row[11]' ");
    $row2=mysqli_fetch_row($qry2);
	$com_sum=$ra-$row2[0];
	//echo $com_sum;
if($com_sum==0){}else{?>				   
<tr>

<td width="43"><?php echo $i; ?></td>

<td width="196" align="center"><?php echo $row[0]." " .$row[1]; ?></td>
<td width="86"> <?php echo $row[2]; ?></td>

<td width='98'><?php echo "Rs. ".$com_sum."/-";  ?></td>
 </tr>
<?php $i++; $total2+=$com_sum; }  }?>
<tr>

<td width="43"></td>

<td width="196" align="center"></td>

<td width='106' colspan="3" align="right"><b>Total Amount: <?php echo "Rs. ".$total2."/-"; ?></b></td>
 </tr>			
</table>
		  
   
   </td></tr></table>  </div>  
   <?php CloseCon($con);?>