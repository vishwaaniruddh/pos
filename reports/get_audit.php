<style>
td{text-align:center;}
</style>
<?php
 $sumorg=0;
$sumqty=0;
$sumtqty=0;
$sumcost=0;
$sumsale=0;

// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$adate=$_GET['barcode'];
?>
<div id="bill">
<b> Audit Date: </b><?php  if(isset($adate) and $adate!='0000-00-00') echo date('d/m/Y',strtotime($adate)); ?> &nbsp;&nbsp;&nbsp;
<?php 
//echo "SELECT * FROM  `audit` where audit_date='$adate'";
$sql11=mysqli_query($con,"SELECT * FROM  `audit` where audit_date='$adate'");
$row11=mysqli_fetch_row($sql11);

if($row11[6]=="Yes"){}else{?>
<input type="button"  value="Edit Audit Item" onclick="javascript:location.href = 'editaudit_item.php?dt=<?php echo $adate; ?>';"/>
<input type="button"  value="Audit Item's" onclick="javascript:location.href = 'audit_item.php?dt=<?php echo $adate; ?>';"/>
<?php } ?>
<table width="636" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
    <th width="42"><font size="2">SR NO.</font></th>
    <th width="99"><font size="2">CATEGORY</font></th>
    <th width="99"><font size="2">BARCODE</font></th>
    <th width="99"><font size="2">ITEM NAME</font></th>
    <th width="102"><font size="2">ORIGINAL QTY</font></th>
    <th width="40"><font size="2"> AUDIT QTY</font></th>
    <th width="83"><font size="2"> MISSING QTY</font></th>
    <th width="83"><font size="2">COST PRICE</font></th>
    <th width="83"><font size="2">SALES PRICE</font></th>
  </tr>
  <?php

$j=1;
$sql=mysqli_query($con,"SELECT * FROM  `audit` where audit_date='$adate'");
while($row=mysqli_fetch_row($sql)){
	
	$sql1=mysqli_query($con,"SELECT * FROM  `phppos_items` where name='$row[1]'");
	$row1=mysqli_fetch_row($sql1);
	$sum=$row[4]-$row[3];
	$tot1=$row1[5]*$sum;
	$tot2=$row1[6]*$sum;
?>
  <tr>
    <td width="42"><?php echo $j++; ?></td>
    <td width="99"><?php echo $row1[1]; ?></td>
    <td width="99"><?php echo $row[2]; ?></td>
    <td width="99"><?php echo $row[1]; ?></td>
    <td width="102"><?php echo $row[4]; $sumorg+=$row[4]; ?></td>
    <td width="40"><?php echo $row[3]; $sumqty+=$row[3];?></td>
    <td width="83"><?php echo $sum; $sumtqty+=$sum; ?></td>
    <td width="83"><?php echo $tot1; $sumcost+=$tot1; ?></td>
    <td width="83"><?php echo $tot2; $sumsale+=$tot2;  ?></td>
  </tr>
  <?php 
}
?>
  <tr>
    <td height="29" colspan="4" align="right">Total : </td>
    <td><?php echo $sumorg; ?></td>
    <td><?php echo $sumqty; ?></td>
    <td><?php echo $sumtqty; ?></td>
    <td><?php echo $sumcost; ?></td>
    <td><?php echo $sumsale; ?></td>
  </tr>
</table>
</div> <?php CloseCon($con);?>