<?php 
include('config.php');
include('items_showing_calculate.php');
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
$srno=$_POST['srno'];
//echo $srno."<br>";
$qty=$_POST['qty'];
//print_r($qty);
$mycont=array();
$mycont=$qty;
//echo $mycont;
$totalqty=$_POST['totalqty'];
$totalamt=$_POST['totalamt'];
$payamt=$_POST['payamt'];
$distype=$_POST['distype'];
$discount=$_POST['per'];

$errors=0;
$myautoid=array();
$begin=mysql_query("BEGIN;");
// -----------Insert data phppos_purchase table-----------------
//echo "INSERT INTO `phppos_purchase`(`pur_id`, `bill_id`, `supp_id`, `date`, `totalqty`, `totalamt`, `outstanding`, `discount`, `payamt`, `dis_type`) VALUES ('','$bill_id','$supp_id',STR_TO_DATE('".$bill_date."','%d/%m/%Y'),'$totalqty','$totalamt','$payamt','$discount','$payamt','$distype')";

$qrypur=mysql_query("INSERT INTO `phppos_purchase`(`pur_id`, `bill_id`, `supp_id`, `date`, `totalqty`, `totalamt`, `outstanding`, `discount`, `payamt`, `dis_type`) VALUES ('','$bill_id','$supp_id',STR_TO_DATE('".$bill_date."','%d/%m/%Y'),'$totalqty','$totalamt','$payamt','$discount','$payamt','$distype')");
if($qrypur)
 {
//echo "1";
$pur_id=mysql_insert_id();
 for($i=0;$i<count($myitemid);$i++){
        // echo "select * from phppos_items where name like '".$myitemid[$i]."%' <br>";
         $res=mysql_query("select * from phppos_items where name like '".$myitemid[$i]."-%'");
         $row = mysql_fetch_row($res); 
         
         $det=mysql_query("INSERT INTO `phppos_purchase_details`(`id`, `pur_id`, `item_id`, `qty`, `price`) VALUES ('','$pur_id', '".$myitemid[$i]."', '$qty[$i]', '$cprice[$i]')");
         if(!$det){
		$errors++;
	}
	else
	{
	//echo "2";
         $pd_id=mysql_insert_id();
if(mysql_num_rows($res)>0){
		//echo "select max(srno) from `phppos_items` where `name` like '".$myitemid[$i]."%'<br>";
		$maxsrno=mysql_query("select max(srno) from `phppos_items` where `name` like '".$myitemid[$i]."-%'");
		$maxsrno1=mysql_fetch_row($maxsrno);
		//echo $mycont[$i]."<br>";	
 // -----------=============Insert data phppos_items table-----------------==========================
 for($j=0;$j<$mycont[$i];$j++){
	$addnum=$maxsrno1[0]+$j+1;
 	$qryitm=mysql_query("INSERT INTO `phppos_items`(`name`, `category`, `supplier_id`, `item_number`, `description`, `cost_price`, `unit_price`,`quantity`,`srno`,`pd_id`) VALUES ('".$myitemid[$i]."-".$addnum."','".$item_cat[$i]."', '".$supp_id."','".$item_no[$i].$addnum."','".$bill_date."', '".$cprice[$i]."', '".$uprice[$i]."','1','".$addnum."','".$pd_id."')");
 	if(!$qryitm)
 	{
		$errors++;
		echo mysql_error();
	}
	//else
	//echo "3";
		}
    }else{
		//
for($j=0;$j<$mycont[$i];$j++){	
	$qryitm=mysql_query("INSERT INTO `phppos_items`(`name`, `category`, `supplier_id`, `item_number`, `description`, `cost_price`, `unit_price`,`quantity`,`srno`,`pd_id`) VALUES ('".$myitemid[$i]."-".($j+1)."','".$item_cat[$i]."', '".$supp_id."','".$item_no[$i].($j+1)."' ,'".$bill_date."', '".$cprice[$i]."', '".$uprice[$i]."','1','".($j+1)."','".$pd_id."')");
	if(!$qryitm)
	$errors++;
	//else
	//echo "4";
	 }
   }
   mysql_query("update phppos_purchase_details set item_id='".mysql_insert_id()."' where id='".$pd_id."'");
   }
  }
  
 }
 else
 $errors++;
	if($errors==0){
	
	mysql_query("COMMIT;");
	for($i=0;$i<count($myitemid);$i++){
		update_items_showing($myitemid[$i]);	
	}
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