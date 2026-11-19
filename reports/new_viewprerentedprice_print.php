<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

function round_amount($amount) {
    $amount = (int)$amount;
    $add_amount = 0;
    $round_num = substr($amount, -2);
    if ($round_num < 50 && $round_num != 00) {
        $add_amount = 50 - $round_num;
    }
    if ($round_num > 50 && $round_num != 00) {
        $add_amount = 100 - $round_num;
    }
    $new_amount = $amount + $add_amount;
    return $new_amount;
}
$sku = $_REQUEST['sku'];
?>

<style>
.removethis:hover{
    color:red;
}
    th, td {
        text-align: center;
    }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<script>
    document.getElementById('skuForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        // Get existing SKU values from the URL
        const urlParams = new URLSearchParams(window.location.search);
        const existingSkus = urlParams.getAll('sku[]');

        // Get all input elements with name "sku[]"
        const newSkus = Array.from(document.querySelectorAll('input[name="sku[]"]'))
            .map(input => input.value)
            .filter(Boolean); // Filter out empty values

        // Combine existing and new SKU values
        const allSkus = [...existingSkus, ...newSkus];

        // Construct the URL
        const baseUrl = 'https://srishringarr.com/pos/reports/viewprerentedprice.php';
        const skuParams = allSkus.map(sku => `sku[]=${encodeURIComponent(sku)}`).join('&');
        const finalUrl = `${baseUrl}?${skuParams}`;
        
        // Redirect to the constructed URL
        window.location.href = finalUrl;
    });
</script>




<table id="printableDiv" border="1" cellpadding="3" cellspacing="0" style="margin:auto;">
    <tr>
        <th></th>
        <th>Product Code</th>
        <th>Rent Price</th>
        <th>Selling Price</th>
        <th>MRP</th>
        <th>Discount (In %) </th>
        <th>Discounted Selling Price</th>
        <th>Deposits</th>
    </tr>
    <?php 
    $totalrent = 0;
    $totalselling = 0;

    foreach ($sku as $skukey => $skuval) {
        $name = $skuval;

        $re = mysqli_query($con, "SELECT unit_price, quantity FROM phppos_items WHERE name LIKE '".$name."'");
        $rero = mysqli_fetch_row($re);

        $re1 = mysqli_query($con, "SELECT SUM(commission_amt) FROM order_detail WHERE item_id='".$name."' AND 
        bill_id IN (SELECT bill_id FROM phppos_rent WHERE booking_status != 'Booked')");
        if ($rero1 = mysqli_fetch_row($re1)) {
            $commissionAmount = $rero1[0];
        }

        $mrp = $unitPrice = $rero[0];
        $currentsp = $unitPrice - $commissionAmount;


$productsql = mysqli_query($con,"Select * from phppos_items where name='".$name."'");
$productsqlResult = mysqli_fetch_assoc($productsql);
$productType = $productsqlResult['category_type'];


if($productType==1){

        
        if($mrp<=2000){
                $courier = 100;
          }else if($mrp<=5000){
              $courier = 250;
          }else if($mrp<=10000){
              $courier = 500;
          }else{
              $courier = 1000;
          }
           
           
        
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
                        $lastSellingPrice = $mrp - ( $mrp * 0.5 ) ; 
        }
        
        if(isset($row['sales_price']) && $row['sales_price'] > 0){
            $lastSellingPrice = $row['sales_price'];
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
                            $lastSellingPrice = $mrp - ( $mrp * 0.5 ) ; 
            }
        }
        
        if(isset($row['sales_price']) && $row['sales_price'] > 0){
            $lastSellingPrice = $row['sales_price'];
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

}


         
         $deposit =  round_amount($deposit);  



        $addedRentPrice = round_amount($addedRentPrice);
        ?>
        <tr>
            <td><span class="removethis" style="cursor:pointer;"> Remove </span></td>
            <td><?php echo $skuval; ?></td>
            <td contenteditable="true" class="rentPrice" data-initial="<?php echo $addedRentPrice; ?>"><?php echo $addedRentPrice; ?></td>
            <td contenteditable="true" class="sellingPrice" data-initial="<?php echo $lastSellingPrice; ?>"><?php echo $lastSellingPrice; ?></td>
            <td class="mrpPrice" data-initial="<?php echo $mrp; ?>"><?php echo $mrp; ?></td>
            <td contenteditable="true" class="discount">0</td>
            <td class="discountedSellingPrice" data-initial="<?php echo $mrp; ?>"><?php echo $mrp; ?></td>
            <td contenteditable="true"  class="deposit"><?php echo $deposit; ?></td>
        </tr>
        <?php 
        $totalrent += $addedRentPrice; 
        $totalselling += $lastSellingPrice; 
        $totalmrp += $mrp;
        $totalDeposits += $deposit ; 
    }
    ?>
    <tr>
        <th></th>
        <th>Total</th>
        <th id="totalRent"><?php echo number_format($totalrent,2); ?></th>
        <th id="totalSelling"><?php echo number_format($totalselling,2); ?></th>
        <th id="totalMrp"><?php echo number_format($totalmrp,2); ?></th>
        <th></th>
        <th id="totalDiscountedSellingPrice">0.00</th> <!-- Add total discounted selling price cell -->
        <th id="totalDeposit"><?php echo $totalDeposits ; ?></th>
        
    </tr>
