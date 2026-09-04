<?php

include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$idd=$_GET['id'];

// echo $idd; 

$res="select * from phppos_people where person_id='".$idd."'";
$resl_query=mysqli_query($con,$res);
$resl=mysqli_fetch_row($resl_query);

// echo "<pre>";print_r($resl);echo"</pre>"; die;


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

 <link href = "https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css"
         rel = "stylesheet">
      <script src = "https://code.jquery.com/jquery-1.10.2.js"></script>
      <script src = "https://code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
      
      <script>
         $(function() {
            $( "#dob" ).datepicker();
            // $( "#dob" ).datepicker("show");
         });
      </script>
      
      
<title>Edit Customer</title>
<style>
input{ width:180px;}

.sub {width:100px;height:25px;}
</style>
</head>

<body>
<center>

<h1>Edit Customer </h1>

<a href="/pos/purchase/view_supplier.php" style="font-size:18px;font-weight:bold;">Back</a>

<form method="post" action="Supp_edit.php">
<table>
<tr>
    
<td width="135" height="34">First Name :</td>
<td width="336"><input type="text" name="first_name" id="first_name" value="<?php echo $resl[0];?>" class="form-control"/></td>
</tr>

<tr>
<td width="135" height="34">Last Name :</td>
<td width="336"><input type="text" name="last_name" id="last_name" value="<?php echo $resl[1];?>" class="form-control"/></td>
</tr>

<tr>
<td height="34">Mobile No. :</td><td><input type="text" name="phone" id="phone" value="<?php echo $resl[2];?>" class="form-control"/></td>
</tr>

<tr>
<td height="34">Email :</td><td><input type="text" name="email" id="email" value="<?php echo $resl[3];?>" class="form-control" /></td>
</tr>

<tr>
<td height="50">Address :</td>
<td><input type="text"  name="add" id="add" class="form-control"  value="<?php echo $resl[4];?>" /></td>
</tr>
<?php
if($resl[12]!="0000-00-00")
{
    if(!is_null($resl[12]))
    {
        $date=date('d/m/Y', strtotime($resl[12]));
    }
}
    

?>
<tr>
<td height="34">DOB :</td><td><input type="text" name="dob" id="dob"  value="<?php echo $date;  ?>" class="form-control" autocomplete="off"  placeholder="dd/mm/yyyy"/></td>
</tr>


<tr>
<td height="34">
    <input type="hidden" name="id" value="<?php echo $idd;?>">
<!--<input type="hidden"  name="mode" value="" />-->
<input type="submit" name="submit" id="submit" class="sub" value="submit"/></td>

</tr>

</table>
</form>
</center>
</body>
</html>

   
<?php CloseCon($con);?> 
