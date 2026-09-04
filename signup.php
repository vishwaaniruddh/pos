<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('./db_connection.php') ;
$con=OpenSrishringarrCon();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $uname = $_POST['uname'];
    $pwd = $_POST['pwd']; // Hash the password
    $permission = $_POST['permission'];
    $designation = $_POST['designation'];
    $level = $_POST['level'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];

    // Prepare and bind
    $stmt = $con->prepare("INSERT INTO loginusers (name, uname, pwd, permission, designation, level, email, contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $uname, $pwd, $permission, $designation, $level, $email, $contact);

    // Execute the statement
    if ($stmt->execute()) {
        echo "Signup successful";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close statement and connection
    $stmt->close();
}

$con->close();
?>
