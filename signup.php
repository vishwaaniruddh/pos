<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('./db_connection.php') ;
$con=OpenSrishringarrCon();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $uname = isset($_POST['uname']) ? trim($_POST['uname']) : '';
    $pwd = isset($_POST['pwd']) ? trim($_POST['pwd']) : '';
    $permission = isset($_POST['permission']) ? trim($_POST['permission']) : '';
    $designation = isset($_POST['designation']) ? trim($_POST['designation']) : 'Staff';
    $level = isset($_POST['level']) ? trim($_POST['level']) : 'Staff';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

    if (empty($name) || empty($uname) || empty($pwd)) {
        if ($isAjax) {
            echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields (Name, Username, Password).']);
            exit;
        }
        echo "Error: Required fields missing.";
        exit;
    }

    // Check if username already exists
    $checkStmt = $con->prepare("SELECT id FROM loginusers WHERE uname = ? LIMIT 1");
    $checkStmt->bind_param("s", $uname);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    if ($checkRes && $checkRes->num_rows > 0) {
        $checkStmt->close();
        if ($isAjax) {
            echo json_encode(['status' => 'error', 'message' => "Username '{$uname}' is already in use. Please choose another username."]);
            exit;
        }
        echo "Error: Username already exists.";
        exit;
    }
    $checkStmt->close();

    // Prepare and bind
    $stmt = $con->prepare("INSERT INTO loginusers (name, uname, pwd, permission, designation, level, email, contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $uname, $pwd, $permission, $designation, $level, $email, $contact);

    // Execute the statement
    if ($stmt->execute()) {
        $insertId = $stmt->insert_id;
        $stmt->close();
        if ($isAjax) {
            echo json_encode(['status' => 'success', 'message' => 'User created successfully', 'insert_id' => $insertId]);
            exit;
        }
        echo "Signup successful";
    } else {
        $errorMsg = $stmt->error;
        $stmt->close();
        if ($isAjax) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $errorMsg]);
            exit;
        }
        echo "Error: " . $errorMsg;
    }
}

$con->close();
?>
