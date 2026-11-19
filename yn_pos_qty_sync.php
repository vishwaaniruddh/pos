<?php
// Database credentials for WooCommerce
$wpxyz_host = "localhost";
$wpxyz_username = "u464193275_FCSOL";
$wpxyz_password = "caMrYFsAmF";
$wpxyz_database = "u464193275_ib3Xh";

// Database credentials for local database (items_log)
$local_host = "localhost";
$local_username = "u464193275_sarmicropos";
$local_password = "Mypos1234";
$local_database = "u464193275_srishringarr";



// Connect to WooCommerce database
$wpxyz_conn = new mysqli($wpxyz_host, $wpxyz_username, $wpxyz_password, $wpxyz_database);
if ($wpxyz_conn->connect_error) {
    die("WooCommerce Connection failed: " . $wpxyz_conn->connect_error);
}

// Connect to local database
$local_conn = new mysqli($local_host, $local_username, $local_password, $local_database);
if ($local_conn->connect_error) {
    die("Local Database Connection failed: " . $local_conn->connect_error);
}

// Fetch pending records from items_log
$sql = "SELECT id, sku, qty FROM items_log WHERE status = 'pending'";
$result = $local_conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $sku = $row['sku'];
        $new_qty = $row['qty'];
        
        // Get product ID based on SKU from WooCommerce database
        $wpxyz_sql = "SELECT ID FROM wpxyz_posts WHERE ID = (SELECT post_id FROM wpxyz_postmeta WHERE meta_key = '_sku' AND meta_value = ? LIMIT 1)";
        $wpxyz_stmt = $wpxyz_conn->prepare($wpxyz_sql);
        $wpxyz_stmt->bind_param("s", $sku);
        $wpxyz_stmt->execute();
        $wpxyz_result = $wpxyz_stmt->get_result();

        if ($wpxyz_result->num_rows > 0) {
            $wpxyz_row = $wpxyz_result->fetch_assoc();
            $product_id = $wpxyz_row['ID'];
            
            // Update stock quantity in WooCommerce database
            $update_sql = "UPDATE wpxyz_postmeta SET meta_value = ? WHERE post_id = ? AND meta_key = '_stock'";
            $update_stmt = $wpxyz_conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $new_qty, $product_id);
            
            if ($update_stmt->execute()) {
                echo "Stock updated for SKU: $sku";
                echo '<br />';
                // Update status to 'complete' in local database
                $local_update_sql = "UPDATE items_log SET status = 'complete' WHERE id = ?";
                $local_update_stmt = $local_conn->prepare($local_update_sql);
                $local_update_stmt->bind_param("i", $id);
                $local_update_stmt->execute();
                $local_update_stmt->close();
            } else {
                echo "Error updating stock for SKU: $sku\n";
            }
            
            $update_stmt->close();
        } else {
            echo "Not found in Yosshitaneha Website with SKU: $sku";
            
            $local_update_sql = "UPDATE items_log SET status = 'Not Found' WHERE id = ?";
                $local_update_stmt = $local_conn->prepare($local_update_sql);
                $local_update_stmt->bind_param("i", $id);
                $local_update_stmt->execute();
                $local_update_stmt->close();
                
                
            echo '<br />';
        }
        
        $wpxyz_stmt->close();
    }
} else {
    echo "No pending updates found in items_log.";
}

// Close connections
$wpxyz_conn->close();
$local_conn->close();
?>
