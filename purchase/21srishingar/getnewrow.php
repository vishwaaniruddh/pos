<?php
// include('config.php');
include('../../db_connection.php') ;
$con=OpenSrishringarrCon();

 $qryitem=mysqli_query($con,"select * from phppos_items where is_deleted = 0");

 ?>

<div>Item Name : <select name="item_id[]" class="item_id"><option value="0">Select Item</option><?php while($row=mysqli_fetch_row($qryitem)){ echo "<option value='".$row[9]."'>".$row[0]."</option>";}?></select>&nbsp; Price : <input type="text" align="right" name="price[]" onChange="subtotal()" value="0" onkeypress="return isNumberKey(event)" class="price">&nbsp; Quantity : <input type="text" name="qty[]" align="right" id="qty" class="qty" value="0" onkeypress="return isNumberKey(event)" onChange="subtotal()"> Amount : <input type="text" name="subtotal[]" class="subtotal" align="right" readonly>
<?php CloseCon($con);?>