<?php
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}

$con = OpenSrishringarrCon();

$name = isset($_GET['qu']) ? trim($_GET['qu']) : '';
if ($name !== '') {
    $name_safe = mysqli_real_escape_string($con, $name);
    $sql = mysqli_query($con, "SELECT name FROM phppos_items WHERE name='" . $name_safe . "' AND is_deleted = 0 LIMIT 1");
    if ($sql && mysqli_num_rows($sql) > 0) {
        echo "taken";
    } else {
        echo "ok";
    }
} else {
    echo "ok";
}

CloseCon($con);
?>