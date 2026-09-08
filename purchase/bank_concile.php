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

// Fetch all Banks for the selector
$banks_list = [];
if ($con) {
    $qb = mysqli_query($con, "SELECT bank_id, bank_name, ac_no, overdraft FROM banks ORDER BY bank_name ASC");
    if ($qb) {
        while ($rb = mysqli_fetch_assoc($qb)) {
            $banks_list[] = $rb;
        }
    }
}

// Read Filter Parameters
$bank_id = isset($_GET['bank_id']) ? intval($_GET['bank_id']) : (isset($_POST['bank_id']) ? intval($_POST['bank_id']) : 0);
// If no bank is selected, default to the first bank in list if available
if ($bank_id <= 0 && !empty($banks_list)) {
    $bank_id = intval($banks_list[0]['bank_id']);
}

$frmdate = isset($_GET['frmdate']) ? trim($_GET['frmdate']) : (isset($_POST['frmdate']) ? trim($_POST['frmdate']) : date('Y-m-01'));
$todate  = isset($_GET['todate']) ? trim($_GET['todate']) : (isset($_POST['todate']) ? trim($_POST['todate']) : date('Y-m-d'));
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all'; // all, pending, reconciled

// Convert DD/MM/YYYY to YYYY-MM-DD if needed
function normalize_date($d, $fallback) {
    if (!$d) return $fallback;
    if (strpos($d, '/') !== false) {
        $parts = explode('/', $d);
        if (count($parts) === 3) {
            return $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        }
    }
    return $d;
}
$norm_from = normalize_date($frmdate, date('Y-m-01'));
$norm_to   = normalize_date($todate, date('Y-m-d'));

// Selected bank details
$selected_bank = null;
foreach ($banks_list as $b) {
    if (intval($b['bank_id']) === $bank_id) {
        $selected_bank = $b;
        break;
    }
}

// KPI Data Containers
$kpi = [
    'opening_bal'     => 0,
    'total_trans'     => 0,
    'period_credit'   => 0,
    'period_debit'    => 0,
    'closing_bal'     => 0,
    'reconciled_cnt'  => 0,
    'pending_cnt'     => 0,
    'recon_credit'    => 0,
    'recon_debit'     => 0,
    'recon_rate'      => 0
];

$transactions = [];

