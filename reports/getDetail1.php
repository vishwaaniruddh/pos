<?php
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

$id = isset($_GET['barcode']) ? mysqli_real_escape_string($con, trim($_GET['barcode'])) : '';
$userids = '0';

if ($id !== '') {
    $allusersql = mysqli_query($con, "SELECT phone_number FROM phppos_people WHERE person_id = '$id'");
    if ($allusersql && $allusersql_result = mysqli_fetch_assoc($allusersql)) {
        $mobilenumber = mysqli_real_escape_string($con, $allusersql_result['phone_number']);
        if ($mobilenumber !== '') {
            $idsResult = mysqli_query($con, "SELECT GROUP_CONCAT(person_id) as person_ids FROM phppos_people WHERE phone_number = '$mobilenumber'");
            if ($idsResult && $idsRow = mysqli_fetch_assoc($idsResult)) {
                $userids = $idsRow['person_ids'] ?: $id;
            }
        } else {
            $userids = $id;
        }
    } else {
        $userids = $id;
    }
}

$qry = "SELECT r.*, p.first_name, p.last_name, p.phone_number,
               th.first_name as th_first_name,
               (SELECT COALESCE(SUM(od.deposit), 0) FROM order_detail od WHERE od.bill_id = r.bill_id) as total_deposit
        FROM phppos_rent r 
        LEFT JOIN phppos_people p ON r.cust_id = p.person_id
        LEFT JOIN phppos_people th ON r.throught = th.person_id
        WHERE r.cust_id IN ($userids) AND r.status = 'A'
        ORDER BY r.bill_id DESC";
$res = mysqli_query($con, $qry);
$num = $res ? mysqli_num_rows($res) : 0;
$dep = 0;
$rent = 0;
?>

<div style="overflow-x: auto; margin-top: 14px; border: 1px solid #e2e8f0; border-radius: 8px;">
    <table class="pm-table" style="width: 100%; border-collapse: collapse; font-size: 12px; background: #ffffff;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <th style="padding: 10px 14px; text-align: center; width: 45px;">#</th>
                <th style="padding: 10px 14px;">Bill / Invoice No</th>
                <th style="padding: 10px 14px;">Customer Name</th>
                <th style="padding: 10px 14px;">Pick-Up</th>
                <th style="padding: 10px 14px;">Delivery</th>
                <th style="padding: 10px 14px;">Referred By</th>
                <th style="padding: 10px 14px;">Bill Date</th>
                <th style="padding: 10px 14px; text-align: right;">Rent (₹)</th>
                <th style="padding: 10px 14px; text-align: right;">Deposit (₹)</th>
                <th style="padding: 10px 14px; text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($num === 0):
            ?>
                <tr>
                    <td colspan="10" style="padding: 30px; text-align: center; color: #94a3b8;">
                        <i class="fa fa-box-open" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                        No active unreturned rentals found for this customer.
                    </td>
                </tr>
            <?php
            else:
                $i = 1;
                while ($row = mysqli_fetch_assoc($res)):
                    $new_bill_number = !empty($row['new_bill_number']) ? $row['new_bill_number'] : $row['bill_id'];
                    $cust_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $th_name = trim((string)($row['th_first_name'] ?? ''));
                    $r_amt = floatval($row['rent_amount'] ?? 0);
                    $d_amt = floatval($row['total_deposit'] ?? 0);
                    $rent += $r_amt;
                    $dep += $d_amt;
            ?>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 10px 14px; text-align: center; color: #64748b;"><?= $i++ ?></td>
                    <td style="padding: 10px 14px; font-weight: 600; font-family: monospace;">
                        <span class="pm-bill-pill"><?= htmlspecialchars($new_bill_number) ?></span>
                    </td>
                    <td style="padding: 10px 14px; font-weight: 600; color: #0f172a;"><?= htmlspecialchars($cust_name) ?></td>
                    <td style="padding: 10px 14px; color: #64748b;"><?= htmlspecialchars($row['pick_date'] ?: '—') ?></td>
                    <td style="padding: 10px 14px; color: #64748b;"><?= htmlspecialchars($row['delivery_date'] ?: '—') ?></td>
                    <td style="padding: 10px 14px; color: #64748b;"><?= htmlspecialchars($th_name ?: '—') ?></td>
                    <td style="padding: 10px 14px; color: #64748b;"><?= ($row['bill_date'] && $row['bill_date'] !== '0000-00-00') ? date('d/m/Y', strtotime($row['bill_date'])) : '—' ?></td>
                    <td style="padding: 10px 14px; text-align: right; font-weight: 700; font-family: monospace; color: #0f172a;">₹ <?= number_format($r_amt) ?></td>
                    <td style="padding: 10px 14px; text-align: right; font-family: monospace; color: #64748b;">₹ <?= number_format($d_amt) ?></td>
                    <td style="padding: 10px 14px; text-align: center;">
                        <div style="display: flex; gap: 4px; justify-content: center;">
                            <a href="rent_detail.php?id=<?= urlencode($row['bill_id']) ?>" class="pm-btn pm-btn-primary pm-btn-sm" style="font-size: 11px; padding: 0 8px; height: 26px;">
                                <i class="fa fa-arrow-rotate-left"></i> Return
                            </a>
                            <button type="button" class="pm-btn-danger-sm" style="height: 26px; padding: 0 6px;" onclick="confirm_delete('<?= urlencode($row['bill_id']) ?>');" title="Delete">
                                <i class="fa fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
        </tbody>
        <?php if ($num > 0): ?>
        <tfoot style="background: #f8fafc; font-weight: 700; border-top: 2px solid #e2e8f0; color: #0f172a;">
            <tr>
                <td colspan="7" style="padding: 10px 14px; text-align: right;">Total Active:</td>
                <td style="padding: 10px 14px; text-align: right; font-family: monospace;">₹ <?= number_format($rent) ?></td>
                <td style="padding: 10px 14px; text-align: right; font-family: monospace;">₹ <?= number_format($dep) ?></td>
                <td></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>

<?php CloseCon($con); ?>