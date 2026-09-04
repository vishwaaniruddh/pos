<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$it=$_GET['barcode'];
$bar=$_GET['barcode2'];
//echo $it."/".$bar;

       if($bar==""){
      // echo "1";
       $barcode=$_GET['barcode'];
       
 $qry="SELECT * FROM `order_detail` where `item_id`='$barcode' and bill_id in(SELECT bill_id  FROM `phppos_rent` WHERE `pick_date` >= now() AND `delivery_date` >= now() ORDER BY `phppos_rent`.`pick_date` ASC) group by bill_id";
//$num=mysqli_num_rows($sql2);
//select `bill_id`,`pick_date`,`delivery_date` from `phppos_rent` where  `pick_date` >= now() AND `delivery_date` >= now() and bill_id in(select bill_id from `order_detail` where `item_id`='b1') 
       
}else if($it==""){
//echo "2";
 $barcode=$_GET['barcode2'];
  $qry="SELECT * FROM `order_detail` where `item_id`='$barcode' and bill_id in(SELECT bill_id  FROM `phppos_rent` WHERE `pick_date` >= now() AND `delivery_date` >= now() ORDER BY `phppos_rent`.`pick_date` ASC) group by bill_id";
}
//echo $qry;
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
if($num>0){	
$i=1;					 				 
?>

<table border="1"  width="43%" align="center"><tr>
<td align="center" colspan="5"><?php echo "<b>Item Name : ".strtoupper($barcode)."</b>";?></td></tr>
<tr>
<th width="61">Sr. No</th><th width="97">Bill No.</th><th width="125">Name</th><th width="160">Description</th><th width="125">Pick Date</th><th width="193">Delivery Date</th></tr>
<?php while($row=mysqli_fetch_row($res)){
		$qryrent=mysqli_query($con,"Select pick_date, delivery_date,cust_id from phppos_rent where bill_id='$row[0]'");
		$resrent=mysqli_fetch_row($qryrent);
		
		$gtds=mysqli_query($con,"select * from phppos_people where person_id='".$resrent[2]."'");
		$gtdsfr=mysqli_fetch_array($gtds);
		?>
        <tr align="center"><td><?php echo $i;?></td>
        
        <td><?php echo $row[0];?></td>
        <td><?php echo $gtdsfr[0]." ".$gtdsfr[1];?></td>
         <td><?php echo $row[7];?></td>
        <td><?php echo date('d-m-Y',strtotime($resrent[0]));?></td>
        
        <td><?php echo  date('d-m-Y',strtotime($resrent[1]));?></td></tr>
        <?php $i+=1;}?>
</table>
			  
               
		<?php }
		else
		echo "No Bookings";
		CloseCon($con);
		?>