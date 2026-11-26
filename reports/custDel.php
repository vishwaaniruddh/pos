<?php
include(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
    CloseCon($con);
    header('Location: custLst.php?status=error&msg=' . urlencode('Invalid customer ID.'));
    exit;
}

$delCust = $con->prepare("DELETE FROM phppos_customers WHERE person_id = ?");
if ($delCust) {
    $delCust->bind_param("i", $id);
    $delCust->execute();
    $delCust->close();
}

$delPeople = $con->prepare("DELETE FROM phppos_people WHERE person_id = ?");
$ok = false;
if ($delPeople) {
    $delPeople->bind_param("i", $id);
    $ok = $delPeople->execute() && ($delPeople->affected_rows >= 0);
    $delPeople->close();
}

CloseCon($con);

if ($ok) {
    header('Location: custLst.php?status=success&msg=' . urlencode("Customer #{$id} deleted successfully."));
} else {
    header('Location: custLst.php?status=error&msg=' . urlencode('Failed to delete customer record.'));
}
exit;
?>
