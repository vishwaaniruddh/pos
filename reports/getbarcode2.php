<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$cid=$_GET['id'];
  $it=$_GET['barcode'];
  $bar=$_GET['barcode2'];
?>

<?php
 if($bar==""){
      // echo "1";
       $barcode=$_GET['barcode'];
       
 $sq="SELECT item_id,sum(qty),sum(return_qty),aid FROM `approval_detail` WHERE item_id='$barcode' and bill_id in (select bill_id from approval where cust_id='$cid' and status='A') group by item_id";
 
 }else if($it==""){
///echo "2";
 $barcode2=$_GET['barcode2'];
 $qry=mysqli_query($con,"select name from  phppos_items where item_number='$barcode2' and is_deleted = 0");
 $row2=mysqli_fetch_row($qry);
 $barcode=$row2[0];
/// echo $barcode;
 $sq="SELECT item_id,sum(qty),sum(return_qty),aid FROM `approval_detail` WHERE item_id='$barcode' and bill_id in (select bill_id from approval where cust_id='$cid' and status='A') group by item_id";
 }
 $result2 = mysqli_query($con,$sq);
 $num=mysqli_num_rows($result2);
 
	while($row2 = mysqli_fetch_row($result2))
{

$sq="SELECT * FROM phppos_items WHERE name='$row2[0]' and is_deleted = 0";
$res2 = mysqli_query($con,$sq);
$num2=mysqli_num_rows($res2);
$row1=mysqli_fetch_row($res2);



//echo $rowordno[0]."/ ".$design[$i]." / ".$prz[$i]." / ".$dep[$i]."<br/>";
if($row2[1]==$row2[2]){
echo "0";
	?>
    
    
<?php	
}else{
  ?>
  
 <table width="765" border="1" cellpadding="4" cellspacing="0" id="results">

 <tr>
  <input name="aid[]" type="hidden" value="<?php echo $row2[3]; ?>"  />
<input name="it[]" type="hidden" value="<?php echo $row2[0]; ?>" class="design"/>
    <td width="82" height="64" align="center"><?php echo $row1[0]; ?><input name="barc[]" type="hidden" value="<?php echo $row1[3] ?>" class="barc"/></td>
    <td width="143" align="center"><?php echo $row1[1]; ?></td>
    <td width="85" align="center"><?php echo $row1[6] ?></td>
    <td width="105" align="center"><?php echo $row2[1] ?></td>
    <td width="108" align="center"><?php echo $row2[2] ?></td>
 
    <td width="180" align="center"><input name="qty[]" type="text" class="qty" value="1" onkeyup="checkTotal();" <?php if ($row2[1]==$row2[2]){echo "readonly=\"readonly\"";}?>/><input type="hidden" id="qt[]" class="qt" name="qt[]" value="<?php echo $row2[1]-$row2[2];?>" /></td>
    
  </tr>
  </table>
  <?php   } }
  
CloseCon($con);

  ?>