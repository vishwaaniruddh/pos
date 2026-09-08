<?php
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

$rem = isset($_REQUEST['rem']) ? mysqli_real_escape_string($con, trim($_REQUEST['rem'])) : '';
$id  = isset($_REQUEST['id']) ? mysqli_real_escape_string($con, trim($_REQUEST['id'])) : '';

if ($id != '') {
    $qry = mysqli_query($con, "UPDATE `bank_transaction` SET `trans_memo` = '$rem' WHERE `trans_id` = '$id'");
    if ($qry) {
        echo "1";
    } else {
        echo "0";
    }
} else {
    echo "0";
}

CloseCon($con);
?>