if ($con && $bank_id > 0) {
    // 1. Calculate Balance Brought Forward (Prior to norm_from)
    $q_prior = mysqli_query($con, "
        SELECT 
            COALESCE(SUM(CASE WHEN trans_type='receit' THEN trans_amt ELSE 0 END), 0) as prior_cr,
            COALESCE(SUM(CASE WHEN trans_type IN ('payment', 'banktrans') THEN trans_amt ELSE 0 END), 0) as prior_dr
        FROM bank_transaction 
        WHERE bank_id = $bank_id AND trans_date < '$norm_from'
    ");
    if ($q_prior && $r_prior = mysqli_fetch_assoc($q_prior)) {
        $kpi['opening_bal'] = floatval($r_prior['prior_cr']) - floatval($r_prior['prior_dr']);
    }

    // 2. Fetch Period Summary
    $q_summary = mysqli_query($con, "
        SELECT 
            COUNT(*) as total_trans,
            COALESCE(SUM(CASE WHEN trans_type='receit' THEN trans_amt ELSE 0 END), 0) as p_cr,
            COALESCE(SUM(CASE WHEN trans_type IN ('payment', 'banktrans') THEN trans_amt ELSE 0 END), 0) as p_dr,
            COALESCE(SUM(CASE WHEN reconcile='Yes' THEN 1 ELSE 0 END), 0) as r_cnt,
            COALESCE(SUM(CASE WHEN reconcile='Yes' AND trans_type='receit' THEN trans_amt ELSE 0 END), 0) as r_cr,
            COALESCE(SUM(CASE WHEN reconcile='Yes' AND trans_type IN ('payment', 'banktrans') THEN trans_amt ELSE 0 END), 0) as r_dr
        FROM bank_transaction 
        WHERE bank_id = $bank_id AND trans_date BETWEEN '$norm_from' AND '$norm_to'
    ");
    if ($q_summary && $r_sum = mysqli_fetch_assoc($q_summary)) {
        $kpi['total_trans']    = intval($r_sum['total_trans']);
        $kpi['period_credit']  = floatval($r_sum['p_cr']);
        $kpi['period_debit']   = floatval($r_sum['p_dr']);
        $kpi['reconciled_cnt'] = intval($r_sum['r_cnt']);
        $kpi['pending_cnt']    = $kpi['total_trans'] - $kpi['reconciled_cnt'];
        $kpi['closing_bal']    = $kpi['opening_bal'] + $kpi['period_credit'] - $kpi['period_debit'];
        $kpi['recon_rate']     = ($kpi['total_trans'] > 0) ? round(($kpi['reconciled_cnt'] / $kpi['total_trans']) * 100) : 100;
    }

    // 3. Fetch Transactions for the view
    $status_clause = "";
    if ($status_filter === 'pending') {
        $status_clause = " AND (reconcile != 'Yes' OR reconcile IS NULL) ";
    } else if ($status_filter === 'reconciled') {
        $status_clause = " AND reconcile = 'Yes' ";
    }

    $q_list = mysqli_query($con, "
        SELECT * 
        FROM bank_transaction 
        WHERE bank_id = $bank_id AND trans_date BETWEEN '$norm_from' AND '$norm_to' $status_clause
        ORDER BY trans_date ASC, trans_id ASC
    ");
    if ($q_list) {
        while ($row = mysqli_fetch_assoc($q_list)) {
            $transactions[] = $row;
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

                /* Filter Card */
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

                /* Form Controls */
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

                /* Preset date chips */
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

                /* Table Styling */
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

                /* Status Pills */
                .pm-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    padding: 3px 8px;
                    font-size: 11px;
                    font-weight: 500;
                    border-radius: 4px;
                    white-space: nowrap;
                }

                .pm-badge-reconciled {
                    background: #f0fdf4;
                    color: #15803d;
                    border: 1px solid #bbf7d0;
                }

                .pm-badge-pending {
                    background: #f8fafc;
                    color: var(--pm-slate-600);
                    border: 1px solid var(--pm-slate-300);
                }

                .pm-badge-neutral {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
                }

                /* Bulk Action Toolbar */
                .pm-bulk-bar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 10px 16px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    margin-bottom: 12px;
                    font-size: 13px;
                }

                /* Toggle Switch */
                .pm-toggle-btn {
                    background: transparent;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 4px;
                    cursor: pointer;
                    padding: 4px 8px;
                    font-size: 11px;
                    color: var(--pm-slate-600);
                    transition: all 0.15s ease;
                }

                .pm-toggle-btn:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900);
                }

                /* Custom Checkbox */
                .pm-checkbox {
                    width: 16px;
                    height: 16px;
                    cursor: pointer;
                    accent-color: var(--pm-slate-900);
                }

                /* Alert message */
                .pm-alert-success {
                    padding: 10px 16px;
                    background: #f0fdf4;
                    border: 1px solid #bbf7d0;
                    border-radius: 6px;
                    color: #15803d;
                    font-size: 13px;
                    margin-bottom: 16px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                /* Print */
                @media print {
                    .navbar, #sidebar, .pm-filter-row, .pm-btn, .pm-preset-group, .pm-bulk-bar, .no-print {
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

            <div class="bank-concile-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-scale-balanced" style="margin-right: 10px; font-size: 20px; color: var(--pm-slate-700);"></i>
                            Bank Statement Reconciliation
                        </h1>
                        <p class="pm-page-subtitle">
                            Cross-verify internal book transactions against bank statements, track cleared deposits, and balance accounts.
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;" class="no-print">
                        <button type="button" class="pm-btn pm-btn-secondary" onclick="exportToCSV();">
                            <i class="fa fa-file-csv"></i> Export CSV
                        </button>
                        <a href="bank_entry.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-plus"></i> New Transaction
                        </a>
                        <a href="bank_report.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-chart-line"></i> Bank Ledger
                        </a>
                    </div>
                </div>

                <?php if (isset($_GET['status_msg']) && $_GET['status_msg'] === 'saved'): ?>
                    <div class="pm-alert-success">
                        <i class="fa fa-circle-check"></i>
                        <span>Reconciliation changes saved successfully! Account balances have been refreshed.</span>
                    </div>
                <?php endif; ?>

                <!-- 5 KPI Metric Cards - 1 Single Row -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Closing Book Balance</span>
                            <div class="pm-kpi-icon"><i class="fa fa-inr"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['closing_bal']) ?></div>
                        <div class="pm-kpi-sub">
                            Opening: ₹ <?= number_format($kpi['opening_bal']) ?>
                        </div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Transactions in Scope</span>
                            <div class="pm-kpi-icon"><i class="fa fa-list-check"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['total_trans']) ?></div>
                        <div class="pm-kpi-sub">Within selected period</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Period Inward (Credit)</span>
                            <div class="pm-kpi-icon"><i class="fa fa-arrow-down"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['period_credit']) ?></div>
                        <div class="pm-kpi-sub">Total credit deposits</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Period Outward (Debit)</span>
                            <div class="pm-kpi-icon"><i class="fa fa-arrow-up"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['period_debit']) ?></div>
                        <div class="pm-kpi-sub">Total debits & payments</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Reconciled Status</span>
                            <div class="pm-kpi-icon"><i class="fa fa-shield-halved"></i></div>
                        </div>
                        <div class="pm-kpi-value">
                            <span id="kpiReconRate"><?= $kpi['recon_rate'] ?>%</span>
                        </div>
                        <div class="pm-kpi-sub" id="kpiReconDetail">
                            <?= $kpi['reconciled_cnt'] ?> Cleared • <?= $kpi['pending_cnt'] ?> Pending
                        </div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="pm-card no-print">
                    <div class="pm-card-header">
                        <h2 class="pm-card-title">
                            <i class="fa-solid fa-sliders"></i> Filter Criteria & Account Selector
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
                        <form method="GET" action="bank_concile.php" id="filterForm">
                            <div class="pm-filter-row">
                                <!-- Bank Selector -->
                                <div class="pm-form-group" style="min-width: 220px; flex: 1;">
                                    <label class="pm-label" for="bank_id">Select Bank Account <span style="color: #ef4444;">*</span></label>
                                    <select name="bank_id" id="bank_id" class="pm-select" onchange="document.getElementById('filterForm').submit();" required>
                                        <option value="0">Choose Bank Account</option>
                                        <?php foreach ($banks_list as $b): ?>
                                            <option value="<?= $b['bank_id'] ?>" <?= ($bank_id === intval($b['bank_id'])) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($b['bank_name']) ?> <?= $b['ac_no'] ? '('.htmlspecialchars($b['ac_no']).')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- From Date -->
                                <div class="pm-form-group" style="width: 150px;">
                                    <label class="pm-label" for="frmdate">Date From</label>
                                    <input type="date" name="frmdate" id="frmdate" class="pm-input" value="<?= htmlspecialchars($norm_from) ?>">
                                </div>

                                <!-- To Date -->
                                <div class="pm-form-group" style="width: 150px;">
                                    <label class="pm-label" for="todate">Date To</label>
                                    <input type="date" name="todate" id="todate" class="pm-input" value="<?= htmlspecialchars($norm_to) ?>">
                                </div>

                                <!-- Status Filter -->
                                <div class="pm-form-group" style="width: 160px;">
                                    <label class="pm-label" for="status">Reconcile Status</label>
                                    <select name="status" id="status" class="pm-select">
                                        <option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>All Statuses</option>
                                        <option value="pending" <?= ($status_filter === 'pending') ? 'selected' : '' ?>>Pending Only</option>
                                        <option value="reconciled" <?= ($status_filter === 'reconciled') ? 'selected' : '' ?>>Reconciled Only</option>
                                    </select>
                                </div>

                                <!-- Action Buttons -->
                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="pm-btn pm-btn-primary">
                                        <i class="fa fa-filter"></i> Apply Filter
                                    </button>
                                    <a href="bank_concile.php" class="pm-btn pm-btn-secondary">
                                        <i class="fa fa-rotate-left"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Main Reconciliation Form & Table Card -->
                <form id="reconcileForm" action="processConcile.php" method="POST">
                    <input type="hidden" name="bank_id" value="<?= $bank_id ?>">
                    <input type="hidden" name="frmdate" value="<?= htmlspecialchars($norm_from) ?>">
                    <input type="hidden" name="todate" value="<?= htmlspecialchars($norm_to) ?>">
                    <input type="hidden" name="count" value="<?= count($transactions) ?>">

                    <div class="pm-card">
                        <div class="pm-card-header">
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <h2 class="pm-card-title">
                                    <i class="fa-solid fa-list-check"></i> 
                                    <?= htmlspecialchars($selected_bank['bank_name'] ?? 'Bank Account') ?> 
                                    <span style="font-weight: 400; color: var(--pm-slate-500); font-size: 13px;">
                                        (<?= date('d/m/Y', strtotime($norm_from)) ?> to <?= date('d/m/Y', strtotime($norm_to)) ?>)
                                    </span>
                                </h2>
                                <span class="pm-badge pm-badge-neutral">
                                    <?= count($transactions) ?> Entries
                                </span>
                            </div>

                            <!-- Live Search Input -->
                            <div class="no-print" style="position: relative; width: 220px;">
                                <i class="fa fa-search" style="position: absolute; left: 10px; top: 11px; color: var(--pm-slate-400); font-size: 12px;"></i>
                                <input type="text" id="tableSearch" class="pm-input" style="padding-left: 28px; height: 32px; font-size: 12px; width: 100%;" placeholder="Search narration, ID, amount..." oninput="filterTableRows();">
                            </div>
                        </div>

                        <!-- Bulk Actions Toolbar -->
                        <div class="pm-bulk-bar no-print">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 500;">
                                    <input type="checkbox" id="selectAllMaster" class="pm-checkbox" onchange="toggleSelectAll(this.checked);">
                                    <span>Select All Visible</span>
                                </label>
                                <span style="color: var(--pm-slate-300);">|</span>
                                <span id="selectedCountMsg" style="color: var(--pm-slate-500); font-size: 12px;">0 items selected</span>
                            </div>

                            <div style="display: flex; gap: 8px; align-items: center;">
                                <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" onclick="bulkUpdateReconcile('Yes');">
                                    <i class="fa fa-check" style="color: #16a34a;"></i> Mark Selected as Reconciled
                                </button>
                                <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" onclick="bulkUpdateReconcile('NO');">
                                    <i class="fa fa-xmark" style="color: var(--pm-slate-500);"></i> Mark Selected as Pending
                                </button>
                                <button type="submit" name="submit" class="pm-btn pm-btn-primary pm-btn-sm">
                                    <i class="fa fa-floppy-disk"></i> Save All Changes
                                </button>
                            </div>
                        </div>

                        <!-- Reconciliation Table -->
                        <div class="pm-table-container">
                            <table class="pm-table" id="reconTable">
                                <thead>
                                    <tr>
                                        <th style="width: 40px; text-align: center;" class="no-print">
                                            <input type="checkbox" class="pm-checkbox" id="headerCheck" onchange="toggleSelectAll(this.checked);">
                                        </th>
                                        <th style="width: 70px;">Trans ID</th>
                                        <th style="width: 100px;">Date</th>
                                        <th style="width: 100px;">Type</th>
                                        <th style="width: 120px; text-align: right;">Deposit / Credit (₹)</th>
                                        <th style="width: 120px; text-align: right;">Withdrawal / Debit (₹)</th>
                                        <th>Transaction Memo / Narration</th>
                                        <th style="width: 110px; text-align: center;">Status</th>
                                        <th style="width: 100px;">Cleared Date</th>
                                        <th style="width: 90px; text-align: center;" class="no-print">Action</th>
                                    </tr>
                                    <tr style="background: #f1f5f9;">
                                        <td class="no-print"></td>
                                        <td colspan="3" style="font-weight: 600; color: var(--pm-slate-700);">
                                            <i class="fa fa-arrow-turn-down-right" style="margin-right: 6px;"></i> Balance Brought Forward (Prior to <?= date('d/m/Y', strtotime($norm_from)) ?>):
                                        </td>
                                        <td colspan="2" style="text-align: right; font-family: monospace; font-weight: 700; color: var(--pm-slate-900);">
                                            ₹ <?= number_format($kpi['opening_bal'], 2) ?>
                                        </td>
                                        <td colspan="4"></td>
                                    </tr>
                                </thead>
                                <tbody id="reconTableBody">
                                    <?php if (empty($transactions)): ?>
                                        <tr>
                                            <td colspan="10" style="text-align: center; padding: 40px 20px; color: var(--pm-slate-400);">
                                                <i class="fa fa-folder-open" style="font-size: 28px; margin-bottom: 8px; display: block; color: var(--pm-slate-300);"></i>
                                                No bank transactions found for the selected account and period.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                        $row_idx = 0;
                                        $tot_cr = 0;
                                        $tot_dr = 0;
                                        foreach ($transactions as $t): 
                                            $t_id      = intval($t['trans_id']);
                                            $t_amt     = floatval($t['trans_amt']);
                                            $t_type    = $t['trans_type'];
                                            $t_date    = ($t['trans_date'] && $t['trans_date'] !== '0000-00-00') ? date('d/m/Y', strtotime($t['trans_date'])) : '—';
                                            $t_memo    = htmlspecialchars($t['trans_memo'] ?: ($t['company_name'] ?: ''));
                                            $is_recon  = ($t['reconcile'] === 'Yes');
                                            $r_date    = ($t['reconcile_date'] && $t['reconcile_date'] !== '0000-00-00') ? date('d/m/Y', strtotime($t['reconcile_date'])) : '—';

                                            $credit_val = ($t_type === 'receit') ? $t_amt : 0;
                                            $debit_val  = ($t_type === 'payment' || $t_type === 'banktrans') ? $t_amt : 0;

                                            $tot_cr += $credit_val;
                                            $tot_dr += $debit_val;

                                            $type_badge_class = ($t_type === 'receit') ? 'pm-badge-reconciled' : (($t_type === 'payment') ? 'pm-badge-neutral' : 'pm-badge-neutral');
                                            $type_text = ($t_type === 'receit') ? 'Deposit' : (($t_type === 'payment') ? 'Payment' : 'Transfer');
                                        ?>
                                            <tr id="row_<?= $t_id ?>" class="recon-row" data-id="<?= $t_id ?>" data-memo="<?= strtolower($t_memo) ?>" data-amt="<?= $t_amt ?>" data-status="<?= $is_recon ? 'reconciled' : 'pending' ?>">
                                                <!-- Row Checkbox -->
                                                <td style="text-align: center;" class="no-print">
                                                    <input type="hidden" name="trans_id[]" value="<?= $t_id ?>">
                                                    <input type="checkbox" name="concil[]" value="<?= $t_id ?>" class="pm-checkbox row-select" <?= $is_recon ? 'checked' : '' ?> onchange="updateSelectedCount();">
                                                </td>

                                                <!-- ID -->
                                                <td style="font-family: monospace; font-weight: 600; color: var(--pm-slate-700);">
                                                    #<?= $t_id ?>
                                                </td>

                                                <!-- Date -->
                                                <td style="font-family: monospace;">
                                                    <?= $t_date ?>
                                                </td>

                                                <!-- Type -->
                                                <td>
                                                    <span class="pm-badge <?= $type_badge_class ?>"><?= $type_text ?></span>
                                                </td>

                                                <!-- Credit -->
                                                <td style="text-align: right; font-family: monospace; font-weight: 600; color: #15803d;">
                                                    <?= ($credit_val > 0) ? '₹ ' . number_format($credit_val, 2) : '—' ?>
                                                </td>

                                                <!-- Debit -->
                                                <td style="text-align: right; font-family: monospace; font-weight: 600; color: #b91c1c;">
                                                    <?= ($debit_val > 0) ? '₹ ' . number_format($debit_val, 2) : '—' ?>
                                                </td>

                                                <!-- Memo -->
                                                <td style="color: var(--pm-slate-700); max-width: 260px;">
                                                    <?= $t_memo ?: '<span style="color: var(--pm-slate-400);">—</span>' ?>
                                                </td>

                                                <!-- Status Badge -->
                                                <td style="text-align: center;">
                                                    <span class="pm-badge <?= $is_recon ? 'pm-badge-reconciled' : 'pm-badge-pending' ?>" id="badge_<?= $t_id ?>">
                                                        <i class="fa <?= $is_recon ? 'fa-check' : 'fa-clock' ?>"></i>
                                                        <span id="badge_text_<?= $t_id ?>"><?= $is_recon ? 'Reconciled' : 'Pending' ?></span>
                                                    </span>
                                                </td>

                                                <!-- Reconcile Date -->
                                                <td style="font-family: monospace; font-size: 12px; color: var(--pm-slate-500);" id="rdate_<?= $t_id ?>">
                                                    <?= $r_date ?>
                                                </td>

                                                <!-- Instant Toggle Action -->
                                                <td style="text-align: center;" class="no-print">
                                                    <button type="button" class="pm-toggle-btn" id="btn_toggle_<?= $t_id ?>" onclick="toggleSingleItem(<?= $t_id ?>);" title="Toggle Reconciled / Pending">
                                                        <i class="fa <?= $is_recon ? 'fa-rotate-left' : 'fa-check' ?>"></i>
                                                        <span id="btn_toggle_text_<?= $t_id ?>"><?= $is_recon ? 'Undo' : 'Clear' ?></span>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php 
                                            $row_idx++;
                                        endforeach; 
                                        ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f8fafc; font-weight: 600;">
                                        <td class="no-print"></td>
                                        <td colspan="3" style="text-align: right; color: var(--pm-slate-700);">Period Movement Totals:</td>
                                        <td style="text-align: right; font-family: monospace; color: #15803d;">₹ <?= number_format($kpi['period_credit'], 2) ?></td>
                                        <td style="text-align: right; font-family: monospace; color: #b91c1c;">₹ <?= number_format($kpi['period_debit'], 2) ?></td>
                                        <td colspan="4"></td>
                                    </tr>
                                    <tr style="background: #f1f5f9; font-weight: 700; font-size: 14px;">
                                        <td class="no-print"></td>
                                        <td colspan="3" style="text-align: right; color: var(--pm-slate-900);">Closing Book Balance:</td>
                                        <td colspan="2" style="text-align: right; font-family: monospace; color: var(--pm-slate-900);">
                                            ₹ <?= number_format($kpi['closing_bal'], 2) ?>
                                        </td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pm-card-header no-print" style="background: #ffffff; justify-content: flex-end;">
                            <button type="submit" name="submit" class="pm-btn pm-btn-primary">
                                <i class="fa fa-floppy-disk"></i> Save Reconciliation
                            </button>
                        </div>
                    </div>
                </form>

            </div>

            <script>
                // Preset Date Picker logic
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
                        fromStr = '2010-01-01';
                    }

                    document.getElementById('frmdate').value = fromStr;
                    document.getElementById('todate').value = toStr;
                    document.getElementById('filterForm').submit();
                }

                function formatDate(d) {
                    const year = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }

                // Table Filtering
                function filterTableRows() {
                    const query = document.getElementById('tableSearch').value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#reconTableBody tr.recon-row');

                    rows.forEach(tr => {
                        const memo = tr.getAttribute('data-memo') || '';
                        const id = tr.getAttribute('data-id') || '';
                        const amt = tr.getAttribute('data-amt') || '';
                        if (!query || memo.includes(query) || id.includes(query) || amt.includes(query)) {
                            tr.style.display = '';
                        } else {
                            tr.style.display = 'none';
                        }
                    });
                    updateSelectedCount();
                }

                // Master Checkbox
                function toggleSelectAll(checked) {
                    document.getElementById('selectAllMaster').checked = checked;
                    document.getElementById('headerCheck').checked = checked;

                    const visibleRows = document.querySelectorAll('#reconTableBody tr.recon-row:not([style*="display: none"])');
                    visibleRows.forEach(tr => {
                        const chk = tr.querySelector('.row-select');
                        if (chk) chk.checked = checked;
                    });
                    updateSelectedCount();
                }

                function updateSelectedCount() {
                    const selected = document.querySelectorAll('.row-select:checked');
                    document.getElementById('selectedCountMsg').innerText = `${selected.length} items checked`;
                }

                // Instant Single Item Toggle via AJAX
                function toggleSingleItem(transId) {
                    const tr = document.getElementById('row_' + transId);
                    if (!tr) return;

                    const chk = tr.querySelector('.row-select');
                    const isCurrentlyRecon = chk.checked;
                    const newState = isCurrentlyRecon ? 'NO' : 'Yes';

                    const btn = document.getElementById('btn_toggle_' + transId);
                    btn.disabled = true;

                    $.ajax({
                        url: 'processConcile.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            is_ajax: 1,
                            action: 'toggle',
                            trans_id: transId,
                            reconcile: newState
                        },
                        success: function(res) {
                            btn.disabled = false;
                            if (res.success) {
                                const isRecon = (res.reconcile === 'Yes');
                                chk.checked = isRecon;

                                // Update row data attribute
                                tr.setAttribute('data-status', isRecon ? 'reconciled' : 'pending');

                                // Update Badge
                                const badge = document.getElementById('badge_' + transId);
                                const badgeText = document.getElementById('badge_text_' + transId);
                                badge.className = 'pm-badge ' + (isRecon ? 'pm-badge-reconciled' : 'pm-badge-pending');
                                badge.querySelector('i').className = 'fa ' + (isRecon ? 'fa-check' : 'fa-clock');
                                badgeText.innerText = isRecon ? 'Reconciled' : 'Pending';

                                // Update Date
                                document.getElementById('rdate_' + transId).innerText = res.reconcile_date || '—';

                                // Update Toggle button
                                const btnText = document.getElementById('btn_toggle_text_' + transId);
                                btn.querySelector('i').className = 'fa ' + (isRecon ? 'fa-rotate-left' : 'fa-check');
                                btnText.innerText = isRecon ? 'Undo' : 'Clear';

                                updateSelectedCount();
                            } else {
                                alert("Error updating status: " + (res.message || 'Unknown error'));
                            }
                        },
                        error: function(err) {
                            btn.disabled = false;
                            alert("Server communication failed.");
                        }
                    });
                }

                // Bulk Update via AJAX
                function bulkUpdateReconcile(newState) {
                    const selected = Array.from(document.querySelectorAll('.row-select:checked')).map(cb => cb.value);
                    if (selected.length === 0) {
                        alert("Please select at least one transaction row using the checkboxes.");
                        return;
                    }

                    const actionLabel = (newState === 'Yes') ? 'Reconciled' : 'Pending';
                    if (!confirm(`Mark ${selected.length} selected transactions as ${actionLabel}?`)) {
                        return;
                    }

                    $.ajax({
                        url: 'processConcile.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            is_ajax: 1,
                            action: 'bulk',
                            reconcile: newState,
                            trans_ids: selected
                        },
                        success: function(res) {
                            if (res.success) {
                                selected.forEach(id => {
                                    const tr = document.getElementById('row_' + id);
                                    if (tr) {
                                        const chk = tr.querySelector('.row-select');
                                        const isRecon = (newState === 'Yes');
                                        chk.checked = isRecon;

                                        tr.setAttribute('data-status', isRecon ? 'reconciled' : 'pending');

                                        const badge = document.getElementById('badge_' + id);
                                        const badgeText = document.getElementById('badge_text_' + id);
                                        if (badge) {
                                            badge.className = 'pm-badge ' + (isRecon ? 'pm-badge-reconciled' : 'pm-badge-pending');
                                            badge.querySelector('i').className = 'fa ' + (isRecon ? 'fa-check' : 'fa-clock');
                                            badgeText.innerText = isRecon ? 'Reconciled' : 'Pending';
                                        }

                                        const rdate = document.getElementById('rdate_' + id);
                                        if (rdate) rdate.innerText = res.date || '—';

                                        const btn = document.getElementById('btn_toggle_' + id);
                                        const btnText = document.getElementById('btn_toggle_text_' + id);
                                        if (btn && btnText) {
                                            btn.querySelector('i').className = 'fa ' + (isRecon ? 'fa-rotate-left' : 'fa-check');
                                            btnText.innerText = isRecon ? 'Undo' : 'Clear';
                                        }
                                    }
                                });
                                updateSelectedCount();
                                alert(`Successfully updated ${res.count} transactions to ${actionLabel}!`);
                            } else {
                                alert("Failed to update items: " + (res.message || 'Unknown error'));
                            }
                        },
                        error: function() {
                            alert("Server error processing bulk update.");
                        }
                    });
                }

                // CSV Export
                function exportToCSV() {
                    const rows = document.querySelectorAll('#reconTable tr');
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
                    downloadLink.download = `bank_reconciliation_<?= $bank_id ?>_${new Date().toISOString().slice(0, 10)}.csv`;
                    downloadLink.href = window.URL.createObjectURL(csvFile);
                    downloadLink.style.display = 'none';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                }

                // Initial count setup
                document.addEventListener('DOMContentLoaded', updateSelectedCount);
            </script>

        </div><!-- /content-wrapper -->
    </div><!-- /main-panel -->
</div><!-- /container-fluid page-body-wrapper -->

<?php
if ($con) {
    CloseCon($con);
}
?>
</body>
</html>