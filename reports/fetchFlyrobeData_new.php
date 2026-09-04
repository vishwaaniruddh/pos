<?php 
include('../db_connection.php');
$con = OpenSrishringarrCon();
$conn = $con; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set current month and year by default
$current_month = date('M');
$current_year = date('Y');

$selectFrachise = $_REQUEST['selectFrachise'];

$selected_month = isset($_REQUEST['month']) ? $_REQUEST['month'] : $current_month;
$selected_year = isset($_REQUEST['year']) ? $_REQUEST['year'] : $current_year;

// Prepare SQL query with filtering and join
$sql = "
SELECT a.*, r.bill_date,r.cust_id,r.pick_date,r.delivery_date, r.new_bill_number
FROM flyrob_commission a
JOIN phppos_rent r ON a.purchase_id = r.bill_id
WHERE a.status = 'Visible'";

if ($selected_month && $selected_year) {
    $sql .= " AND MONTH(r.bill_date) = MONTH(STR_TO_DATE('$selected_month', '%b')) AND YEAR(r.bill_date) = '$selected_year'";
} elseif ($selected_month) {
    $sql .= " AND MONTH(r.bill_date) = MONTH(STR_TO_DATE('$selected_month', '%b'))";
} elseif ($selected_year) {
    $sql .= " AND YEAR(r.bill_date) = '$selected_year'";
}


if($selectFrachise==2){
    $sql .= " AND a.isFlyrobeProduct in(0,1) ";
}else if($selectFrachise==1){
        $sql .= " AND a.isFlyrobeProduct in(1) ";
}else if($selectFrachise==0){
        $sql .= " AND a.isFlyrobeProduct in(0) ";
}


$sql .= " ORDER BY a.id DESC";
$query = mysqli_query($con, $sql);

$totals = [
    'rentAmount' => 0,
    'beauticianDiscountAmount' => 0,
    'netAmount' => 0,
    'flyrobeCommissionAmount' => 0,
    'thisProductTotalGst'=>0,
    'thisssfsAmount' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_REQUEST['export'])) {
    ob_start(); // Start output buffering
    ?>
    <thead>
        <tr>
            <th>Sr No.</th>
            <th>Bill Date</th>
            <th>Product Type</th>
            <th>SKU</th>
            <th>Invoice No</th>
            <th>Customer Name</th>
            <th>Pick-up Date</th>
            <th>Return Date</th>
            <th>Rent Amount</th>
            <th>GST</th>
            <th>GST Amount</th>
            <th>Beautician Discount</th>
            <th>Beautician Discount Rate</th>
            <th>Beautician Discount Amount</th>
            <th class="tooltip">Net Amount
                <span class="tooltiptext">Rent - GST - Beautician</span>
            </th>
            <th>Is Flyrobe Product</th>
            <th>Flyrobe Commission</th>
            <th>Flyrobe Commission Amount</th>
            <th>SSFS</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (mysqli_num_rows($query) > 0) {
            $srno = 1;
            while ($row = mysqli_fetch_assoc($query)) {
                $billDate = $row['bill_date'];
                $thisProductTotalTaxable = $row["totalProductAmount"] / ($row["productType"] == 2 ? 1.12 : 1.03);
                $thisProductTotalGst = round($thisProductTotalTaxable * ($row["productType"] == 2 ? 0.12 : 0.03), 2);
                $ssfs = $row["netAmount"] - $row["commision_amount"];
                $new_bill_number = ($row['new_bill_number'] ?  $row['new_bill_number'] : $row["purchase_id"]);
                
                $cust_id = $row['cust_id'];
                
                $peoplesql = mysqli_query($con,"select first_name,last_name from phppos_people where person_id='".$cust_id."'");
                $peoplesqlResult = mysqli_fetch_assoc($peoplesql);
                
                $customerName = $peoplesqlResult['first_name'] . ' ' . $peoplesqlResult['last_name'];
                
                $pick_date = date('d-m-Y',strtotime($row['pick_date']));
                
                $delivery_date = date('d-m-Y',strtotime($row['delivery_date']));
                

                $totals['rentAmount'] += $row["totalProductAmount"];
                $totals['beauticianDiscountAmount'] += $row["beautician_discountAmount"];
                $totals['netAmount'] += $row["netAmount"];
                $totals['flyrobeCommissionAmount'] += $row["commision_amount"];
                $totals['thisProductTotalGst'] += $thisProductTotalGst;
                $totals['thisssfsAmount'] += $ssfs;
                

               echo '
<tr>
    <td>' . $srno . '</td>
    <td style="white-space:nowrap;">' . date('d-m-Y', strtotime($billDate)) . '</td>
    <td>' . ($row["productType"] == 2 ? 'Apparel' : 'Jewellery') . '</td>
    <td style="white-space:nowrap;">' . $row["sku"] . '</td>
    
    <td style="white-space:nowrap;"><a href="./rent_report_detail.php?id=' . $row["purchase_id"] . '" target="_blank">' . $new_bill_number . '</a></td>
    
    <td style="white-space:nowrap;">'.$customerName.'</td>
    <td style="white-space:nowrap;">'.$pick_date.'</td>
    <td style="white-space:nowrap;">'.$delivery_date.'</td>
    <td>' . number_format($row["totalProductAmount"]) . '</td>
    <td>' . ($row["productType"] == 2 ? '12%' : '3%') . '</td>
    <td>' . number_format($thisProductTotalGst, 2) . '</td>
    <td>' . ($row["isBeauticianDiscountGiven"] == 1 ? 'Yes' : 'No') . '</td>
    <td>' . ($row["beautician_discountRate"] ? $row["beautician_discountRate"] . $row["beautician_discountType"] : '') . '</td>
    <td>' . number_format($row["beautician_discountAmount"], 2) . '</td>
    <td>' . number_format($row["netAmount"], 2) . '</td>
    <td>' . ($row["isFlyrobeProduct"] == 1 ? 'Yes' : 'No') . '</td>
    <td>' . $row["commision"] . '</td>
    <td>' . number_format($row["commision_amount"], 2) . '</td>
    <td>' . number_format($ssfs, 2) . '</td>
</tr>';

                $srno++;
            }

            // Add totals row
            echo "
            <tfoot>
                <tr>
                    <th colspan='8'>Total:</th>
                    <th>" . number_format($totals['rentAmount']) . "</th>
                    <th></th>
                    <th>" . number_format($totals['thisProductTotalGst'], 2) . "</th>
                    <th></th>
                    <th></th>
                    <th>" . number_format($totals['beauticianDiscountAmount'], 2) . "</th>
                    <th>" . number_format($totals['netAmount'], 2) . "</th>
                    <th></th>
                    <th></th>
                    <th>" . number_format($totals['flyrobeCommissionAmount'], 2) . "</th>
                    <th>" . number_format($totals['thisssfsAmount'], 2) . "</th>
                </tr>
            </tfoot>";
        } else {
            echo "
            <tr>
                <td colspan='16' class='text-center'>No data found</td>
            </tr>";
        }
        ?>
    </tbody>
    <?php
    $output = ob_get_clean(); // Get the buffered output
    echo $output; // Return it as the AJAX response
    exit();
}

