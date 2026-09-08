<?php
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

$id = isset($_GET['cid']) ? mysqli_real_escape_string($con, trim($_GET['cid'])) : '';

$num1 = 0;
$person_id = 0;

if ($id !== '') {
    $qry1 = "SELECT person_id FROM phppos_people WHERE phone_number='$id' LIMIT 1";
    $res1 = mysqli_query($con, $qry1);
    if ($res1 && mysqli_num_rows($res1) > 0) {
        $num1 = 1;
        $row1 = mysqli_fetch_row($res1);
        $person_id = $row1[0] ?? 0;
    }
}

echo $num1 . "&&" . $person_id;
CloseCon($con);
?>
