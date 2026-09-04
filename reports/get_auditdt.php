<?php
// include("config.php");
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$cat=$_GET['cat'];
$qry=mysqli_query($con,"select distinct(audit_date) from audit where item_id in (select name from phppos_items where category='".$cat."')");
?>
<option value="-1">select</option>
<?php
while($qryro=mysql_fetch_array($qry))
{
?>
<option value="<?php echo $qryro[0]; ?>"><?php echo date("d/m/Y",strtotime($qryro[0])); ?></option>
<?php 
}

CloseCon($con);

?>