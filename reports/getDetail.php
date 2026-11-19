<?php
ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


       $id=$_GET['barcode'];

$batotal=0;
$rnttotal=0;
$nettotal=0;

$qry="SELECT * FROM  `approval` where cust_id='$id' and status='A'";
$qry_new="SELECT * FROM  `approval` where cust_id='$id' and status='S'";
$res=mysqli_query($con,$qry);                
$res_new=mysqli_query($con,$qry_new);
$num=mysqli_num_rows($res);

		 				 
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
	<table width="795">
		<tr>
			<td width="281"><a href="app_detail1.php?id=<?php echo $id; ?>"><font style="font-size:18px;"><B>Approval Return</B></font></a></td>
			<td width="260"><a href="sold_qty.php?id=<?php echo $id; ?>"><font style="font-size:18px;"><B>Sold Qty</B></font></a></td>
			<td width="238"><a href="payapp_detail.php?id=<?php echo $id; ?>" target="_new"><font style="font-size:18px;"><B>Paid Amount Detail</B></font></a></td>
		</tr>
	</table>
	<br/>
	<br/>
	<table border="1" cellpadding="4" cellspacing="0" width="792" align="left" id="bill">
		<tr>
			<th width='75' height="34">
				<U>Sr.No.</U>
			</th>
			<th width='75' height="34">
				<U>Bill No.</U>
			</th>
			<th width='176'><u>Customer Name</u></th>
			<th width='105'>
				<U>Bill Date</U>
			</th>
			<th width='105'>
				<U>Bill Amount</U>
			</th>
			<!--<th width='103'><U>Paid Amount</U></th>
    <th width='136'><U>Balance Amount</U></th>-->
			<th width='136'>
				<U>Return Amount</U>
			</th>
			<th width='136'>
				<U>Net Amount</U>
			</th>
			<th width='135'>
				<U>Return Detail</U>
			</th>
			<th width='135'>
				<U>Bill Delete</U>
			</th>
		</tr>
		<?php 
$i=1;
while($row = mysqli_fetch_array($res)) 
 {
	 $s1=0;			
$pd=0;
$ba=0;
$na=0;	
$ra=0;	
$sql1=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$row[1]'");
$row1=mysqli_fetch_row($sql1);
/// echo $s1."ss<br/>";
///echo $row[0]."<br/>";
 $qry2="SELECT sum(paid_amount) FROM  `approval` where bill_id ='$row[0]' and status='A' ";
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

$pd=$row[4];
$na=$ba;
$s1=$ba-$a1;
//echo "cust=".$row[11]."paidamt=".$pd."bal amt=".$na."return".$a1."net amt=".$s1."<br/>";
$s=$row3[0]-$row2[0];
//echo $row2[0]."&&".$s;
?>
			<tr>
				<td width="75">
					<?php echo $i; ?>
				</td>
				<td width="75">

					<?php echo $row['new_bill_number'] ? $row['new_bill_number'] : $row[0]; ?>
				</td>
				<td width="176" align="center">
					<?php echo $row1[0]." " .$row1[1]; ?>
				</td>
				<td width="105">
					<?php if(isset($row[2]) and $row[2]!='0000-00-00') echo date('d/m/Y',strtotime($row[2])); ?>
				</td>
				<th width='105'>
					<?php echo $ba; ?>
				</th>
				<!--<td width="103"><?php //echo $row2[0]; $pd+=$row2[0]; ?></td>
                    <td width="136"><?php echo $na; $batotal+=$ba;
                    ?></td>-->
				<th width='136'>
					<?php echo $a1; $rnttotal+=$a1;
                        ?>
				</th>
				<th width='136'>
					<?php echo $s1; 

$nettotal+=$s1; 
  ?>
				</th>
				<?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
					<td align="left" width="135"><a href="app_detail.php?id=<?php echo $row[0]; ?>" target="_new">Return Detail</a></td>
					<th width='135'><a href="javascript:confirm_delete(<?php echo $row[0]; ?>);"><U>Bill Delete</U></a></th>
			</tr>
			<?php $i++; }  ?>
				<tr>
					<td colspan="4" align="right"><b>Total :</b></td>
					<!--<td width="103"><?php ///echo $pd; ?></td>-->
					<td width="136">
						<?php echo $batotal ?>
					</td>
					<th width='136'>
						<?php echo $rnttotal; ?>
					</th>
					<th width='136'>
						<?php echo $nettotal; ?>
					</th>
					<?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
						<td align="left" width="135"></td>
						<td align="left" width="135"></td>
				</tr>
				<tr>
					<td colspan="9" align="right">&nbsp;</td>
					<!--<td width="103"><?php ///echo $pd; ?></td>-->
					<?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
				</tr>
				<tr>
					<td colspan="6" align="right"><b>Total Bill Amount :</b></td>
					<!--<td width="103"><?php ///echo $pd; ?></td>-->
					<td width="136">
						<?php echo $batotal ?>
					</td>
					<?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
						<td align="left" width="135"></td>
						<td align="left" width="135"></td>
				</tr>
				<tr>
					<td colspan="6" align="right"><b>Total Return Amount :</b></td>
					<!--<td width="103"><?php ///echo $pd; ?></td>-->
					<td width="136">
						<?php echo $rnttotal; ?>
					</td>
					<?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
						<td align="left" width="135"></td>
						<td align="left" width="135"></td>
				</tr>
				<tr>
					<td colspan="6" align="right"><b>Total Approval Amount :</b></td>
					<!--<td width="103"><?php ///echo $pd; ?></td>-->
					<td width="136">
						<?php $app_amont=$batotal-$rnttotal; echo $app_amont; ?>
					</td>
					<?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
						<td align="left" width="135"></td>
						<td align="left" width="135"></td>
				</tr>
				<tr>
					<?php
	  $qry41="SELECT sum(amount)  FROM `paid_amount` WHERE `bill_id` = '$id'  and amt_of<>'S'";
