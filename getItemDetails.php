<?php

include('db_connection.php');

$item_name = $_GET['iname'];
$type = $_GET['type'];

$con = OpenSrishringarrCon();

if($con){
    //echo "success";
    //echo "select * from phppos_items where name='" . $item_name . "'";
    $res=mysqli_query($con,"select * from phppos_items where name='" . $item_name . "'");
    
    $row= mysqli_fetch_row($res);
    
    $bar = $row[3];
    
    $sid = $row[2];
    
    $dt = $row[4];
    
    $amt = $row[5];
    
    $val = $row[6];
    
    if($type=="1")
    echo $bar;
    else if($type == "2")
    {
      //  echo "select * from phppos_suppliers where person_id='" . $sid . "'";
    $res1=mysqli_query($con,"select * from phppos_suppliers where person_id='" . $sid . "'");
    $row1= mysqli_fetch_row($res1);
    
    $x = $row1[2];
    
    echo $sid.'@'.$dt.'@'.$amt.'@'.$val.'@'.$item_name.'@'.$x;
    }
    else if($type == "3")
    {
    $res1=mysqli_query($con,"select * from 0_stock_moves where stock_id='" . $item_name . "'");
    
    $row1= mysqli_fetch_row($res1);
    
    $x = $row1[9];
    
    $res2=mysqli_query($con,"select material_cost from 0_stock_master where stock_id='" . $item_name . "'");
    
    $row2= mysqli_fetch_row($res2);
    
    $y = $row2[0];
    
    $res3=mysqli_query($con,"select price from 0_prices where stock_id='" . $item_name . "'");
    
    $row3= mysqli_fetch_row($res3);
    
    $z = $row3[0];
    
    echo $x.'@'.$y.'@'.$z;
    
    }
    else if($type == "4")
    {
    $res1=mysqli_query($con,"select sum(commission_amt) from order_detail where item_id='" . $item_name . "' and bill_id in(select bill_id from phppos_rent where booking_status!='Booked')");
    
    $row1= mysqli_fetch_row($res1);
    
    $x = $row1[0];
    
    $res2=mysqli_query($con,"select * from phppos_suppliers where person_id='" . $sid . "'");
    $row2= mysqli_fetch_row($res2);
    
    $y = $row2[2];
    
    echo $sid.'@'.$dt.'@'.$amt.'@'.$val.'@'.$item_name.'@'.$x.'@'.$y;
    }
    
    mysqli_close($con);
}
else{
    echo "con error";
}
?>