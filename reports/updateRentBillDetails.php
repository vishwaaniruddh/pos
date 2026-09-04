<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collecting posted data
    $bill_id = $_REQUEST['bill_id'];
    $bill_date = $_POST['bill_date'];
    $pick_date = $_POST['pick_date'];
    $delivery_date = $_POST['delivery_date'];
    $measurement = $_POST['measurement'];
    $measurement_note = $_POST['measurement_note'];
    $delivery = $_POST['delivery'];

    // Prepare the SQL query for updating the main bill details
    $update_query = "
        UPDATE phppos_rent 
        SET bill_date = ?, pick_date = ?, delivery_date = ?, measurement = ?, measurement_note = ?, delivery = ?
        WHERE bill_id = ?
    ";

    // Prepare statement
    if ($stmt = mysqli_prepare($con, $update_query)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "ssssssi", $bill_date, $pick_date, $delivery_date, $measurement, $measurement_note, $delivery, $bill_id);
        
        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            // Update item details
            $item_ids = $_POST['detail_id'];
            $item_details = $_POST['item_detail'];

            foreach ($item_ids as $index => $id) {
                $item_detail = $item_details[$index];
                $update_item_query = "UPDATE order_detail SET item_detail = ? WHERE id = ?";

                if ($item_stmt = mysqli_prepare($con, $update_item_query)) {
                    mysqli_stmt_bind_param($item_stmt, "si", $item_detail, $id);
                    mysqli_stmt_execute($item_stmt);
                    mysqli_stmt_close($item_stmt);
                }
            }

            // Redirect back to the page displaying the rent bill details with success message
            header("Location: ./editrentbillDetails.php?bill_id=" . urlencode($bill_id) . "&status=success");
            exit;
        } else {
            // Redirect back with error message
            header("Location: ./editrentbillDetails.php?bill_id=" . urlencode($bill_id) . "&status=error");
            exit;
        }
        // Close statement
        mysqli_stmt_close($stmt);
    } else {
        // Redirect back with error message
        header("Location: ./editrentbillDetails.php?bill_id=" . urlencode($bill_id) . "&status=error");
        exit;
    }

    // Close connection
    mysqli_close($con);
} else {
    echo "Invalid request method.";
}
?>
