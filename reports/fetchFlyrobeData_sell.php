<?php 
include('../db_connection.php');
$con = OpenSrishringarrCon();
$conn = $con; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Default to current month/year
$current_month = date('M');
$current_year = date('Y');

$selectFrachise = isset($_REQUEST['selectFrachise']) ? $_REQUEST['selectFrachise'] : null;
$selected_month = isset($_REQUEST['month']) ? $_REQUEST['month'] : $current_month;
$selected_year = isset($_REQUEST['year']) ? $_REQUEST['year'] : $current_year;

// Base SQL
$sql = "
SELECT a.*, r.bill_date, r.cust_id, r.new_bill_number
FROM flyrob_commission_sell a
JOIN approval r ON a.purchase_id = r.bill_id
WHERE a.status = 'Visible'";

// Filters
if ($selected_month && $selected_year) {
    $sql .= " AND MONTH(r.bill_date) = MONTH(STR_TO_DATE('$selected_month', '%b')) AND YEAR(r.bill_date) = '$selected_year'";
} elseif ($selected_month) {
    $sql .= " AND MONTH(r.bill_date) = MONTH(STR_TO_DATE('$selected_month', '%b'))";
} elseif ($selected_year) {
    $sql .= " AND YEAR(r.bill_date) = '$selected_year'";
}

if ($selectFrachise === '2') {
    $sql .= " AND a.isFlyrobeProduct IN (0,1)";
} elseif ($selectFrachise === '1') {
    $sql .= " AND a.isFlyrobeProduct = 1";
} elseif ($selectFrachise === '0') {
    $sql .= " AND a.isFlyrobeProduct = 0";
}

$sql .= " ORDER BY a.id DESC";
$query = mysqli_query($con, $sql);

// Totals
$totals = [
    'rentAmount' => 0,
    'beauticianDiscountAmount' => 0,
    'netAmount' => 0,
    'flyrobeCommissionAmount' => 0,
    'thisProductTotalGst' => 0,
    'thisssfsAmount' => 0
];

// ===================== POST (HTML Table) =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_REQUEST['export'])) {
    ob_start(); ?>
    <thead>
        <tr>
            <th>Sr No.</th>
            <th>Bill Date</th>
            <th>Product Type</th>
            <th>SKU</th>
            <th>Invoice No</th>
            <th>Customer Name</th>
            <th>Rent Amount</th>
            <th>GST</th>
            <th>GST Amount</th>
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
            $productType = isset($row["productType"]) ? (int)$row["productType"] : 2;
            $sku = $row["sku"] ?? '';
            $purchase_id = $row["purchase_id"];
            $totalProductAmount = (float)($row["totalProductAmount"] ?? 0);
            $beautician_discountAmount = (float)($row["beautician_discountAmount"] ?? 0);
            $netAmount = (float)($row["netAmount"] ?? 0);
            $commision_amount = (float)($row["commision_amount"] ?? 0);
            $isFlyrobeProduct = (int)($row["isFlyrobeProduct"] ?? 0);
            $commision = $row["commision"] ?? '';
            $new_bill_number = $row['new_bill_number'] ?: $purchase_id;
            $cust_id = $row['cust_id'];

            // GST Calculation
            $thisProductTotalTaxable = $totalProductAmount / ($productType == 2 ? 1.12 : 1.03);
            $thisProductTotalGst = round($thisProductTotalTaxable * ($productType == 2 ? 0.12 : 0.03), 2);
            $ssfs = $netAmount - $commision_amount;

            // Customer Name
            $customerName = '';
            $peoplesql = mysqli_query($con, "SELECT first_name, last_name FROM phppos_people WHERE person_id = '$cust_id'");
            if ($peoplesqlResult = mysqli_fetch_assoc($peoplesql)) {
                $customerName = $peoplesqlResult['first_name'] . ' ' . $peoplesqlResult['last_name'];
            }

            // Totals
            $totals['rentAmount'] += $totalProductAmount;
            $totals['beauticianDiscountAmount'] += $beautician_discountAmount;
            $totals['netAmount'] += $netAmount;
            $totals['flyrobeCommissionAmount'] += $commision_amount;
            $totals['thisProductTotalGst'] += $thisProductTotalGst;
            $totals['thisssfsAmount'] += $ssfs;

            echo "<tr>
                <td>{$srno}</td>
                <td style='white-space:nowrap;'>" . date('d-m-Y', strtotime($billDate)) . "</td>
                <td>" . ($productType == 2 ? 'Apparel' : 'Jewellery') . "</td>
                <td style='white-space:nowrap;'>{$sku}</td>
                <td style='white-space:nowrap;'><a href='./sales_report_detail.php?id={$purchase_id}' target='_blank'>{$new_bill_number}</a></td>
                <td style='white-space:nowrap;'>{$customerName}</td>
                <td>" . number_format($totalProductAmount) . "</td>
                <td>" . ($productType == 2 ? '12%' : '3%') . "</td>
                <td>" . number_format($thisProductTotalGst, 2) . "</td>
                <td>" . number_format($netAmount, 2) . "</td>
                <td>" . ($isFlyrobeProduct == 1 ? 'Yes' : 'No') . "</td>
                <td>{$commision}</td>
                <td>" . number_format($commision_amount, 2) . "</td>
                <td>" . number_format($ssfs, 2) . "</td>
            </tr>";
            $srno++;
        }

        // Totals Row
        echo "<tfoot><tr>
            <th colspan='6'>Total:</th>
            <th>" . number_format($totals['rentAmount']) . "</th>
            <th></th>
            <th>" . number_format($totals['thisProductTotalGst'], 2) . "</th>
            <th>" . number_format($totals['netAmount'], 2) . "</th>
            <th></th><th></th>
            <th>" . number_format($totals['flyrobeCommissionAmount'], 2) . "</th>
            <th>" . number_format($totals['thisssfsAmount'], 2) . "</th>
        </tr></tfoot>";
    } else {
        echo "<tr><td colspan='14' class='text-center'>No data found</td></tr>";
    }
    echo '</tbody>';
    echo ob_get_clean();
    exit();
}

