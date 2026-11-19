<?php
include('../db_connection.php') ;
$con=OpenSrishringarrCon();



if (isset($_POST['input'])) {
    $input = $_POST['input'];

     $query = "SELECT first_name, last_name FROM phppos_people
              WHERE first_name like '%" . mysqli_real_escape_string($con, $input) . "%' OR 
              last_name like '%" . mysqli_real_escape_string($con, $input) . "%'
              ";
    

    
    // $query = "SELECT atmid FROM sites WHERE atmid LIKE '%" . mysqli_real_escape_string($con, $input) . "%'";
    $result = mysqli_query($con, $query);

    $suggestions = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $suggestions[] = $row['first_name'] . ' ' . $row['last_name'] ;
    }

    echo json_encode($suggestions);
}
?>

