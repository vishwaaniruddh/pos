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
// AJAX ENDPOINT: Fetch Detailed Rental History for a Specific SKU
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
            od.bill_id,
            od.qty,
            od.rent,
            od.deposit,
            od.pickup_date,
            od.return_date,
            r.new_bill_number,
            r.bill_date,
            r.booking_status,
            cust.person_id,
            cust.first_name,
            cust.last_name,
            cust.phone_number
        FROM order_detail od
        JOIN phppos_rent r ON od.bill_id = r.bill_id
        LEFT JOIN phppos_people cust ON r.cust_id = cust.person_id
        WHERE od.item_id = '$sku_esc'
        ORDER BY r.bill_date DESC, od.bill_id DESC
    ";

    $res_hist = mysqli_query($con, $sql_hist);
    $history = [];
    $total_rent_item = 0;
    $total_qty_item  = 0;
    $total_dep_item  = 0;

    if ($res_hist) {
        while ($row = mysqli_fetch_assoc($res_hist)) {
            $rent_val = floatval($row['rent'] ?? 0);
            $qty_val  = intval($row['qty'] ?? 1);
            $dep_val  = floatval($row['deposit'] ?? 0);

            $total_rent_item += $rent_val;
            $total_qty_item  += $qty_val;
            $total_dep_item  += $dep_val;

            $history[] = [
                'bill_id'         => $row['bill_id'],
                'bill_no'         => !empty($row['new_bill_number']) ? $row['new_bill_number'] : ('BILL-' . $row['bill_id']),
                'bill_date'       => ($row['bill_date'] && $row['bill_date'] !== '0000-00-00') ? date('d M Y', strtotime($row['bill_date'])) : '—',
                'booking_status'  => !empty($row['booking_status']) ? $row['booking_status'] : 'Pending',
                'cust_name'       => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: 'Guest Customer',
                'cust_phone'      => $row['phone_number'] ?? '—',
                'qty'             => $qty_val,
                'rent'            => $rent_val,
                'deposit'         => $dep_val,
                'pickup_date'     => ($row['pickup_date'] && $row['pickup_date'] !== '0000-00-00') ? date('d M Y', strtotime($row['pickup_date'])) : '—',
                'return_date'     => ($row['return_date'] && $row['return_date'] !== '0000-00-00') ? date('d M Y', strtotime($row['return_date'])) : '—',
            ];
        }
    }

    echo json_encode([
        'success'        => true,
        'sku'            => $sku,
        'meta'           => $item_meta,
        'total_bookings' => count($history),
        'total_qty'      => $total_qty_item,
        'total_rent'     => $total_rent_item,
        'total_deposit'  => $total_dep_item,
        'history'        => $history
    ]);
    exit;
}

// -------------------------------------------------------------
// MAIN PAGE DATA PROCESSING & FILTERS
// -------------------------------------------------------------
$search_sku     = safe_str($_GET['item_id'] ?? ($_GET['sku'] ?? ''));
$selected_cat   = safe_str($_GET['category'] ?? '');
$booking_status = safe_str($_GET['status'] ?? '');
$from_date      = parse_date_to_db($_GET['frmdate'] ?? ($_GET['from'] ?? ''));
$to_date        = parse_date_to_db($_GET['todate'] ?? ($_GET['to'] ?? ''));

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

// Overall Inventory Rental KPI Metrics
$kpi_total_skus     = 0;
$kpi_total_rent_rev = 0;
$kpi_total_units    = 0;
$kpi_total_bookings = 0;

