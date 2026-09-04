<? include('../config.php');
header('Content-Type: application/json; charset=utf-8');


$todaysdt=date("Y-m-d");


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

$pathmain ='https://yosshitaneha.com/';

$sku = $_REQUEST['query'] ;

        $re = mysqli_query($con3,"SELECT unit_price,quantity,category FROM phppos_items where name like '".$sku."'");
        $rero=mysqli_fetch_row($re);
        
        $re1 = mysqli_query($con3,"select sum(commission_amt) from order_detail where item_id='".$sku."' and 
        bill_id in(select bill_id from phppos_rent where booking_status!='Booked')");
        $rero1=mysqli_fetch_row($re1);
        

        $qty=round($rero[1]);

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
        
        
        
           
        
        
            
        $data[] = ['mrp'=>$mrp,'addedRentPrice'=>$addedRentPrice,'sku'=>$sku,'deposit'=>$deposit,
        'discount'=>$discount,'lastSellingPrice'=>$lastSellingPrice,'type'=>'Apparel'];
        
        
        
        
        
        
        
        $re = mysqli_query($con3,"SELECT unit_price,quantity FROM phppos_items where name like '".$sku."'");
        $rero=mysqli_fetch_row($re);
        
        $re1 = mysqli_query($con3,"select sum(commission_amt) from order_detail where item_id='".$sku."' and 
        bill_id in(select bill_id from phppos_rent where booking_status!='Booked')");
        $rero1=mysqli_fetch_row($re1);
        

        $qty=round($rero[1]);

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
            $lastSellingPrice = $mrp - ( $mrp * 0.5 ) ; 
        }
        
        
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
        
            
        $data[] = ['mrp'=>$mrp,'addedRentPrice'=>$addedRentPrice,'sku'=>$sku,'deposit'=>$deposit,
        'discount'=>$discount,'courier'=>$courier,'lastSellingPrice'=>$lastSellingPrice,'type'=>'Jewellery'];
            



 echo json_encode($data);
?>