</table>



<br />
<div style="text-align:center;">
    <button class="print-button" onclick="printTable()">Print Data</button>
    <button id="goBack">Go Back</button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const totalRentCell = document.getElementById('totalRent');
        const totalSellingCell = document.getElementById('totalSelling');
        const totalMrpCell = document.getElementById('totalMrp');
        const totalDiscountedSellingPriceCell = document.getElementById('totalDiscountedSellingPrice');

        const rentPriceCells = document.querySelectorAll('.rentPrice');
        const sellingPriceCells = document.querySelectorAll('.sellingPrice');
        const mrpPriceCells = document.querySelectorAll('.mrpPrice');
        const discountCells = document.querySelectorAll('.discount');
        const discountedSellingPriceCells = document.querySelectorAll('.discountedSellingPrice');
        const depositCells = document.querySelectorAll('.deposit');
        
        function updateTotals() {
            let totalRent = 0;
            let totalSelling = 0;
            let totalMrp = 0;
            let totalDiscountedSellingPrice = 0; // Initialize total discounted selling price

            rentPriceCells.forEach(cell => {
                const value = parseFloat(cell.textContent) || 0;
                totalRent += value;
            });

            sellingPriceCells.forEach(cell => {
                const value = parseFloat(cell.textContent) || 0;
                totalSelling += value;
            });

            mrpPriceCells.forEach(cell => {
                const value = parseFloat(cell.textContent) || 0;
                totalMrp += value;
            });

            discountedSellingPriceCells.forEach(cell => {
                const value = parseFloat(cell.textContent) || 0;
                totalDiscountedSellingPrice += value; // Add up the discounted selling prices
            });

            totalRentCell.textContent = totalRent.toFixed(2);
            totalSellingCell.textContent = totalSelling.toFixed(2);
            totalMrpCell.textContent = totalMrp.toFixed(2);
            totalDiscountedSellingPriceCell.textContent = totalDiscountedSellingPrice.toFixed(2); // Update total discounted selling price
        }

        function updateDiscountedSellingPrices() {
            discountCells.forEach((cell, index) => {
                const discountValue = parseFloat(cell.textContent) || 0;
                const mrpValue = parseFloat(mrpPriceCells[index].textContent) || 0;
                const discountedPrice = mrpValue * (1 - (discountValue / 100));
                discountedSellingPriceCells[index].textContent = discountedPrice.toFixed(2);
            });
        }

        rentPriceCells.forEach(cell => {
            cell.addEventListener('input', () => {
                updateTotals();
                updateDiscountedSellingPrices();
            });
        });

        sellingPriceCells.forEach(cell => {
            cell.addEventListener('input', () => {
                updateTotals();
                updateDiscountedSellingPrices();
            });
        });

        discountCells.forEach(cell => {
            cell.addEventListener('input', () => {
                updateDiscountedSellingPrices();
                updateTotals(); // Update totals after discount changes
            });
        });

        // Initialize totals on page load
        updateTotals();
        updateDiscountedSellingPrices();
    });


function printTable() {
    // Get the table and remove columns with the "removethis" class
    const table = document.getElementById('printableDiv');
    const removeElements = table.querySelectorAll('.removethis');

    // Temporarily hide the "Remove" column
    removeElements.forEach(element => {
        element.style.display = 'none';
    });

    // Prepare the content for printing
    const printContents = table.outerHTML;
    const printWindow = window.open('', '', 'height=500,width=800');
    printWindow.document.write('<html><head><title>Print Table</title></head><body>');
    printWindow.document.write(printContents);
    printWindow.document.write('</body></html>');
    printWindow.document.close();

    // Trigger print
    printWindow.print();

    // Restore the "Remove" column visibility
    removeElements.forEach(element => {
        element.style.display = '';
    });
}



    document.querySelectorAll(".removethis").forEach(element => {
    element.addEventListener("click", function () {
        const row = this.closest("tr"); // Find the closest table row
        const sku = row.children[1].innerText; // Get the SKU value from the second column
        
        // Get the current URL and extract query parameters
        const url = new URL(window.location.href);
        const searchParams = new URLSearchParams(url.search);

        // Get all SKU values as an array
        const skus = searchParams.getAll("sku[]");

        // Remove the clicked SKU from the array
        const updatedSkus = skus.filter(value => value !== sku);

        // Construct a clean query string with the remaining SKUs
        const queryString = updatedSkus.map(updatedSku => `sku[]=${encodeURIComponent(updatedSku)}`).join('&');

        // Redirect to the updated URL
        const newUrl = `${url.origin}${url.pathname}?${queryString}`;
        window.location.href = newUrl;
    });
});


$(document).on('click','#goBack',function(){
    window.history.back();
})


</script>