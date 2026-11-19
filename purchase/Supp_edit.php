<?php 
include('../db_connection.php');
$con = OpenSrishringarrCon();

$idd = isset($_POST["id"]) ? mysqli_real_escape_string($con, $_POST["id"]) : '';
if (empty($idd)) {
    echo '<script>alert("Invalid supplier ID!"); window.location="view_supplier.php";</script>';
    exit;
}

$fname = isset($_POST['first_name']) ? mysqli_real_escape_string($con, trim($_POST['first_name'])) : '';
$lname = isset($_POST['last_name']) ? mysqli_real_escape_string($con, trim($_POST['last_name'])) : '';
$mobile = isset($_POST['phone']) ? mysqli_real_escape_string($con, trim($_POST['phone'])) : '';
$email = isset($_POST['email']) ? mysqli_real_escape_string($con, trim($_POST['email'])) : '';
$address = isset($_POST['add']) ? mysqli_real_escape_string($con, trim($_POST['add'])) : '';
$dob = isset($_POST['dob']) ? trim($_POST['dob']) : '';

$dob1 = (!empty($dob) && $dob != '0000-00-00') ? "'" . date("Y-m-d", strtotime(str_replace('/', '-', $dob))) . "'" : "NULL";

$update = "UPDATE `phppos_people` SET 
    `first_name` = '{$fname}',
    `last_name` = '{$lname}',
    `phone_number` = '{$mobile}',
    `email` = '{$email}',
    `address_1` = '{$address}',
    `dob` = {$dob1}
WHERE `person_id` = '{$idd}'";

$updqry = mysqli_query($con, $update);

// Also update company_name and account_number in phppos_suppliers if provided
if (isset($_POST['company_name']) || isset($_POST['account_number'])) {
    $comp = isset($_POST['company_name']) ? mysqli_real_escape_string($con, trim($_POST['company_name'])) : '';
    $acc = isset($_POST['account_number']) ? mysqli_real_escape_string($con, trim($_POST['account_number'])) : '';
    mysqli_query($con, "UPDATE `phppos_suppliers` SET `company_name` = '{$comp}', `account_number` = '{$acc}' WHERE `person_id` = '{$idd}'");
}

if ($updqry) {
    echo '<script>alert("Supplier Data Updated Successfully"); window.location="view_supplier.php";</script>';
} else {
    echo '<script>alert("Something went wrong while updating! Please try again."); window.location="view_supplier.php";</script>';
}

CloseCon($con);
?>
