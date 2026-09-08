<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

$is_export = (isset($_GET['export']) && $_GET['export'] == '1') || (isset($_REQUEST['export']) && $_REQUEST['export'] == '1');

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

// Request parameters
$cid_param       = safe_str($_REQUEST['cid'] ?? '');
$phone_param     = safe_str($_REQUEST['phoneNo'] ?? '');
$invno_param     = safe_str($_REQUEST['invno'] ?? '');
$status_param    = safe_str($_REQUEST['status'] ?? 'S'); // Default to 'S' (Confirmed Sales)
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

// Build SQL WHERE conditions
$where_clauses = ["1=1"];

if ($status_param !== 'all' && $status_param !== '') {
    $st_esc = mysqli_real_escape_string($con, $status_param);
    $where_clauses[] = "a.status = '$st_esc'";
}

if ($cid_param !== '' && $cid_param !== '-1') {
    $c_esc = mysqli_real_escape_string($con, $cid_param);
    $where_clauses[] = "a.cust_id = '$c_esc'";
}

if ($phone_param !== '') {
    $p_esc = mysqli_real_escape_string($con, $phone_param);
    $where_clauses[] = "p.phone_number LIKE '%$p_esc%'";
}

if ($invno_param !== '') {
    $inv_esc = mysqli_real_escape_string($con, $invno_param);
    $where_clauses[] = "(a.bill_id = '$inv_esc' OR a.new_bill_number LIKE '%$inv_esc%')";
}

if ($from_date !== '' && $to_date !== '') {
    $where_clauses[] = "a.bill_date BETWEEN '$from_date' AND '$to_date'";
} elseif ($from_date !== '') {
    $where_clauses[] = "a.bill_date >= '$from_date'";
} elseif ($to_date !== '') {
    $where_clauses[] = "a.bill_date <= '$to_date'";
}

if ($search_query !== '') {
    $q_esc = mysqli_real_escape_string($con, $search_query);
    $where_clauses[] = "(a.bill_id LIKE '%$q_esc%' OR a.new_bill_number LIKE '%$q_esc%' OR p.first_name LIKE '%$q_esc%' OR p.last_name LIKE '%$q_esc%' OR p.phone_number LIKE '%$q_esc%' OR a.pay_by LIKE '%$q_esc%' OR a.bill_prepare_by LIKE '%$q_esc%')";
}

$where_sql = implode(" AND ", $where_clauses);

// Scope Summary & KPI Metrics
$kpi = [
    'total_bills' => 0,
    'total_sales' => 0,
    'total_paid'  => 0,
    'total_bal'   => 0,
    'avg_ticket'  => 0
];

// Customer Specific Dossier (if cid selected)
$customer_dossier = null;
if ($cid_param !== '' && $cid_param !== '-1' && $con) {
    $c_esc = mysqli_real_escape_string($con, $cid_param);
    $q_cust = mysqli_query($con, "SELECT person_id, first_name, last_name, phone_number, email FROM phppos_people WHERE person_id = '$c_esc' LIMIT 1");
    if ($q_cust && $rcust = mysqli_fetch_assoc($q_cust)) {
        $customer_dossier = $rcust;
        $q_paid = mysqli_query($con, "SELECT COALESCE(SUM(paid_amount), 0), COALESCE(SUM(CAST(amountTotal AS DECIMAL(12,2))), 0) FROM approval WHERE cust_id = '$c_esc' AND status = 'S'");
        $r_paid = mysqli_fetch_row($q_paid);
        $customer_dossier['lifetime_paid'] = floatval($r_paid[0] ?? 0);
        $customer_dossier['lifetime_sales'] = floatval($r_paid[1] ?? 0);
        $customer_dossier['lifetime_bal'] = max(0, $customer_dossier['lifetime_sales'] - $customer_dossier['lifetime_paid']);
    }
}

// Fast Scope KPI Query
if ($con) {
    $sql_kpi = "
        SELECT 
            COUNT(a.bill_id) as total_bills,
            COALESCE(SUM(CAST(a.amountTotal AS DECIMAL(12,2))), 0) as total_sales,
            COALESCE(SUM(a.paid_amount), 0) as total_paid,
            COALESCE(SUM(CAST(a.amountTotal AS DECIMAL(12,2)) - a.paid_amount), 0) as total_bal
        FROM approval a
        LEFT JOIN phppos_people p ON a.cust_id = p.person_id
        WHERE $where_sql
    ";
    $res_kpi = mysqli_query($con, $sql_kpi);
    if ($res_kpi && $rkpi = mysqli_fetch_assoc($res_kpi)) {
        $kpi['total_bills'] = intval($rkpi['total_bills'] ?? 0);
        $kpi['total_sales'] = floatval($rkpi['total_sales'] ?? 0);
        $kpi['total_paid']  = floatval($rkpi['total_paid'] ?? 0);
        $kpi['total_bal']   = floatval($rkpi['total_bal'] ?? 0);
        if ($kpi['total_bills'] > 0) {
            $kpi['avg_ticket'] = round($kpi['total_sales'] / $kpi['total_bills'], 2);
        }
    }
}

