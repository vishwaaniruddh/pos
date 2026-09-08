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
// AJAX ENDPOINT: Fetch Sales Ledger for a Specific Item SKU
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'item_history') {
    header('Content-Type: application/json');
    $sku = safe_str($_GET['sku'] ?? ($_GET['item_id'] ?? ''));

    if (!$con || $sku === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid or missing SKU.']);
        exit;
    }

    $sku_esc = mysqli_real_escape_string($con, $sku);

    // Fetch item profile
    $item_meta = null;
    $q_meta = mysqli_query($con, "SELECT name, category, cost_price, unit_price, description FROM phppos_items WHERE name = '$sku_esc' LIMIT 1");
    if ($q_meta && $row_m = mysqli_fetch_assoc($q_meta)) {
        $item_meta = $row_m;
    }

    $sql_hist = "
        SELECT 
            a.bill_id,
            a.new_bill_number,
            a.bill_date,
            a.status,
            cust.person_id,
            cust.first_name,
            cust.last_name,
            cust.phone_number,
            ad.qty,
            ad.return_qty,
            (ad.qty - ad.return_qty) as sold_qty,
            ad.amount,
            (ad.amount * (ad.qty - ad.return_qty) / ad.qty) as net_amount,
            ad.discount
        FROM approval a
        JOIN approval_detail ad ON a.bill_id = ad.bill_id
        LEFT JOIN phppos_people cust ON a.cust_id = cust.person_id
        WHERE a.status = 's' AND ad.item_id = '$sku_esc' AND ad.qty > 0
        ORDER BY a.bill_date DESC, a.bill_id DESC
    ";

    $res_hist = mysqli_query($con, $sql_hist);
    $history = [];
    $total_sales_item = 0;
    $total_sold_item  = 0;

    if ($res_hist) {
        while ($row = mysqli_fetch_assoc($res_hist)) {
            $sold_q = intval($row['sold_qty'] ?? 0);
            $net_a  = floatval($row['net_amount'] ?? 0);

            $total_sales_item += $net_a;
            $total_sold_item  += $sold_q;

            $history[] = [
                'bill_id'    => $row['bill_id'],
                'bill_no'    => !empty($row['new_bill_number']) ? $row['new_bill_number'] : ('BILL-' . $row['bill_id']),
                'bill_date'  => ($row['bill_date'] && $row['bill_date'] !== '0000-00-00') ? date('d M Y', strtotime($row['bill_date'])) : '—',
                'cust_name'  => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: 'Guest Customer',
                'cust_phone' => $row['phone_number'] ?? '—',
                'qty'        => intval($row['qty'] ?? 1),
                'return_qty' => intval($row['return_qty'] ?? 0),
                'sold_qty'   => $sold_q,
                'amount'     => floatval($row['amount'] ?? 0),
                'net_amount' => $net_a,
                'discount'   => $row['discount'] ?? '—',
            ];
        }
    }

    echo json_encode([
        'success'        => true,
        'sku'            => $sku,
        'meta'           => $item_meta,
        'total_bills'    => count($history),
        'total_sold_qty' => $total_sold_item,
        'total_sales'    => $total_sales_item,
        'history'        => $history
    ]);
    exit;
}

// -------------------------------------------------------------
// MAIN PAGE DATA PROCESSING & FILTERS
// -------------------------------------------------------------
$selected_cat = safe_str($_GET['cate'] ?? ($_GET['cat'] ?? ''));
$search_query = safe_str($_GET['q'] ?? ($_GET['sku'] ?? ''));
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

// Fetch list of distinct categories for dropdown
$categories_list = [];
if ($con) {
    $q_cat = mysqli_query($con, "SELECT DISTINCT category FROM phppos_items WHERE category != '' AND category IS NOT NULL ORDER BY category ASC");
    if ($q_cat) {
        while ($rc = mysqli_fetch_row($q_cat)) {
            $cat_val = trim($rc[0]);
            if ($cat_val !== '' && !in_array($cat_val, $categories_list)) {
                $categories_list[] = $cat_val;
            }
        }
    }
}

// Overall Store Sales KPI Baseline
$kpi_store_revenue = 0;
$kpi_store_bills   = 0;
$kpi_store_sold    = 0;
$kpi_store_cats    = count($categories_list);

