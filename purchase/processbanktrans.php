<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

include(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

$bank_id    = isset($_POST['bank_id']) ? intval($_POST['bank_id']) : 0;
$bank_id1   = isset($_POST['bank_id1']) ? intval($_POST['bank_id1']) : 0;
$trans_type = isset($_POST['trans_type']) ? trim($_POST['trans_type']) : '';
$cntr       = isset($_POST['count']) ? intval($_POST['count']) : 0;
$trans_date = isset($_POST['transdate']) ? trim($_POST['transdate']) : date('d/m/Y');

$c = 0;
$amt = [];
$memo = [];
$flag = [];

if (isset($_POST['amt']) && is_array($_POST['amt'])) {
    foreach ($_POST['amt'] as $idx => $raw_amt) {
        $clean_amt = floatval($raw_amt);
        if ($clean_amt > 0) {
            $amt[$c] = $clean_amt;
            $memo[$c] = isset($_POST['memo'][$idx]) ? trim($_POST['memo'][$idx]) : '';
            $flag[$c] = 1;
            $c++;
        }
    }
}

// Fetch Bank Names for display
$source_bank_name = "Bank #$bank_id";
$dest_bank_name = $bank_id1 ? "Bank #$bank_id1" : "";
if ($con) {
    $qb1 = mysqli_query($con, "SELECT bank_name FROM banks WHERE bank_id = $bank_id LIMIT 1");
    if ($qb1 && $rb1 = mysqli_fetch_assoc($qb1)) {
        $source_bank_name = $rb1['bank_name'];
    }
    if ($bank_id1 > 0) {
        $qb2 = mysqli_query($con, "SELECT bank_name FROM banks WHERE bank_id = $bank_id1 LIMIT 1");
        if ($qb2 && $rb2 = mysqli_fetch_assoc($qb2)) {
            $dest_bank_name = $rb2['bank_name'];
        }
    }
}

$transqry = [];
$transqry1 = [];
$transac = [];
$error_msg = "";
$success = 0;

if ($con && $c > 0 && $bank_id > 0 && in_array($trans_type, ['payment', 'receit', 'banktrans'])) {
    mysqli_query($con, "SET AUTOCOMMIT=0");
    mysqli_query($con, "START TRANSACTION");

    for ($i = 0; $i < $c; $i++) {
        $safe_memo = mysqli_real_escape_string($con, $memo[$i]);
        $val_amt   = $amt[$i];
        
        $insert_sql = "INSERT INTO `bank_transaction` (`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`, `enrty_date`) 
                       VALUES ('', '$bank_id', '$trans_type', '$val_amt', STR_TO_DATE('$trans_date', '%d/%m/%Y'), '$safe_memo', 'NO', NOW())";
        
        $transqry[$i] = mysqli_query($con, $insert_sql);
        $new_id = mysqli_insert_id($con);
        $transac[$i] = $new_id;

        if ($transqry[$i]) {
            if ($trans_type === 'banktrans') {
                $transfer_sql = "INSERT INTO `bank_transaction` (`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`, `enrty_date`) 
                                 VALUES ('', '$bank_id1', 'receit', '$val_amt', STR_TO_DATE('$trans_date', '%d/%m/%Y'), '$safe_memo', 'NO', NOW())";
                $transqry1 = mysqli_query($con, $transfer_sql);
                if (!$transqry1) {
                    $flag[$i] = 0;
                    $error_msg = mysqli_error($con);
                }
            }
        } else {
            $flag[$i] = 0;
            $error_msg = mysqli_error($con);
        }
    }

    $all_ok = 1;
    for ($i = 0; $i < $c; $i++) {
        if (!($transqry[$i] && $flag[$i] == 1)) {
            $all_ok = 0;
            break;
        }
    }

    if ($all_ok == 1) {
        mysqli_query($con, "COMMIT");
        $success = 1;
    } else {
        mysqli_query($con, "ROLLBACK");
        $success = 0;
    }
} else {
    $error_msg = "Invalid transaction parameters or no line items provided.";
}

if (file_exists(__DIR__ . '/../top-header.php')) include_once(__DIR__ . '/../top-header.php');
if (file_exists(__DIR__ . '/../top-navbar.php')) include_once(__DIR__ . '/../top-navbar.php');
?>

<div class="container-fluid page-body-wrapper" style="padding-top: 58px !important;">
    <?php if (file_exists(__DIR__ . '/../navbar.php')) include_once(__DIR__ . '/../navbar.php'); ?>
    <div class="main-panel" style="margin-left: 240px;">
        <div class="content-wrapper" style="padding: 2rem !important; background: #f8fafc; min-height: calc(100vh - 58px); display: flex; justify-content: center; align-items: flex-start;">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
            
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

                #sidebar {
                    position: fixed !important;
                    top: 58px;
                    left: 0;
                    bottom: 0;
                    height: calc(100vh - 58px);
                    z-index: 99;
                }

                .result-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 12px;
                    width: 100%;
                    max-width: 640px;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
                    overflow: hidden;
                    margin-top: 20px;
                }

                .result-header {
                    padding: 24px 28px;
                    text-align: center;
                    border-bottom: 1px solid var(--pm-slate-200);
                }

                .result-icon {
                    width: 56px;
                    height: 56px;
                    border-radius: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    margin-bottom: 12px;
                }

                .result-icon-success {
                    background: #f0fdf4;
                    color: #16a34a;
                    border: 1px solid #bbf7d0;
                }

                .result-icon-error {
                    background: #fef2f2;
                    color: #dc2626;
                    border: 1px solid #fecaca;
                }

                .result-title {
                    font-size: 20px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                }

                .result-subtitle {
                    font-size: 13px;
                    color: var(--pm-slate-500);
                    margin-top: 4px;
                }

                .result-body {
                    padding: 24px 28px;
                }

                .detail-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid var(--pm-slate-100);
                    font-size: 13px;
                }

                .detail-row:last-child {
                    border-bottom: none;
                }

                .detail-label {
                    color: var(--pm-slate-500);
                    font-weight: 500;
                }

                .detail-val {
                    color: var(--pm-slate-900);
                    font-weight: 600;
                }

                .pm-btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    height: 38px;
                    padding: 0 16px;
                    font-size: 13px;
                    font-weight: 500;
                    border-radius: 6px;
                    cursor: pointer;
                    text-decoration: none;
                    transition: all 0.15s ease;
                }

                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border: 1px solid var(--pm-slate-900);
                }

                .pm-btn-primary:hover {
                    background: var(--pm-slate-800);
                    color: #ffffff;
                }

                .pm-btn-secondary {
                    background: #ffffff;
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
                }

                .pm-btn-secondary:hover {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-900);
                }

                .result-actions {
                    padding: 16px 28px;
                    background: var(--pm-slate-50);
                    border-top: 1px solid var(--pm-slate-200);
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                }
            </style>

            <div class="result-card">
                <?php if ($success == 1): ?>
                    <div class="result-header">
                        <div class="result-icon result-icon-success">
                            <i class="fa fa-check"></i>
                        </div>
                        <h2 class="result-title">Transaction Posted Successfully</h2>
                        <p class="result-subtitle">All entries have been recorded and ledger balances updated in real-time.</p>
                    </div>

                    <div class="result-body">
                        <div class="detail-row">
                            <span class="detail-label">Transaction Type:</span>
                            <span class="detail-val" style="text-transform: uppercase;"><?= htmlspecialchars($trans_type) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Date:</span>
                            <span class="detail-val"><?= htmlspecialchars($trans_date) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Primary Bank:</span>
                            <span class="detail-val"><?= htmlspecialchars($source_bank_name) ?></span>
                        </div>
                        <?php if ($trans_type === 'banktrans'): ?>
                        <div class="detail-row">
                            <span class="detail-label">Destination Bank:</span>
                            <span class="detail-val"><?= htmlspecialchars($dest_bank_name) ?></span>
                        </div>
                        <?php endif; ?>

                        <div style="margin-top: 16px; border: 1px solid var(--pm-slate-200); border-radius: 8px; overflow: hidden;">
                            <div style="background: var(--pm-slate-50); padding: 8px 14px; font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--pm-slate-500); border-bottom: 1px solid var(--pm-slate-200);">
                                Recorded Line Items (<?= $c ?>)
                            </div>
                            <?php 
                            $total_posted = 0;
                            for ($i = 0; $i < $c; $i++): 
                                $total_posted += $amt[$i];
                            ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; border-bottom: 1px solid var(--pm-slate-100); font-size: 13px;">
                                    <div>
                                        <span style="font-family: monospace; color: var(--pm-slate-400); margin-right: 6px;">#<?= $transac[$i] ?></span>
                                        <span style="color: var(--pm-slate-600);"><?= htmlspecialchars($memo[$i] ?: 'No narration') ?></span>
                                    </div>
                                    <span style="font-family: monospace; font-weight: 700; color: var(--pm-slate-900);">
                                        ₹ <?= number_format($amt[$i], 2) ?>
                                    </span>
                                </div>
                            <?php endfor; ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: var(--pm-slate-50); font-size: 14px;">
                                <span style="font-weight: 600; color: var(--pm-slate-700);">Total Posted:</span>
                                <span style="font-weight: 800; font-family: monospace; color: var(--pm-slate-900);">
                                    ₹ <?= number_format($total_posted, 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="result-actions">
                        <a href="bank_report.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-chart-line"></i> Bank Ledger
                        </a>
                        <a href="bank_entry.php" class="pm-btn pm-btn-primary">
                            <i class="fa fa-plus"></i> Another Transaction
                        </a>
                    </div>

                <?php else: ?>
                    <div class="result-header">
                        <div class="result-icon result-icon-error">
                            <i class="fa fa-triangle-exclamation"></i>
                        </div>
                        <h2 class="result-title">Transaction Failed</h2>
                        <p class="result-subtitle">Could not commit the bank transaction. No funds or ledger entries were affected.</p>
                    </div>

                    <div class="result-body">
                        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 14px; font-size: 13px; color: #b91c1c;">
                            <strong>Error Details:</strong> <?= htmlspecialchars($error_msg ?: 'An unexpected database error occurred.') ?>
                        </div>
                    </div>

                    <div class="result-actions">
                        <a href="bank_entry.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-arrow-left"></i> Return to Bank Entry
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php 
if ($con) CloseCon($con);
?>
</body>
</html>