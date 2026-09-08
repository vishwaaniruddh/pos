<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} elseif (file_exists('../db_connection.php')) {
    include_once('../db_connection.php');
}
$con = null;
if (function_exists('OpenSrishringarrCon')) {
    $con = OpenSrishringarrCon();
}

if (!function_exists('safe_str')) {
    function safe_str($v) {
        return trim((string)($v ?? ''));
    }
}

if (!function_exists('parse_date_to_db')) {
    function parse_date_to_db($d) {
        $d = trim((string)$d);
        if ($d === '') return '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            return $d;
        }
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $d, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        $ts = strtotime($d);
        return $ts ? date('Y-m-d', $ts) : '';
    }
}

// -------------------------------------------------------------
// AJAX ENDPOINT: Fetch Complete Sales Ledger for a Customer
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'customer_sales_ledger') {
    header('Content-Type: application/json');
    $cust_id = safe_str($_GET['cust_id'] ?? '');

    if (!$con || $cust_id === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid customer identifier.']);
        exit;
    }

    $cid_esc = mysqli_real_escape_string($con, $cust_id);

    // Fetch customer profile
    $cust_meta = null;
    $q_cust = mysqli_query($con, "SELECT person_id, first_name, last_name, phone_number, email, address_1, city FROM phppos_people WHERE person_id = '$cid_esc' LIMIT 1");
    if ($q_cust && $row_c = mysqli_fetch_assoc($q_cust)) {
        $cust_meta = $row_c;
    }

    $sql_ledger = "
        SELECT 
            a.bill_id,
            a.new_bill_number,
            a.bill_date,
            a.status,
            a.paid_amount,
            COALESCE(MAX(ad.discount), a.discount_per, '—') as discount,
            a.bill_by,
            COUNT(ad.aid) as item_count,
            COALESCE(SUM(ad.qty), 0) as total_qty,
            COALESCE(SUM(ad.return_qty), 0) as total_return_qty,
            COALESCE(SUM(CASE WHEN ad.qty > 0 THEN (ad.qty - COALESCE(ad.return_qty, 0)) * ad.amount / ad.qty ELSE 0 END), 0) as net_amount,
            GROUP_CONCAT(CONCAT(ad.item_id, ' (', ad.qty, ')') SEPARATOR ', ') as item_codes
        FROM approval a
        LEFT JOIN approval_detail ad ON a.bill_id = ad.bill_id
        WHERE a.cust_id = '$cid_esc'
        GROUP BY a.bill_id
        ORDER BY a.bill_date DESC, a.bill_id DESC
    ";

    $res_ledger = mysqli_query($con, $sql_ledger);
    $invoices = [];
    $total_sales_cust    = 0;
    $total_approval_cust = 0;
    $total_pieces_cust   = 0;

    if ($res_ledger) {
        while ($b = mysqli_fetch_assoc($res_ledger)) {
            $is_sale  = (strtolower($b['status'] ?? '') === 's');
            $net_val  = floatval($b['net_amount'] ?? 0);
            $sold_q   = intval($b['total_qty'] ?? 0) - intval($b['total_return_qty'] ?? 0);

            if ($is_sale) {
                $total_sales_cust  += $net_val;
                $total_pieces_cust += max(0, $sold_q);
            } else {
                $total_approval_cust += $net_val;
            }

            $invoices[] = [
                'bill_id'         => $b['bill_id'],
                'bill_no'         => !empty($b['new_bill_number']) ? $b['new_bill_number'] : ('BILL-' . $b['bill_id']),
                'bill_date'       => ($b['bill_date'] && $b['bill_date'] !== '0000-00-00') ? date('d M Y', strtotime($b['bill_date'])) : '—',
                'status'          => $b['status'] ?? 's',
                'status_label'    => $is_sale ? 'Sale' : 'Approval',
                'net_amount'      => $net_val,
                'paid_amount'     => floatval($b['paid_amount'] ?? 0),
                'discount'        => $b['discount'] ?? '—',
                'item_count'      => intval($b['item_count'] ?? 0),
                'sold_qty'        => max(0, $sold_q),
                'item_codes'      => $b['item_codes'] ?? 'None',
                'bill_by'         => $b['bill_by'] ?? '—',
            ];
        }
    }

    echo json_encode([
        'success'        => true,
        'customer'       => $cust_meta,
        'total_bills'    => count($invoices),
        'total_sales'    => $total_sales_cust,
        'total_approval' => $total_approval_cust,
        'total_pieces'   => $total_pieces_cust,
        'invoices'       => $invoices
    ]);
    exit;
}

// -------------------------------------------------------------
// MAIN PAGE DATA PROCESSING & FILTERS
// -------------------------------------------------------------
$search_query = safe_str($_GET['q'] ?? ($_GET['search'] ?? ''));
$inv_no       = safe_str($_GET['invno'] ?? ($_GET['billid'] ?? ''));
$trans_type   = safe_str($_GET['type'] ?? 'all');
$from_date    = parse_date_to_db($_GET['frmdate'] ?? ($_GET['from'] ?? ''));
$to_date      = parse_date_to_db($_GET['todate'] ?? ($_GET['to'] ?? ''));

