<?php
include('../db_connection.php') ;
$con3=OpenPurchaseSrishringarrCon();

function update_items_showing($product_name)
{
// 	$con3=mysqli_connect("198.38.84.10", "satyavan_pos123", "Mypos1234","satyavan_pos");

	$i=1;
	$lowestsp=0;
	$lowestid=0;
	$re = mysqli_query($con3,"SELECT unit_price,cost_price,quantity,description,name,item_id FROM phppos_items where name like '".$product_name."-%' order by item_id");
	while($rero=mysqli_fetch_array($re))
	{
		if($rero['quantity']>0)
		{
		    $re1 = mysqli_query($con3,"select sum(commission_amt) from order_detail where item_id='".$rero['name']."' and bill_id in(select bill_id from phppos_rent where booking_status!='Booked')");
		    $rero1=mysqli_fetch_row($re1);  	   
		     $currentsp=$rero[0]-$rero1[0];
		     $splimit=$rero[1]*0.8;
		    if($currentsp>$splimit)
		    $newsp=$currentsp;
		    else
		    $newsp=$splimit;
		    if($i==1)
		    {
		    	$lowestsp=$newsp;
		    	$lowestid=$rero['item_id'];
		    }
		    else
		    {
		    	if($lowestsp>$newsp)
		    	{
			    	$lowestsp=$newsp;
			    	$lowestid=$rero['item_id'];	    		
		    	}
		    }	    
		    $i++;
	    	}
	}
	echo "Id: ".$lowestid." Price: ".$lowestsp;
	if($lowestid!=0)
	{
		mysqli_query($con3,"update phppos_items set showing='n' where name like '".$product_name."-%'");
		mysqli_query($con3,"update phppos_items set showing='y' where item_id='".$lowestid."'");
	}
}
CloseCon($con3);
?>