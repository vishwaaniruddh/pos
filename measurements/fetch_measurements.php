<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

$product_id = $_REQUEST['product_id'];

// Query to fetch measurement details for the given product_id
$sql = "
    SELECT m.measure_name, pm.measure_value
    FROM product_measurements pm
    JOIN measurements m ON pm.measure_id = m.measure_id
    WHERE pm.product_id = '$product_id' AND m.activityStatus = 'Active'
";

$result = $con->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
