<?php 

// Include the database connection file
include('../db_connection.php');
$con = OpenSrishringarrCon();

// Get the 'people' parameter from the request
$people = trim($_REQUEST['people']);

if ($people) {
    // Corrected SQL query using CONCAT for concatenating first and last names
    $sql = mysqli_query($con, "SELECT * FROM phppos_people WHERE CONCAT(first_name, ' ', last_name) = '$people'");
    
    // Fetch the result
    if ($sql_result = mysqli_fetch_assoc($sql)) {
        // Extract the person_id from the result
        $person_id = $sql_result['person_id'];
        $data = ['person_id' => $person_id];
        
        // Return the result as a JSON response
        echo json_encode($data);    
    } else {
        // Return 0 if no matching record is found
        echo 0;
    }
} else {
    // Return 0 if 'people' parameter is not provided
    echo 0;
}

// Close the database connection (optional but recommended)
mysqli_close($con);
?>
