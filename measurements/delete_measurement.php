<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $measure_id = $_POST['measure_id'];
    
    $sql = "UPDATE measurements SET activityStatus = 'Deleted' WHERE measure_id = $measure_id";
    if ($con->query($sql) === TRUE) {
        echo "Measurement marked as deleted successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . $con->error;
    }
}
?>
