<?php
include_once(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

$bank_id = isset($_GET['bank_id']) ? intval($_GET['bank_id']) : 0;
$frmdate = isset($_GET['frmdate']) ? trim($_GET['frmdate']) : '';
$todate  = isset($_GET['todate']) ? trim($_GET['todate']) : '';

$balance = 0;
$payment = 0;
$income  = 0;

if ($frmdate === "" && $todate === "") {
    $date = date('Y-m-d');
    $qrytrans = "SELECT * FROM `bank_transaction` WHERE `trans_date`='$date' AND `bank_id`='$bank_id' ORDER BY trans_id ASC";
    $qrybal   = mysqli_query($con, "SELECT * FROM `bank_transaction` WHERE `trans_date`<'$date' AND `bank_id`='$bank_id'");
} else {
    // Check if dates are DD/MM/YYYY or YYYY-MM-DD
    $from_sql = (strpos($frmdate, '/') !== false) ? "STR_TO_DATE('$frmdate','%d/%m/%Y')" : "'$frmdate'";
    $to_sql   = (strpos($todate, '/') !== false) ? "STR_TO_DATE('$todate','%d/%m/%Y')" : "'$todate'";

    $qrytrans = "SELECT * FROM `bank_transaction` WHERE `bank_id`='$bank_id' AND (`trans_date` BETWEEN $from_sql AND $to_sql) ORDER BY trans_date ASC, trans_id ASC";
    $qrybal   = mysqli_query($con, "SELECT * FROM `bank_transaction` WHERE `bank_id`='$bank_id' AND `trans_date` < $from_sql");
}

if ($qrybal) {
    while ($resbal = mysqli_fetch_row($qrybal)) {
        if ($resbal[2] === "payment" || $resbal[2] === "banktrans") {
            $payment += floatval($resbal[3]);
        } else if ($resbal[2] === "receit") {
            $income += floatval($resbal[3]);
        }
    }
}
$balance = $income - $payment;

$trans = $con ? mysqli_query($con, $qrytrans) : null;
?>

<div style="overflow-x: auto; margin-top: 16px;">
    <table class="pm-items-table" style="width: 100%;">
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th style="width: 80px;">Trans ID</th>
                <th style="width: 110px;">Date</th>
                <th style="width: 120px; text-align: right;">Credit / Deposit (₹)</th>
                <th style="width: 120px; text-align: right;">Debit / Payment (₹)</th>
                <th>Transaction Memo / Narration</th>
                <th style="width: 130px; text-align: center;">Reconcile</th>
            </tr>
            <tr style="background: #f1f5f9;">
                <td colspan="3" style="font-weight: 600; text-align: right; color: var(--pm-slate-700);">Balance Brought Forward:</td>
                <td colspan="2" style="text-align: right; font-family: monospace; font-weight: 700; color: var(--pm-slate-900);">
                    ₹ <?= number_format($balance, 2) ?>
                </td>
                <td colspan="2"></td>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 0;
            $cu_pay = 0;
            $cu_income = 0;
            if ($trans && mysqli_num_rows($trans) > 0):
                while ($row = mysqli_fetch_assoc($trans)):
                    $t_id   = $row['trans_id'];
                    $t_amt  = floatval($row['trans_amt']);
                    $t_date = ($row['trans_date'] && $row['trans_date'] !== '0000-00-00') ? date('d/m/Y', strtotime($row['trans_date'])) : '—';
                    $t_memo = htmlspecialchars($row['trans_memo'] ?? '');
                    $is_rec = ($row['reconcile'] === 'Yes');
                    
                    $credit_amt = ($row['trans_type'] === 'receit') ? $t_amt : 0;
                    $debit_amt  = ($row['trans_type'] === 'payment' || $row['trans_type'] === 'banktrans') ? $t_amt : 0;
                    
                    $cu_income += $credit_amt;
                    $cu_pay    += $debit_amt;
            ?>
                <tr id="row_<?= $t_id ?>">
                    <td style="font-family: monospace; color: var(--pm-slate-400);"><?= $i + 1 ?></td>
                    <td style="font-family: monospace; font-weight: 600;">
                        <input type="hidden" name="trans_id[]" value="<?= $t_id ?>">
                        #<?= $t_id ?>
                    </td>
                    <td style="font-family: monospace;"><?= $t_date ?></td>
                    <td style="text-align: right; font-family: monospace; font-weight: 600; color: #15803d;">
                        <?= ($credit_amt > 0) ? '₹ ' . number_format($credit_amt, 2) : '—' ?>
                    </td>
                    <td style="text-align: right; font-family: monospace; font-weight: 600; color: #b91c1c;">
                        <?= ($debit_amt > 0) ? '₹ ' . number_format($debit_amt, 2) : '—' ?>
                    </td>
                    <td style="color: var(--pm-slate-700);"><?= $t_memo ?: '—' ?></td>
                    <td style="text-align: center;">
                        <label class="pm-checkbox-label">
                            <input type="checkbox" name="concil[<?= $i ?>]" value="<?= $t_id ?>" <?= $is_rec ? 'checked' : '' ?> class="recon-check" onchange="toggleReconcile(<?= $t_id ?>, this.checked);">
                            <span class="pm-badge-<?= $is_rec ? 'reconciled' : 'pending' ?>" id="status_<?= $t_id ?>">
                                <?= $is_rec ? 'Reconciled' : 'Pending' ?>
                            </span>
                        </label>
                    </td>
                </tr>
            <?php 
                    $i++;
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 32px; color: var(--pm-slate-400);">
                        No transactions found for the selected bank account and date range.
                    </td>
                </tr>
            <?php endif; 
            $outstand = $balance + $cu_income - $cu_pay;
            ?>
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: 600;">
                <td colspan="3" style="text-align: right; color: var(--pm-slate-700);">Period Totals:</td>
                <td style="text-align: right; font-family: monospace; color: #15803d;">₹ <?= number_format($cu_income, 2) ?></td>
                <td style="text-align: right; font-family: monospace; color: #b91c1c;">₹ <?= number_format($cu_pay, 2) ?></td>
                <td colspan="2"></td>
            </tr>
            <tr style="background: #f1f5f9; font-weight: 700; font-size: 14px;">
                <td colspan="3" style="text-align: right; color: var(--pm-slate-900);">Closing Book Balance:</td>
                <td colspan="2" style="text-align: right; font-family: monospace; color: var(--pm-slate-900);">₹ <?= number_format($outstand, 2) ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    <input type="hidden" name="count" value="<?= $i ?>" />
</div>

<?php 
if ($con) CloseCon($con);
?>