if ($con) {
    $q_kpi = mysqli_query($con, "
        SELECT 
            COUNT(DISTINCT od.item_id) as total_skus,
            COUNT(DISTINCT od.bill_id) as total_bookings,
            COALESCE(SUM(od.qty), 0) as total_units,
            COALESCE(SUM(od.rent), 0) as total_rent
        FROM order_detail od
        WHERE od.item_id != '' AND od.item_id IS NOT NULL
    ");
    if ($q_kpi && $rk = mysqli_fetch_assoc($q_kpi)) {
        $kpi_total_skus     = intval($rk['total_skus'] ?? 0);
        $kpi_total_bookings = intval($rk['total_bookings'] ?? 0);
        $kpi_total_units    = intval($rk['total_units'] ?? 0);
        $kpi_total_rent_rev = floatval($rk['total_rent'] ?? 0);
    }
}

// Build query conditions
$has_single_item = ($search_sku !== '');
$has_date_filter = (!empty($from_date) || !empty($to_date));
$has_status_filter = (!empty($booking_status) && $booking_status !== 'all');
$has_cat_filter = (!empty($selected_cat) && $selected_cat !== 'all');

$where_clauses = ["od.item_id != ''", "od.item_id IS NOT NULL"];
$needs_rent_join = $has_date_filter || $has_status_filter;

if ($has_single_item) {
    $sku_esc = mysqli_real_escape_string($con, $search_sku);
    $where_clauses[] = "od.item_id LIKE '%$sku_esc%'";
}

if ($has_status_filter) {
    $st_esc = mysqli_real_escape_string($con, $booking_status);
    $where_clauses[] = "r.booking_status = '$st_esc'";
}

if (!empty($from_date) && !empty($to_date)) {
    $where_clauses[] = "r.bill_date BETWEEN '$from_date' AND '$to_date'";
} elseif (!empty($from_date)) {
    $where_clauses[] = "r.bill_date >= '$from_date'";
} elseif (!empty($to_date)) {
    $where_clauses[] = "r.bill_date <= '$to_date'";
}

// Category filter
if ($has_cat_filter) {
    $cat_esc = mysqli_real_escape_string($con, $selected_cat);
    // Fetch items belonging to category
    $q_citems = mysqli_query($con, "SELECT name FROM phppos_items WHERE category = '$cat_esc'");
    $c_skus = [];
    if ($q_citems) {
        while ($rc = mysqli_fetch_row($q_citems)) {
            $c_skus[] = "'" . mysqli_real_escape_string($con, $rc[0]) . "'";
        }
    }
    if (!empty($c_skus)) {
        $where_clauses[] = "od.item_id IN (" . implode(',', $c_skus) . ")";
    } else {
        $where_clauses[] = "1=0";
    }
}

$join_rent_sql = $needs_rent_join ? "JOIN phppos_rent r ON od.bill_id = r.bill_id" : "";
$where_sql = implode(" AND ", $where_clauses);
$limit_sql = ($has_single_item || $has_cat_filter) ? "" : "LIMIT 250";

$report_items = [];
$scope_total_rent     = 0;
$scope_total_units    = 0;
$scope_total_bookings = 0;
$scope_total_deposit  = 0;

if ($con) {
    $main_sql = "
        SELECT 
            od.item_id,
            COUNT(DISTINCT od.bill_id) as booking_count,
            COALESCE(SUM(od.qty), 0) as total_qty,
            COALESCE(SUM(od.rent), 0) as total_rent,
            COALESCE(SUM(od.deposit), 0) as total_deposit
        FROM order_detail od
        $join_rent_sql
        WHERE $where_sql
        GROUP BY od.item_id
        ORDER BY total_rent DESC, booking_count DESC
        $limit_sql
    ";

    $res_main = mysqli_query($con, $main_sql);
    $item_ids_to_fetch = [];

    if ($res_main) {
        while ($row = mysqli_fetch_assoc($res_main)) {
            $report_items[$row['item_id']] = $row;
            $item_ids_to_fetch[] = "'" . mysqli_real_escape_string($con, $row['item_id']) . "'";
            $scope_total_rent     += floatval($row['total_rent'] ?? 0);
            $scope_total_units    += intval($row['total_qty'] ?? 0);
            $scope_total_bookings += intval($row['booking_count'] ?? 0);
            $scope_total_deposit  += floatval($row['total_deposit'] ?? 0);
        }
    }

    // Enrich with item metadata in one fast lookup
    if (!empty($item_ids_to_fetch)) {
        $meta_sql = "SELECT name, category, unit_price, cost_price, description FROM phppos_items WHERE name IN (" . implode(',', $item_ids_to_fetch) . ")";
        $res_meta = mysqli_query($con, $meta_sql);
        if ($res_meta) {
            while ($m = mysqli_fetch_assoc($res_meta)) {
                if (isset($report_items[$m['name']])) {
                    $report_items[$m['name']]['category']    = $m['category'];
                    $report_items[$m['name']]['unit_price']  = $m['unit_price'];
                    $report_items[$m['name']]['cost_price']  = $m['cost_price'];
                    $report_items[$m['name']]['description'] = $m['description'];
                }
            }
        }
    }
}

// Single item detailed ledger if exact match exists
$single_item_profile = null;
$single_item_history = [];
if ($has_single_item && count($report_items) === 1) {
    $single_item_profile = reset($report_items);
    $exact_sku = $single_item_profile['item_id'];
    $sku_esc = mysqli_real_escape_string($con, $exact_sku);

    $sql_single_hist = "
        SELECT 
            od.bill_id,
            od.qty,
            od.rent,
            od.deposit,
            od.pickup_date,
            od.return_date,
            r.new_bill_number,
            r.bill_date,
            r.booking_status,
            cust.person_id,
            cust.first_name,
            cust.last_name,
            cust.phone_number
        FROM order_detail od
        JOIN phppos_rent r ON od.bill_id = r.bill_id
        LEFT JOIN phppos_people cust ON r.cust_id = cust.person_id
        WHERE od.item_id = '$sku_esc'
        ORDER BY r.bill_date DESC, od.bill_id DESC
    ";
    $res_sh = mysqli_query($con, $sql_single_hist);
    if ($res_sh) {
        while ($b = mysqli_fetch_assoc($res_sh)) {
            $single_item_history[] = $b;
        }
    }
}

$has_filter = $has_single_item || $has_cat_filter || $has_status_filter || $has_date_filter;

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

                .itemrent-container {
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

                /* SKU Badge */
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

                .pm-status-pill {
                    display: inline-flex;
                    align-items: center;
                    padding: 2px 8px;
                    border-radius: 9999px;
                    font-size: 11px;
                    font-weight: 500;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
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
                    width: 320px;
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
                    .navbar, #sidebar, .pm-page-header .pm-btn, .pm-card, .pm-table-toolbar, .pm-btn, .pm-modal-overlay {
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
                    .itemrent-container {
                        max-width: 100% !important;
                    }
                }
            </style>

            <div class="itemrent-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-gem" style="margin-right: 10px; font-size: 18px; color: var(--pm-slate-700);"></i>
                            Consolidated Item Rent Report
                        </h1>
                        <p class="pm-page-subtitle">
                            Analyze individual item rental turnover, utilization frequency, earnings, and booking history.
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
                            <span class="pm-kpi-label">Active Rental SKUs</span>
                            <div class="pm-kpi-icon"><i class="fa fa-barcode"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($has_filter ? count($report_items) : $kpi_total_skus) ?></div>
                        <div class="pm-kpi-sub">
                            <?= $has_filter ? ('Matching criteria (' . count($report_items) . ' shown)') : ('Total rental catalog: ' . number_format($kpi_total_skus) . ' SKUs') ?>
                        </div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Rental Turnover (Revenue)</span>
                            <div class="pm-kpi-icon"><i class="fa fa-inr"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($has_filter ? $scope_total_rent : $kpi_total_rent_rev) ?></div>
                        <div class="pm-kpi-sub">
                            <?= $has_filter ? 'Rent generated in scope' : 'Storewide rental revenue accrued' ?>
                        </div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Dispatched Units (Qty)</span>
                            <div class="pm-kpi-icon"><i class="fa fa-cubes"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($has_filter ? $scope_total_units : $kpi_total_units) ?></div>
                        <div class="pm-kpi-sub">Individual pieces rented out</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Booking Invoices</span>
                            <div class="pm-kpi-icon"><i class="fa fa-receipt"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($has_filter ? $scope_total_bookings : $kpi_total_bookings) ?></div>
                        <div class="pm-kpi-sub">Distinct rental orders processed</div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="pm-card">
                    <div class="pm-card-body">
                        <form method="GET" action="item_rent_report.php" id="filterForm">
                            <div class="pm-filter-grid">
                                <div class="pm-form-group">
                                    <label class="pm-label" for="item_id">Item SKU / Code</label>
                                    <input type="text" name="item_id" id="item_id" class="pm-input" 
                                           placeholder="e.g. T1086, YNL238XL, B254..." 
                                           value="<?= htmlspecialchars($search_sku) ?>">
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="category">Category</label>
                                    <select name="category" id="category" class="pm-select">
                                        <option value="all" <?= (!$has_cat_filter || $selected_cat === 'all') ? 'selected' : '' ?>>All Categories</option>
                                        <?php foreach ($categories_list as $cat_opt): ?>
                                            <option value="<?= htmlspecialchars($cat_opt) ?>" <?= ($selected_cat === $cat_opt) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat_opt) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="status">Booking Status</label>
                                    <select name="status" id="status" class="pm-select">
                                        <option value="all" <?= (!$has_status_filter || $booking_status === 'all') ? 'selected' : '' ?>>All Statuses</option>
                                        <option value="Returned" <?= ($booking_status === 'Returned') ? 'selected' : '' ?>>Returned</option>
                                        <option value="Picked" <?= ($booking_status === 'Picked') ? 'selected' : '' ?>>Picked</option>
                                        <option value="Booked" <?= ($booking_status === 'Booked') ? 'selected' : '' ?>>Booked / Pending</option>
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

                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="pm-btn pm-btn-primary" style="flex: 1;">
                                        <i class="fa fa-filter"></i> Apply
                                    </button>
                                    <a href="item_rent_report.php" class="pm-btn pm-btn-secondary" title="Reset Filters">
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

                <!-- Single Item Profile Banner (if exact SKU match found) -->
                <?php if ($single_item_profile): ?>
                <div style="background: #ffffff; border: 1px solid var(--pm-slate-200); border-radius: 8px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid var(--pm-slate-200);">
                        <div>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                                <span class="pm-sku-pill" style="font-size: 15px; padding: 4px 12px;">
                                    <i class="fa fa-barcode text-muted"></i> <?= htmlspecialchars($single_item_profile['item_id']) ?>
                                </span>
                                <span class="pm-status-pill"><?= htmlspecialchars($single_item_profile['category'] ?? 'General') ?></span>
                            </div>
                            <div style="font-size: 13px; color: var(--pm-slate-500);">
                                <?= htmlspecialchars($single_item_profile['description'] ?? 'Inventory Item') ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <a href="item_rent_report.php" class="pm-btn pm-btn-secondary pm-btn-sm">
                                <i class="fa fa-arrow-left"></i> Back to All Items
                            </a>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px;">
                        <div style="background: var(--pm-slate-50); border: 1px solid var(--pm-slate-200); border-radius: 6px; padding: 12px 14px;">
                            <div style="font-size: 11px; color: var(--pm-slate-500); font-weight: 600; text-transform: uppercase;">Retail Price</div>
                            <div style="font-size: 17px; font-weight: 700; color: var(--pm-slate-900);">
                                <?= floatval($single_item_profile['unit_price'] ?? 0) > 0 ? ('₹ ' . number_format($single_item_profile['unit_price'])) : '—' ?>
                            </div>
                        </div>
                        <div style="background: var(--pm-slate-50); border: 1px solid var(--pm-slate-200); border-radius: 6px; padding: 12px 14px;">
                            <div style="font-size: 11px; color: var(--pm-slate-500); font-weight: 600; text-transform: uppercase;">Cost Price</div>
                            <div style="font-size: 17px; font-weight: 700; color: var(--pm-slate-900);">
                                <?= floatval($single_item_profile['cost_price'] ?? 0) > 0 ? ('₹ ' . number_format($single_item_profile['cost_price'])) : '—' ?>
                            </div>
                        </div>
                        <div style="background: var(--pm-slate-50); border: 1px solid var(--pm-slate-200); border-radius: 6px; padding: 12px 14px;">
                            <div style="font-size: 11px; color: var(--pm-slate-500); font-weight: 600; text-transform: uppercase;">Lifetime Bookings</div>
                            <div style="font-size: 17px; font-weight: 700; color: var(--pm-slate-900);"><?= number_format($single_item_profile['booking_count']) ?></div>
                        </div>
                        <div style="background: var(--pm-slate-50); border: 1px solid var(--pm-slate-200); border-radius: 6px; padding: 12px 14px;">
                            <div style="font-size: 11px; color: var(--pm-slate-500); font-weight: 600; text-transform: uppercase;">Total Rent Earned</div>
                            <div style="font-size: 17px; font-weight: 700; color: var(--pm-slate-900);">₹ <?= number_format($single_item_profile['total_rent']) ?></div>
                        </div>
                        <div style="background: var(--pm-slate-50); border: 1px solid var(--pm-slate-200); border-radius: 6px; padding: 12px 14px;">
                            <div style="font-size: 11px; color: var(--pm-slate-500); font-weight: 600; text-transform: uppercase;">Deposit Handled</div>
                            <div style="font-size: 17px; font-weight: 700; color: var(--pm-slate-900);">₹ <?= number_format($single_item_profile['total_deposit']) ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Table Section -->
                <div class="pm-card" style="margin-bottom: 32px;">
                    <!-- Table Toolbar with In-Table Live Search -->
                    <div class="pm-table-toolbar">
                        <div class="pm-search-input-wrap">
                            <i class="fa fa-search"></i>
                            <input type="text" id="tableSearch" class="pm-input" placeholder="Search SKU, category, customer in table..." onkeyup="filterItemTable()">
                        </div>
                        <div style="font-size: 12px; color: var(--pm-slate-500);">
                            Showing <span id="visibleRowCount" style="font-weight: 700; color: var(--pm-slate-900);">
                                <?= $single_item_profile ? count($single_item_history) : count($report_items) ?>
                            </span> rows
                        </div>
                    </div>

                    <div class="pm-table-container">
                        <table class="pm-table" id="reportTable">
                            <?php if ($single_item_profile): ?>
                                <!-- Single Item Booking Ledger -->
                                <thead>
                                    <tr>
                                        <th style="width: 45px;" class="text-center">#</th>
                                        <th>Bill Number</th>
                                        <th>Bill Date</th>
                                        <th>Customer Details</th>
                                        <th class="text-center" style="width: 100px;">Status</th>
                                        <th class="text-center" style="width: 90px;">Qty</th>
                                        <th class="text-right" style="width: 130px;">Rent (₹)</th>
                                        <th class="text-right" style="width: 130px;">Deposit (₹)</th>
                                        <th class="text-center" style="width: 130px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($single_item_history)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center" style="padding: 48px; color: var(--pm-slate-400);">
                                                <i class="fa fa-receipt" style="font-size: 32px; margin-bottom: 12px; display: block;"></i>
                                                No rental bookings recorded for this SKU.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $idx = 1; foreach ($single_item_history as $b): 
                                            $b_no = !empty($b['new_bill_number']) ? $b['new_bill_number'] : ('BILL-' . $b['bill_id']);
                                            $c_name = trim(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? '')) ?: 'Guest Customer';
                                            $c_phone = trim((string)($b['phone_number'] ?? ''));
                                            $clean_p = preg_replace('/[^0-9]/', '', $c_phone);
                                            if (strlen($clean_p) === 10) $clean_p = '91' . $clean_p;
                                        ?>
                                            <tr>
                                                <td class="text-center font-mono text-muted"><?= $idx++ ?></td>
                                                <td><span class="font-mono" style="font-weight: 700;"><?= htmlspecialchars($b_no) ?></span></td>
                                                <td class="font-mono text-muted">
                                                    <?= (!empty($b['bill_date']) && $b['bill_date'] !== '0000-00-00') ? date('d M Y', strtotime($b['bill_date'])) : '—' ?>
                                                </td>
                                                <td>
                                                    <div style="font-weight: 600; color: var(--pm-slate-800);"><?= htmlspecialchars($c_name) ?></div>
                                                    <?php if ($c_phone !== ''): ?>
                                                        <div style="font-size: 11px; color: var(--pm-slate-500); display: flex; align-items: center; gap: 4px;">
                                                            <i class="fa fa-phone" style="font-size: 9px;"></i> <?= htmlspecialchars($c_phone) ?>
                                                            <a href="https://wa.me/<?= htmlspecialchars($clean_p) ?>" target="_blank" style="color: #10b981; margin-left: 2px;">
                                                                <i class="fa-brands fa-whatsapp"></i>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="pm-status-pill"><?= htmlspecialchars($b['booking_status'] ?? 'Pending') ?></span>
                                                </td>
                                                <td class="text-center font-mono"><?= intval($b['qty'] ?? 1) ?></td>
                                                <td class="text-right font-mono" style="font-weight: 700; color: var(--pm-slate-900);">
                                                    ₹ <?= number_format($b['rent'] ?? 0) ?>
                                                </td>
                                                <td class="text-right font-mono text-muted">
                                                    <?= floatval($b['deposit'] ?? 0) > 0 ? ('₹ ' . number_format($b['deposit'])) : '—' ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="/pos/reports/rent_report_detail.php?id=<?= $b['bill_id'] ?>" target="_blank" class="pm-btn pm-btn-secondary pm-btn-sm" style="font-size: 11px; padding: 2px 8px; height: 26px;">
                                                        <i class="fa fa-external-link"></i> View Bill
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($single_item_history)): ?>
                                <tfoot>
                                    <tr class="pm-table-footer">
                                        <td colspan="5" class="text-right">Total Summary:</td>
                                        <td class="text-center font-mono"><?= number_format($single_item_profile['total_qty']) ?></td>
                                        <td class="text-right font-mono">₹ <?= number_format($single_item_profile['total_rent']) ?></td>
                                        <td class="text-right font-mono">₹ <?= number_format($single_item_profile['total_deposit']) ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>

                            <?php else: ?>
                                <!-- Items Leaderboard View -->
                                <thead>
                                    <tr>
                                        <th style="width: 50px;" class="text-center">#</th>
                                        <th style="width: 140px;">SKU / Code</th>
                                        <th style="width: 130px;">Category</th>
                                        <th class="text-right" style="width: 110px;">Retail (₹)</th>
                                        <th class="text-center" style="width: 100px;">Bookings</th>
                                        <th class="text-center" style="width: 90px;">Units</th>
                                        <th class="text-right" style="width: 140px;">Total Rent (₹)</th>
                                        <th class="text-right" style="width: 130px;">Deposit (₹)</th>
                                        <th class="text-center" style="width: 140px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($report_items)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center" style="padding: 48px; color: var(--pm-slate-400);">
                                                <i class="fa fa-box-open" style="font-size: 32px; margin-bottom: 12px; display: block;"></i>
                                                No rental records match the selected SKU or filter criteria.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $idx = 1; foreach ($report_items as $item): ?>
                                            <tr>
                                                <td class="text-center font-mono text-muted"><?= $idx++ ?></td>
                                                <td>
                                                    <span class="pm-sku-pill">
                                                        <i class="fa fa-barcode" style="font-size: 10px; color: var(--pm-slate-400);"></i>
                                                        <?= htmlspecialchars($item['item_id']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="pm-status-pill"><?= htmlspecialchars($item['category'] ?? 'General') ?></span>
                                                </td>
                                                <td class="text-right font-mono">
                                                    <?= floatval($item['unit_price'] ?? 0) > 0 ? ('₹ ' . number_format($item['unit_price'])) : '—' ?>
                                                </td>
                                                <td class="text-center font-mono">
                                                    <strong><?= number_format($item['booking_count'] ?? 0) ?></strong>
                                                </td>
                                                <td class="text-center font-mono">
                                                    <?= number_format($item['total_qty'] ?? 0) ?>
                                                </td>
                                                <td class="text-right font-mono" style="font-weight: 700; color: var(--pm-slate-900);">
                                                    ₹ <?= number_format($item['total_rent'] ?? 0) ?>
                                                </td>
                                                <td class="text-right font-mono text-muted">
                                                    <?= floatval($item['total_deposit'] ?? 0) > 0 ? ('₹ ' . number_format($item['total_deposit'])) : '—' ?>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm"
                                                            onclick="openItemRentalHistory('<?= htmlspecialchars(addslashes($item['item_id'])) ?>');"
                                                            title="View customer booking ledger for this item">
                                                        <i class="fa fa-history"></i> Rental Ledger
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($report_items)): ?>
                                <tfoot>
                                    <tr class="pm-table-footer">
                                        <td colspan="4" class="text-right">Scope Total:</td>
                                        <td class="text-center font-mono"><?= number_format($scope_total_bookings) ?></td>
                                        <td class="text-center font-mono"><?= number_format($scope_total_units) ?></td>
                                        <td class="text-right font-mono">₹ <?= number_format($scope_total_rent) ?></td>
                                        <td class="text-right font-mono">₹ <?= number_format($scope_total_deposit) ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal: Item Rental Breakdown / History -->
