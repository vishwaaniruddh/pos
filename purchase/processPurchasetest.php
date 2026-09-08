<?php 
session_start();

if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$bill_id   = isset($_POST['bill_id']) ? mysqli_real_escape_string($con, trim($_POST['bill_id'])) : '';
$bill_date = isset($_POST['bill_date']) ? trim($_POST['bill_date']) : '';
$supp_id   = isset($_POST['supp_id']) ? mysqli_real_escape_string($con, trim($_POST['supp_id'])) : '0';

$myitemid  = isset($_POST['myitemid']) ? $_POST['myitemid'] : [];
$item_cat  = isset($_POST['item_cat']) ? $_POST['item_cat'] : [];
$item_no   = isset($_POST['item_no']) ? $_POST['item_no'] : [];
$cprice    = isset($_POST['cprice']) ? $_POST['cprice'] : [];
$uprice    = isset($_POST['uprice']) ? $_POST['uprice'] : [];
$qty       = isset($_POST['qty']) ? $_POST['qty'] : [];

$totalqty  = isset($_POST['totalqty']) ? floatval($_POST['totalqty']) : 0;
$totalamt  = isset($_POST['totalamt']) ? floatval($_POST['totalamt']) : 0;
$payamt    = isset($_POST['payamt']) ? floatval($_POST['payamt']) : 0;
$distype   = isset($_POST['distype']) ? mysqli_real_escape_string($con, trim($_POST['distype'])) : 'Rupees';
$discount  = isset($_POST['per']) ? floatval($_POST['per']) : 0;

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

// Normalize date to YYYY-MM-DD
$db_bill_date = date('Y-m-d');
if (!empty($bill_date)) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $bill_date)) {
        $db_bill_date = $bill_date;
    } else if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $bill_date, $m)) {
        $db_bill_date = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    } else {
        $ts = strtotime($bill_date);
        if ($ts) $db_bill_date = date('Y-m-d', $ts);
    }
}

$errors = 0;
mysqli_query($con, "BEGIN;");

$qrypur = mysqli_query($con, "INSERT INTO `phppos_purchase`(`pur_id`, `bill_id`, `supp_id`, `date`, `totalqty`, `totalamt`, `outstanding`, `discount`, `payamt`, `dis_type`) VALUES ('', '$bill_id', '$supp_id', '$db_bill_date', '$totalqty', '$totalamt', '$payamt', '$discount', '$payamt', '$distype')");

if ($qrypur) {
    $pur_id = mysqli_insert_id($con);
    
    for ($i = 0; $i < count($myitemid); $i++) {
        $raw_name = trim($myitemid[$i]);
        if ($raw_name === '') continue; // Skip empty rows

        $escaped_name = mysqli_real_escape_string($con, $raw_name);
        $escaped_cat  = isset($item_cat[$i]) ? mysqli_real_escape_string($con, trim($item_cat[$i])) : '';
        $escaped_no   = isset($item_no[$i]) ? mysqli_real_escape_string($con, trim($item_no[$i])) : '';
        $row_cprice   = isset($cprice[$i]) ? floatval($cprice[$i]) : 0;
        $row_uprice   = isset($uprice[$i]) ? floatval($uprice[$i]) : 0;
        $row_qty      = isset($qty[$i]) ? intval($qty[$i]) : 0;

        $productType = '';
        if ($escaped_cat !== '') {
            $productsql = mysqli_query($con, "SELECT `typ` FROM `categories` WHERE `category` = '$escaped_cat'");
            if ($productsql && $productsql_result = mysqli_fetch_assoc($productsql)) {
                $productType = mysqli_real_escape_string($con, $productsql_result['typ']);
            }
        }

        $res = mysqli_query($con, "SELECT * FROM `phppos_items` WHERE `name` = '$escaped_name' AND `is_deleted` = 0");
        
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_array($res);
            $orgqt = intval($row["quantity"]);
            $newqtry = $row_qty + $orgqt;
            $myautoid = $row["item_id"];

            $str = "UPDATE `phppos_items` SET `category` = '$escaped_cat', `supplier_id` = '$supp_id', `description` = '$db_bill_date', `cost_price` = '$row_cprice', `unit_price` = '$row_uprice', `quantity` = '$newqtry', `category_type` = '$productType' WHERE `item_number` = '$escaped_no'";
            $qryitm = mysqli_query($con, $str);
            if (!$qryitm) $errors++;
        } else {
            $itemNumber = $escaped_no;
            $insert_item_qry = "INSERT INTO `phppos_items`(`name`, `category`, `supplier_id`, `item_number`, `description`, `cost_price`, `unit_price`, `quantity`, `category_type`) VALUES ('$escaped_name', '$escaped_cat', '$supp_id', '$itemNumber', '$db_bill_date', '$row_cprice', '$row_uprice', '$row_qty', '$productType')";
            $qryitm = mysqli_query($con, $insert_item_qry);
            $myautoid = mysqli_insert_id($con);
            if (!$qryitm) $errors++;
        }

        // Insert into phppos_purchase_details
        $det = mysqli_query($con, "INSERT INTO `phppos_purchase_details`(`id`, `pur_id`, `item_id`, `qty`, `price`) VALUES ('', '$pur_id', '$myautoid', '$row_qty', '$row_cprice')");
        if (!$det) $errors++;
    }
} else {
    $errors++;
}

if ($errors == 0) {
    mysqli_query($con, "COMMIT;");
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Purchase invoice #' . htmlspecialchars($bill_id) . ' recorded successfully.',
            'pur_id' => $pur_id,
            'total_qty' => $totalqty,
            'pay_amt' => $payamt
        ]);
        exit;
    }
    header('location:view_bills.php');
    exit;
} else {
    mysqli_query($con, "ROLLBACK;");
    $err = mysqli_error($con);
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to save purchase bill: ' . $err
        ]);
        exit;
    }
    echo '<script>alert("Error processing purchase: ' . addslashes($err) . '"); window.history.back();</script>';
    exit;
}

CloseCon($con);
?>