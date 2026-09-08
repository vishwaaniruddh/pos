<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

if (file_exists(__DIR__ . '/../top-header.php')) include_once(__DIR__ . '/../top-header.php');
elseif (file_exists('../top-header.php')) include_once('../top-header.php');

if (file_exists(__DIR__ . '/../top-navbar.php')) include_once(__DIR__ . '/../top-navbar.php');
elseif (file_exists('../top-navbar.php')) include_once('../top-navbar.php');

$con = null;
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
    if (function_exists('OpenSrishringarrCon')) {
        $con = OpenSrishringarrCon();
    }
} elseif (file_exists('../db_connection.php')) {
    include_once('../db_connection.php');
    if (function_exists('OpenSrishringarrCon')) {
        $con = OpenSrishringarrCon();
    }
}

if (!function_exists('safe_str')) {
    function safe_str($v) {
        return trim((string)($v ?? ''));
    }
}

$month_num_map = [
    'Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4,
    'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8,
    'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12
];

// Detect latest available year and month from sales commission records
$latest_month = 'all';
$latest_year  = 'all';
if ($con) {
    $q_latest = mysqli_query($con, "
        SELECT YEAR(r.bill_date) as y, MONTH(r.bill_date) as m
        FROM flyrob_commission_sell a
        JOIN approval r ON a.purchase_id = r.bill_id
        WHERE a.status = 'Visible'
        ORDER BY r.bill_date DESC
        LIMIT 1
    ");
    if ($q_latest && $r_latest = mysqli_fetch_assoc($q_latest)) {
        $latest_year = (string)$r_latest['y'];
        $m_int = intval($r_latest['m']);
        foreach ($month_num_map as $m_abbr => $m_idx) {
            if ($m_idx === $m_int) {
                $latest_month = $m_abbr;
                break;
            }
        }
    }
}

// Request parameters (support both GET and POST)
$selected_month = isset($_REQUEST['month']) ? safe_str($_REQUEST['month']) : $latest_month;
$selected_year  = isset($_REQUEST['year']) ? safe_str($_REQUEST['year']) : $latest_year;
$selectFrachise = isset($_REQUEST['selectFrachise']) ? safe_str($_REQUEST['selectFrachise']) : '2'; // 2=All, 1=Flyrobe, 0=Srishringarr
$product_type   = isset($_REQUEST['product_type']) ? safe_str($_REQUEST['product_type']) : 'all'; // all, 1=Jewellery, 2=Apparel
$search_query   = isset($_REQUEST['q']) ? safe_str($_REQUEST['q']) : '';

$limit_param  = safe_str($_REQUEST['limit'] ?? '50');
$is_unlimited = ($limit_param === 'all');
$per_page     = $is_unlimited ? 999999 : intval($limit_param);
if ($per_page <= 0) $per_page = 50;
if ($per_page > 500 && !$is_unlimited) $per_page = 250;

$current_page = max(1, intval($_REQUEST['page'] ?? 1));
$offset       = ($current_page - 1) * $per_page;

// Build SQL WHERE Conditions
$where_clauses = ["a.status = 'Visible'"];

if ($selected_month !== '' && $selected_month !== 'all' && isset($month_num_map[$selected_month])) {
    $m_num = $month_num_map[$selected_month];
    $where_clauses[] = "MONTH(r.bill_date) = $m_num";
}

if ($selected_year !== '' && $selected_year !== 'all') {
    $y_int = intval($selected_year);
    if ($y_int > 0) {
        $where_clauses[] = "YEAR(r.bill_date) = $y_int";
    }
}

if ($selectFrachise === '1') {
    $where_clauses[] = "a.isFlyrobeProduct = 1";
} elseif ($selectFrachise === '0') {
    $where_clauses[] = "a.isFlyrobeProduct = 0";
}

if ($product_type === '1') {
    $where_clauses[] = "a.productType = '1'";
} elseif ($product_type === '2') {
    $where_clauses[] = "a.productType = '2'";
}

if ($search_query !== '') {
    $q_esc = mysqli_real_escape_string($con, $search_query);
    $where_clauses[] = "(a.sku LIKE '%$q_esc%' OR r.new_bill_number LIKE '%$q_esc%' OR a.purchase_id LIKE '%$q_esc%' OR a.company_name LIKE '%$q_esc%' OR p.first_name LIKE '%$q_esc%' OR p.last_name LIKE '%$q_esc%' OR p.phone_number LIKE '%$q_esc%')";
}

$where_sql = implode(" AND ", $where_clauses);

// Helper function for GST and SSFS calculation on outright sales
if (!function_exists('calculate_flyrobe_sell_metrics')) {
    function calculate_flyrobe_sell_metrics($row) {
        $billDate = $row['bill_date'];
        $saleAmount = floatval($row['totalProductAmount'] ?? 0);
        $productType = intval($row['productType'] ?? 2);

        if ($productType == 2) {
            // Apparel
            if (strtotime($billDate) < strtotime("2025-09-22")) {
                $gst_rate = 12;
            } else {
                $gst_rate = ($saleAmount > 2500) ? 18 : 5;
            }
        } else {
            // Jewellery
            $gst_rate = 3;
        }

        $taxable = $saleAmount / (1 + ($gst_rate / 100));
        $gst_amt = round($taxable * ($gst_rate / 100), 2);

        $netAmount = floatval($row['netAmount'] ?? 0);
        if ($netAmount <= 0) {
            $netAmount = max(0, $saleAmount - $gst_amt);
        }

        $commAmount = floatval($row['commision_amount'] ?? 0);
        $ssfs = $netAmount - $commAmount;

        return [
            'gst_rate'    => $gst_rate,
            'taxable'     => $taxable,
            'gst_amt'     => $gst_amt,
            'net_amt'     => $netAmount,
            'comm_amt'    => $commAmount,
            'ssfs_amt'    => $ssfs,
            'sale_amt'    => $saleAmount
        ];
    }
}

// Full-Scope Summary Metrics
$summary = [
    'total_rows'       => 0,
    'total_sale'       => 0,
    'total_gst'        => 0,
    'total_net'        => 0,
    'total_flyrobe'    => 0,
    'total_ssfs'       => 0,
];

// Phase 1: Fast Scope Summary
if ($con) {
    $sql_all = "
        SELECT a.id, a.productType, a.totalProductAmount, a.netAmount, a.commision_amount, 
               r.bill_date
        FROM flyrob_commission_sell a
        JOIN approval r ON a.purchase_id = r.bill_id
        LEFT JOIN phppos_people p ON r.cust_id = p.person_id
        WHERE $where_sql
    ";
    $res_sum = mysqli_query($con, $sql_all);
    if ($res_sum) {
        $summary['total_rows'] = mysqli_num_rows($res_sum);
        while ($r = mysqli_fetch_assoc($res_sum)) {
            $m = calculate_flyrobe_sell_metrics($r);
            $summary['total_sale']    += $m['sale_amt'];
            $summary['total_gst']     += $m['gst_amt'];
            $summary['total_net']     += $m['net_amt'];
            $summary['total_flyrobe'] += $m['comm_amt'];
            $summary['total_ssfs']    += $m['ssfs_amt'];
        }
    }
}

// Handle Export to CSV (Server-side)
if (isset($_REQUEST['export']) && $_REQUEST['export'] == '1') {
    if ($con) {
        $export_sql = "
            SELECT a.*, r.bill_date, r.cust_id, r.new_bill_number,
                   p.first_name, p.last_name, p.phone_number
            FROM flyrob_commission_sell a
            JOIN approval r ON a.purchase_id = r.bill_id
            LEFT JOIN phppos_people p ON r.cust_id = p.person_id
            WHERE $where_sql
            ORDER BY a.id DESC
        ";
        $export_res = mysqli_query($con, $export_sql);
        if ($export_res) {
            $filename = "Flyrobe_Sales_Commission_" . ($selected_month !== 'all' ? $selected_month . "_" : "") . ($selected_year !== 'all' ? $selected_year . "_" : "") . date('Ymd_His') . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Bill Date', 'Product Type', 'SKU', 'Invoice No', 'Customer Name', 'Customer Phone',
                'Company Name', 'Sales Amount (₹)', 'GST Rate', 'GST Amount (₹)',
                'Net Amount (₹)', 'Is Flyrobe Product', 'Flyrobe Commission Rate',
                'Flyrobe Commission (₹)', 'SSFS Share (₹)'
            ]);

            while ($erow = mysqli_fetch_assoc($export_res)) {
                $m = calculate_flyrobe_sell_metrics($erow);
                $cust_name = trim(($erow['first_name'] ?? '') . ' ' . ($erow['last_name'] ?? ''));
                $bill_no = !empty($erow['new_bill_number']) ? $erow['new_bill_number'] : $erow['purchase_id'];

                fputcsv($output, [
                    date('d-m-Y', strtotime($erow['bill_date'])),
                    $erow['productType'] == 2 ? 'Apparel' : 'Jewellery',
                    $erow['sku'],
                    $bill_no,
                    $cust_name ?: 'Walk-in',
                    $erow['phone_number'] ?: '—',
                    $erow['company_name'] ?: '—',
                    $m['sale_amt'],
                    $m['gst_rate'] . '%',
                    $m['gst_amt'],
                    $m['net_amt'],
                    $erow['isFlyrobeProduct'] == 1 ? 'Yes' : 'No',
                    $erow['commision'] ?: '—',
                    $m['comm_amt'],
                    $m['ssfs_amt']
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, [
                'Total Scope (' . $summary['total_rows'] . ' Records)',
                '', '', '', '', '', '',
                $summary['total_sale'],
                '',
                $summary['total_gst'],
                $summary['total_net'],
                '', '',
                $summary['total_flyrobe'],
                $summary['total_ssfs']
            ]);

            fclose($output);
            exit();
        }
    }
}

// Phase 2: Paginated Fetch for Display
$report_rows = [];
$total_pages = ($per_page > 0 && !$is_unlimited && $summary['total_rows'] > 0) ? ceil($summary['total_rows'] / $per_page) : 1;

if ($con && $summary['total_rows'] > 0) {
    $limit_clause = $is_unlimited ? "" : "LIMIT $offset, $per_page";
    $sql_rows = "
        SELECT a.*, r.bill_date, r.cust_id, r.new_bill_number,
               p.first_name, p.last_name, p.phone_number
        FROM flyrob_commission_sell a
        JOIN approval r ON a.purchase_id = r.bill_id
        LEFT JOIN phppos_people p ON r.cust_id = p.person_id
        WHERE $where_sql
        ORDER BY a.id DESC
        $limit_clause
    ";
    $res_rows = mysqli_query($con, $sql_rows);
    if ($res_rows) {
        while ($rw = mysqli_fetch_assoc($res_rows)) {
            $report_rows[] = $rw;
        }
    }
}

// Query distinct years available in sales records
$years_list = [];
if ($con) {
    $q_y = mysqli_query($con, "
        SELECT DISTINCT YEAR(r.bill_date) as y
        FROM flyrob_commission_sell a
        JOIN approval r ON a.purchase_id = r.bill_id
        WHERE a.status = 'Visible'
        ORDER BY y DESC
    ");
    if ($q_y) {
        while ($ry = mysqli_fetch_row($q_y)) {
            if ($ry[0]) $years_list[] = intval($ry[0]);
        }
    }
}
if (empty($years_list)) {
    $years_list = [2026, 2025, 2024];
}

// Pagination URL Builder Helper
if (!function_exists('get_flyrobe_sell_page_url')) {
    function get_flyrobe_sell_page_url($p) {
        $params = $_GET;
        $params['page'] = $p;
        return 'flyrobe_sell.php?' . http_build_query($params);
    }
}
?>

<div class="container-fluid page-body-wrapper">
    <?php
    if (file_exists(__DIR__ . '/../navbar.php')) include_once(__DIR__ . '/../navbar.php');
    elseif (file_exists('../navbar.php')) include_once('../navbar.php');
    ?>

    <!-- Main Panel -->
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">

            <!-- Load External Assets -->
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"
                integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

            <style>
                :root {
                    --pm-slate-900: #0f172a;
                    --pm-slate-800: #1e293b;
                    --pm-slate-700: #334155;
                    --pm-slate-600: #475569;
                    --pm-slate-500: #64748b;
                    --pm-slate-400: #94a3b8;
                    --pm-slate-300: #cbd5e1;
                    --pm-slate-200: #e2e8f0;
                    --pm-slate-100: #f1f5f9;
                    --pm-slate-50:  #f8fafc;
                }

                body {
                    background-color: var(--pm-slate-50);
                    color: var(--pm-slate-900);
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                .flyrobe-container {
                    max-width: 1440px;
                    margin: 0 auto;
                }

                /* Fixed Header & Offset adjustments */
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

                /* Header */
                .pm-page-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 16px;
                    margin-bottom: 24px;
                    padding-bottom: 16px;
                    border-bottom: 1px solid var(--pm-slate-200);
                }

                .pm-page-title {
                    font-size: 22px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    letter-spacing: -0.02em;
                    margin: 0;
                }

                .pm-page-subtitle {
                    font-size: 13px;
                    color: var(--pm-slate-500);
                    margin-top: 4px;
                    margin-bottom: 0;
                }

                /* KPI Grid - 5 Cards in One Row */
                .pm-kpi-grid {
                    display: grid;
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                    gap: 12px;
                    margin-bottom: 20px;
                }

                @media (max-width: 1200px) {
                    .pm-kpi-grid {
                        grid-template-columns: repeat(3, minmax(0, 1fr));
                    }
                }

                @media (max-width: 768px) {
                    .pm-kpi-grid {
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                    }
                }

                @media (max-width: 480px) {
                    .pm-kpi-grid {
                        grid-template-columns: 1fr;
                    }
                }

                .pm-kpi-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 14px 16px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    min-width: 0;
                }

                .pm-kpi-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 6px;
                    margin-bottom: 6px;
                }

                .pm-kpi-label {
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                    color: var(--pm-slate-500);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .pm-kpi-icon {
                    width: 28px;
                    height: 28px;
                    border-radius: 6px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--pm-slate-600);
                    font-size: 12px;
                    flex-shrink: 0;
                }

                .pm-kpi-value {
                    font-size: 20px;
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
                    margin-top: 4px;
                    line-height: 1.3;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                /* Card Surface */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 24px;
                    overflow: hidden;
                }

                .pm-card-body {
                    padding: 18px 20px;
                }

                /* Filter Toolbar Grid */
                .pm-filter-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                    gap: 14px;
                    align-items: flex-end;
                }

                .pm-form-group {
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                }

                .pm-label {
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--pm-slate-700);
                    margin: 0;
                }

                .pm-select, .pm-input {
                    height: 38px;
                    padding: 0 12px;
                    font-size: 13px;
                    color: var(--pm-slate-900);
                    background-color: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    outline: none;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                    width: 100%;
                }

                .pm-select:focus, .pm-input:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                /* Buttons */
                .pm-btn {
                    height: 38px;
                    padding: 0 16px;
                    font-size: 13px;
                    font-weight: 500;
                    border-radius: 6px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                    white-space: nowrap;
                    border: 1px solid transparent;
                }

                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                .pm-btn-primary:hover {
                    background: var(--pm-slate-800);
                    color: #ffffff;
                }

                .pm-btn-secondary {
                    background: #ffffff;
                    color: var(--pm-slate-700);
                    border-color: var(--pm-slate-200);
                }

                .pm-btn-secondary:hover {
                    background: var(--pm-slate-50);
                    border-color: var(--pm-slate-300);
                    color: var(--pm-slate-900);
                }

                .pm-btn-sm {
                    height: 30px;
                    padding: 0 10px;
                    font-size: 12px;
                }

                /* Table Toolbar */
                .pm-table-toolbar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 12px;
                    padding: 14px 20px;
                    background: var(--pm-slate-50);
                    border-bottom: 1px solid var(--pm-slate-200);
                }

                .pm-search-input {
                    height: 36px;
                    padding: 0 12px 0 34px;
                    font-size: 13px;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    background: #ffffff;
                    width: 280px;
                    outline: none;
                }

                .pm-search-input:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                .pm-search-wrapper {
                    position: relative;
                    display: inline-block;
                }

                .pm-search-wrapper i {
                    position: absolute;
                    left: 11px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: var(--pm-slate-400);
                    font-size: 13px;
                }

                /* Table Styles */
                .pm-table-container {
                    overflow-x: auto;
                    background: #ffffff;
                }

                .pm-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 12px;
                    text-align: left;
                    white-space: nowrap;
                }

                .pm-table th {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-700);
                    font-weight: 600;
                    padding: 11px 14px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                }

                .pm-table td {
                    padding: 12px 14px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
                    vertical-align: middle;
                }

                .pm-table tbody tr {
                    transition: background-color 0.1s ease;
                }

                .pm-table tbody tr:hover {
                    background-color: #f1f5f9 !important;
                }

                .pm-table-footer {
                    background: var(--pm-slate-50);
                    font-weight: 700;
                    border-top: 2px solid var(--pm-slate-200);
                    color: var(--pm-slate-900);
                }

                .pm-table-footer td {
                    padding: 12px 14px;
                }

                /* Badge & Pills */
                .pm-badge-neutral {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    padding: 2px 8px;
                    font-size: 11px;
                    font-weight: 500;
                    border-radius: 4px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-600);
                }

                .pm-sku-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-weight: 600;
                    font-size: 12px;
                    color: var(--pm-slate-900);
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    padding: 2px 7px;
                    border-radius: 4px;
                }

                .pm-status-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    padding: 2px 8px;
                    font-size: 11px;
                    font-weight: 500;
                    border-radius: 9999px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
                }

                .pm-dot-indicator {
                    width: 6px;
                    height: 6px;
                    border-radius: 50%;
                    background: var(--pm-slate-500);
                    display: inline-block;
                }

                .pm-dot-flyrobe {
                    background: #2563eb;
                }

                .pm-dot-srishringarr {
                    background: #475569;
                }

                .font-mono {
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                }

                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .text-muted { color: var(--pm-slate-500); }

                /* Pagination */
                .pm-pagination-bar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 12px;
                    padding: 14px 20px;
                    background: #ffffff;
                    border-top: 1px solid var(--pm-slate-200);
                }

                .pm-page-links {
                    display: flex;
                    gap: 4px;
                    align-items: center;
                }

                .pm-page-link {
                    height: 32px;
                    min-width: 32px;
                    padding: 0 8px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    font-size: 12px;
                    font-weight: 500;
                    color: var(--pm-slate-700);
                    background: #ffffff;
                    text-decoration: none;
                    transition: all 0.15s ease;
                }

                .pm-page-link:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900);
                }

                .pm-page-link.active {
                    background: var(--pm-slate-900);
                    border-color: var(--pm-slate-900);
                    color: #ffffff;
                }

                .pm-page-link.disabled {
                    opacity: 0.4;
                    pointer-events: none;
                    cursor: not-allowed;
                }

                /* Print Styles */
                @media print {
                    .navbar, #sidebar, .pm-page-header .pm-btn, .pm-card, .pm-table-toolbar, .pm-pagination-bar, .pm-btn {
                        display: none !important;
                    }
                    .main-panel {
                        margin-left: 0 !important;
                        width: 100% !important;
                    }
                    .content-wrapper {
                        padding: 0 !important;
                        background: #ffffff !important;
                    }
                    .pm-table th, .pm-table td {
                        padding: 5px 8px !important;
                        font-size: 10px !important;
                    }
                    .pm-kpi-grid {
                        grid-template-columns: repeat(5, 1fr) !important;
                        gap: 8px !important;
                    }
                    .flyrobe-container {
                        max-width: 100% !important;
                    }
                }
            </style>

            <div class="flyrobe-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-cart-shopping" style="margin-right: 10px; font-size: 20px; color: var(--pm-slate-700);"></i>
                            Flyrobe Sales Commission Records
                        </h1>
                        <p class="pm-page-subtitle">
                            Review outright retail sales commissions, GST breakdowns, partner settlement percentages, and net revenue distribution.
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button type="button" class="pm-btn pm-btn-secondary" onclick="window.print();">
                            <i class="fa fa-print"></i> Print Report
                        </button>
                        <a href="flyrobe_sell.php?export=1&<?= http_build_query($_GET) ?>" class="pm-btn pm-btn-primary" id="btnExportCSV">
                            <i class="fa fa-download"></i> Export CSV
                        </a>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Gross Sales Turnover</span>
                            <div class="pm-kpi-icon"><i class="fa fa-inr"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($summary['total_sale']) ?></div>
                        <div class="pm-kpi-sub">
                            Retail sold items across <?= number_format($summary['total_rows']) ?> records
                        </div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Flyrobe Commission</span>
                            <div class="pm-kpi-icon"><i class="fa fa-percent"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($summary['total_flyrobe'], 2) ?></div>
                        <div class="pm-kpi-sub">Partner commission payout liability</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">SSFS Net Share</span>
                            <div class="pm-kpi-icon"><i class="fa fa-building-columns"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($summary['total_ssfs'], 2) ?></div>
                        <div class="pm-kpi-sub">Sri Shringarr retail sales share</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total GST</span>
                            <div class="pm-kpi-icon"><i class="fa fa-receipt"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($summary['total_gst'], 2) ?></div>
                        <div class="pm-kpi-sub">Sales tax component (3% / 12% / 18%)</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Net Realized</span>
                            <div class="pm-kpi-icon"><i class="fa fa-scale-balanced"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($summary['total_net'], 2) ?></div>
                        <div class="pm-kpi-sub">Net retail amount after tax</div>
                    </div>
                </div>

                <!-- Filter Toolbar Card -->
                <div class="pm-card">
                    <div class="pm-card-body">
                        <form id="filterForm" method="GET" action="flyrobe_sell.php">
                            <div class="pm-filter-grid">

                                <!-- Month Selector -->
                                <div class="pm-form-group">
                                    <label class="pm-label" for="month">Billing Month</label>
                                    <select class="pm-select" id="month" name="month">
                                        <option value="all" <?= ($selected_month === 'all' || $selected_month === '') ? 'selected' : '' ?>>All Months</option>
                                        <?php
                                        $months = [
                                            "Jan" => "January", "Feb" => "February", "Mar" => "March", 
                                            "Apr" => "April",   "May" => "May",      "Jun" => "June",
                                            "Jul" => "July",    "Aug" => "August",   "Sep" => "September", 
                                            "Oct" => "October", "Nov" => "November", "Dec" => "December"
                                        ];
                                        foreach ($months as $key => $value) {
                                            $sel = ($selected_month === $key) ? 'selected' : '';
                                            echo "<option value='$key' $sel>$value</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Year Selector -->
                                <div class="pm-form-group">
                                    <label class="pm-label" for="year">Billing Year</label>
                                    <select class="pm-select" id="year" name="year">
                                        <option value="all" <?= ($selected_year === 'all' || $selected_year === '') ? 'selected' : '' ?>>All Years</option>
                                        <?php foreach ($years_list as $yr): ?>
                                            <option value="<?= $yr ?>" <?= ($selected_year == $yr) ? 'selected' : '' ?>><?= $yr ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Franchise Selector -->
                                <div class="pm-form-group">
                                    <label class="pm-label" for="selectFrachise">Franchise (Product Wise)</label>
                                    <select class="pm-select" id="selectFrachise" name="selectFrachise">
                                        <option value="2" <?= ($selectFrachise === '2') ? 'selected' : '' ?>>All Products</option>
                                        <option value="1" <?= ($selectFrachise === '1') ? 'selected' : '' ?>>Flyrobe Only</option>
                                        <option value="0" <?= ($selectFrachise === '0') ? 'selected' : '' ?>>Srishringarr Only</option>
                                    </select>
                                </div>

                                <!-- Product Type Selector -->
                                <div class="pm-form-group">
                                    <label class="pm-label" for="product_type">Product Type</label>
                                    <select class="pm-select" id="product_type" name="product_type">
                                        <option value="all" <?= ($product_type === 'all') ? 'selected' : '' ?>>All Types</option>
                                        <option value="2" <?= ($product_type === '2') ? 'selected' : '' ?>>Apparel</option>
                                        <option value="1" <?= ($product_type === '1') ? 'selected' : '' ?>>Jewellery</option>
                                    </select>
                                </div>

                                <!-- Search Query -->
                                <div class="pm-form-group" style="min-width: 180px;">
                                    <label class="pm-label" for="filterQ">SKU / Invoice / Customer / Company</label>
                                    <input type="text" class="pm-input" id="filterQ" name="q" 
                                           placeholder="Search SKU, customer, company..." 
                                           value="<?= htmlspecialchars($search_query) ?>">
                                </div>

                                <!-- Per Page Limit -->
                                <div class="pm-form-group" style="max-width: 120px;">
                                    <label class="pm-label" for="limit">Rows / Page</label>
                                    <select class="pm-select" id="limit" name="limit">
                                        <option value="50" <?= ($limit_param === '50') ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= ($limit_param === '100') ? 'selected' : '' ?>>100</option>
                                        <option value="250" <?= ($limit_param === '250') ? 'selected' : '' ?>>250</option>
                                        <option value="all" <?= ($limit_param === 'all') ? 'selected' : '' ?>>All (<?= number_format($summary['total_rows']) ?>)</option>
                                    </select>
                                </div>

                                <!-- Action Buttons -->
                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="pm-btn pm-btn-primary" style="flex: 1;">
                                        <i class="fa fa-filter"></i> Apply Filter
                                    </button>
                                    <a href="flyrobe_sell.php" class="pm-btn pm-btn-secondary" title="Reset Filters">
                                        <i class="fa fa-refresh"></i> Reset
                                    </a>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

                <!-- Main Data Table Card -->
                <div class="pm-card">
                    <div class="pm-table-toolbar">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 13px; font-weight: 600; color: var(--pm-slate-800);">
                                Records: <span class="font-mono" style="font-weight: 700;"><?= number_format($summary['total_rows']) ?></span>
                            </span>
                            <span style="font-size: 12px; color: var(--pm-slate-400);">&bull;</span>
                            <span style="font-size: 12px; color: var(--pm-slate-500);">
                                Viewing <span id="visibleRowCount"><?= count($report_rows) ?></span> on this page
                            </span>
                        </div>

                        <div class="pm-search-wrapper">
                            <i class="fa fa-search"></i>
                            <input type="text" id="tableSearch" class="pm-search-input" placeholder="Quick filter loaded rows...">
                        </div>
                    </div>

                    <div class="pm-table-container">
                        <table class="pm-table" id="reportTable">
                            <thead>
                                <tr>
                                    <th style="width: 45px;" class="text-center">#</th>
                                    <th style="width: 100px;">Sale Date</th>
                                    <th style="width: 90px;">Type</th>
                                    <th style="width: 120px;">SKU</th>
                                    <th style="width: 135px;">Invoice No</th>
                                    <th>Customer Details</th>
                                    <th>Company</th>
                                    <th class="text-right" style="width: 120px;">Sales Amount</th>
                                    <th class="text-center" style="width: 70px;">GST %</th>
                                    <th class="text-right" style="width: 100px;">GST (₹)</th>
                                    <th class="text-right" style="width: 120px;">Net Amount</th>
                                    <th class="text-center" style="width: 120px;">Franchise</th>
                                    <th class="text-center" style="width: 80px;">Comm %</th>
                                    <th class="text-right" style="width: 120px;">Commission (₹)</th>
                                    <th class="text-right" style="width: 120px;">SSFS Share (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($report_rows)): ?>
                                    <tr>
                                        <td colspan="15" class="text-center" style="padding: 48px; color: var(--pm-slate-400);">
                                            <i class="fa fa-cart-shopping" style="font-size: 32px; margin-bottom: 12px; display: block;"></i>
                                            No sales commission records found matching your filter criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $idx = $offset + 1; 
                                    foreach ($report_rows as $row): 
                                        $m = calculate_flyrobe_sell_metrics($row);
                                        $cust_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                        $cust_phone = trim((string)($row['phone_number'] ?? ''));
                                        $bill_no = !empty($row['new_bill_number']) ? $row['new_bill_number'] : $row['purchase_id'];
                                        $company = trim((string)($row['company_name'] ?? ''));
                                        $is_flyrobe = ($row['isFlyrobeProduct'] == 1);
                                        $is_apparel = ($row['productType'] == 2);

                                        $search_text = strtolower($row['sku'] . ' ' . $bill_no . ' ' . $cust_name . ' ' . $cust_phone . ' ' . $company);
                                    ?>
                                        <tr data-search="<?= htmlspecialchars($search_text) ?>">
                                            <td class="text-center font-mono text-muted"><?= $idx++ ?></td>
                                            <td class="font-mono text-muted">
                                                <?= ($row['bill_date'] && $row['bill_date'] !== '0000-00-00') ? date('d M Y', strtotime($row['bill_date'])) : '—' ?>
                                            </td>
                                            <td>
                                                <span class="pm-badge-neutral">
                                                    <i class="fa <?= $is_apparel ? 'fa-shirt' : 'fa-gem' ?>" style="font-size: 10px;"></i>
                                                    <?= $is_apparel ? 'Apparel' : 'Jewellery' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="pm-sku-pill">
                                                    <i class="fa fa-barcode" style="font-size: 10px; color: var(--pm-slate-400);"></i>
                                                    <?= htmlspecialchars($row['sku']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="./sales_report_detail.php?id=<?= urlencode($row['purchase_id']) ?>" 
                                                   target="_blank" 
                                                   class="font-mono" 
                                                   style="font-weight: 600; color: var(--pm-slate-900); text-decoration: none; border-bottom: 1px dotted var(--pm-slate-400);">
                                                    <?= htmlspecialchars($bill_no) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600; color: var(--pm-slate-900);">
                                                    <?= htmlspecialchars($cust_name ?: 'Walk-in Customer') ?>
                                                </div>
                                                <?php if ($cust_phone && $cust_phone !== '—'): ?>
                                                    <div style="font-size: 11px; color: var(--pm-slate-500); margin-top: 1px;">
                                                        <i class="fa fa-phone" style="font-size: 10px;"></i> <?= htmlspecialchars($cust_phone) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($company): ?>
                                                    <span class="pm-badge-neutral"><?= htmlspecialchars($company) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right font-mono" style="font-weight: 600; color: var(--pm-slate-900);">
                                                ₹ <?= number_format($m['sale_amt']) ?>
                                            </td>
                                            <td class="text-center font-mono text-muted">
                                                <?= $m['gst_rate'] ?>%
                                            </td>
                                            <td class="text-right font-mono text-muted">
                                                ₹ <?= number_format($m['gst_amt'], 2) ?>
                                            </td>
                                            <td class="text-right font-mono" style="font-weight: 600; color: var(--pm-slate-900);">
                                                ₹ <?= number_format($m['net_amt'], 2) ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="pm-status-pill">
                                                    <span class="pm-dot-indicator <?= $is_flyrobe ? 'pm-dot-flyrobe' : 'pm-dot-srishringarr' ?>"></span>
                                                    <?= $is_flyrobe ? 'Flyrobe' : 'Srishringarr' ?>
                                                </span>
                                            </td>
                                            <td class="text-center font-mono text-muted">
                                                <?= htmlspecialchars($row['commision'] ?: '—') ?>
                                            </td>
                                            <td class="text-right font-mono" style="font-weight: 700; color: var(--pm-slate-900);">
                                                ₹ <?= number_format($m['comm_amt'], 2) ?>
                                            </td>
                                            <td class="text-right font-mono" style="font-weight: 700; color: var(--pm-slate-900);">
                                                ₹ <?= number_format($m['ssfs_amt'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($report_rows)): ?>
                            <tfoot class="pm-table-footer">
                                <tr>
                                    <td colspan="7" class="text-right">Scope Total (<?= number_format($summary['total_rows']) ?> Records):</td>
                                    <td class="text-right font-mono">₹ <?= number_format($summary['total_sale']) ?></td>
                                    <td></td>
                                    <td class="text-right font-mono">₹ <?= number_format($summary['total_gst'], 2) ?></td>
                                    <td class="text-right font-mono">₹ <?= number_format($summary['total_net'], 2) ?></td>
                                    <td colspan="2"></td>
                                    <td class="text-right font-mono">₹ <?= number_format($summary['total_flyrobe'], 2) ?></td>
                                    <td class="text-right font-mono">₹ <?= number_format($summary['total_ssfs'], 2) ?></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <?php if (!$is_unlimited && $total_pages > 1): ?>
                    <div class="pm-pagination-bar">
                        <div style="font-size: 13px; color: var(--pm-slate-500);">
                            Page <strong style="color: var(--pm-slate-900);"><?= $current_page ?></strong> of <strong><?= $total_pages ?></strong>
                        </div>
                        <div class="pm-page-links">
                            <a href="<?= get_flyrobe_sell_page_url(1) ?>" class="pm-page-link <?= ($current_page <= 1) ? 'disabled' : '' ?>" title="First Page">
                                <i class="fa fa-angle-double-left"></i>
                            </a>
                            <a href="<?= get_flyrobe_sell_page_url($current_page - 1) ?>" class="pm-page-link <?= ($current_page <= 1) ? 'disabled' : '' ?>" title="Previous Page">
                                <i class="fa fa-angle-left"></i>
                            </a>

                            <?php
                            $start_p = max(1, $current_page - 2);
                            $end_p   = min($total_pages, $current_page + 2);
                            if ($start_p > 1) {
                                echo '<span style="padding: 0 4px; color: var(--pm-slate-400);">...</span>';
                            }
                            for ($p = $start_p; $p <= $end_p; $p++):
                            ?>
                                <a href="<?= get_flyrobe_sell_page_url($p) ?>" class="pm-page-link <?= ($p === $current_page) ? 'active' : '' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>
                            <?php if ($end_p < $total_pages): ?>
                                <span style="padding: 0 4px; color: var(--pm-slate-400);">...</span>
                            <?php endif; ?>

                            <a href="<?= get_flyrobe_sell_page_url($current_page + 1) ?>" class="pm-page-link <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>" title="Next Page">
                                <i class="fa fa-angle-right"></i>
                            </a>
                            <a href="<?= get_flyrobe_sell_page_url($total_pages) ?>" class="pm-page-link <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>" title="Last Page">
                                <i class="fa fa-angle-double-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Live Table Search with 200ms debounce
var searchDebounceTimer = null;
var inputSearchEl = document.getElementById("tableSearch");

if (inputSearchEl) {
    // Enter key triggers server-side query
    inputSearchEl.addEventListener("keydown", function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            var form = document.getElementById("filterForm");
            if (form) {
                var qInput = document.getElementById("filterQ");
                if (qInput) qInput.value = this.value.trim();
                form.submit();
            }
        }
    });

    // In-page client-side filter
    inputSearchEl.addEventListener("input", function() {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(function() {
            var filter = inputSearchEl.value.toLowerCase().trim();
            var table = document.getElementById("reportTable");
            if (!table) return;
            var tbody = table.querySelector("tbody");
            if (!tbody) return;
            var trs = tbody.querySelectorAll("tr[data-search]");
            var visibleCount = 0;

            for (var i = 0; i < trs.length; i++) {
                var searchVal = trs[i].getAttribute("data-search") || "";
                if (filter === "" || searchVal.indexOf(filter) > -1) {
                    trs[i].style.display = "";
                    visibleCount++;
                } else {
                    trs[i].style.display = "none";
                }
            }
            var countEl = document.getElementById("visibleRowCount");
            if (countEl) countEl.innerText = visibleCount;
        }, 200);
    });
}
</script>

<?php
if ($con) {
    CloseCon($con);
}
?>
