<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>

</head>

<body>

<?php
ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();



$cust=$_GET['cust'];
$beu=$_GET['beu'];

if($cust=='Customer' && $beu=='Beautician'){
$qry="SELECT * FROM `phppos_people` WHERE `person_id` not in (SELECT `person_id` FROM `phppos_suppliers`) ORDER BY `phppos_people`.`first_name` ASC";
$res=mysqli_query($con,$qry);    }            

else if($cust=='Customer') { 
$qry="SELECT * FROM `phppos_people` WHERE `person_id` not in (SELECT `person_id` FROM `phppos_suppliers` ) and `first_name` not Like 'B %' ORDER BY `phppos_people`.`first_name` ASC";
$res=mysqli_query($con,$qry); 
}

else if($beu=='Beautician'){
$qry="SELECT * FROM  `phppos_people` where first_name like 'B %' order by first_name ASC";
$res=mysqli_query($con,$qry);                
}

?>

<table border="1" cellpadding="1" cellspacing="0">
<tr>
<th>Customer Name </th>
<th>Mobile </th>
<th>Select </th>
</tr>
<?php
while($row=mysqli_fetch_row($res)){
?>
<tr>
<td><?php echo $row[0]." ".$row[1]; ?></td>
<td><?php echo $row[2]; ?></td>
<td><input type="checkbox" name="send" value="send" class="checkall"/></td>
</tr>
<?php } 
CloseCon($con);
?>
</table>

</body>
</html>