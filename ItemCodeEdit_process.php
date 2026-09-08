<?php 
include_once('db_connection.php');
$con = OpenSrishringarrCon();

$itemid = isset($_POST["itemid"]) ? intval($_POST["itemid"]) : 0;
$quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
$_category = isset($_POST['category']) ? trim($_POST['category']) : '';
$category = ucfirst($_category);

date_default_timezone_set('Asia/Kolkata');
$updated_at = date('Y-m-d H:i:s');

$is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['is_ajax']);

if ($con && $itemid > 0) {
    $category_safe = mysqli_real_escape_string($con, $category);
    $update = "UPDATE `phppos_items` 
               SET `quantity` = '$quantity', `updated_at` = '$updated_at', `category` = '$category_safe' 
               WHERE `item_id` = '$itemid'";
    $updqry = mysqli_query($con, $update);
    CloseCon($con);

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => (bool)$updqry, 'item_id' => $itemid, 'quantity' => $quantity, 'category' => $category]);
        exit;
    }

    if ($updqry) {
        header('Location: itemcode_details.php?msg=updated');
    } else {
        header('Location: itemcode_details.php?msg=error');
    }
    exit;
}

if ($con) CloseCon($con);
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}
header('Location: itemcode_details.php?msg=error');
exit;
?>
