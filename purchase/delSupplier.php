<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

$id = isset($_GET['id']) ? mysqli_real_escape_string($con, $_GET['id']) : '';

if (!empty($id)) {
    $delqry = mysqli_query($con, "DELETE FROM `phppos_suppliers` WHERE `person_id` = '{$id}'");
    if ($delqry) {
        echo '<script>alert("Supplier Deleted Successfully"); window.location="view_supplier.php";</script>';
    } else {
        echo '<script>alert("Error deleting supplier record. Please try again."); window.location="view_supplier.php";</script>';
    }
} else {
    echo '<script>alert("Invalid supplier ID."); window.location="view_supplier.php";</script>';
}

CloseCon($con);
?>
