<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$it=$_GET['barcode'];
$bar=$_GET['barcode2'];
$cnt=$_GET['cnt'];
$pdt=$_GET['pdt'];
$ddt=$_GET['ddt'];
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

<table border="1"  width="1223" align="left">
<?php
$z=1;
while($suggest = mysqli_fetch_array($res, mysqli_ASSOC)) 
 {

$item_number =$suggest['item_number']; 
$name=$suggest['name']; 
$category=$suggest['category']; 
$unit_price=$suggest['unit_price'];
$a=($unit_price*30)/100;
$b=($a-$discount);
?>				   
				   
<tr>
<td width="55"><?php echo ++$cnt; ?></td>
<td width="77"><?php echo $name; ?>
  <input name="design[]" type="hidden" value="<?php echo $name; ?>" class="design"/>
<input name="barc[]" type="hidden" value="<?php echo $item_number; ?>" class="barc"/>
</td>
<td width="137" ><?php echo $category; ?><br>
  <br><textarea name="items[]" id="items[]" rows="10" cols="15">Neck PC,Long Set, Earrings, Tikka, Damini, Hathful, Baju Bandh, Kamar Patta, Pag Panja, Payal, Nath</textarea></td>
<td width="38" align="left"> <?php echo $unit_price; ?></td>

<td width="43">

<table  border="0">
<?php 
//echo"SELECT * FROM `order_detail` where `item_id`='$barcode' and bill_id in (select bill_id from phppos_rent where  `booking_status`='Picked' or booking_status='' or booking_status='Returned')";
$sql=mysqli_query($con,"SELECT * FROM `order_detail` where `item_id`='$barcode' and bill_id in (select bill_id from phppos_rent where  `booking_status`='Picked' or booking_status='' or booking_status='Returned')");
while($row=mysqli_fetch_row($sql)){
?>
<tr><td>(<?php echo $row[8]; ?>)</td></tr>
<?php  } ?>
</table>

</td>

<td  align="left" width="119"> <input name="qty[]" type="text" value="1" class="qty" style="width:100px;" onKeyUp="checkTotalqty();checkTotal1();checkTotal();"/></td>
<td  align="left" width="137"> <input name="prz[]" class="prz" id="prz1" type="text" style="width:100px;" value="<?php echo round($a, 0); ?>"  onKeyUp="checkTotalqty();checkTotal1();checkTotal();;"/></td>
<td width="150"><input type="text" name="discount[]" class="discount" id="prz2" style="width:100px;" onKeyUp="checkTotalqty();checkTotal1();checkTotal();" /></td>
<td width="130"><input type="text" class="amt"  name="amt[]" style="width:100px;"  onKeyUp="checkTotalqty();checkTotal1();checkTotal();"/></td>
<td width="125"> <input name="dep[]" type="text" style="width:100px;" class="txt" /></td> 

<td width="142">
<?php 
//echo "SELECT * FROM `order_detail` where `item_id`='$barcode' and bill_id in(SELECT bill_id  FROM `phppos_rent` WHERE `pick_date` >= STR_TO_DATE('".$pdt."','%d/%m/%Y') AND `delivery_date` <= STR_TO_DATE('".$ddt."','%d/%m/%Y') ORDER BY `phppos_rent`.`pick_date` ASC)";
$sql2=mysqli_query($con,"SELECT * FROM `order_detail` where `item_id`='$barcode' and bill_id in(SELECT bill_id  FROM `phppos_rent` WHERE `pick_date` >= STR_TO_DATE('".$pdt."','%d/%m/%Y') AND `delivery_date` <= STR_TO_DATE('".$ddt."','%d/%m/%Y') ORDER BY `phppos_rent`.`pick_date` ASC) group by bill_id");
$num=mysqli_num_rows($sql2);
///echo $num;
if($num==0){
echo "hii";
$sql2=mysqli_query($con,"SELECT * FROM `order_detail` where `item_id`='$barcode' and bill_id in(SELECT bill_id  FROM `phppos_rent` WHERE  `delivery_date` <= STR_TO_DATE('".$ddt."','%d/%m/%Y') ORDER BY `phppos_rent`.`pick_date` ASC) group by bill_id");
}
while($row2=mysqli_fetch_row($sql2)){
//echo "SELECT * FROM `phppos_rent` where  bill_id='$row2[0]'";
$sql3=mysqli_query($con,"SELECT * FROM `phppos_rent` where  bill_id='$row2[0]'");
$row3=mysqli_fetch_row($sql3);
//echo $row2[9]."-".$row2[10]."-".$row2[0];	
if(($row2[9]-$row2[10])==0){}else{																					
  if(isset($row3[11]) and $row3[11]!='0000-00-00')  echo date('d/m/Y',strtotime($row3[11]))."&nbsp;-&nbsp;";if(isset($row3[12]) and $row3[12]!='0000-00-00')  echo date('d/m/Y',strtotime($row3[12]))."<br/>"; 
  }
  } ?></td>

</tr>
				
			<?php  } 
			CloseCon($con);
			?>
</table>
			  
               