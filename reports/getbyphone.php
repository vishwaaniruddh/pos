<?php
ini_set("display_errors", 0);
include_once(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

$raw = trim((string)($_GET['cid'] ?? ''));
$digits = preg_replace('/[^0-9]/', '', $raw);
if (strlen($digits) > 10 && substr($digits, 0, 2) === '91') {
    $digits = substr($digits, 2);
} elseif (strlen($digits) === 11 && substr($digits, 0, 1) === '0') {
    $digits = substr($digits, 1);
}

$num1 = 0;
$personId = '';

if ($raw !== '' || $digits !== '') {
    $normalizedCol = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_number, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')";

    if (strlen($digits) >= 10) {
        $last10 = substr($digits, -10);
        $stmt = $con->prepare("
            SELECT person_id FROM phppos_people
            WHERE RIGHT($normalizedCol, 10) = ? OR $normalizedCol = ? OR phone_number = ?
            ORDER BY person_id DESC
        ");
        if ($stmt) {
            $stmt->bind_param("sss", $last10, $digits, $raw);
            $stmt->execute();
            $res = $stmt->get_result();
            $num1 = $res->num_rows;
            if ($row = $res->fetch_row()) {
                $personId = $row[0];
            }
            $stmt->close();
        }
    } elseif ($digits !== '') {
        $likeDigits = '%' . $digits . '%';
        $likeRaw = '%' . $raw . '%';
        $stmt = $con->prepare("
            SELECT person_id FROM phppos_people
            WHERE $normalizedCol LIKE ? OR phone_number LIKE ?
            ORDER BY person_id DESC
        ");
        if ($stmt) {
            $stmt->bind_param("ss", $likeDigits, $likeRaw);
            $stmt->execute();
            $res = $stmt->get_result();
            $num1 = $res->num_rows;
            if ($row = $res->fetch_row()) {
                $personId = $row[0];
            }
            $stmt->close();
        }
    } else {
        $stmt = $con->prepare("
            SELECT person_id FROM phppos_people
            WHERE phone_number = ?
            ORDER BY person_id DESC
        ");
        if ($stmt) {
            $stmt->bind_param("s", $raw);
            $stmt->execute();
            $res = $stmt->get_result();
            $num1 = $res->num_rows;
            if ($row = $res->fetch_row()) {
                $personId = $row[0];
            }
            $stmt->close();
        }
    }
}

echo $num1 . "&&" . $personId;

CloseCon($con);
?>
