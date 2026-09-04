<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();
$conn = $con;

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Set current month and year by default
$current_month = date('M');
$current_year = date('Y');

$selectFrachise = $_REQUEST['selectFrachise'];

$selected_month = isset($_REQUEST['month']) ? $_REQUEST['month'] : $current_month;
$selected_year = isset($_REQUEST['year']) ? $_REQUEST['year'] : $current_year;

// Prepare SQL query with filtering and join
$sql = "
SELECT a.item_id as sku,a.total_amount as totalProductAmount,a.qty,a.rent AS single_rent,
r.throught,r.total_comm, 
r.bill_date,r.cust_id,r.pick_date,r.delivery_date, r.new_bill_number, r.bill_id , r.new_bill_number,
r.comm_amount,
(SELECT COUNT(*) FROM order_detail od WHERE od.bill_id = r.bill_id) AS total_records,
r.comm_by,a.is_new_with_gst

FROM order_detail a
JOIN phppos_rent r ON a.bill_id = r.bill_id
WHERE r.company_name in ('SS','Sri Shringarr')";

if ($selected_month && $selected_year) {
    $sql .= " AND MONTH(r.bill_date) = MONTH(STR_TO_DATE('$selected_month', '%b')) AND YEAR(r.bill_date) = '$selected_year'";
} elseif ($selected_month) {
    $sql .= " AND MONTH(r.bill_date) = MONTH(STR_TO_DATE('$selected_month', '%b'))";
} elseif ($selected_year) {
    $sql .= " AND YEAR(r.bill_date) = '$selected_year'";
}

 $sql .= " ORDER BY a.bill_id DESC";


$query = mysqli_query($con, $sql);

// return ; 