// Pagination & Limit configuration
$limit_param  = safe_str($_GET['limit'] ?? '50');
$is_unlimited = ($limit_param === 'all');
$per_page     = $is_unlimited ? 999999 : intval($limit_param);
if ($per_page <= 0) $per_page = 50;
if ($per_page > 500 && !$is_unlimited) $per_page = 250;

$current_page = max(1, intval($_GET['page'] ?? 1));
$offset       = ($current_page - 1) * $per_page;

// Overall Store Sales KPI Baseline
$kpi_store_revenue   = 0;
$kpi_store_bills     = 0;
$kpi_store_customers = 0;
$kpi_store_sold_qty  = 0;

if ($con) {
    $q_kpi = mysqli_query($con, "
        SELECT 
            COUNT(DISTINCT a.cust_id) as total_customers,
            COUNT(DISTINCT CASE WHEN a.status = 's' THEN a.bill_id END) as total_sales_bills,
            COALESCE(SUM(CASE WHEN a.status = 's' AND ad.qty > 0 THEN (ad.qty - COALESCE(ad.return_qty, 0)) * ad.amount / ad.qty ELSE 0 END), 0) as total_sales_revenue,
            COALESCE(SUM(CASE WHEN a.status = 's' THEN (ad.qty - COALESCE(ad.return_qty, 0)) ELSE 0 END), 0) as total_sold_qty
        FROM approval a
        LEFT JOIN approval_detail ad ON a.bill_id = ad.bill_id
        WHERE a.cust_id != 0 AND a.cust_id != '' AND a.cust_id IS NOT NULL
    ");
    if ($q_kpi && $rk = mysqli_fetch_assoc($q_kpi)) {
        $kpi_store_customers = intval($rk['total_customers'] ?? 0);
        $kpi_store_bills     = intval($rk['total_sales_bills'] ?? 0);
        $kpi_store_revenue   = floatval($rk['total_sales_revenue'] ?? 0);
        $kpi_store_sold_qty  = intval($rk['total_sold_qty'] ?? 0);
    }
}

// Build SQL WHERE Conditions for Filtered Results
$where_clauses = ["a.cust_id != 0", "a.cust_id != ''", "a.cust_id IS NOT NULL"];

if ($inv_no !== '') {
    $inv_esc = mysqli_real_escape_string($con, $inv_no);
    $where_clauses[] = "(a.bill_id = '$inv_esc' OR a.new_bill_number LIKE '%$inv_esc%')";
}

if ($search_query !== '') {
    $q_esc = mysqli_real_escape_string($con, $search_query);
    $where_clauses[] = "(p.first_name LIKE '%$q_esc%' OR p.last_name LIKE '%$q_esc%' OR p.phone_number LIKE '%$q_esc%' OR p.person_id = '$q_esc')";
}

if ($trans_type === 's') {
    $where_clauses[] = "a.status = 's'";
} elseif ($trans_type === 'a') {
    $where_clauses[] = "a.status = 'a'";
}

if (!empty($from_date) && !empty($to_date)) {
    $where_clauses[] = "a.bill_date BETWEEN '$from_date' AND '$to_date'";
} elseif (!empty($from_date)) {
    $where_clauses[] = "a.bill_date >= '$from_date'";
} elseif (!empty($to_date)) {
    $where_clauses[] = "a.bill_date <= '$to_date'";
}

$where_sql = implode(" AND ", $where_clauses);

// Phase 1: Fast Full-Scope KPI Summary & Customer Count Query
$total_customers_in_scope = 0;
$summary_stats = [
    'total_sales'    => 0,
    'total_approved' => 0,
    'total_sold_qty' => 0,
    'total_bills'    => 0,
];

if ($con) {
    $sql_summary = "
        SELECT 
            COUNT(DISTINCT a.cust_id) as total_customers,
            COUNT(DISTINCT CASE WHEN a.status = 's' THEN a.bill_id END) as total_sales_bills,
            COALESCE(SUM(CASE WHEN a.status = 's' AND ad.qty > 0 THEN (ad.qty - COALESCE(ad.return_qty, 0)) * ad.amount / ad.qty ELSE 0 END), 0) as total_sales_revenue,
            COALESCE(SUM(CASE WHEN a.status = 'a' AND ad.qty > 0 THEN (ad.qty - COALESCE(ad.return_qty, 0)) * ad.amount / ad.qty ELSE 0 END), 0) as total_approved_revenue,
            COALESCE(SUM(CASE WHEN a.status = 's' THEN (ad.qty - COALESCE(ad.return_qty, 0)) ELSE 0 END), 0) as total_sold_qty
        FROM approval a
        JOIN phppos_people p ON a.cust_id = p.person_id
        LEFT JOIN approval_detail ad ON a.bill_id = ad.bill_id
        WHERE $where_sql
    ";
    $res_sum = mysqli_query($con, $sql_summary);
    if ($res_sum && $rsum = mysqli_fetch_assoc($res_sum)) {
        $total_customers_in_scope       = intval($rsum['total_customers'] ?? 0);
        $summary_stats['total_sales']    = floatval($rsum['total_sales_revenue'] ?? 0);
        $summary_stats['total_approved'] = floatval($rsum['total_approved_revenue'] ?? 0);
        $summary_stats['total_sold_qty'] = intval($rsum['total_sold_qty'] ?? 0);
        $summary_stats['total_bills']    = intval($rsum['total_sales_bills'] ?? 0);
    }
}

// Phase 2: Fetch Paginated Slice of Customers
$report_rows = [];
if ($con && $total_customers_in_scope > 0) {
    $limit_clause = $is_unlimited ? "" : "LIMIT $offset, $per_page";
    $sql_customers = "
        SELECT 
            p.person_id,
            p.first_name,
            p.last_name,
            p.phone_number,
            p.city,
            p.address_1,
            COUNT(DISTINCT CASE WHEN a.status = 's' THEN a.bill_id END) as sales_bills_count,
            COUNT(DISTINCT CASE WHEN a.status = 'a' THEN a.bill_id END) as approval_bills_count,
            COALESCE(SUM(CASE WHEN a.status = 's' AND ad.qty > 0 THEN (ad.qty - COALESCE(ad.return_qty, 0)) * ad.amount / ad.qty ELSE 0 END), 0) as total_sold_amount,
            COALESCE(SUM(CASE WHEN a.status = 'a' AND ad.qty > 0 THEN (ad.qty - COALESCE(ad.return_qty, 0)) * ad.amount / ad.qty ELSE 0 END), 0) as total_approved_amount,
            COALESCE(SUM(CASE WHEN a.status = 's' THEN (ad.qty - COALESCE(ad.return_qty, 0)) ELSE 0 END), 0) as total_sold_qty,
            MAX(a.bill_date) as last_bill_date,
            MAX(CASE WHEN a.new_bill_number != '' THEN a.new_bill_number ELSE CONCAT('BILL-', a.bill_id) END) as latest_bill_number
        FROM approval a
        JOIN phppos_people p ON a.cust_id = p.person_id
        LEFT JOIN approval_detail ad ON a.bill_id = ad.bill_id
        WHERE $where_sql
        GROUP BY p.person_id
        ORDER BY total_sold_amount DESC, total_approved_amount DESC
        $limit_clause
    ";

    $res_cust = mysqli_query($con, $sql_customers);
    if ($res_cust) {
        while ($row = mysqli_fetch_assoc($res_cust)) {
            $report_rows[] = $row;
        }
    }
}

// Compute averages & total pages
$total_pages = ($per_page > 0 && !$is_unlimited && $total_customers_in_scope > 0) ? ceil($total_customers_in_scope / $per_page) : 1;
$avg_sale_per_cust = ($total_customers_in_scope > 0) ? ($summary_stats['total_sales'] / $total_customers_in_scope) : 0;
$avg_sale_per_bill = ($summary_stats['total_bills'] > 0) ? ($summary_stats['total_sales'] / $summary_stats['total_bills']) : 0;

// Pagination URL Builder Helper
if (!function_exists('get_cust_page_url')) {
    function get_cust_page_url($p) {
        $params = $_GET;
        $params['page'] = $p;
        return 'custsales_report.php?' . http_build_query($params);
    }
}

if (file_exists(__DIR__ . '/../top-header.php')) include_once(__DIR__ . '/../top-header.php');
elseif (file_exists('../top-header.php')) include_once('../top-header.php');

if (file_exists(__DIR__ . '/../top-navbar.php')) include_once(__DIR__ . '/../top-navbar.php');
elseif (file_exists('../top-navbar.php')) include_once('../top-navbar.php');
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

                .custsales-container {
                    max-width: 1440px;
                    margin: 0 auto;
                }

                /* Page Header */
                .pm-page-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    flex-wrap: wrap;
                    gap: 16px;
                    margin-bottom: 24px;
                    padding-bottom: 16px;
                    border-bottom: 1px solid var(--pm-slate-200);
                }

                .pm-page-title {
                    font-size: 20px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0 0 4px 0;
                    letter-spacing: -0.02em;
                    display: flex;
                    align-items: center;
                }

                .pm-page-subtitle {
                    font-size: 13px;
                    color: var(--pm-slate-500);
                    margin: 0;
                }

                /* KPI Metric Cards */
                .pm-kpi-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                    gap: 16px;
                    margin-bottom: 24px;
                }

                .pm-kpi-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 18px 20px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    transition: transform 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-kpi-card:hover {
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                }

                .pm-kpi-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 10px;
                }

                .pm-kpi-label {
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--pm-slate-500);
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                }

                .pm-kpi-icon {
                    width: 32px;
                    height: 32px;
                    border-radius: 6px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--pm-slate-600);
                    font-size: 14px;
                }

                .pm-kpi-value {
                    font-size: 24px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    letter-spacing: -0.02em;
                    line-height: 1.1;
                }

                .pm-kpi-sub {
                    font-size: 12px;
                    color: var(--pm-slate-500);
                    margin-top: 6px;
                }

                /* Filter Toolbar Card */
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

                .pm-filter-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                    gap: 16px;
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

                /* Preset Pills */
                .pm-preset-bar {
                    display: flex;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 6px;
                    margin-top: 14px;
                    padding-top: 14px;
                    border-top: 1px solid var(--pm-slate-100);
                }

                .pm-preset-label {
                    font-size: 11px;
                    font-weight: 600;
                    color: var(--pm-slate-400);
                    text-transform: uppercase;
                    margin-right: 4px;
                }

                .pm-preset-pill {
                    padding: 4px 10px;
                    font-size: 11px;
                    font-weight: 500;
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-600);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 4px;
                    cursor: pointer;
                    transition: all 0.12s ease;
                }

                .pm-preset-pill:hover {
                    background: var(--pm-slate-200);
                    color: var(--pm-slate-900);
                }

                /* Table Styling */
                .pm-table-container {
                    overflow-x: auto;
                    width: 100%;
                }

                .pm-table {
                    width: 100%;
                    border-collapse: collapse;
                    text-align: left;
                    font-size: 13px;
                }

                .pm-table th {
                    background-color: var(--pm-slate-50);
                    font-weight: 600;
                    color: var(--pm-slate-600);
                    padding: 12px 16px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    white-space: nowrap;
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                }

                .pm-table td {
                    padding: 12px 16px;
                    border-bottom: 1px solid var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    vertical-align: middle;
                }

                .pm-table tbody tr:hover {
                    background-color: var(--pm-slate-50);
                }

                .pm-table tbody tr:last-child td {
                    border-bottom: none;
                }

                .pm-table-footer {
                    background-color: var(--pm-slate-100);
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    border-top: 2px solid var(--pm-slate-300);
                }

                .pm-table-footer td {
                    padding: 14px 16px;
                    border-bottom: none;
                }

                .text-right {
                    text-align: right !important;
                }

                .text-center {
                    text-align: center !important;
                }

                .font-mono {
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-size: 12px;
                }

                .pm-badge-neutral {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    padding: 2px 8px;
                    border-radius: 4px;
                    font-family: ui-monospace, SFMono-Regular, monospace;
                    font-size: 11px;
                    font-weight: 600;
                    color: var(--pm-slate-800);
                }

                .pm-status-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    padding: 2px 8px;
                    border-radius: 9999px;
                    font-size: 11px;
                    font-weight: 500;
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
                }

                /* In-Table Live Search Bar */
                .pm-table-toolbar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 12px;
                    padding: 14px 18px;
                    background: #ffffff;
                    border-bottom: 1px solid var(--pm-slate-200);
                }

                .pm-search-input-wrap {
                    position: relative;
                    width: 360px;
                    max-width: 100%;
                }

                .pm-search-input-wrap i {
                    position: absolute;
                    left: 12px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: var(--pm-slate-400);
                    font-size: 13px;
                }

                .pm-search-input-wrap input {
                    padding-left: 34px;
                    height: 36px;
                }

                /* Pagination controls */
                .pm-pagination-bar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 12px;
                    padding: 14px 18px;
                    background: #ffffff;
                    border-top: 1px solid var(--pm-slate-200);
                }

                .pm-page-links {
                    display: flex;
                    align-items: center;
                    gap: 4px;
                }

                .pm-page-link {
                    min-width: 32px;
                    height: 32px;
                    padding: 0 8px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: 500;
                    border-radius: 6px;
                    color: var(--pm-slate-700);
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    text-decoration: none;
                    transition: all 0.12s ease;
                }

                .pm-page-link:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900);
                    text-decoration: none;
                }

                .pm-page-link.active {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                .pm-page-link.disabled {
                    opacity: 0.4;
                    pointer-events: none;
                    background: var(--pm-slate-50);
                }

                /* Modal Overlay */
                .pm-modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(15, 23, 42, 0.55);
                    backdrop-filter: blur(2px);
                    z-index: 9999;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }

                .pm-modal {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 10px;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                    width: 100%;
                    max-width: 980px;
                    max-height: 90vh;
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                    animation: modalFadeIn 0.2s ease-out;
                }

                @keyframes modalFadeIn {
                    from { opacity: 0; transform: scale(0.98); }
                    to { opacity: 1; transform: scale(1); }
                }

                .pm-modal-header {
                    padding: 16px 20px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    background: var(--pm-slate-50);
                }

                .pm-modal-title {
                    font-size: 16px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .pm-modal-close {
                    background: none;
                    border: none;
                    color: var(--pm-slate-400);
                    font-size: 18px;
                    cursor: pointer;
                    padding: 4px;
                    border-radius: 4px;
                }

                .pm-modal-close:hover {
                    color: var(--pm-slate-900);
                    background: var(--pm-slate-200);
                }

                .pm-modal-body {
                    padding: 20px;
                    overflow-y: auto;
                    flex: 1;
                }

                .pm-modal-footer {
                    padding: 12px 20px;
                    border-top: 1px solid var(--pm-slate-200);
                    display: flex;
                    justify-content: flex-end;
                    background: var(--pm-slate-50);
                }

                /* Print Styles */
                @media print {
                    .navbar, #sidebar, .pm-page-header .pm-btn, .pm-card, .pm-table-toolbar, .pm-pagination-bar, .pm-btn, .pm-modal-overlay {
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
                        padding: 6px 10px !important;
                        font-size: 11px !important;
                    }
                    .pm-kpi-grid {
                        grid-template-columns: repeat(4, 1fr) !important;
                        gap: 10px !important;
                    }
                    .custsales-container {
                        max-width: 100% !important;
                    }
                }
            </style>

            <div class="custsales-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-users" style="margin-right: 10px; font-size: 18px; color: var(--pm-slate-700);"></i>
                            Customer Sales Report
                        </h1>
                        <p class="pm-page-subtitle">
                            Analyze client lifetime sales turnover, invoice volume, return adjustments, and purchase history.
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button type="button" class="pm-btn pm-btn-secondary" onclick="window.print();">
                            <i class="fa fa-print"></i> Print Report
                        </button>
                        <button type="button" class="pm-btn pm-btn-primary" onclick="exportReportToCSV();">
                            <i class="fa fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Sales Turnover (Scope)</span>
                            <div class="pm-kpi-icon"><i class="fa fa-inr"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($summary_stats['total_sales']) ?></div>
                        <div class="pm-kpi-sub">
                            <?= number_format($total_customers_in_scope) ?> matching customers in scope
                        </div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Sales Invoices / Bills</span>
                            <div class="pm-kpi-icon"><i class="fa fa-receipt"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($summary_stats['total_bills']) ?></div>
                        <div class="pm-kpi-sub">Total sales invoices recorded</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Pieces Sold</span>
                            <div class="pm-kpi-icon"><i class="fa fa-bag-shopping"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($summary_stats['total_sold_qty']) ?></div>
                        <div class="pm-kpi-sub">Net items purchased after returns</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Avg Realized / Customer</span>
                            <div class="pm-kpi-icon"><i class="fa fa-chart-line"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($avg_sale_per_cust) ?></div>
                        <div class="pm-kpi-sub">
                            Avg ticket size: ₹ <?= number_format($avg_sale_per_bill) ?>
                        </div>
                    </div>
                </div>

                <!-- Filter Toolbar Card -->
                <div class="pm-card">
                    <div class="pm-card-body">
                        <form method="GET" action="custsales_report.php" id="filterForm">
                            <div class="pm-filter-grid">
                                <div class="pm-form-group">
                                    <label class="pm-label" for="filterQ">Search Customer</label>
                                    <input type="text" name="q" id="filterQ" class="pm-input" 
                                           placeholder="Name, Phone, or ID..." 
                                           value="<?= htmlspecialchars($search_query) ?>">
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="invno">Invoice / Bill #</label>
                                    <input type="text" name="invno" id="invno" class="pm-input" 
                                           placeholder="e.g. 6101, BILL-102..." 
                                           value="<?= htmlspecialchars($inv_no) ?>">
                                </div>

                                <div class="pm-form-group" style="max-width: 170px;">
                                    <label class="pm-label" for="typeSelect">Transaction Type</label>
                                    <select name="type" id="typeSelect" class="pm-select">
                                        <option value="all" <?= ($trans_type === 'all' || empty($trans_type)) ? 'selected' : '' ?>>All (Sales & Approvals)</option>
                                        <option value="s" <?= ($trans_type === 's') ? 'selected' : '' ?>>Sales Only (Sold)</option>
                                        <option value="a" <?= ($trans_type === 'a') ? 'selected' : '' ?>>Approvals Only</option>
                                    </select>
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="frmdate">From Date</label>
                                    <input type="date" name="frmdate" id="frmdate" class="pm-input" value="<?= htmlspecialchars($from_date) ?>">
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="todate">To Date</label>
                                    <input type="date" name="todate" id="todate" class="pm-input" value="<?= htmlspecialchars($to_date) ?>">
                                </div>

                                <div class="pm-form-group" style="max-width: 130px;">
                                    <label class="pm-label" for="limitSelect">Display</label>
                                    <select name="limit" id="limitSelect" class="pm-select">
                                        <option value="50" <?= ($limit_param === '50' || empty($limit_param)) ? 'selected' : '' ?>>50 / page</option>
                                        <option value="100" <?= ($limit_param === '100') ? 'selected' : '' ?>>100 / page</option>
                                        <option value="250" <?= ($limit_param === '250') ? 'selected' : '' ?>>250 / page</option>
                                        <option value="all" <?= ($limit_param === 'all') ? 'selected' : '' ?>>All Customers</option>
                                    </select>
                                </div>

                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="pm-btn pm-btn-primary" style="flex: 1;">
                                        <i class="fa fa-filter"></i> Filter
                                    </button>
                                    <a href="custsales_report.php" class="pm-btn pm-btn-secondary" title="Reset Filters">
                                        <i class="fa fa-rotate-left"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Date Presets -->
                            <div class="pm-preset-bar">
                                <span class="pm-preset-label">Quick Ranges:</span>
                                <button type="button" class="pm-preset-pill" onclick="setDateRange('', '')">All Time</button>
                                <button type="button" class="pm-preset-pill" onclick="setDatePreset('this_month')">This Month</button>
                                <button type="button" class="pm-preset-pill" onclick="setDatePreset('last_30')">Last 30 Days</button>
                                <button type="button" class="pm-preset-pill" onclick="setDatePreset('this_fy')">Current FY</button>
                                <button type="button" class="pm-preset-pill" onclick="setDatePreset('last_year')">Last Year</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Data Table Section -->
                <div class="pm-card" style="margin-bottom: 32px;">
                    <!-- Table Toolbar with Live Search -->
                    <div class="pm-table-toolbar">
                        <div class="pm-search-input-wrap">
                            <i class="fa fa-search"></i>
                            <input type="text" id="tableSearch" class="pm-input" 
                                   placeholder="Quick filter on this page or hit Enter to search all..." 
                                   value="<?= htmlspecialchars($search_query) ?>">
                        </div>
                        <div style="font-size: 12px; color: var(--pm-slate-500);">
                            <?php if (!$is_unlimited && $total_customers_in_scope > $per_page): ?>
                                Showing <strong style="color: var(--pm-slate-900);"><?= min($total_customers_in_scope, $offset + 1) ?> - <?= min($total_customers_in_scope, $offset + count($report_rows)) ?></strong> of <strong><?= number_format($total_customers_in_scope) ?></strong> customers (Page <?= $current_page ?> of <?= $total_pages ?>)
                            <?php else: ?>
                                Showing <span id="visibleRowCount" style="font-weight: 700; color: var(--pm-slate-900);"><?= count($report_rows) ?></span> rows
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="pm-table-container">
                        <table class="pm-table" id="reportTable">
                            <thead>
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th>Customer Dossier</th>
                                    <th style="width: 140px;">Contact Number</th>
                                    <th class="text-center" style="width: 100px;">Sales Bills</th>
                                    <th class="text-center" style="width: 100px;">Pieces Sold</th>
                                    <th class="text-right" style="width: 140px;">Realized Sales (₹)</th>
                                    <th class="text-right" style="width: 140px;">Approved (₹)</th>
                                    <th style="width: 150px;">Latest Activity</th>
                                    <th class="text-center" style="width: 130px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($report_rows)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center" style="padding: 48px; color: var(--pm-slate-400);">
                                            <i class="fa fa-users-slash" style="font-size: 32px; margin-bottom: 12px; display: block;"></i>
                                            No customer sales records found matching your filter criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $idx = $offset + 1; 
                                    foreach ($report_rows as $row): 
                                        $cust_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                        if ($cust_name === '') $cust_name = 'Customer #' . $row['person_id'];
                                        $s_amt = floatval($row['total_sold_amount'] ?? 0);
                                        $a_amt = floatval($row['total_approved_amount'] ?? 0);
                                        $search_text = strtolower($cust_name . ' ' . ($row['phone_number'] ?? '') . ' ' . ($row['city'] ?? '') . ' ' . $row['person_id']);
                                    ?>
                                        <tr data-search="<?= htmlspecialchars($search_text) ?>">
                                            <td class="text-center font-mono text-muted"><?= $idx++ ?></td>
                                            <td>
                                                <div style="font-weight: 600; color: var(--pm-slate-900);">
                                                    <a href="javascript:void(0);" onclick="openCustomerSalesLedger('<?= $row['person_id'] ?>');" style="color: var(--pm-slate-900); text-decoration: none;">
                                                        <?= htmlspecialchars($cust_name) ?>
                                                    </a>
                                                </div>
                                                <div style="font-size: 11px; color: var(--pm-slate-500); display: flex; align-items: center; gap: 8px; margin-top: 2px;">
                                                    <span class="pm-badge-neutral">ID: <?= $row['person_id'] ?></span>
                                                    <?php if (!empty($row['city'])): ?>
                                                        <span><i class="fa fa-location-dot" style="font-size: 10px;"></i> <?= htmlspecialchars($row['city']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="font-mono">
                                                <?= !empty($row['phone_number']) ? htmlspecialchars($row['phone_number']) : '—' ?>
                                            </td>
                                            <td class="text-center font-mono">
                                                <strong><?= intval($row['sales_bills_count'] ?? 0) ?></strong>
                                            </td>
                                            <td class="text-center font-mono">
                                                <?= number_format($row['total_sold_qty'] ?? 0) ?>
                                            </td>
                                            <td class="text-right font-mono" style="font-weight: 700; color: var(--pm-slate-900);">
                                                ₹ <?= number_format($s_amt) ?>
                                            </td>
                                            <td class="text-right font-mono text-muted">
                                                <?= $a_amt > 0 ? ('₹ ' . number_format($a_amt)) : '—' ?>
                                            </td>
                                            <td>
                                                <div class="font-mono" style="font-size: 12px; font-weight: 600; color: var(--pm-slate-800);">
                                                    <?= !empty($row['latest_bill_number']) ? htmlspecialchars($row['latest_bill_number']) : '—' ?>
                                                </div>
                                                <div style="font-size: 11px; color: var(--pm-slate-400);">
                                                    <?= ($row['last_bill_date'] && $row['last_bill_date'] !== '0000-00-00') ? date('d M Y', strtotime($row['last_bill_date'])) : '—' ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" 
                                                        onclick="openCustomerSalesLedger('<?= $row['person_id'] ?>');"
                                                        title="View customer sales breakdown & invoices">
                                                    <i class="fa fa-receipt"></i> Sales Ledger
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($report_rows)): ?>
                            <tfoot class="pm-table-footer">
                                <tr>
                                    <td colspan="3" class="text-right">Scope Total (<?= number_format($total_customers_in_scope) ?> Customers):</td>
                                    <td class="text-center font-mono"><?= number_format($summary_stats['total_bills']) ?></td>
                                    <td class="text-center font-mono"><?= number_format($summary_stats['total_sold_qty']) ?></td>
                                    <td class="text-right font-mono">₹ <?= number_format($summary_stats['total_sales']) ?></td>
                                    <td class="text-right font-mono">₹ <?= number_format($summary_stats['total_approved']) ?></td>
                                    <td colspan="2"></td>
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
                            <a href="<?= get_cust_page_url(1) ?>" class="pm-page-link <?= ($current_page <= 1) ? 'disabled' : '' ?>" title="First Page">
                                <i class="fa fa-angle-double-left"></i>
                            </a>
                            <a href="<?= get_cust_page_url($current_page - 1) ?>" class="pm-page-link <?= ($current_page <= 1) ? 'disabled' : '' ?>" title="Previous Page">
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
                                <a href="<?= get_cust_page_url($p) ?>" class="pm-page-link <?= ($p === $current_page) ? 'active' : '' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>
                            <?php if ($end_p < $total_pages): ?>
                                <span style="padding: 0 4px; color: var(--pm-slate-400);">...</span>
                            <?php endif; ?>

                            <a href="<?= get_cust_page_url($current_page + 1) ?>" class="pm-page-link <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>" title="Next Page">
                                <i class="fa fa-angle-right"></i>
                            </a>
                            <a href="<?= get_cust_page_url($total_pages) ?>" class="pm-page-link <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>" title="Last Page">
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

