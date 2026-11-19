<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_REQUEST['bill_id'])) {
    $bill_id = mysqli_real_escape_string($con, $_REQUEST['bill_id']);

    // Start a transaction
    mysqli_begin_transaction($con);

    try {
        // Deletion queries
        $deleteQueries = [
            "DELETE FROM phppos_rent WHERE bill_id = '$bill_id'",
            "DELETE FROM rent_amount WHERE bill_id = '$bill_id'",
            "DELETE FROM bank_transaction WHERE bill_id = '$bill_id'",
            "DELETE FROM order_detail WHERE bill_id = '$bill_id'",
            "DELETE FROM flyrob_commission WHERE purchase_id = '$bill_id'"
        ];

        // Execute each query
        foreach ($deleteQueries as $query) {
            if (!mysqli_query($con, $query)) {
                throw new Exception("Error executing query: $query");
            }
        }

        // Commit the transaction if all queries are successful
        mysqli_commit($con);
        
        // Send success response with HTTP 200
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Bill deleted successfully."]);
    } catch (Exception $e) {
        // Rollback the transaction on error
        mysqli_rollback($con);

        // Send error response with HTTP 500
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    // Send invalid request response with HTTP 400
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
}

// Close the connection
mysqli_close($con);
?>
