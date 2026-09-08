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

// Scope Summary & KPI Metrics
$kpi = [
    'total_banks'     => 0,
    'total_receipts'  => 0,
    'total_payments'  => 0,
    'total_transfers' => 0,
    'net_funds'       => 0
];

$banks_list = [];
$recent_trans = [];

if ($con) {
    // Banks count and list
    $q_banks = mysqli_query($con, "SELECT bank_id, bank_name FROM banks ORDER BY bank_name ASC");
    if ($q_banks) {
        while ($rb = mysqli_fetch_assoc($q_banks)) {
            $banks_list[] = $rb;
        }
    }
    $kpi['total_banks'] = count($banks_list);

    // KPI Metrics
    $q_stats = mysqli_query($con, "
        SELECT 
            COALESCE(SUM(CASE WHEN trans_type='receit' THEN trans_amt ELSE 0 END), 0) as receipts,
            COALESCE(SUM(CASE WHEN trans_type='payment' THEN trans_amt ELSE 0 END), 0) as payments,
            COALESCE(SUM(CASE WHEN trans_type='banktrans' THEN trans_amt ELSE 0 END), 0) as transfers
        FROM bank_transaction
    ");
    if ($q_stats && $rstat = mysqli_fetch_assoc($q_stats)) {
        $kpi['total_receipts']  = floatval($rstat['receipts'] ?? 0);
        $kpi['total_payments']  = floatval($rstat['payments'] ?? 0);
        $kpi['total_transfers'] = floatval($rstat['transfers'] ?? 0);
        $kpi['net_funds']       = $kpi['total_receipts'] - $kpi['total_payments'];
    }

    // Recent Transactions
    $q_recent = mysqli_query($con, "
        SELECT bt.*, b.bank_name 
        FROM bank_transaction bt 
        LEFT JOIN banks b ON bt.bank_id = b.bank_id 
        ORDER BY bt.trans_id DESC 
        LIMIT 10
    ");
    if ($q_recent) {
        while ($rt = mysqli_fetch_assoc($q_recent)) {
            $recent_trans[] = $rt;
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
                    font-size: 15px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .pm-card-body {
                    padding: 20px;
                }

                /* Form Controls */
                .pm-form-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                    gap: 16px;
                    margin-bottom: 20px;
                }

                .pm-form-group {
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                }

                .pm-label {
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    color: var(--pm-slate-700);
                    margin: 0;
                }

                .pm-input, .pm-select, .pm-textarea {
                    height: 38px;
                    padding: 0 12px;
                    font-size: 13px;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    background: #ffffff;
                    color: var(--pm-slate-900);
                    outline: none;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                    width: 100%;
                    box-sizing: border-box;
                }

                .pm-textarea {
                    height: 54px;
                    padding: 8px 12px;
                    resize: vertical;
                }

                .pm-input:focus, .pm-select:focus, .pm-textarea:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                /* Bank Balance Info Box */
                .pm-bank-info {
                    display: flex;
                    gap: 12px;
                    align-items: center;
                    padding: 10px 14px;
                    background: var(--pm-slate-50);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    margin-top: 6px;
                    font-size: 12px;
                }

                .pm-bank-metric {
                    display: flex;
                    flex-direction: column;
                }

                .pm-bank-metric-label {
                    font-size: 10px;
                    font-weight: 600;
                    text-transform: uppercase;
                    color: var(--pm-slate-400);
                }

                .pm-bank-metric-val {
                    font-size: 14px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    font-family: ui-monospace, monospace;
                }

                /* Line Items Table */
                .pm-items-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 12px;
                    margin-bottom: 16px;
                }

                .pm-items-table th {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-700);
                    font-weight: 600;
                    padding: 10px 14px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    text-align: left;
                }

                .pm-items-table td {
                    padding: 10px 14px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    vertical-align: middle;
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

                /* Status Badges */
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

                .pm-badge-receit {
                    background: #f0fdf4;
                    color: #15803d;
                    border: 1px solid #bbf7d0;
                }

                .pm-badge-payment {
                    background: #fef2f2;
                    color: #b91c1c;
                    border: 1px solid #fecaca;
                }

                .pm-badge-transfer {
                    background: #f1f5f9;
                    color: #334155;
                    border: 1px solid #cbd5e1;
                }

                /* Summary Footer */
                .pm-total-bar {
                    display: flex;
                    justify-content: flex-end;
                    align-items: center;
                    gap: 16px;
                    padding: 14px 20px;
                    background: var(--pm-slate-50);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    margin-bottom: 20px;
                }

                /* Print */
                @media print {
                    .navbar, #sidebar, .pm-page-header .pm-btn, .pm-card-header .pm-btn, .pm-btn, .action-cell {
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

            <div class="bank-entry-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-building-columns" style="margin-right: 10px; font-size: 20px; color: var(--pm-slate-700);"></i>
                            Bank Transaction Entry
                        </h1>
                        <p class="pm-page-subtitle">
                            Record bank deposits, vendor withdrawals, and inter-bank account transfers with real-time balance checks.
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <a href="bank_report.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-chart-line"></i> Bank Ledger Report
                        </a>
                        <a href="newbank.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-plus"></i> Add New Bank
                        </a>
                    </div>
                </div>

                <!-- 5 KPI Metric Cards - 1 Single Row -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Active Bank Accounts</span>
                            <div class="pm-kpi-icon"><i class="fa fa-landmark"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['total_banks']) ?></div>
                        <div class="pm-kpi-sub">Configured bank entities</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Net Bank Funds</span>
                            <div class="pm-kpi-icon"><i class="fa fa-inr"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['net_funds']) ?></div>
                        <div class="pm-kpi-sub">Cumulative bank liquidity</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Inward Receipts</span>
                            <div class="pm-kpi-icon"><i class="fa fa-arrow-down"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['total_receipts']) ?></div>
                        <div class="pm-kpi-sub">Total credit deposits</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Outward Payments</span>
                            <div class="pm-kpi-icon"><i class="fa fa-arrow-up"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['total_payments']) ?></div>
                        <div class="pm-kpi-sub">Total debits & expenses</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Inter-Bank Transfers</span>
                            <div class="pm-kpi-icon"><i class="fa fa-repeat"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['total_transfers']) ?></div>
                        <div class="pm-kpi-sub">Funds shifted between banks</div>
                    </div>
                </div>

                <!-- Main Transaction Form Card -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <h2 class="pm-card-title">
                            <i class="fa-solid fa-pen-to-square"></i> New Transaction Entry
                        </h2>
                        <span class="pm-badge-neutral">
                            <i class="fa fa-shield-halved" style="font-size: 10px;"></i> Balance Guard Active
                        </span>
                    </div>
                    <div class="pm-card-body">
                        <form id="bankEntryForm" action="processbanktrans.php" method="POST" onsubmit="return validateTransaction();">
                            <input type="hidden" name="balance" id="balance" value="0">
                            <input type="hidden" name="od" id="od" value="0">
                            <input type="hidden" name="count" id="count" value="2">
                            <input type="hidden" name="transdate" id="transdateHidden" value="<?= date('d/m/Y') ?>">

                            <div class="pm-form-grid">
                                <!-- Transaction Type -->
                                <div class="pm-form-group">
                                    <label class="pm-label" for="trans_type">Transaction Type <span style="color: #ef4444;">*</span></label>
                                    <select name="trans_type" id="trans_type" class="pm-select" onchange="handleTypeChange();" required>
                                        <option value="0">Select Transaction Type</option>
                                        <option value="payment">Payment (Debit / Outflow)</option>
                                        <option value="receit">Receipt (Credit / Inflow)</option>
                                        <option value="banktrans">Bank Transfer (Inter-Bank)</option>
                                    </select>
                                </div>

                                <!-- Transaction Date -->
                                <div class="pm-form-group">
                                    <label class="pm-label" for="displayDate">Transaction Date <span style="color: #ef4444;">*</span></label>
                                    <input type="date" id="displayDate" class="pm-input" value="<?= date('Y-m-d') ?>" onchange="syncDate();" required>
                                </div>

                                <!-- Bank Account -->
                                <div class="pm-form-group">
                                    <label class="pm-label" for="bank_id">Bank Account <span style="color: #ef4444;">*</span></label>
                                    <select name="bank_id" id="bank_id" class="pm-select" onchange="fetchBankBalance();" required>
                                        <option value="0">Select Bank Account</option>
                                        <?php foreach ($banks_list as $b): ?>
                                            <option value="<?= $b['bank_id'] ?>"><?= htmlspecialchars($b['bank_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <!-- Live Balance Box -->
                                    <div id="bankBalanceBox" class="pm-bank-info" style="display: none;">
                                        <div class="pm-bank-metric">
                                            <span class="pm-bank-metric-label">Available Balance</span>
                                            <span class="pm-bank-metric-val" id="dispBal">₹ 0</span>
                                        </div>
                                        <div class="pm-bank-metric" style="border-left: 1px solid var(--pm-slate-200); padding-left: 12px;" id="odBox">
                                            <span class="pm-bank-metric-label">Overdraft Limit</span>
                                            <span class="pm-bank-metric-val" id="dispOd">₹ 0</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Transfer To Bank (Shown only on banktrans) -->
                                <div class="pm-form-group" id="transferBankGroup" style="display: none;">
                                    <label class="pm-label" for="bank_id1">Destination Bank <span style="color: #ef4444;">*</span></label>
                                    <select name="bank_id1" id="bank_id1" class="pm-select">
                                        <option value="0">Select Destination Bank</option>
                                        <?php foreach ($banks_list as $b): ?>
                                            <option value="<?= $b['bank_id'] ?>"><?= htmlspecialchars($b['bank_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Line Items Table -->
                            <div style="margin-top: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span class="pm-label">Transaction Line Items</span>
                                    <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" onclick="addNewRow();">
                                        <i class="fa fa-plus"></i> Add Line Item
                                    </button>
                                </div>

                                <table class="pm-items-table" id="itemsTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th style="width: 220px;">Amount (₹) <span style="color: #ef4444;">*</span></th>
                                            <th>Transaction Memo / Narration</th>
                                            <th style="width: 50px; text-align: center;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsTableBody">
                                        <!-- Row 0 -->
                                        <tr class="item-row" id="row_0">
                                            <td style="font-family: monospace; color: var(--pm-slate-400);">1</td>
                                            <td>
                                                <input type="number" step="any" name="amt[0]" id="amt0" class="pm-input amt-input" placeholder="0.00" oninput="calculateTotal();">
                                            </td>
                                            <td>
                                                <textarea name="memo[0]" id="memo0" class="pm-textarea" placeholder="Enter transaction memo / vendor / purpose..."></textarea>
                                            </td>
                                            <td style="text-align: center;"></td>
                                        </tr>
                                        <!-- Row 1 -->
                                        <tr class="item-row" id="row_1">
                                            <td style="font-family: monospace; color: var(--pm-slate-400);">2</td>
                                            <td>
                                                <input type="number" step="any" name="amt[1]" id="amt1" class="pm-input amt-input" placeholder="0.00" oninput="calculateTotal();">
                                            </td>
                                            <td>
                                                <textarea name="memo[1]" id="memo1" class="pm-textarea" placeholder="Enter transaction memo / vendor / purpose..."></textarea>
                                            </td>
                                            <td style="text-align: center;">
                                                <button type="button" class="pm-btn pm-btn-danger pm-btn-sm" onclick="removeRow(1);"><i class="fa fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <!-- Row 2 -->
                                        <tr class="item-row" id="row_2">
                                            <td style="font-family: monospace; color: var(--pm-slate-400);">3</td>
                                            <td>
                                                <input type="number" step="any" name="amt[2]" id="amt2" class="pm-input amt-input" placeholder="0.00" oninput="calculateTotal();">
                                            </td>
                                            <td>
                                                <textarea name="memo[2]" id="memo2" class="pm-textarea" placeholder="Enter transaction memo / vendor / purpose..."></textarea>
                                            </td>
                                            <td style="text-align: center;">
                                                <button type="button" class="pm-btn pm-btn-danger pm-btn-sm" onclick="removeRow(2);"><i class="fa fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Total Bar -->
                                <div class="pm-total-bar">
                                    <span style="font-size: 13px; font-weight: 600; color: var(--pm-slate-600);">Total Transaction Amount:</span>
                                    <span style="font-size: 18px; font-weight: 800; font-family: ui-monospace, monospace; color: var(--pm-slate-900);" id="totalAmtDisplay">₹ 0.00</span>
                                </div>
                            </div>

                            <!-- Submit Bar -->
                            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                                <a href="bank_entry.php" class="pm-btn pm-btn-secondary">
                                    <i class="fa fa-rotate-left"></i> Reset Form
                                </a>
                                <button type="submit" name="submit" class="pm-btn pm-btn-primary">
                                    <i class="fa fa-check"></i> Post Transaction
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Recent Transactions Card -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <h2 class="pm-card-title">
                            <i class="fa-solid fa-clock-rotate-left"></i> Recent Bank Transactions
                        </h2>
                        <a href="bank_report.php" class="pm-btn pm-btn-secondary pm-btn-sm">
                            View All History <i class="fa fa-arrow-right" style="font-size: 10px;"></i>
                        </a>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="pm-items-table" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Trans ID</th>
                                    <th>Date</th>
                                    <th>Bank Account</th>
                                    <th>Type</th>
                                    <th style="text-align: right;">Amount (₹)</th>
                                    <th>Memo / Narration</th>
                                    <th style="text-align: center;">Reconciled</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_trans)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--pm-slate-400);">No recent transactions recorded.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_trans as $t): 
                                        $type_class = ($t['trans_type'] === 'receit') ? 'pm-badge-receit' : (($t['trans_type'] === 'payment') ? 'pm-badge-payment' : 'pm-badge-transfer');
                                        $type_lbl   = ($t['trans_type'] === 'receit') ? 'Receipt' : (($t['trans_type'] === 'payment') ? 'Payment' : 'Transfer');
                                        $t_date     = ($t['trans_date'] && $t['trans_date'] !== '0000-00-00') ? date('d/m/Y', strtotime($t['trans_date'])) : '—';
                                    ?>
                                    <tr>
                                        <td style="font-family: monospace; color: var(--pm-slate-400);">#<?= $t['trans_id'] ?></td>
                                        <td style="font-family: monospace;"><?= $t_date ?></td>
                                        <td style="font-weight: 600; color: var(--pm-slate-900);"><?= htmlspecialchars($t['bank_name'] ?: 'Bank #' . $t['bank_id']) ?></td>
                                        <td>
                                            <span class="pm-status-pill <?= $type_class ?>"><?= $type_lbl ?></span>
                                        </td>
                                        <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--pm-slate-900);">
                                            ₹ <?= number_format(floatval($t['trans_amt']), 2) ?>
                                        </td>
                                        <td style="color: var(--pm-slate-600);"><?= htmlspecialchars($t['trans_memo'] ?: '—') ?></td>
                                        <td style="text-align: center;">
                                            <span class="pm-badge-neutral"><?= htmlspecialchars($t['reconcile'] ?: 'NO') ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <script>
                let rowCount = 2; // Initial 3 rows (0, 1, 2)

                function syncDate() {
                    const disp = document.getElementById('displayDate').value;
                    if (disp) {
                        const parts = disp.split('-');
                        if (parts.length === 3) {
                            document.getElementById('transdateHidden').value = `${parts[2]}/${parts[1]}/${parts[0]}`;
                        }
                    }
                }

                function handleTypeChange() {
                    const t = document.getElementById('trans_type').value;
                    const destGroup = document.getElementById('transferBankGroup');
                    if (t === 'banktrans') {
                        destGroup.style.display = 'flex';
                        document.getElementById('bank_id1').setAttribute('required', 'required');
                    } else {
                        destGroup.style.display = 'none';
                        document.getElementById('bank_id1').removeAttribute('required');
                    }
                }

                function fetchBankBalance() {
                    const bankId = document.getElementById('bank_id').value;
                    const box = document.getElementById('bankBalanceBox');

                    if (!bankId || bankId === '0') {
                        box.style.display = 'none';
                        document.getElementById('balance').value = '0';
                        document.getElementById('od').value = '0';
                        return;
                    }

                    // Fetch balance
                    fetch('getbalance.php?bank_id=' + encodeURIComponent(bankId))
                        .then(res => res.text())
                        .then(bal => {
                            const balVal = parseFloat(bal.trim()) || 0;
                            document.getElementById('balance').value = balVal;
                            document.getElementById('dispBal').innerText = '₹ ' + balVal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            box.style.display = 'flex';
                        })
                        .catch(err => console.error(err));

                    // Fetch OD limit
                    fetch('getod.php?bank_id=' + encodeURIComponent(bankId))
                        .then(res => res.text())
                        .then(od => {
                            const odVal = parseFloat(od.trim()) || 0;
                            document.getElementById('od').value = odVal;
                            document.getElementById('dispOd').innerText = '₹ ' + odVal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                        })
                        .catch(err => console.error(err));
                }

                function calculateTotal() {
                    let total = 0;
                    const inputs = document.querySelectorAll('.amt-input');
                    inputs.forEach(input => {
                        const val = parseFloat(input.value) || 0;
                        total += val;
                    });
                    document.getElementById('totalAmtDisplay').innerText = '₹ ' + total.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                    return total;
                }

                function reindexRows() {
                    const rows = document.querySelectorAll('#itemsTableBody tr.item-row');
                    rows.forEach((tr, index) => {
                        const numCell = tr.querySelector('td:first-child');
                        if (numCell) numCell.innerText = (index + 1);
                        const amtInput = tr.querySelector('.amt-input');
                        if (amtInput) amtInput.name = `amt[${index}]`;
                        const memoInput = tr.querySelector('.pm-textarea');
                        if (memoInput) memoInput.name = `memo[${index}]`;
                        tr.id = `row_${index}`;
                        const deleteBtn = tr.querySelector('.pm-btn-danger');
                        if (deleteBtn) {
                            deleteBtn.setAttribute('onclick', `removeRow(${index});`);
                        }
                    });
                    document.getElementById('count').value = Math.max(0, rows.length - 1);
                }

                function addNewRow() {
                    rowCount++;
                    const tbody = document.getElementById('itemsTableBody');
                    const tr = document.createElement('tr');
                    tr.className = 'item-row';

                    tr.innerHTML = `
                        <td style="font-family: monospace; color: var(--pm-slate-400);"></td>
                        <td>
                            <input type="number" step="any" class="pm-input amt-input" placeholder="0.00" oninput="calculateTotal();">
                        </td>
                        <td>
                            <textarea class="pm-textarea" placeholder="Enter transaction memo / vendor / purpose..."></textarea>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="pm-btn pm-btn-danger pm-btn-sm"><i class="fa fa-trash"></i></button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                    reindexRows();
                }

                function removeRow(idx) {
                    const row = document.getElementById('row_' + idx);
                    if (row) {
                        row.remove();
                        reindexRows();
                        calculateTotal();
                    }
                }

                function validateTransaction() {
                    reindexRows();
                    syncDate();

                    const transType = document.getElementById('trans_type').value;
                    const bankId = document.getElementById('bank_id').value;
                    const bankId1 = document.getElementById('bank_id1').value;

                    if (transType === '0') {
                        alert("Please select a valid transaction type.");
                        document.getElementById('trans_type').focus();
                        return false;
                    }

                    if (bankId === '0') {
                        alert("Please select the bank account.");
                        document.getElementById('bank_id').focus();
                        return false;
                    }

                    if (transType === 'banktrans') {
                        if (bankId1 === '0') {
                            alert("Please select the destination bank account for transfer.");
                            document.getElementById('bank_id1').focus();
                            return false;
                        }
                        if (bankId === bankId1) {
                            alert("Source bank and Destination bank cannot be the same account.");
                            document.getElementById('bank_id1').focus();
                            return false;
                        }
                    }

                    const totalAmt = calculateTotal();
                    if (totalAmt <= 0) {
                        alert("Please enter at least one valid line item amount greater than 0.");
                        return false;
                    }

                    const bal = parseFloat(document.getElementById('balance').value) || 0;
                    const od = parseFloat(document.getElementById('od').value) || 0;

                    if ((transType === 'payment' || transType === 'banktrans') && (totalAmt > (bal + od))) {
                        const maxAvail = bal + od;
                        alert(`Insufficient funds for this transaction.\n\nTotal Needed: ₹ ${totalAmt.toLocaleString()}\nAvailable (Balance + OD): ₹ ${maxAvail.toLocaleString()}`);
                        return false;
                    }

                    return confirm(`Confirm posting ${transType.toUpperCase()} of ₹ ${totalAmt.toLocaleString('en-IN', {minimumFractionDigits: 2})}?`);
                }
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