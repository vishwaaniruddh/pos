<?php
// return ; 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$wp_host = "localhost";
$wp_username = "u464193275_FCSOL";
$wp_password = "caMrYFsAmF";
$wp_database = "u464193275_ib3Xh";

// Database credentials for local database (items_log)
$pos_host = "localhost";
$pos_username = "u464193275_sarmicropos";
$pos_password = "Mypos1234";
$pos_database = "u464193275_srishringarr";

// POS (phppos_items) Database Credentials
// $pos_host = "your_pos_host";
// $pos_username = "your_pos_username";
// $pos_password = "your_pos_password";
// $pos_database = "your_pos_database";




// Connect to WooCommerce database
$wp_conn = new mysqli($wp_host, $wp_username, $wp_password, $wp_database);
if ($wp_conn->connect_error) {
    die("WooCommerce Connection failed: " . $wp_conn->connect_error);
}

// Connect to POS database
$pos_conn = new mysqli($pos_host, $pos_username, $pos_password, $pos_database);
if ($pos_conn->connect_error) {
    die("POS Database Connection failed: " . $pos_conn->connect_error);
}

// Fetch all WooCommerce products with SKU
$wp_sql = "SELECT meta_value AS sku, post_id FROM wp_postmeta WHERE meta_key = '_sku' AND meta_value != ''";
$wp_result = $wp_conn->query($wp_sql);

if ($wp_result->num_rows > 0) {
    while ($wp_row = $wp_result->fetch_assoc()) {
        $sku = $wp_row['sku'];
        $product_id = $wp_row['post_id'];

        // Get latest quantity from phppos_items
        $pos_sql = "SELECT quantity FROM phppos_items WHERE name = ?";
        $pos_stmt = $pos_conn->prepare($pos_sql);
        $pos_stmt->bind_param("s", $sku);
        $pos_stmt->execute();
        $pos_result = $pos_stmt->get_result();

        if ($pos_result->num_rows > 0) {
            $pos_row = $pos_result->fetch_assoc();
            $new_qty = $pos_row['quantity'];

            // Update WooCommerce stock
            $update_sql = "UPDATE wp_postmeta SET meta_value = ? WHERE post_id = ? AND meta_key = '_stock'";
            $update_stmt = $wp_conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $new_qty, $product_id);

            if ($update_stmt->execute()) {
                echo "Stock updated for SKU: $sku | New Qty: $new_qty\n";
            } else {
                echo "Error updating stock for SKU: $sku\n";
            }

            $update_stmt->close();
        } else {
            echo "No matching SKU in phppos_items for SKU: $sku\n";
        }

        $pos_stmt->close();
    }
} else {
    echo "No products found in WooCommerce with SKUs.";
}

// Close connections
$wp_conn->close();
$pos_conn->close();
?>
