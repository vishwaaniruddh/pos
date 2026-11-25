<?php 
require 'vendor/autoload.php';
include('db_connection.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$con = OpenSrishringarrCon();
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Get filters from parent page
$bill_id = $_GET['bill_id'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$sku = $_GET['sku'] ?? '';
$company_name = $_GET['company_name'] ?? '';

// Build SQL Query with Filters
$where_conditions = ["1=1"];

if (!empty($bill_id)) { 
    $where_conditions[] = "pr.bill_id = '" . mysqli_real_escape_string($con, $bill_id) . "'"; 
}
if (!empty($from_date)) { 
    $where_conditions[] = "pr.bill_date >= '" . mysqli_real_escape_string($con, $from_date) . "'"; 
}
if (!empty($to_date)) { 
    $where_conditions[] = "pr.bill_date <= '" . mysqli_real_escape_string($con, $to_date) . "'"; 
}
if (!empty($sku)) { 
    $escaped_sku = mysqli_real_escape_string($con, $sku);
    $where_conditions[] = "pr.bill_id IN (SELECT bill_id FROM approval_detail WHERE item_id LIKE '%$escaped_sku%')"; 
}
if (!empty($company_name)) { 
    $escaped_company = mysqli_real_escape_string($con, $company_name);
    $where_conditions[] = "pr.company_name LIKE '%$escaped_company%'"; 
}

$where_sql = "WHERE " . implode(" AND ", $where_conditions);

// Fetch Parent Records
$sales_sql = mysqli_query($con, "
    SELECT pr.bill_id, pr.bill_date, pr.paid_amount, pr.new_bill_number,
           IFNULL(CONCAT(pp.first_name, ' ', pp.last_name), '-') AS customer_name,
           pr.card_perc, pr.pay_by
    FROM approval pr   
    LEFT JOIN phppos_people pp ON pr.cust_id = pp.person_id
    $where_sql
    ORDER BY pr.bill_id DESC
");

// Set column headers
$headers = ['Bill Date', 'Invoice No', 'Customer Name', 'Bill Amount', 'GST No', 'Product Type', 'SKU', 'Quantity', 'Amount', 'Total Taxable', 'GST Rate', 'CGST', 'SGST', 'Total GST', 'Total Amount', 'Payment Mode', 'Payment Amount'];
$sheet->fromArray([$headers], NULL, 'A1');

// Style headers
$sheet->getStyle('A1:Q1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']]
]);

$row = 2;

