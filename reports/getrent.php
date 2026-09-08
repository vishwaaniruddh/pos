<?php
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} elseif (file_exists('../db_connection.php')) {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

$frmdate = trim((string)($_GET['frmdate'] ?? ''));
$todate  = trim((string)($_GET['todate'] ?? ''));
$invno   = trim((string)($_GET['invno'] ?? ''));

$frmdate = str_replace('/', '-', $frmdate);
$todate  = str_replace('/', '-', $todate);
$from_sql = ($frmdate !== '') ? date('Y-m-d', strtotime($frmdate)) : '';
$to_sql   = ($todate !== '') ? date('Y-m-d', strtotime($todate)) : '';

$where = ["r.cust_id != ''", "r.cust_id IS NOT NULL", "r.cust_id != '0'"];
if ($invno !== '') {
    $inv_esc = mysqli_real_escape_string($con, $invno);
    $where[] = "(r.bill_id = '$inv_esc' OR r.new_bill_number LIKE '%$inv_esc%')";
}
if ($from_sql !== '' && $to_sql !== '') {
    $where[] = "r.bill_date BETWEEN '$from_sql' AND '$to_sql'";
} elseif ($from_sql !== '') {
    $where[] = "r.bill_date >= '$from_sql'";
} elseif ($to_sql !== '') {
    $where[] = "r.bill_date <= '$to_sql'";
}

$sql = "
    SELECT 
        p.person_id,
        p.first_name,
        p.last_name,
        p.phone_number,
        COUNT(DISTINCT r.bill_id) as total_bookings,
        COALESCE(SUM(r.rent_amount), 0) as total_rent_amount,
        COALESCE(SUM(r.amount), 0) as total_net_amount,
        COALESCE(SUM(r.bal_amount), 0) as total_balance,
        MAX(r.bill_date) as last_rental_date
    FROM phppos_rent r
    JOIN phppos_people p ON r.cust_id = p.person_id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY p.person_id
    ORDER BY total_rent_amount DESC
    LIMIT 200
";

$res = mysqli_query($con, $sql);
?>
<table border="1" id="tbl" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse; font-family: sans-serif; font-size: 13px;">
    <tr style="background: #0f172a; color: #fff;">
        <th colspan="7" align="center" style="padding: 10px;">Rent Report (Customer Wise)</th>
    </tr>
    <tr style="background: #f1f5f9;">
        <th style="width: 40px;">#</th>
        <th>Customer Name</th>
        <th>Contact No.</th>
        <th align="center">Bookings</th>
        <th align="right">Total Rent (₹)</th>
        <th align="right">Balance (₹)</th>
        <th align="center">Option</th>
    </tr>
    <?php
    $i = 0;
    $total_rent = 0;
    $total_bal  = 0;
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $i++;
            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $r_amt = floatval($row['total_rent_amount'] ?? 0);
            $b_amt = floatval($row['total_balance'] ?? 0);
            $total_rent += $r_amt;
            $total_bal  += $b_amt;
            ?>
            <tr>
                <td align="center"><?= $i ?></td>
                <td><strong><?= htmlspecialchars($name) ?></strong></td>
                <td><?= htmlspecialchars($row['phone_number'] ?? '—') ?></td>
                <td align="center"><?= intval($row['total_bookings'] ?? 0) ?></td>
                <td align="right">₹ <?= number_format($r_amt) ?></td>
                <td align="right" style="<?= $b_amt > 0 ? 'color: #b91c1c; font-weight: bold;' : '' ?>">₹ <?= number_format($b_amt) ?></td>
                <td align="center">
                    <input type="button" value="View Ledger" onclick="if(typeof openCustomerLedger==='function'){openCustomerLedger('<?= htmlspecialchars(addslashes($row['person_id'])) ?>');}else{popup('<?= htmlspecialchars(addslashes($row['person_id'])) ?>');}">
                </td>
            </tr>
            <?php
        }
    } else {
        echo "<tr><td colspan='7' align='center' style='padding: 24px; color: #64748b;'>No customer rental records match the query.</td></tr>";
    }
    ?>
    <tr style="background: #f1f5f9; font-weight: bold;">
        <td colspan="4" align="right">Total Summary:</td>
        <td align="right">₹ <?= number_format($total_rent) ?></td>
        <td align="right">₹ <?= number_format($total_bal) ?></td>
        <td></td>
    </tr>
</table>
<?php
CloseCon($con);
?>