<?php session_start();
date_default_timezone_set('Asia/Kolkata');
    // file_get_contents("https://srishringarr.com/pos/yn_pos_qty_sync.php");
    // file_get_contents("https://srishringarr.com/pos/sync_wp_inventory.php");


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$datetime = date('Y-m-d H:i:s');

if (!function_exists('OpenCon')) {
function OpenCon()
 {
 $dbhost = "localhost";
 $dbuser = "root";
 $dbpass = "";
 $db = "esurv";
// $port = "3308";
 $conn = new mysqli($dbhost, $dbuser, $dbpass,$db) or die("Connect failed: %s\n". $conn -> error);
 
 return $conn;
 }
}
 
 
 if (!function_exists('OpenComsarmiCon')) {
 function OpenComsarmiCon()
 {
 $dbhost = "localhost";
 $dbuser = "comsarmi_DVR";
 $dbpass = "sar@1234";
 $db = "comsarmi_dvr_data";
// $port = "3308";
 $conn = new mysqli($dbhost, $dbuser, $dbpass,$db) or die("Connect failed: %s\n". $conn -> error);
 
 return $conn;
 }
 }
 
 if (!function_exists('OpenNewCon')) {
 function OpenNewCon()
 {
 $dbhost = "192.168.100.21";
 $dbuser = "esurv";
 $dbpass = "Esurv123*";
 $db = "esurv";
 $conn = new mysqli($dbhost, $dbuser, $dbpass,$db) or die("Connect failed: %s\n". $conn -> error);
 
 return $conn;
 }
 }
 
 
 if (!function_exists('OpenSrishringarrCon')) {
  function OpenSrishringarrCon()
 {
 //$dbhost = "localhost";
 $dbhost = "localhost";
 $dbuser = "u464193275_sarmicropos";
 $dbpass = "Mypos1234";
 $db = "u464193275_srishringarr";
 $conn = new mysqli($dbhost, $dbuser, $dbpass,$db) or die("Connect failed: %s\n". $conn -> error);
 
 return $conn;
 }
 }
 if (!function_exists('OpenNewSrishringarrCon')) {
 
function OpenNewSrishringarrCon()
 {
 $dbhost = "localhost";
 $dbhost = "localhost";
 $dbuser = "u464193275_srishrinjuser";
 $dbpass = "9b@hMgk!=zI";
 $db = "u464193275_srishrinjewels";
 $conn = new mysqli($dbhost, $dbuser, $dbpass,$db) or die("Connect failed: %s\n". $conn -> error);
 
 return $conn;
 } 
 
 }
 if (!function_exists('OpenPurchaseSrishringarrCon')) {
 function OpenPurchaseSrishringarrCon()
 {
//  $dbhost = "198.38.84.10";
 $dbhost = "localhost";
 $dbuser = "satyavanpos123";
 $dbpass = "Mypos1234";
 $db = "satyavanpos";
 $conn = new mysqli($dbhost, $dbuser, $dbpass,$db) or die("Connect failed: %s\n". $conn -> error);
 
 return $conn;
 } 
 
 }
 
 
 if (!function_exists('CloseCon')) {
function CloseCon($conn)
 {
 $conn -> close();
 }
 }

$con=OpenSrishringarrCon();
$userid = $_SESSION['userid'];



if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $requestData = json_encode($_REQUEST);
    $sessionData = json_encode($_SESSION);
    $fileData = json_encode($_FILES);

    $dataRecordsSql = "insert into datarecords(requestData,sessionData,fileData,created_at,created_by) 
            values('" . $requestData . "','" . $sessionData . "','" . $fileData . "','" . $datetime . "','" . $userid . "')";

    mysqli_query($con, $dataRecordsSql);
}


$web_con = OpenNewSrishringarrCon();



$current_filename =  basename($_SERVER['REQUEST_URI'],PATHINFO_BASENAME);


$get_menu = mysqli_query($con,"select * from sub_menu where page='".$current_filename."'");
if($get_menu_result = mysqli_fetch_assoc($get_menu)){
    $title = $get_menu_result['title'];
}



// $basepath = 

if($title){

echo "<title>$title</title>";    
}

?>

