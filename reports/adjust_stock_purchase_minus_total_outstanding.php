<?
include('../db_connection.php');
    $con = OpenSrishringarrCon();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$item_id = $_REQUEST['item_id'];
if($item_id){
    
    $items_sql = mysqli_query($con,"select * from phppos_items where name='".$item_id."'");
    $items_sql_result = mysqli_fetch_assoc($items_sql);
    
    $id = $items_sql_result['item_id'];
    
    echo "SELECT `cust_id` FROM `approval` WHERE `status` in ('a','s') and`bill_id` in(select `bill_id` from `approval_detail` where `item_id` ='$item_id') group by `cust_id`" ; 
$sql = mysqli_query($con,"SELECT `cust_id` FROM `approval` WHERE `status` in ('a','s') and `bill_id` in (select `bill_id` from `approval_detail` where `item_id` ='$item_id') group by `cust_id`");   

$arr = array();
while($res = mysqli_fetch_row($sql)){
    $arr[] = $res[0]; // Using [] automatically appends values to array
}

$custname = array();
$qty = array();
$amt = array();
$ret = array();

foreach ($arr as $index => $cust_id) {
    $qrycust = mysqli_query($con,"SELECT first_name, last_name FROM `phppos_people` WHERE person_id='$cust_id'");
    $rescust = mysqli_fetch_row($qrycust);

    if ($rescust) {
        $custname[$index] = $rescust[0] . " " . $rescust[1];
    } else {
        $custname[$index] = "Unknown Customer";
    }

    $qrybal = mysqli_query($con,"SELECT SUM(qty), SUM(amount), SUM(return_qty) FROM `approval_detail` WHERE `bill_id` IN (SELECT `bill_id` FROM `approval` WHERE `status` IN ('a','s') AND `cust_id`='$cust_id' AND `item_id`='$item_id')");

    $row = mysqli_fetch_array($qrybal);

    // Ensure values are set
    $qty[$index] = $row[0] ?? 0;
    $amt[$index] = $row[1] ?? 0;
    $ret[$index] = $row[2] ?? 0;
}

echo "<br/>";
print_r($custname);
print_r($qty);

echo "<table width='100%' bgcolor='#CCCCCC' border='1' id='tbl'>
<tr><td colspan='4' align='center'><strong>**Sales Report**<br>Item Name: $item_id</strong></td></tr>
<tr><th align='center'>Sr No</th><th align='center'>Customer Name</th><th align='center'>Quantity</th><th align='center'>Amount</th></tr>";

$k = 1;
$total_final_qty = 0 ;
foreach ($qty as $index => $quantity) {
    if ($quantity > $ret[$index]) {
        $final_qty = $quantity - $ret[$index];
        $final_amt = ($final_qty * $amt[$index]) / max($quantity, 1); // Prevent division by zero

        echo "<tr><td align='center'>{$k}</td><td align='center'>{$custname[$index]}</td><td align='right'>{$final_qty}</td><td align='right'>{$final_amt}</td></tr>";
        $k++;
                $total_final_qty = $total_final_qty + $final_qty ; 
    }
}
echo "</table>";
	
	
	
$purchase_sql = mysqli_query($con,"SELECT item_id,sum(qty) as total_purchase_qty from  `phppos_purchase_details` where item_id = '".$id."'");
$purchase_sql_result = mysqli_fetch_assoc($purchase_sql);

$total_purchase_qty = $purchase_sql_result['total_purchase_qty'];

$remaining_stocks = $total_purchase_qty - $total_final_qty ; 

echo '$remaining_stocks = ' . $remaining_stocks ; 

	
	
}else{
$statement = mysqli_query($con,"Select * from phppos_items");
while($statement_result = mysqli_fetch_assoc($statement)){
    
    $item_id = $statement_result['name'];
    $id = $statement_result['item_id'];
    
    
    
$purchase_sql = mysqli_query($con, 
    "SELECT item_id, COALESCE(SUM(qty), 0) AS total_purchase_qty 
     FROM phppos_purchase_details 
     WHERE item_id = '" . $id . "'"
);
if (!$purchase_sql) {
    die("Query Error: " . mysqli_error($con)); // Check for SQL errors
}

    
    
    
    $sql = mysqli_query($con,"SELECT `cust_id` FROM `approval` WHERE `status` in ('a','s') and `bill_id` in (select `bill_id` from `approval_detail` where `item_id` ='$item_id') group by `cust_id`");   

$arr = array();
while($res = mysqli_fetch_row($sql)){
    $arr[] = $res[0]; // Using [] automatically appends values to array
}

$custname = array();
$qty = array();
$amt = array();
$ret = array();

foreach ($arr as $index => $cust_id) {
    $qrycust = mysqli_query($con,"SELECT first_name, last_name FROM `phppos_people` WHERE person_id='$cust_id'");
    $rescust = mysqli_fetch_row($qrycust);

    if ($rescust) {
        $custname[$index] = $rescust[0] . " " . $rescust[1];
    } else {
        $custname[$index] = "Unknown Customer";
    }

    $qrybal = mysqli_query($con,"SELECT SUM(qty), SUM(amount), SUM(return_qty) FROM `approval_detail` WHERE `bill_id` IN (SELECT `bill_id` FROM `approval` WHERE `status` IN ('a','s') AND `cust_id`='$cust_id' AND `item_id`='$item_id')");

    $row = mysqli_fetch_array($qrybal);

    // Ensure values are set
    $qty[$index] = $row[0] ?? 0;
    $amt[$index] = $row[1] ?? 0;
    $ret[$index] = $row[2] ?? 0;
}

// echo "<br/>";
// print_r($custname);
// print_r($qty);

echo "<table width='100%' bgcolor='#CCCCCC' border='1' id='tbl'>
<tr><td colspan='4' align='center'><strong>**Sales Report**<br>Item Name: $item_id</strong></td></tr>
<tr><th align='center'>Sr No</th><th align='center'>Customer Name</th><th align='center'>Quantity</th><th align='center'>Amount</th></tr>";

$k = 1;
$total_final_qty = 0 ;
foreach ($qty as $index => $quantity) {
    if ($quantity > $ret[$index]) {
        $final_qty = $quantity - $ret[$index];
        $final_amt = ($final_qty * $amt[$index]) / max($quantity, 1); // Prevent division by zero

        echo "<tr><td align='center'>{$k}</td><td align='center'>{$custname[$index]}</td><td align='right'>{$final_qty}</td><td align='right'>{$final_amt}</td></tr>";
        $k++;
        $total_final_qty = $total_final_qty + $final_qty ; 
    }
}
echo "</table>";




// total purchase 

if ($purchase_sql_result = mysqli_fetch_assoc($purchase_sql)) {
    $total_purchase_qty = $purchase_sql_result['total_purchase_qty'];
    if($total_purchase_qty > 0){
    
        // Debugging: Check retrieved purchase quantity
        echo "Total Purchase Qty: " . $total_purchase_qty . "<br>";
    
        // Assume $total_final_qty is set
        $remaining_stocks = $total_purchase_qty - $total_final_qty;
    
        echo "Remaining Stocks = " . $remaining_stocks . "<br>";
        echo "Update Query: UPDATE phppos_items SET quantity='" . $remaining_stocks . "' WHERE item_id='" . $id . "'<br>";
    
        // Uncomment this line to update the database
        mysqli_query($con, "UPDATE phppos_items SET quantity='" . $remaining_stocks . "' WHERE item_id='" . $id . "'");    
    }

} else {
    echo "Item not found in purchases.";
}


    
}
}



?>

