<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php';
include_once(__DIR__ . '/db_connection.php');

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

// -------------------------------------------------------------------------
// Filters & Parameters
// -------------------------------------------------------------------------
$bill_id      = isset($_GET['bill_id']) ? trim($_GET['bill_id']) : '';
$from_date    = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date      = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$sku          = isset($_GET['sku']) ? trim($_GET['sku']) : '';
$company_name = isset($_GET['company_name']) ? trim($_GET['company_name']) : '';
$limit        = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
if (!in_array($limit, [10, 20, 50, 100])) {
    $limit = 20;
}
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// -------------------------------------------------------------------------
// Build Filter Conditions
// -------------------------------------------------------------------------
$where_conditions = ["1=1"];

if (!empty($bill_id)) {
    $safe_bill = mysqli_real_escape_string($con, $bill_id);
    $where_conditions[] = "(pr.bill_id = '$safe_bill' OR pr.new_bill_number LIKE '%$safe_bill%')";
}
if (!empty($from_date)) {
    $safe_from = mysqli_real_escape_string($con, $from_date);
    $where_conditions[] = "pr.bill_date >= '$safe_from'";
}
if (!empty($to_date)) {
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

// -------------------------------------------------------------------------
// 1. Fast Global KPI Aggregation for Filtered Set
// -------------------------------------------------------------------------
$total_rows = 0;
$total_sales_sum = 0.00;

if ($con) {
    $q_kpi = mysqli_query($con, "
        SELECT 
            COUNT(*) as total_count,
            COALESCE(SUM(pr.paid_amount), 0) as total_sum
        FROM approval pr
        WHERE $where_sql
    ");
    if ($q_kpi && $rkpi = mysqli_fetch_assoc($q_kpi)) {
        $total_rows      = (int)$rkpi['total_count'];
        $total_sales_sum = (float)$rkpi['total_sum'];
    }
}

$total_pages = max(1, ceil($total_rows / $limit));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;

// -------------------------------------------------------------------------
// 2. Fetch Parent Sales Records for Current Page
// -------------------------------------------------------------------------
$page_bills = [];
$page_bill_ids = [];

if ($con && $total_rows > 0) {
    $query = "
        SELECT pr.bill_id, pr.bill_date, pr.paid_amount, pr.new_bill_number, pr.company_name,
               IFNULL(CONCAT(pp.first_name, ' ', pp.last_name), '-') AS customer_name,
               pr.card_perc
        FROM approval pr   
        LEFT JOIN phppos_people pp ON pr.cust_id = pp.person_id
        WHERE $where_sql
        ORDER BY pr.bill_id DESC 
        LIMIT $limit OFFSET $offset
    ";
    $sales_sql = mysqli_query($con, $query);
    if ($sales_sql) {
        while ($row = mysqli_fetch_assoc($sales_sql)) {
            $page_bills[] = $row;
            $page_bill_ids[] = (int)$row['bill_id'];
        }
    }
}

// -------------------------------------------------------------------------
// 3. Batch Fetch Items & Payment Modes for Current Page
// -------------------------------------------------------------------------
$items_by_bill = [];
$payments_by_bill = [];

if ($con && !empty($page_bill_ids)) {
    $ids_str = implode(",", $page_bill_ids);

    // Items from approval_detail
    $q_items = mysqli_query($con, "
        SELECT od.bill_id, od.item_id AS sku, pi.category_type, od.qty, od.amount AS single_rent
        FROM approval_detail od
        INNER JOIN phppos_items pi ON od.item_id = pi.name
        WHERE od.bill_id IN ($ids_str)
    ");
    if ($q_items) {
        while ($it = mysqli_fetch_assoc($q_items)) {
            $items_by_bill[$it['bill_id']][] = $it;
        }
    }

    // Payments from paid_amount
    $q_pay = mysqli_query($con, "
        SELECT bill_id, payment_by, amount 
        FROM paid_amount 
        WHERE bill_id IN ($ids_str)
    ");
    if ($q_pay) {
        while ($p = mysqli_fetch_assoc($q_pay)) {
            $payments_by_bill[$p['bill_id']][] = $p;
        }
    }
}

// -------------------------------------------------------------------------
// 4. Precompute GST & Taxable Values
// -------------------------------------------------------------------------
$page_gross         = 0.00;
$page_taxable       = 0.00;
$page_gst           = 0.00;
$page_jewel_count   = 0;
$page_apparel_count = 0;

$processed_bills = [];

foreach ($page_bills as $bill) {
    $b_id = $bill['bill_id'];
    $b_date = $bill['bill_date'];
    $b_gross = (float)$bill['paid_amount'];
    $page_gross += $b_gross;

    $bill_taxable   = 0.00;
    $bill_cgst      = 0.00;
    $bill_sgst      = 0.00;
    $bill_total_gst = 0.00;

    $items = $items_by_bill[$b_id] ?? [];
    $processed_items = [];

    foreach ($items as $order) {
        $item_sku       = $order['sku'];
        $cat_type_raw   = (int)$order['category_type'];
        $cat_type_label = ($cat_type_raw === 1) ? 'Jewellery' : 'Apparel';
        $qty            = max(1, (int)$order['qty']);
        $single_rent    = (float)$order['single_rent'];

        if ($cat_type_raw === 1) {
            $gst_rate = 3;
            $this_taxable = $single_rent / 1.03;
            $this_gst     = $this_taxable * 0.03;
            $cgst = $sgst = $this_gst / 2;
            $page_jewel_count += $qty;
        } else {
            $page_apparel_count += $qty;
            if (strtotime($b_date) < strtotime('2025-09-22')) {
                $gst_rate = 12;
                $this_taxable = $single_rent / 1.12;
                $this_gst     = $this_taxable * 0.12;
                $cgst = $sgst = $this_gst / 2;
            } else {
                if ($single_rent > 2500) {
                    $gst_rate = 18;
                    $this_taxable = $single_rent / 1.18;
                    $this_gst     = $this_taxable * 0.18;
                    $cgst = $sgst = $this_gst / 2;
                } else {
                    $gst_rate = 5;
                    $this_taxable = $single_rent / 1.05;
                    $this_gst     = $this_taxable * 0.05;
                    $cgst = $sgst = $this_gst / 2;
                }
            }
        }

        $single_taxable = $single_rent - ($cgst + $sgst);
        $total_taxable  = $single_taxable * $qty;
        $total_rent     = $single_rent * $qty;
        $item_cgst      = $cgst * $qty;
        $item_sgst      = $sgst * $qty;
        $item_total_gst = ($cgst + $sgst) * $qty;

        $bill_taxable   += $total_taxable;
        $bill_cgst      += $item_cgst;
        $bill_sgst      += $item_sgst;
        $bill_total_gst += $item_total_gst;

        $is_sku_match = (!empty($sku) && stripos($item_sku, $sku) !== false);

        $processed_items[] = [
            'sku'            => $item_sku,
            'category_type'  => $cat_type_label,
            'qty'            => $qty,
            'single_rent'    => $single_rent,
            'single_taxable' => $single_taxable,
            'total_taxable'  => $total_taxable,
            'gst_rate'       => $gst_rate,
            'cgst'           => $item_cgst,
            'sgst'           => $item_sgst,
            'total_gst'      => $item_total_gst,
            'total_rent'     => $total_rent,
            'sku_match'      => $is_sku_match
        ];
    }

    $page_taxable += $bill_taxable;
    $page_gst     += $bill_total_gst;

    // Payments formatting
    $bill_payments = $payments_by_bill[$b_id] ?? [];
    $pay_modes = [];
    $pay_amounts = [];
    foreach ($bill_payments as $p) {
        $pay_modes[] = htmlspecialchars($p['payment_by']);
        $pay_amounts[] = '₹ ' . number_format((float)$p['amount'], 2);
    }
    $pay_mode_str = !empty($pay_modes) ? implode(', ', $pay_modes) : "NA";
    $pay_amount_str = !empty($pay_amounts) ? implode(', ', $pay_amounts) : "NA";

    $processed_bills[] = [
        'raw'             => $bill,
        'bill_id'         => $b_id,
        'bill_date'       => $b_date,
        'new_bill_number' => $bill['new_bill_number'],
        'customer_name'   => ucwords(strtolower($bill['customer_name'])),
        'paid_amount'     => $b_gross,
        'company_name'    => $bill['company_name'] ?? '',
        'taxable'         => $bill_taxable,
        'cgst'            => $bill_cgst,
        'sgst'            => $bill_sgst,
        'total_gst'       => $bill_total_gst,
        'pay_mode_str'    => $pay_mode_str,
        'pay_amount_str'  => $pay_amount_str,
        'items'           => $processed_items
    ];
}

$query_params = http_build_query([
    'bill_id'      => $bill_id,
    'from_date'    => $from_date,
    'to_date'      => $to_date,
    'sku'          => $sku,
    'company_name' => $company_name,
    'limit'        => $limit
]);

// -------------------------------------------------------------------------
// POS Layout Shell Inclusion
// -------------------------------------------------------------------------
if (file_exists(__DIR__ . '/top-header.php')) include_once(__DIR__ . '/top-header.php');
if (file_exists(__DIR__ . '/top-navbar.php')) include_once(__DIR__ . '/top-navbar.php');
?>

<div class="container-fluid page-body-wrapper">
    <?php if (file_exists(__DIR__ . '/navbar.php')) include_once(__DIR__ . '/navbar.php'); ?>
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.25rem 1.5rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
            <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

            <style>
                :root {
                    --pm-slate-50: #f8fafc;
                    --pm-slate-100: #f1f5f9;
                    --pm-slate-200: #e2e8f0;
                    --pm-slate-300: #cbd5e1;
                    --pm-slate-400: #94a3b8;
                    --pm-slate-500: #64748b;
                    --pm-slate-600: #475569;
                    --pm-slate-700: #334155;
                    --pm-slate-800: #1e293b;
                    --pm-slate-900: #0f172a;
                }

                body {
                    background-color: var(--pm-slate-50);
                    color: var(--pm-slate-900);
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                .page-body-wrapper {
                    padding-top: 58px !important;
                }

                #sidebar {
                    position: fixed !important;
                    top: 58px;
                    left: 0;
                    bottom: 0;
                    height: calc(100vh - 58px);
                    z-index: 99;
                }

                .main-panel {
                    margin-left: 240px;
                    transition: margin-left 0.2s ease;
                }

                @media (max-width: 991px) {
                    .main-panel {
                        margin-left: 0 !important;
                    }
                }

                /* Header Toolbar */
                .pm-header-toolbar {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 12px;
                    margin-bottom: 16px;
                }

                .pm-page-title {
                    font-size: 19px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 9px;
                    letter-spacing: -0.02em;
                }

                .pm-page-subtitle {
                    font-size: 12.5px;
                    color: var(--pm-slate-500);
                    margin-top: 2px;
                    margin-bottom: 0;
                }

                .pm-toolbar-actions {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    flex-wrap: wrap;
                }

                /* 5 Compact KPI Grid across a single row */
                .pm-kpi-grid {
                    display: grid;
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                    gap: 10px;
                    margin-bottom: 16px;
                }

                @media (max-width: 1200px) {
                    .pm-kpi-grid {
                        grid-template-columns: repeat(3, minmax(0, 1fr));
                    }
                }

                @media (max-width: 768px) {
                    .pm-kpi-grid {
                        grid-template-columns: repeat(1, minmax(0, 1fr));
                    }
                }

                .pm-kpi-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 11px 14px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    transition: border-color 0.15s ease;
                }

                .pm-kpi-card:hover {
                    border-color: var(--pm-slate-300);
                }

                .pm-kpi-top {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 6px;
                }

                .pm-kpi-label {
                    font-size: 10.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    color: var(--pm-slate-500);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .pm-kpi-icon {
                    width: 26px;
                    height: 26px;
                    border-radius: 6px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--pm-slate-600);
                    font-size: 11px;
                    flex-shrink: 0;
                }

                .pm-kpi-value {
                    font-size: 18px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    letter-spacing: -0.02em;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    line-height: 1.2;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .pm-kpi-sub {
                    font-size: 11px;
                    color: var(--pm-slate-500);
                    margin-top: 3px;
                    line-height: 1.2;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                /* Filter Card */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 16px;
                    overflow: hidden;
                }

                .pm-card-header {
                    padding: 10px 16px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 10px;
                }

                .pm-card-title {
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 7px;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                }

                .pm-card-body {
                    padding: 12px 16px;
                }

                /* Balanced Filter Toolbar */
                .pm-filter-row {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    align-items: flex-end;
                }

                .pm-form-group {
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                    flex: 1;
                    min-width: 140px;
                }

                .pm-form-group.sm {
                    max-width: 120px;
                    min-width: 100px;
                }

                .pm-label {
                    font-size: 10.5px;
                    font-weight: 600;
                    color: var(--pm-slate-600);
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                }

                .pm-input, .pm-select {
                    height: 34px;
                    padding: 0 10px;
                    font-size: 12.5px;
                    color: var(--pm-slate-900);
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    outline: none;
                    transition: all 0.15s ease;
                    width: 100%;
                }

                .pm-input:focus, .pm-select:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                /* Buttons */
                .pm-btn {
                    height: 34px;
                    padding: 0 12px;
                    font-size: 12.5px;
                    font-weight: 500;
                    border-radius: 6px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                    white-space: nowrap;
                    border: 1px solid transparent;
                }

                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff !important;
                    border-color: var(--pm-slate-900);
                }

                .pm-btn-primary:hover {
                    background: var(--pm-slate-800);
                    border-color: var(--pm-slate-800);
                }

                .pm-btn-outline {
                    background: #ffffff;
                    color: var(--pm-slate-700) !important;
                    border-color: var(--pm-slate-200);
                }

                .pm-btn-outline:hover {
                    background: var(--pm-slate-50);
                    border-color: var(--pm-slate-300);
                    color: var(--pm-slate-900) !important;
                }

                /* Table Styling */
                .pm-table-container {
                    overflow-x: auto;
                    background: #ffffff;
                }

                .pm-table {
                    width: 100%;
                    border-collapse: separate;
                    border-spacing: 0;
                    font-size: 12px;
                    text-align: left;
                }

                .pm-table th {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    font-weight: 600;
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    padding: 9px 12px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    white-space: nowrap;
                }

                .pm-table th.text-right, .pm-table td.text-right {
                    text-align: right;
                }

                .pm-table th.text-center, .pm-table td.text-center {
                    text-align: center;
                }

                /* Parent Invoice Row */
                .pm-bill-header-row {
                    background: #f8fafc;
                    font-weight: 600;
                }

                .pm-bill-header-row td {
                    padding: 9px 12px;
                    border-top: 1px solid var(--pm-slate-300);
                    border-bottom: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-900);
                    vertical-align: middle;
                    font-size: 12px;
                }

                /* Bill Number Whitespace Nowrap */
                .pm-bill-nowrap {
                    white-space: nowrap !important;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-weight: 600;
                    color: var(--pm-slate-900);
                }

                .pm-bill-link {
                    color: var(--pm-slate-900);
                    text-decoration: none;
                    white-space: nowrap !important;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-weight: 600;
                    padding: 2px 6px;
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 4px;
                    transition: all 0.15s ease;
                }

                .pm-bill-link:hover {
                    background: var(--pm-slate-200);
                    color: var(--pm-slate-900);
                    text-decoration: none;
                }

                .pm-amount {
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-weight: 600;
                    white-space: nowrap;
                    color: var(--pm-slate-900);
                }

                /* Badges */
                .pm-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    padding: 2px 6px;
                    font-size: 10.5px;
                    font-weight: 500;
                    border-radius: 4px;
                    white-space: nowrap;
                }

                .pm-badge-slate {
                    background: var(--pm-slate-200);
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-300);
                }

                .pm-badge-rate {
                    background: #f1f5f9;
                    color: #0f172a;
                    border: 1px solid #cbd5e1;
                    font-family: ui-monospace, monospace;
                    font-weight: 600;
                }

                .pm-badge-sku {
                    font-family: ui-monospace, monospace;
                    font-weight: 600;
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-800);
                    padding: 2px 6px;
                }

                .pm-badge-sku-matched {
                    background: #fef08a !important;
                    color: #854d0e !important;
                    border-color: #facc15 !important;
                    font-weight: 700;
                }

                /* Nested Sub-Table Line Items (Always Visible, No Arrows) */
                .pm-items-cell {
                    padding: 4px 12px 14px 36px !important;
                    background: #ffffff !important;
                    border-bottom: 2px solid var(--pm-slate-200) !important;
                }

                .pm-sub-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    overflow: hidden;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
                }

                .pm-sub-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 11.5px;
                }

                .pm-sub-table th {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-600);
                    font-weight: 600;
                    font-size: 10px;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                    padding: 6px 10px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    white-space: nowrap;
                }

                .pm-sub-table td {
                    padding: 6px 10px;
                    border-bottom: 1px solid var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    font-size: 11px;
                }

                .pm-sub-table tr:last-child td {
                    border-bottom: none;
                }

                .pm-sub-table td.text-right, .pm-sub-table th.text-right {
                    text-align: right;
                }

                .pm-sub-table td.text-center, .pm-sub-table th.text-center {
                    text-align: center;
                }

                /* Pagination */
                .pm-pagination {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 12px 16px;
                    background: #ffffff;
                    border-top: 1px solid var(--pm-slate-200);
                    flex-wrap: wrap;
                    gap: 10px;
                }

                .pm-page-list {
                    display: flex;
                    align-items: center;
                    gap: 4px;
                }

                .pm-page-link {
                    height: 30px;
                    min-width: 30px;
                    padding: 0 8px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: 500;
                    border-radius: 6px;
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
                    text-decoration: none;
                    transition: all 0.15s ease;
                }

                .pm-page-link:hover:not(.disabled):not(.active) {
                    background: var(--pm-slate-50);
                    border-color: var(--pm-slate-300);
                    color: var(--pm-slate-900);
                    text-decoration: none;
                }

                .pm-page-link.active {
                    background: var(--pm-slate-900);
                    border-color: var(--pm-slate-900);
                    color: #ffffff;
                }

                .pm-page-link.disabled {
                    background: var(--pm-slate-50);
                    border-color: var(--pm-slate-100);
                    color: var(--pm-slate-400);
                    cursor: not-allowed;
                    pointer-events: none;
                }

                /* Empty State */
                .pm-empty-state {
                    padding: 40px 20px;
                    text-align: center;
                }

                .pm-empty-icon {
                    width: 44px;
                    height: 44px;
                    border-radius: 50%;
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-400);
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 18px;
                    margin-bottom: 10px;
                }

                .pm-empty-title {
                    font-size: 14px;
                    font-weight: 600;
                    color: var(--pm-slate-800);
                    margin-bottom: 4px;
                }

                .pm-empty-desc {
                    font-size: 12.5px;
                    color: var(--pm-slate-500);
                    margin: 0;
                }
            </style>

            <!-- Page Header Toolbar -->
            <div class="pm-header-toolbar">
                <div>
                    <h1 class="pm-page-title">
                        <i class="fa-solid fa-cart-shopping" style="color: var(--pm-slate-700);"></i>
                        Billing Sales Data (GST / Tally View)
                    </h1>
                    <p class="pm-page-subtitle">Itemized retail sales invoices with slab-wise GST breakdown, taxable values, and Tally export</p>
                </div>
                <div class="pm-toolbar-actions">
                    <button type="button" class="pm-btn pm-btn-outline" onclick="toggleAllSubTables()">
                        <i class="fa-solid fa-eye-slash" id="toggle-sub-icon"></i>
                        <span id="toggle-sub-text">Hide Line Items</span>
                    </button>
                    <a href="tally_salesData_export.php?<?= $query_params ?>" class="pm-btn pm-btn-primary">
                        <i class="fa-solid fa-file-excel"></i>
                        Export to Excel (Tally)
                    </a>
                </div>
            </div>

            <!-- 5 Compact KPI Cards (1 Row) -->
            <div class="pm-kpi-grid">
                <!-- Card 1: Total Sales Invoices -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Total Invoices</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-receipt"></i></div>
                    </div>
                    <div class="pm-kpi-value"><?= number_format($total_rows) ?></div>
                    <div class="pm-kpi-sub">Filtered sales invoices</div>
                </div>

                <!-- Card 2: Filtered Gross Sales -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Filtered Gross Sales</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    </div>
                    <div class="pm-kpi-value">₹ <?= number_format($total_sales_sum, 2) ?></div>
                    <div class="pm-kpi-sub">Cumulative sales revenue</div>
                </div>

                <!-- Card 3: Page Taxable Value -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Page Taxable Base</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-calculator"></i></div>
                    </div>
                    <div class="pm-kpi-value">₹ <?= number_format($page_taxable, 2) ?></div>
                    <div class="pm-kpi-sub">Base value before GST</div>
                </div>

                <!-- Card 4: Page GST Output -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Page GST Output</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-percent"></i></div>
                    </div>
                    <div class="pm-kpi-value">₹ <?= number_format($page_gst, 2) ?></div>
                    <div class="pm-kpi-sub">CGST + SGST collected</div>
                </div>

                <!-- Card 5: Page Line Items -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Items On Page</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                    </div>
                    <div class="pm-kpi-value"><?= number_format($page_jewel_count + $page_apparel_count) ?></div>
                    <div class="pm-kpi-sub">Jewel: <?= $page_jewel_count ?> | App: <?= $page_apparel_count ?></div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-card-title">
                        <i class="fa-solid fa-filter"></i>
                        Filter Sales Invoices & Audit Records
                    </h3>
                    <div style="font-size: 11.5px; color: var(--pm-slate-500);">
                        Showing page <?= $page ?> of <?= $total_pages ?> (<?= number_format($total_rows) ?> total records)
                    </div>
                </div>
                <div class="pm-card-body">
                    <form method="GET">
                        <div class="pm-filter-row">
                            <div class="pm-form-group">
                                <label class="pm-label">Bill ID / Invoice</label>
                                <input type="text" name="bill_id" class="pm-input" placeholder="e.g. 16003 or APP-26" value="<?= htmlspecialchars($bill_id) ?>">
                            </div>
                            <div class="pm-form-group">
                                <label class="pm-label">From Date</label>
                                <input type="date" name="from_date" class="pm-input" value="<?= htmlspecialchars($from_date) ?>">
                            </div>
                            <div class="pm-form-group">
                                <label class="pm-label">To Date</label>
                                <input type="date" name="to_date" class="pm-input" value="<?= htmlspecialchars($to_date) ?>">
                            </div>
                            <div class="pm-form-group">
                                <label class="pm-label">Item SKU</label>
                                <input type="text" name="sku" class="pm-input" placeholder="Search item code..." value="<?= htmlspecialchars($sku) ?>">
                            </div>
                            <div class="pm-form-group">
                                <label class="pm-label">Company</label>
                                <select name="company_name" class="pm-select">
                                    <option value="">All Companies</option>
                                    <option value="SAKAR" <?= ($company_name === 'SAKAR') ? 'selected' : '' ?>>SAKAR</option>
                                    <option value="SS" <?= ($company_name === 'SS') ? 'selected' : '' ?>>Srishringarr</option>
                                </select>
                            </div>
                            <div class="pm-form-group sm">
                                <label class="pm-label">Per Page</label>
                                <select name="limit" class="pm-select">
                                    <option value="10" <?= ($limit == 10) ? 'selected' : '' ?>>10</option>
                                    <option value="20" <?= ($limit == 20) ? 'selected' : '' ?>>20</option>
                                    <option value="50" <?= ($limit == 50) ? 'selected' : '' ?>>50</option>
                                    <option value="100" <?= ($limit == 100) ? 'selected' : '' ?>>100</option>
                                </select>
                            </div>
                            <div style="display: flex; gap: 6px;">
                                <button type="submit" class="pm-btn pm-btn-primary">
                                    <i class="fa-solid fa-magnifying-glass"></i> Filter
                                </button>
                                <?php if (!empty($bill_id) || !empty($from_date) || !empty($to_date) || !empty($sku) || !empty($company_name) || $limit != 20): ?>
                                    <a href="ssfs_format_sales_view.php" class="pm-btn pm-btn-outline" title="Reset Filters">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sales Data Table Card -->
            <div class="pm-card">
                <div class="pm-table-container">
                    <table class="pm-table">
                        <thead>
                            <tr>
                                <th style="width: 36px;" class="text-center">#</th>
                                <th style="white-space: nowrap;">Invoice No</th>
                                <th style="white-space: nowrap;">Bill Date</th>
                                <th style="white-space: nowrap;">Customer Name</th>
                                <th class="text-right">Bill Gross</th>
                                <th class="text-right">Taxable Base</th>
                                <th class="text-right">CGST</th>
                                <th class="text-right">SGST</th>
                                <th class="text-right">Total GST</th>
                                <th class="text-right">Total Bill</th>
                                <th>Payment Mode</th>
                                <th class="text-right">Payment Recd</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($processed_bills)): ?>
                                <tr>
                                    <td colspan="12">
                                        <div class="pm-empty-state">
                                            <div class="pm-empty-icon"><i class="fa-solid fa-inbox"></i></div>
                                            <div class="pm-empty-title">No Sales Bills Found</div>
                                            <p class="pm-empty-desc">No sales transaction matches the selected filter criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $srno = $offset + 1;
                                foreach ($processed_bills as $b): 
                                    $b_id = $b['bill_id'];
                                    $has_items = !empty($b['items']);
                                ?>
                                    <!-- Parent Invoice Row -->
                                    <tr class="pm-bill-header-row" id="bill-row-<?= $b_id ?>">
                                        <td class="text-center" style="color: var(--pm-slate-500); font-weight: 500;">
                                            <?= $srno++ ?>
                                        </td>
                                        <td class="pm-bill-nowrap">
                                            <a href="./reports/sales_report_detail.php?id=<?= $b_id ?>" target="_blank" class="pm-bill-link" title="Open Bill Details">
                                                <?= htmlspecialchars($b['new_bill_number'] ? $b['new_bill_number'] : $b_id) ?>
                                            </a>
                                            <span style="font-size: 10px; color: var(--pm-slate-400);">(#<?= $b_id ?>)</span>
                                        </td>
                                        <td style="white-space: nowrap; font-weight: 500;">
                                            <?= !empty($b['bill_date']) ? date('d-m-Y', strtotime($b['bill_date'])) : '-' ?>
                                        </td>
                                        <td style="font-weight: 600; color: var(--pm-slate-900); white-space: nowrap !important;">
                                            <span style="white-space: nowrap !important;"><?= htmlspecialchars($b['customer_name']) ?></span>
                                            <?php if (!empty($b['company_name'])): ?>
                                                <span class="pm-badge pm-badge-slate" style="font-size: 9.5px; margin-left: 4px; white-space: nowrap !important;">
                                                    <?= htmlspecialchars($b['company_name']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <span style="font-size: 11px; color: var(--pm-slate-500); font-weight: 400; margin-left: 6px; white-space: nowrap !important;">
                                                &bull; <?= count($b['items']) ?> item<?= count($b['items']) === 1 ? '' : 's' ?>
                                            </span>
                                        </td>
                                        <td class="text-right pm-amount">
                                            ₹ <?= number_format($b['paid_amount'], 2) ?>
                                        </td>
                                        <td class="text-right pm-amount" style="color: var(--pm-slate-700);">
                                            ₹ <?= number_format($b['taxable'], 2) ?>
                                        </td>
                                        <td class="text-right pm-amount" style="color: var(--pm-slate-600);">
                                            ₹ <?= number_format($b['cgst'], 2) ?>
                                        </td>
                                        <td class="text-right pm-amount" style="color: var(--pm-slate-600);">
                                            ₹ <?= number_format($b['sgst'], 2) ?>
                                        </td>
                                        <td class="text-right pm-amount" style="color: var(--pm-slate-900);">
                                            ₹ <?= number_format($b['total_gst'], 2) ?>
                                        </td>
                                        <td class="text-right pm-amount">
                                            ₹ <?= number_format($b['paid_amount'], 2) ?>
                                        </td>
                                        <td style="font-size: 11.5px; color: var(--pm-slate-700); white-space: nowrap;">
                                            <?= $b['pay_mode_str'] ?>
                                        </td>
                                        <td class="text-right pm-amount" style="font-size: 11.5px;">
                                            <?= $b['pay_amount_str'] ?>
                                        </td>
                                    </tr>

                                    <!-- Nested Line Items Row (Always Open, No Arrows, Perfectly Aligned) -->
                                    <?php if ($has_items): ?>
                                        <tr class="pm-bill-items-row" id="items-row-<?= $b_id ?>">
                                            <td colspan="12" class="pm-items-cell">
                                                <div class="pm-sub-card">
                                                    <table class="pm-sub-table">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 140px;">Item SKU</th>
                                                                <th style="width: 90px;">Product Type</th>
                                                                <th class="text-center" style="width: 50px;">Qty</th>
                                                                <th class="text-right" style="width: 110px;">Unit Taxable</th>
                                                                <th class="text-right" style="width: 120px;">Total Taxable</th>
                                                                <th class="text-center" style="width: 80px;">GST Rate</th>
                                                                <th class="text-right" style="width: 100px;">CGST</th>
                                                                <th class="text-right" style="width: 100px;">SGST</th>
                                                                <th class="text-right" style="width: 110px;">Total GST</th>
                                                                <th class="text-right" style="width: 120px;">Line Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($b['items'] as $item): ?>
                                                                <tr>
                                                                    <td>
                                                                        <span class="pm-badge <?= $item['sku_match'] ? 'pm-badge-sku-matched' : 'pm-badge-sku' ?>">
                                                                            <?= htmlspecialchars($item['sku']) ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="pm-badge pm-badge-slate"><?= $item['category_type'] ?></span>
                                                                    </td>
                                                                    <td class="text-center" style="font-weight: 600;">
                                                                        <?= $item['qty'] ?>
                                                                    </td>
                                                                    <td class="text-right pm-amount" style="color: var(--pm-slate-600);">
                                                                        ₹ <?= number_format($item['single_taxable'], 2) ?>
                                                                    </td>
                                                                    <td class="text-right pm-amount" style="color: var(--pm-slate-700);">
                                                                        ₹ <?= number_format($item['total_taxable'], 2) ?>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="pm-badge pm-badge-rate"><?= $item['gst_rate'] ?>%</span>
                                                                    </td>
                                                                    <td class="text-right pm-amount" style="color: var(--pm-slate-600);">
                                                                        ₹ <?= number_format($item['cgst'], 2) ?>
                                                                    </td>
                                                                    <td class="text-right pm-amount" style="color: var(--pm-slate-600);">
                                                                        ₹ <?= number_format($item['sgst'], 2) ?>
                                                                    </td>
                                                                    <td class="text-right pm-amount" style="color: var(--pm-slate-600);">
                                                                        ₹ <?= number_format($item['total_gst'], 2) ?>
                                                                    </td>
                                                                    <td class="text-right pm-amount" style="color: var(--pm-slate-900); font-weight: 600;">
                                                                        ₹ <?= number_format($item['total_rent'], 2) ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <?php if ($total_pages > 1): ?>
                    <div class="pm-pagination">
                        <div style="font-size: 12px; color: var(--pm-slate-500);">
                            Showing <strong><?= min($total_rows, $offset + 1) ?></strong> to <strong><?= min($total_rows, $offset + count($processed_bills)) ?></strong> of <strong><?= number_format($total_rows) ?></strong> records
                        </div>
                        <div class="pm-page-list">
                            <a href="?page=1&<?= $query_params ?>" class="pm-page-link <?= ($page == 1) ? 'disabled' : '' ?>" title="First Page">
                                <i class="fa-solid fa-angles-left"></i>
                            </a>
                            <a href="?page=<?= max(1, $page - 1) ?>&<?= $query_params ?>" class="pm-page-link <?= ($page == 1) ? 'disabled' : '' ?>" title="Previous Page">
                                <i class="fa-solid fa-angle-left"></i>
                            </a>
                            <?php 
                            $start_p = max(1, $page - 2);
                            $end_p = min($total_pages, $page + 2);
                            for ($i = $start_p; $i <= $end_p; $i++): 
                            ?>
                                <a href="?page=<?= $i ?>&<?= $query_params ?>" class="pm-page-link <?= ($page == $i) ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            <a href="?page=<?= min($total_pages, $page + 1) ?>&<?= $query_params ?>" class="pm-page-link <?= ($page == $total_pages) ? 'disabled' : '' ?>" title="Next Page">
                                <i class="fa-solid fa-angle-right"></i>
                            </a>
                            <a href="?page=<?= $total_pages ?>&<?= $query_params ?>" class="pm-page-link <?= ($page == $total_pages) ? 'disabled' : '' ?>" title="Last Page">
                                <i class="fa-solid fa-angles-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Interactivity Script -->
            <script>
                var itemsVisible = true;

                function toggleAllSubTables() {
                    var rows = document.querySelectorAll('.pm-bill-items-row');
                    var icon = document.getElementById('toggle-sub-icon');
                    var text = document.getElementById('toggle-sub-text');

                    itemsVisible = !itemsVisible;

                    rows.forEach(function(r) {
                        r.style.display = itemsVisible ? '' : 'none';
                    });

                    if (icon && text) {
                        if (itemsVisible) {
                            icon.className = 'fa-solid fa-eye-slash';
                            text.innerText = 'Hide Line Items';
                        } else {
                            icon.className = 'fa-solid fa-eye';
                            text.innerText = 'Show Line Items';
                        }
                    }
                }
            </script>

        </div><!-- /content-wrapper -->
    </div><!-- /main-panel -->
</div><!-- /container-fluid page-body-wrapper -->

<?php
if ($con && function_exists('CloseCon')) {
    CloseCon($con);
}
?>
</body>
</html>
