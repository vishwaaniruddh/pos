<?php 
require 'vendor/autoload.php';
include('db_connection.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Build SQL Query with Filters
$where_conditions = [];

if (!empty($bill_id)) {
    $where_conditions[] = "pr.bill_id = '$bill_id'";
}

if (!empty($from_date) && !empty($to_date)) {
    $where_conditions[] = "pr.bill_date BETWEEN '$from_date' AND '$to_date'";
}

if (!empty($sku)) { $where_conditions[]= "pr.bill_id IN (SELECT bill_id FROM order_detail WHERE item_id = '$sku')"; }


$where_sql = (!empty($where_conditions)) ? "WHERE " . implode(" AND ", $where_conditions) : "";


// Fetch Parent Records
$rent_sql = mysqli_query($con, "
    SELECT pr.bill_id, pr.bill_date, pr.rent_amount,pr.new_bill_number,
           IFNULL(CONCAT(pp.first_name, ' ', pp.last_name), '-') AS customer_name,
           pr.card_perc
    FROM phppos_rent pr   
    LEFT JOIN phppos_people pp ON pr.cust_id = pp.person_id
    $where_sql
    ORDER BY pr.bill_id DESC
");

// Set column headers
$headers = ['Bill Date', 'Invoice No', 'Customer Name', 'Bill Amount', 'GST No', 'Product Type', 'SKU', 'Quantity', 'Amount', 'Total Taxable', 'GST Rate', 'CGST', 'SGST', 'Total GST','Total Amount', 'Payment Mode', 'Payment Amount'];
$sheet->fromArray([$headers], NULL, 'A1');

// Style headers
$sheet->getStyle('A1:Q1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']]
]);

$row = 2;

while ($bill = mysqli_fetch_assoc($rent_sql)) {
    
    
    $totalGST = 0; // Initialize GST counters
    $totalCGST = 0;
    $totalSGST = 0;
    
    
    
    $bill_id = $bill['bill_id'];
    $bill_date = $bill['bill_date'];
    $customer_name = ucwords(strtolower($bill['customer_name']));
    $rent_amount = $bill['rent_amount'];
    $totalGST = 0;

    $new_bill_number = $bill['new_bill_number'];
    $new_bill_id = ($new_bill_number ? $new_bill_number : $bill_id) ; 
    
    
    // Determine Payment Mode
    $mode = $bill['card_perc'];
    $mode_status = ($mode == 0) ? "Cash" : (($mode == 1) ? "Cheque" : (($mode == 2) ? "Card" : "NA"));

    // Fetch Payment Details
    $payment_by_mode_ar = [];
    $payment_amount_ar = [];

    $payment_mode_sql = mysqli_query($con, "SELECT * FROM rent_amount WHERE bill_id = '$bill_id'");
    while ($payment_mode = mysqli_fetch_assoc($payment_mode_sql)) {
        $payment_by_mode_ar[] = $payment_mode['payment_by'];
        $payment_amount_ar[] = $payment_mode['amount'];
    }

    $payment_by_mode_str = !empty($payment_by_mode_ar) ? implode(',', $payment_by_mode_ar) : "NA";
    $payment_amount_str = !empty($payment_amount_ar) ? implode(',', $payment_amount_ar) : "NA";

    // Fetch Child Records (Order Details)
    $order_query = "
        SELECT od.item_id AS sku, pi.category_type, od.qty, od.rent AS total_single_rent
        FROM order_detail od
        INNER JOIN phppos_items pi ON od.item_id = pi.name
        WHERE od.bill_id = '$bill_id'
    ";

    if (!empty($sku)) {
        $order_query .= " AND od.item_id = '$sku'";
    }

    $order_details_sql = mysqli_query($con, $order_query);

    $orderDetails = [];
    while ($order = mysqli_fetch_assoc($order_details_sql)) {
        $category_type = ($order['category_type'] == 1) ? 'Jewellery' : 'Apparel';
        $qty = $order['qty'];
        $single_rent = $order['total_single_rent'];
        $total_single_rent = $single_rent * $qty;
        $gst_rate = ($order['category_type'] == 1) ? 3 : 12;


        if($order['category_type']==1){
            $thisProductTotalTaxable = $single_rent/1.03 ; 
            $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.03 ; 
            $cgst = $sgst = $thisProductTotalGst / 2 ;
        }else if($order['category_type']==2){
            $thisProductTotalTaxable = $single_rent/1.12 ;
            $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.12 ; 
            $cgst = $sgst = $thisProductTotalGst / 2 ;
        }

        $single_taxable = $single_rent - ($cgst + $sgst); 
        $total_rent = $single_rent * $qty;
        

$totalGST += ($cgst + $sgst);
        
        $total_taxable = $single_taxable * $qty  ; 
        $totalCGST += $cgst;
        $totalSGST += $sgst;
        
        // $thisProductTotalTaxable = ($total_single_rent / (($gst_rate / 100) + 1));
        // $thisProductTotalGst = $thisProductTotalTaxable * ($gst_rate / 100);
        // $cgst = $sgst = $thisProductTotalGst / 2;
        // $totalGST += $thisProductTotalGst;

        $orderDetails[] = [$category_type, $order['sku'], $qty, round($single_taxable,2), round($total_taxable,2), $gst_rate, round($cgst, 2), round($sgst, 2), round($thisProductTotalGst, 2),$total_rent, '-'];
    }

$bill_date = date('d-m-Y',strtotime($bill_date)) ; 
    // Write Parent Row (Highlighted)
    $sheet->fromArray([[$bill_date, $new_bill_id, $customer_name, $rent_amount, '-', '-', '-', '-', '-', '-', '-', '-', '-', round($totalGST, 2), $rent_amount, $payment_by_mode_str, $payment_amount_str]], NULL, "A$row");
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
foreach (range('A', 'P') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Save Excel File
$filename = "rent_orders_" . date('Y-m-d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
