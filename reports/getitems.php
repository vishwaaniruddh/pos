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

$where = ["a.status = 's'", "ad.qty > 0", "i.category = '" . mysqli_real_escape_string($con, $cat) . "'"];
if ($frmdate !== '' && $todate !== '') {
    $where[] = "(a.bill_date BETWEEN STR_TO_DATE('" . mysqli_real_escape_string($con, $frmdate) . "', '%d/%m/%Y') AND STR_TO_DATE('" . mysqli_real_escape_string($con, $todate) . "', '%d/%m/%Y') OR a.bill_date BETWEEN '" . mysqli_real_escape_string($con, $frmdate) . "' AND '" . mysqli_real_escape_string($con, $todate) . "')";
}

$sql = "
    SELECT 
        ad.item_id,
        COALESCE(SUM(ad.qty - ad.return_qty), 0) as total_sold_qty,
        COALESCE(SUM(ad.amount * (ad.qty - ad.return_qty) / ad.qty), 0) as total_sales_amt
    FROM approval a
    JOIN approval_detail ad ON a.bill_id = ad.bill_id
    JOIN phppos_items i ON ad.item_id = i.name
    WHERE " . implode(' AND ', $where) . "
    GROUP BY ad.item_id
    HAVING total_sold_qty > 0
    ORDER BY total_sales_amt DESC
";

$res = mysqli_query($con, $sql);
?>
<table align="center" width="90%" border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-family: sans-serif; font-size: 13px;">
    <tr style="background: #0f172a; color: #fff;">
        <th colspan="5" align="center" style="padding: 10px;">Sales Category: <?= htmlspecialchars($cat) ?></th>
    </tr>
    <tr style="background: #f1f5f9;">
        <th style="width: 40px;">#</th>
        <th>Item SKU</th>
        <th align="right">Sold Qty</th>
        <th align="right">Sales Amount (₹)</th>
        <th align="center">Option</th>
    </tr>
    <?php
    $i = 0;
    $tot_qty = 0;
    $tot_amt = 0;
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $i++;
            $q = intval($row['total_sold_qty']);
            $a = floatval($row['total_sales_amt']);
            $tot_qty += $q;
            $tot_amt += $a;
            ?>
            <tr>
                <td align="center"><?= $i ?></td>
                <td align="center"><strong><?= htmlspecialchars($row['item_id']) ?></strong></td>
                <td align="right"><?= number_format($q) ?></td>
                <td align="right">₹ <?= number_format($a) ?></td>
                <td align="center">
                    <input type="button" value="View Details" onclick="if(typeof openItemSalesHistory==='function'){openItemSalesHistory('<?= htmlspecialchars(addslashes($row['item_id'])) ?>');}else{popup('<?= htmlspecialchars(addslashes($row['item_id'])) ?>','<?= htmlspecialchars(addslashes($frmdate)) ?>','<?= htmlspecialchars(addslashes($todate)) ?>');}">
                </td>
            </tr>
            <?php
        }
    } else {
        echo "<tr><td colspan='5' align='center' style='padding: 20px; color: #64748b;'>No sales records found for category: " . htmlspecialchars($cat) . "</td></tr>";
    }
    ?>
    <tr style="background: #f1f5f9; font-weight: bold;">
        <td colspan="2" align="right">Total:</td>
        <td align="right"><?= number_format($tot_qty) ?></td>
        <td align="right">₹ <?= number_format($tot_amt) ?></td>
        <td></td>
    </tr>
</table>
<?php
CloseCon($con);
?>