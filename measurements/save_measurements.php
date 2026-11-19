<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

$product_id = $_REQUEST['product_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve POST data
    $measure_names = $_POST['measure_name'];
    $measure_values = $_POST['measure_value'];
    
    $invalid_rows = [];
    $valid_data = [];



    // Validate and prepare data
    foreach ($measure_names as $index => $measure_name) {
        $measure_value = $measure_values[$index];

        // Escape input data
        $measure_name = $con->real_escape_string($measure_name);
        $measure_value = $con->real_escape_string($measure_value);

        // Get measure_id from measure_name
        $measure_id_query = "SELECT measure_id FROM measurements WHERE measure_name = '$measure_name' AND activityStatus = 'Active'";
        $measure_id_result = $con->query($measure_id_query);

        if ($measure_id_result && $measure_id_row = $measure_id_result->fetch_assoc()) {
            $measure_id = $measure_id_row['measure_id'];
            $valid_data[] = "('$product_id', '$measure_id', '$measure_value')";
        } else {
            $invalid_rows[] = $index;
        }
    }
    
    // Insert valid data
    if (!empty($valid_data)) {
        
        mysqli_query($con,"delete from product_measurements where product_id='".$product_id."'");
        
        $values = implode(',', $valid_data);
        $sql = "INSERT INTO product_measurements (product_id, measure_id, measure_value) VALUES $values";
        if (!$con->query($sql)) {
            echo "Error: " . $con->error;
            exit;
        }
    }

    // Return invalid rows for highlighting
    echo json_encode($invalid_rows);
}
?>