$totals = [
    'rentAmount' => 0,
    'beauticianDiscountAmount' => 0,
    'netAmount' => 0,
    'flyrobeCommissionAmount' => 0,
    'thisProductTotalGst' => 0,
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
            <th>Beautician Commission</th>
            <th>Beautician Commission Rate</th>
            <th>Beautician Commission Amount</th>
            <th class="tooltip">Net Amount
                <span class="tooltiptext">Rent - GST - Beautician</span>
            </th>
            <th>Is Flyrobe Product</th>
            <th>SSFS</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (mysqli_num_rows($query) > 0) {
            $srno = 1;
            while ($row = mysqli_fetch_assoc($query)) {

                $is_new_with_gst = $row['is_new_with_gst'];
                $sku = $row["sku"];
                $billDate = $row['bill_date'];
                $thisProductTotalTaxable = $row["totalProductAmount"] / ($row["productType"] == 2 ? 1.12 : 1.03);
                $thisProductTotalGst = round($thisProductTotalTaxable * ($row["productType"] == 2 ? 0.12 : 0.03), 2);
                $new_bill_number = ($row['new_bill_number'] ? $row['new_bill_number'] : $row["bill_id"]);

                $cust_id = $row['cust_id'];

                $peoplesql = mysqli_query($con, "select first_name,last_name from phppos_people where person_id='" . $cust_id . "'");
                $peoplesqlResult = mysqli_fetch_assoc($peoplesql);

                $customerName = $peoplesqlResult['first_name'] . ' ' . $peoplesqlResult['last_name'];

                $pick_date = date('d-m-Y', strtotime($row['pick_date']));

                $delivery_date = date('d-m-Y', strtotime($row['delivery_date']));



                $total_records = $row['total_records'];

                // $beautician_discountAmount = ($row["comm_amount"] > 0) ? ($row["total_comm"] / $total_records) : 0;




                $single_rent = $row['single_rent'];
                $item_sql = mysqli_query($con, "select * from phppos_items where name like '" . $sku . "'");
                $item_sql_result = mysqli_fetch_assoc($item_sql);

                $supplier_id = $item_sql_result['supplier_id'];
                $isFlyrobeProduct = ($supplier_id == '242875') ? 'Yes' : 'No';

                if ($is_new_with_gst == 1) {

                    if ($item_sql_result['category_type'] == 1) {
                        $thisProductTotalTaxable = $single_rent / 1.03;
                        $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.03;
                        $cgst = $sgst = $thisProductTotalGst / 2;
                    } else if ($item_sql_result['category_type'] == 2) {
                        $thisProductTotalTaxable = $single_rent / 1.12;
                        $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.12;
                        $cgst = $sgst = $thisProductTotalGst / 2;
                    }

                    $gst_rate = ($row["productType"] == 2 ? '12%' : '3%');

                } else {
                    $gst_rate = $cgst = $sgst = $thisProductTotalGst = 0;


                }

                $category_type = ($item_sql_result['category_type'] == 1) ? 'Jewellery' : 'Apparel';


                // 
                $single_taxable = $single_rent - ($cgst + $sgst);
                $total_rent = $single_rent * $qty;
                
                
                $beautician_discountAmount = 0 ;
                if($row["throught"]){
                    if($row["total_comm"] > 0){
                         $beautician_discountAmount = $row["total_comm"] / $total_records ; 
                    }else if($row["total_comm"] == 0 ){
                        if($row['comm_by']=='%'){
                            
                             $beautician_discountAmount = $single_taxable * ($row['comm_amount']/100) ;
                            
                        }else{
                            $beautician_discountAmount = $single_taxable - ($row['comm_amount']) ;
                        }
                    }
                }
                


                $netAmount = $ssfs = $single_taxable - $beautician_discountAmount;

                $comm_by = $row['comm_amount'] . $row['comm_by'];



                $totals['rentAmount'] += $row["totalProductAmount"];
                $totals['beauticianDiscountAmount'] += $beautician_discountAmount;
                $totals['netAmount'] += $ssfs;
                $totals['flyrobeCommissionAmount'] += $row["commision_amount"];
                $totals['thisProductTotalGst'] += $thisProductTotalGst;
                $totals['thisssfsAmount'] += $ssfs;
                echo '
<tr>

    <td>' . $srno . '</td>
    <td style="white-space:nowrap;">' . date('d-m-Y', strtotime($billDate)) . '</td>
    <td>' . $category_type . '</td>
    <td style="white-space:nowrap;">' . $sku . '</td>
    
    <td style="white-space:nowrap;"><a href="./rent_report_detail.php?id=' . $row["bill_id"] . '" target="_blank">' . $new_bill_number . '</a></td>
    
    <td style="white-space:nowrap;">' . $customerName . '</td>
    <td style="white-space:nowrap;">' . $pick_date . '</td>
    <td style="white-space:nowrap;">' . $delivery_date . '</td>
    <td>' . number_format($row["totalProductAmount"]) . '</td>
    <td>' . $gst_rate . '</td>
    <td>' . number_format($thisProductTotalGst, 2) . '</td>
    <td>' . ($row["comm_amount"] > 0 ? 'Yes' : 'No') . '</td>
    <td>' . ($row["comm_amount"] > 0 ? $comm_by : '') . '</td>
    <td>' . number_format($beautician_discountAmount, 2) . '</td>
    <td>' . round($netAmount, 2) . '</td>
    <td>' . $isFlyrobeProduct . '</td>

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
        fputcsv($output, ['Bill Date', 'Product Type', 'SKU', 'Invoice No', 'Customer Name', 'Pick-up Date', 'Return Date', 'Rent Amount', 'GST', 'GST Amount', 'Beautician Discount', 'Beautician Discount Rate', 'Beautician Discount Amount', 'Net Amount', 'Is Flyrobe Product', 'SSFS']);

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

                $peoplesql = mysqli_query($con, "select first_name,last_name from phppos_people where person_id='" . $cust_id . "'");
                $peoplesqlResult = mysqli_fetch_assoc($peoplesql);
                $customerName = $peoplesqlResult['first_name'] . ' ' . $peoplesqlResult['last_name'];



                $pick_date = date('d-m-Y', strtotime($row['pick_date']));

                $delivery_date = date('d-m-Y', strtotime($row['delivery_date']));



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

                number_format($totals['thisssfsAmount'], 2)
            ]);
        } else {
            fputcsv($output, ['No data found']);
        }

        fclose($output);
        exit();
    }

    // Redirect back to the page after export
    header("Location: ./commission.php");
    exit();
}
?>