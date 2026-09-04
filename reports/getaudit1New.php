<style>
input { width:130px;}
</style>
<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$barcode=$_GET['barcode'];
$cnt=$_GET['cnt'];

  
       $qry="select item_number,name,category,unit_price,quantity from  phppos_items where category IN ($barcode)   order by name";
 
    //   echo $qry;
   

$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
			
if($num==0){
	echo "0";
}else{
?>
<table border="0"  width="734" align="left">
<?php
$sum=0;
while($suggest = mysqli_fetch_row($res)){


 $qryaudit=mysqli_query("SELECT * FROM `auditNew` where item_id='".$suggest[1]."' ");
  $fetchAudit= mysqli_fetch_array($qryaudit);
    // echo "SELECT * FROM `auditNew` where item_id='".$suggest[1]."' ";
    
    //===================insert query==========
  // $result1 = mysqli_query("insert into `auditNew` (item_id,barcode,qty,org_qty,audit_date,current_dt,category) values ('$design[$i]','$barc[$i]','$qty[$i]','$org_qty[$i]','".$bill_date."','".$currdt."','".$category."')");

 
    //==========================================

$item_number =$suggest[0]; 
$name=$suggest[1]; 
$category=$suggest[2]; 
$unit_price=$suggest[3]; 
$quan=$suggest[4]; 
?>				   

				   
<tr>
<td width="37"><?php echo ++$cnt; ?></td>
<td width="95" align="center">
    <?php echo $item_number; ?><input name="barc[]" type="hidden" value="<?php echo $item_number; ?>" class="barc"/></td>
<td width="97" align="center"><?php echo $name; ?>
    <input name="design[]" type="hidden" value="<?php echo $name; ?>" class="design"/>
</td>
<td width="114" align="center">
    <?php echo $category; ?>
</td>
<td width="86" align="center"> 
    <?php echo $unit_price;  ?>
    <input name="toamt[]" type="hidden" value="<?php echo $unit_price; ?>" class="toamt"/>
    <input name="org_qty[]" type="hidden"  class="org_qty" value="<?php echo $quan; ?>"/>
</td>

<td  align="center" width="104">
     <?php echo $quan; ?>
</td>

<?php if($fetchAudit['category']!="")
{ ?>
    <td  align="center" width="155"> <input name="qty[]" type="text" value="<?php echo $fetchAudit['qty'] ;?>" class="qty" onkeyup="checkTotal();Totalamount();"/></td>
<?php }
else { 
?>
<td  align="center" width="155"> <input name="qty[]" type="text" class="qty" onkeyup="checkTotal();Totalamount();"/></td>

<?php  } ?>
</tr>
				
			<?php }	?>
            
           <input name="catg[]" type="hidden" value="<?php echo $barcode; ?>" class="catg"/>
          
            </table>
			  <?php }
			  CloseCon($con); ?>
             