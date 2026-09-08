<?php
session_start();
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

$id = isset($_REQUEST['id']) ? mysqli_real_escape_string($con, trim($_REQUEST['id'])) : '';
$is_success = false;

if ($id != '') {
    $delqry = mysqli_query($con, "DELETE FROM `phppos_suppliers` WHERE `person_id` = '$id'");
    if ($delqry) {
        $is_success = true;
        $_SESSION['success'] = 1;
    } else {
        $is_success = false;
        $_SESSION['success'] = 0;
    }
}

CloseCon($con);

// Return JSON if AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['status' => $is_success ? 'success' : 'error']);
    exit;
}

header('location:view_supplier.php');
exit;
?>
