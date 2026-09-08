<?php
include_once('db_connection.php');
$con = OpenSrishringarrCon();

date_default_timezone_set('Asia/Kolkata');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$deleted_at = date("Y-m-d H:i:s");

if ($con && $id > 0) {
    $sql = mysqli_query($con, "UPDATE phppos_items SET deleted_at = '$deleted_at', is_deleted = 1 WHERE item_id = '$id'");
    CloseCon($con);
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    header("Location: itemcode_details.php?msg=deleted");
    exit;
}

if ($con) CloseCon($con);
header("Location: itemcode_details.php?msg=error");
exit;
?>