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
Beautician List<br/>
<br/></td></tr>
<tr><td>
<table  border="1" cellpadding="4" cellspacing="0" width="867" align="left" id="bill">
 <tr>
 <th width='43' height="34"><U>Sr.No.</U></th>
    
    <th width='196'><u>Customer Name</u></th>
    <th width='196'><u>Mobile No.</u></th>
    <th width='196'><u>Email</u></th>
    <th width='188'><u>Address</u></th>
    <th width='78'><u>DOB</u></th>
    
  </tr>
<?php 
$i=1;
while($row = mysqli_fetch_row($res)) 
 {
 
	//echo $com_sum;
?>				   
<tr>

<td width="43"><?php echo $i; ?></td>

<td width="196" align="left"><?php echo $row[0]." " .$row[1]; ?></td>
<td width="196"> <?php echo $row[2]; ?></td>
<td width="196"> <?php echo $row[3]; ?></td>
<td width="188"> <?php echo $row[4].$row[5]; ?></td>
<td width="78"> <?php  if(isset($row[12]) and $row[12]!='0000-00-00') echo date('d/m/Y',strtotime($row[12])); ?></td>

 </tr>
<?php $i++; ; }  ?>			
</table>
		  
   
   </td></tr></table>  </div>   
   <?php CloseCon($con);?>