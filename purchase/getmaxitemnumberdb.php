<?php
header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}

$con = OpenSrishringarrCon();

$nm = isset($_POST["nm"]) ? trim($_POST["nm"]) : '';
$nm_safe = mysqli_real_escape_string($con, $nm);

$nrws = 0;
$res = false;
if ($nm !== '') {
    $res = mysqli_query($con, "SELECT * FROM phppos_items WHERE name='" . $nm_safe . "' AND is_deleted = 0 LIMIT 1");
    $nrws = ($res) ? mysqli_num_rows($res) : 0;
}

$itemnum = '';
$category = '';
$costprice = 0;
$unitprice = 0;
$qty = 1;

if ($nrws > 0) {
    $fr = mysqli_fetch_assoc($res);
    $itemnum = $fr["item_number"] ?? '';
    $category = $fr["category"] ?? '';
    $costprice = $fr["cost_price"] ?? 0;
    $unitprice = $fr["unit_price"] ?? 0;
    $qty = $fr["quantity"] ?? 1;
} else {
    $resf = mysqli_query($con, "SELECT item_number FROM phppos_items WHERE item_id = (SELECT MAX(item_id) FROM phppos_items)");
    if ($resf && $rowf = mysqli_fetch_row($resf)) {
        $itemnum = $rowf[0] ?? 'AAAA';
    } else {
        $itemnum = 'AAAA';
    }
}

$data = [
    "numrows" => (int)$nrws,
    "item_num" => (string)$itemnum,
    "category" => (string)$category,
    "costprice" => $costprice,
    "unitprice" => $unitprice,
    "qty" => $qty
];

CloseCon($con);
echo json_encode($data);
exit;
?>