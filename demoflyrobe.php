<?php
include('./db_connection.php');
$con = OpenSrishringarrCon();

// Check if the connection was successful
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Query to fetch relevant records
$sql = "SELECT id, sku, purchase_id, netAmount FROM `flyrob_commission` 
        WHERE `isFlyrobeProduct` = 1 AND `status` = 'Visible'";
$result = mysqli_query($con, $sql);

if (mysqli_num_rows($result) > 0) {
    // Loop through the results
    while ($row = mysqli_fetch_assoc($result)) {
        
        $id = $row['id'];
        
        $sku = $row['sku'];
        $purchase_id = $row['purchase_id'];
        $netAmount = $row['netAmount'];

        // Calculate 43% of netAmount
        $netCommissionAmount = $netAmount * 0.43;
        $newNetAmount = $netAmount - $netCommissionAmount ;  
$newNetAmount = number_format($newNetAmount,0);
        // Update the order_detail table
        $detail_sql = "UPDATE `order_detail` 
                       SET `commission_amt` = '".$newNetAmount."' 
                       WHERE `item_id` = '$sku' AND `bill_id` = '$purchase_id'";
                       
        mysqli_query($con,$detail_sql);
        
        $fytable_update = "update flyrob_commission set commision='43%', commision_amount='".$netCommissionAmount."' where id='".$id."'" ; 
        mysqli_query($con,$fytable_update);
                       
        
        // if (mysqli_query($con, $detail_sql)) {
        //     echo "Commission updated for SKU: $sku, Purchase ID: $purchase_id<br>";
        // } else {
        //     echo "Error updating commission for SKU: $sku, Purchase ID: $purchase_id: " . mysqli_error($con) . "<br>";
        // }
    }
} else {
    echo "No records found.";
}

// Close the connection
mysqli_close($con);
?>
