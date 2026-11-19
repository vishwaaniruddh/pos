<?php 
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$bill_id=$_POST['bill_id']; 
//echo $bill_id."<br>";
$bill_date=$_POST['bill_date'];
//echo $bill_date;
$supp_id=$_POST['supp_id'];
//echo $supp_id."<br>";
$item_no=$_POST['item_no']; 
//echo $item_no."<br>";//Number of item
///////////////////////////////////////////////////////////////////////////////

$myitemid=$_POST['item']; //name of item
//print_r($myitemid);
$item_cat=$_POST['item_cat'];
//echo $item_cat."<br>"; //cat of item
$cprice=$_POST['cprice'];
//print_r($cprice)."<br>";
$sprice=$_POST['sprice'];
//echo $sprice."<br>";
$qty=$_POST['qty'];
//print_r($qty)."<br>";

$totalqty=$_POST['totalqty'];
//echo "totalqty=". $totalqty."<br>";
$totalamt=$_POST['totalamt'];
//echo "totalamt=".$totalamt."<br>";
$payamts=$_POST['payamts'];
//echo "payamts=".$payamts."<br>";
$distype=$_POST['distype'];
//echo "distype=".$distype."<br>";
$discount=$_POST['per'];
//echo "discount=".$discount."<br>";

 for($i=0;$i<count($myitemid);$i++)
   { 
    $qqry=mysqli_query($con,"select qty from `phppos_purchase_details` where id='".$_POST['purai_id'][$i]."' ");	
    $qrow=mysqli_fetch_row($qqry);

    $bal=$qty[$i]-$qrow[0];

    // -----------Insert data phppos_items table-----------------   
       //echo "update `phppos_items` set `name`='".$myitemid[$i]."', `category`='".$item_cat[$i]."', `cost_price`='".$cprice[$i]."', `unit_price`='".$sprice[$i]."' where item_id='".$_POST['itmai_id'][$i]."' .<br>";
   
   $qryitm=mysqli_query($con,"update `phppos_items` set `name`='".$myitemid[$i]."', `category`='".$item_cat[$i]."', `cost_price`='".$cprice[$i]."', `unit_price`='".$sprice[$i]."', quantity=quantity+".$bal." where item_id='".$_POST['itmai_id'][$i]."'");
     
  // -----------Insert data phppos_purchase_details table-----------------  
   
  // echo "update `phppos_purchase_details` set `qty`='".$qty[$i]."', `price`='".$cprice[$i]."' where id='".$_POST['purai_id'][$i]."' <br>"; 
   $qryitm=mysqli_query($con,"update `phppos_purchase_details` set `qty`='".$qty[$i]."', `price`='".$cprice[$i]."' where id='".$_POST['purai_id'][$i]."' ");	
  }
  
    $err=array();
  if($qryitm)
      {
	 
	  }else{
		  
		  }
  // -----------Insert data phppos_purchase table-----------------  
  //echo "update `phppos_purchase` set `totalqty`='".$totalqty."', `totalamt`='".$totalamt."', `discount`='".$discount."', `payamt`='".$payamts."', `dis_type`='".$distype."' where pur_id='".$supp_id."' <br>";
  
  if($qryitm)
  {
  	//$purchase_payment_qry=mysql_query("select sum(amt) from `phppos_purchase_payments` where bill_no='".$supp_id."'");
  	//$purchase_payment=mysql_fetch_array($purchase_payment_qry);	
  	$qry_purchase=mysqli_query($con,"select * from `phppos_purchase` where  pur_id='".$supp_id."'");
  	$row_purchase=mysqli_fetch_array($qry_purchase);
  	$outstanding=$row_purchase['outstanding']+($totalamt-$row_purchase['totalamt']);
  	$tabpur=mysqli_query($con,"update `phppos_purchase` set `totalqty`='".$totalqty."', `totalamt`='".$totalamt."', `discount`='".$discount."', `payamt`='".$payamts."', `dis_type`='".$distype."',`outstanding`='".$outstanding."' where pur_id='".$supp_id."'");
  }
  
  
  echo " <center>Entries Done Successfully.<br> <<==<a href='view_bills.php'>GO BACK <<==</a> <a href='/pos/home_dashboard.php'>HOME <<==</a> <a href=''>BACK PURCHASE</a> </center>";
  if(!$qryitm and !$tabpur)
  echo mysqli_error($con);
        
CloseCon($con);
?>