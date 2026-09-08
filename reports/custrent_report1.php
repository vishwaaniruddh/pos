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
// AJAX ENDPOINT: Fetch Complete Rental Ledger for a Customer
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'customer_ledger') {
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
            r.bill_id,
            r.new_bill_number,
            r.bill_date,
            r.rent_amount,
            r.amount as net_amount,
            r.bal_amount,
            r.booking_status,
            r.pick_date,
            r.delivery_date,
            COUNT(od.item_id) as item_count,
            GROUP_CONCAT(CONCAT(od.item_id, ' (', COALESCE(od.qty, 1), ')') SEPARATOR ', ') as item_codes
        FROM phppos_rent r
        LEFT JOIN order_detail od ON r.bill_id = od.bill_id
        WHERE r.cust_id = '$cid_esc'
        GROUP BY r.bill_id
        ORDER BY r.bill_date DESC, r.bill_id DESC
    ";

    $res_ledger = mysqli_query($con, $sql_ledger);
    $bookings = [];
    $total_rent_cust = 0;
    $total_net_cust  = 0;
    $total_bal_cust  = 0;

    if ($res_ledger) {
        while ($b = mysqli_fetch_assoc($res_ledger)) {
            $rent_val = floatval($b['rent_amount'] ?? 0);
            $net_val  = floatval($b['net_amount'] ?? 0);
            $bal_val  = floatval($b['bal_amount'] ?? 0);

            $total_rent_cust += $rent_val;
            $total_net_cust  += $net_val;
            $total_bal_cust  += $bal_val;

            $bookings[] = [
                'bill_id'         => $b['bill_id'],
                'bill_no'         => !empty($b['new_bill_number']) ? $b['new_bill_number'] : ('BILL-' . $b['bill_id']),
                'bill_date'       => ($b['bill_date'] && $b['bill_date'] !== '0000-00-00') ? date('d M Y', strtotime($b['bill_date'])) : '—',
                'rent_amount'     => $rent_val,
                'net_amount'      => $net_val,
                'bal_amount'      => $bal_val,
                'booking_status'  => !empty($b['booking_status']) ? $b['booking_status'] : 'Pending',
                'pick_date'       => ($b['pick_date'] && $b['pick_date'] !== '0000-00-00') ? date('d M Y', strtotime($b['pick_date'])) : '—',
                'delivery_date'   => ($b['delivery_date'] && $b['delivery_date'] !== '0000-00-00') ? date('d M Y', strtotime($b['delivery_date'])) : '—',
                'item_count'      => intval($b['item_count'] ?? 0),
                'item_codes'      => $b['item_codes'] ?? 'None',
            ];
        }
    }

    echo json_encode([
        'success'        => true,
        'customer'       => $cust_meta,
        'total_bookings' => count($bookings),
        'total_rent'     => $total_rent_cust,
        'total_net'      => $total_net_cust,
        'total_balance'  => $total_bal_cust,
        'bookings'       => $bookings
    ]);
    exit;
}

// -------------------------------------------------------------
// MAIN PAGE DATA QUERY & FILTERING
// -------------------------------------------------------------
$search_query = safe_str($_GET['q'] ?? ($_GET['search_query'] ?? ''));
$inv_no       = safe_str($_GET['invno'] ?? '');
$from_date    = parse_date_to_db($_GET['frmdate'] ?? ($_GET['from'] ?? ''));
$to_date      = parse_date_to_db($_GET['todate'] ?? ($_GET['to'] ?? ''));

// Build SQL WHERE filters
$where_clauses = ["r.cust_id != ''", "r.cust_id IS NOT NULL", "r.cust_id != '0'"];

if ($inv_no !== '') {
    $inv_esc = mysqli_real_escape_string($con, $inv_no);
    $where_clauses[] = "(r.bill_id = '$inv_esc' OR r.new_bill_number LIKE '%$inv_esc%')";
}

if ($search_query !== '') {
    $q_esc = mysqli_real_escape_string($con, $search_query);
    $where_clauses[] = "(p.first_name LIKE '%$q_esc%' OR p.last_name LIKE '%$q_esc%' OR p.phone_number LIKE '%$q_esc%' OR p.person_id = '$q_esc')";
}

