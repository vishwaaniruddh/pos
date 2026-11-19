<?php
session_start();
/*(if(!isset($_SESSION['user']))
echo "<script type='text/javascript'>window.close();</script>";
else
{*/
$i=0;
// include("config.php");

include('../db_connection.php') ;
$con=OpenSrishringarrCon();

?>
<table><tr><td>Sr No</td><td>Name</td><td>Contact Number</td><td>Area</td><td>State</td></tr>
<?php
$qry=mysqli_query($con,"select * from area_engg where loginid not in(select engineer from eng_feedback)");
while($qryro=mysqli_fetch_array($qry))
{
$state=mysqli_query($con,"select state from state where state_id='".$qryro[2]."'");
$statero=mysqli_fetch_row($state);
?>
<tr><td><?php echo $i=$i++; ?></td><td><?php echo $qryro[1]; ?></td><td><?php echo $qryro[5]; ?></td><td><?php echo $statero[0] ?></td></tr>
<?php
}
//}
CloseCon($con);
?></table>