<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$it=$_GET['barcode'];
$bar=$_GET['barcode2'];
//echo $it."/".$bar;

       if($bar==""){
      // echo "1";
       $barcode=$_GET['barcode'];
       $qry="select item_number,name,category,unit_price from  phppos_items where name='$barcode' ";
       
}else if($it==""){
//echo "2";
 $barcode=$_GET['barcode2'];
 $qry="select item_number,name,category,unit_price from  phppos_items where item_number='$barcode'";
}

$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
			
				 				 
?>
<table border="0"  width="989" align="left">
<?php
$suggest = mysqli_fetch_row($res);


$item_number =$suggest[0]; 
$name=$suggest[1]; 
$category=$suggest[2]; 
$unit_price=$suggest[3]; 
?>				   
				   
<tr>
<td width="181"><?php echo $barcode; ?>
  <input name="design[]" type="hidden" value="<?php echo $name; ?>" class="design"/><input name="barc[]" type="hidden" value="<?php echo $item_number; ?>" class="barc"/></td>
<td width="189" ><?php echo $category; ?></td>
<td width="197"> <?php echo $unit_price; ?>
  <input name="prz[]" type="hidden"  class="prz" value="<?php echo $unit_price; ?>"/></td>
  <td width="179"><input name="amount[]" type="text" value="" class="amount" onKeyUp="Totalamount();" /></td>
 <td  align="left" width="209"> Rs
   <input name="<?php echo $name; ?>" type="radio" class="ds" value="Rs" checked onClick="Totalamount();">&nbsp;&nbsp;%<input name="<?php echo $name; ?>" type="radio" value="%" class="ds1" onClick="Totalamount();"><br/>

<input name="dis1[]" type="text" value="" class="disamt" onKeyUp="Totalamount();" /></td>
 
</tr>
				
			<?php 
			 CloseCon($con);
			?>
</table>
			  
             