if (!empty($from_date) && !empty($to_date)) {
    $where_clauses[] = "r.bill_date BETWEEN '$from_date' AND '$to_date'";
} elseif (!empty($from_date)) {
    $where_clauses[] = "r.bill_date >= '$from_date'";
} elseif (!empty($to_date)) {
    $where_clauses[] = "r.bill_date <= '$to_date'";
}

$where_sql = implode(" AND ", $where_clauses);

// Overall Store Customer Rental KPI Metrics
$kpi_total_customers = 0;
$kpi_total_rent_vol  = 0;
$kpi_total_bookings  = 0;
$kpi_total_balance   = 0;

if ($con) {
    $q_kpi = mysqli_query($con, "
        SELECT 
            COUNT(DISTINCT r.cust_id) as total_customers,
            COUNT(DISTINCT r.bill_id) as total_bookings,
            COALESCE(SUM(r.rent_amount), 0) as total_rent,
            COALESCE(SUM(r.bal_amount), 0) as total_balance
        FROM phppos_rent r
        WHERE r.cust_id != '' AND r.cust_id IS NOT NULL AND r.cust_id != '0'
    ");
    if ($q_kpi && $rk = mysqli_fetch_assoc($q_kpi)) {
        $kpi_total_customers = intval($rk['total_customers'] ?? 0);
        $kpi_total_bookings  = intval($rk['total_bookings'] ?? 0);
        $kpi_total_rent_vol  = floatval($rk['total_rent'] ?? 0);
        $kpi_total_balance   = floatval($rk['total_balance'] ?? 0);
    }
}

// Fetch Filtered Customer List
$report_rows = [];
$scope_total_rent     = 0;
$scope_total_net      = 0;
$scope_total_bal      = 0;
$scope_total_bookings = 0;

// Apply intelligent limit if unbounded
$limit_clause = (empty($from_date) && empty($to_date) && empty($search_query) && empty($inv_no)) ? "LIMIT 250" : "";

if ($con) {
    $main_sql = "
        SELECT 
            p.person_id,
            p.first_name,
            p.last_name,
            p.phone_number,
            p.email,
            p.city,
            COUNT(DISTINCT r.bill_id) as total_bookings,
            COALESCE(SUM(r.rent_amount), 0) as total_rent_amount,
            COALESCE(SUM(r.amount), 0) as total_net_amount,
            COALESCE(SUM(r.bal_amount), 0) as total_balance,
            MAX(r.bill_date) as last_rental_date,
            MAX(r.new_bill_number) as latest_bill_number
        FROM phppos_rent r
        JOIN phppos_people p ON r.cust_id = p.person_id
        WHERE $where_sql
        GROUP BY p.person_id
        ORDER BY total_rent_amount DESC, total_bookings DESC
        $limit_clause
    ";

    $res_main = mysqli_query($con, $main_sql);
    if ($res_main) {
        while ($row = mysqli_fetch_assoc($res_main)) {
            $report_rows[] = $row;
            $scope_total_rent     += floatval($row['total_rent_amount'] ?? 0);
            $scope_total_net      += floatval($row['total_net_amount'] ?? 0);
            $scope_total_bal      += floatval($row['total_balance'] ?? 0);
            $scope_total_bookings += intval($row['total_bookings'] ?? 0);
        }
    }
}

$has_filter = !empty($search_query) || !empty($inv_no) || !empty($from_date) || !empty($to_date);

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

                .custrent-container {
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
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

                .pm-avatar-badge {
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 11px;
                    font-weight: 700;
                    color: var(--pm-slate-700);
                    flex-shrink: 0;
                }

                .pm-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    padding: 3px 8px;
                    border-radius: 4px;
                    font-size: 11px;
                    font-weight: 600;
                    color: var(--pm-slate-700);
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
                    width: 340px;
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
                    max-width: 1040px;
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
                    .custrent-container {
                        max-width: 100% !important;
                    }
                }
            </style>

            <div class="custrent-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-users" style="margin-right: 10px; font-size: 18px; color: var(--pm-slate-700);"></i>
                            Consolidated Customer Rent Report
                        </h1>
                        <p class="pm-page-subtitle">
                            Analyze client rental volume, lifetime order frequency, billed amounts, and outstanding balances.
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
                            <span class="pm-kpi-label">Active Rental Clients</span>
                            <div class="pm-kpi-icon"><i class="fa fa-user-check"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($has_filter ? count($report_rows) : $kpi_total_customers) ?></div>
                        <div class="pm-kpi-sub">
                            <?= $has_filter ? ('Matching current criteria (' . count($report_rows) . ' shown)') : ('Total store lifetime customers: ' . number_format($kpi_total_customers)) ?>
                        </div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Rental Volume Generated</span>
                            <div class="pm-kpi-icon"><i class="fa fa-inr"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($has_filter ? $scope_total_rent : $kpi_total_rent_vol) ?></div>
                        <div class="pm-kpi-sub">
                            <?= $has_filter ? 'Rental value in filtered scope' : 'Storewide rental transaction volume' ?>
                        </div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Booking Orders</span>
                            <div class="pm-kpi-icon"><i class="fa fa-receipt"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($has_filter ? $scope_total_bookings : $kpi_total_bookings) ?></div>
                        <div class="pm-kpi-sub">
                            <?= $has_filter ? 'Rental orders for selected patrons' : 'Total rental invoices generated' ?>
                        </div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Outstanding Balance</span>
                            <div class="pm-kpi-icon"><i class="fa fa-clock"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($has_filter ? $scope_total_bal : $kpi_total_balance) ?></div>
                        <div class="pm-kpi-sub">Receivable balance pending collection</div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="pm-card">
                    <div class="pm-card-body">
                        <form method="GET" action="custrent_report1.php" id="filterForm">
                            <div class="pm-filter-grid">
                                <div class="pm-form-group">
                                    <label class="pm-label" for="search_query">Search Customer</label>
                                    <input type="text" name="q" id="search_query" class="pm-input" 
                                           placeholder="Name, Phone, or Client ID..." 
                                           value="<?= htmlspecialchars($search_query) ?>">
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="invno">Invoice / Bill Number</label>
                                    <input type="text" name="invno" id="invno" class="pm-input" 
                                           placeholder="e.g. SAK-25/R-00278 or 7143..." 
                                           value="<?= htmlspecialchars($inv_no) ?>">
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
                                        <i class="fa fa-filter"></i> Apply Filter
                                    </button>
                                    <a href="custrent_report1.php" class="pm-btn pm-btn-secondary" title="Reset Filters">
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

                <!-- Scope Status / Banner if filtered -->
                <?php if ($has_filter): ?>
                <div style="background: #ffffff; border: 1px solid var(--pm-slate-200); border-radius: 8px; padding: 12px 18px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 13px; color: var(--pm-slate-700);">
                        <i class="fa fa-info-circle text-muted" style="margin-right: 6px;"></i>
                        Filtered view active: Showing <strong><?= count($report_rows) ?></strong> client records.
                        <?php if ($search_query): ?> Search: "<strong><?= htmlspecialchars($search_query) ?></strong>" | <?php endif; ?>
                        <?php if ($inv_no): ?> Invoice: "<strong><?= htmlspecialchars($inv_no) ?></strong>" | <?php endif; ?>
                        <?php if ($from_date || $to_date): ?>
                            Date Range: <strong><?= $from_date ? date('d M Y', strtotime($from_date)) : 'Start' ?></strong> to <strong><?= $to_date ? date('d M Y', strtotime($to_date)) : 'Present' ?></strong>
                        <?php endif; ?>
                    </div>
                    <a href="custrent_report1.php" class="pm-btn pm-btn-secondary pm-btn-sm">
                        <i class="fa fa-times"></i> Clear Filters
                    </a>
                </div>
                <?php endif; ?>

                <!-- Data Table Card -->
                <div class="pm-card" style="margin-bottom: 32px;">
                    <!-- Table Toolbar with Live In-Table Search -->
                    <div class="pm-table-toolbar">
                        <div class="pm-search-input-wrap">
                            <i class="fa fa-search"></i>
                            <input type="text" id="tableSearch" class="pm-input" placeholder="Search by name, phone, city in table..." onkeyup="filterCustomerTable()">
                        </div>
                        <div style="font-size: 12px; color: var(--pm-slate-500);">
                            Showing <span id="visibleRowCount" style="font-weight: 700; color: var(--pm-slate-900);"><?= count($report_rows) ?></span> customer rows
                        </div>
                    </div>

                    <div class="pm-table-container">
                        <table class="pm-table" id="reportTable">
                            <thead>
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th>Customer Name</th>
                                    <th style="width: 150px;">Contact Number</th>
                                    <th class="text-center" style="width: 100px;">Bookings</th>
                                    <th class="text-right" style="width: 140px;">Gross Rent (₹)</th>
                                    <th class="text-right" style="width: 130px;">Net Billed (₹)</th>
                                    <th class="text-right" style="width: 130px;">Balance (₹)</th>
                                    <th style="width: 130px;">Last Booking</th>
                                    <th class="text-center" style="width: 140px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($report_rows)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center" style="padding: 48px; color: var(--pm-slate-400);">
                                            <i class="fa fa-user-slash" style="font-size: 32px; margin-bottom: 12px; display: block;"></i>
                                            No customer rental records match the selected criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $idx = 1; foreach ($report_rows as $row): 
                                        $full_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                        if ($full_name === '') $full_name = 'Guest Customer';
                                        $initials = strtoupper(substr(trim($row['first_name'] ?? 'C'), 0, 1) . substr(trim($row['last_name'] ?? 'R'), 0, 1));
                                        $phone = trim((string)($row['phone_number'] ?? ''));
                                        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
                                        if (strlen($clean_phone) === 10) $clean_phone = '91' . $clean_phone;
                                    ?>
                                        <tr>
                                            <td class="text-center font-mono text-muted"><?= $idx++ ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div class="pm-avatar-badge"><?= htmlspecialchars($initials) ?></div>
                                                    <div>
                                                        <div style="font-weight: 700; color: var(--pm-slate-900);">
                                                            <?= htmlspecialchars($full_name) ?>
                                                        </div>
                                                        <?php if (!empty($row['city'])): ?>
                                                            <div style="font-size: 11px; color: var(--pm-slate-500);">
                                                                <i class="fa fa-location-dot" style="font-size: 10px;"></i> <?= htmlspecialchars($row['city']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($phone !== ''): ?>
                                                    <div style="display: flex; align-items: center; gap: 6px;">
                                                        <span class="font-mono"><?= htmlspecialchars($phone) ?></span>
                                                        <a href="https://wa.me/<?= htmlspecialchars($clean_phone) ?>" target="_blank" title="Chat on WhatsApp" style="color: #10b981; font-size: 14px; text-decoration: none;">
                                                            <i class="fa-brands fa-whatsapp"></i>
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center font-mono">
                                                <strong><?= number_format($row['total_bookings'] ?? 0) ?></strong>
                                            </td>
                                            <td class="text-right font-mono" style="font-weight: 700; color: var(--pm-slate-900);">
                                                ₹ <?= number_format($row['total_rent_amount'] ?? 0) ?>
                                            </td>
                                            <td class="text-right font-mono">
                                                ₹ <?= number_format($row['total_net_amount'] ?? 0) ?>
                                            </td>
                                            <td class="text-right font-mono" style="<?= floatval($row['total_balance'] ?? 0) > 0 ? 'font-weight: 600;' : 'color: var(--pm-slate-400);' ?>">
                                                <?= floatval($row['total_balance'] ?? 0) > 0 ? ('₹ ' . number_format($row['total_balance'])) : '₹ 0' ?>
                                            </td>
                                            <td class="font-mono text-muted" style="font-size: 12px;">
                                                <?= !empty($row['last_rental_date']) ? date('d M Y', strtotime($row['last_rental_date'])) : '—' ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" 
                                                        onclick="openCustomerLedger('<?= htmlspecialchars(addslashes($row['person_id'])) ?>');"
                                                        title="View all bookings for this customer">
                                                    <i class="fa fa-folder-open"></i> View Ledger
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($report_rows)): ?>
                            <tfoot>
                                <tr class="pm-table-footer">
                                    <td colspan="3" class="text-right">Scope Total:</td>
                                    <td class="text-center font-mono"><?= number_format($scope_total_bookings) ?></td>
                                    <td class="text-right font-mono">₹ <?= number_format($scope_total_rent) ?></td>
                                    <td class="text-right font-mono">₹ <?= number_format($scope_total_net) ?></td>
                                    <td class="text-right font-mono">₹ <?= number_format($scope_total_bal) ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal: Customer Rental Dossier / Ledger -->
<div class="pm-modal-overlay" id="custLedgerModal">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h3 class="pm-modal-title">
                <i class="fa fa-address-book text-muted"></i>
                <span id="modalCustTitle">Customer Rental Dossier</span>
            </h3>
            <button type="button" class="pm-modal-close" onclick="closeCustModal();">&times;</button>
        </div>
        <div class="pm-modal-body" id="modalCustBody">
            <div style="text-align: center; padding: 40px; color: var(--pm-slate-400);">
                <i class="fa fa-circle-notch fa-spin fa-2x"></i>
                <p style="margin-top: 10px; font-size: 13px;">Loading client transaction history...</p>
            </div>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" onclick="closeCustModal();">Close</button>
        </div>
    </div>
</div>

<script>
// Filter Table dynamically
function filterCustomerTable() {
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

// Open Customer Ledger Modal via AJAX
function openCustomerLedger(custId) {
    var modal = document.getElementById('custLedgerModal');
    var modalBody = document.getElementById('modalCustBody');
    var title = document.getElementById('modalCustTitle');

    title.innerText = 'Customer Rental Dossier (ID: ' + custId + ')';
    modalBody.innerHTML = '<div style="text-align:center; padding:40px; color:var(--pm-slate-400);"><i class="fa fa-circle-notch fa-spin fa-2x"></i><p style="margin-top:10px; font-size:13px;">Loading client transaction history...</p></div>';
    modal.style.display = 'flex';

    fetch('custrent_report1.php?action=customer_ledger&cust_id=' + encodeURIComponent(custId))
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data.success) {
                modalBody.innerHTML = '<div style="padding:20px; color:#ef4444;"><i class="fa fa-exclamation-triangle"></i> ' + (data.message || 'Error loading records') + '</div>';
                return;
            }

            var c = data.customer || {};
            var custName = ((c.first_name || '') + ' ' + (c.last_name || '')).trim() || 'Client';
            title.innerHTML = 'Rental Dossier: <strong>' + custName + '</strong> <span class="pm-pill" style="margin-left:8px;">ID #' + custId + '</span>';

            var html = '';
            // Customer Header Cards
            html += '<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:18px;">';
            html += '  <div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); padding:10px 14px; border-radius:6px;">';
            html += '    <div style="font-size:11px; color:var(--pm-slate-500); font-weight:600; text-transform:uppercase;">Lifetime Bookings</div>';
            html += '    <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">' + data.total_bookings + ' Orders</div>';
            html += '  </div>';
            html += '  <div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); padding:10px 14px; border-radius:6px;">';
            html += '    <div style="font-size:11px; color:var(--pm-slate-500); font-weight:600; text-transform:uppercase;">Gross Rent Volume</div>';
            html += '    <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">₹ ' + Number(data.total_rent).toLocaleString('en-IN') + '</div>';
            html += '  </div>';
            html += '  <div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); padding:10px 14px; border-radius:6px;">';
            html += '    <div style="font-size:11px; color:var(--pm-slate-500); font-weight:600; text-transform:uppercase;">Net Billed</div>';
            html += '    <div style="font-size:18px; font-weight:700; color:var(--pm-slate-900);">₹ ' + Number(data.total_net).toLocaleString('en-IN') + '</div>';
            html += '  </div>';
            html += '  <div style="background:var(--pm-slate-50); border:1px solid var(--pm-slate-200); padding:10px 14px; border-radius:6px;">';
            html += '    <div style="font-size:11px; color:var(--pm-slate-500); font-weight:600; text-transform:uppercase;">Outstanding Balance</div>';
            html += '    <div style="font-size:18px; font-weight:700; color:' + (data.total_balance > 0 ? '#b91c1c' : '#0f172a') + ';">₹ ' + Number(data.total_balance).toLocaleString('en-IN') + '</div>';
            html += '  </div>';
            html += '</div>';

            // Customer Contact Bar
            if (c.phone_number || c.email || c.city) {
                html += '<div style="font-size:12px; color:var(--pm-slate-600); margin-bottom:14px; padding:8px 12px; background:var(--pm-slate-100); border-radius:6px; display:flex; gap:16px; flex-wrap:wrap;">';
                if (c.phone_number) html += '<span><i class="fa fa-phone text-muted"></i> ' + c.phone_number + '</span>';
                if (c.email) html += '<span><i class="fa fa-envelope text-muted"></i> ' + c.email + '</span>';
                if (c.city) html += '<span><i class="fa fa-city text-muted"></i> ' + c.city + '</span>';
                html += '</div>';
            }

            if (!data.bookings || data.bookings.length === 0) {
                html += '<div style="text-align:center; padding:30px; color:var(--pm-slate-500);">No rental orders recorded for this customer.</div>';
            } else {
                html += '<div style="overflow-x:auto; border:1px solid var(--pm-slate-200); border-radius:6px;">';
                html += '<table class="pm-table" style="margin:0;">';
                html += '<thead><tr>';
                html += '<th style="width:40px;" class="text-center">#</th>';
                html += '<th>Invoice #</th>';
                html += '<th>Bill Date</th>';
                html += '<th>Items Rented</th>';
                html += '<th class="text-center">Status</th>';
                html += '<th class="text-right">Rent (₹)</th>';
                html += '<th class="text-right">Net (₹)</th>';
                html += '<th class="text-right">Balance (₹)</th>';
                html += '<th class="text-center">Action</th>';
                html += '</tr></thead><tbody>';

                data.bookings.forEach(function(b, idx) {
                    html += '<tr>';
                    html += '<td class="text-center font-mono text-muted">' + (idx + 1) + '</td>';
                    html += '<td><span class="font-mono" style="font-weight:700;">' + b.bill_no + '</span></td>';
                    html += '<td class="font-mono text-muted">' + b.bill_date + '</td>';
                    html += '<td><div style="font-size:12px; max-width:240px; white-space:normal;">' + b.item_codes + '</div></td>';
                    html += '<td class="text-center"><span class="pm-pill">' + b.booking_status + '</span></td>';
                    html += '<td class="text-right font-mono" style="font-weight:600;">₹ ' + Number(b.rent_amount).toLocaleString('en-IN') + '</td>';
                    html += '<td class="text-right font-mono">₹ ' + Number(b.net_amount).toLocaleString('en-IN') + '</td>';
                    html += '<td class="text-right font-mono" style="' + (b.bal_amount > 0 ? 'font-weight:600; color:#b91c1c;' : 'color:var(--pm-slate-400);') + '">₹ ' + Number(b.bal_amount).toLocaleString('en-IN') + '</td>';
                    html += '<td class="text-center"><a href="/pos/reports/rent_report_detail.php?id=' + b.bill_id + '" target="_blank" class="pm-btn pm-btn-secondary pm-btn-sm" style="font-size:11px; padding:2px 8px; height:26px;"><i class="fa fa-external-link"></i> View Bill</a></td>';
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
    var modal = document.getElementById('custLedgerModal');
    if (modal) modal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('custLedgerModal');
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
    downloadLink.download = "Customer_Rent_Report_" + (new Date().toISOString().slice(0, 10)) + ".csv";
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