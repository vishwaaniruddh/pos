<?php
session_start();

if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

$fname          = isset($_POST['fname']) ? mysqli_real_escape_string($con, trim($_POST['fname'])) : '';
$lname          = isset($_POST['lname']) ? mysqli_real_escape_string($con, trim($_POST['lname'])) : '';
$email          = isset($_POST['email']) ? mysqli_real_escape_string($con, trim($_POST['email'])) : '';
$phone_no       = isset($_POST['ph_no']) ? mysqli_real_escape_string($con, trim($_POST['ph_no'])) : '';
$add1           = isset($_POST['add1']) ? mysqli_real_escape_string($con, trim($_POST['add1'])) : '';
$add2           = isset($_POST['add2']) ? mysqli_real_escape_string($con, trim($_POST['add2'])) : '';
$city           = isset($_POST['city']) ? mysqli_real_escape_string($con, trim($_POST['city'])) : '';
$state          = isset($_POST['state']) ? mysqli_real_escape_string($con, trim($_POST['state'])) : '';
$pincode        = isset($_POST['pincode']) ? mysqli_real_escape_string($con, trim($_POST['pincode'])) : '';
$country        = isset($_POST['country']) ? mysqli_real_escape_string($con, trim($_POST['country'])) : '';
$comment        = isset($_POST['comments']) ? mysqli_real_escape_string($con, trim($_POST['comments'])) : (isset($_POST['comment']) ? mysqli_real_escape_string($con, trim($_POST['comment'])) : '');
$company_name   = isset($_POST['supp_comp_name']) ? mysqli_real_escape_string($con, trim($_POST['supp_comp_name'])) : '';
$company_acc_no = isset($_POST['supp_acc_no']) ? mysqli_real_escape_string($con, trim($_POST['supp_acc_no'])) : '';

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if (!$fname || !$company_name) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'First Name and Supplier Company Name are required.']);
        exit;
    }
    echo '<script>alert("First Name and Supplier Company Name are required."); window.location.href="add_supplier.php";</script>';
    exit;
}

// Use transaction for safe multi-table insert
mysqli_query($con, "BEGIN");

$insert = mysqli_query($con, "INSERT INTO `phppos_people` (`first_name`, `last_name`, `phone_number`, `email`, `address_1`, `address_2`, `city`, `state`, `zip`, `country`, `comments`) VALUES ('$fname', '$lname', '$phone_no', '$email', '$add1', '$add2', '$city', '$state', '$pincode', '$country', '$comment')");

if ($insert) {
    $person_id = mysqli_insert_id($con);
    
    $insert_supp = mysqli_query($con, "INSERT INTO `phppos_suppliers` (`person_id`, `company_name`, `account_number`) VALUES ('$person_id', '$company_name', '$company_acc_no')");
    
    if ($insert_supp) {
        mysqli_query($con, "COMMIT");
        $_SESSION['success'] = 1;
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => 'Supplier registered successfully',
                'person_id' => $person_id,
                'company_name' => stripslashes($company_name)
            ]);
            exit;
        }
        
        echo '<script>alert("Successfully Inserted"); window.location.href="add_supplier.php";</script>';
        exit;
    } else {
        mysqli_query($con, "ROLLBACK");
        $err = mysqli_error($con);
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Failed to save supplier details: ' . $err]);
            exit;
        }
        echo '<script>alert("Error saving supplier details"); window.location.href="add_supplier.php";</script>';
        exit;
    }
} else {
    mysqli_query($con, "ROLLBACK");
    $err = mysqli_error($con);
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Failed to save contact profile: ' . $err]);
        exit;
    }
    echo '<script>alert("Error saving contact profile"); window.location.href="add_supplier.php";</script>';
    exit;
}

CloseCon($con);
?>