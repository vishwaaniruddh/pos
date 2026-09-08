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

// Request parameters (support both GET and POST)
$cid_param       = safe_str($_REQUEST['cid'] ?? '');
$phone_param     = safe_str($_REQUEST['phoneNo'] ?? '');
$invno_param     = safe_str($_REQUEST['invno'] ?? '');
$status_param    = safe_str($_REQUEST['status'] ?? 'all');
$search_query    = safe_str($_REQUEST['q'] ?? '');

$from_raw        = safe_str($_REQUEST['from'] ?? '');
$to_raw          = safe_str($_REQUEST['to_date'] ?? ($_REQUEST['to'] ?? ''));
$from_date       = parse_date_to_db($from_raw);
$to_date         = parse_date_to_db($to_raw);

$limit_param     = safe_str($_REQUEST['limit'] ?? '50');
$is_unlimited    = ($limit_param === 'all');
$per_page        = $is_unlimited ? 999999 : intval($limit_param);
if ($per_page <= 0) $per_page = 50;
if ($per_page > 500 && !$is_unlimited) $per_page = 250;

$current_page    = max(1, intval($_REQUEST['page'] ?? 1));
$offset          = ($current_page - 1) * $per_page;

// Build SQL WHERE Conditions
$where_clauses = ["1=1"];

if ($cid_param !== '' && $cid_param !== '-1') {
    $c_esc = mysqli_real_escape_string($con, $cid_param);
    $where_clauses[] = "r.cust_id = '$c_esc'";
}

if ($phone_param !== '') {
    $p_esc = mysqli_real_escape_string($con, $phone_param);
    $where_clauses[] = "(p.phone_number LIKE '%$p_esc%' OR r.throught_phone LIKE '%$p_esc%')";
}

if ($invno_param !== '') {
    $inv_esc = mysqli_real_escape_string($con, $invno_param);
    $where_clauses[] = "(r.bill_id = '$inv_esc' OR r.new_bill_number LIKE '%$inv_esc%')";
}

if ($status_param !== 'all' && $status_param !== '') {
    $st_esc = mysqli_real_escape_string($con, $status_param);
    $where_clauses[] = "r.status = '$st_esc'";
}

if ($from_date !== '' && $to_date !== '') {
    $where_clauses[] = "r.bill_date BETWEEN '$from_date' AND '$to_date'";
} elseif ($from_date !== '') {
    $where_clauses[] = "r.bill_date >= '$from_date'";
} elseif ($to_date !== '') {
    $where_clauses[] = "r.bill_date <= '$to_date'";
}

if ($search_query !== '') {
    $q_esc = mysqli_real_escape_string($con, $search_query);
    $where_clauses[] = "(r.bill_id LIKE '%$q_esc%' OR r.new_bill_number LIKE '%$q_esc%' OR p.first_name LIKE '%$q_esc%' OR p.last_name LIKE '%$q_esc%' OR p.phone_number LIKE '%$q_esc%' OR r.cust_name LIKE '%$q_esc%')";
}

$where_sql = implode(" AND ", $where_clauses);

// Scope Summary & KPI Metrics
$kpi = [
    'total_bills'  => 0,
    'total_rent'   => 0,
    'total_comm'   => 0,
    'total_bal'    => 0,
    'active_rent'  => 0,
];

// Specific Customer Dossier (if cid selected)
$customer_dossier = null;
if ($cid_param !== '' && $cid_param !== '-1' && $con) {
    $c_esc = mysqli_real_escape_string($con, $cid_param);
    $q_cust = mysqli_query($con, "SELECT person_id, first_name, last_name, phone_number, email FROM phppos_people WHERE person_id = '$c_esc' LIMIT 1");
    if ($q_cust && $rcust = mysqli_fetch_assoc($q_cust)) {
        $customer_dossier = $rcust;
        $q_paid = mysqli_query($con, "SELECT COALESCE(SUM(amount), 0) FROM rent_amount WHERE cust_id = '$c_esc'");
        $r_paid = mysqli_fetch_row($q_paid);
        $customer_dossier['lifetime_paid'] = floatval($r_paid[0] ?? 0);
    }
}