// ===================== EXPORT (CSV) =====================
if (isset($_REQUEST['export'])) {
    $export_query = mysqli_query($con, $sql);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="flyrobe_commission_records_' . date('Ymd') . '.csv"');
    $output = fopen('php://output', 'w');

    fputcsv($output, ['Bill Date', 'Product Type', 'SKU', 'Invoice No','Customer Name', 'Rent Amount', 'GST', 'GST Amount', 'Beautician Discount', 'Beautician Discount Rate', 'Beautician Discount Amount', 'Net Amount', 'Is Flyrobe Product', 'Flyrobe Commission', 'Flyrobe Commission Amount', 'SSFS']);

    if (mysqli_num_rows($export_query) > 0) {
        while ($row = mysqli_fetch_assoc($export_query)) {
            $productType = (int)($row["productType"] ?? 2);
            $totalProductAmount = (float)($row["totalProductAmount"] ?? 0);
            $beautician_discountAmount = (float)($row["beautician_discountAmount"] ?? 0);
            $netAmount = (float)($row["netAmount"] ?? 0);
            $commision_amount = (float)($row["commision_amount"] ?? 0);
            $billDate = $row["bill_date"];
            $ssfs = $netAmount - $commision_amount;
            $gstAmount = round(($totalProductAmount / ($productType == 2 ? 1.12 : 1.03)) * ($productType == 2 ? 0.12 : 0.03), 2);

            $cust_id = $row['cust_id'];
            $customerName = '';
            $peoplesql = mysqli_query($con, "SELECT first_name, last_name FROM phppos_people WHERE person_id = '$cust_id'");
            if ($peoplesqlResult = mysqli_fetch_assoc($peoplesql)) {
                $customerName = $peoplesqlResult['first_name'] . ' ' . $peoplesqlResult['last_name'];
            }

            fputcsv($output, [
                date('d-m-Y', strtotime($billDate)),
                $productType == 2 ? 'Apparel' : 'Jewellery',
                $row["sku"] ?? '',
                $row["purchase_id"],
                $customerName,
                number_format($totalProductAmount),
                $productType == 2 ? '12%' : '3%',
                number_format($gstAmount, 2),
                $row["isBeauticianDiscountGiven"] == 1 ? 'Yes' : 'No',
                $row["beautician_discountRate"] . ($row["beautician_discountType"] ?? ''),
                number_format($beautician_discountAmount, 2),
                number_format($netAmount, 2),
                $row["isFlyrobeProduct"] == 1 ? 'Yes' : 'No',
                $row["commision"] ?? '',
                number_format($commision_amount, 2),
                number_format($ssfs, 2)
            ]);
        }
    } else {
        fputcsv($output, ['No data found']);
    }

    fclose($output);
    exit();
}
?>