// Server-side CSV Export
if ($is_export) {
    if ($con) {
        $export_sql = "
            SELECT a.*, p.first_name, p.last_name, p.phone_number
            FROM approval a
            LEFT JOIN phppos_people p ON a.cust_id = p.person_id
            WHERE $where_sql
            ORDER BY a.bill_id DESC
        ";
        $export_res = mysqli_query($con, $export_sql);
        if ($export_res) {
            $filename = "Sales_Report_" . date('Ymd_His') . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Bill Date', 'Invoice / Bill No', 'Bill ID', 'Customer Name', 'Phone Number',
                'Status', 'Sales Amount (₹)', 'Paid Amount (₹)', 'Balance Due (₹)',
                'Payment Mode', 'Prepared By', 'Company'
            ]);

            while ($erow = mysqli_fetch_assoc($export_res)) {
                $cust_name = trim(($erow['first_name'] ?? '') . ' ' . ($erow['last_name'] ?? ''));
                if (!$cust_name) $cust_name = 'Walk-in / General';
                $phone = $erow['phone_number'] ?: '—';
                $bill_no = !empty($erow['new_bill_number']) ? $erow['new_bill_number'] : $erow['bill_id'];
                $bill_date = ($erow['bill_date'] && $erow['bill_date'] !== '0000-00-00') ? date('d-m-Y', strtotime($erow['bill_date'])) : '—';
                $amt_total = floatval($erow['amountTotal'] ?? 0);
                $paid_amt  = floatval($erow['paid_amount'] ?? 0);
                $bal_amt   = $amt_total - $paid_amt;

                $st_text = $erow['status'] === 'S' ? 'Confirmed Sale' : ($erow['status'] === 'A' ? 'Approval' : $erow['status']);

                fputcsv($output, [
                    $bill_date,
                    $bill_no,
                    $erow['bill_id'],
                    $cust_name,
                    $phone,
                    $st_text,
                    $amt_total,
                    $paid_amt,
                    $bal_amt,
                    $erow['pay_by'] ?: '—',
                    $erow['bill_prepare_by'] ?: '—',
                    $erow['company_name'] ?: 'Sri Shringarr'
                ]);
            }
            fclose($output);
            exit;
        }
    }
}

// Pagination & Query Execution
$total_records = $kpi['total_bills'];
$total_pages   = $is_unlimited ? 1 : max(1, ceil($total_records / $per_page));
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $per_page;
}

$rows = [];
if ($con && $total_records > 0) {
    $sql_data = "
        SELECT a.*, p.first_name, p.last_name, p.phone_number
        FROM approval a
        LEFT JOIN phppos_people p ON a.cust_id = p.person_id
        WHERE $where_sql
        ORDER BY a.bill_id DESC
        LIMIT $offset, $per_page
    ";
    $res_data = mysqli_query($con, $sql_data);
    if ($res_data) {
        while ($r = mysqli_fetch_assoc($res_data)) {
            $rows[] = $r;
        }
    }
}

