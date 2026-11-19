<?php include('config.php');
 $qryitem=mysql_query("select distinct category from phppos_items");	 	
		$category=array();
		 while($row=mysql_fetch_row($qryitem))
		 {
		$category[]=$row[0];
		}
$i=$_REQUEST['num'];
	 ?>
     <script type="text/javascript" src="jquery-1.11.1.min.js"></script>
<table>
<tbody>
<div>
<tr>
	<td><input type="text" name="item_no[]" class="item_no" id="item_no_<?php echo $i; ?>" autocomplete="off" <?php if($i==1){ ?> value="<?php echo $item_info;?>" readonly="readonly" <?php }else{ ?> onClick="item_num1(this)"<?php } ?>></td>    
	<td><input type="text" name="myitemid[]" class="item_id" id="textField_<?php echo $i; ?>" value="" onKeyUp="checkUsername(event);" /> </td>&nbsp;
	<td>
		<select  name="item_cat[]" class="item_cat" id="itemcat_<?php echo $i; ?>">
			<option value="0">Select</option>
			<?php 
			for($j=0;$j< count($category);$j++)
			{
			?>
				<option value="<?php echo $category[$j]; ?>"><?php echo $category[$j]; ?></option>
			<?php
			}
			?>
		</select>
	</td>
	<td><input type="text" name="cprice[]" class="cprice" id="cprice_<?php echo $i; ?>" onChange="subtotal()" value=""  onkeypress="return isNumberKey(event)" autocomplete="off" /> </td> 
	<td><input type="text" name="uprice[]" class="uprice" id="uprice_<?php echo $i; ?>"  value=""   autocomplete="off">&nbsp; </td> 
	<td> <input type="text" name="qty[]" class="qty" id="qty_<?php echo $i; ?>" onChange="subtotal()" onKeyPress="return isNumberKey(event)" value=""  autocomplete="off"></td> 
	<td> <input type="text" name="subtotal[]" class="subtotal" align="right" readonly></td>
	<td><input type="button" value="Remove" onClick="deleteRow(this)"/></td>
</tr>
</div><tbody><table>