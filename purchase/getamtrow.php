<?php 

// include('config.php'); 
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


 $cnt=$_GET['cnt'];
CloseCon($con); 
 ?>

<table width="547">
<tr><td width="94">Amount : </td>
<td width="166"><input type="text" name="amt[<?php echo $cnt; ?>]" id="amt<?php echo $cnt; ?>" placeholder="Enter Amount" /></td><td width="66"> Memo </td>
<td width="201"><textarea id="memo<?php echo $cnt; ?>" name="memo[<?php echo $cnt; ?>]" placeholder="Enter Transaction Memo"></textarea></td></tr></table>