// Phase 1: Fast Scope KPI Query
if ($con) {
    $sql_kpi = "
        SELECT 
            COUNT(r.bill_id) as total_bills,
            COALESCE(SUM(r.rent_amount), 0) as total_rent,
            COALESCE(SUM(CAST(r.total_comm AS DECIMAL(10,2))), 0) as total_comm,
            COALESCE(SUM(r.bal_amount), 0) as total_bal,
            COALESCE(SUM(CASE WHEN r.status = 'A' THEN r.rent_amount ELSE 0 END), 0) as active_rent
        FROM phppos_rent r
        LEFT JOIN phppos_people p ON r.cust_id = p.person_id
        WHERE $where_sql
    ";
    $res_kpi = mysqli_query($con, $sql_kpi);
    if ($res_kpi && $rkpi = mysqli_fetch_assoc($res_kpi)) {
        $kpi['total_bills'] = intval($rkpi['total_bills'] ?? 0);
        $kpi['total_rent']  = floatval($rkpi['total_rent'] ?? 0);
        $kpi['total_comm']  = floatval($rkpi['total_comm'] ?? 0);
        $kpi['total_bal']   = floatval($rkpi['total_bal'] ?? 0);
        $kpi['active_rent'] = floatval($rkpi['active_rent'] ?? 0);
    }
}

// Handle Export to CSV (Server-side)
if (isset($_REQUEST['export']) && $_REQUEST['export'] == '1') {
    if ($con) {
        $export_sql = "
            SELECT r.*, p.first_name, p.last_name, p.phone_number
            FROM phppos_rent r
            LEFT JOIN phppos_people p ON r.cust_id = p.person_id
            WHERE $where_sql
            ORDER BY r.bill_id DESC
        ";
        $export_res = mysqli_query($con, $export_sql);
        if ($export_res) {
            $filename = "Rent_Report_" . date('Ymd_His') . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Bill Date', 'Invoice No', 'Bill ID', 'Customer Name', 'Phone Number',
                'Rent Amount (₹)', 'Commission Rate', 'Total Commission (₹)',
                'Balance Due (₹)', 'Status', 'Pick-up Date', 'Delivery Date'
            ]);

            while ($erow = mysqli_fetch_assoc($export_res)) {
                $cust_name = trim(($erow['first_name'] ?? '') . ' ' . ($erow['last_name'] ?? ''));
                if (!$cust_name) $cust_name = $erow['cust_name'] ?: 'Walk-in';
                $phone = $erow['phone_number'] ?: '—';
                $bill_no = !empty($erow['new_bill_number']) ? $erow['new_bill_number'] : $erow['bill_id'];
                $bill_date = ($erow['bill_date'] && $erow['bill_date'] !== '0000-00-00') ? date('d-m-Y', strtotime($erow['bill_date'])) : '—';
                $pick_d = (!empty($erow['pick_date']) && $erow['pick_date'] !== '0000-00-00') ? date('d-m-Y', strtotime($erow['pick_date'])) : '—';
                $delv_d = (!empty($erow['delivery_date']) && $erow['delivery_date'] !== '0000-00-00') ? date('d-m-Y', strtotime($erow['delivery_date'])) : '—';

                $comm_str = '';
                if (!empty($erow['commission'])) {
                    $comm_str = ($erow['commission_currency'] === 'Rs.') ? ('Rs.' . $erow['commission']) : ($erow['commission'] . ($erow['commission_currency'] ?? ''));
                } else {
                    $comm_str = '—';
                }

                fputcsv($output, [
                    $bill_date,
                    $bill_no,
                    $erow['bill_id'],
                    $cust_name,
                    $phone,
                    floatval($erow['rent_amount'] ?? 0),
                    $comm_str,
                    floatval($erow['total_comm'] ?? 0),
                    floatval($erow['bal_amount'] ?? 0),
                    $erow['status'] === 'A' ? 'Active' : $erow['status'],
                    $pick_d,
                    $delv_d
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, [
                'Total Scope (' . $kpi['total_bills'] . ' Bills)',
                '', '', '', '',
                $kpi['total_rent'],
                '',
                $kpi['total_comm'],
                $kpi['total_bal'],
                '', '', ''
            ]);

            fclose($output);
            exit();
        }
    }
}

