<?php
// include('config.php');
    include('../db_connection.php') ;
$con=OpenSrishringarrCon();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


function round_amount($amount){
$amount = (int)$amount;
$add_amount = 0;

    $round_num = substr( $amount, -2);
    
        if($round_num < 50 && $round_num!=00 ){
            $add_amount = 50 - $round_num;  
        
        }
        if($round_num > 50 && $round_num != 00 ){
            $add_amount = 100 - $round_num;  
        }
    $new_amount = $amount + $add_amount; 
    
    return $new_amount;            

}



$it=$_GET['barcode'];
$bar=$_GET['barcode2'];
$cnt=$_GET['cnt'];
$pdt=$_GET['pdt'];
$ddt=$_GET['ddt'];
//echo $it."/".$bar;

       if($bar==""){
      // echo "1";
       $barcode=$_GET['barcode'];
       $qry="select item_number,name,category,unit_price,quantity from  phppos_items where name='$barcode' and CAST(abs(quantity-0.00) AS DECIMAL) > 0";
       
}else if($it==""){
//echo "2";
 $barcode=$_GET['barcode2'];
 $qry="select item_number,name,category,unit_price,quantity from  phppos_items where item_number='$barcode' and CAST(abs(quantity-0.00) AS DECIMAL) > 0";
}


// echo $qry ;

