<?php 
include('config.php');
$bill_id=$_POST['bill_id']; 
//echo $bill_id."<br>";
$bill_date=$_POST['bill_date'];
$supp_id=$_POST['supp_id'];
//echo $supp_id."<br>";

$myitemid=$_POST['myitemid']; //name of item
//print_r($myitemid);
$item_cat=$_POST['item_cat'];
//echo $item_cat."<br>"; //cat of item
$item_no=$_POST['item_no']; 
//echo $item_no."<br>";//Number of item
$cprice=$_POST['cprice'];
//print_r($cprice)."<br>";
$uprice=$_POST['uprice'];
//echo $uprice."<br>";
$qty=$_POST['qty'];
$totalqty=$_POST['totalqty'];
$totalamt=$_POST['totalamt'];
$payamt=$_POST['payamt'];
$distype=$_POST['distype'];
$discount=$_POST['per'];

$errors=0;

$begin=mysql_query("BEGIN;");
// -----------Insert data phppos_purchase table-----------------
//echo "INSERT INTO `phppos_purchase`(`pur_id`, `bill_id`, `supp_id`, `date`, `totalqty`, `totalamt`, `outstanding`, `discount`, `payamt`, `dis_type`) VALUES ('','$bill_id','$supp_id',STR_TO_DATE('".$bill_date."','%d/%m/%Y'),'$totalqty','$totalamt','$payamt','$discount','$payamt','$distype')";

$qrypur=mysql_query("INSERT INTO `phppos_purchase`(`pur_id`, `bill_id`, `supp_id`, `date`, `totalqty`, `totalamt`, `outstanding`, `discount`, `payamt`, `dis_type`) VALUES ('','$bill_id','$supp_id',STR_TO_DATE('".$bill_date."','%d/%m/%Y'),'$totalqty','$totalamt','$payamt','$discount','$payamt','$distype')");

if($qrypur)
 {
$pur_id=mysql_insert_id();
 for($i=0;$i<count($myitemid);$i++)
   {  
         $res=mysql_query("select * from phppos_items where name='".$myitemid[$i]."' ");
         $row = mysql_fetch_row($res); 
         
    if(mysql_num_rows($res)>0){
 // -----------Insert data phppos_items table-----------------
    //echo "INSERT INTO `phppos_items`(`name`, `category`, `supplier_id`, `item_number`, `description`, `cost_price`, `unit_price`, `quantity`) VALUES ('".$myitemid[$i]."-".$item_no[$i]."','".$item_cat[$i]."', '".$supp_id."','".$item_no[$i]."' , STR_TO_DATE('".$bill_date."','%d/%m/%Y'), '".$cprice[$i]."', '".$uprice[$i]."', '".$qty[$i]."')";
   
   $qryitm=mysql_query("INSERT INTO `phppos_items`(`name`, `category`, `supplier_id`, `item_number`, `description`, `cost_price`, `unit_price`) VALUES ('".$myitemid[$i]."-".$item_no[$i]."','".$item_cat[$i]."', '".$supp_id."','".$item_no[$i]."' , '".$bill_date."', '".$cprice[$i]."', '".$uprice[$i]."' )");
    }else{
  // echo "INSERT INTO `phppos_items`(`name`, `category`, `supplier_id`, `item_number`, `description`, `cost_price`, `unit_price`, `quantity`) VALUES ('".$myitemid[$i]."','".$item_cat[$i]."', '".$supp_id."','".$item_no[$i]."' , STR_TO_DATE('".$bill_date."','%d/%m/%Y'), '".$cprice[$i]."', '".$uprice[$i]."', '".$qty[$i]."')";
   
   $qryitm=mysql_query("INSERT INTO `phppos_items`(`name`, `category`, `supplier_id`, `item_number`, `description`, `cost_price`, `unit_price`) VALUES ('".$myitemid[$i]."','".$item_cat[$i]."', '".$supp_id."','".$item_no[$i]."' ,'".$bill_date."', '".$cprice[$i]."', '".$uprice[$i]."')");
   }
  
  // -----------Insert data phppos_purchase_details table-----------------  
   $myautoid=mysql_insert_id();
   $det=mysql_query("INSERT INTO `phppos_purchase_details`(`id`, `pur_id`, `item_id`, `qty`, `price`) VALUES ('','$pur_id', '".$myautoid."', '$qty[$i]', '$cprice[$i]')");			
	
	if(!$qryitm || !$det)
	$errors++;
  }
  
 }
 else
 $errors++;
	if($errors==0){
	
	mysql_query("COMMIT;");
	echo " <center>Entries Done Successfully.<br> <<==<a href='purchase_entry.php'>GO BACK <<==</a> <a href='../../../index.php/purchase'>HOME <<==</a> <a href=''>BACK PURCHASE</a> </center>";
	if(!$det){
		$err=mysql_error();
		//header('Location:addlead.php?err='.$err);
		}		
	
		}else{
			mysql_query("ROLLBACK;");
			echo "Error in Insering  ".mysql_error()."<a href='purchase_entry.php'>GO BACK <<==</a>";
			}
	
	
	
?>