// Calculate Page Row Subtotals
$page_sales_sum = 0;
$page_paid_sum  = 0;
$page_bal_sum   = 0;
foreach ($rows as $r) {
    $a_tot = floatval($r['amountTotal'] ?? 0);
    $p_tot = floatval($r['paid_amount'] ?? 0);
    $page_sales_sum += $a_tot;
    $page_paid_sum  += $p_tot;
    $page_bal_sum   += ($a_tot - $p_tot);
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
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"
                integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

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

                        /* Header */
                        .pm-page-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            flex-wrap: wrap;
                            gap: 16px;
                            margin-bottom: 20px;
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

                        /* 5 KPI Metric Cards - 1 Single Row */
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
                            margin-bottom: 20px;
                            overflow: hidden;
                        }

                        .pm-card-header {
                            padding: 14px 20px;
                            border-bottom: 1px solid var(--pm-slate-200);
                            background: #ffffff;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            flex-wrap: wrap;
                            gap: 12px;
                        }

                        .pm-card-title {
                            font-size: 14px;
                            font-weight: 600;
                            color: var(--pm-slate-900);
                            margin: 0;
                            display: flex;
                            align-items: center;
                            gap: 8px;
                        }

                        .pm-card-body {
                            padding: 16px 20px;
                        }

                        /* Customer Dossier Card */
                        .pm-dossier-card {
                            background: #ffffff;
                            border: 1px solid var(--pm-slate-200);
                            border-left: 4px solid var(--pm-slate-900);
                            border-radius: 8px;
                            padding: 14px 20px;
                            margin-bottom: 20px;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            flex-wrap: wrap;
                            gap: 16px;
                            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                        }

                        /* Filter Toolbar */
                        .pm-filter-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                            gap: 12px;
                            align-items: flex-end;
                        }

                        .pm-form-group {
                            display: flex;
                            flex-direction: column;
                            gap: 5px;
                        }

                        .pm-label {
                            font-size: 11px;
                            font-weight: 600;
                            text-transform: uppercase;
                            letter-spacing: 0.04em;
                            color: var(--pm-slate-600);
                            margin: 0;
                        }

                        .pm-input, .pm-select {
                            height: 36px;
                            padding: 0 10px;
                            font-size: 13px;
                            border: 1px solid var(--pm-slate-200);
                            border-radius: 6px;
                            background: #ffffff;
                            color: var(--pm-slate-800);
                            outline: none;
                            transition: border-color 0.15s ease, box-shadow 0.15s ease;
                            width: 100%;
                            box-sizing: border-box;
                        }

                        .pm-input:focus, .pm-select:focus {
                            border-color: var(--pm-slate-900);
                            box-shadow: 0 0 0 1px var(--pm-slate-900);
                        }

                        /* Date Presets */
                        .pm-preset-bar {
                            display: flex;
                            align-items: center;
                            flex-wrap: wrap;
                            gap: 6px;
                            margin-top: 12px;
                            padding-top: 12px;
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
                            height: 36px;
                            padding: 0 14px;
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
                            box-sizing: border-box;
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
                            height: 28px;
                            padding: 0 8px;
                            font-size: 11px;
                            border-radius: 4px;
                            gap: 4px;
                        }

                        /* Table Toolbar */
                        .pm-table-toolbar {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            flex-wrap: wrap;
                            gap: 12px;
                            padding: 12px 20px;
                            background: var(--pm-slate-50);
                            border-bottom: 1px solid var(--pm-slate-200);
                        }

                        .pm-search-input {
                            height: 34px;
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
                            padding: 11px 14px;
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

                        /* Badges & Pills */
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

                        .pm-badge-neutral {
                            display: inline-flex;
                            align-items: center;
                            gap: 4px;
                            padding: 2px 7px;
                            font-size: 11px;
                            font-weight: 500;
                            border-radius: 4px;
                            background: var(--pm-slate-100);
                            border: 1px solid var(--pm-slate-200);
                            color: var(--pm-slate-600);
                        }

                        .pm-badge-due {
                            display: inline-flex;
                            align-items: center;
                            padding: 2px 6px;
                            font-size: 10px;
                            font-weight: 600;
                            border-radius: 4px;
                            background: var(--pm-slate-100);
                            border: 1px solid var(--pm-slate-300);
                            color: var(--pm-slate-800);
                            margin-left: 4px;
                        }

                        .pm-status-pill {
                            display: inline-flex;
                            align-items: center;
                            gap: 5px;
                            padding: 2px 8px;
                            font-size: 11px;
                            font-weight: 500;
                            border-radius: 4px;
                            background: var(--pm-slate-100);
                            border: 1px solid var(--pm-slate-200);
                            color: var(--pm-slate-700);
                        }

                        .pm-status-dot {
                            width: 6px;
                            height: 6px;
                            border-radius: 50%;
                            background: var(--pm-slate-400);
                        }

                        .pm-status-dot.active {
                            background: #10b981;
                        }

                        /* Pagination Bar */
                        .pm-pagination-bar {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 12px 20px;
                            background: #ffffff;
                            border-top: 1px solid var(--pm-slate-200);
                            flex-wrap: wrap;
                            gap: 12px;
                        }

                        .pm-pagination-info {
                            font-size: 12px;
                            color: var(--pm-slate-500);
                        }

                        .pm-pagination-links {
                            display: flex;
                            gap: 4px;
                            align-items: center;
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
                            border: 1px solid var(--pm-slate-200);
                            background: #ffffff;
                            color: var(--pm-slate-700);
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
                            .salereport-container {
                                max-width: 100% !important;
                            }
                        }
                    </style>

                    <div class="salereport-container">

                        <!-- Page Header -->
                        <div class="pm-page-header">
                            <div>
                                <h1 class="pm-page-title">
                                    <i class="fa-solid fa-receipt" style="margin-right: 10px; font-size: 20px; color: var(--pm-slate-700);"></i>
                                    Sales Report
                                </h1>
                                <p class="pm-page-subtitle">
                                    Comprehensive sold invoices ledger, customer transaction history, collections, and outstanding balance receivables.
                                </p>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <button type="button" class="pm-btn pm-btn-secondary" onclick="window.print();">
                                    <i class="fa fa-print"></i> Print Report
                                </button>
                                <a href="saleReport.php?export=1&<?= http_build_query($_GET) ?>" class="pm-btn pm-btn-primary" id="btnExportCSV">
                                    <i class="fa fa-download"></i> Export CSV
                                </a>
                            </div>
                        </div>

                        <!-- KPI Metric Cards - 5 Cards across in a single row -->
                        <div class="pm-kpi-grid">
                            <div class="pm-kpi-card">
                                <div class="pm-kpi-header">
                                    <span class="pm-kpi-label">Total Sales Turnover</span>
                                    <div class="pm-kpi-icon"><i class="fa fa-inr"></i></div>
                                </div>
                                <div class="pm-kpi-value">₹ <?= number_format($kpi['total_sales']) ?></div>
                                <div class="pm-kpi-sub">
                                    Gross sales volume in scope
                                </div>
                            </div>

                            <div class="pm-kpi-card">
                                <div class="pm-kpi-header">
                                    <span class="pm-kpi-label">Sales Invoices</span>
                                    <div class="pm-kpi-icon"><i class="fa fa-file-invoice"></i></div>
                                </div>
                                <div class="pm-kpi-value"><?= number_format($kpi['total_bills']) ?></div>
                                <div class="pm-kpi-sub">Total billed transactions</div>
                            </div>

                            <div class="pm-kpi-card">
                                <div class="pm-kpi-header">
                                    <span class="pm-kpi-label">Total Collected</span>
                                    <div class="pm-kpi-icon"><i class="fa fa-check-circle"></i></div>
                                </div>
                                <div class="pm-kpi-value">₹ <?= number_format($kpi['total_paid']) ?></div>
                                <div class="pm-kpi-sub">Realized sales receipts</div>
                            </div>

                            <div class="pm-kpi-card">
                                <div class="pm-kpi-header">
                                    <span class="pm-kpi-label">Balance Receivable</span>
                                    <div class="pm-kpi-icon"><i class="fa fa-scale-balanced"></i></div>
                                </div>
                                <div class="pm-kpi-value">₹ <?= number_format($kpi['total_bal']) ?></div>
                                <div class="pm-kpi-sub">Pending customer collections</div>
                            </div>

                            <div class="pm-kpi-card">
                                <div class="pm-kpi-header">
                                    <span class="pm-kpi-label">Avg. Ticket Size</span>
                                    <div class="pm-kpi-icon"><i class="fa fa-chart-pie"></i></div>
                                </div>
                                <div class="pm-kpi-value">₹ <?= number_format($kpi['avg_ticket']) ?></div>
                                <div class="pm-kpi-sub">Average value per invoice</div>
                            </div>
                        </div>

                        <!-- Customer Dossier (if cid selected) -->
                        <?php if ($customer_dossier): ?>
                        <div class="pm-dossier-card">
                            <div>
                                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--pm-slate-400); letter-spacing: 0.04em;">Customer Dossier</div>
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
                                    <div style="font-size: 11px; font-weight: 600; color: var(--pm-slate-400); text-transform: uppercase;">Total Sales</div>
                                    <div style="font-size: 16px; font-weight: 700; color: var(--pm-slate-900); font-family: ui-monospace, monospace;">₹ <?= number_format($customer_dossier['lifetime_sales']) ?></div>
                                </div>
                                <div style="text-align: right; border-left: 1px solid var(--pm-slate-200); padding-left: 16px;">
                                    <div style="font-size: 11px; font-weight: 600; color: var(--pm-slate-400); text-transform: uppercase;">Total Paid</div>
                                    <div style="font-size: 16px; font-weight: 700; color: var(--pm-slate-900); font-family: ui-monospace, monospace;">₹ <?= number_format($customer_dossier['lifetime_paid']) ?></div>
                                </div>
                                <div style="text-align: right; border-left: 1px solid var(--pm-slate-200); padding-left: 16px;">
                                    <div style="font-size: 11px; font-weight: 600; color: var(--pm-slate-400); text-transform: uppercase;">Outstanding Due</div>
                                    <div style="font-size: 16px; font-weight: 700; color: var(--pm-slate-900); font-family: ui-monospace, monospace;">₹ <?= number_format($customer_dossier['lifetime_bal']) ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Filter Toolbar Card -->
                        <div class="pm-card">
                            <div class="pm-card-body">
                                <form id="filterForm" method="GET" action="saleReport.php">
                                    <input type="hidden" name="cid" id="cid" value="<?= htmlspecialchars($cid_param) ?>">

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
                                                   placeholder="e.g. SSFS-26/S-00085 or 16002" 
                                                   value="<?= htmlspecialchars($invno_param) ?>">
                                        </div>

                                        <!-- Phone Number -->
                                        <div class="pm-form-group">
                                            <label class="pm-label" for="phoneNo">
                                                Phone Number
                                                <a href="javascript:void(0)" onclick="loadPhoneNo();" style="font-size: 10px; text-transform: none; margin-left: 6px; color: var(--pm-slate-500); text-decoration: underline;">(Lookup)</a>
                                            </label>
                                            <input type="text" class="pm-input" id="phoneNo" name="phoneNo" 
                                                   placeholder="e.g. 9820012345" 
                                                   value="<?= htmlspecialchars($phone_param) ?>">
                                        </div>

                                        <!-- Customer / Search Query -->
                                        <div class="pm-form-group" style="min-width: 170px;">
                                            <label class="pm-label" for="filterQ">Customer / Search</label>
                                            <input type="text" class="pm-input" id="filterQ" name="q" 
                                                   placeholder="Search by customer, biller..." 
                                                   value="<?= htmlspecialchars($search_query) ?>">
                                        </div>

                                        <!-- Status -->
                                        <div class="pm-form-group" style="max-width: 130px;">
                                            <label class="pm-label" for="status">Status</label>
                                            <select class="pm-select" id="status" name="status">
                                                <option value="S" <?= ($status_param === 'S') ? 'selected' : '' ?>>Sales (S)</option>
                                                <option value="A" <?= ($status_param === 'A') ? 'selected' : '' ?>>Approvals (A)</option>
                                                <option value="all" <?= ($status_param === 'all' || $status_param === '') ? 'selected' : '' ?>>All Status</option>
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

                                        <!-- Filter Actions -->
                                        <div style="display: flex; gap: 8px;">
                                            <button type="submit" class="pm-btn pm-btn-primary" style="flex: 1;" name="submit" value="Search">
                                                <i class="fa fa-filter"></i> Apply
                                            </button>
                                            <a href="saleReport.php" class="pm-btn pm-btn-secondary" title="Clear all filters">
                                                <i class="fa fa-rotate-left"></i>
                                            </a>
                                        </div>

                                    </div>

                                    <!-- Quick Date Range Presets -->
                                    <div class="pm-preset-bar">
                                        <span class="pm-preset-label"><i class="fa fa-bolt" style="font-size: 10px; margin-right: 3px;"></i> Quick Presets:</span>
                                        <button type="button" class="pm-preset-btn" onclick="applyDatePreset('today');">Today</button>
                                        <button type="button" class="pm-preset-btn" onclick="applyDatePreset('this_month');">This Month</button>
                                        <button type="button" class="pm-preset-btn" onclick="applyDatePreset('last_30');">Last 30 Days</button>
                                        <button type="button" class="pm-preset-btn" onclick="applyDatePreset('this_fy');">This FY</button>
                                        <button type="button" class="pm-preset-btn" onclick="applyDatePreset('all_time');">All Time</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Main Table Card -->
                        <div class="pm-card">

                            <!-- Table Toolbar (Search within page & quick status count) -->
                            <div class="pm-table-toolbar">
                                <div class="pm-search-wrapper">
                                    <i class="fa fa-magnifying-glass"></i>
                                    <input type="text" id="tableSearch" class="pm-search-input" placeholder="Quick filter loaded rows...">
                                </div>
                                <div style="display: flex; gap: 12px; align-items: center;">
                                    <span class="pm-badge-neutral">
                                        Scope: <?= number_format($total_records) ?> bills
                                    </span>
                                    <span class="pm-badge-neutral" id="visibleRowCountBadge">
                                        Showing <?= count($rows) ?> rows
                                    </span>
                                </div>
                            </div>

                            <!-- Table Container -->
                            <div class="pm-table-container">
                                <table class="pm-table" id="salesTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Invoice / Bill No</th>
                                            <th>Date</th>
                                            <th>Customer Details</th>
                                            <th>Payment Mode</th>
                                            <th style="text-align: right;">Sales Amount (₹)</th>
                                            <th style="text-align: right;">Paid Amount (₹)</th>
                                            <th style="text-align: right;">Balance Due (₹)</th>
                                            <th>Billed By</th>
                                            <th style="text-align: center; width: 140px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($rows)): ?>
                                            <tr>
                                                <td colspan="10" style="text-align: center; padding: 40px 20px; color: var(--pm-slate-500);">
                                                    <i class="fa fa-folder-open" style="font-size: 28px; color: var(--pm-slate-300); margin-bottom: 8px; display: block;"></i>
                                                    No sales invoices found matching the current filter criteria.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php
                                            $row_idx = $offset + 1;
                                            foreach ($rows as $row):
                                                $bill_id = $row['bill_id'];
                                                $bill_no = !empty($row['new_bill_number']) ? $row['new_bill_number'] : $bill_id;
                                                $cust_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                                if (!$cust_name) $cust_name = 'Walk-in / General';
                                                $phone = $row['phone_number'] ?? '';
                                                $bill_date = ($row['bill_date'] && $row['bill_date'] !== '0000-00-00') ? date('d/m/Y', strtotime($row['bill_date'])) : '—';
                                                
                                                $amt_total = floatval($row['amountTotal'] ?? 0);
                                                $paid_amt  = floatval($row['paid_amount'] ?? 0);
                                                $bal_amt   = $amt_total - $paid_amt;

                                                $is_gst = (!empty($row['typ']) && $row['typ'] == 1) || (!empty($row['statetyp']) && $row['statetyp'] > 0);
                                            ?>
                                            <tr class="sales-row">
                                                <td style="color: var(--pm-slate-400); font-family: ui-monospace, monospace;"><?= $row_idx ?></td>
                                                <td>
                                                    <span class="pm-bill-pill">
                                                        <i class="fa fa-hashtag" style="font-size: 9px; opacity: 0.6;"></i> <?= htmlspecialchars($bill_no) ?>
                                                    </span>
                                                    <?php if (!empty($row['new_bill_number']) && $row['new_bill_number'] !== $bill_id): ?>
                                                        <span style="font-size: 10px; color: var(--pm-slate-400); margin-left: 4px;">(#<?= $bill_id ?>)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span style="font-family: ui-monospace, monospace;"><?= $bill_date ?></span>
                                                </td>
                                                <td>
                                                    <div style="font-weight: 600; color: var(--pm-slate-900);">
                                                        <a href="saleReport.php?cid=<?= urlencode($row['cust_id']) ?>" style="color: inherit; text-decoration: none;" title="Filter by this customer">
                                                            <?= htmlspecialchars($cust_name) ?>
                                                        </a>
                                                    </div>
                                                    <?php if (!empty($phone)): ?>
                                                        <div style="font-size: 11px; color: var(--pm-slate-500); margin-top: 1px;">
                                                            <i class="fa fa-phone" style="font-size: 9px;"></i> <?= htmlspecialchars($phone) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($row['pay_by'])): ?>
                                                        <span class="pm-badge-neutral"><?= htmlspecialchars($row['pay_by']) ?></span>
                                                    <?php else: ?>
                                                        <span style="color: var(--pm-slate-400);">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align: right; font-weight: 600; font-family: ui-monospace, monospace; color: var(--pm-slate-900);">
                                                    ₹ <?= number_format($amt_total) ?>
                                                </td>
                                                <td style="text-align: right; font-family: ui-monospace, monospace; color: var(--pm-slate-700);">
                                                    ₹ <?= number_format($paid_amt) ?>
                                                </td>
                                                <td style="text-align: right; font-family: ui-monospace, monospace;">
                                                    <?php if ($bal_amt > 0): ?>
                                                        <span style="font-weight: 700; color: var(--pm-slate-900);">₹ <?= number_format($bal_amt) ?></span>
                                                        <span class="pm-badge-due">Due</span>
                                                    <?php else: ?>
                                                        <span style="color: var(--pm-slate-400);">₹ 0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span style="font-size: 11px; color: var(--pm-slate-600);">
                                                        <?= htmlspecialchars($row['bill_prepare_by'] ?: ($row['bill_by'] ?: '—')) ?>
                                                    </span>
                                                </td>
                                                <td style="text-align: center;">
                                                    <div style="display: inline-flex; gap: 4px;">
                                                        <a href="sales_report_detail.php?id=<?= $bill_id ?>" target="_blank" class="pm-btn pm-btn-secondary pm-btn-sm" title="View Full Bill Detail">
                                                            <i class="fa fa-eye"></i> Detail
                                                        </a>
                                                        <?php if ($is_gst): ?>
                                                            <a href="sales_report_detailgstnew.php?id=<?= $bill_id ?>" target="_blank" class="pm-btn pm-btn-secondary pm-btn-sm" title="View GST Invoice">
                                                                <i class="fa fa-file-pdf"></i> GST
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                                $row_idx++;
                                            endforeach;
                                            ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if (!empty($rows)): ?>
                                    <tfoot>
                                        <tr class="pm-table-footer">
                                            <td colspan="5" style="text-align: right; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">
                                                Page Subtotal (<?= count($rows) ?> rows):
                                            </td>
                                            <td style="text-align: right; font-family: ui-monospace, monospace; font-size: 13px;">
                                                ₹ <?= number_format($page_sales_sum) ?>
                                            </td>
                                            <td style="text-align: right; font-family: ui-monospace, monospace; font-size: 13px;">
                                                ₹ <?= number_format($page_paid_sum) ?>
                                            </td>
                                            <td style="text-align: right; font-family: ui-monospace, monospace; font-size: 13px;">
                                                ₹ <?= number_format($page_bal_sum) ?>
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                        <tr class="pm-table-footer" style="background: var(--pm-slate-100); border-top: 1px solid var(--pm-slate-300);">
                                            <td colspan="5" style="text-align: right; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--pm-slate-800);">
                                                Scope Grand Total (<?= number_format($kpi['total_bills']) ?> invoices):
                                            </td>
                                            <td style="text-align: right; font-family: ui-monospace, monospace; font-size: 14px; font-weight: 800; color: var(--pm-slate-900);">
                                                ₹ <?= number_format($kpi['total_sales']) ?>
                                            </td>
                                            <td style="text-align: right; font-family: ui-monospace, monospace; font-size: 14px; font-weight: 800; color: var(--pm-slate-900);">
                                                ₹ <?= number_format($kpi['total_paid']) ?>
                                            </td>
                                            <td style="text-align: right; font-family: ui-monospace, monospace; font-size: 14px; font-weight: 800; color: var(--pm-slate-900);">
                                                ₹ <?= number_format($kpi['total_bal']) ?>
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>

                            <!-- Pagination Bar -->
                            <?php if (!$is_unlimited && $total_pages > 1): ?>
                            <div class="pm-pagination-bar">
                                <div class="pm-pagination-info">
                                    Showing <?= number_format($offset + 1) ?> to <?= number_format(min($total_records, $offset + $per_page)) ?> of <?= number_format($total_records) ?> entries
                                </div>
                                <div class="pm-pagination-links">
                                    <?php
                                    $query_params = $_GET;

                                    // Previous Button
                                    $prev_page = max(1, $current_page - 1);
                                    $query_params['page'] = $prev_page;
                                    $prev_url = 'saleReport.php?' . http_build_query($query_params);
                                    ?>
                                    <a href="<?= $prev_url ?>" class="pm-page-link <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                        <i class="fa fa-chevron-left" style="font-size: 10px;"></i>
                                    </a>

                                    <?php
                                    $start_p = max(1, $current_page - 2);
                                    $end_p   = min($total_pages, $current_page + 2);

                                    if ($start_p > 1) {
                                        $query_params['page'] = 1;
                                        echo '<a href="saleReport.php?' . http_build_query($query_params) . '" class="pm-page-link">1</a>';
                                        if ($start_p > 2) echo '<span class="pm-page-link disabled">&hellip;</span>';
                                    }

                                    for ($p = $start_p; $p <= $end_p; $p++) {
                                        $query_params['page'] = $p;
                                        $p_url = 'saleReport.php?' . http_build_query($query_params);
                                        $act = ($p == $current_page) ? 'active' : '';
                                        echo '<a href="' . $p_url . '" class="pm-page-link ' . $act . '">' . $p . '</a>';
                                    }

                                    if ($end_p < $total_pages) {
                                        if ($end_p < $total_pages - 1) echo '<span class="pm-page-link disabled">&hellip;</span>';
                                        $query_params['page'] = $total_pages;
                                        echo '<a href="saleReport.php?' . http_build_query($query_params) . '" class="pm-page-link">' . $total_pages . '</a>';
                                    }

                                    // Next Button
                                    $next_page = min($total_pages, $current_page + 1);
                                    $query_params['page'] = $next_page;
                                    $next_url = 'saleReport.php?' . http_build_query($query_params);
                                    ?>
                                    <a href="<?= $next_url ?>" class="pm-page-link <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                        <i class="fa fa-chevron-right" style="font-size: 10px;"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                        </div>

                    </div>

                    <script>
                        // Quick Date Range Presets
                        function applyDatePreset(preset) {
                            const fromInput = document.getElementById('from');
                            const toInput = document.getElementById('to_date');
                            const now = new Date();

                            function formatDate(d) {
                                const year = d.getFullYear();
                                const month = String(d.getMonth() + 1).padStart(2, '0');
                                const day = String(d.getDate()).padStart(2, '0');
                                return `${year}-${month}-${day}`;
                            }

                            if (preset === 'today') {
                                const todayStr = formatDate(now);
                                fromInput.value = todayStr;
                                toInput.value = todayStr;
                            } else if (preset === 'this_month') {
                                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                                fromInput.value = formatDate(firstDay);
                                toInput.value = formatDate(now);
                            } else if (preset === 'last_30') {
                                const pastDate = new Date();
                                pastDate.setDate(now.getDate() - 30);
                                fromInput.value = formatDate(pastDate);
                                toInput.value = formatDate(now);
                            } else if (preset === 'this_fy') {
                                let fyYear = now.getFullYear();
                                if (now.getMonth() < 3) fyYear -= 1; // Before April
                                fromInput.value = `${fyYear}-04-01`;
                                toInput.value = formatDate(now);
                            } else if (preset === 'all_time') {
                                fromInput.value = '';
                                toInput.value = '';
                            }
                            document.getElementById('filterForm').submit();
                        }

                        // Phone Number Lookup
                        function loadPhoneNo() {
                            const phoneVal = document.getElementById('phoneNo').value.trim();
                            if (!phoneVal) {
                                alert("Please enter a phone number to search.");
                                document.getElementById('phoneNo').focus();
                                return;
                            }

                            const xhr = new XMLHttpRequest();
                            xhr.onreadystatechange = function() {
                                if (xhr.readyState === 4 && xhr.status === 200) {
                                    const parts = xhr.responseText.trim().split('&&');
                                    if (parts[0] === "0" || !parts[1] || parts[1] === "0") {
                                        alert("No registered customer found with phone: " + phoneVal + ".\nDirect filter will still search invoices.");
                                        document.getElementById('filterForm').submit();
                                    } else {
                                        document.getElementById("cid").value = parts[1];
                                        document.getElementById('filterForm').submit();
                                    }
                                }
                            };
                            xhr.open("GET", "getbyphone.php?cid=" + encodeURIComponent(phoneVal), true);
                            xhr.send();
                        }

                        // Instant Client-side Table Search (Debounced 200ms)
                        (function() {
                            const searchInput = document.getElementById('tableSearch');
                            const rows = document.querySelectorAll('#salesTable tbody tr.sales-row');
                            const countBadge = document.getElementById('visibleRowCountBadge');
                            let timeout = null;

                            if (!searchInput) return;

                            searchInput.addEventListener('input', function() {
                                clearTimeout(timeout);
                                timeout = setTimeout(function() {
                                    const term = searchInput.value.toLowerCase().trim();
                                    let visibleCount = 0;

                                    rows.forEach(function(row) {
                                        const text = row.textContent.toLowerCase();
                                        if (term === '' || text.indexOf(term) !== -1) {
                                            row.style.display = '';
                                            visibleCount++;
                                        } else {
                                            row.style.display = 'none';
                                        }
                                    });

                                    if (countBadge) {
                                        countBadge.textContent = 'Showing ' + visibleCount + ' rows';
                                    }
                                }, 200);
                            });
                        })();
                    </script>

                </div><!-- /content-wrapper -->
            </div><!-- /main-panel -->
        </div><!-- /container-fluid page-body-wrapper -->
    </div><!-- /container-scroller (opened in navbar/top-navbar) -->

<?php
if ($con) {
    CloseCon($con);
}
?>
</body>
</html>