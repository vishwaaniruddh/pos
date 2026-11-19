<?php
// include("config.php");
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$q=$_GET['searchdata'];
//echo "select name from phppos_items where  name like '%".$q."%' order by item_id DESC LIMIT 5";
$sql_res=mysqli_query($con,"select name from phppos_items where  name like '%".$q."%' order by item_id DESC LIMIT 5");
echo "<select class='show' >";
while($row=mysqli_fetch_array($sql_res))
{
$username=$row['name'];
//$email=$row['email'];
//$b_username='<strong>'.$q.'</strong>';
//$b_email='<strong>'.$q.'</strong>';
//$final_username = str_ireplace($q, $b_username, $username);
//$final_email = str_ireplace($q, $b_email, $email);
?>

<!--<img src="author.PNG" style="width:50px; height:50px; float:left; margin-right:6px;" />-->
<option><?php echo $username; ?> </option>


<?php
}
echo "</select>";

CloseCon($con);

?>