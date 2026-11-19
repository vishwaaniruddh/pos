<?php  include('../db_connection.php');
$garmentid = $_REQUEST['garmentid'];

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


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



$pathmain ='https://srishringarr.com/yn/';


$sql_query = "select *, CAST(REGEXP_SUBSTR(gproduct_code,'[0-9]+') AS UNSIGNED) as sku from `garment_product` 
where  product_for='".$garmentid."' and gproduct_id in(select gproduct_id from product_images_new where gproduct_id>0) order by sku desc";

$raw_data = mysqli_query($web_con,$sql_query);
$i = 1;



?>


<div class="row">
    
    
<?
while($row = mysqli_fetch_array($raw_data)){
      
      $courier=0 ; 
        $deposit = 0;
        $product_id = $row[0];    
        $prcode=$row[2];
        $sku = $prcode;
        
         $re = mysqli_query($con,"SELECT unit_price,quantity FROM phppos_items where name like '".$prcode."'");
        $rero=mysqli_fetch_row($re);
        
    

    $qty = 0;
        $qty=$rero[1];
        if($qty && $qty > 0){
         
                $rentReceivedsql = "select sum(commission_amt) from order_detail where item_id='".$prcode."' and 
        bill_id in(select bill_id from phppos_rent where booking_status!='Booked')" ; 
        
        $re1 = mysqli_query($con,"select sum(commission_amt) from order_detail where item_id='".$prcode."' and 
        bill_id in(select bill_id from phppos_rent where booking_status!='Booked')");
        $rero1=mysqli_fetch_row($re1);
        
        
        
            $mrp = $unitPrice = $rero[0];
            $commissionAmount = $rero1[0] ;
            $currentsp = $unitPrice - $commissionAmount ;   
        
        $lastSellingPrice = 0 ; 
        $sellingPriceCalculation = $mrp - $commissionAmount ; 
        
        $sellingPriceCalculationPrecentageAmount = $sellingPriceCalculation * 0.4 ; 
        $sellingPriceCalculation = $sellingPriceCalculation - $sellingPriceCalculationPrecentageAmount  ;  

        if($mrp>=10000){
            if($sellingPriceCalculation < 5000){
                $lastSellingPrice = 5000 ; 

            }else{
                $lastSellingPrice = $sellingPriceCalculation ;

            }
        }else if($mrp < 10000){
            if($sellingPriceCalculation<3000){
                $lastSellingPrice = 3000 ; 
            }else{
                $lastSellingPrice = $sellingPriceCalculation ; 
            }
        }
        
        
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
        
        
        
        
            $deposit =  round_amount($deposit);  
        $addedRentPrice = round_amount($addedRentPrice) ; 
        
        
        
        $sqlimg="SELECT img_name FROM `product_images_new` WHERE `gproduct_id`='".$product_id."'";
            $qryimg = mysqli_query($web_con,$sqlimg);
            $rowimg = mysqli_fetch_row($qryimg);
           
              $path = ($pathmain."uploads".$rowimg[0]);
                $source_img = trim("yn/uploads".$rowimg[0]);
                $filename = basename($source_img);
                $_file_parent = "https://srishringarr.com/";
                $_new_filename = $_file_parent.$source_img;
                if(!file_exists($_new_filename)){
                   $destination_img =  $path;
                }else{
                    $destination_img =  str_replace($filename,'',$source_img) .'com_'.$filename;
                }
                
                // $imgframe = '<img class="lazyload img-fluid product_img" loading="lazy" style="width: 100%; object-fit: contain; user-select: auto;" src="https://yosshitaneha.com/thumbs/'.$source_img.'">';
                           $imgframe = '<img class="lazyload img-fluid product_img" loading="lazy" style="width: 100%; object-fit: contain; user-select: auto;" src="//images.weserv.nl/?url='.$destination_img.'&w=400&h=300">';

            
            $link = "apparel_detail.php?id=$product_id&days=3";
            $newProductName = $row['newProductName'];
            if($newProductName){
                $link = 'apparel/'.$newProductName.'&days=3' ; 
            }
            
            
        
        
if(isset($row['rent_price']) && $row['rent_price'] > 0 ){
    $addedRentPrice = $row['rent_price'] + $courier;
}

if(isset($row['deposit']) && $row['deposit'] > 0 ){
    $deposit = $row['deposit'];
}


if(isset($row['sales_price']) && $row['sales_price'] > 0){
    $lastSellingPrice = $row['sales_price'];
}


 
 ?>
    <div class="col-sm-3 product_grid" style="margin: 10px auto;" data-selling_price = <?= $lastSellingPrice ;?> data-rent_price = <?= $addedRentPrice ;?> >
        <img class="grid_img" src="<?php echo $destination_img ; ?>" style="width:100%;" />
        <hr />
        SKU: <strong><?= $sku; ?></strong>
        <p class="rent_price">Rent Price : <?= $addedRentPrice ; ?></p>
        <p class="selling_price">Selling Price : <?= $lastSellingPrice ; ?></p>
        
<a class="btn btn-primary measurment_button" href="./editMeasuments.php?productid=<?php echo $product_id; ?>&sku=<?php echo $sku; ?>&img=<?php echo $destination_img ; ?>">Add / Edit Measuments</a>        

    </div>
 <?
}
}

?>

</div>



 <script>
    $(document).ready(function() {
        $('.addInExclusiveCollection').click(function() {
            // Get data attributes from the button
            var image = $(this).data('image');
            var sku = $(this).data('sku');
            var link = $(this).data('link');

            // Create an object with the data
            var dataToSend = {
                image: image,
                sku: sku,
                link: link,
                type:'Apparel'
            };
        });
    });
</script>
