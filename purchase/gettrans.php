<?php
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

$bank_id = isset($_GET['bank_id']) ? mysqli_real_escape_string($con, trim($_GET['bank_id'])) : '0';
$frmdate = isset($_GET['frmdate']) ? trim($_GET['frmdate']) : '';
$todate  = isset($_GET['todate']) ? trim($_GET['todate']) : '';
$ld      = isset($_GET['ld']) ? trim($_GET['ld']) : '';

// Helper to normalize dates to YYYY-MM-DD
function normalize_date($d) {
    if (!$d) return '';
    $d = trim($d);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        return $d;
    }
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $d, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    $ts = strtotime($d);
    return $ts ? date('Y-m-d', $ts) : '';
}

function safe_str($v) {
    return trim((string)($v ?? ''));
}

$today = date('Y-m-d');
if ($ld == "ld") {
    $sql_from = $today;
    $sql_to   = $today;
} else if ($frmdate == "" && $todate == "") {
    $sql_from = $today;
    $sql_to   = $today;
} else {
    $sql_from = normalize_date($frmdate);
    $sql_to   = normalize_date($todate);
    if (!$sql_from) $sql_from = $today;
    if (!$sql_to)   $sql_to   = $today;
}

// Fetch bank name for display
$bank_name = 'All Bank Accounts';
if ($bank_id != '0' && $bank_id != '') {
    $qrybank = mysqli_query($con, "SELECT `bank_name` FROM `banks` WHERE `bank_id` = '$bank_id'");
    if ($qrybank && $bRow = mysqli_fetch_row($qrybank)) {
        $bank_name = $bRow[0];
    }
}

// 1. Calculate Opening Balance (all transactions before $sql_from)
$balance = 0;
$prior_payment = 0;
$prior_income = 0;

if ($bank_id != '0' && $bank_id != '') {
    $qrybal = mysqli_query($con, "SELECT trans_type, trans_amt FROM `bank_transaction` WHERE `bank_id` = '$bank_id' AND `trans_date` < '$sql_from'");
} else {
    $qrybal = mysqli_query($con, "SELECT trans_type, trans_amt FROM `bank_transaction` WHERE `trans_date` < '$sql_from'");
}

if ($qrybal) {
    while ($resbal = mysqli_fetch_row($qrybal)) {
        $type = strtolower(safe_str($resbal[0] ?? ''));
        $amt  = floatval($resbal[1]);
        if ($type == "payment" || $type == "banktrans") {
            $prior_payment += $amt;
        } else if ($type == "receit" || $type == "receipt") {
            $prior_income += $amt;
        }
    }
}
$opening_balance = $prior_income - $prior_payment;

// 2. Query Period Transactions
if ($bank_id != '0' && $bank_id != '') {
    $qrytrans = "SELECT bt.trans_id, bt.bank_id, bt.trans_type, bt.trans_amt, bt.trans_date, bt.trans_memo, bt.reconcile, b.bank_name 
                 FROM `bank_transaction` bt 
                 LEFT JOIN `banks` b ON bt.bank_id = b.bank_id 
                 WHERE bt.bank_id = '$bank_id' 
                   AND (bt.trans_date BETWEEN '$sql_from' AND '$sql_to') 
                 ORDER BY bt.trans_date ASC, bt.trans_id ASC";
} else {
    $qrytrans = "SELECT bt.trans_id, bt.bank_id, bt.trans_type, bt.trans_amt, bt.trans_date, bt.trans_memo, bt.reconcile, b.bank_name 
                 FROM `bank_transaction` bt 
                 LEFT JOIN `banks` b ON bt.bank_id = b.bank_id 
                 WHERE (bt.trans_date BETWEEN '$sql_from' AND '$sql_to') 
                 ORDER BY bt.trans_date ASC, bt.trans_id ASC";
}

$trans_res = mysqli_query($con, $qrytrans);
$rows = [];
$cu_pay = 0;
$cu_income = 0;
$debit_count = 0;
$credit_count = 0;

if ($trans_res) {
    while ($r = mysqli_fetch_assoc($trans_res)) {
        $rows[] = $r;
        $type = strtolower(safe_str($r['trans_type'] ?? ''));
        $amt = floatval($r['trans_amt']);
        if ($type == "payment" || $type == "banktrans") {
            $cu_pay += $amt;
            $debit_count++;
        } else if ($type == "receit" || $type == "receipt") {
            $cu_income += $amt;
            $credit_count++;
        }
    }
}

