<?php
include('config.php');
$bid=$_GET['bid'];
$qry=mysql_query("select * from order_detail where bill_id='$bid'");
mysql_query("update `phppos_rent` set booking_status='Picked' where bill_id='$bid'");
while($row=mysql_fetch_row($qry)){

mysql_query("update `phppos_items` set quantity=quantity-$row[9] WHERE name='".$row[1]."'");
}
header('location:http:../../../index.php/home');
?>