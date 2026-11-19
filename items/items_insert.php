<?php 
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

// $itemID = $_POST['itemId'];
// echo $itemID;
$itemNo = $_POST['itemNo'];
if($itemNo = '') { $itemNo="NULL"; } else { $itemNo = $_POST['itemNo']; }

$name = $_POST['itemName'];
$category = $_POST['category'];
$supp_id = $_POST['supp_id'];
    if($supp_name = '') { $supp_name = "NULL"; }else { $supp_name = $_POST['supp_id']; } 
$costPrice = $_POST['cost_price'];
$unitPrice = $_POST['unit_price'];
$tax1 = $_POST['tax1'];
$tax2 = $_POST['tax2'];
$quantity  = $_POST['quantity'];
$reorder = $_POST['reorder'];
$description = $_POST['description'];
$allow_alt_desc = $_POST['allow_alt_desc'];
$is_serialized = $_POST['is_serialized'];
echo $is_serialized;

$sql = mysqli_query($con,"insert into phppos_items_test (name,category,supplier_id,item_number,description,cost_price,unit_price,quantity,reorder_level,allow_alt_description,is_serialized) values ('".$name."','".$category."','".$supp_id."','".$itemNo."','".$description."','".$costPrice."','".$unitPrice."','".$quantity."','".$reorder."','".$allow_alt_desc."','".$is_serialized."') ");
if($sql)
{
    
}





if($sql)
{
    echo 'Done';
}
else
{
    echo 'error';
}

CloseCon($con);
?>