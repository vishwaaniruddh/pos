

<?php
//  include('config.php');
 include('../db_connection.php') ;
$con=OpenSrishringarrCon();

 
$result=mysqli_query($con,"select * from  `phppos_people` where first_name not like 'B %'");
//$row = mysqli_fetch_array($result);

?>
<center>
<font size="+1"><a href="/pos/home_dashboard.php">Back</a></font><br>
<img src="bill.PNG" width="408" height="165"/><br/><br/>
<h2> Customer Report </h2><br/>


<table width="840" border="1" cellpadding="0" cellspacing="0">
<tr>
<th width="104">Sr No. </th>
<th width="416">Name </th>
<th width="66">Phone No </th>
<th width="74">Email </th>
<th width="168">Address </th>
</tr>

<?php
$i=1; 
while($row=mysqli_fetch_row($result))
{ ?>
<tr>
<td><?php echo $i; ?></td>
<td><?php echo $row[0]." ".$row[1]; ?></td>
<td><?php echo $row[2]; ?></td>
<td><?php echo $row[3]; ?></td>
<td><?php echo $row[4]." ".$row[5]; ?></td>
</tr>
<?php  $i++; } ?>

</table>
</center>
<?php CloseCon($con);?>