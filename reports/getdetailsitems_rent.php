<?php
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} elseif (file_exists('../db_connection.php')) {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

$item_id = trim((string)($_GET['item_id'] ?? ''));
$frmdate = trim((string)($_GET['frmdate'] ?? ''));
$todate  = trim((string)($_GET['todate'] ?? ''));
$status  = trim((string)($_GET['status'] ?? ''));

$frmdate = str_replace('/', '-', $frmdate);
$todate  = str_replace('/', '-', $todate);
$from_sql = ($frmdate !== '') ? date('Y-m-d', strtotime($frmdate)) : '';
$to_sql   = ($todate !== '') ? date('Y-m-d', strtotime($todate)) : '';

$where = ["od.item_id = '" . mysqli_real_escape_string($con, $item_id) . "'"];
if ($from_sql !== '' && $to_sql !== '') {
    $where[] = "r.bill_date BETWEEN '$from_sql' AND '$to_sql'";
} elseif ($from_sql !== '') {
    $where[] = "r.bill_date >= '$from_sql'";
} elseif ($to_sql !== '') {
    $where[] = "r.bill_date <= '$to_sql'";
}

if ($status !== '' && $status !== 'all' && $status !== 'a' && $status !== 's') {
    $where[] = "r.booking_status = '" . mysqli_real_escape_string($con, $status) . "'";
}

$sql = "
    SELECT 
        od.bill_id,
        od.qty,
        od.rent,
        od.deposit,
        od.pickup_date,
        od.return_date,
        r.new_bill_number,
        r.bill_date,
        r.booking_status,
        cust.first_name,
        cust.last_name,
        cust.phone_number
    FROM order_detail od
    JOIN phppos_rent r ON od.bill_id = r.bill_id
    LEFT JOIN phppos_people cust ON r.cust_id = cust.person_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY r.bill_date DESC, od.bill_id DESC
";

$res = mysqli_query($con, $sql);
?>
<table width="100%" border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-family: sans-serif; font-size: 13px;">
    <tr style="background: #0f172a; color: #fff;">
        <th colspan="7" align="center" style="padding: 10px;">Rental Ledger for SKU: <?= htmlspecialchars($item_id) ?></th>
    </tr>
    <tr style="background: #f1f5f9;">
        <th style="width: 40px;">#</th>
        <th>Bill No</th>
        <th>Bill Date</th>
        <th>Customer Details</th>
        <th align="center">Status</th>
        <th align="center">Qty</th>
        <th align="right">Rent (₹)</th>
    </tr>
    <?php
    $i = 0;
    $total_qty  = 0;
    $total_rent = 0;
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $i++;
            $b_no = !empty($row['new_bill_number']) ? $row['new_bill_number'] : ('BILL-' . $row['bill_id']);
            $c_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: 'Guest';
            $q = intval($row['qty'] ?? 1);
            $r = floatval($row['rent'] ?? 0);
            $total_qty  += $q;
            $total_rent += $r;
            ?>
            <tr>
                <td align="center"><?= $i ?></td>
                <td><strong><?= htmlspecialchars($b_no) ?></strong></td>
                <td><?= $row['bill_date'] ? date('d/m/Y', strtotime($row['bill_date'])) : '—' ?></td>
                <td><?= htmlspecialchars($c_name) ?> (<?= htmlspecialchars($row['phone_number'] ?? '—') ?>)</td>
                <td align="center"><?= htmlspecialchars($row['booking_status'] ?? 'Returned') ?></td>
                <td align="center"><?= $q ?></td>
                <td align="right">₹ <?= number_format($r) ?></td>
            </tr>
            <?php
        }
    } else {
        echo "<tr><td colspan='7' align='center' style='padding: 20px; color: #64748b;'>No rental bookings recorded for SKU: " . htmlspecialchars($item_id) . "</td></tr>";
    }
    ?>
    <tr style="background: #f1f5f9; font-weight: bold;">
        <td colspan="5" align="right">Total:</td>
        <td align="center"><?= number_format($total_qty) ?></td>
        <td align="right">₹ <?= number_format($total_rent) ?></td>
    </tr>
</table>
<?php
CloseCon($con);
?>