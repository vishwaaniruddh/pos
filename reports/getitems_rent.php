<?php
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} elseif (file_exists('../db_connection.php')) {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

$cat     = trim((string)($_GET['cate'] ?? ''));
$frmdate = trim((string)($_GET['frmdate'] ?? ''));
$todate  = trim((string)($_GET['todate'] ?? ''));

$where = ["i.category = '" . mysqli_real_escape_string($con, $cat) . "'"];
if ($frmdate !== '' && $todate !== '') {
    $where[] = "(r.bill_date BETWEEN STR_TO_DATE('" . mysqli_real_escape_string($con, $frmdate) . "', '%d/%m/%Y') AND STR_TO_DATE('" . mysqli_real_escape_string($con, $todate) . "', '%d/%m/%Y') OR r.bill_date BETWEEN '" . mysqli_real_escape_string($con, $frmdate) . "' AND '" . mysqli_real_escape_string($con, $todate) . "')";
}

$sql = "
    SELECT 
        od.item_id,
        COALESCE(SUM(od.qty), 0) as total_qty,
        COALESCE(SUM(od.rent), 0) as total_rent,
        COALESCE(SUM(od.deposit), 0) as total_deposit
    FROM order_detail od
    JOIN phppos_rent r ON od.bill_id = r.bill_id
    JOIN phppos_items i ON od.item_id = i.name
    WHERE " . implode(' AND ', $where) . "
    GROUP BY od.item_id
    ORDER BY total_rent DESC
";

$res = mysqli_query($con, $sql);
?>
<table align="center" width="90%" border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-family: sans-serif; font-size: 13px;">
    <tr style="background: #0f172a; color: #fff;">
        <th colspan="6" align="center" style="padding: 10px;">Category: <?= htmlspecialchars($cat) ?></th>
    </tr>
    <tr style="background: #f1f5f9;">
        <th>Sr No</th>
        <th>Item SKU</th>
        <th align="right">Rented Qty</th>
        <th align="right">Total Rent (₹)</th>
        <th align="right">Deposit (₹)</th>
        <th align="center">Option</th>
    </tr>
    <?php
    $i = 0;
    $tot_qty = 0;
    $tot_rent = 0;
    $tot_dep = 0;
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $i++;
            $tot_qty += intval($row['total_qty']);
            $tot_rent += floatval($row['total_rent']);
            $tot_dep += floatval($row['total_deposit']);
            ?>
            <tr>
                <td align="center"><?= $i ?></td>
                <td align="center"><strong><?= htmlspecialchars($row['item_id']) ?></strong></td>
                <td align="right"><?= number_format($row['total_qty']) ?></td>
                <td align="right">₹ <?= number_format($row['total_rent']) ?></td>
                <td align="right">₹ <?= number_format($row['total_deposit']) ?></td>
                <td align="center">
                    <input type="button" value="View Details" onclick="if(typeof openItemRentalHistory==='function'){openItemRentalHistory('<?= htmlspecialchars(addslashes($row['item_id'])) ?>');}else{popup('<?= htmlspecialchars(addslashes($row['item_id'])) ?>','<?= htmlspecialchars(addslashes($frmdate)) ?>','<?= htmlspecialchars(addslashes($todate)) ?>');}">
                </td>
            </tr>
            <?php
        }
    } else {
        echo "<tr><td colspan='6' align='center' style='padding: 20px; color: #64748b;'>No rental records found for category: " . htmlspecialchars($cat) . "</td></tr>";
    }
    ?>
    <tr style="background: #f1f5f9; font-weight: bold;">
        <td colspan="2" align="right">Total:</td>
        <td align="right"><?= number_format($tot_qty) ?></td>
        <td align="right">₹ <?= number_format($tot_rent) ?></td>
        <td align="right">₹ <?= number_format($tot_dep) ?></td>
        <td></td>
    </tr>
</table>
<?php
CloseCon($con);
?>