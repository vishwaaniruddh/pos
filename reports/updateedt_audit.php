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
<a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="/app_return.php">Back</a><br><br>
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


$c=count($design);
$j=1;

for($i=0;$i<$c;$i++)
{
	$sum=$org_qty[$i]-$qty[$i];
//$result = mysqli_query($con,"insert into `audit` (item_id,barcode,qty,org_qty,audit_date) values ('$design[$i]','$barc[$i]','$qty[$i]','$org_qty[$i]',STR_TO_DATE('".$bill_date."','%d/%m/%Y'))");
//echo "update audit set qty='".$qty[$i]."',org_qty='".$org_qty[$i]."' where id='".$_POST[auid][$i]."'";
$result=mysqli_query($con,"update audit set qty='".$qty[$i]."',org_qty='".$org_qty[$i]."' where id='".$_POST[auid][$i]."'");
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
<?php CloseCon($con);?>
</center>