$res41=mysqli_query($con,$qry41);
$num411=mysqli_num_rows($res41);
$row41=mysqli_fetch_row($res41);
//echo $num411."?<br/>";




$total_paid_sql = mysqli_query($con,"SELECT sum(amount) as total_amount_paid FROM `paid_amount` WHERE `bill_id` = '".$id."'");
$total_paid_sql_result = mysqli_fetch_assoc($total_paid_sql);
$total_paid_amount = $total_paid_sql_result['total_amount_paid'];




 $qry42="SELECT SUM( paid_amount ) FROM  `approval` WHERE  `cust_id` ='$id'";
$res42=mysqli_query($con,$qry42);
$row42=mysqli_fetch_row($res42);
///echo $row42[0];
//my code:
$qry4="SELECT *  FROM `approval_detail` WHERE bill_id ='$row[0]'";
$res4=mysqli_query($con,$qry4);

while($row4=mysqli_fetch_row($res4)){

$a=round(($row4[7]/$row4[2])*$row4[4]);
$a1+=$a;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba+=$row4[7];
}
$pd=$row[4];
$na=$ba;
$s1=$ba-$a1;
$sum+=$s1;
while($row_new = mysqli_fetch_row($res_new)) 
{
$qry10="SELECT *  FROM `approval_detail` WHERE bill_id ='$row_new[0]'";
$res10=mysqli_query($con,$qry10);

while($row10=mysqli_fetch_row($res10)){

$a10=round(($row10[7]/$row10[2])*$row10[4]);
$a11+=$a10;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba10+=$row10[7];
}

$s10=$ba10-$a11;
$salesamount=null;
$salesamount+=$s10;
}

?>
						<tr>
							<tr>
								<td colspan="6" align="right"><b>Total Approval and Sales Amount :</b></td>
								<!--<td width="103"><?php ///echo $pd; ?></td>-->
								<td width="136">
									<?php /*echo $salesamount." ".$app_amont." ";*/$net=$salesamount + $app_amont; echo $net; ?>
								</td>
								<?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
									<td align="left" width="135"></td>
									<td align="left" width="135"></td>
							</tr>
							<tr>
								<td colspan="6" align="right"><b>Total Paid Amount :</b></td>
								<td width="136">
									<?php /* if($num411==0 || $num411=="" ) {  $pd11=$row42[0]; }else{
                                        
                                         if($row41[0]=="0"){ 
                                         
                                         $pd11=$row42[0];
                                          }else{ 
                                         $pd11=$row41[0];
                                         } 
                                         
                                           }*/
                                            // echo /*$pd11*/$row42[0]; 
                                            echo $total_paid_amount ; 
                                            
                                            ?>
								</td>
								<td align="left" width="135"></td>
								<td align="left" width="135"></td>
							</tr>
							<tr>
								<td colspan="6" align="right"><b>Total Balance Amount :</b></td>
								<!--<td width="103"><?php ///echo $pd; ?></td>-->
								<td width="136">
									<?php echo $net- $total_paid_amount ." /-"; ?>
								</td>
								<?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
									<td align="left" width="135"></td>
									<td align="left" width="135"></td>
							</tr>
	</table>
	<?php CloseCon($con);?>