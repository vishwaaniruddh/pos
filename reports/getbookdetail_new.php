<?php
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$it=$_REQUEST['barcode'];
$bar=$_REQUEST['barcode2'];
if($_REQUEST['fromdate']!=""){
  $_fromdatearr = explode("/",$_REQUEST['fromdate']);
  $fromdate= $_fromdatearr[2]."-".$_fromdatearr[1]."-".$_fromdatearr[0];
}
//$todate = $_REQUEST['todate'];


// var_dump($_REQUEST);

if($_REQUEST['todate']!=""){
    $_todatearr = explode("/",$_REQUEST['todate']);
    $todate= $_todatearr[2]."-".$_todatearr[1]."-".$_todatearr[0];
}


// var_dump($_REQUEST);

// Retrieve and sanitize input
$barcode = isset($_REQUEST['barcode']) && $_REQUEST['barcode'] !== '' ? $_REQUEST['barcode'] : (isset($_REQUEST['barcode2']) && $_REQUEST['barcode2'] !== '' ? $_REQUEST['barcode2'] : '');
$fromdate = isset($_REQUEST['fromdate']) && $_REQUEST['fromdate'] !== '' ? date('Y-m-d', strtotime($_REQUEST['fromdate'])) : '';
$todate = isset($_REQUEST['todate']) && $_REQUEST['todate'] !== '' ? date('Y-m-d', strtotime($_REQUEST['todate'])) : '';

// Initialize the base query
$qry = "SELECT a.*, b.pick_date as rentpickdate, b.delivery_date as rentdeliverydate,b.new_bill_number FROM `order_detail` a 
        INNER JOIN phppos_rent b ON a.bill_id = b.bill_id";

// Build conditions based on input
$conditions = [];

if ($barcode !== '') {
    $conditions[] = "a.item_id = '$barcode'";
}

if ($fromdate !== '' && $todate !== '') {
    // Both fromdate and todate are provided
    $conditions[] = "b.pick_date BETWEEN '$fromdate' AND '$todate'";
} elseif ($fromdate !== '') {
    // Only fromdate is provided
    $conditions[] = "b.pick_date >= '$fromdate'";
} elseif ($todate !== '') {
    // Only todate is provided
    $conditions[] = "b.pick_date <= '$todate'";
}

// Append conditions to the query if any are set
if (count($conditions) > 0) {
    $qry .= " WHERE " . implode(' AND ', $conditions);
}

// Finalize the query
$qry .= " AND b.pick_date >= CURDATE()
 GROUP BY a.bill_id ORDER BY b.pick_date";

// echo $qry ; 

$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
if($num>0){	
$i=1;					 				 
?>

<table border="1"  width="43%" align="center">
    <?php if($barcode!=""){?>
    <tr>
<td align="center" colspan="8"><?php echo "<b>Item Name : ".strtoupper($barcode)."</b>";?></td></tr>
<?php }?>
<tr>
<th width="61">Sr. No</th>
<?php if($barcode==""){?>
<th width="97">Item ID</th>
<?php }?>
<th width="97">Bill No.</th><th width="125">Customer Name</th><th width="160">Contact No.</th><th width="160">Description</th><th width="125">Pick Date</th><th width="193">Delivery Date</th><th width="160">Trail Date</th><th width="125">Measurement</th><th width="193">Is Delivery</th></tr>
<?php while($row=mysqli_fetch_array($res)){
    
    $new_bill_number = $row['new_bill_number'];
		$qryrent=mysqli_query($con,"Select pick_date, delivery_date,cust_id,trail_date,measurement,is_delivery from phppos_rent where bill_id='$row[0]'");
		$resrent=mysqli_fetch_row($qryrent);
		
		$gtds=mysqli_query($con,"select * from phppos_people where person_id='".$resrent[2]."'");
		$gtdsfr=mysqli_fetch_array($gtds);
		
		$bill=mysqli_query($con,"SELECT * FROM `order_detail` where bill_id='$row[0]'");
		?>
        <tr align="center"><td><?php echo $i;?></td>
        <?php if($barcode==""){?>
       <!-- <td><?php //echo $row[1];?></td>-->
        <td>
           <?php while($bill1 = mysqli_fetch_array($bill)){
            $item=$bill1[1];
            $item1=mysqli_query($con,"SELECT * FROM `phppos_items` where name='$item'");
            $item2=mysqli_fetch_row($item1);?>
            <?php echo $item2[0]."--".$bill1[9]."<br>"; } ?>
        </td>
        <?php }?>
        <td><?php echo $new_bill_number ? $new_bill_number : $row[0];?></td>
        <td><?php echo $gtdsfr[0]." ".$gtdsfr[1];?></td>
        <td><?php echo $gtdsfr[2];?></td>
         <td><?php echo $row[7];?></td>
         
         
        <td style="white-space:nowrap;"><?php if($resrent[0]=='') { echo $resrent[0]; } else { echo date('d-m-Y',strtotime($resrent[0]));} ?></td>
        
        
        
        
        
        
        <td style="white-space:nowrap;"><?php if($resrent[1]=='0000-00-00') { echo $resrent[1];} else { echo date('d-m-Y',strtotime($resrent[1])); }?></td>
        
        <td style="white-space:nowrap;"><?php if($resrent[3]=='0000-00-00') { echo $resrent[3];} else{ echo  date('d-m-Y',strtotime($resrent[3]));} ?></td>
        
         <td><?php echo $resrent[4];?></td>
          <td><?php echo $resrent[5];?></td>
        </tr>
        <?php $i+=1;}?>
</table>
			  
               
		<?php }
		
		else
		echo "No Bookings"; 
		 CloseCon($con);
		?>