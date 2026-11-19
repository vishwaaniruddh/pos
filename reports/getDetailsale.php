<?php
ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


       $id=$_GET['barcode'];

$batotal=0;
$rnttotal=0;
$nettotal=0;

$qry="SELECT * FROM  `approval` where cust_id='$id' and status='S' ";
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);

$s1=0;			
$pd=0;
$ba=0;
$na=0;	
$ra=0;			 				 
?>
<script type="text/javascript">     
        function PrintDiv() {    
           var divToPrint = document.getElementById('bill');
           divToPrint.style.fontSize = "10px";
           var popupWin = window.open('', '_blank', 'width=800,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
            popupWin.document.close();
                }

       
     </script>
     
<table width="795"><tr><td width="281"><a href="Sale_detail1.php?id=<?php echo $id; ?>"><font style="font-size:18px;"><B>Sale's Return</B></font></a></td>
<td width="260"><a href="sold_qty.php?id=<?php echo $id; ?>"></a></td>

<td width="238"><a href="payapp_detail.php?id=<?php echo $id; ?>" target="_new"><font style="font-size:18px;"><B>Paid Amount Detail</B></font></a></td></tr></table><br/>
<br/>
<table  border="1" cellpadding="4" cellspacing="0" width="792" align="left" id="bill">
 <tr>
 <th width='75' height="34"><U>Sr.No.</U></th>
    <th width='75' height="34"><U>Bill No.</U></th>
    <th width='176'><u>Customer Name</u></th>
    <th width='105'><U>Bill Date</U></th>
    <!--<th width='103'><U>Paid Amount</U></th>-->
    <th width='136'><U>Amount</U></th>
   <th width='136'><U>Return Amount</U></th>
    <th width='136'><U>Net Amount</U></th>
    <th width='135'><U>sale Detail</U></th>
       <th width='135'><U>Bill Delete</U></th>
  </tr>
<?php 
$i=1;
while($row = mysqli_fetch_row($res)) 
 {
	 
$sql1=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$row[1]'");
$row1=mysqli_fetch_row($sql1);
/// echo $s1."ss<br/>";
///echo $row[0]."<br/>";
 $qry2="SELECT sum(paid_amount) FROM  `approval` where bill_id ='$row[0]' and status='S' ";
$res2=mysqli_query($con,$qry2);                
$num2=mysqli_num_rows($res2);
$row2=mysqli_fetch_row($res2);
			
$qry3="SELECT sum(`amount`) FROM `approval_detail` WHERE bill_id ='$row[0]'";
$res3=mysqli_query($con,$qry3);
$row3=mysqli_fetch_row($res3);
$a=0;
$a1=0;
$qry4="SELECT *  FROM `approval_detail` WHERE bill_id ='$row[0]'";
$res4=mysqli_query($con,$qry4);

while($row4=mysqli_fetch_row($res4)){

$a=round(($row4[7]/$row4[2])*$row4[4]);
$a1+=$a;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba+=$row4[7];
}

$pd+=$row[4];
$na=$ba-$pd;
$s1=$ba-$a1-$pd;
//echo "cust=".$row[11]."paidamt=".$pd."bal amt=".$na."return".$a1."net amt=".$s1."<br/>";
$s=$row3[0]-$row2[0];
//echo $row2[0]."&&".$s;
?>				   
				   
<tr>

<td width="75"><?php echo $i; ?></td>
<td width="75"><?php echo $row[0]; ?></td>
<td width="176" align="center"><?php echo $row1[0]." " .$row1[1]; ?></td>
<td width="105"> <?php if(isset($row[2]) and $row[2]!='0000-00-00') echo date('d/m/Y',strtotime($row[2])); ?></td>
<!--<td width="103"><?php //echo $row2[0]; $pd+=$row2[0]; ?></td>-->
<td width="136"><?php echo $na; $batotal+=$na;
?></td>
 <th width='136'><?php echo $a1; $rnttotal+=$a1;
?></th>
  <th width='136'><?php echo $s1; 

$nettotal+=$s1; 
  ?></th><?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
 <td  align="left" width="135"><a href="get_sale.php?id=<?php echo $row[0]; ?>" target="_new">Return Detail</a></td>
 <th width='135'><a href="delete_sale.php?id=<?php echo $row[0]; ?>"><U>Bill Delete</U></a></th>
     </tr>
				
			<?php $i++; }  ?>
			<tr>
<td colspan="4" align="right"><b>Total :</b></td>
<!--<td width="103"><?php ///echo $pd; ?></td>-->
<td width="136"><?php echo $batotal ?></td>
 <th width='136'><?php echo $rnttotal; ?></th>
  <th width='136'><?php echo $nettotal; ?></th><?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
 <td  align="left" width="135"></td>
     </tr>
</table>
<?php CloseCon($con);?>		  
               