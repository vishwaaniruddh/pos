<?php 
require 'vendor/autoload.php';
include('db_connection.php');

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$con = OpenSrishringarrCon();

// Get filters
$bill_id = $_GET['bill_id'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$sku = $_GET['sku'] ?? '';
$company_name = $_GET['company_name'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Base query
$query = "SELECT pr.bill_id, pr.bill_date, pr.rent_amount,pr.new_bill_number,
           IFNULL(CONCAT(pp.first_name, ' ', pp.last_name), '-') AS customer_name,
           pr.card_perc
    FROM phppos_rent pr   
    LEFT JOIN phppos_people pp ON pr.cust_id = pp.person_id
    WHERE 1=1";

// Apply filters
if (!empty($bill_id)) { $query .= " AND pr.bill_id = '$bill_id'"; }
if (!empty($from_date)) { $query .= " AND pr.bill_date >= '$from_date'"; }
if (!empty($to_date)) { $query .= " AND pr.bill_date <= '$to_date'"; }
if (!empty($sku)) { $query .= " AND pr.bill_id IN (SELECT bill_id FROM order_detail WHERE item_id like '%$sku%')"; }

if (!empty($company_name)) { $query .= " AND pr.company_name like '%$company_name%'"; }

// Get total rows (for pagination)
$total_result = mysqli_query($con, $query);
$total_rows = mysqli_num_rows($total_result);
$total_pages = ceil($total_rows / $limit);

// Add LIMIT for pagination
$query .= " ORDER BY pr.bill_id DESC LIMIT $limit OFFSET $offset";
$rent_sql = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Rent Data | Srishringarr Fashion Studio </title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td {    border: 1px solid #ddd;padding: 8px;text-align: center;white-space: nowrap;}
        th { background-color: #4F81BD; color: white; }
        .highlight { background-color: #FFFF99; font-weight: bold; }
        .filter-form { margin-bottom: 20px; }
        .filter-form input, .filter-form select { padding: 8px; margin-right: 10px; }
        .btn { padding: 8px 12px; background: #007BFF; color: white; border: none; cursor: pointer; text-decoration: none; }
        .pagination { margin-top: 20px; text-align: center; }
        .pagination a { padding: 8px 12px; margin: 0 5px; background: #007BFF; color: white; text-decoration: none; border-radius: 4px; }
        .pagination a.disabled { background: #ccc; pointer-events: none; }
        .skuFound{ background: yellow;padding: 5px; }
    </style>
</head>
<body>

<h2>Rent Data</h2>

<form class="filter-form" method="GET">
    Bill ID: <input type="text" name="bill_id" value="<?= htmlspecialchars($bill_id) ?>">
    From Date: <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>">
    To Date: <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
    SKU: <input type="text" name="sku" value="<?= htmlspecialchars($sku) ?>">
    Company: 
    <select name="company_name">
        <option value="">Select</option>
        <option value="SAKAR" <? if($company_name=='SAKAR'){ echo 'selected'; } ?> >SAKAR</option>
        <option value="SS" <? if($company_name=='SS'){ echo 'selected'; } ?> >Srishringarr</option>
    </select>
    <button type="submit" class="btn">Filter</button>
    <a href="tally_rentData_export.php?<?= http_build_query($_GET) ?>" class="btn">Export to Excel</a>
</form>

<table>
    <tr>
        <th>Sr No</th>
        <th>Bill Date</th>
        <th>Invoice No</th>
        <th>Customer Name</th>
        <th>Bill Amount</th>
        <th>GST No</th>
        <th>Product Type</th>
        <th>SKU</th>
        <th>Quantity</th>
        <th>Amount</th>
        <th>Total Taxable</th>
        <th>GST Rate</th>
        <th>CGST</th>
        <th>SGST</th>
        <th>Total GST</th>
        <th>Total Amount</th>
        <th>Payment Mode</th>
        <th>Payment Amount</th>
    </tr>

<?php 
$srno = $offset + 1 ; 
while ($bill = mysqli_fetch_assoc($rent_sql)): 
    $bill_id = $bill['bill_id'];
    $bill_date = $bill['bill_date'];
    $customer_name = ucwords(strtolower($bill['customer_name']));
    $rent_amount = $bill['rent_amount'];
    $totalGST = 0; // Initialize GST counters
    $totalCGST = 0;
    $totalSGST = 0;
    $new_bill_number = $bill['new_bill_number'];
    
    $mode_status = ($bill['card_perc'] == 0) ? "Cash" : (($bill['card_perc'] == 1) ? "Cheque" : "Card");

    $payment_mode_sql = mysqli_query($con, "SELECT payment_by, amount FROM rent_amount WHERE bill_id = '$bill_id'");
    $payment_modes = [];
    $payment_amounts = [];
    while ($payment_row = mysqli_fetch_assoc($payment_mode_sql)) {
        $payment_modes[] = '₹ ' . $payment_row['payment_by'];
        $payment_amounts[] = '₹ ' .$payment_row['amount'];
    }
    $payment_by_mode_str = !empty($payment_modes) ? implode(', ', $payment_modes) : "NA";
    $payment_amount_str = !empty($payment_amounts) ? implode(', ', $payment_amounts) : "NA";
    ?>

    <tr class="highlight">
        <td><?= $srno ?></td>
        <td><?= date('d-m-Y',strtotime($bill_date)) ?></td>
        <td><a href="./reports/rent_report_detail.php?id=<?php echo $bill_id ; ?>"><?= ($new_bill_number ? $new_bill_number : $bill_id); ?></a></td>
        <td><?= $customer_name ?></td>
        <td><?= '&#x20B9 '.$rent_amount ?></td>
        <td>-</td>
        <td colspan="5"></td>
        <td id="parent-gst-rate-<?= $bill_id ?>">-</td>
        <td id="parent-cgst-<?= $bill_id ?>">0</td>
        <td id="parent-sgst-<?= $bill_id ?>">0</td>
        <td id="parent-total-gst-<?= $bill_id ?>">0</td>
        <td><?= '&#x20B9 '.$rent_amount ?></td>
        <td><?= $payment_by_mode_str ?></td>
        <td><?= $payment_amount_str ?></td>
    </tr>

    <?php
    $order_details_sql = mysqli_query($con, "
        SELECT od.item_id AS sku, pi.category_type, od.qty, od.rent AS single_rent
        FROM order_detail od
        INNER JOIN phppos_items pi ON od.item_id = pi.name
        WHERE od.bill_id = '$bill_id'
    ");

    while ($order = mysqli_fetch_assoc($order_details_sql)):
        $sku = $order['sku'];
        $category_type = ($order['category_type'] == 1) ? 'Jewellery' : 'Apparel';
        $qty = $order['qty'];
        $single_rent = $order['single_rent'];
        
                if($order['category_type']==1){
            $gst_rate=3;
            
            $thisProductTotalTaxable = $single_rent/1.03 ; 
            $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.03 ; 
            $cgst = $sgst = $thisProductTotalGst / 2 ;
            
        }else{
            
            if(strtotime($bill_date) < strtotime('2025-09-22')){
                $gst_rate = 12;
                
                $thisProductTotalTaxable = $single_rent / 1.12; 
                $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.12; 
                $cgst = $sgst = $thisProductTotalGst / 2;
        
            }else{
                // After 22-09-2025, apply slab logic
                if($single_rent > 2500){
                    $gst_rate = 18;
                
                    $thisProductTotalTaxable = $single_rent / 1.18; 
                    $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.18; 
                    $cgst = $sgst = $thisProductTotalGst / 2;
                    
                }else{
                    $gst_rate = 5;
                    
                    $thisProductTotalTaxable = $single_rent / 1.05; 
                    $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.05; 
                    $cgst = $sgst = $thisProductTotalGst / 2;
                }
            }
        }
        
        

        $single_taxable = $single_rent - ($cgst + $sgst);
        $total_rent = $single_rent * $qty;
        

        $totalGST += ($cgst + $sgst);
        
        $total_taxable = $single_taxable * $qty  ; 
        $totalCGST += $cgst;
        $totalSGST += $sgst;
        
        if (str_contains(strtolower($sku), strtolower($_REQUEST['sku'])) && $_REQUEST['sku'] ) {
            $skuFound = true;
        } else {
            $skuFound = false;
        }
    ?>
        <tr>
            <td colspan="5"></td>
            <td>-</td>
            <td><?= $category_type ?></td>
            <td><span class="<?php if ($skuFound) { echo 'skuFound'; } ?>"><?= $sku ?></span></td>
            <td><?= $qty ?></td>
            <td><?= '&#x20B9 '.round($single_taxable,2) ?></td>
            <td><?= '&#x20B9 '.round($total_taxable,2) ?></td>
            <td><?= $gst_rate ?>%</td>
            <td><?= '&#x20B9 '.round($qty*$cgst, 2) ?></td>
            <td><?= '&#x20B9 '.round($qty*$sgst, 2) ?></td>
            <td><?= '&#x20B9 '.round($qty*($cgst + $sgst), 2) ?></td>
            <td><?= '&#x20B9 '.$total_rent ?></td>
            <td colspan="2"></td>
        </tr>
    <?php endwhile; ?>

    <!-- Update the parent row's GST values dynamically -->
    <script>
        document.getElementById("parent-cgst-<?= $bill_id ?>").innerText = "<?= '₹ ' . round($totalCGST, 2) ?>";
        document.getElementById("parent-sgst-<?= $bill_id ?>").innerText = "<?= '₹ ' . round($totalSGST, 2) ?>";
        document.getElementById("parent-total-gst-<?= $bill_id ?>").innerText = "<?= '₹ ' . round($totalGST, 2) ?>";
    </script>

<?php endwhile; ?>

</table>

<?php
$from_date = $_REQUEST['from_date'] ?? '';
$to_date = $_REQUEST['to_date'] ?? '';
$sku = $_REQUEST['sku'] ?? '';
$company_name = $_REQUEST['company_name'] ?? '';
$bill_id = $_REQUEST['bill_id'] ?? '';

$query_params = http_build_query([
    'from_date' => $from_date,
    'to_date' => $to_date,
    'sku' => $sku,
    'company_name' => $company_name,
    'bill_id' => $bill_id
]);
?>

<!-- Pagination -->
<div class="pagination">
    <a href="?page=1&<?= $query_params ?>" class="<?= ($page == 1) ? 'disabled' : '' ?>">First</a>
    <a href="?page=<?= max(1, $page - 1) ?>&<?= $query_params ?>">Prev</a>
    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <a href="?page=<?= $i ?>&<?= $query_params ?>" class="<?= ($page == $i) ? 'disabled' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <a href="?page=<?= min($total_pages, $page + 1) ?>&<?= $query_params ?>">Next</a>
    <a href="?page=<?= $total_pages ?>&<?= $query_params ?>" class="<?= ($page == $total_pages) ? 'disabled' : '' ?>">Last</a>
</div>

</body>
</html>