if (isset($_REQUEST['export'])) {
    $export_sql = $sql; // Use the same SQL query as before
    $export_query = mysqli_query($con, $export_sql);



    if ($export_query) {
        $filename = "flyrobe_commission_records_" . date('Ymd') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Bill Date', 'Product Type', 'SKU', 'Invoice No','Customer Name','Pick-up Date','Return Date', 'Rent Amount', 'GST', 'GST Amount', 'Beautician Discount', 'Beautician Discount Rate', 'Beautician Discount Amount', 'Net Amount', 'Is Flyrobe Product', 'Flyrobe Commission', 'Flyrobe Commission Amount', 'SSFS']);

        // Initialize totals
        $totals = [
            'rentAmount' => 0,
            'beauticianDiscountAmount' => 0,
            'netAmount' => 0,
            'flyrobeCommissionAmount' => 0,
            'thisProductTotalGst' => 0,
            'thisssfsAmount' => 0,
        ];

        if (mysqli_num_rows($export_query) > 0) {
            while ($row = mysqli_fetch_assoc($export_query)) {
                $billDate = $row['bill_date'];
                $thisProductTotalTaxable = $row["totalProductAmount"] / ($row["productType"] == 2 ? 1.12 : 1.03);
                $thisProductTotalGst = round($thisProductTotalTaxable * ($row["productType"] == 2 ? 0.12 : 0.03), 2);
                $ssfs = $row["netAmount"] - $row["commision_amount"];
                $cust_id = $row['cust_id'];

                $peoplesql = mysqli_query($con,"select first_name,last_name from phppos_people where person_id='".$cust_id."'");
                $peoplesqlResult = mysqli_fetch_assoc($peoplesql);
                $customerName = $peoplesqlResult['first_name'] . ' ' . $peoplesqlResult['last_name'];
                
                
                
                $pick_date = date('d-m-Y',strtotime($row['pick_date']));
                
                $delivery_date = date('d-m-Y',strtotime($row['delivery_date']));
                
                
                
                // Update totals
                $totals['rentAmount'] += $row["totalProductAmount"];
                $totals['beauticianDiscountAmount'] += $row["beautician_discountAmount"];
                $totals['netAmount'] += $row["netAmount"];
                $totals['flyrobeCommissionAmount'] += $row["commision_amount"];
                $totals['thisProductTotalGst'] += $thisProductTotalGst;
                $totals['thisssfsAmount'] += $ssfs;

                fputcsv($output, [
                    date('d-m-Y', strtotime($billDate)),
                    $row["productType"] == 2 ? 'Apparel' : 'Jewellery',
                    $row["sku"],
                    $row["purchase_id"],
                    $customerName,
                    $pick_date,
                    $delivery_date,
                    number_format($row["totalProductAmount"]),
                    $row["productType"] == 2 ? '12%' : '3%',
                    number_format($thisProductTotalGst, 2),
                    $row["isBeauticianDiscountGiven"] == 1 ? 'Yes' : 'No',
                    $row["beautician_discountRate"] ? $row["beautician_discountRate"] . $row["beautician_discountType"] : '',
                    number_format($row["beautician_discountAmount"], 2),
                    number_format($row["netAmount"], 2),
                    $row["isFlyrobeProduct"] == 1 ? 'Yes' : 'No',
                    $row["commision"],
                    number_format($row["commision_amount"], 2),
                    number_format($ssfs, 2)
                ]);
            }

            // Write totals to CSV
            fputcsv($output, []); // Add an empty row before totals
            fputcsv($output, [
                'Total',
                '',
                '',
                '',
                '',
                '',
                '',
                number_format($totals['rentAmount']),
                '',
                number_format($totals['thisProductTotalGst'], 2),
                '',
                '',
                number_format($totals['beauticianDiscountAmount'], 2),
                number_format($totals['netAmount'], 2),
                '',
                '',
                number_format($totals['flyrobeCommissionAmount'], 2),
                number_format($totals['thisssfsAmount'], 2)
            ]);
        } else {
            fputcsv($output, ['No data found']);
        }

        fclose($output);
        exit();
    }

    // Redirect back to the page after export
    header("Location: ./flyrobe.php");
    exit();
}
?>