// Phase 2: Paginated Fetch for Table Display
$report_rows = [];
$total_pages = ($per_page > 0 && !$is_unlimited && $kpi['total_bills'] > 0) ? ceil($kpi['total_bills'] / $per_page) : 1;

if ($con && $kpi['total_bills'] > 0) {
    $limit_clause = $is_unlimited ? "" : "LIMIT $offset, $per_page";
    $sql_rows = "
        SELECT r.*, p.first_name, p.last_name, p.phone_number
        FROM phppos_rent r
        LEFT JOIN phppos_people p ON r.cust_id = p.person_id
        WHERE $where_sql
        ORDER BY r.bill_id DESC
        $limit_clause
    ";
    $res_rows = mysqli_query($con, $sql_rows);
    if ($res_rows) {
        while ($rw = mysqli_fetch_assoc($res_rows)) {
            $report_rows[] = $rw;
        }
    }
}

// Pagination URL Builder Helper
if (!function_exists('get_rent_report_page_url')) {
    function get_rent_report_page_url($p) {
        $params = $_GET;
        $params['page'] = $p;
        return 'rentReport.php?' . http_build_query($params);
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

                .rentreport-container {
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
                    letter-spacing: 0.04em;
                    margin-right: 4px;
                }

                .pm-preset-btn {
                    padding: 4px 10px;
                    font-size: 11px;
                    font-weight: 500;
                    color: var(--pm-slate-600);
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 4px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                }

                .pm-preset-btn:hover {
                    background: var(--pm-slate-200);
                    color: var(--pm-slate-900);
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

                .pm-bill-pill {
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

                .pm-dot-active {
                    background: #2563eb;
                }

                .font-mono {
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                }

                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .text-muted { color: var(--pm-slate-500); }

                /* Customer Dossier Card */
                .pm-dossier-card {
                    background: var(--pm-slate-50);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 14px 18px;
                    margin-bottom: 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 16px;
                }

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
                    .rentreport-container {
                        max-width: 100% !important;
                    }
                }
            </style>

            <div class="rentreport-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-receipt" style="margin-right: 10px; font-size: 20px; color: var(--pm-slate-700);"></i>
                            Rent Report
                        </h1>
                        <p class="pm-page-subtitle">
                            Comprehensive rental order history, customer accounts ledger, commission disbursements, and outstanding balance receivables.
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button type="button" class="pm-btn pm-btn-secondary" onclick="window.print();">
                            <i class="fa fa-print"></i> Print Report
                        </button>
                        <a href="rentReport.php?export=1&<?= http_build_query($_GET) ?>" class="pm-btn pm-btn-primary" id="btnExportCSV">
                            <i class="fa fa-download"></i> Export CSV
                        </a>
                    </div>
                </div>

                <!-- KPI Metric Cards - 5 Cards across in a single row -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Gross Rent Turnover</span>
                            <div class="pm-kpi-icon"><i class="fa fa-inr"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['total_rent']) ?></div>
                        <div class="pm-kpi-sub">
                            Across <?= number_format($kpi['total_bills']) ?> bookings in scope
                        </div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Bookings Count</span>
                            <div class="pm-kpi-icon"><i class="fa fa-calendar-check"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['total_bills']) ?></div>
                        <div class="pm-kpi-sub">Total rental orders recorded</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Commission</span>
                            <div class="pm-kpi-icon"><i class="fa fa-percent"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['total_comm']) ?></div>
                        <div class="pm-kpi-sub">Referral & agent commissions</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Balance Receivable</span>
                            <div class="pm-kpi-icon"><i class="fa fa-hand-holding-dollar"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['total_bal']) ?></div>
                        <div class="pm-kpi-sub">Total pending bill balance</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Active Rent Turn</span>
                            <div class="pm-kpi-icon"><i class="fa fa-clock"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['active_rent']) ?></div>
                        <div class="pm-kpi-sub">Active status 'A' rentals</div>
                    </div>
                </div>

                <!-- Optional Customer Specific Dossier Header -->
                <?php if ($customer_dossier): ?>
                <div class="pm-dossier-card">
                    <div>
                        <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--pm-slate-400); letter-spacing: 0.04em;">Customer Ledger Filter</div>
                        <div style="font-size: 16px; font-weight: 700; color: var(--pm-slate-900); margin-top: 2px;">
                            <?= htmlspecialchars($customer_dossier['first_name'] . ' ' . $customer_dossier['last_name']) ?>
                            <span class="pm-badge-neutral" style="margin-left: 6px;">ID: #<?= $customer_dossier['person_id'] ?></span>
                        </div>
                        <div style="font-size: 12px; color: var(--pm-slate-500); margin-top: 2px;">
                            <?php if (!empty($customer_dossier['phone_number'])): ?>
                                <i class="fa fa-phone" style="font-size: 10px;"></i> <?= htmlspecialchars($customer_dossier['phone_number']) ?> &bull;
                            <?php endif; ?>
                            <?php if (!empty($customer_dossier['email'])): ?>
                                <i class="fa fa-envelope" style="font-size: 10px;"></i> <?= htmlspecialchars($customer_dossier['email']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <div style="text-align: right;">
                            <div style="font-size: 11px; font-weight: 600; color: var(--pm-slate-400); text-transform: uppercase;">Total Rent</div>
                            <div style="font-size: 16px; font-weight: 700; color: var(--pm-slate-900); font-family: ui-monospace, monospace;">₹ <?= number_format($kpi['total_rent']) ?></div>
                        </div>
                        <div style="text-align: right; border-left: 1px solid var(--pm-slate-200); padding-left: 16px;">
                            <div style="font-size: 11px; font-weight: 600; color: var(--pm-slate-400); text-transform: uppercase;">Paid Amount</div>
                            <div style="font-size: 16px; font-weight: 700; color: var(--pm-slate-900); font-family: ui-monospace, monospace;">₹ <?= number_format($customer_dossier['lifetime_paid']) ?></div>
                        </div>
                        <div style="text-align: right; border-left: 1px solid var(--pm-slate-200); padding-left: 16px;">
                            <div style="font-size: 11px; font-weight: 600; color: var(--pm-slate-400); text-transform: uppercase;">Net Balance</div>
                            <div style="font-size: 16px; font-weight: 700; color: var(--pm-slate-900); font-family: ui-monospace, monospace;">₹ <?= number_format(max(0, $kpi['total_rent'] - $customer_dossier['lifetime_paid'])) ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filter Toolbar Card -->
                <div class="pm-card">
                    <div class="pm-card-body">
                        <form id="filterForm" method="GET" action="rentReport.php">
                            <div class="pm-filter-grid">

                                <!-- From Date -->
                                <div class="pm-form-group">
                                    <label class="pm-label" for="from">From Date</label>
                                    <input type="date" class="pm-input" id="from" name="from" 
                                           value="<?= htmlspecialchars($from_date) ?>">
                                </div>

                                <!-- To Date -->
                                <div class="pm-form-group">
                                    <label class="pm-label" for="to_date">To Date</label>
                                    <input type="date" class="pm-input" id="to_date" name="to_date" 
                                           value="<?= htmlspecialchars($to_date) ?>">
                                </div>

                                <!-- Invoice Number -->
                                <div class="pm-form-group">
                                    <label class="pm-label" for="invno">Invoice / Bill No</label>
                                    <input type="text" class="pm-input" id="invno" name="invno" 
                                           placeholder="e.g. SAK-26/R-00043 or 7194" 
                                           value="<?= htmlspecialchars($invno_param) ?>">
                                </div>

                                <!-- Phone Number -->
                                <div class="pm-form-group">
                                    <label class="pm-label" for="phoneNo">Phone Number</label>
                                    <input type="text" class="pm-input" id="phoneNo" name="phoneNo" 
                                           placeholder="e.g. 9819182536" 
                                           value="<?= htmlspecialchars($phone_param) ?>">
                                </div>

                                <!-- Customer Search Query -->
                                <div class="pm-form-group" style="min-width: 170px;">
                                    <label class="pm-label" for="filterQ">Customer / Search</label>
                                    <input type="text" class="pm-input" id="filterQ" name="q" 
                                           placeholder="Search by customer name..." 
                                           value="<?= htmlspecialchars($search_query) ?>">
                                </div>

                                <!-- Booking Status -->
                                <div class="pm-form-group" style="max-width: 130px;">
                                    <label class="pm-label" for="status">Status</label>
                                    <select class="pm-select" id="status" name="status">
                                        <option value="all" <?= ($status_param === 'all' || $status_param === '') ? 'selected' : '' ?>>All Status</option>
                                        <option value="A" <?= ($status_param === 'A') ? 'selected' : '' ?>>Active (A)</option>
                                        <option value="R" <?= ($status_param === 'R') ? 'selected' : '' ?>>Returned (R)</option>
                                        <option value="C" <?= ($status_param === 'C') ? 'selected' : '' ?>>Cancelled (C)</option>
                                    </select>
                                </div>

                                <!-- Rows Per Page -->
                                <div class="pm-form-group" style="max-width: 110px;">
                                    <label class="pm-label" for="limit">Rows</label>
                                    <select class="pm-select" id="limit" name="limit">
                                        <option value="50" <?= ($limit_param === '50') ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= ($limit_param === '100') ? 'selected' : '' ?>>100</option>
                                        <option value="250" <?= ($limit_param === '250') ? 'selected' : '' ?>>250</option>
                                        <option value="all" <?= ($limit_param === 'all') ? 'selected' : '' ?>>All (<?= number_format($kpi['total_bills']) ?>)</option>
                                    </select>
                                </div>

                                <!-- Filter Buttons -->
                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="pm-btn pm-btn-primary" style="flex: 1;">
                                        <i class="fa fa-filter"></i> Apply
                                    </button>
                                    <a href="rentReport.php" class="pm-btn pm-btn-secondary" title="Reset Filters">
                                        <i class="fa fa-refresh"></i>
                                    </a>
                                </div>

                            </div>

                            <!-- Preset Date Buttons -->
                            <div class="pm-preset-bar">
                                <span class="pm-preset-label">Quick Presets:</span>
                                <button type="button" class="pm-preset-btn" onclick="setDatePreset('today');">Today</button>
                                <button type="button" class="pm-preset-btn" onclick="setDatePreset('this_month');">This Month</button>
                                <button type="button" class="pm-preset-btn" onclick="setDatePreset('last_30');">Last 30 Days</button>
                                <button type="button" class="pm-preset-btn" onclick="setDatePreset('this_fy');">This FY</button>
                                <button type="button" class="pm-preset-btn" onclick="setDatePreset('all');">All Time</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Main Data Table Card -->
                <div class="pm-card">
                    <div class="pm-table-toolbar">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 13px; font-weight: 600; color: var(--pm-slate-800);">
                                Bookings: <span class="font-mono" style="font-weight: 700;"><?= number_format($kpi['total_bills']) ?></span>
                            </span>
                            <span style="font-size: 12px; color: var(--pm-slate-400);">&bull;</span>
                            <span style="font-size: 12px; color: var(--pm-slate-500);">
                                Showing <span id="visibleRowCount"><?= count($report_rows) ?></span> on this page
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
                                    <th style="width: 140px;">Bill / Invoice No</th>
                                    <th style="width: 100px;">Bill Date</th>
                                    <th>Customer Details</th>
                                    <th class="text-right" style="width: 120px;">Rent Amount</th>
                                    <th class="text-center" style="width: 100px;">Commission</th>
                                    <th class="text-right" style="width: 120px;">Total Comm (₹)</th>
                                    <th class="text-right" style="width: 120px;">Balance (₹)</th>
                                    <th class="text-center" style="width: 90px;">Status</th>
                                    <th class="text-center" style="width: 110px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($report_rows)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center" style="padding: 48px; color: var(--pm-slate-400);">
                                            <i class="fa fa-receipt" style="font-size: 32px; margin-bottom: 12px; display: block;"></i>
                                            No rental records found matching your filter criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $idx = $offset + 1; 
                                    foreach ($report_rows as $row): 
                                        $cust_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                        if (!$cust_name) $cust_name = $row['cust_name'] ?: 'Walk-in Customer';
                                        $cust_phone = trim((string)($row['phone_number'] ?? ''));
                                        $bill_no = !empty($row['new_bill_number']) ? $row['new_bill_number'] : $row['bill_id'];
                                        $rent_amt = floatval($row['rent_amount'] ?? 0);
                                        $comm_amt = floatval($row['total_comm'] ?? 0);
                                        $bal_amt = floatval($row['bal_amount'] ?? 0);
                                        $is_active = ($row['status'] === 'A');

                                        $comm_str = '—';
                                        if (!empty($row['commission'])) {
                                            $comm_str = ($row['commission_currency'] === 'Rs.') ? ('Rs.' . $row['commission']) : ($row['commission'] . ($row['commission_currency'] ?? ''));
                                        }

                                        $search_text = strtolower($bill_no . ' ' . $row['bill_id'] . ' ' . $cust_name . ' ' . $cust_phone);
                                    ?>
                                        <tr data-search="<?= htmlspecialchars($search_text) ?>">
                                            <td class="text-center font-mono text-muted"><?= $idx++ ?></td>
                                            <td>
                                                <a href="rent_report_detail.php?id=<?= urlencode($row['bill_id']) ?>" 
                                                   target="_blank" 
                                                   class="pm-bill-pill" 
                                                   style="text-decoration: none;" 
                                                   title="View Full Bill Details">
                                                    <i class="fa fa-receipt" style="font-size: 10px; color: var(--pm-slate-400);"></i>
                                                    <?= htmlspecialchars($bill_no) ?>
                                                </a>
                                            </td>
                                            <td class="font-mono text-muted">
                                                <?= ($row['bill_date'] && $row['bill_date'] !== '0000-00-00') ? date('d M Y', strtotime($row['bill_date'])) : '—' ?>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600; color: var(--pm-slate-900);">
                                                    <a href="rentReport.php?cid=<?= urlencode($row['cust_id']) ?>" 
                                                       style="color: inherit; text-decoration: none; border-bottom: 1px dotted var(--pm-slate-400);" 
                                                       title="Filter bookings for this customer">
                                                        <?= htmlspecialchars($cust_name) ?>
                                                    </a>
                                                </div>
                                                <?php if ($cust_phone && $cust_phone !== '—'): ?>
                                                    <div style="font-size: 11px; color: var(--pm-slate-500); margin-top: 1px;">
                                                        <i class="fa fa-phone" style="font-size: 10px;"></i> <?= htmlspecialchars($cust_phone) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right font-mono" style="font-weight: 700; color: var(--pm-slate-900);">
                                                ₹ <?= number_format($rent_amt) ?>
                                            </td>
                                            <td class="text-center font-mono text-muted">
                                                <?= htmlspecialchars($comm_str) ?>
                                            </td>
                                            <td class="text-right font-mono text-muted">
                                                <?= $comm_amt > 0 ? ('₹ ' . number_format($comm_amt)) : '—' ?>
                                            </td>
                                            <td class="text-right font-mono" style="font-weight: 600; color: var(--pm-slate-700);">
                                                <?= $bal_amt > 0 ? ('₹ ' . number_format($bal_amt)) : '—' ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="pm-status-pill">
                                                    <span class="pm-dot-indicator <?= $is_active ? 'pm-dot-active' : '' ?>"></span>
                                                    <?= $is_active ? 'Active' : ($row['status'] ?: 'Standard') ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="rent_report_detail.php?id=<?= urlencode($row['bill_id']) ?>" 
                                                   target="_blank" 
                                                   class="pm-btn pm-btn-secondary pm-btn-sm" 
                                                   style="font-size: 11px; padding: 2px 8px; height: 26px;">
                                                    <i class="fa fa-external-link"></i> Bill Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($report_rows)): ?>
                            <tfoot class="pm-table-footer">
                                <tr>
                                    <td colspan="4" class="text-right">Scope Total (<?= number_format($kpi['total_bills']) ?> Bookings):</td>
                                    <td class="text-right font-mono">₹ <?= number_format($kpi['total_rent']) ?></td>
                                    <td></td>
                                    <td class="text-right font-mono">₹ <?= number_format($kpi['total_comm']) ?></td>
                                    <td class="text-right font-mono">₹ <?= number_format($kpi['total_bal']) ?></td>
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
                            <a href="<?= get_rent_report_page_url(1) ?>" class="pm-page-link <?= ($current_page <= 1) ? 'disabled' : '' ?>" title="First Page">
                                <i class="fa fa-angle-double-left"></i>
                            </a>
                            <a href="<?= get_rent_report_page_url($current_page - 1) ?>" class="pm-page-link <?= ($current_page <= 1) ? 'disabled' : '' ?>" title="Previous Page">
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
                                <a href="<?= get_rent_report_page_url($p) ?>" class="pm-page-link <?= ($p === $current_page) ? 'active' : '' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>
                            <?php if ($end_p < $total_pages): ?>
                                <span style="padding: 0 4px; color: var(--pm-slate-400);">...</span>
                            <?php endif; ?>

                            <a href="<?= get_rent_report_page_url($current_page + 1) ?>" class="pm-page-link <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>" title="Next Page">
                                <i class="fa fa-angle-right"></i>
                            </a>
                            <a href="<?= get_rent_report_page_url($total_pages) ?>" class="pm-page-link <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>" title="Last Page">
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
// Date Preset Helper
function setDatePreset(type) {
    var today = new Date();
    var pad = function(n) { return n < 10 ? '0' + n : n; };
    var fmt = function(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };

    var fromEl = document.getElementById('from');
    var toEl = document.getElementById('to_date');

    if (type === 'today') {
        var str = fmt(today);
        fromEl.value = str;
        toEl.value = str;
    } else if (type === 'this_month') {
        var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        fromEl.value = fmt(firstDay);
        toEl.value = fmt(lastDay);
    } else if (type === 'last_30') {
        var past30 = new Date();
        past30.setDate(today.getDate() - 30);
        fromEl.value = fmt(past30);
        toEl.value = fmt(today);
    } else if (type === 'this_fy') {
        var year = today.getFullYear();
        if (today.getMonth() < 3) {
            year = year - 1;
        }
        var fyStart = new Date(year, 3, 1);
        var fyEnd = new Date(year + 1, 2, 31);
        fromEl.value = fmt(fyStart);
        toEl.value = fmt(fyEnd);
    } else if (type === 'all') {
        fromEl.value = '';
        toEl.value = '';
    }
    document.getElementById('filterForm').submit();
}

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