<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


 $res=mysqli_query($con,"select * from phppos_items where name='".$_POST["nm"]."'  and is_deleted = 0   ");
         
         $nrws=mysqli_num_rows($res);
		
if($nrws>0)
{
    
    $fr=mysqli_fetch_array($res);
    $itemnum=$fr["item_number"];
    $category=$fr["category"];
    $costprice=$fr["cost_price"];
    $unitprice=$fr["unit_price"];
    $qty=$fr["quantity"];
}
else
{
    
   
   $resf =mysqli_query($con,"select item_number from phppos_items where item_id=(select max(item_id) from phppos_items)");
         $rowf = mysqli_fetch_row($resf);
         $itemnum = $rowf[0];

}
$data=array();
$data=["numrows"=>$nrws,"item_num"=>$itemnum,"category"=>$category,"costprice"=>$costprice,"unitprice"=>$unitprice,"qty"=>$qty];

echo json_encode($data);
CloseCon($con);
?>