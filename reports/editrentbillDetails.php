<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

$bill_id = $_REQUEST['bill_id'];
$status = isset($_GET['status']) ? $_GET['status'] : '';

$query = "
    SELECT r.bill_id, r.cust_id, r.bill_date, r.pick_date, r.delivery_date, r.measurement, r.measurement_note,r.delivery,
    p.first_name, p.last_name
    FROM phppos_rent r
    JOIN phppos_people p ON r.cust_id = p.person_id
    WHERE bill_id = '".$bill_id."'
";

$sql = mysqli_query($con, $query);
$sql_result = mysqli_fetch_assoc($sql);

$bill_date = $sql_result['bill_date'];
$pick_date = $sql_result['pick_date'];
$delivery_date = $sql_result['delivery_date'];
$measurement = $sql_result['measurement'];
$measurement_note = $sql_result['measurement_note'];

$first_name = $sql_result['first_name'];
$last_name =  $sql_result['last_name'];
$delivery = $sql_result['delivery'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Rent Bill Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #eaeaea;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 15px;
        }
        .grid {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .section {
            flex: 1;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 6px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 8px; /* Reduced padding */
            text-align: left;
            border: 1px solid #ccc;
        }
        th {
            background-color: #4a90e2; /* Changed to a more muted blue */
            color: white;
        }
        input[type="date"],
        input[type="text"],
        select {
            width: 100%;
            padding: 6px; /* Reduced padding */
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #4a90e2; /* Changed button color */
            color: white;
            padding: 8px; /* Reduced padding */
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
            margin-top: 10px; /* Adjusted margin */
        }
        input[type="submit"]:hover {
            background-color: #357ab8; /* Darker shade on hover */
        }
         .status-message {
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            animation: fade-in 0.5s;
        }
        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>

<div class="container">
     <?php if ($status == 'success'): ?>
        <div class="status-message" style="color: green;">Rent bill details updated successfully!</div>
    <?php elseif ($status == 'error'): ?>
        <div class="status-message" style="color: red;">There was an error updating the rent bill details.</div>
    <?php endif; ?>
    
    
    <form method="POST" action="./updateRentBillDetails.php">
        <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>" />
        <h2>Invoice Number: <?php echo $bill_id; ?></h2>
        
        <!-- Grid Layout for Bill and Item Details -->
        <div class="grid">
            <!-- Bill Details Section -->
            <div class="section">
                <h3>Bill Details</h3>
                <b>Name: </b> <?php echo $first_name . ' ' . $last_name; ?>
                <table>
                    <tr>
                        <th>Bill Date</th>
                        <td><input type="date" name="bill_date" value="<?php echo $bill_date; ?>" /></td>
                    </tr>
                    <tr>
                        <th>Pick Date</th>
                        <td><input type="date" name="pick_date" value="<?php echo $pick_date; ?>" /></td>
                    </tr>
                    <tr>
                        <th>Delivery Date</th>
                        <td><input type="date" name="delivery_date" value="<?php echo $delivery_date; ?>" /></td>
                    </tr>
                    <tr>
                        <th>Measurement</th>
                        <td>
                            <select id="measurement" name="measurement">
                                <option value="">Select</option>
                                <option value="yes" <?php if($measurement=='yes'){ echo 'selected'; } ?>>Yes</option>
                                <option value="no" <?php if($measurement=='no'){ echo 'selected'; } ?>>No</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Measurement Note</th>
                        <td><input type="text" name="measurement_note" value="<?php echo $measurement_note; ?>" /></td>
                    </tr>
                 
                 <tr>
                     <th>Delivery</th>
                     <td>
                    <select id="delivery" name="delivery">
                        <option value="">Select </option>
                        <option value="yes" <?php if($delivery=='yes'){ echo 'selected' ;} ?>>yes</option>
                        <option value="no" <?php if($delivery=='no'){ echo 'selected' ;} ?>>no</option>
                    </select>     
                     </td>
                 </tr>   
                    
                </table>
            </div>

            <!-- Item Details Section -->
            <div class="section">
                <h3>Item Details</h3>
                <table>
                    <?php 
                    $detailssql = mysqli_query($con, "SELECT * FROM order_detail WHERE bill_id='".$bill_id."'");
                    while ($detailssql_result = mysqli_fetch_assoc($detailssql)) {
                        $id = $detailssql_result['id'];
                        $sku = $detailssql_result['item_id']; // this is sku
                        $item_detail = $detailssql_result['item_detail'];
                    ?>
                        <tr>
                            <input type="hidden" name="detail_id[]" value="<?php echo $id; ?>" />
                            <th><?php echo $sku; ?></th>
                            <td><input type="text" name="item_detail[]" value="<?php echo $item_detail; ?>" /></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
        
        <input type="submit" name="submit" value="Update Bill Details" />
    </form>
</div>



<script>
    // Wait for the document to fully load
    document.addEventListener("DOMContentLoaded", function() {
        // Select the status message
        const message = document.querySelector('.status-message');
        if (message) {
            // Set a timeout to remove the message after 5 seconds
            setTimeout(() => {
                message.style.display = 'none';
            }, 5000); // 5000 milliseconds = 5 seconds
        }
    });
</script>
</body>
</html>
