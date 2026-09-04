<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


       $id=$_GET['barcode'];

$qry="SELECT * FROM  `scheme` where cust_id='$id' and status='A' ";
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
	$bal1=0;
	$pay=0;		
	$sra=0;	
	$na1=0;		 				 
?>
<table  border="1" cellpadding="4" cellspacing="0" width="823" align="left">
 <tr>
  <th width='57' height="34"><U>Sr.No.</U></th>
    <th width='57' height="34"><U>Bill No.</U></th>
    <th width='96'><u>Customer Name</u></th>
    <th width='66'><u>Throught</U></th>
     <th width='65'><u>Bill Date</U></th>
	 <th width='78'><u>Maturty Date</U></th>
  <th width='98'><U>Paid Amount </U></th>
    <th width='87'><U>Balance  Amount</U></th>
	<th width='87'><U>Scheme Return Amount </U></th>
		<th width='87'><U>Net Amount </U></th>
    <th width='97'><U>Scheme  Return</U></th>
  </tr>
<?php
$i=1;
while($row = mysqli_fetch_array($res)){
    
    $new_bill_number = $row['new_bill_number'];
$sql1=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$row[1]'");
$row1=mysqli_fetch_row($sql1);
$bal=$row[3]-$row[6]; 
?>				   
				   
<tr>
<td width="57"><?php echo $i; ?></td>
<td width="57"><?php echo $new_bill_number ? $new_bill_number : $row[0]; ?></td>
<td width="96" align="center"><?php echo $row1[0]." " .$row1[1]; ?></td>
<td width="66"><?php echo $row[5]; ?></td>
<td width="65"> <?php if(isset($row[2]) and $row[2]!='0000-00-00') echo date('d/m/Y',strtotime($row[2])); ?></td>
<td width="78"> <?php if(isset($row[7]) and $row[7]!='0000-00-00') echo date('d/m/Y',strtotime($row[7])); ?></td>
<td width="98"><?php echo $row[6]; $pay+=$row[6]; ?></td>
<td width="87"><?php echo  $bal; $bal1+=$bal;?></td>
<td  align="left" width="87"><?php $p=round($row[3]*(65/100.0)); echo $p;  $sra+=$p;?></td>
<td><?php $na=$row[3]-$p; echo $na; $na1+=$na;?></td>
 <td  align="left" width="97"><a href="rent_detail1.php?id=<?php echo $row[0]; ?>">Scheme Return</a></td>
  </tr>
				
			<?php $i++; } ?>
			<tr>
<td colspan="5" align="right"><b>Total : </b></td>
<td width="98"><?php echo $pay;?></td>
<td width="87"><?php echo  $bal1; ?></td>

 <td  align="left" width="87"><?php echo $sra; ?></td>
  <td  align="left" width="87"><?php echo $na1; ?></td>
   <td  align="left" width="87"><?php  ?></td>
  </tr>
</table>
		<?php CloseCon($con);?>	  
               