if ($con) {
    $q_kpi = mysqli_query($con, "
        SELECT 
            COUNT(DISTINCT a.bill_id) as total_bills,
            SUM(ad.qty - ad.return_qty) as total_sold_qty,
            SUM(ad.amount * (ad.qty - ad.return_qty) / ad.qty) as total_sales_amt
        FROM approval a
        JOIN approval_detail ad ON a.bill_id = ad.bill_id
        WHERE a.status = 's' AND ad.qty > 0
    ");
    if ($q_kpi && $rk = mysqli_fetch_assoc($q_kpi)) {
        $kpi_store_bills   = intval($rk['total_bills'] ?? 0);
        $kpi_store_sold    = intval($rk['total_sold_qty'] ?? 0);
        $kpi_store_revenue = floatval($rk['total_sales_amt'] ?? 0);
    }
}

// Filtered report processing
$is_specific_category = ($selected_cat !== '' && $selected_cat !== '0' && strtolower($selected_cat) !== 'all');
$report_rows = [];
$total_items_in_scope = 0;
$summary_stats = [
    'total_sales' => 0,
    'total_sold'  => 0,
    'total_bills' => 0,
    'item_count'  => 0,
];

if ($con) {
    $date_conditions = [];
    if (!empty($from_date) && !empty($to_date)) {
        $date_conditions[] = "a.bill_date BETWEEN '$from_date' AND '$to_date'";
    } elseif (!empty($from_date)) {
        $date_conditions[] = "a.bill_date >= '$from_date'";
    } elseif (!empty($to_date)) {
        $date_conditions[] = "a.bill_date <= '$to_date'";
    }
    $date_sql = !empty($date_conditions) ? (" AND " . implode(" AND ", $date_conditions)) : "";

    $search_sql = "";
    if ($search_query !== '') {
        $q_esc = mysqli_real_escape_string($con, $search_query);
        $search_sql = " AND (ad.item_id LIKE '%$q_esc%' OR i.description LIKE '%$q_esc%')";
    }

    if ($is_specific_category) {
        $cat_esc = mysqli_real_escape_string($con, $selected_cat);

        // 1. Fast Full-Category KPI & Total Count Query
        $sql_cat_summary = "
            SELECT 
                COUNT(DISTINCT ad.item_id) as total_items,
                COUNT(DISTINCT a.bill_id) as total_bills,
                COALESCE(SUM(ad.qty - ad.return_qty), 0) as total_sold_qty,
                COALESCE(SUM(ad.amount * (ad.qty - ad.return_qty) / ad.qty), 0) as total_sales_amt
            FROM approval a
            JOIN approval_detail ad ON a.bill_id = ad.bill_id
            JOIN phppos_items i ON ad.item_id = i.name
            WHERE a.status = 's' 
              AND ad.qty > 0 
              AND i.category = '$cat_esc' $date_sql $search_sql
        ";
        $res_c_sum = mysqli_query($con, $sql_cat_summary);
        if ($res_c_sum && $rcs = mysqli_fetch_assoc($res_c_sum)) {
            $total_items_in_scope       = intval($rcs['total_items'] ?? 0);
            $summary_stats['total_sales'] = floatval($rcs['total_sales_amt'] ?? 0);
            $summary_stats['total_sold']  = intval($rcs['total_sold_qty'] ?? 0);
            $summary_stats['total_bills'] = intval($rcs['total_bills'] ?? 0);
            $summary_stats['item_count']  = $total_items_in_scope;
        }

        // 2. Fetch Paginated Slice of Items
        $limit_clause = $is_unlimited ? "" : "LIMIT $offset, $per_page";
        $sql_items = "
            SELECT 
                ad.item_id,
                COALESCE(i.category, '$cat_esc') as category,
                COALESCE(i.cost_price, 0) as cost_price,
                COALESCE(i.unit_price, 0) as unit_price,
                COALESCE(i.description, '') as description,
                COUNT(DISTINCT a.bill_id) as bill_count,
                COALESCE(SUM(ad.qty - ad.return_qty), 0) as total_sold_qty,
                COALESCE(SUM(ad.amount * (ad.qty - ad.return_qty) / ad.qty), 0) as total_sales_amt
            FROM approval a
            JOIN approval_detail ad ON a.bill_id = ad.bill_id
            JOIN phppos_items i ON ad.item_id = i.name
            WHERE a.status = 's' 
              AND ad.qty > 0 
              AND i.category = '$cat_esc' $date_sql $search_sql
            GROUP BY ad.item_id
            HAVING total_sold_qty > 0
            ORDER BY total_sales_amt DESC, bill_count DESC
            $limit_clause
        ";

        $res_items = mysqli_query($con, $sql_items);
        if ($res_items) {
            while ($row = mysqli_fetch_assoc($res_items)) {
                $report_rows[] = $row;
            }
        }
    } else {
        // CATEGORY SALES LEADERBOARD / ALL CATEGORIES OVERVIEW
        $sql_cat_lead = "
            SELECT 
                i.category,
                COUNT(DISTINCT a.bill_id) as bill_count,
                COALESCE(SUM(ad.qty - ad.return_qty), 0) as total_sold_qty,
                COALESCE(SUM(ad.amount * (ad.qty - ad.return_qty) / ad.qty), 0) as total_sales_amt,
                COUNT(DISTINCT ad.item_id) as unique_items
            FROM approval a
            JOIN approval_detail ad ON a.bill_id = ad.bill_id
            JOIN phppos_items i ON ad.item_id = i.name
            WHERE a.status = 's' 
              AND ad.qty > 0 
              AND i.category != '' AND i.category IS NOT NULL $date_sql
            GROUP BY i.category
            HAVING total_sales_amt > 0
            ORDER BY total_sales_amt DESC
        ";

        $res_cats = mysqli_query($con, $sql_cat_lead);
        if ($res_cats) {
            while ($row = mysqli_fetch_assoc($res_cats)) {
                $report_rows[] = $row;
                $summary_stats['total_sales'] += floatval($row['total_sales_amt'] ?? 0);
                $summary_stats['total_sold']  += intval($row['total_sold_qty'] ?? 0);
                $summary_stats['total_bills'] += intval($row['bill_count'] ?? 0);
                $summary_stats['item_count']++;
            }
        }
        $total_items_in_scope = count($report_rows);
    }
}

// Compute averages & total pages
$total_pages = ($per_page > 0 && !$is_unlimited && $total_items_in_scope > 0) ? ceil($total_items_in_scope / $per_page) : 1;
$avg_sale_per_unit = ($summary_stats['total_sold'] > 0) ? ($summary_stats['total_sales'] / $summary_stats['total_sold']) : 0;
$avg_sale_per_bill = ($summary_stats['total_bills'] > 0) ? ($summary_stats['total_sales'] / $summary_stats['total_bills']) : 0;

// Pagination URL Builder Helper
function get_page_url($p) {
    $params = $_GET;
    $params['page'] = $p;
    return 'catagorysales_report.php?' . http_build_query($params);
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

                .catsales-container {
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
                    padding: 16px 18px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    transition: transform 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-kpi-card:hover {
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
                }

                .pm-kpi-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 10px;
                }

                .pm-kpi-label {
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    color: var(--pm-slate-500);
                    margin: 0;
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
                    font-size: 13px;
                }

                .pm-kpi-value {
                    font-size: 24px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    line-height: 1.2;
                    margin-bottom: 4px;
                    font-feature-settings: "tnum";
                }

                .pm-kpi-sub {
                    font-size: 11px;
                    color: var(--pm-slate-500);
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

                /* Category Header Pill Banner */
                .pm-banner-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 16px 20px;
                    margin-bottom: 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 14px;
                }

                .pm-banner-title {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                .pm-cat-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 6px 12px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                }

                /* Data Table Styling */
                .pm-table-container {
                    overflow-x: auto;
                    border-radius: 8px;
                    border: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                }

                .pm-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 13px;
                    text-align: left;
                }

                .pm-table th {
                    background-color: var(--pm-slate-50);
                    color: var(--pm-slate-600);
                    font-weight: 600;
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

                .pm-sku-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    padding: 3px 8px;
                    border-radius: 4px;
                    font-family: ui-monospace, SFMono-Regular, monospace;
                    font-weight: 600;
                    color: var(--pm-slate-900);
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

                /* Progress bar for category share */
                .pm-progress-wrap {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    width: 140px;
                }

                .pm-progress-bar {
                    flex: 1;
                    height: 6px;
                    background: var(--pm-slate-200);
                    border-radius: 9999px;
                    overflow: hidden;
                }

                .pm-progress-fill {
                    height: 100%;
                    background: var(--pm-slate-800);
                    border-radius: 9999px;
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
                    max-width: 960px;
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
                    .catsales-container {
                        max-width: 100% !important;
                    }
                }
            </style>

            <div class="catsales-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-chart-pie" style="margin-right: 10px; font-size: 18px; color: var(--pm-slate-700);"></i>
                            Consolidated Category Sales Report
                        </h1>
                        <p class="pm-page-subtitle">
                            Analyze outright retail sales turnover, net sold units, return deductions, and merchandise performance by category.
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
                            <span class="pm-kpi-label"><?= $is_specific_category ? 'Category Sales Turnover' : 'Scope Sales Turnover' ?></span>
                            <div class="pm-kpi-icon"><i class="fa fa-inr"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($summary_stats['total_sales']) ?></div>
                        <div class="pm-kpi-sub">
                            <?= $is_specific_category ? ('Category: ' . htmlspecialchars($selected_cat)) : ('Across ' . $summary_stats['item_count'] . ' active sales categories') ?>
                        </div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Units Sold (Net Pieces)</span>
                            <div class="pm-kpi-icon"><i class="fa fa-shopping-bag"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($summary_stats['total_sold']) ?></div>
                        <div class="pm-kpi-sub">Net pieces sold after return adjustments</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Sales Invoices / Bills</span>
                            <div class="pm-kpi-icon"><i class="fa fa-receipt"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($summary_stats['total_bills']) ?></div>
                        <div class="pm-kpi-sub">Sales transactions recorded</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Avg Realized Price / Unit</span>
                            <div class="pm-kpi-icon"><i class="fa fa-tag"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($avg_sale_per_unit) ?></div>
                        <div class="pm-kpi-sub">
                            Avg ticket size: ₹ <?= number_format($avg_sale_per_bill) ?>
                        </div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="pm-card">
                    <div class="pm-card-body">
                        <form method="GET" action="catagorysales_report.php" id="filterForm">
                            <div class="pm-filter-grid">
                                <div class="pm-form-group">
                                    <label class="pm-label" for="catSelect">Select Category</label>
                                    <select name="cate" id="catSelect" class="pm-select">
                                        <option value="all" <?= (!$is_specific_category || $selected_cat === 'all') ? 'selected' : '' ?>>
                                            All Categories (Sales Leaderboard)
                                        </option>
                                        <?php foreach ($categories_list as $cat_opt): ?>
                                            <option value="<?= htmlspecialchars($cat_opt) ?>" <?= ($selected_cat === $cat_opt) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat_opt) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="filterQ">Search SKU / Item</label>
                                    <input type="text" name="q" id="filterQ" class="pm-input" 
                                           placeholder="e.g. ND185, T699EON..." 
                                           value="<?= htmlspecialchars($search_query) ?>">
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="frmdate">From Date</label>
                                    <input type="date" name="frmdate" id="frmdate" class="pm-input" value="<?= htmlspecialchars($from_date) ?>">
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="todate">To Date</label>
                                    <input type="date" name="todate" id="todate" class="pm-input" value="<?= htmlspecialchars($to_date) ?>">
                                </div>

                                <div class="pm-form-group" style="max-width: 140px;">
                                    <label class="pm-label" for="limitSelect">Display</label>
                                    <select name="limit" id="limitSelect" class="pm-select">
                                        <option value="50" <?= ($limit_param === '50' || empty($limit_param)) ? 'selected' : '' ?>>50 / page</option>
                                        <option value="100" <?= ($limit_param === '100') ? 'selected' : '' ?>>100 / page</option>
                                        <option value="250" <?= ($limit_param === '250') ? 'selected' : '' ?>>250 / page</option>
                                        <option value="all" <?= ($limit_param === 'all') ? 'selected' : '' ?>>All Items</option>
                                    </select>
                                </div>

                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="pm-btn pm-btn-primary" style="flex: 1;">
                                        <i class="fa fa-filter"></i> Filter
                                    </button>
                                    <a href="catagorysales_report.php" class="pm-btn pm-btn-secondary" title="Reset Filters">
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

                <!-- Category Scope Banner (if specific category chosen) -->
                <?php if ($is_specific_category): ?>
                <div class="pm-banner-card">
                    <div class="pm-banner-title">
                        <div class="pm-cat-badge">
                            <i class="fa fa-folder-open text-muted"></i>
                            <?= htmlspecialchars($selected_cat) ?>
                        </div>
                        <div style="font-size: 13px; color: var(--pm-slate-600);">
                            Found <strong><?= number_format($total_items_in_scope) ?></strong> matching sold items in this category.
                            <?php if ($search_query !== ''): ?> Filter: "<strong><?= htmlspecialchars($search_query) ?></strong>" | <?php endif; ?>
                            <?php if (!empty($from_date) || !empty($to_date)): ?>
                                Date: <strong><?= !empty($from_date) ? date('d M Y', strtotime($from_date)) : 'Start' ?></strong> to <strong><?= !empty($to_date) ? date('d M Y', strtotime($to_date)) : 'Present' ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <a href="catagorysales_report.php" class="pm-btn pm-btn-secondary pm-btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to All Categories
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Data Table Section -->
                <div class="pm-card" style="margin-bottom: 32px;">
                    <!-- Table Toolbar with In-Table Live Search -->
                    <div class="pm-table-toolbar">
                        <div class="pm-search-input-wrap">
                            <i class="fa fa-search"></i>
                            <input type="text" id="tableSearch" class="pm-input" 
                                   placeholder="Quick filter on this page or hit Enter to search all..." 
                                   value="<?= htmlspecialchars($search_query) ?>">
                        </div>
                        <div style="font-size: 12px; color: var(--pm-slate-500);">
                            <?php if ($is_specific_category && !$is_unlimited && $total_items_in_scope > $per_page): ?>
                                Showing <strong style="color: var(--pm-slate-900);"><?= min($total_items_in_scope, $offset + 1) ?> - <?= min($total_items_in_scope, $offset + count($report_rows)) ?></strong> of <strong><?= number_format($total_items_in_scope) ?></strong> items (Page <?= $current_page ?> of <?= $total_pages ?>)
                            <?php else: ?>
                                Showing <span id="visibleRowCount" style="font-weight: 700; color: var(--pm-slate-900);"><?= count($report_rows) ?></span> rows
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="pm-table-container">
                        <table class="pm-table" id="reportTable">
                            <?php if ($is_specific_category): ?>
                                <!-- Specific Category Item-Wise View -->
                                <thead>
                                    <tr>
                                        <th style="width: 50px;" class="text-center">#</th>
                                        <th style="width: 140px;">Item SKU / Code</th>
                                        <th>Description</th>
                                        <th class="text-right" style="width: 110px;">Retail Price</th>
                                        <th class="text-center" style="width: 100px;">Bills</th>
                                        <th class="text-center" style="width: 90px;">Sold Qty</th>
                                        <th class="text-right" style="width: 130px;">Sales Amount (₹)</th>
                                        <th class="text-right" style="width: 120px;">Avg / Unit (₹)</th>
                                        <th class="text-center" style="width: 130px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($report_rows)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center" style="padding: 48px; color: var(--pm-slate-400);">
                                                <i class="fa fa-inbox" style="font-size: 32px; margin-bottom: 12px; display: block;"></i>
                                                No sales records found matching your filter criteria.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $idx = $offset + 1; foreach ($report_rows as $row): 
                                            $s_amt = floatval($row['total_sales_amt'] ?? 0);
                                            $s_qty = intval($row['total_sold_qty'] ?? 0);
                                            $avg_u = ($s_qty > 0) ? ($s_amt / $s_qty) : 0;
                                            $search_text = strtolower(($row['item_id'] ?? '') . ' ' . ($row['description'] ?? ''));
                                        ?>
                                            <tr data-search="<?= htmlspecialchars($search_text) ?>">
                                                <td class="text-center font-mono text-muted"><?= $idx++ ?></td>
                                                <td>
                                                    <span class="pm-sku-pill">
                                                        <i class="fa fa-barcode" style="font-size: 10px; color: var(--pm-slate-400);"></i>
                                                        <?= htmlspecialchars($row['item_id'] ?? '') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="font-weight: 600; color: var(--pm-slate-800);">
                                                        <?= htmlspecialchars(!empty($row['description']) ? $row['description'] : ($row['item_id'] . ' (' . $selected_cat . ')')) ?>
                                                    </div>
                                                </td>
                                                <td class="text-right font-mono">
                                                    <?= floatval($row['unit_price'] ?? 0) > 0 ? ('₹ ' . number_format($row['unit_price'])) : '—' ?>
                                                </td>
                                                <td class="text-center font-mono">
                                                    <strong><?= intval($row['bill_count'] ?? 0) ?></strong>
                                                </td>
                                                <td class="text-center font-mono">
                                                    <?= number_format($s_qty) ?>
                                                </td>
                                                <td class="text-right font-mono" style="font-weight: 700; color: var(--pm-slate-900);">
                                                    ₹ <?= number_format($s_amt) ?>
                                                </td>
                                                <td class="text-right font-mono text-muted">
                                                    ₹ <?= number_format($avg_u) ?>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" 
                                                            onclick="openItemSalesHistory('<?= htmlspecialchars(addslashes($row['item_id'])) ?>');"
                                                            title="View customer sales breakdown">
                                                        <i class="fa fa-history"></i> Sales Ledger
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($report_rows)): ?>
                                <tfoot>
                                    <tr class="pm-table-footer">
                                        <td colspan="4" class="text-right">Category Scope Total (<?= number_format($total_items_in_scope) ?> Items):</td>
                                        <td class="text-center font-mono"><?= number_format($summary_stats['total_bills']) ?></td>
                                        <td class="text-center font-mono"><?= number_format($summary_stats['total_sold']) ?></td>
                                        <td class="text-right font-mono">₹ <?= number_format($summary_stats['total_sales']) ?></td>
                                        <td class="text-right font-mono">₹ <?= number_format($avg_sale_per_unit) ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>

                            <?php else: ?>
                                <!-- All Categories Overview / Leaderboard View -->
                                <thead>
                                    <tr>
                                        <th style="width: 50px;" class="text-center">Rank</th>
                                        <th>Category Name</th>
                                        <th class="text-center" style="width: 120px;">Unique SKUs</th>
                                        <th class="text-center" style="width: 120px;">Sales Bills</th>
                                        <th class="text-center" style="width: 120px;">Units Sold</th>
                                        <th class="text-right" style="width: 160px;">Total Sales Revenue</th>
                                        <th style="width: 160px;">Revenue Share</th>
                                        <th class="text-center" style="width: 140px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($report_rows)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center" style="padding: 48px; color: var(--pm-slate-400);">
                                                <i class="fa fa-inbox" style="font-size: 32px; margin-bottom: 12px; display: block;"></i>
                                                No category sales records found for the selected date range.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                        $rank = 1; 
                                        $total_sales_universe = ($summary_stats['total_sales'] > 0) ? $summary_stats['total_sales'] : 1;
                                        foreach ($report_rows as $cat_row): 
                                            $cat_name = $cat_row['category'];
                                            $share_pct = round((floatval($cat_row['total_sales_amt']) / $total_sales_universe) * 100, 1);
                                            $cat_search = strtolower($cat_name);
                                        ?>
                                            <tr data-search="<?= htmlspecialchars($cat_search) ?>">
                                                <td class="text-center font-mono text-muted"><?= $rank++ ?></td>
                                                <td>
                                                    <a href="catagorysales_report.php?cate=<?= urlencode($cat_name) ?>&frmdate=<?= urlencode($from_date) ?>&todate=<?= urlencode($to_date) ?>" 
                                                       style="font-weight: 700; color: var(--pm-slate-900); text-decoration: none;"
                                                       class="pm-cat-link">
                                                        <i class="fa fa-folder" style="color: var(--pm-slate-400); margin-right: 6px;"></i>
                                                        <?= htmlspecialchars($cat_name) ?>
                                                    </a>
                                                </td>
                                                <td class="text-center font-mono">
                                                    <?= number_format($cat_row['unique_items'] ?? 0) ?>
                                                </td>
                                                <td class="text-center font-mono">
                                                    <?= number_format($cat_row['bill_count'] ?? 0) ?>
                                                </td>
                                                <td class="text-center font-mono">
                                                    <?= number_format($cat_row['total_sold_qty'] ?? 0) ?>
                                                </td>
                                                <td class="text-right font-mono" style="font-weight: 700; color: var(--pm-slate-900);">
                                                    ₹ <?= number_format($cat_row['total_sales_amt'] ?? 0) ?>
                                                </td>
                                                <td>
                                                    <div class="pm-progress-wrap">
                                                        <div class="pm-progress-bar">
                                                            <div class="pm-progress-fill" style="width: <?= min(100, $share_pct) ?>%;"></div>
                                                        </div>
                                                        <span class="font-mono text-muted" style="font-size: 11px; width: 38px; text-align: right;"><?= $share_pct ?>%</span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <a href="catagorysales_report.php?cate=<?= urlencode($cat_name) ?>&frmdate=<?= urlencode($from_date) ?>&todate=<?= urlencode($to_date) ?>" 
                                                       class="pm-btn pm-btn-secondary pm-btn-sm">
                                                        View Items <i class="fa fa-chevron-right" style="font-size: 10px; margin-left: 2px;"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($report_rows)): ?>
                                <tfoot>
                                    <tr class="pm-table-footer">
                                        <td colspan="3" class="text-right">Total Aggregate:</td>
                                        <td class="text-center font-mono"><?= number_format($summary_stats['total_bills']) ?></td>
                                        <td class="text-center font-mono"><?= number_format($summary_stats['total_sold']) ?></td>
                                        <td class="text-right font-mono">₹ <?= number_format($summary_stats['total_sales']) ?></td>
                                        <td>100.0%</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            <?php endif; ?>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <?php if ($is_specific_category && !$is_unlimited && $total_pages > 1): ?>
                    <div class="pm-pagination-bar">
                        <div style="font-size: 13px; color: var(--pm-slate-500);">
                            Page <strong style="color: var(--pm-slate-900);"><?= $current_page ?></strong> of <strong><?= $total_pages ?></strong>
                        </div>
                        <div class="pm-page-links">
                            <a href="<?= get_page_url(1) ?>" class="pm-page-link <?= ($current_page <= 1) ? 'disabled' : '' ?>" title="First Page">
                                <i class="fa fa-angle-double-left"></i>
                            </a>
                            <a href="<?= get_page_url($current_page - 1) ?>" class="pm-page-link <?= ($current_page <= 1) ? 'disabled' : '' ?>" title="Previous Page">
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
                                <a href="<?= get_page_url($p) ?>" class="pm-page-link <?= ($p === $current_page) ? 'active' : '' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>
                            <?php if ($end_p < $total_pages): ?>
                                <span style="padding: 0 4px; color: var(--pm-slate-400);">...</span>
                            <?php endif; ?>

                            <a href="<?= get_page_url($current_page + 1) ?>" class="pm-page-link <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>" title="Next Page">
                                <i class="fa fa-angle-right"></i>
                            </a>
                            <a href="<?= get_page_url($total_pages) ?>" class="pm-page-link <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>" title="Last Page">
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

