<?php
// 	include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

	ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


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
{alert("hi");
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
    <?php

$sqlp=mysqli_query($con,"SELECT * FROM  `phppos_people` WHERE  `person_id` ='$name'");
		$people=mysqli_fetch_row($sqlp);
		

	  $qry="SELECT * FROM  `phppos_rent` where throught='$name' ";
	  
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
$sum=0;			
				 				 
?>
<?php
while($row = mysqli_fetch_row($res)) 
 {
	
//echo $row2[0]."&&".$s;
  $sum+=(float)$row[19]; 	}
  $i=1;
		 $amt=0;
  
    $qry1=mysqli_query($con,"SELECT sum(amount) FROM  `commission_paid` where name='$name' ");
    $row1=mysqli_fetch_row($qry1);
     ?>
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
<img src="bill.PNG" width="408" height="165"/><br/><br/>
Commission Paid<br/><br/><center>
<form  action="processComm.php" id="frm1" name="frm1" method="POST">
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br/><br/>
     
     <table width="100%"><tr><td width="370">Commision Given To :&nbsp;&nbsp;<?php echo $people[0]; ?></td><td>Mobile : &nbsp;&nbsp;<?php echo $people[2]; ?></td></tr>
     <tr><td>Paid Amount : &nbsp;&nbsp;<?php echo $row1[0]; ?></td><td>Balance Amount :&nbsp;&nbsp;<?php echo $sum-$row1[0]; ?></td></tr>
     
         <tr>
       <td height="57" colspan="2" align="center"><table width="410" border="1" cellpadding="4" cellspacing="0">
         <tr>
           <td width="126">Sr No</td>
           <td width="116">Amount</td>
           <td width="136">Payment Date</td>
         </tr>
         <?php 
    $qry2=mysqli_query($con,"SELECT * FROM  `commission_paid` where name='$name' ");
    while($row2=mysqli_fetch_row($qry2)){
      ?>
         <tr>
           <td><?php echo $i++; ?></td>
           <td><?php echo $row2[3]; $amt+=$row2[3]; ?></td>
           <td> <?php if(isset($row2[4]) and $row2[4]!='0000-00-00') echo date('d/m/Y',strtotime($row2[4])); ?></td>
         </tr>
         <?php } ?>
         <tr>
           <td>Total Amount :</td>
           <td><?php echo $amt; ?></td>
           <td>&nbsp;</td>
         </tr>
       </table></td></tr>
     
     </table>
      
</form></center>
 </td></tr>
 </table>
</div>

<?php CloseCon($con);?>


<div align="center">You are using Point Of Sale Version 10.5 .</div>