<?php

include('db_connection.php') ;
$con=OpenSrishringarrCon();

$id=$_GET['id'];

// echo $idd; 

$res="select * from phppos_items where item_id='".$id."'";
$resl_query=mysqli_query($con,$res);
$resl=mysqli_fetch_row($resl_query);

$sup_id = $resl[2];
// echo $sup_id;

$supplier = mysqli_query($con,"select company_name from phppos_suppliers where person_id='".$sup_id."' ");
$supplier_res = mysqli_fetch_assoc($supplier);
$supp_name = $supplier_res['company_name'];

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
      
      
<title>Edit ItemCode Quantity</title>
<style>
input{ width:180px;}

.sub {width:100px;height:25px;}
</style>
</head>

<body>
<center>

<h1>Edit Itemcode </h1>
<a href="/pos/reports/itemcode_details.php" style="font-size:18px;font-weight:bold;">Back</a>

<form method="post" action="ItemCodeEdit_process.php">
<table>
<tr>
    
<td width="135" height="34">ItemCode :</td>
<td width="336"><input type="text" name="itemcode" id="itemcode" value="<?php echo $resl[0];?>" class="form-control" readonly/></td>
</tr>

<tr>
<td width="135" height="34">Quantity :</td>
<td width="336"><input type="text" name="quantity" id="quantity" value="<?php echo $resl[7];?>" class="form-control"/></td>
</tr>

<tr>
<td width="135" height="34">Supplier Name :</td>
<td width="336"><input type="text" name="supp_name" id="supp_name" value="<?php echo $supp_name;?>" class="form-control" readonly/></td>
</tr>

<tr>
<td width="135" height="34">Purchase Date :</td>
<td width="336"><input type="text" name="pur_date" id="pur_date" value="<?php echo $resl[4];?>" class="form-control"  readonly/></td>
</tr>

<tr>
<td width="135" height="34">Category :</td>
<td width="336"><input type="text" name="category" id="category" value="<?php echo $resl[1];?>" class="form-control"/></td>
</tr>

<tr>
<td height="34">
    <input type="hidden" name="itemid" value="<?php echo $id;?>">
<!--<input type="hidden"  name="mode" value="" />-->
<input type="submit" name="submit" id="submit" class="sub" value="submit"/></td>

</tr>

</table>
</form>
</center>
</body>
</html>

   
<?php CloseCon($con);?> 
