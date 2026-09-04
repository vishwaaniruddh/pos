<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $measure_id = $_POST['measure_id'];
    $measure_name = $_POST['measure_name'];
    
    $sql = "UPDATE measurements SET measure_name = '$measure_name' WHERE measure_id = $measure_id";
    if ($con->query($sql) === TRUE) {
        echo "Measurement updated successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . $con->error;
    }
}
?>
