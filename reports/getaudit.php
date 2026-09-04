<style>
input { width:130px;}
</style>
<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$it=$_GET['barcode'];
$bar=$_GET['barcode2'];
$cnt=$_GET['cnt'];
//echo $it."/".$bar;

       if($bar==""){
      // echo "1";
       $barcode=$_GET['barcode'];
       $qry="select item_number,name,category,unit_price,quantity from  phppos_items where name='$barcode' ";
       
}else if($it==""){
//echo "2";
 $barcode=$_GET['barcode2'];
 $qry="select item_number,name,category,unit_price,quantity from  phppos_items where item_number='$barcode'";
 
}

$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
			
if($num==0){
	echo "0";
}else{
?>
<table border="0"  width="734" align="left">
<?php

$suggest = mysqli_fetch_row($res);


$item_number =$suggest[0]; 
$name=$suggest[1]; 
$category=$suggest[2]; 
$unit_price=$suggest[3]; 
$quan=$suggest[4]; 
?>				   
				   
<tr>
<td width="37"><?php echo ++$cnt; ?></td>
<td width="95" align="center"><?php echo $item_number; ?><input name="barc[]" type="hidden" value="<?php echo $item_number; ?>" class="barc"/></td>
<td width="97" align="center"><?php echo $name; ?><input name="design[]" type="hidden" value="<?php echo $name; ?>" class="design"/>
</td>
<td width="114" align="center"><?php echo $category; ?></td>
<td width="86" align="center"> <?php echo $unit_price; ?><input name="org_qty[]" type="hidden"  class="org_qty" value="<?php echo $quan; ?>"/></td>
<td  align="center" width="104"> <?php echo $quan; ?></td>

<td  align="center" width="155"> <input name="qty[]" type="text" value="1" class="qty" onkeyup="checkTotal();Totalamount();"/></td>
</tr>
				
			<?php 	?>
            </table>
			  <?php
			  
    CloseCon($con);
} 
			  
			  ?>
             