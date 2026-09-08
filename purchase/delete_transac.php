<?php
session_start();
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

$trans_id = isset($_REQUEST['trans_id']) ? mysqli_real_escape_string($con, trim($_REQUEST['trans_id'])) : '';
$is_success = false;

if ($trans_id != '') {
    mysqli_query($con, "BEGIN");
    $qry = mysqli_query($con, "INSERT INTO `bank_transaction_hist`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`, `reconcile_date`, `enrty_date`, `delete_date`) SELECT `trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`, `reconcile_date`, `enrty_date`,'" . date('Y-m-d H:i:s') . "' FROM `bank_transaction` WHERE `trans_id`='$trans_id'");
    $qry1 = mysqli_query($con, "DELETE FROM `bank_transaction` WHERE `trans_id`='$trans_id'");

    if ($qry && $qry1) {
        mysqli_query($con, "COMMIT");
        $_SESSION['success'] = 1;
        $is_success = true;
    } else {
        mysqli_query($con, "ROLLBACK");
        $_SESSION['success'] = 0;
        $is_success = false;
    }
}

CloseCon($con);

// Check if request was made via AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['status' => $is_success ? 'success' : 'error']);
    exit;
}

header('location:bank_report.php');
?>