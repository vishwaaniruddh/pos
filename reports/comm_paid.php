<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


// 	include('config.php');

	include('../db_connection.php') ;
$con=OpenSrishringarrCon();

	
$name=$_GET['name'];
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

/////////////////
function formSubmit()
{
if(document.getElementById('paid_date').value=="")
 {
alert("Please Select payment Date to continue.");
document.getElementById('paid_date').focus();
return false;
} else if(document.getElementById('amount').value=="")
 {
alert("Please Enter Amount to continue.");
document.getElementById('amount').focus();
return false;
}
else{

document.getElementById("frm1").submit();
 return true;
 }

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

$sqlp=mysqli_query($con,"SELECT * FROM  `phppos_people` WHERE  `person_id` ='$name'");
		$people=mysqli_fetch_row($sqlp);
		

	  $qry="SELECT * FROM  `phppos_rent` where throught='$name' ";
	$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
$sum=0;			
				 				 
?>
<?php
while($row = mysqli_fetch_array($res)) 
 {
	
  $sum = $sum + (float)$row['total_comm']; 
  	}
  
//   echo 'this' . $sum ; 
  echo "SELECT sum(amount) FROM  `commission_paid` where name='$name' " ; 
    $qry1=mysqli_query($con,"SELECT sum(amount) FROM  `commission_paid` where name='$name' ");
    $row1=mysqli_fetch_row($qry1);
     ?>
    <table width="788" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
  
<tr><td  valign="top" align="center">
<?php
 
$result5=mysqli_query($con,"select * from   `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($result5,10);

$row7=mysqli_fetch_array($result5);

?>
<img src="bill.PNG" width="408" height="165"/><br/><br/>
Commission Paid<br/><br/><center>
<form  action="processComm.php" id="frm1" name="frm1" method="POST">
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br/><br/>
     
     <table width="100%">
     <tr>
     <td width="370">Commision Given To :&nbsp;&nbsp;<?php echo $people[0]; ?></td>
     <td>Mobile :&nbsp;&nbsp;<?php echo $people[3]; ?></td>
     </tr>
     
     <tr>
     <td>Paid Amount : <?php echo $row1[0]; ?></td>
     <td>Balance Amount:<?php 
     
     echo (float)$sum-(float)$row1[0]; ?></td>
     </tr>
     
     <tr>
     <td height="59"> Paid Date :
       
       <input type="text" name="paid_date" id="paid_date"  onClick="displayDatePicker('paid_date');" /></td>
       <td> <input type="hidden" name="cname" value="<?php echo $people[0]; ?> "  /> </td>
     </tr>
     <tr><td height="57" >Amount :
       <label for="textfield"></label>
       <input type="hidden" name="name" id="name" value="<?php echo $name; ?>" /> <input type="text" name="amount" id="amount" />       &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
       </td>
       
        <td>In Account :
	 <select id="acc" name='acc'><option value="0">Select </option>
      <?php 
      
      $qryitem=mysqli_query($con,"select bank_name, bank_id from banks");
       while($row=mysqli_fetch_row($qryitem))
		 {
				echo "<option value='".$row[1]."'>".$row[0]."</option>";
			}?></select></td>
       
       </tr>
     
     <tr><td colspan="2" align="center"><input type="button" onClick="formSubmit()" value="Submit" /></td></tr>
     </table>
      
      <br/>
</form></center>
 </td></tr>
 
 </table>
	

	
</div>

<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>