<style>
td{text-align:center;}
</style>
<?php
 $sumorg=0;
$sumqty=0;
$sumtqty=0;
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();



$dt=$_GET['dt'];
?>
<div id="bill" align="center">
<b> Audit Date: </b><?php  if(isset($dt) and $dt!='0000-00-00') echo date('d/m/Y',strtotime($dt)); ?>
 &nbsp;&nbsp;&nbsp;
<table width="636" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
    <th width="42"><font size="2">SR NO.</font></th>
    <th width="99"><font size="2">CATEGORY</font></th>
    <th width="99"><font size="2">BARCODE</font></th>
    <th width="99"><font size="2">ITEM NAME</font></th>
    <th width="102"><font size="2">ORIGINAL QTY</font></th>
    <th width="40"><font size="2"> QTY</font></th>
    <th width="83"><font size="2"> MISSING QTY</font></th>
    
  </tr>
  <?php

$j=1;
$sql=mysqli_query($con,"SELECT * FROM  `audit` where audit_date='$dt'");
while($row=mysqli_fetch_row($sql)){
	
	$sql1=mysqli_query($con,"SELECT * FROM  `phppos_items` where name='$row[1]'");
	$row1=mysqli_fetch_row($sql1);
	$sql1=mysqli_query($con,"SELECT * FROM  `phppos_items` where name='$row[1]'");
	$row1=mysqli_fetch_row($sql1);
	$sum=$row[4]-$row[3];
	
	//$ex=mysqli_query($con,"update `phppos_items` set quantity='$row[3]' where name='$row[1]' and item_number='$row[2]'");
	$ex=mysqli_query($con,"update `audit` set status='Yes' where id='$row[0]'");
	if($ex){
				//echo "Audit Item Are Done";
		//echo "<p >Audit Item's Not Done</p>"
	
	
?>


  <tr>
    <td width="42"><?php echo $j++; ?></td>
    <td width="99"><?php echo $row1[1]; ?></td>
    <td width="99"><?php echo $row[2]; ?></td>
    <td width="99"><?php echo $row[1]; ?></td>
    <td width="102"><?php echo $row[4]; $sumorg+=$row[4]; ?> </td>
    <td width="40"><?php echo $row[3]; $sumqty+=$row[3];?> </td>
    <td width="83"><?php echo $sum; $sumtqty+=$sum;?> </td>
    
  </tr>
  <?php 
     } 
}
CloseCon($con);

?>
  
</table>
</div>

