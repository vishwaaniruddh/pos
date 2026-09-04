<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$it=$_GET['barcode'];
$bar=$_GET['barcode2'];
$nm=$_GET['num'];
$by=$_GET['by'];
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
<?php

$suggest = mysqli_fetch_row($res);


$item_number =$suggest[0]; 
$name=$suggest[1]; 
$category=$suggest[2]; 
$unit_price=$suggest[3]; 






$gstrate=0;
$sgstrate=0;
$cgstrate=0;
$igstrate=0;
 $gstamt=0;
 $cgstamt=0;
 $igstamt=0;
 
/*****************************GSTcalc************************************/
$cttyp="";
if($by!="APPROVAL RECEIPT")
{
    
    $gttp=mysqli_query($con,"select * from categories where category='".$category."'");
    $catdesc=mysqli_fetch_array($gttp);
 
 $cttyp=$catdesc['typ'];
  if($unit_price>=1000)
     {
         if($catdesc['typ']=="1")
         {
         $gstrate=3;
         }else
         {
             $gstrate=12;
         
         }
     }else
     {
         if($catdesc['typ']=="1")
         {
         $gstrate=3;
         }else
         {
             $gstrate=5;
         }
     }
 



 if($_GET['statetyp']=="1")
 {
     
    
     $rtc=$gstrate/100;
     $gstamt1=($unit_price*$rtc);
    
     $gstamt=$gstamt1/2;
     $cgstamt=$gstamt1/2;

    $sgstrate=$gstrate/2;
    $cgstrate=$gstrate/2;
    $igstrate=0;

 }else
 {
     
     $rtc=$gstrate/100;
     $igstamt=($unit_price*$rtc);
     $sgstrate=0;
     $cgstrate=0;
     $igstrate=$gstrate;

 }
 
 

}




?>				   
<tr>

<td width="83"><?php echo $name; ?><input name="design[]" type="hidden" value="<?php echo $name; ?>" id="design<?php echo $nm;?>" class="design"/>
<input name="barc[]" type="hidden" value="<?php echo $item_number; ?>" id="barc<?php echo $nm;?>" class="barc" readonly/>
<input name="cattyp[]" type="text" value="<?php echo $cttyp; ?>" id="cattyp<?php echo $nm;?>"/>
</td>


<td width="131" align="center"><?php echo $category; ?></td>
<td width="111"> <?php echo $unit_price; ?>
<input name="prz[]" style="width:72px;" type="hidden"  class="prz" value="<?php echo $unit_price; ?>" id="prz<?php echo $nm;?>" readonly/></td>
<td  align="left" width="181"> Rs<input name="<?php echo $name; ?>" type="radio" class="ds" value="Rs" checked onClick="Totalamount();">&nbsp;&nbsp;%<input name="<?php echo $name; ?>" type="radio" value="%" class="ds1" onClick="Totalamount();"><br/>

<input name="dis1[]"  type="text" value="" id="dis1<?php echo $nm;?>" class="disamt" onKeyUp="Totalamount();" /></td>
<td width="111"> 
<input name="przf[]" style="width:72px;" type="text"  class="przf" value="<?php echo $unit_price; ?>" id="przf<?php echo $nm;?>"readonly/></td>
 <td  align="left" width=""> <input name="qty[]" style="width:72px;" type="text" value="1" class="qty" id="qty<?php echo $nm;?>" onkeyup="checkTotal();Totalamount();" readonly/></td>

 <td width="83">
     <input style="width:72px;"  type="hidden" name="gstrate[]"   id="gstrate<?php echo $nm;?>" value="<?php echo $gstrate; ?>" readonly/> 
     <?php echo $suggest[4]; ?><input type="hidden" name="total_qty[]" class="total_qty" id="total_qty<?php echo $nm;?>" value="<?php echo $suggest[4]; ?>" readonly></td>
 <td width="72"><input style="width:72px;"  type="text" name="sgstrate[]"   id="sgstrate<?php echo $nm;?>" value="<?php echo $sgstrate; ?>" readonly/> </td>
 <td width="72"><input style="width:72px;" type="text" name="sgstamt[]"  id="sgstamt<?php echo $nm;?>"  value="<?php echo $gstamt; ?>" readonly/></td>
 <td width="72"><input style="width:72px;"  type="text" name="cgstrate[]"   id="cgstrate<?php echo $nm;?>" value="<?php echo $cgstrate; ?>" readonly/> </td>
 <td width="72"><input style="width:72px;" type="text" name="cgstamt[]"  id="cgstamt<?php echo $nm;?>"  value="<?php echo $cgstamt; ?>" readonly/></td>
 <td width="72"><input style="width:72px;"  type="text" name="igstrate[]"   id="igstrate<?php echo $nm;?>" value="<?php echo $igstrate; ?>" readonly/> </td>
 <td width="72"><input style="width:72px;" type="text" name="igstamt[]"  id="igstamt<?php echo $nm;?>"  value="<?php echo $igstamt; ?>" readonly/></td>
 <!--<td width="72"><input style="width:72px;" type="text" name="amtwithgsttot[]"  id="amtwithgsttot<?php echo $nm;?>"  value="<?php echo $unit_price+$gstamt+$cgstamt+$igstamt; ?>" readonly/></td>-->
 <td width="72"><input style="width:72px;" type="text" name="amtwithgsttot[]"  id="amtwithgsttot<?php echo $nm;?>"  value="" readonly/></td>
 <td width="72"><a href="javascript:void(0)" onclick="deleteRow(this)">Delete</td>
 
     </tr>
<?php } 
CloseCon($con);
?>
             