<?php
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}

$con = OpenSrishringarrCon();

$q = isset($_GET['searchdata']) ? trim($_GET['searchdata']) : '';
if ($q !== '') {
    $q_safe = mysqli_real_escape_string($con, $q);
    $sql_res = mysqli_query($con, "SELECT name FROM phppos_items WHERE name LIKE '%" . $q_safe . "%' AND is_deleted = 0 ORDER BY item_id DESC LIMIT 5");
    if ($sql_res && mysqli_num_rows($sql_res) > 0) {
        echo "<select class='show'>";
        while ($row = mysqli_fetch_assoc($sql_res)) {
            $username = htmlspecialchars($row['name'] ?? '');
            echo "<option value=\"" . $username . "\">" . $username . "</option>";
        }
        echo "</select>";
    }
}

CloseCon($con);
?>