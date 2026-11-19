<?php 

include('db_connection.php');
$con = OpenSrishringarrCon();

$itemid = $_POST["itemid"];
$quantity = $_POST['quantity'];
$_category = $_POST['category'];
$category = ucfirst($_category);

date_default_timezone_set('Asia/Kolkata');
$updated_at = date('Y-m-d H:i:s');

$update = "UPDATE `phppos_items` 
           SET `quantity` = '$quantity', updated_at = '$updated_at', category = '$category' 
           WHERE item_id = '$itemid'";
$updqry = mysqli_query($con, $update);

if($updqry) {
    echo '<script>alert("Data Updated Successfully")</script>';
    echo '<script>window.location="itemcode_details.php"</script>';
} else {
    echo '<script>alert("Something Wrong!! Try Again!!")</script>';
    echo '<script>window.location="itemcode_details.php"</script>';
}

CloseCon($con); 
?>
