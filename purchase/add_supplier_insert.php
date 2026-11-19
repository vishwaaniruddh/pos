<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

// Safely retrieve input parameters
$fname          = trim($_POST['fname'] ?? '');
$lname          = trim($_POST['lname'] ?? '');
$email          = trim($_POST['email'] ?? '');
$phone_no       = trim($_POST['ph_no'] ?? '');
$add1           = trim($_POST['add1'] ?? '');
$add2           = trim($_POST['add2'] ?? '');
$city           = trim($_POST['city'] ?? '');
$state          = trim($_POST['state'] ?? '');
$pincode        = trim($_POST['pincode'] ?? '');
$country        = trim($_POST['country'] ?? '');
$comment        = trim($_POST['comments'] ?? $_POST['comment'] ?? '');
$company_name   = trim($_POST['supp_comp_name'] ?? '');
$company_acc_no = trim($_POST['supp_acc_no'] ?? '');

if (empty($fname) || empty($company_name)) {
    echo '<script>alert("Please fill all required fields."); window.location.href="add_supplier.php";</script>';
    CloseCon($con);
    exit;
}

$success = false;

try {
    mysqli_begin_transaction($con);

    // 1. Insert into phppos_people using prepared statement
    $stmt1 = mysqli_prepare($con, "INSERT INTO `phppos_people` (`first_name`, `last_name`, `phone_number`, `email`, `address_1`, `address_2`, `city`, `state`, `zip`, `country`, `comments`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt1) {
        throw new Exception("Prepare failed for people insert: " . mysqli_error($con));
    }
    mysqli_stmt_bind_param($stmt1, "sssssssssss", $fname, $lname, $phone_no, $email, $add1, $add2, $city, $state, $pincode, $country, $comment);
    mysqli_stmt_execute($stmt1);
    mysqli_stmt_close($stmt1);

    // 2. Fetch the exact generated person_id from the insert
    $person_id = mysqli_insert_id($con);
    if (!$person_id) {
        throw new Exception("Failed to retrieve generated person_id.");
    }

    // 3. Insert into phppos_suppliers using prepared statement
    $stmt2 = mysqli_prepare($con, "INSERT INTO `phppos_suppliers` (`person_id`, `company_name`, `account_number`) VALUES (?, ?, ?)");
    if (!$stmt2) {
        throw new Exception("Prepare failed for suppliers insert: " . mysqli_error($con));
    }
    mysqli_stmt_bind_param($stmt2, "iss", $person_id, $company_name, $company_acc_no);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    mysqli_commit($con);
    $success = true;
} catch (Throwable $e) {
    mysqli_rollback($con);
    error_log("Add supplier error: " . $e->getMessage());
    $success = false;
}

CloseCon($con);
?>

<?php if ($success): ?>
    <script>
        alert("Successfully Inserted");
        window.location.href = "add_supplier.php";
    </script>
<?php else: ?>
    <script>
        alert("Something went wrong while adding the supplier. Please check if the supplier details already exist.");
        window.location.href = "add_supplier.php";
    </script>
<?php endif; ?>
