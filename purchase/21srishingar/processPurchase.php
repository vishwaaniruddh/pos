<?php 
// include('config.php');
include('../../db_connection.php') ;
$con=OpenSrishringarrCon();


$bill_id=$_POST['bill_id'];
$bill_date=$_POST['bill_date'];
$supp_id=$_POST['supp_id'];
$item_id=$_POST['item_id'];
$price=$_POST['price'];
$qty=$_POST['qty'];
$totalqty=$_POST['totalqty'];
$totalamt=$_POST['totalamt'];
$payamt=$_POST['payamt'];
$distype=$_POST['distype'];
$discount=$_POST['per'];

//echo "INSERT INTO `phppos_purchase`(`pur_id`, `bill_id`, `supp_id`, `date`) VALUES ('','$bill_id','$supp_id',STR_TO_DATE('".$bill_date."','%d/%m/%Y'))";
$qrypur=mysqli_query($con,"INSERT INTO `phppos_purchase`(`pur_id`, `bill_id`, `supp_id`, `date`, `totalqty`, `totalamt`, `outstanding`, `discount`, `payamt`, `dis_type`) VALUES ('','$bill_id','$supp_id',STR_TO_DATE('".$bill_date."','%d/%m/%Y'),'$totalqty','$totalamt','$payamt','$discount','$payamt','$distype')");
if($qrypur)
{
$pur_id=$_POST['pur_id'];
//echo count($item_id);
for($i=0;$i<count($item_id);$i++)
    {
	//echo "\nINSERT INTO `phppos_purchase_details`(`id`, `pur_id`, `item_id`, `qty`, `price`) VALUES ('','$pur_id','$item_id[$i]','$qty[$i]','$price[$i]')";
	$det=mysqli_query($con,"INSERT INTO `phppos_purchase_details`(`id`, `pur_id`, `item_id`, `qty`, `price`) VALUES ('','$pur_id','$item_id[$i]','$qty[$i]','$price[$i]')");			
    }
echo " <center>Entries Done Successfully.<br> <<==<a href='purchase_entry.php'>GO BACK <<==</a> <a href='/pos/home_dashboard.php'>HOME <<==</a> <a href=''>BACK PURCHASE</a> </center>";

}
else
{
	echo "Error in Insering  ".mysqli_error()."<a href='purchase_entry.php'>GO BACK <<==</a>";
	CloseCon($con);
	}
?>