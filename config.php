<?php 
$host="localhost";
$user="root";
$pass="";
$dbname="esurv";
$con = new mysqli($host, $user, $pass, $dbname);
// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
} else {
    //echo "Connected succesfull";
}
?>

<?php 
/*
$newhost = "192.168.100.21";
$newuser="esurv";
$newpass="Esurv123*";
$newdbname="esurv";
$newconn = new mysqli($newhost, $newuser, $newpass, $newdbname);

if ($newconn->connect_error) {
    die("Connection failed: " . $newconn->connect_error);
} else {
    
}  
*/
/*
$host="localhost";
$user="comsarmi_DVR";
$pass="sar@1234";
$dbname="comsarmi_DVR_Data";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    
}
*/


include("globalfunctions.php");
?>