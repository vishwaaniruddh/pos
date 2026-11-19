<a href="https://srishringarr.com/pos/">Go Back</a>
<?php

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);


$wpxyz_host = "localhost";
$wpxyz_username = "u464193275_FCSOL";
$wpxyz_password = "caMrYFsAmF";
$wpxyz_database = "u464193275_ib3Xh";

// Database credentials for local database (booking_log)
$pos_host = "localhost";
$pos_username = "u464193275_sarmicropos";
$pos_password = "Mypos1234";
$pos_database = "u464193275_srishringarr";

// Connect to WooCommerce database
$wpxyz_conn = new mysqli($wpxyz_host, $wpxyz_username, $wpxyz_password, $wpxyz_database);
if ($wpxyz_conn->connect_error) {
    die("WooCommerce Connection failed: " . $wpxyz_conn->connect_error);
}

// Connect to POS database
$pos_conn = new mysqli($pos_host, $pos_username, $pos_password, $pos_database);
if ($pos_conn->connect_error) {
    die("POS Database Connection failed: " . $pos_conn->connect_error);
}

// Fetch pending bookings with future pickupdate
$sql = "SELECT DISTINCT b.sku, b.bill_id, p.quantity 
        FROM booking_log b
        JOIN phppos_items p ON p.name = b.sku
        WHERE b.operationStatus = 'pending' AND b.pickupdate >= CURDATE()";

$result = $pos_conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sku = $row['sku'];
        $bill_id = $row['bill_id'];
        $stock_quantity = $row['quantity'];
        

        $ordersql = mysqli_query($pos_conn,"select * from order_detail where bill_id='".$bill_id."' and item_id='".$sku."'");
        if($ordersql_result = mysqli_fetch_assoc($ordersql)){
            $booking_qty = $ordersql_result['qty'];
        }

        $new_quantity = $stock_quantity - $booking_qty ; 

        // Set stock to actual quantity instead of 0
        $update_sql = "UPDATE wpxyz_postmeta 
                       SET meta_value = ? 
                       WHERE post_id = (SELECT post_id FROM wpxyz_postmeta WHERE meta_key = '_sku' AND meta_value = ?) 
                       AND meta_key = '_stock'";
        $update_stmt = $wpxyz_conn->prepare($update_sql);
        $update_stmt->bind_param("is", $new_quantity, $sku);
        
        if ($update_stmt->execute()) {
            echo "Stock updated to $new_quantity for SKU: $sku\n";

            // Update booking_log status to 'complete'
            $update_booking_sql = "UPDATE booking_log SET operationStatus = 'booked' WHERE bill_id = ? AND sku = ?";
            $update_booking_stmt = $pos_conn->prepare($update_booking_sql);
            $update_booking_stmt->bind_param("is", $bill_id, $sku);
            $update_booking_stmt->execute();
            $update_booking_stmt->close();
        } else {
            echo "Error updating stock for SKU: $sku\n";
        }
        $update_stmt->close();
    }
}

// Restore stock for completed bookings
$restore_sql = "SELECT DISTINCT b.sku, b.bill_id, p.quantity 
                FROM booking_log b
                JOIN phppos_items p ON p.name = b.sku
                WHERE b.operationStatus = 'Returned'";
$restore_result = $pos_conn->query($restore_sql);

if ($restore_result->num_rows > 0) {
    while ($restore_row = $restore_result->fetch_assoc()) {
        $sku = $restore_row['sku'];
        $bill_id = $restore_row['bill_id'];
        $stock_quantity = $restore_row['quantity'];



        $ordersql = mysqli_query($pos_conn,"select * from order_detail where bill_id='".$bill_id."' and item_id='".$sku."'");
        if($ordersql_result = mysqli_fetch_assoc($ordersql)){
            $booking_qty = $ordersql_result['qty'];
        }

$new_quantity = $stock_quantity - $booking_qty ; 


        // Restore WooCommerce stock to original quantity
        $restore_wpxyz_sql = "UPDATE wpxyz_postmeta 
                           SET meta_value = ? 
                           WHERE post_id = (SELECT post_id FROM wpxyz_postmeta WHERE meta_key = '_sku' AND meta_value = ?) 
                           AND meta_key = '_stock'";
        $restore_wpxyz_stmt = $wpxyz_conn->prepare($restore_wpxyz_sql);
        $restore_wpxyz_stmt->bind_param("is", $new_quantity, $sku);
        
        if ($restore_wpxyz_stmt->execute()) {
            echo "Stock restored to $new_quantity for SKU: $sku";
echo '<br />';
            // Update booking_log status to 'complete'
            $update_booking_sql = "UPDATE booking_log SET operationStatus = 'complete' WHERE bill_id = ? AND sku = ?";
            $update_booking_stmt = $pos_conn->prepare($update_booking_sql);
            $update_booking_stmt->bind_param("is", $bill_id, $sku);
            $update_booking_stmt->execute();
            $update_booking_stmt->close();
        } else {
            echo "Error restoring stock for SKU: $sku\n";
        }

        $restore_wpxyz_stmt->close();
    }
}

echo '<br />';
echo 'Operation Completed ! ' ; 
echo '<br />';



// Close connections
$wpxyz_conn->close();
$pos_conn->close();
?>


<a href="https://srishringarr.com/pos/">Go Back</a>