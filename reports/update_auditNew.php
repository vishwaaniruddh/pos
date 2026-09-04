<script type="text/javascript">     
        function PrintDiv() {    
           var divToPrint = document.getElementById('bill');
           var popupWin = window.open('', '_blank', 'width=800,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
            popupWin.document.close();
                }

</script>
<?php $bill_date=$_POST['bill_date'];

    $sumorg=0;
    $sumqty=0;
    $sumtqty=0;


?>
<center>
<a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="/auditReportNew.php">Back</a><br><br>
<div id="bill">
<img src="bill.png" width="408" height="165"/><br/><br/>
<h2><U> AUDIT ENTRY</U></h2><br>

 Audit Date : <?php echo $bill_date; ?><br><br>
 
 <table width="801" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
    <th width="39"><font size="2">SR NO.</font></th>
    <th width="91"><font size="2">BARCODE</font></th>
    <th width="91"><font size="2">ITEM NAME</font></th>
    <th width="105"><font size="2">ORIGINAL QTY</font></th>
    <th width="148"><font size="2"> QTY</font></th>
     <th width="81"><font size="2"> TOTAL QTY</font></th>
  </tr>
   
<?php
 
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$barc=$_POST['barc'];
$design=$_POST['design'];
$qty=$_POST['qty'];
$org_qty=$_POST['org_qty'];
$category=$_POST['categry'];
$delid=$_POST['delid'];

date_default_timezone_set('Asia/Kolkata');
$currdt = date('Y-m-d H:i:s');


$c=count($design);
$j=1;
$selectdata=mysqli_query($con,"select * from auditNew ");
 $num =mysqli_num_rows($selectdata);
for($i=0;$i<$c;$i++)
{
	$sum=$org_qty[$i]-$qty[$i];
 
	if($num<=0)
	{
       $result1 = mysqli_query($con,"insert into `auditNew` (item_id,barcode,qty,org_qty,audit_date,current_dt,category) values ('$design[$i]','$barc[$i]','$qty[$i]','$org_qty[$i]','".$bill_date."','".$currdt."','".$category."')");
    }
    else
    {
        $fetdata= mysqli_fetch_array($selectdata);
        
   $result = mysqli_query($con,"update auditNew set item_id='".$design[$i]."',barcode='".$barc[$i]."',qty='".$qty[$i]."',org_qty='".$org_qty[$i]."',audit_date='".$bill_date."',current_dt='".$currdt."',category='".$category."' where id='".$fetdata['id']."'  ");
  //echo "update auditNew set item_id='".$design[$i]."',barcode='".$barc[$i]."',qty='".$qty[$i]."',org_qty='".$org_qty[$i]."',audit_date='".$bill_date."',current_dt='".$currdt."',category='".$category."' where id='".$fetdata['id']."'  ";
    }
        
   

//$result = mysqli_query($con,"update auditNew set item_id='".$design[$i]."',barcode='".$barc[$i]."',qty='".$qty[$i]."',org_qty='".$org_qty[$i]."',audit_date='".$bill_date."',current_dt='".$currdt."',category='".$category."' where  ");
?>
<tr>
    <td width="39"><?php echo $j++; ?></td>
    <td width="91"><?php echo $design[$i]; ?></td>
    <td width="91"><?php echo $barc[$i]; ?></td>
    <td width="81"><?php echo $org_qty[$i]; $sumorg+=$org_qty[$i]; ?></td>
    <td width="107"><?php echo $qty[$i]; $sumqty+=$qty[$i];?></td>
    <td width="105"><?php echo $sum; $sumtqty+=$sum;?></td>
    
  </tr>
  

<?php 
}
?>
<tr>
<td colspan="3" align="right">Total : </td>
<td><?php echo $sumorg; ?></td>
<td><?php echo $sumqty; ?></td>
<td><?php echo $sumtqty; ?></td>
</tr>
</table>
</div>
</center>

<?php 
if($delid==1)
{
   /* $insertdata=mysqli_query($con,"select * from auditNew ");
    while($inserdata= mysqli_fetch_array($insertdata))
    {
        mysqli_query($con,"insert into finalAuditNew (item_id,barcode,qty,org_qty,audit_date,current_dt,category) values('".$inserdata[0]."','".$inserdata[1]."','".$inserdata[2]."','".$inserdata[3]."','".$inserdata[4]."','".$inserdata[5]."','".$inserdata[6]."')");
    }*/
    
    
    
    
    mysqli_query($con,"INSERT INTO finalAuditNew(item_id,barcode,qty,org_qty,audit_date,current_dt,category) SELECT item_id,barcode,qty,org_qty,audit_date,current_dt,category FROM auditNew");
    
    
    
    
    
    // $check_sql = mysqli_query($con,"SELECT * FROM auditNew");
    // while($check_sql_result = mysqli_fetch_assoc($check_sql)){
    //     $sku = $check_sql_result['item_id'];
    //     $qty = $check_sql_result['qty'];
        
        // $get_sql = mysqli_query($con,"SELECT * FROM `phppos_items` where name like '".$sku."'");
        // $get_sql_result = mysqli_fetch_assoc($get_sql);
        
        // $pos_qty  = $get_sql_result['quantity'];
        // $new_qty =  $qty ; 
        

        // mysqli_query($con,"update phppos_items set quantity = '".$new_qty."' where name like '".$sku."'");
    // }
    $deleteAuditNew=mysqli_query($con,"TRUNCATE TABLE auditNew;");
}
?>
<?php CloseCon($con);?>


