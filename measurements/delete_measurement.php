<?php
include_once(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

$is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['is_ajax']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $con) {
    $measure_id = isset($_POST['measure_id']) ? intval($_POST['measure_id']) : 0;
    $action     = isset($_POST['action']) ? trim($_POST['action']) : 'delete';
    
    if ($measure_id > 0) {
        $status = ($action === 'restore') ? 'Active' : 'Deleted';
        $sql = "UPDATE `measurements` SET `activityStatus` = '$status' WHERE `measure_id` = $measure_id";
        $ok = $con->query($sql);
        CloseCon($con);
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => (bool)$ok, 'status' => $status]);
            exit;
        }
        header("Location: measurementsType.php?msg=" . ($action === 'restore' ? 'restored' : 'deleted'));
        exit;
    }
}

if ($con) CloseCon($con);
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
header("Location: measurementsType.php?msg=error");
exit;
?>