<div class="pm-modal-overlay" id="itemHistoryModal">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h3 class="pm-modal-title">
                <i class="fa fa-history text-muted"></i>
                <span id="modalSkuTitle">Rental Ledger</span>
            </h3>
            <button type="button" class="pm-modal-close" onclick="closeItemModal();">&times;</button>
        </div>
        <div class="pm-modal-body" id="modalBody">
            <div style="text-align: center; padding: 40px; color: var(--pm-slate-400);">
                <i class="fa fa-circle-notch fa-spin fa-2x"></i>
                <p style="margin-top: 10px; font-size: 13px;">Loading rental transaction ledger...</p>
            </div>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" onclick="closeItemModal();">Close</button>
        </div>
    </div>
</div>

<script>
// Filter Table dynamically
function filterItemTable() {
    var input = document.getElementById("tableSearch");
    var filter = input.value.toLowerCase().trim();
    var table = document.getElementById("reportTable");
    var tbody = table.getElementsByTagName("tbody")[0];
    var trs = tbody.getElementsByTagName("tr");
    var visibleCount = 0;

    for (var i = 0; i < trs.length; i++) {
        var text = trs[i].innerText.toLowerCase();
        if (text.indexOf(filter) > -1) {
            trs[i].style.display = "";
            visibleCount++;
        } else {
            trs[i].style.display = "none";
        }
    }
    var countEl = document.getElementById("visibleRowCount");
    if (countEl) countEl.innerText = visibleCount;
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

// Open Item Rental History Modal via AJAX
function openItemRentalHistory(sku) {
    var modal = document.getElementById('itemHistoryModal');
    var modalBody = document.getElementById('modalBody');
    var title = document.getElementById('modalSkuTitle');

    title.innerHTML = 'Rental History for SKU: <span class="pm-sku-pill">' + sku + '</span>';
    modalBody.innerHTML = '<div style="text-align:center; padding:40px; color:var(--pm-slate-400);"><i class="fa fa-circle-notch fa-spin fa-2x"></i><p style="margin-top:10px; font-size:13px;">Loading rental transaction ledger...</p></div>';
    modal.style.display = 'flex';

    fetch('item_rent_report.php?action=item_history&sku=' + encodeURIComponent(sku))
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data.success) {
                modalBody.innerHTML = '<div style="padding:20px; color:#ef4444;"><i class="fa fa-exclamation-triangle"></i> ' + (data.message || 'Error loading records') + '</div>';
                return;
            }

            var html = '';
            html += '<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:18px;">';
            html += '  <div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); padding:10px 14px; border-radius:6px;">';
            html += '    <div style="font-size:11px; color:var(--pm-slate-500); font-weight:600; text-transform:uppercase;">Lifetime Bookings</div>';
            html += '    <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">' + data.total_bookings + '</div>';
            html += '  </div>';
            html += '  <div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); padding:10px 14px; border-radius:6px;">';
            html += '    <div style="font-size:11px; color:var(--pm-slate-500); font-weight:600; text-transform:uppercase;">Pieces Dispatched</div>';
            html += '    <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">' + data.total_qty + '</div>';
            html += '  </div>';
            html += '  <div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); padding:10px 14px; border-radius:6px;">';
            html += '    <div style="font-size:11px; color:var(--pm-slate-500); font-weight:600; text-transform:uppercase;">Total Rent Earned</div>';
            html += '    <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">₹ ' + Number(data.total_rent).toLocaleString('en-IN') + '</div>';
            html += '  </div>';
            html += '  <div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); padding:10px 14px; border-radius:6px;">';
            html += '    <div style="font-size:11px; color:var(--pm-slate-500); font-weight:600; text-transform:uppercase;">Security Deposit</div>';
            html += '    <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">₹ ' + Number(data.total_deposit).toLocaleString('en-IN') + '</div>';
            html += '  </div>';
            html += '</div>';

            if (!data.history || data.history.length === 0) {
                html += '<div style="text-align:center; padding:30px; color:var(--pm-slate-500);">No rental bookings recorded for this SKU.</div>';
            } else {
                html += '<div style="overflow-x:auto; border:1px solid var(--pm-slate-200); border-radius:6px;">';
                html += '<table class="pm-table" style="margin:0;">';
                html += '<thead><tr>';
                html += '<th style="width:40px;" class="text-center">#</th>';
                html += '<th>Bill Number</th>';
                html += '<th>Bill Date</th>';
                html += '<th>Customer Details</th>';
                html += '<th class="text-center">Status</th>';
                html += '<th class="text-center">Qty</th>';
                html += '<th class="text-right">Rent (₹)</th>';
                html += '<th class="text-right">Deposit (₹)</th>';
                html += '<th class="text-center">Action</th>';
                html += '</tr></thead><tbody>';

                data.history.forEach(function(item, idx) {
                    html += '<tr>';
                    html += '<td class="text-center font-mono text-muted">' + (idx + 1) + '</td>';
                    html += '<td><span class="font-mono" style="font-weight:600;">' + item.bill_no + '</span></td>';
                    html += '<td class="font-mono text-muted">' + item.bill_date + '</td>';
                    html += '<td><div><strong>' + item.cust_name + '</strong></div><div style="font-size:11px; color:var(--pm-slate-500);"><i class="fa fa-phone"></i> ' + item.cust_phone + '</div></td>';
                    html += '<td class="text-center"><span class="pm-status-pill">' + item.booking_status + '</span></td>';
                    html += '<td class="text-center font-mono">' + item.qty + '</td>';
                    html += '<td class="text-right font-mono" style="font-weight:600;">₹ ' + Number(item.rent).toLocaleString('en-IN') + '</td>';
                    html += '<td class="text-right font-mono text-muted">' + (item.deposit > 0 ? ('₹ ' . Number(item.deposit).toLocaleString('en-IN')) : '—') + '</td>';
                    html += '<td class="text-center"><a href="/pos/reports/rent_report_detail.php?id=' + item.bill_id + '" target="_blank" class="pm-btn pm-btn-secondary pm-btn-sm" style="font-size:11px; padding:2px 8px; height:26px;"><i class="fa fa-external-link"></i> View Bill</a></td>';
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
    downloadLink.download = "Item_Rent_Report_" + (new Date().toISOString().slice(0, 10)) + ".csv";
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