<?php include('config.php');
 $qryitem=mysql_query("select distinct category from phppos_items");	 	
		$category=array();
		 while($row=mysql_fetch_row($qryitem))
		 {
		$category[]=$row[0];
		}
	 ?>
     <script type="text/javascript" src="jquery-1.11.1.min.js"></script>
<table>
<tbody>
<div><tr>
<td><input type="text" name="item_no[]" class="item_no" id="item_no" value="" onClick="item_num1(this)" autocomplete="off" / ></td>
<td><input type="text" name="myitemid[]" class="item_id" id="textField" value="" onkeyup="checkUsername(event);" />  </td>
    <td><select name="item_cat[]" class="item_cat">
       <option value="0">Select</option>
       <?php 
	   
	   for($i=0;$i< count($category);$i++){
		   ?><option value="<?php echo $category[$i]; ?>"><?php echo $category[$i]; ?></option><?php
	   
	   }
	   ?>
       
    </select></td> 
      
<td><input type="text" name="cprice[]" class="cprice" onChange="subtotal()" value=""  onkeypress="return isNumberKey(event)" autocomplete="off" /> </td> 
  <td><input type="text" name="uprice[]" class="uprice"  value=""   autocomplete="off">&nbsp; </td> 
    <td> <input type="text" name="qty[]" id="qty" class="qty" onChange="subtotal()"  onkeypress="return isNumberKey(event)" value="" align="right" autocomplete="off">  </td>

<td> <input type="text" name="subtotal[]" class="subtotal" align="right" readonly></td>