<?php 
require 'vendor/autoload.php';
include('db_connection.php');

ini_set('display_errors', 0);
error_reporting(E_ALL);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$con = null;
if (function_exists('OpenSrishringarrCon')) {
    $con = OpenSrishringarrCon();
}
if (!$con) {
    $is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);
    if ($is_local) {
        $con = @mysqli_connect("localhost", "root", "", "u464193275_srishringarr");
    } else {
        $con = @mysqli_connect("localhost", "u464193275_sarmicropos", "Mypos1234", "u464193275_srishringarr");
    }
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Get filters from parent page
$bill_id      = isset($_GET['bill_id']) ? trim($_GET['bill_id']) : '';
$from_date    = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date      = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$sku          = isset($_GET['sku']) ? trim($_GET['sku']) : '';
$company_name = isset($_GET['company_name']) ? trim($_GET['company_name']) : '';

// Build SQL Query with Filters
$where_conditions = ["1=1"];

if (!empty($bill_id)) {
    $safe_bill = mysqli_real_escape_string($con, $bill_id);
    $where_conditions[] = "(pr.bill_id = '$safe_bill' OR pr.new_bill_number LIKE '%$safe_bill%')";
}

if (!empty($from_date) && !empty($to_date)) {
    $safe_from = mysqli_real_escape_string($con, $from_date);
    $safe_to = mysqli_real_escape_string($con, $to_date);
    $where_conditions[] = "pr.bill_date BETWEEN '$safe_from' AND '$safe_to'";
} elseif (!empty($from_date)) {
    $safe_from = mysqli_real_escape_string($con, $from_date);
    $where_conditions[] = "pr.bill_date >= '$safe_from'";
} elseif (!empty($to_date)) {
    $safe_to = mysqli_real_escape_string($con, $to_date);
    $where_conditions[] = "pr.bill_date <= '$safe_to'";
}

if (!empty($sku)) {
    $safe_sku = mysqli_real_escape_string($con, $sku);
    $where_conditions[] = "pr.bill_id IN (SELECT bill_id FROM approval_detail WHERE item_id LIKE '%$safe_sku%')";
}

if (!empty($company_name)) {
    $safe_company = mysqli_real_escape_string($con, $company_name);
    $where_conditions[] = "pr.company_name LIKE '%$safe_company%'";
}

$where_sql = implode(" AND ", $where_conditions);

// Fetch Parent Records
$sales_sql = mysqli_query($con, "
    SELECT pr.bill_id, pr.bill_date, pr.paid_amount, pr.new_bill_number, pr.company_name,
           IFNULL(CONCAT(pp.first_name, ' ', pp.last_name), '-') AS customer_name,
           pr.card_perc
    FROM approval pr   
    LEFT JOIN phppos_people pp ON pr.cust_id = pp.person_id
    WHERE $where_sql
    ORDER BY pr.bill_id DESC
");

// Set column headers
$headers = ['Bill Date', 'Invoice No', 'Customer Name', 'Bill Amount', 'GST No', 'Product Type', 'SKU', 'Quantity', 'Amount', 'Total Taxable', 'GST Rate', 'CGST', 'SGST', 'Total GST', 'Total Amount', 'Payment Mode', 'Payment Amount'];
$sheet->fromArray([$headers], NULL, 'A1');

// Style headers
$sheet->getStyle('A1:Q1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']]
]);

$row = 2;

if ($sales_sql) {
    while ($bill = mysqli_fetch_assoc($sales_sql)) {
        $bill_id = $bill['bill_id'];
        $bill_date = $bill['bill_date'];
        $customer_name = ucwords(strtolower($bill['customer_name']));
        $paid_amount = (float)$bill['paid_amount'];
        $totalGST = 0;
        $totalCGST = 0;
        $totalSGST = 0;

        $new_bill_number = $bill['new_bill_number'];
        $new_bill_id = ($new_bill_number ? $new_bill_number : $bill_id);

        // Fetch Payment Details
        $payment_by_mode_ar = [];
        $payment_amount_ar = [];

        $payment_mode_sql = mysqli_query($con, "SELECT payment_by, amount FROM paid_amount WHERE bill_id = '$bill_id'");
        if ($payment_mode_sql) {
            while ($payment_mode = mysqli_fetch_assoc($payment_mode_sql)) {
                $payment_by_mode_ar[] = $payment_mode['payment_by'];
                $payment_amount_ar[] = $payment_mode['amount'];
            }
        }

        $payment_by_mode_str = !empty($payment_by_mode_ar) ? implode(', ', $payment_by_mode_ar) : "NA";
        $payment_amount_str = !empty($payment_amount_ar) ? implode(', ', $payment_amount_ar) : "NA";

        // Fetch Child Records (Approval Details)
        $order_query = "
            SELECT od.item_id AS sku, pi.category_type, od.qty, od.amount AS total_single_rent
            FROM approval_detail od
            INNER JOIN phppos_items pi ON od.item_id = pi.name
            WHERE od.bill_id = '$bill_id'
        ";

        if (!empty($sku)) {
            $safe_sku_child = mysqli_real_escape_string($con, $sku);
            $order_query .= " AND od.item_id LIKE '%$safe_sku_child%'";
        }

        $order_details_sql = mysqli_query($con, $order_query);
        $orderDetails = [];

        if ($order_details_sql) {
            while ($order = mysqli_fetch_assoc($order_details_sql)) {
                $category_type = ($order['category_type'] == 1) ? 'Jewellery' : 'Apparel';
                $qty = max(1, (int)$order['qty']);
                $single_rent = (float)$order['total_single_rent'];

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
                $total_rent = $single_rent * $qty;
                $total_taxable = $single_taxable * $qty;

                $totalGST += (($cgst + $sgst) * $qty);
                $totalCGST += ($cgst * $qty);
                $totalSGST += ($sgst * $qty);

                $orderDetails[] = [
                    $category_type, 
                    $order['sku'], 
                    $qty, 
                    round($single_taxable, 2), 
                    round($total_taxable, 2), 
                    $gst_rate . '%', 
                    round($cgst * $qty, 2), 
                    round($sgst * $qty, 2), 
                    round(($cgst + $sgst) * $qty, 2), 
                    round($total_rent, 2), 
                    '-'
                ];
            }
        }

        $formatted_date = !empty($bill_date) ? date('d-m-Y', strtotime($bill_date)) : '-';

        // Write Parent Row
        $sheet->fromArray([[$formatted_date, $new_bill_id, $customer_name, $paid_amount, '-', '-', '-', '-', '-', '-', '-', round($totalCGST, 2), round($totalSGST, 2), round($totalGST, 2), $paid_amount, $payment_by_mode_str, $payment_amount_str]], NULL, "A$row");
        $sheet->getStyle("A$row:Q$row")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']]
        ]);
        $row++;

        // Write Child Rows
        foreach ($orderDetails as $order) {
            $sheet->fromArray([['-', '-', '-', '-', '-', ...$order]], NULL, "A$row");
            $row++;
        }
    }
}

// Set column width automatically
foreach (range('A', 'Q') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Save Excel File
$filename = "sales_orders_" . date('Y-m-d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