// Net Closing Balance
$closing_balance = $opening_balance + $cu_income - $cu_pay;
$total_count = count($rows);
?>

<style>
    /* Metric Cards Grid */
    .pm-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }

    @media (max-width: 992px) {
        .pm-kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .pm-kpi-grid {
            grid-template-columns: 1fr;
        }
    }

    .pm-kpi-card {
        background: #ffffff;
        border: 1px solid var(--pm-slate-200);
        border-radius: 8px;
        padding: 16px 18px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
    }

    .pm-kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--pm-slate-500);
        margin-bottom: 4px;
    }

    .pm-kpi-value {
        font-size: 20px;
        font-weight: 700;
        color: var(--pm-slate-900);
        line-height: 1.2;
        letter-spacing: -0.02em;
    }

    .pm-kpi-sub {
        font-size: 11.5px;
        color: var(--pm-slate-500);
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .pm-kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: var(--pm-slate-100);
        border: 1px solid var(--pm-slate-200);
        color: var(--pm-slate-600);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
        margin-left: 12px;
    }

    /* Ledger Table Styling */
    .pm-ledger-card {
        background: #ffffff;
        border: 1px solid var(--pm-slate-200);
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    .pm-ledger-header {
        padding: 14px 18px;
        background: #ffffff;
        border-bottom: 1px solid var(--pm-slate-200);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .pm-ledger-title {
        font-size: 14.5px;
        font-weight: 700;
        color: var(--pm-slate-900);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pm-badge-count {
        background: var(--pm-slate-100);
        border: 1px solid var(--pm-slate-200);
        color: var(--pm-slate-700);
        font-size: 11.5px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 12px;
    }

    .pm-search-input {
        height: 34px;
        font-size: 12.5px;
        color: var(--pm-slate-900);
        background: #ffffff;
        border: 1px solid var(--pm-slate-200);
        border-radius: 6px;
        padding: 4px 12px;
        width: 220px;
        transition: all 0.15s ease;
    }

    .pm-search-input:focus {
        border-color: var(--pm-slate-900);
        outline: none;
    }

    .pm-ledger-table {
        margin: 0;
        border-collapse: collapse;
        width: 100%;
    }

    .pm-ledger-table th {
        background: var(--pm-slate-50);
        color: var(--pm-slate-600);
        font-size: 11.5px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 10px 14px;
        border-bottom: 1px solid var(--pm-slate-200);
        border-top: none;
        white-space: nowrap;
    }

    .pm-ledger-table td {
        padding: 11px 14px;
        font-size: 13px;
        color: var(--pm-slate-900);
        border-bottom: 1px solid var(--pm-slate-200);
        vertical-align: middle;
    }

    .pm-ledger-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .pm-row-bf {
        background-color: #fafbfc;
    }

    .pm-code-badge {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 11.5px;
        font-weight: 600;
        background: var(--pm-slate-100);
        border: 1px solid var(--pm-slate-200);
        color: var(--pm-slate-700);
        padding: 2px 6px;
        border-radius: 4px;
        white-space: nowrap;
    }

    .pm-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11.5px;
        font-weight: 500;
        background: var(--pm-slate-100);
        border: 1px solid var(--pm-slate-200);
        color: var(--pm-slate-700);
    }

    .pm-status-pill .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .pm-status-pill .dot-green {
        background-color: #10b981;
    }

    .pm-status-pill .dot-slate {
        background-color: #94a3b8;
    }

    .pm-action-btn {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: 1px solid var(--pm-slate-200);
        background: #ffffff;
        color: var(--pm-slate-600);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
        margin-right: 4px;
    }

    .pm-action-btn:hover {
        background: var(--pm-slate-100);
        color: var(--pm-slate-900);
        border-color: #cbd5e1;
    }

    .pm-action-btn.btn-delete:hover {
        background: #fef2f2;
        color: #ef4444;
        border-color: #fecaca;
    }

    .pm-memo-text {
        font-size: 12.5px;
        color: var(--pm-slate-700);
        line-height: 1.45;
        max-width: 320px;
        word-break: break-word;
    }

    .pm-totals-row {
        background: var(--pm-slate-50);
        font-weight: 700;
    }

    .pm-totals-row td {
        border-top: 2px solid var(--pm-slate-200);
        font-size: 13px;
        color: var(--pm-slate-900);
    }

    .pm-closing-row {
        background: #ffffff;
        font-weight: 700;
    }

    .pm-closing-row td {
        border-top: 1px solid var(--pm-slate-200);
        border-bottom: 2px solid var(--pm-slate-900);
        font-size: 13.5px;
        color: var(--pm-slate-900);
        padding: 14px;
    }
</style>

<!-- 1. KPI Summary Cards -->
<div class="pm-kpi-grid">
    <!-- Opening Balance -->
    <div class="pm-kpi-card">
        <div>
            <div class="pm-kpi-label">Opening Balance</div>
            <div class="pm-kpi-value">₹<?php echo number_format(abs($opening_balance), 2); ?></div>
            <div class="pm-kpi-sub">
                <span class="pm-code-badge" style="font-size: 11px;">
                    <?php echo ($opening_balance >= 0) ? 'Credit (Cr)' : 'Debit (Dr)'; ?>
                </span>
                <span>Prior to <?php echo date('d M Y', strtotime($sql_from)); ?></span>
            </div>
        </div>
        <div class="pm-kpi-icon" title="Balance brought forward">
            <i class="fa fa-balance-scale"></i>
        </div>
    </div>

    <!-- Total Debits -->
    <div class="pm-kpi-card">
        <div>
            <div class="pm-kpi-label">Total Debits (Outflows)</div>
            <div class="pm-kpi-value">₹<?php echo number_format($cu_pay, 2); ?></div>
            <div class="pm-kpi-sub">
                <span class="pm-code-badge" style="font-size: 11px;"><?php echo $debit_count; ?> Txns</span>
                <span>Payments & Transfers</span>
            </div>
        </div>
        <div class="pm-kpi-icon" title="Debit transactions">
            <i class="fa fa-arrow-up text-muted"></i>
        </div>
    </div>

    <!-- Total Credits -->
    <div class="pm-kpi-card">
        <div>
            <div class="pm-kpi-label">Total Credits (Inflows)</div>
            <div class="pm-kpi-value">₹<?php echo number_format($cu_income, 2); ?></div>
            <div class="pm-kpi-sub">
                <span class="pm-code-badge" style="font-size: 11px;"><?php echo $credit_count; ?> Txns</span>
                <span>Receipts & Deposits</span>
            </div>
        </div>
        <div class="pm-kpi-icon" title="Credit transactions">
            <i class="fa fa-arrow-down text-muted"></i>
        </div>
    </div>

    <!-- Net Closing Balance -->
    <div class="pm-kpi-card">
        <div>
            <div class="pm-kpi-label">Net Closing Balance</div>
            <div class="pm-kpi-value">₹<?php echo number_format(abs($closing_balance), 2); ?></div>
            <div class="pm-kpi-sub">
                <span class="pm-code-badge" style="font-size: 11px;">
                    <?php echo ($closing_balance >= 0) ? 'Cr Available' : 'Dr Overdraft'; ?>
                </span>
                <span>As of <?php echo date('d M Y', strtotime($sql_to)); ?></span>
            </div>
        </div>
        <div class="pm-kpi-icon" title="Closing balance outstanding">
            <i class="fa fa-university"></i>
        </div>
    </div>
</div>

<!-- 2. Ledger Transactions Card -->
<div class="pm-ledger-card">
    <div class="pm-ledger-header">
        <div>
            <h3 class="pm-ledger-title">
                <i class="fa fa-book text-muted" style="font-size: 15px; margin-right: 6px;"></i>
                Statement Ledger: <?php echo htmlspecialchars($bank_name); ?>
                <span class="pm-badge-count"><?php echo $total_count; ?> entries</span>
            </h3>
            <div style="font-size: 12px; color: var(--pm-slate-500); margin-top: 2px;">
                Period: <?php echo date('d M Y', strtotime($sql_from)); ?> to <?php echo date('d M Y', strtotime($sql_to)); ?>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 d-print-none flex-wrap">
            <input type="text" id="tableSearchInput" class="pm-search-input" 
                   placeholder="Search memo or Txn ID..." 
                   onkeyup="filterLedgerTable()" />
            <button type="button" class="pm-btn-outline" style="height: 34px; padding: 4px 12px;" onclick="exportLedgerToCSV()">
                <i class="fa fa-file-excel-o"></i> Export CSV
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table pm-ledger-table" id="bankLedgerTable">
            <thead>
                <tr>
                    <th style="width: 45px; text-align: center;">#</th>
                    <th style="width: 95px;">Txn ID</th>
                    <th style="width: 105px;">Date</th>
                    <th style="width: 140px;">Bank Account</th>
                    <th style="width: 125px; text-align: right;">Debit (₹)</th>
                    <th style="width: 125px; text-align: right;">Credit (₹)</th>
                    <th>Particulars / Memo</th>
                    <th style="width: 90px; text-align: center;">Reconcile</th>
                    <th style="width: 90px; text-align: center;" class="d-print-none">Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Balance Brought Forward Row -->
                <tr class="pm-row-bf">
                    <td class="text-center text-muted" style="font-weight: 600;">—</td>
                    <td><span class="pm-code-badge">—</span></td>
                    <td style="font-size: 12px; color: var(--pm-slate-600);"><?php echo date('d M Y', strtotime($sql_from)); ?></td>
                    <td style="font-size: 12px; color: var(--pm-slate-600);"><?php echo htmlspecialchars($bank_name); ?></td>
                    <td class="text-right" style="font-weight: 600; color: var(--pm-slate-900);">
                        <?php echo ($opening_balance < 0) ? number_format(abs($opening_balance), 2) : '—'; ?>
                    </td>
                    <td class="text-right" style="font-weight: 600; color: var(--pm-slate-900);">
                        <?php echo ($opening_balance >= 0) ? number_format($opening_balance, 2) : '—'; ?>
                    </td>
                    <td colspan="2">
                        <span style="font-weight: 600; color: var(--pm-slate-900);">
                            <i class="fa fa-history text-muted" style="margin-right: 6px;"></i> Balance Brought Forward (Opening Balance)
                        </span>
                    </td>
                    <td class="text-center d-print-none text-muted">—</td>
                </tr>

                <?php
                if ($total_count == 0) {
                ?>
                    <tr class="pm-empty-row">
                        <td colspan="9" class="text-center py-5">
                            <div style="font-size: 14px; font-weight: 600; color: var(--pm-slate-700); margin-bottom: 4px;">
                                No transactions found
                            </div>
                            <div style="font-size: 12px; color: var(--pm-slate-500);">
                                No bank debits or credits recorded for <?php echo htmlspecialchars($bank_name); ?> between <?php echo date('d M Y', strtotime($sql_from)); ?> and <?php echo date('d M Y', strtotime($sql_to)); ?>.
                            </div>
                        </td>
                    </tr>
                <?php
                } else {
                    $cnt = 1;
                    foreach ($rows as $row) {
                        $trans_id  = $row['trans_id'];
                        $type      = strtolower(safe_str($row['trans_type'] ?? ''));
                        $amt       = floatval($row['trans_amt']);
                        $is_debit  = ($type == "payment" || $type == "banktrans");
                        $is_credit = ($type == "receit" || $type == "receipt");
                        $memo      = safe_str($row['trans_memo'] ?? '');
                        $is_reconciled = (strtoupper(safe_str($row['reconcile'] ?? '')) == 'YES');
                        $t_bank_name   = !empty($row['bank_name']) ? $row['bank_name'] : $bank_name;
                        $t_date        = !empty($row['trans_date']) ? date('d M Y', strtotime($row['trans_date'])) : '—';
                ?>
                    <tr class="pm-data-row" id="txn-row-<?php echo $trans_id; ?>">
                        <td class="text-center text-muted" style="font-size: 12px;"><?php echo $cnt; ?></td>
                        <td>
                            <span class="pm-code-badge">#<?php echo $trans_id; ?></span>
                        </td>
                        <td style="font-size: 12px; color: var(--pm-slate-700); white-space: nowrap;">
                            <?php echo $t_date; ?>
                        </td>
                        <td style="font-size: 12px; color: var(--pm-slate-700);">
                            <?php echo htmlspecialchars($t_bank_name); ?>
                        </td>
                        <td class="text-right" style="font-weight: 600; color: var(--pm-slate-900);">
                            <?php echo $is_debit ? number_format($amt, 2) : '—'; ?>
                        </td>
                        <td class="text-right" style="font-weight: 600; color: var(--pm-slate-900);">
                            <?php echo $is_credit ? number_format($amt, 2) : '—'; ?>
                        </td>
                        <td>
                            <div id="showrem<?php echo $cnt; ?>1" class="pm-memo-text">
                                <?php echo htmlspecialchars($memo); ?>
                            </div>
                            <input type="hidden" id="rem<?php echo $cnt; ?>" value="<?php echo htmlspecialchars($memo, ENT_QUOTES); ?>" />
                        </td>
                        <td class="text-center">
                            <?php if ($is_reconciled) { ?>
                                <span class="pm-status-pill" title="Reconciled">
                                    <span class="dot dot-green"></span> Yes
                                </span>
                            <?php } else { ?>
                                <span class="pm-status-pill" title="Not reconciled">
                                    <span class="dot dot-slate"></span> No
                                </span>
                            <?php } ?>
                        </td>
                        <td class="text-center d-print-none" style="white-space: nowrap;">
                            <button type="button" class="pm-action-btn" 
                                    onclick="edit_memo(<?php echo $cnt; ?>, '<?php echo $trans_id; ?>')" 
                                    title="Edit Memo">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button type="button" class="pm-action-btn btn-delete" 
                                    onclick="confirmDeleteTransaction('<?php echo $trans_id; ?>')" 
                                    title="Delete Transaction">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>
                        </td>
                    </tr>
                <?php
                        $cnt++;
                    }
                }
                ?>

                <!-- Period Totals Row -->
                <tr class="pm-totals-row">
                    <td colspan="4" class="text-right" style="font-weight: 700; color: var(--pm-slate-700);">
                        Total for Period (<?php echo $total_count; ?> Txns):
                    </td>
                    <td class="text-right" style="font-weight: 700; color: var(--pm-slate-900);">
                        ₹<?php echo number_format($cu_pay, 2); ?>
                    </td>
                    <td class="text-right" style="font-weight: 700; color: var(--pm-slate-900);">
                        ₹<?php echo number_format($cu_income, 2); ?>
                    </td>
                    <td colspan="2"></td>
                    <td class="d-print-none"></td>
                </tr>

                <!-- Net Closing Balance Row -->
                <tr class="pm-closing-row">
                    <td colspan="4" class="text-right" style="font-size: 13.5px; font-weight: 700; color: var(--pm-slate-900);">
                        Closing Balance (As of <?php echo date('d M Y', strtotime($sql_to)); ?>):
                    </td>
                    <td colspan="2" class="text-right" style="font-size: 15px; font-weight: 800; color: var(--pm-slate-900);">
                        ₹<?php echo number_format(abs($closing_balance), 2); ?>
                        <span class="pm-code-badge" style="font-size: 11px; margin-left: 6px;">
                            <?php echo ($closing_balance >= 0) ? 'Cr (Positive)' : 'Dr (Overdraft)'; ?>
                        </span>
                    </td>
                    <td colspan="2" style="font-size: 12px; color: var(--pm-slate-500);">
                        Opening (<?php echo number_format($opening_balance, 2); ?>) + Credit (<?php echo number_format($cu_income, 2); ?>) - Debit (<?php echo number_format($cu_pay, 2); ?>)
                    </td>
                    <td class="d-print-none"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Live table search filtering
    function filterLedgerTable() {
        var input = document.getElementById('tableSearchInput');
        if (!input) return;
        var filter = input.value.toLowerCase().trim();
        var rows = document.querySelectorAll('#bankLedgerTable tbody tr.pm-data-row');
        
        rows.forEach(function(row) {
            var text = row.innerText.toLowerCase();
            row.style.display = (text.indexOf(filter) > -1) ? '' : 'none';
        });
    }

    // Export Table to CSV
    function exportLedgerToCSV() {
        var table = document.getElementById('bankLedgerTable');
        if (!table) return;

        var csv = [];
        var rows = table.querySelectorAll('tr');

        rows.forEach(function(row) {
            // Skip empty placeholder row if present
            if (row.classList.contains('pm-empty-row')) return;

            var cols = row.querySelectorAll('th, td');
            var rowData = [];
            cols.forEach(function(col) {
                if (col.classList.contains('d-print-none')) return; // ignore action column
                var text = col.innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/"/g, '""').trim();
                rowData.push('"' + text + '"');
            });
            if (rowData.length > 0) {
                csv.push(rowData.join(','));
            }
        });

        var csvContent = "data:text/csv;charset=utf-8," + encodeURIComponent(csv.join("\n"));
        var link = document.createElement("a");
        var filename = "Bank_Ledger_" + "<?php echo preg_replace('/[^a-zA-Z0-9_-]/', '_', $bank_name); ?>" + "_" + "<?php echo $sql_from; ?>" + "_to_" + "<?php echo $sql_to; ?>.csv";
        link.setAttribute("href", csvContent);
        link.setAttribute("download", filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>

<?php
CloseCon($con);
?>