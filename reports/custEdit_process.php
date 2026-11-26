<?php
include(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

$idd = (int)($_POST['id'] ?? 0);
$fname = trim((string)($_POST['first_name'] ?? ''));
$lname = trim((string)($_POST['last_name'] ?? ''));
$mobile = trim((string)($_POST['phone'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$address = trim((string)($_POST['add'] ?? ''));
$rawDob = trim((string)($_POST['dob'] ?? ''));

$dob = '0000-00-00';
if ($rawDob !== '' && $rawDob !== '0000-00-00') {
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $rawDob, $m)) {
        $dob = "{$m[3]}-{$m[2]}-{$m[1]}";
    } else {
        $ts = strtotime($rawDob);
        if ($ts !== false) {
            $dob = date('Y-m-d', $ts);
        }
    }
}

$ok = false;
if ($idd > 0) {
    $stmt = $con->prepare("UPDATE `phppos_people` SET `first_name` = ?, `last_name` = ?, `phone_number` = ?, `email` = ?, `address_1` = ?, `dob` = ? WHERE `person_id` = ?");
    if ($stmt) {
        $stmt->bind_param("ssssssi", $fname, $lname, $mobile, $email, $address, $dob, $idd);
        $ok = $stmt->execute();
        $stmt->close();
    }
}

CloseCon($con);

if ($ok) {
    header('Location: custLst.php?status=success&msg=' . urlencode("Customer #{$idd} updated successfully."));
} else {
    header('Location: custLst.php?status=error&msg=' . urlencode('Something went wrong updating customer.'));
}
exit;
?>
