<?php session_start() ; 

include('../top-header.php');?>
     <?php include('../top-navbar.php');?>
            <div class="container-fluid page-body-wrapper">
                <?php include('../navbar.php');
                $con = OpenSrishringarrCon();
                ?>
                
                <!-- partial -->
                  <div class="main-panel">
                        <div class="content-wrapper">
                    <?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


function round_amount($amount)
{
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
$todaysdt=date("Y-m-d");
?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<style>
      .removethis:hover {
            color: red;
        }

        th, td {
            text-align: center;
            border: 1px solid;
        }

        .red {
            color: red;
            font-weight: 700;
        }


    </style>

<div class="container mt-5">
        <h2 class="text-center mb-4">View Pre-Rented Price</h2>
        <hr />
        <form id="skuForm" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="sku[]" placeholder="Enter SKU" />
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>

<table id="printableDiv" class="table" border="1" cellpadding="3" cellspacing="0" style="margin:auto;">
<!--<table id="printableDiv" class="table table-bordered">-->
            <thead class="table-dark">
                <tr>
                    <th class="no-print">Action</th>
                    <th>Product Code</th>
                    <th>Rent Price</th>
                    <th>Selling Price</th>
                    <th>MRP</th>
                    <th class="no-print">Cost Price</th>
                    <th>Discount (In %)</th>
                    <th>Discounted Selling Price</th>
                    <th>Deposits</th>
                    <th class="no-print">Total Rent Receive</th>
                    <th class="no-print">MRP - Total Rent</th>
                    <th class="no-print">Cost Price - Total Rent</th>
                    <th class="no-print">MRP - Cost Price</th>
                    <th class="no-print">Booking</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php
                $totalrent = 0;
                $totalselling = 0;

                foreach ($sku as $skukey => $skuval) {
                    $name = $skuval;

                    $re = mysqli_query($con, "SELECT unit_price, quantity FROM phppos_items WHERE name LIKE '" . $name . "'");
                    $rero = mysqli_fetch_row($re);


$re1 = mysqli_query($con, "SELECT SUM(CAST(REPLACE(commission_amt, ',', '') AS DECIMAL(10,2))) 
                           FROM order_detail 
                           WHERE item_id='" . $name . "' 
                           AND bill_id IN (SELECT bill_id FROM phppos_rent WHERE booking_status != 'Booked')");

if ($rero1 = mysqli_fetch_row($re1)) {
    $commissionAmount = (float) $rero1[0];

}



                    $mrp = $unitPrice = $rero[0];
                    $currentsp = $unitPrice - $commissionAmount;

                    $productsql = mysqli_query($con, "Select * from phppos_items where name='" . $name . "'");
                    $productsqlResult = mysqli_fetch_assoc($productsql);
                    $productType = $productsqlResult['category_type'];
                    $cost_price = $productsqlResult['cost_price'];

                    if ($productType == 1) {
                        if ($mrp <= 2000) {
                            $courier = 100;
                        } else if ($mrp <= 5000) {
                            $courier = 250;
                        } else if ($mrp <= 10000) {
                            $courier = 500;
                        } else {
                            $courier = 1000;
                        }

                        $lastSellingPrice = 0;
                        $sellingPriceCalculation  = $mrpMinusTotalRent = $mrp - $commissionAmount;
                        $costMinusTotalRent = $cost_price - $commissionAmount;

                        $sellingPriceCalculationPrecentageAmount = $sellingPriceCalculation * 0.4;
                        $sellingPriceCalculation = $sellingPriceCalculation - $sellingPriceCalculationPrecentageAmount;

                        if ($mrp >= 10000) {
                            if ($sellingPriceCalculation < 5000) {
                                $lastSellingPrice = 5000;
                            } else {
                                $lastSellingPrice = $sellingPriceCalculation;
                            }
                        } else if ($mrp < 10000) {
                            if ($sellingPriceCalculation < 3000) {
                                $lastSellingPrice = 3000;
                            } else {
                                $lastSellingPrice = $sellingPriceCalculation;
                            }
                            $lastSellingPrice = $mrp - ($mrp * 0.5);
                        }

                        if (isset($row['sales_price']) && $row['sales_price'] > 0) {
                            $lastSellingPrice = $row['sales_price'];
                        }

                        if ($currentsp > 0) {
                            if ($mrp <= 10000) {
                                $rentprice = $mrp * 0.20;
                                $addedRentPrice = $courier + $rentprice;
                                $deposit = $mrp * 0.35;
                            } else {
                                if ($currentsp <= 40000) {
                                    $rentprice = $currentsp * 0.20;
                                } else if ($currentsp <= 60000) {
                                    $rentprice = $currentsp * 0.17;
                                } else {
                                    $rentprice = $currentsp * 0.15;
                                }
                                $addedRentPrice = $courier + $rentprice;
                                if ($addedRentPrice < 3000) {
                                    $addedRentPrice = 3000;
                                }
                                $deposit = $currentsp * 0.35;
                                if ($deposit < 3000) {
                                    $deposit = 3000;
                                }
                            }
                        } else {
                            if ($mrp <= 10000) {
                                $rentprice = $mrp * 0.20;
                                $addedRentPrice = $courier + $rentprice;
                                $deposit = $mrp * 0.35;
                            } else {
                                $deposit = 3000;
                                $addedRentPrice = 3000;
                            }
                        }
                    } else if ($productType == 2) {
                        $lastSellingPrice = 0;
                        $sellingPriceCalculation = $mrpMinusTotalRent = $mrp - $commissionAmount;

                        $costMinusTotalRent = $cost_price - $commissionAmount;

                        $sellingPriceCalculationPrecentageAmount = $sellingPriceCalculation * 0.4;
                        $sellingPriceCalculation = $sellingPriceCalculation - $sellingPriceCalculationPrecentageAmount;

                        if ($mrp >= 10000) {
                            if ($sellingPriceCalculation < 5000) {
                                $lastSellingPrice = 5000;
                            } else {
                                $lastSellingPrice = $sellingPriceCalculation;
                            }
                        } else if ($mrp < 10000) {
                            if ($sellingPriceCalculation < 3000) {
                                $lastSellingPrice = 3000;
                            } else {
                                $lastSellingPrice = $sellingPriceCalculation;
                                $lastSellingPrice = $mrp - ($mrp * 0.5);
                            }
                        }

                        if (isset($row['sales_price']) && $row['sales_price'] > 0) {
                            $lastSellingPrice = $row['sales_price'];
                        }

                        if ($currentsp > 0) {
                            if ($mrp <= 10000) {
                                $courier = 1000;
                                $rentprice = $mrp * 0.20;
                                $addedRentPrice = $courier + $rentprice;
                                $deposit = $mrp * 0.35;
                            } else {
                                $courier = 2000;
                                if ($currentsp <= 40000) {
                                    $rentprice = $currentsp * 0.20;
                                } else if ($currentsp <= 60000) {
                                    $rentprice = $currentsp * 0.17;
                                } else {
                                    $rentprice = $currentsp * 0.15;
                                }
                                $addedRentPrice = $courier + $rentprice;
                                if ($addedRentPrice < 3000) {
                                    $addedRentPrice = 3000;
                                }

                                $deposit = $currentsp * 0.35;
                                if ($deposit < 3000) {
                                    $deposit = 3000;
                                }
                            }
                        } else {
                            if ($mrp <= 10000) {
                                $courier = 1000;
                                $rentprice = $mrp * 0.20;
                                $addedRentPrice = $courier + $rentprice;
                                $deposit = $mrp * 0.35;
                            } else {
                                $deposit = 3000;
                                $addedRentPrice = 3000;
                            }
                        }
                    }

                    $allMrpMinusTotalRent[] = $mrpMinusTotalRent;
                    $allsku[] = $skuval;
                    $allmrp[] = $mrp;
                    $allCommissionAmount[] = $commissionAmount;

                    $deposit =  round_amount($deposit);

                    $addedRentPrice = round_amount($addedRentPrice);
                    $mrpMinusCostPrice = $mrp - $cost_price;
                    
                    
                    
                    // Get Bookings
                    $order_sql = mysqli_query($con,"SELECT * FROM `order_detail` where `item_id`='".$skuval."' and bill_id in(SELECT bill_id  FROM `phppos_rent` WHERE (`pick_date` >='".$todaysdt."' or `delivery_date` >='".$todaysdt."') and booking_status!='Returned' ORDER BY `phppos_rent`.`pick_date` ASC) group by bill_id");
        
        
        if($totalBookings = mysqli_num_rows($order_sql)){
            
            if($totalBookings>1){
                        $booking_date =' <span style="color:red;"> '. $totalBookings .' Bookings</span>' ;
            }else{
        $booking_date =' <span style="color:red;"> '. $totalBookings .' Booking</span>' ;                
            }

        
        while($order_sql_result = mysqli_fetch_assoc($order_sql)){
            $booking_billid = $order_sql_result['bill_id'];
            
            $booking_sql = mysqli_query($con,"select * from phppos_rent where bill_id ='".$booking_billid."'") ;
            $booking_sql_result = mysqli_fetch_assoc($booking_sql);
            
            $pick_date = $booking_sql_result['pick_date'];
            $delivery_date = $booking_sql_result['delivery_date'];
            $booking_status = $booking_sql_result['booking_status'];
            
            if($pick_date!='' && $delivery_date!='' && $booking_status !='Returned') {

                  if($pick_date!="0000-00-00" && $delivery_date!="0000-00-00"){
                    $booking_date .= '<div>'  .  date("d-m-Y", strtotime($pick_date)) .' <b>To</b> '. date("d-m-Y", strtotime($delivery_date)) . '</div>';
                  }      
            }
            else{
                $booking_date .='' ;
            }
        }
        }else{
                $booking_date ='No Bookings !' ;
            }
        
                    
                    
                    
                    
                    
                    
                    
                ?>
                    <tr>
                        <td class="no-print" style="text-align: center; "><span class="removethis" style="cursor:pointer;"> 
                        <i class="material-icons" style="font-size:24px;color:red" title="Remove This Product">delete</i>
                        </span></td>
                        
                        <td style="text-align: center; "><?php echo $skuval; ?></td>
                        <td style="text-align: center; " contenteditable="true" class="rentPrice" data-initial="<?php echo $addedRentPrice; ?>"><?php echo $addedRentPrice; ?></td>
                        <td style="text-align: center; " contenteditable="true" class="sellingPrice" data-initial="<?php echo $lastSellingPrice; ?>"><?php echo $lastSellingPrice; ?></td>
                        <td style="text-align: center; " class="mrpPrice" data-initial="<?php echo $mrp; ?>"><?php echo $mrp; ?></td>
                        <td style="text-align: center; " class="no-print" data-initial="<?php echo $cost_price; ?>"><?php echo $cost_price; ?></td>
                        <td style="text-align: center; " contenteditable="true" class="discount">0</td>
                        <td style="text-align: center; " class="discountedSellingPrice" data-initial="<?php echo $mrp; ?>"><?php echo $mrp; ?></td>
                        <td style="text-align: center; " contenteditable="true" class="deposit"><?php echo $deposit; ?></td>
                        <td style="text-align: center; " class="no-print"><?php echo $commissionAmount; ?></td>
                        <td style="text-align: center; " class="no-print"><?php echo $mrpMinusTotalRent; ?></td>
                        <td style="text-align: center; " class="no-print"><?php echo $costMinusTotalRent; ?></td>
                        <td style="text-align: center; " class="no-print"><?php echo $mrpMinusCostPrice; ?></td>
                        <td style="text-align: center;     white-space: nowrap; " class="no-print"><?php echo $booking_date; ?></td>
                        
                    </tr>
                <?php
                    $totalrent += $addedRentPrice;
                    $totalselling += $lastSellingPrice;
                    $totalmrp += $mrp;
                    $totalCostPrice += $cost_price ; 
                    $totalDeposits += $deposit;
                    $totalmrpMinusCostPrice += $mrpMinusCostPrice;
                    $totalcostMinusTotalRent += $costMinusTotalRent;
                    $totalmrpMinusTotalRent += $mrpMinusTotalRent;
                    $totalCommissionAmount += $commissionAmount;
                }
                ?>
                <tr class="table-info">
                    <th class="no-print"></th>
                    <th>Total</th>
                    <th id="totalRent"><?php echo number_format($totalrent, 2); ?></th>
                    <th id="totalSelling"><?php echo number_format($totalselling, 2); ?></th>
                    <th id="totalMrp"><?php echo number_format($totalmrp, 2); ?></th>
                    <th class="no-print" id="totalCostPrice"><?php echo number_format($totalCostPrice, 2); ?></th>
                    <th></th>
                    <th id="totalDiscountedSellingPrice">0.00</th>
                    <th id="totalDeposit"><?php echo $totalDeposits; ?></th>
                    <th class="no-print"><?php echo $totalCommissionAmount; ?></th>
                    <th class="no-print"><?php echo $totalmrpMinusTotalRent; ?></th>
                    <th class="no-print"><?php echo $totalcostMinusTotalRent; ?></th>
                    <th class="no-print"><?php echo $totalmrpMinusCostPrice; ?></th>
                    <th class="no-print"></th>
                </tr>
            </tbody>
        </table>
        
        
         <div class="text-center mt-4">
<!--redirect-->
            <button class="btn btn-primary "  onclick="printTable()">Print</button>
        </div>



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







            <br />
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const totalRentCell = document.getElementById('totalRent');
                    const totalSellingCell = document.getElementById('totalSelling');
                    const totalMrpCell = document.getElementById('totalMrp');
                    const totalDiscountedSellingPriceCell = document.getElementById('totalDiscountedSellingPrice');
                    const totalDepositCell = document.getElementById('totalDeposit');

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
                        let totalDeposit = 0;

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

                        depositCells.forEach(cell => {
                            const value = parseFloat(cell.textContent) || 0;
                            totalDeposit += value;
                        });


                        totalRentCell.textContent = totalRent.toFixed(2);
                        totalSellingCell.textContent = totalSelling.toFixed(2);
                        totalMrpCell.textContent = totalMrp.toFixed(2);
                        totalDiscountedSellingPriceCell.textContent = totalDiscountedSellingPrice.toFixed(2); // Update total discounted selling price
                        totalDepositCell.textContent = totalDeposit.toFixed(2); // Update total discounted selling price
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
                    depositCells.forEach(cell => {
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
                    element.addEventListener("click", function() {
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
                
                
               document.querySelector(".redirect").addEventListener("click", function() {
                    let currentUrl = new URL(window.location.href);
                    let newUrl = currentUrl.href.replace("viewprerentedprice.php", "new_viewprerentedprice_print.php");
                    window.location.href = newUrl;
                });
                
                
function printTable() {
    // Get the table and remove columns with the "removethis" class
    const table = document.getElementById('printableDiv');
    const removeElements = table.querySelectorAll('.no-print');

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
            </script>



        </div>


    </div>

</div>    
</div>

                    
                 
                    
                    
                    
                    
                    
                    
                    
                    
                	    </div>
                	
                	 <?php include('../footer.php');?>
                  </div>

    </div>

</div>

<script src="../vendors/js/vendor.bundle.base.js">
</script>
<script src="../vendors/js/vendor.bundle.addons.js">
</script>

<script src="../js/off-canvas.js">
</script>
<script src="../js/hoverable-collapse.js">
</script>
<script src="../js/misc.js">
</script>
<script src="../js/settings.js">
</script>
<script src="../js/todolist.js"></script>

<script src="../js/data-table.js"></script>
<script src="../js/data-table2.js"></script>
<script src="../js/select2.js"></script>
            
</body>
</html>