<!-- Modal: Customer Sales Breakdown / Ledger -->
<div class="pm-modal-overlay" id="custSalesModal">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h3 class="pm-modal-title">
                <i class="fa fa-receipt text-muted"></i>
                <span id="modalCustTitle">Customer Sales Ledger</span>
            </h3>
            <button type="button" class="pm-modal-close" onclick="closeCustModal();">&times;</button>
        </div>
        <div class="pm-modal-body" id="modalCustBody">
            <div style="text-align: center; padding: 40px; color: var(--pm-slate-400);">
                <i class="fa fa-circle-notch fa-spin fa-2x"></i>
                <p style="margin-top: 10px; font-size: 13px;">Loading sales transaction ledger...</p>
            </div>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" onclick="closeCustModal();">Close</button>
        </div>
    </div>
</div>

<script>
// Filter Table dynamically with 200ms debounce
var searchDebounceTimer = null;
var inputSearchEl = document.getElementById("tableSearch");

if (inputSearchEl) {
    // If Enter key pressed, perform full server-side search
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

    // Debounced in-page client filter
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

// Preset dates helper
function setDateRange(from, to) {
    document.getElementById('frmdate').value = from;
    document.getElementById('todate').value = to;
    document.getElementById('filterForm').submit();
}

function setDatePreset(type) {
    var today = new Date();
    var pad = function(n) { return n < 10 ? '0' + n : n; };
    var fmt = function(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };

    if (type === 'this_month') {
        var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        setDateRange(fmt(firstDay), fmt(lastDay));
    } else if (type === 'last_30') {
        var past30 = new Date();
        past30.setDate(today.getDate() - 30);
        setDateRange(fmt(past30), fmt(today));
    } else if (type === 'this_fy') {
        var year = today.getFullYear();
        if (today.getMonth() < 3) {
            year = year - 1;
        }
        var fyStart = new Date(year, 3, 1);
        var fyEnd = new Date(year + 1, 2, 31);
        setDateRange(fmt(fyStart), fmt(fyEnd));
    } else if (type === 'last_year') {
        var prevYear = today.getFullYear() - 1;
        var start = new Date(prevYear, 0, 1);
        var end = new Date(prevYear, 11, 31);
        setDateRange(fmt(start), fmt(end));
    }
}

// Open Customer Sales Ledger Modal
function openCustomerSalesLedger(custId) {
    var modal = document.getElementById('custSalesModal');
    var modalBody = document.getElementById('modalCustBody');
    var title = document.getElementById('modalCustTitle');

    title.innerHTML = 'Sales Ledger for Customer <span class="pm-badge-neutral">ID #' + custId + '</span>';
    modalBody.innerHTML = '<div style="text-align:center; padding:40px; color:var(--pm-slate-400);"><i class="fa fa-circle-notch fa-spin fa-2x"></i><p style="margin-top:10px; font-size:13px;">Loading customer sales records...</p></div>';
    modal.style.display = 'flex';

    fetch('custsales_report.php?action=customer_sales_ledger&cust_id=' + encodeURIComponent(custId))
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data.success) {
                modalBody.innerHTML = '<div style="padding:20px; color:#ef4444;"><i class="fa fa-exclamation-triangle"></i> ' + (data.message || 'Error loading records') + '</div>';
                return;
            }

            var c = data.customer || {};
            var custName = ((c.first_name || '') + ' ' + (c.last_name || '')).trim() || ('Customer #' + custId);
            title.innerHTML = 'Sales Ledger: <span style="color:var(--pm-slate-900); font-weight:700;">' + custName + '</span> <span class="pm-badge-neutral">ID #' + custId + '</span>';

            var html = '';
            
            // Profile & KPI Dossier
            html += '<div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); border-radius:8px; padding:14px 18px; margin-bottom:18px; display:flex; justify-content:space-between; flex-wrap:wrap; gap:12px;">';
            html += '  <div>';
            html += '    <div style="font-weight:700; font-size:15px; color:var(--pm-slate-900);">' + custName + '</div>';
            html += '    <div style="font-size:12px; color:var(--pm-slate-500); margin-top:2px;">';
            html += '      <span><i class="fa fa-phone"></i> ' + (c.phone_number || '—') + '</span>';
            if (c.city) html += ' &bull; <span><i class="fa fa-location-dot"></i> ' + c.city + '</span>';
            if (c.email) html += ' &bull; <span><i class="fa fa-envelope"></i> ' + c.email + '</span>';
            html += '    </div>';
            html += '  </div>';
            html += '  <div style="display:flex; gap:16px;">';
            html += '    <div style="text-align:right;">';
            html += '      <div style="font-size:11px; font-weight:600; color:var(--pm-slate-400); text-transform:uppercase;">Lifetime Sales</div>';
            html += '      <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">₹ ' + Number(data.total_sales).toLocaleString('en-IN') + '</div>';
            html += '    </div>';
            html += '    <div style="text-align:right; border-left:1px solid var(--pm-slate-200); padding-left:16px;">';
            html += '      <div style="font-size:11px; font-weight:600; color:var(--pm-slate-400); text-transform:uppercase;">Invoices</div>';
            html += '      <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">' + data.total_bills + '</div>';
            html += '    </div>';
            html += '  </div>';
            html += '</div>';

            if (!data.invoices || data.invoices.length === 0) {
                html += '<div style="text-align:center; padding:30px; color:var(--pm-slate-400);">No sales invoices recorded for this customer.</div>';
            } else {
                html += '<div style="overflow-x:auto; border:1px solid var(--pm-slate-200); border-radius:6px;">';
                html += '<table class="pm-table" style="margin:0;">';
                html += '<thead><tr>';
                html += '<th style="width:40px;" class="text-center">#</th>';
                html += '<th>Invoice #</th>';
                html += '<th>Date</th>';
                html += '<th class="text-center">Type</th>';
                html += '<th class="text-center">Items</th>';
                html += '<th class="text-right">Net Amount (₹)</th>';
                html += '<th class="text-center">Discount</th>';
                html += '<th class="text-center">Action</th>';
                html += '</tr></thead><tbody>';

                data.invoices.forEach(function(item, idx) {
                    html += '<tr>';
                    html += '<td class="text-center font-mono text-muted">' + (idx + 1) + '</td>';
                    html += '<td><span class="font-mono" style="font-weight:600;">' + item.bill_no + '</span></td>';
                    html += '<td class="font-mono text-muted">' + item.bill_date + '</td>';
                    html += '<td class="text-center"><span class="pm-status-pill">' + item.status_label + '</span></td>';
                    html += '<td class="text-center font-mono" title="' + (item.item_codes || '') + '">' + item.item_count + ' (' + item.sold_qty + ' pcs)</td>';
                    html += '<td class="text-right font-mono" style="font-weight:600;">₹ ' + Number(item.net_amount).toLocaleString('en-IN') + '</td>';
                    html += '<td class="text-center font-mono text-muted">' + item.discount + '</td>';
                    html += '<td class="text-center"><a href="/pos/reports/sales_report_detail.php?id=' + item.bill_id + '" target="_blank" class="pm-btn pm-btn-secondary pm-btn-sm" style="font-size:11px; padding:2px 8px; height:26px;"><i class="fa fa-external-link"></i> View Invoice</a></td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
            }

            modalBody.innerHTML = html;
        })
        .catch(function(err) {
            modalBody.innerHTML = '<div style="padding:20px; color:#ef4444;"><i class="fa fa-exclamation-triangle"></i> Failed to load ledger: ' + err.message + '</div>';
        });
}

function closeCustModal() {
    var modal = document.getElementById('custSalesModal');
    if (modal) modal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('custSalesModal');
    if (event.target === modal) {
        closeCustModal();
    }
};

// Export visible data to CSV
function exportReportToCSV() {
    var table = document.getElementById("reportTable");
    var rows = table.querySelectorAll("tr");
    var csv = [];

    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        if (rows[i].style.display === "none") continue;

        for (var j = 0; j < cols.length - 1; j++) {
            var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/\s+/g, " ").trim();
            data = data.replace(/"/g, '""');
            data = data.replace(/₹/g, '').trim();
            row.push('"' + data + '"');
        }
        if (row.length > 0) {
            csv.push(row.join(","));
        }
    }

    var csvFile = new Blob([csv.join("\n")], { type: "text/csv;charset=utf-8;" });
    var downloadLink = document.createElement("a");
    downloadLink.download = "Customer_Sales_Report_" + (new Date().toISOString().slice(0, 10)) + ".csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

<?php
if ($con) {
    CloseCon($con);
}
?>