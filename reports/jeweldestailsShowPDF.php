<?php  include('../db_connection.php');

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


$jewelid = $_REQUEST['jewelid'];

$get_id = $jewelid ; 

$pathmain ='https://srishringarr.com/yn/';


if($get_id==0 && $get_type==1 && $viewall == 4 ){
 $all = "'2','1','6','3','68','4'";
 $necklace=1;
}

if($get_id==1 ||$get_id==2 ||$get_id==3 ||$get_id==4 ||$get_id==6||$get_id==68){
    if($type==1){
      $necklace=1;
    }
}

if($get_id==0 && $get_type==1 && $viewall == 76 ){
 $all = "'59','74','75','76','77','78','79','80'";
}



   if($all){
    $sql_query = "select product_id, product_image, product_code, product_name, product_desc, date_added, categories_id, subcategory, subcat_id, maincatagory, 
    subcatagoty, sales_price, rent_price, itemid, product_new_imageid, deposit, facebook, Instagram, Google, Twitter, Pinterest, flipkart, amazon, discount,
    total_amt, seen_count, is_customized, is_color_same, is_pattern_same, is_piece_same, cgst, sgst, igst, short_desc, brand_color,
    youtube,CAST(REGEXP_SUBSTR(product_code,'[0-9]+') AS UNSIGNED) as sku,newProductName,ss_product_name from `product` where subcat_id in($all) order by sku desc";
   }else{
    $sql_query = "select product_id, product_image, product_code, product_name, product_desc, date_added, categories_id, subcategory, subcat_id, maincatagory,
    subcatagoty, sales_price, rent_price, itemid, product_new_imageid, deposit, facebook, Instagram, Google, Twitter, Pinterest, flipkart, amazon, discount, 
    total_amt, seen_count, is_customized, is_color_same, is_pattern_same, is_piece_same, cgst, sgst, igst, short_desc, brand_color, youtube,
    CAST(REGEXP_SUBSTR(product_code,'[0-9]+') AS UNSIGNED) as sku,newProductName,ss_product_name from `product` where subcat_id='".$get_id."' order by sku desc";
}

// echo $sql_query ; 


// echo '<br />';

$raw_data = mysqli_query($web_con,$sql_query);
$i = 1;
$cur_sym = $currency_symbol;


?>
<div class="row">
    
    
<?php
while($row = mysqli_fetch_array($raw_data)){
      
      
        $deposit = 0;
        $product_id = $row[0];    
        $prcode=$row[2];
        $sku = $prcode;
          
          
           $re = mysqli_query($con,"SELECT unit_price,quantity FROM phppos_items where name like '".$prcode."'");
        $rero=mysqli_fetch_array($re);
        
    

    $qty = 0;
        $qty=$rero['quantity'];
        if($qty && $qty > 0){
            
            
            $re1 = mysqli_query($con,"select sum(commission_amt) as commision_amount from order_detail where item_id='".$prcode."' and 
        bill_id in(select bill_id from phppos_rent where booking_status!='Booked')");
        $rero1=mysqli_fetch_array($re1);
        
        
        
            $mrp = $unitPrice = $rero['unit_price'];
            $commissionAmount = $rero1['commision_amount'] ;
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
        
        
           $sqlimg="SELECT img_name FROM `product_images_new` WHERE `product_id`='".$product_id."'";
            $qryimg = mysqli_query($web_con,$sqlimg);
            $rowimg = mysqli_fetch_row($qryimg);
             if($youtube){
                $ytarray=explode("/", $youtube);
                $ytendstring=end($ytarray);
                $ytendarray=explode("?v=", $ytendstring);
                $ytendstring=end($ytendarray);
                $ytendarray=explode("&", $ytendstring);
                $ytcode=$ytendarray[0];
                $imgframe =  "<iframe title=\"\" width=\"100%\" height=\"315\"  src=\"https://www.youtube.com/embed/$ytcode\" autoplay=\"0\"  frameborder=\"0\" allowfullscreen  loading=\"lazy\"></iframe>";
            }
            else if($rowimg){
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
                $imgframe = '<img class="lazyload img-fluid product_img" loading="lazy" style="width: 100%; object-fit: contain; user-select: auto;" src="//images.weserv.nl/?url='.$destination_img.'&w=400&h=300" alt="'.$product_name.'">';
            }
        
        
        $link = "jewel_detail.php?id=$product_id&days=3";
        $newProductName = $row['newProductName'];
        if($newProductName){
            $link = 'jewel/'.$newProductName.'&days=3' ; 
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
<img src="<?php echo $destination_img ; ?>" style="width:100%;" />

        <hr />
        SKU: <strong><?= $sku; ?></strong>
        <p class="rent_price">Rent Price : <?= $addedRentPrice ; ?></p>
        <p class="selling_price">Selling Price : <?= $lastSellingPrice ; ?></p>
        
        
        <button class="btn btn-primary addInExclusiveCollection" data-productid="<?= $product_id; ?>"  data-image="<?= $destination_img; ?>" data-sku="<?= $sku; ?>" data-link="<?= $link; ?>"> Add </button>
    </div>
 <?php
}
}

?>

</div>



 <script>
    $(document).ready(function() {
        $('.addInExclusiveCollection').click(function() {
            var image = $(this).data('image');
            var sku = $(this).data('sku');
            var link = $(this).data('link');
            
            var dataToSend = {
                image: image,
                sku: sku,
                link: link,
                type:'Jewel'
            };
           
        });
    });
</script>