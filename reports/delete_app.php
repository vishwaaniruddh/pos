<?php
ini_set("display_errors", 0);

include(__DIR__ . '/../db_connection.php');
require_once __DIR__ . '/../includes/yn_stock_sync.php';
$con = OpenSrishringarrCon();

delete_legacy_pos_approval_bill($con, (int)($_GET['id'] ?? 0), 'Deleted via delete_app.php');

header('location:../../../application/views/reports/app_return.php');
CloseCon($con);
?>