<!-- Modal: Item Sales Breakdown / Ledger -->
<div class="pm-modal-overlay" id="itemHistoryModal">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h3 class="pm-modal-title">
                <i class="fa fa-receipt text-muted"></i>
                <span id="modalSkuTitle">Sales Ledger</span>
            </h3>
            <button type="button" class="pm-modal-close" onclick="closeItemModal();">&times;</button>
        </div>
        <div class="pm-modal-body" id="modalBody">
            <div style="text-align: center; padding: 40px; color: var(--pm-slate-400);">
                <i class="fa fa-circle-notch fa-spin fa-2x"></i>
                <p style="margin-top: 10px; font-size: 13px;">Loading sales transaction ledger...</p>
            </div>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" onclick="closeItemModal();">Close</button>
        </div>
    </div>
</div>

<script>
// Filter Table dynamically with 200ms debounce
var searchDebounceTimer = null;
var inputSearchEl = document.getElementById("tableSearch");

if (inputSearchEl) {
    // If Enter key pressed, perform full server-side category search
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

    // Debounced client-side filtering for visible page rows
    inputSearchEl.addEventListener("input", function() {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(function() {
            var filter = inputSearchEl.value.toLowerCase().trim();
            var table = document.getElementById("reportTable");
            if (!table) return;
            var tbody = table.getElementsByTagName("tbody")[0];
            if (!tbody) return;
            var trs = tbody.getElementsByTagName("tr");
            var visibleCount = 0;

            for (var i = 0; i < trs.length; i++) {
                var searchData = trs[i].getAttribute("data-search") || trs[i].textContent.toLowerCase();
                if (searchData.indexOf(filter) > -1) {
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

// Open Item Sales History Modal via AJAX
function openItemSalesHistory(sku) {
    var modal = document.getElementById('itemHistoryModal');
    var modalBody = document.getElementById('modalBody');
    var title = document.getElementById('modalSkuTitle');

    title.innerHTML = 'Sales Ledger for SKU: <span class="pm-sku-pill">' + sku + '</span>';
    modalBody.innerHTML = '<div style="text-align:center; padding:40px; color:var(--pm-slate-400);"><i class="fa fa-circle-notch fa-spin fa-2x"></i><p style="margin-top:10px; font-size:13px;">Loading sales transaction ledger...</p></div>';
    modal.style.display = 'flex';

    fetch('catagorysales_report.php?action=item_history&sku=' + encodeURIComponent(sku))
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data.success) {
                modalBody.innerHTML = '<div style="padding:20px; color:#ef4444;"><i class="fa fa-exclamation-triangle"></i> ' + (data.message || 'Error loading records') + '</div>';
                return;
            }

            var html = '';
            html += '<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:18px;">';
            html += '  <div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); padding:10px 14px; border-radius:6px;">';
            html += '    <div style="font-size:11px; color:var(--pm-slate-500); font-weight:600; text-transform:uppercase;">Sales Bills</div>';
            html += '    <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">' + data.total_bills + '</div>';
            html += '  </div>';
            html += '  <div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); padding:10px 14px; border-radius:6px;">';
            html += '    <div style="font-size:11px; color:var(--pm-slate-500); font-weight:600; text-transform:uppercase;">Net Units Sold</div>';
            html += '    <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">' + data.total_sold_qty + '</div>';
            html += '  </div>';
            html += '  <div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); padding:10px 14px; border-radius:6px;">';
            html += '    <div style="font-size:11px; color:var(--pm-slate-500); font-weight:600; text-transform:uppercase;">Total Sales Revenue</div>';
            html += '    <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">₹ ' + Number(data.total_sales).toLocaleString('en-IN') + '</div>';
            html += '  </div>';
            html += '  <div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); padding:10px 14px; border-radius:6px;">';
            html += '    <div style="font-size:11px; color:var(--pm-slate-500); font-weight:600; text-transform:uppercase;">Avg / Unit Sold</div>';
            var avgPerU = (data.total_sold_qty > 0) ? (data.total_sales / data.total_sold_qty) : 0;
            html += '    <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">₹ ' + Math.round(avgPerU).toLocaleString('en-IN') + '</div>';
            html += '  </div>';
            html += '</div>';

            if (!data.history || data.history.length === 0) {
                html += '<div style="text-align:center; padding:30px; color:var(--pm-slate-500);">No sales transactions recorded for this SKU.</div>';
            } else {
                html += '<div style="overflow-x:auto; border:1px solid var(--pm-slate-200); border-radius:6px;">';
                html += '<table class="pm-table" style="margin:0;">';
                html += '<thead><tr>';
                html += '<th style="width:40px;" class="text-center">#</th>';
                html += '<th>Bill Number</th>';
                html += '<th>Bill Date</th>';
                html += '<th>Customer Details</th>';
                html += '<th class="text-center">Gross Qty</th>';
                html += '<th class="text-center">Return Qty</th>';
                html += '<th class="text-center">Net Sold</th>';
                html += '<th class="text-right">Net Amount (₹)</th>';
                html += '</tr></thead><tbody>';

                data.history.forEach(function(item, idx) {
                    html += '<tr>';
                    html += '<td class="text-center font-mono text-muted">' + (idx + 1) + '</td>';
                    html += '<td><span class="font-mono" style="font-weight:600;">' + item.bill_no + '</span></td>';
                    html += '<td class="font-mono text-muted">' + item.bill_date + '</td>';
                    html += '<td><div><strong>' + item.cust_name + '</strong></div><div style="font-size:11px; color:var(--pm-slate-500);"><i class="fa fa-phone"></i> ' + item.cust_phone + '</div></td>';
                    html += '<td class="text-center font-mono">' + item.qty + '</td>';
                    html += '<td class="text-center font-mono text-muted">' + item.return_qty + '</td>';
                    html += '<td class="text-center font-mono" style="font-weight:700;">' + item.sold_qty + '</td>';
                    html += '<td class="text-right font-mono" style="font-weight:600;">₹ ' + Number(item.net_amount).toLocaleString('en-IN') + '</td>';
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

function closeItemModal() {
    var modal = document.getElementById('itemHistoryModal');
    if (modal) modal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('itemHistoryModal');
    if (event.target === modal) {
        closeItemModal();
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
    downloadLink.download = "Category_Sales_Report_" + (new Date().toISOString().slice(0, 10)) + ".csv";
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