<?php
include_once(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

$cid = isset($_POST['id']) ? mysqli_real_escape_string($con, trim($_POST['id'])) : '';
$amt = isset($_POST['amt']) ? floatval($_POST['amt']) : 0;

if ($cid !== '') {
    $sql = "UPDATE `scheme` SET status = 'S', paid_amount = paid_amount + $amt WHERE bill_id = '$cid'";
    mysqli_query($con, $sql);
}

CloseCon($con);
header('Location: rent_return1.php');
exit;
?>