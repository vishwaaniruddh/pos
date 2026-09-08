<?php
include_once(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

$id = isset($_GET['barcode']) ? mysqli_real_escape_string($con, trim($_GET['barcode'])) : '';

$qry = "
    SELECT s.*, p.first_name, p.last_name, p.phone_number 
    FROM scheme s 
    LEFT JOIN phppos_people p ON s.cust_id = p.person_id 
    WHERE s.cust_id = '$id' AND s.status = 'A'
    ORDER BY s.bill_id DESC
";
$res = mysqli_query($con, $qry);
$num = $res ? mysqli_num_rows($res) : 0;

$pay = 0;
$bal1 = 0;
$sra = 0;
$na1 = 0;
?>

<div style="margin-top: 14px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #ffffff;">
    <div style="padding: 10px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; color: #0f172a;">
        Active Scheme Returns (<?= $num ?> Found)
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left; white-space: nowrap;">
            <thead>
                <tr style="background: #f8fafc; color: #475569; border-bottom: 1px solid #e2e8f0; font-size: 11px; text-transform: uppercase;">
                    <th style="padding: 10px 12px;">#</th>
                    <th style="padding: 10px 12px;">Bill No</th>
                    <th style="padding: 10px 12px;">Customer Name</th>
                    <th style="padding: 10px 12px;">Agent / Through</th>
                    <th style="padding: 10px 12px;">Bill Date</th>
                    <th style="padding: 10px 12px;">Maturity Date</th>
                    <th style="padding: 10px 12px; text-align: right;">Paid Amt (₹)</th>
                    <th style="padding: 10px 12px; text-align: right;">Balance (₹)</th>
                    <th style="padding: 10px 12px; text-align: right;">Return (65%)</th>
                    <th style="padding: 10px 12px; text-align: right;">Net Due (₹)</th>
                    <th style="padding: 10px 12px; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($num == 0): ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 30px; color: #64748b;">
                            No active scheme records found for this customer.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $i = 1;
                    $today = date('Y-m-d');
                    while ($row = mysqli_fetch_assoc($res)) {
                        $bill_no = !empty($row['new_bill_number']) ? $row['new_bill_number'] : $row['bill_id'];
                        $cust_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                        if (!$cust_name) $cust_name = 'Walk-in / General';
                        
                        $s_amt = floatval($row['amount'] ?? 0);
                        $p_amt = floatval($row['paid_amount'] ?? 0);
                        $bal = $s_amt - $p_amt;
                        $p = round($s_amt * 0.65);
                        $na = $s_amt - $p;

                        $pay += $p_amt;
                        $bal1 += $bal;
                        $sra += $p;
                        $na1 += $na;

                        $bill_date = ($row['bill_date'] && $row['bill_date'] !== '0000-00-00') ? date('d/m/Y', strtotime($row['bill_date'])) : '—';
                        $m_date = ($row['m_date'] && $row['m_date'] !== '0000-00-00') ? date('d/m/Y', strtotime($row['m_date'])) : '—';
                        $is_due = ($row['m_date'] && $row['m_date'] !== '0000-00-00' && $row['m_date'] <= $today);
                    ?>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 10px 12px; color: #94a3b8; font-family: monospace;"><?= $i ?></td>
                        <td style="padding: 10px 12px; font-weight: 600; font-family: monospace; color: #0f172a;"><?= htmlspecialchars($bill_no) ?></td>
                        <td style="padding: 10px 12px; font-weight: 500; color: #0f172a;"><?= htmlspecialchars($cust_name) ?></td>
                        <td style="padding: 10px 12px; color: #475569;"><?= htmlspecialchars($row['throught'] ?: '—') ?></td>
                        <td style="padding: 10px 12px; font-family: monospace; color: #475569;"><?= $bill_date ?></td>
                        <td style="padding: 10px 12px; font-family: monospace; color: #475569;">
                            <?= $m_date ?>
                            <?php if ($is_due): ?>
                                <span style="font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 4px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; margin-left: 4px;">Due</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px 12px; text-align: right; font-family: monospace; color: #334155;">₹ <?= number_format($p_amt) ?></td>
                        <td style="padding: 10px 12px; text-align: right; font-family: monospace; font-weight: 600; color: #0f172a;">₹ <?= number_format($bal) ?></td>
                        <td style="padding: 10px 12px; text-align: right; font-family: monospace; color: #475569;">₹ <?= number_format($p) ?></td>
                        <td style="padding: 10px 12px; text-align: right; font-family: monospace; font-weight: 600; color: #0f172a;">₹ <?= number_format($na) ?></td>
                        <td style="padding: 10px 12px; text-align: center;">
                            <a href="rent_detail1.php?id=<?= $row['bill_id'] ?>" style="display: inline-block; padding: 4px 10px; background: #0f172a; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: 500;">
                                Scheme Return
                            </a>
                        </td>
                    </tr>
                    <?php $i++; } ?>
                <?php endif; ?>
            </tbody>
            <?php if ($num > 0): ?>
            <tfoot>
                <tr style="background: #f1f5f9; font-weight: 700; color: #0f172a; border-top: 2px solid #e2e8f0;">
                    <td colspan="6" style="padding: 10px 12px; text-align: right; font-size: 11px; text-transform: uppercase;">Total:</td>
                    <td style="padding: 10px 12px; text-align: right; font-family: monospace;">₹ <?= number_format($pay) ?></td>
                    <td style="padding: 10px 12px; text-align: right; font-family: monospace;">₹ <?= number_format($bal1) ?></td>
                    <td style="padding: 10px 12px; text-align: right; font-family: monospace;">₹ <?= number_format($sra) ?></td>
                    <td style="padding: 10px 12px; text-align: right; font-family: monospace;">₹ <?= number_format($na1) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php CloseCon($con); ?>