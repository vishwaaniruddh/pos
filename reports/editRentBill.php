<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

$con = null;
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
    if (function_exists('OpenSrishringarrCon')) {
        $con = OpenSrishringarrCon();
    }
}

if (!$con) {
    $is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);
    if ($is_local) {
        $con = @mysqli_connect("localhost", "root", "", "u464193275_srishringarr");
    } else {
        $con = @mysqli_connect("localhost", "u464193275_sarmicropos", "Mypos1234", "u464193275_srishringarr");
    }
}

// 5 KPI Metrics
$kpi = [
    'total_bills'     => 0,
    'booked_count'    => 0,
    'total_rent'      => 0,
    'total_deposit'   => 0,
    'recent_30d'      => 0
];

if ($con) {
    $q_kpi = mysqli_query($con, "
        SELECT 
            COUNT(*) as total_bills,
            COALESCE(SUM(rent_amount), 0) as total_rent,
            COALESCE(SUM(CASE WHEN amount REGEXP '^[0-9]+(\\\\.[0-9]+)?$' THEN CAST(amount AS DECIMAL(15,2)) ELSE 0 END), 0) as total_deposit,
            COALESCE(SUM(CASE WHEN booking_status = 'Booked' THEN 1 ELSE 0 END), 0) as booked_count,
            COALESCE(SUM(CASE WHEN bill_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) as recent_30d
        FROM phppos_rent
    ");
    if ($q_kpi && $rk = mysqli_fetch_assoc($q_kpi)) {
        $kpi['total_bills']   = intval($rk['total_bills']);
        $kpi['booked_count']  = intval($rk['booked_count']);
        $kpi['total_rent']    = floatval($rk['total_rent']);
        $kpi['total_deposit'] = floatval($rk['total_deposit']);
        $kpi['recent_30d']    = intval($rk['recent_30d']);
    }
}

// Search & Filter Parameters
$searchBillId    = isset($_GET['bill_id']) ? trim($_GET['bill_id']) : '';
$searchCustomer  = isset($_GET['customer']) ? trim($_GET['customer']) : '';
$searchStartDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$searchEndDate   = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$statusFilter    = isset($_GET['booking_status']) ? trim($_GET['booking_status']) : 'all';

$limit  = isset($_GET['limit']) ? max(10, min(250, intval($_GET['limit']))) : 50;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Construct WHERE conditions
$where_clauses = ["1=1"];

if ($searchBillId !== '') {
    $safe_bid = mysqli_real_escape_string($con, $searchBillId);
    $where_clauses[] = "(r.bill_id = '$safe_bid' OR r.new_bill_number LIKE '%$safe_bid%')";
}

if ($searchCustomer !== '') {
    $safe_cust = mysqli_real_escape_string($con, $searchCustomer);
    $where_clauses[] = "(p.first_name LIKE '%$safe_cust%' OR p.last_name LIKE '%$safe_cust%' OR p.phone_number LIKE '%$safe_cust%' OR r.cust_name LIKE '%$safe_cust%')";
}

if ($searchStartDate !== '' && $searchEndDate !== '') {
    $safe_start = mysqli_real_escape_string($con, $searchStartDate);
    $safe_end   = mysqli_real_escape_string($con, $searchEndDate);
    $where_clauses[] = "r.bill_date BETWEEN '$safe_start' AND '$safe_end'";
} else if ($searchStartDate !== '') {
    $safe_start = mysqli_real_escape_string($con, $searchStartDate);
    $where_clauses[] = "r.bill_date >= '$safe_start'";
} else if ($searchEndDate !== '') {
    $safe_end = mysqli_real_escape_string($con, $searchEndDate);
    $where_clauses[] = "r.bill_date <= '$safe_end'";
}

if ($statusFilter !== 'all' && $statusFilter !== '') {
    $safe_st = mysqli_real_escape_string($con, $statusFilter);
    $where_clauses[] = "r.booking_status = '$safe_st'";
}

$where_sql = implode(' AND ', $where_clauses);

// Count Total Filtered Records
$total_filtered = 0;
if ($con) {
    $q_cnt = mysqli_query($con, "
        SELECT COUNT(*) as cnt 
        FROM phppos_rent r
        LEFT JOIN phppos_people p ON r.cust_id = p.person_id
        WHERE $where_sql
    ");
    if ($q_cnt && $rcnt = mysqli_fetch_assoc($q_cnt)) {
        $total_filtered = intval($rcnt['cnt']);
    }
}

$totalPages = ($total_filtered > 0) ? ceil($total_filtered / $limit) : 1;
if ($page > $totalPages) $page = $totalPages;

// Fetch Filtered Invoices
$invoices = [];
if ($con && $total_filtered > 0) {
    $q_inv = mysqli_query($con, "
        SELECT 
            r.bill_id, 
            r.cust_id, 
            r.bill_date, 
            r.new_bill_number,
            r.rent_amount,
            r.amount as deposit_amt,
            r.booking_status,
            r.pick_date,
            r.delivery_date,
            r.cust_name as fallback_name,
            p.first_name, 
            p.last_name,
            p.phone_number
        FROM phppos_rent r
        LEFT JOIN phppos_people p ON r.cust_id = p.person_id
        WHERE $where_sql
        ORDER BY r.bill_id DESC 
        LIMIT $limit OFFSET $offset
    ");
    if ($q_inv) {
        while ($row = mysqli_fetch_assoc($q_inv)) {
            $invoices[] = $row;
        }
    }
}

if (file_exists(__DIR__ . '/../top-header.php')) include_once(__DIR__ . '/../top-header.php');
if (file_exists(__DIR__ . '/../top-navbar.php')) include_once(__DIR__ . '/../top-navbar.php');
?>

<div class="container-fluid page-body-wrapper">
    <?php
    if (file_exists(__DIR__ . '/../navbar.php')) include_once(__DIR__ . '/../navbar.php');
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

                /* Page Header */
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

                /* Cards */
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

                /* Filter Row */
                .pm-filter-row {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 12px;
                    align-items: flex-end;
                }

                .pm-form-group {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }

                .pm-label {
                    font-size: 12px;
                    font-weight: 500;
                    color: var(--pm-slate-700);
                }

                .pm-select, .pm-input {
                    height: 36px;
                    padding: 0 12px;
                    font-size: 13px;
                    color: var(--pm-slate-900);
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    box-sizing: border-box;
                    outline: none;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-select:focus, .pm-input:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
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
                    height: 30px;
                    padding: 0 10px;
                    font-size: 12px;
                    border-radius: 4px;
                }

                .pm-btn-danger {
                    background: #ffffff;
                    color: #dc2626;
                    border-color: #fecaca;
                }

                .pm-btn-danger:hover {
                    background: #fef2f2;
                    border-color: #dc2626;
                }

                /* Preset Date Chips */
                .pm-preset-group {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 6px;
                    align-items: center;
                }

                .pm-chip {
                    padding: 4px 10px;
                    font-size: 11px;
                    font-weight: 500;
                    border-radius: 20px;
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-600);
                    border: 1px solid var(--pm-slate-200);
                    cursor: pointer;
                    text-decoration: none;
                    transition: all 0.15s ease;
                }

                .pm-chip:hover, .pm-chip.active {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                /* Table */
                .pm-table-container {
                    overflow-x: auto;
                }

                .pm-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 13px;
                }

                .pm-table th {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-600);
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                    padding: 10px 14px;
                    text-align: left;
                    border-bottom: 1px solid var(--pm-slate-200);
                    white-space: nowrap;
                }

                .pm-table td {
                    padding: 11px 14px;
                    border-bottom: 1px solid var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    vertical-align: middle;
                }

                .pm-table tr:hover td {
                    background-color: var(--pm-slate-50);
                }

                /* Status Badges */
                .pm-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    padding: 2px 8px;
                    font-size: 11px;
                    font-weight: 500;
                    border-radius: 4px;
                    white-space: nowrap;
                }

                .pm-badge-booked {
                    background: #f1f5f9;
                    color: #334155;
                    border: 1px solid #cbd5e1;
                }

                .pm-badge-neutral {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
                }

                /* Pagination */
                .pm-pagination-bar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 12px;
                    padding: 14px 20px;
                    border-top: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                }

                .pm-page-link {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    height: 32px;
                    min-width: 32px;
                    padding: 0 8px;
                    font-size: 12px;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 4px;
                    background: #ffffff;
                    color: var(--pm-slate-700);
                    text-decoration: none;
                    transition: all 0.15s ease;
                }

                .pm-page-link:hover, .pm-page-link.active {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                .pm-page-link.disabled {
                    opacity: 0.4;
                    pointer-events: none;
                }

                /* Print */
                @media print {
                    .navbar, #sidebar, .pm-filter-row, .pm-btn, .pm-preset-group, .pm-pagination-bar, .no-print {
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
                    .pm-kpi-grid {
                        grid-template-columns: repeat(5, 1fr) !important;
                        gap: 8px !important;
                    }
                }
            </style>

            <div class="edit-rent-bill-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-file-invoice" style="margin-right: 10px; font-size: 20px; color: var(--pm-slate-700);"></i>
                            Rent Bill Records & Modification
                        </h1>
                        <p class="pm-page-subtitle">
                            Search rental invoices, modify delivery and measurement dates, print customer receipts, and manage bookings.
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;" class="no-print">
                        <button type="button" class="pm-btn pm-btn-secondary" onclick="exportToCSV();">
                            <i class="fa fa-file-csv"></i> Export Visible CSV
                        </button>
                        <a href="rentReport.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-chart-line"></i> Rent Report
                        </a>
                        <a href="rent_return_new.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-rotate-left"></i> Returns Desk
                        </a>
                    </div>
                </div>

                <!-- 5 KPI Metric Cards - 1 Single Row -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Rent Invoices</span>
                            <div class="pm-kpi-icon"><i class="fa fa-receipt"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['total_bills']) ?></div>
                        <div class="pm-kpi-sub">Lifetime rental orders</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Active Bookings</span>
                            <div class="pm-kpi-icon"><i class="fa fa-bookmark"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['booked_count']) ?></div>
                        <div class="pm-kpi-sub">Upcoming / on-rent items</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Rent Revenue</span>
                            <div class="pm-kpi-icon"><i class="fa fa-inr"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['total_rent']) ?></div>
                        <div class="pm-kpi-sub">Cumulative rental earnings</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Security Deposits</span>
                            <div class="pm-kpi-icon"><i class="fa fa-shield-halved"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['total_deposit']) ?></div>
                        <div class="pm-kpi-sub">Cumulative caution funds</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Recent (Last 30 Days)</span>
                            <div class="pm-kpi-icon"><i class="fa fa-calendar-days"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['recent_30d']) ?></div>
                        <div class="pm-kpi-sub">Invoices booked recently</div>
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="pm-card no-print">
                    <div class="pm-card-header">
                        <h2 class="pm-card-title">
                            <i class="fa-solid fa-filter"></i> Search & Invoice Filters
                        </h2>
                        
                        <div class="pm-preset-group">
                            <span style="font-size: 11px; font-weight: 600; color: var(--pm-slate-400); text-transform: uppercase;">Presets:</span>
                            <button type="button" class="pm-chip" onclick="applyPreset('today');">Today</button>
                            <button type="button" class="pm-chip" onclick="applyPreset('this_month');">This Month</button>
                            <button type="button" class="pm-chip" onclick="applyPreset('last_month');">Last Month</button>
                            <button type="button" class="pm-chip" onclick="applyPreset('this_fy');">Financial Year</button>
                            <button type="button" class="pm-chip" onclick="applyPreset('all');">All Time</button>
                        </div>
                    </div>

                    <div class="pm-card-body">
                        <form method="GET" action="editRentBill.php" id="filterForm">
                            <div class="pm-filter-row">
                                <!-- Search by Bill ID / New Bill Number -->
                                <div class="pm-form-group" style="flex: 1.2; min-width: 170px;">
                                    <label class="pm-label" for="bill_id">Bill ID / Number</label>
                                    <div style="position: relative;">
                                        <i class="fa fa-receipt" style="position: absolute; left: 10px; top: 11px; color: var(--pm-slate-400); font-size: 12px;"></i>
                                        <input type="text" name="bill_id" id="bill_id" class="pm-input" style="padding-left: 30px; width: 100%;" placeholder="e.g. 12345, R-..." value="<?= htmlspecialchars($searchBillId) ?>">
                                    </div>
                                </div>

                                <!-- Customer Search -->
                                <div class="pm-form-group" style="flex: 1.5; min-width: 190px;">
                                    <label class="pm-label" for="customer">Customer Name / Phone</label>
                                    <div style="position: relative;">
                                        <i class="fa fa-user" style="position: absolute; left: 10px; top: 11px; color: var(--pm-slate-400); font-size: 12px;"></i>
                                        <input type="text" name="customer" id="customer" class="pm-input" style="padding-left: 30px; width: 100%;" placeholder="Customer name or mobile..." value="<?= htmlspecialchars($searchCustomer) ?>">
                                    </div>
                                </div>

                                <!-- Start Date -->
                                <div class="pm-form-group" style="width: 140px;">
                                    <label class="pm-label" for="start_date">Date From</label>
                                    <input type="date" name="start_date" id="start_date" class="pm-input" value="<?= htmlspecialchars($searchStartDate) ?>">
                                </div>

                                <!-- End Date -->
                                <div class="pm-form-group" style="width: 140px;">
                                    <label class="pm-label" for="end_date">Date To</label>
                                    <input type="date" name="end_date" id="end_date" class="pm-input" value="<?= htmlspecialchars($searchEndDate) ?>">
                                </div>

                                <!-- Booking Status -->
                                <div class="pm-form-group" style="width: 150px;">
                                    <label class="pm-label" for="booking_status">Booking Status</label>
                                    <select name="booking_status" id="booking_status" class="pm-select">
                                        <option value="all" <?= ($statusFilter === 'all') ? 'selected' : '' ?>>All Bookings</option>
                                        <option value="Booked" <?= ($statusFilter === 'Booked') ? 'selected' : '' ?>>Booked Only</option>
                                        <option value="Returned" <?= ($statusFilter === 'Returned') ? 'selected' : '' ?>>Returned</option>
                                        <option value="Picked" <?= ($statusFilter === 'Picked') ? 'selected' : '' ?>>Picked</option>
                                    </select>
                                </div>

                                <!-- Page Size -->
                                <div class="pm-form-group" style="width: 100px;">
                                    <label class="pm-label" for="limit">Page Size</label>
                                    <select name="limit" id="limit" class="pm-select">
                                        <option value="25" <?= ($limit === 25) ? 'selected' : '' ?>>25</option>
                                        <option value="50" <?= ($limit === 50) ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= ($limit === 100) ? 'selected' : '' ?>>100</option>
                                        <option value="200" <?= ($limit === 200) ? 'selected' : '' ?>>200</option>
                                    </select>
                                </div>

                                <!-- Action Buttons -->
                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="pm-btn pm-btn-primary">
                                        <i class="fa fa-magnifying-glass"></i> Search
                                    </button>
                                    <a href="editRentBill.php" class="pm-btn pm-btn-secondary">
                                        <i class="fa fa-rotate-left"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Rent Invoices Table Card -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <h2 class="pm-card-title">
                                <i class="fa-solid fa-list-check"></i> Rent Invoice Records
                            </h2>
                            <span class="pm-badge pm-badge-neutral">
                                <?= number_format($total_filtered) ?> Matching Invoices
                            </span>
                        </div>
                        <div style="font-size: 12px; color: var(--pm-slate-500);">
                            Showing page <?= $page ?> of <?= $totalPages ?>
                        </div>
                    </div>

                    <div class="pm-table-container">
                        <table class="pm-table" id="invoicesTable">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th style="width: 130px; white-space: nowrap;">Bill Number</th>
                                    <th style="width: 110px; white-space: nowrap;">Bill Date</th>
                                    <th style="white-space: nowrap;">Customer Details</th>
                                    <th style="width: 120px; text-align: right; white-space: nowrap;">Rent Amount (₹)</th>
                                    <th style="width: 120px; text-align: right; white-space: nowrap;">Deposit (₹)</th>
                                    <th style="width: 120px; text-align: center; white-space: nowrap;">Booking Status</th>
                                    <th style="width: 180px; text-align: center; white-space: nowrap;" class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($invoices)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 40px 20px; color: var(--pm-slate-400);">
                                            <i class="fa fa-folder-open" style="font-size: 28px; margin-bottom: 8px; display: block; color: var(--pm-slate-300);"></i>
                                            No rent bill records found matching your search criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $srno = $offset + 1;
                                    foreach ($invoices as $inv): 
                                        $bid         = intval($inv['bill_id']);
                                        $new_bill    = htmlspecialchars($inv['new_bill_number'] ?: '');
                                        $display_num = $new_bill ? $new_bill : '#' . $bid;
                                        $b_date      = ($inv['bill_date'] && $inv['bill_date'] !== '0000-00-00') ? date('d/m/Y', strtotime($inv['bill_date'])) : '—';
                                        
                                        $fname       = trim($inv['first_name'] ?? '');
                                        $lname       = trim($inv['last_name'] ?? '');
                                        $full_name   = trim("$fname $lname");
                                        if (!$full_name) $full_name = trim($inv['fallback_name'] ?? 'Customer #' . $inv['cust_id']);
                                        $phone       = htmlspecialchars($inv['phone_number'] ?? '');

                                        $rent_val    = floatval($inv['rent_amount']);
                                        $dep_val     = floatval($inv['deposit_amt']);
                                        $b_status    = htmlspecialchars($inv['booking_status'] ?: 'Booked');
                                    ?>
                                        <tr id="row_<?= $bid ?>">
                                            <!-- Sr No -->
                                            <td style="font-family: monospace; color: var(--pm-slate-400);"><?= $srno ?></td>

                                            <!-- Bill Number -->
                                            <td style="white-space: nowrap;">
                                                <div style="display: flex; flex-direction: column; white-space: nowrap;">
                                                    <span style="font-family: ui-monospace, SFMono-Regular, monospace; font-weight: 700; color: var(--pm-slate-900); white-space: nowrap;">
                                                        <?= $display_num ?>
                                                    </span>
                                                    <?php if ($new_bill && $new_bill != $bid): ?>
                                                        <span style="font-family: monospace; font-size: 11px; color: var(--pm-slate-400); white-space: nowrap;">ID: #<?= $bid ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <!-- Bill Date -->
                                            <td style="font-family: monospace;">
                                                <?= $b_date ?>
                                            </td>

                                            <!-- Customer -->
                                            <td style="white-space: nowrap !important;">
                                                <div style="display: flex; flex-direction: column; white-space: nowrap !important;">
                                                    <span style="font-weight: 600; color: var(--pm-slate-900); white-space: nowrap !important;">
                                                        <?= htmlspecialchars($full_name) ?>
                                                    </span>
                                                    <?php if ($phone): ?>
                                                        <span style="font-size: 11px; color: var(--pm-slate-500); font-family: monospace; white-space: nowrap !important;">
                                                            <i class="fa fa-phone" style="font-size: 10px; margin-right: 3px;"></i><?= $phone ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <!-- Rent Amount -->
                                            <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--pm-slate-900);">
                                                ₹ <?= number_format($rent_val, 2) ?>
                                            </td>

                                            <!-- Deposit -->
                                            <td style="text-align: right; font-family: monospace; font-weight: 600; color: var(--pm-slate-600);">
                                                <?= ($dep_val > 0) ? '₹ ' . number_format($dep_val, 2) : '—' ?>
                                            </td>

                                            <!-- Status -->
                                            <td style="text-align: center;">
                                                <span class="pm-badge pm-badge-booked">
                                                    <i class="fa fa-circle" style="font-size: 6px; color: var(--pm-slate-500);"></i>
                                                    <?= $b_status ?>
                                                </span>
                                            </td>

                                            <!-- Actions -->
                                            <td style="text-align: center;" class="no-print">
                                                <div style="display: inline-flex; gap: 6px;">
                                                    <!-- Edit Dates & Delivery -->
                                                    <a href="editrentbillDetails.php?bill_id=<?= $bid ?>" class="pm-btn pm-btn-secondary pm-btn-sm" title="Edit Bill Details & Dates">
                                                        <i class="fa fa-pen-to-square"></i> Edit
                                                    </a>

                                                    <!-- View Print Receipt -->
                                                    <a href="rent_report_detail.php?id=<?= $bid ?>" target="_blank" class="pm-btn pm-btn-secondary pm-btn-sm" title="View Full Bill">
                                                        <i class="fa fa-eye"></i> View
                                                    </a>

                                                    <!-- Delete Bill -->
                                                    <button type="button" class="pm-btn pm-btn-danger pm-btn-sm delete_bill" data-bill-id="<?= $bid ?>" title="Delete Bill">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        $srno++;
                                    endforeach; 
                                    ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Bar -->
                    <?php if ($totalPages > 1): 
                        $query_params = $_GET;
                        function get_rent_page_url($p, $params) {
                            $params['page'] = $p;
                            return 'editRentBill.php?' . http_build_query($params);
                        }
                    ?>
                        <div class="pm-pagination-bar no-print">
                            <div style="font-size: 13px; color: var(--pm-slate-500);">
                                Showing <strong><?= $offset + 1 ?></strong> to <strong><?= min($total_filtered, $offset + $limit) ?></strong> of <strong><?= number_format($total_filtered) ?></strong> Rent Bills
                            </div>
                            <div style="display: flex; gap: 4px; align-items: center;">
                                <a href="<?= get_rent_page_url(1, $query_params) ?>" class="pm-page-link <?= ($page <= 1) ? 'disabled' : '' ?>" title="First Page">
                                    <i class="fa fa-angles-left"></i>
                                </a>
                                <a href="<?= get_rent_page_url($page - 1, $query_params) ?>" class="pm-page-link <?= ($page <= 1) ? 'disabled' : '' ?>" title="Previous Page">
                                    <i class="fa fa-angle-left"></i>
                                </a>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page   = min($totalPages, $page + 2);
                                for ($p = $start_page; $p <= $end_page; $p++):
                                ?>
                                    <a href="<?= get_rent_page_url($p, $query_params) ?>" class="pm-page-link <?= ($p === $page) ? 'active' : '' ?>">
                                        <?= $p ?>
                                    </a>
                                <?php endfor; ?>

                                <a href="<?= get_rent_page_url($page + 1, $query_params) ?>" class="pm-page-link <?= ($page >= $totalPages) ? 'disabled' : '' ?>" title="Next Page">
                                    <i class="fa fa-angle-right"></i>
                                </a>
                                <a href="<?= get_rent_page_url($totalPages, $query_params) ?>" class="pm-page-link <?= ($page >= $totalPages) ? 'disabled' : '' ?>" title="Last Page">
                                    <i class="fa fa-angles-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <script>
                // Preset Date logic
                function applyPreset(type) {
                    const today = new Date();
                    let fromStr = '';
                    let toStr = formatDate(today);

                    if (type === 'today') {
                        fromStr = toStr;
                    } else if (type === 'this_month') {
                        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                        fromStr = formatDate(firstDay);
                    } else if (type === 'last_month') {
                        const firstLast = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        const lastLast = new Date(today.getFullYear(), today.getMonth(), 0);
                        fromStr = formatDate(firstLast);
                        toStr = formatDate(lastLast);
                    } else if (type === 'this_fy') {
                        const year = (today.getMonth() >= 3) ? today.getFullYear() : today.getFullYear() - 1;
                        fromStr = `${year}-04-01`;
                    } else if (type === 'all') {
                        fromStr = '';
                        toStr = '';
                    }

                    document.getElementById('start_date').value = fromStr;
                    document.getElementById('end_date').value = toStr;
                    document.getElementById('filterForm').submit();
                }

                function formatDate(d) {
                    const year = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }

                // Delete Bill handler via AJAX
                document.querySelectorAll(".delete_bill").forEach(button => {
                    button.addEventListener("click", function () {
                        const billId = this.getAttribute("data-bill-id");

                        if (confirm(`Are you sure you want to permanently delete Rent Bill #${billId}?\nThis will remove corresponding ledger entries and order details.`)) {
                            button.disabled = true;
                            fetch("./rent_report_delete.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/x-www-form-urlencoded",
                                },
                                body: `bill_id=${encodeURIComponent(billId)}`,
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`HTTP error! Status: ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.status === "success") {
                                    alert(data.message);
                                    const row = document.getElementById("row_" + billId);
                                    if (row) row.remove();
                                } else {
                                    button.disabled = false;
                                    alert(`Error: ${data.message}`);
                                }
                            })
                            .catch(error => {
                                button.disabled = false;
                                alert(`Request failed: ${error.message}`);
                            });
                        }
                    });
                });

                // Export to CSV
                function exportToCSV() {
                    const rows = document.querySelectorAll('#invoicesTable tr');
                    let csv = [];
                    rows.forEach(row => {
                        if (row.style.display === 'none') return;
                        let rowData = [];
                        const cols = row.querySelectorAll('th, td');
                        cols.forEach((col, idx) => {
                            if (col.classList.contains('no-print')) return;
                            let text = col.innerText.replace(/(\r\n|\n|\r)/gm, ' ').trim();
                            text = text.replace(/"/g, '""');
                            rowData.push(`"${text}"`);
                        });
                        if (rowData.length > 0) csv.push(rowData.join(','));
                    });

                    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
                    const downloadLink = document.createElement('a');
                    downloadLink.download = `rent_bill_records_${new Date().toISOString().slice(0, 10)}.csv`;
                    downloadLink.href = window.URL.createObjectURL(csvFile);
                    downloadLink.style.display = 'none';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                }
            </script>

        </div><!-- /content-wrapper -->
    </div><!-- /main-panel -->
</div><!-- /container-fluid page-body-wrapper -->

<?php
if ($con) CloseCon($con);
?>
</body>
</html>
