<?php
include_once(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

$is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['is_ajax']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $con) {
    $measure_id   = isset($_POST['measure_id']) ? intval($_POST['measure_id']) : 0;
    $measure_name = isset($_POST['measure_name']) ? trim($_POST['measure_name']) : '';
    
    if ($measure_id > 0 && $measure_name !== '') {
        $name_safe = $con->real_escape_string($measure_name);
        $sql = "UPDATE `measurements` SET `measure_name` = '$name_safe' WHERE `measure_id` = $measure_id";
        $ok = $con->query($sql);
        CloseCon($con);
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => (bool)$ok, 'measure_name' => $measure_name]);
            exit;
        }
        header("Location: measurementsType.php?msg=updated");
        exit;
    }
}

if ($con) CloseCon($con);
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}
header("Location: measurementsType.php?msg=error");
exit;
?>
