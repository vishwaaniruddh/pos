<?php
// 	include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

	
$name=$_GET['name'];
$mobile=$_GET['mobile'];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css"  />
<script type="text/javascript">
function validate(form){
	with(form){
		if(through.value=="0"){
			alert("Please select Through Name");
			through.focus();
			return false;
		}
	}
	return true;
}
</script>
</head>

<body>

<table width="879" align="center">
<tr><td align="center">
<a href="/pos/home_dashboard.php">Back</a>
</td></tr>
	<tr>
    
	</tr>
    <tr><td>
    <?php


	  $qry="SELECT * FROM  `phppos_rent` where throught='$name' and throught_phone='$mobile'";
	  
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
$sum=0;			
				 				 
?>
<?php
while($row = mysqli_fetch_row($res)) 
 {
	 $qry1="SELECT * FROM phppos_people where person_id='$row[1]'";
	 $result1=mysqli_query($con,$qry1);
	 $qry2="SELECT * FROM phppos_rent where throught='$throgh[0]' and throught_phone='$throgh[1]'";
	 $result2=mysqli_query($con,$qry2);
	 
	 $row1=mysqli_fetch_row($result1);
	 $row2=mysqli_fetch_row($result2);

//echo $row2[0]."&&".$s;
?>				   
				   
<?php  $sum+=$row2[19];?>

    
				
			<?php 	} ?>
    <table width="788" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
  
<tr><td  valign="top" align="center">
<?php
 include('config.php');
 
$result5=mysqli_query($con,"select * from   `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($result5,10);

$row7=mysqli_fetch_array($result5);

?>
<b><?php echo $row6[1]; ?></b><br><?php echo $row5[1]; ?><br/><?php echo $row7[1]; ?><br/><br/>
Approval Return<br/><br/><center>
<form  action="approval_detail.php" id="frm1" name="frm1" method="POST">
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br/><br/>
     
     <table width="100%"><tr><td width="370">Commision Given To :<?php echo $name; ?></td><td>Mobile<?php echo $mobile; ?></td></tr>
     <tr><td>Paid Amount: </td><td>Balance Amount:<?php echo $sum; ?></td></tr>
     
     <tr><td height="59">Date :
       <label for="textfield2"></label>
       <input type="text" name="textfield2" id="textfield2" /></td><td></td>
     </tr>
     <tr><td height="57" colspan="2" align="center">Amount :
       <label for="textfield"></label>
       <input type="text" name="name" id="name" value="<?php echo $name; ?>" /> <input type="text" name="mobile" id="mobile" value="<?php echo $mobile; ?>" /> <input type="text" name="amount" id="amount" />       &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
       <input type="button" onClick="formSubmit()" value="Submit" /></td></tr>
     
     </table>
      
      <br/>
</form></center>
 </td></tr>
 
 </table>
</div>
<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>