while ($bill = mysqli_fetch_assoc($sales_sql)) {
    $bill_id = $bill['bill_id'];
    $bill_date = $bill['bill_date'];
    $customer_name = ucwords(strtolower($bill['customer_name']));
    $paid_amount = $bill['paid_amount'];
    $new_bill_number = $bill['new_bill_number'];
    $new_bill_id = ($new_bill_number ? $new_bill_number : $bill_id);

    $default_mode = !empty($bill['pay_by']) && trim($bill['pay_by']) !== '' && trim($bill['pay_by']) !== '0'
        ? ucfirst(trim($bill['pay_by']))
        : (($bill['card_perc'] == 1) ? 'Cheque' : (($bill['card_perc'] == 2) ? 'Card' : 'Cash'));

    // Lookup payment records from paid_amount table
    $pa_conds = ["(bid = '$bill_id' AND bid > 0)"];
    if (!empty($new_bill_number)) {
        $escaped_nbn = mysqli_real_escape_string($con, $new_bill_number);
        $pa_conds[] = "new_bill_number = '$escaped_nbn'";
    }
    $payment_mode_sql = mysqli_query($con, "SELECT payment_by, amount FROM paid_amount WHERE (" . implode(" OR ", $pa_conds) . ") ORDER BY id ASC");

    $payment_modes = [];
    $payment_amounts = [];
    while ($payment_row = mysqli_fetch_assoc($payment_mode_sql)) {
        $p_mode = !empty($payment_row['payment_by']) && trim($payment_row['payment_by']) !== ''
            ? ucfirst(trim($payment_row['payment_by']))
            : $default_mode;

        $payment_modes[] = $p_mode;
        if (isset($payment_row['amount']) && $payment_row['amount'] !== '') {
            $amt_val = floatval($payment_row['amount']);
            $formatted_amt = ($amt_val == intval($amt_val)) ? number_format($amt_val, 0) : number_format($amt_val, 2);
            $payment_amounts[] = '₹ ' . $formatted_amt;
        }
    }

    // Fallback to approval table if no entries found in paid_amount
    if (empty($payment_modes) && empty($payment_amounts)) {
        if (!empty($bill['paid_amount']) && floatval($bill['paid_amount']) > 0) {
            $payment_modes[] = $default_mode;
            $amt_val = floatval($bill['paid_amount']);
            $formatted_amt = ($amt_val == intval($amt_val)) ? number_format($amt_val, 0) : number_format($amt_val, 2);
            $payment_amounts[] = '₹ ' . $formatted_amt;
        } elseif (!empty($bill['pay_by']) && trim($bill['pay_by']) !== '' && trim($bill['pay_by']) !== '0') {
            $payment_modes[] = ucfirst(trim($bill['pay_by']));
            $payment_amounts[] = '₹ 0';
        }
    }

    $payment_by_mode_str = !empty($payment_modes) ? implode(', ', $payment_modes) : "NA";
    $payment_amount_str = !empty($payment_amounts) ? implode(', ', $payment_amounts) : "NA";

    // Fetch Child Records (Approval Details)
    $order_query = "
        SELECT od.item_id AS sku, pi.category_type, od.qty, od.amount AS single_rent
        FROM approval_detail od
        INNER JOIN phppos_items pi ON od.item_id = pi.name
        WHERE od.bill_id = '$bill_id'
    ";
    if (!empty($sku)) {
        $escaped_sku = mysqli_real_escape_string($con, $sku);
        $order_query .= " AND od.item_id LIKE '%$escaped_sku%'";
    }

    $order_details_sql = mysqli_query($con, $order_query);

    $orderDetails = [];
    $totalGST = 0;
    $totalCGST = 0;
    $totalSGST = 0;

    while ($order = mysqli_fetch_assoc($order_details_sql)) {
        $category_type = ($order['category_type'] == 1) ? 'Jewellery' : 'Apparel';
        $qty = $order['qty'];
        $single_rent = $order['single_rent'];
        $total_rent = $single_rent * $qty;

        if ($order['category_type'] == 1) {
            $gst_rate = 3;
            $thisProductTotalTaxable = $single_rent / 1.03;
            $thisProductTotalGst = $thisProductTotalTaxable * 0.03;
            $cgst = $sgst = $thisProductTotalGst / 2;
        } else {
            if (strtotime($bill_date) < strtotime('2025-09-22')) {
                $gst_rate = 12;
                $thisProductTotalTaxable = $single_rent / 1.12;
                $thisProductTotalGst = $thisProductTotalTaxable * 0.12;
                $cgst = $sgst = $thisProductTotalGst / 2;
            } else {
                if ($single_rent > 2500) {
                    $gst_rate = 18;
                    $thisProductTotalTaxable = $single_rent / 1.18;
                    $thisProductTotalGst = $thisProductTotalTaxable * 0.18;
                    $cgst = $sgst = $thisProductTotalGst / 2;
                } else {
                    $gst_rate = 5;
                    $thisProductTotalTaxable = $single_rent / 1.05;
                    $thisProductTotalGst = $thisProductTotalTaxable * 0.05;
                    $cgst = $sgst = $thisProductTotalGst / 2;
                }
            }
        }

        $single_taxable = $single_rent - ($cgst + $sgst);
        $total_taxable = $single_taxable * $qty;
        $totalCGST += $cgst;
        $totalSGST += $sgst;
        $totalGST += ($cgst + $sgst);

        $orderDetails[] = [
            $category_type,
            $order['sku'],
            $qty,
            round($single_rent, 2),
            round($total_taxable, 2),
            $gst_rate . '%',
            round($cgst, 2),
            round($sgst, 2),
            round($thisProductTotalGst, 2),
            $total_rent,
            '-',
            '-'
        ];
    }

    $formatted_bill_date = date('d-m-Y', strtotime($bill_date));

    // Write Parent Row (Highlighted)
    $sheet->fromArray([[$formatted_bill_date, $new_bill_id, $customer_name, $paid_amount, '-', '-', '-', '-', '-', '-', '-', round($totalCGST, 2), round($totalSGST, 2), round($totalGST, 2), $paid_amount, $payment_by_mode_str, $payment_amount_str]], NULL, "A$row");
    $sheet->getStyle("A$row:Q$row")->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF99']]
    ]);
    $row++;

    // Write Child Rows
    foreach ($orderDetails as $order) {
        $sheet->fromArray([['-', '-', '-', '-', '-', ...$order]], NULL, "A$row");
        $row++;
    }
}

// Set column width automatically
foreach (range('A', 'Q') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output Excel File
$filename = "sales_orders_" . date('Y-m-d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