$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
if($num>0){							 				 
?>

<table border="1"  width="1223" align="left">
<?php
$z=1;
while($suggest = mysqli_fetch_array($res, MYSQLI_ASSOC)) {

$item_number =$suggest['item_number']; 
$name=$suggest['name']; 
$category=$suggest['category']; 
$unit_price=$suggest['unit_price'];
$quantity=$suggest['quantity'];

$a=($unit_price*30)/100;
$b=($a-$discount);




$commissionAmount = 0 ; 
$courier = 0;
$deposit = 0;




// echo "SELECT unit_price,quantity FROM phppos_items where name like '".$name."'" ; 
$re = mysqli_query($con,"SELECT unit_price,quantity FROM phppos_items where name like '".$name."'");
        $rero=mysqli_fetch_row($re);
        

        $re1 = mysqli_query($con,"select sum(commission_amt) from order_detail where item_id='".$name."' and 
        bill_id in(select bill_id from phppos_rent where booking_status!='Booked')");
        if($rero1=mysqli_fetch_row($re1)){
            
            $commissionAmount = $rero1[0] ;
        }
        
        
        
            $mrp = $unitPrice = $rero[0];
            $currentsp = $unitPrice - $commissionAmount ;   
        


// Get product type 
// echo "Select * from phppos_items where name='".$row2[1]."'" ; 
$productsql = mysqli_query($con,"Select * from phppos_items where name='".$name."'");
$productsqlResult = mysqli_fetch_assoc($productsql);
$productType = $productsqlResult['category_type'];

// echo '$productType = ' . $productType  ; 
if($productType==1){
    // Jewellery 
    // calculate 3% total GST
    
    
//   echo 'jewellery';
        
        if($mrp<=2000){
                $courier = 100;
          }else if($mrp<=5000){
              $courier = 250;
          }else if($mrp<=10000){
              $courier = 500;
          }else{
              $courier = 1000;
          }
           
           
        
        if($currentsp > 0 ) {
            if($mrp<=10000){
                $rentprice=$mrp*0.20;
                $addedRentPrice = $courier + $rentprice ;
                $deposit = $mrp * 0.35 ;
            }else {
                if($currentsp<=40000){
                    $rentprice=$currentsp*0.20;
                    // echo 'rentprice = currentsp * 0.20 = ' . $rentprice .'<br>';
                    } else if($currentsp<=60000){
                        $rentprice=$currentsp*0.17;
                        // echo 'rentprice = currentsp * 0.17 = ' . $rentprice .'<br>';
                        } else{
                            $rentprice=$currentsp*0.15;
                            // echo 'rentprice = currentsp * 0.15 = ' . $rentprice.'<br>' ;
                            }
                            $addedRentPrice = $courier + $rentprice ;
                            if($addedRentPrice < 3000){
                                $addedRentPrice = 3000 ;
                            }
                            $deposit = $currentsp * 0.35 ;
                            if($deposit<3000){
                                $deposit = 3000 ;
                            }
            }
        }
        else{
            if($mrp<=10000){
                $rentprice=$mrp*0.20;
                $addedRentPrice = $courier + $rentprice ;
                $deposit = $mrp * 0.35 ;
            }else{
                $deposit = 3000 ;
                $addedRentPrice = 3000 ;
            }   
         }
        
        
        
        
        
    
}else if($productType==2){
    // Apparel 
    // calculate 12% total GST
    
    
    
       
        if($currentsp > 0 ) {
                            if($mrp<=10000){
                               $courier = 1000;
                               $rentprice=$mrp*0.20;
                               $addedRentPrice = $courier + $rentprice ;
                               $deposit = $mrp * 0.35 ;
                            }else {
                               $courier = 2000;
                                if($currentsp<=40000){
                                    $rentprice=$currentsp*0.20; 
                                } else if($currentsp<=60000){
                                    $rentprice=$currentsp*0.17; 
                                } else{
                                    $rentprice=$currentsp*0.15; 
                                }
                                $addedRentPrice = $courier + $rentprice ;
                                if($addedRentPrice < 3000){
                                    $addedRentPrice = 3000 ; 
                                }
                                
                                $deposit = $currentsp * 0.35 ; 
                                    if($deposit<3000){
                                        $deposit = 3000 ; 
                                    }
                                    
                                
                            }
                            

        }
        else{
                            if($mrp<=10000){
                               $courier = 1000;
                               $rentprice=$mrp*0.20;
                               $addedRentPrice = $courier + $rentprice ;
                               $deposit = $mrp * 0.35 ;
                            }else{
                                $deposit = 3000 ;
                                $addedRentPrice = 3000 ;                                 
                            }   
                            

         }
         
         
        
        

}




        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
         
         
         
         
         $deposit =  round_amount($deposit);  

$addedRentPrice = round_amount($addedRentPrice); 
        
         
         








?>				   
				   
<tr>
<td width="55"><?php echo ++$cnt; ?></td>
<td width="77"><?php echo $name; ?>
  <input name="design[]" type="hidden" value="<?php echo $name; ?>" class="design"/>
<input name="barc[]" type="hidden" value="<?php echo $item_number; ?> " class="barc"/>
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

<td  align="left" width="119"> <input name="qty[]" type="text" value="1" class="qty" style="width:100px;" onKeyUp="checkTotalqty();checkTotal1();checkTotal();checkTotalDeposit();"/></td>
<!--<td  align="left" width="137"> <input name="prz[]" class="prz" id="prz1" type="text" style="width:100px;" value="<?php echo round($a, 0); ?>"  onKeyUp="checkTotalqty();checkTotal1();checkTotal();;"/></td>-->

<td  align="left" width="137"> <input name="prz[]" class="prz" id="prz1" type="text" style="width:100px;" value="<?php echo $addedRentPrice ; ?>"  onKeyUp="checkTotalqty();checkTotal1();checkTotal();checkTotalDeposit();"/></td>
<td width="150"><input type="text" name="discount[]" class="discount" id="prz2" style="width:100px;" onKeyUp="checkTotalqty();checkTotal1();checkTotal();checkTotalDeposit()" /></td>
<td width="130"><input type="text" class="amt"  name="amt[]" style="width:100px;"   onKeyUp="checkTotalqty();checkTotal1();checkTotal();checkTotalDeposit();"/></td>
<input name="og_dep[]" type="hidden" style="width:100px;" class="txt" value="<?php echo $deposit ; ?>" onKeyUp="checkTotalDeposit();" />
<td width="125"> <input name="dep[]" type="text" style="width:100px;" class="dep" value="<?php echo $deposit ; ?>" onKeyUp="checkTotalDeposit();" /></td> 

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
  } ?>
  <input type="hidden" name="total_qty[]" class="total_qty" value="<?php echo $quantity; ?>"></td>

</tr>
				
			<?php  } ?>
</table>
	<?php }
		else
		echo "0"; 
		CloseCon($con);
		?>		  
               