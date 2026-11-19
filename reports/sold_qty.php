<script>
	var isCtrl = false;
	document.onkeyup = function(e) {
		if (e.which == 17) isCtrl = false;
	}
	document.onkeydown = function(e) {
			if (e.which == 17) isCtrl = true;
			if (e.which == 66 && isCtrl == true) {
				document.getElementById("barcode").focus();
				return false;
			}
		}
		////////////////
	function formSubmit() {
		if (document.getElementById('cid').value == -1) {
			alert("Please enter Customer Id to continue.");
			document.getElementById('cid').focus();
			return false;
		} else {
			document.getElementById("frm1").submit();
			return true;
		}
	}
	var searchReq = getXMLHttp();

	function getXMLHttp() {
		var xmlHttp
			// alert("hi1");
		try {
			//Firefox, Opera 8.0+, Safari
			xmlHttp = new XMLHttpRequest();
		} catch (e) {
			//Internet Explorer
			try {
				xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try {
					xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e) {
					alert("Your browser does not support AJAX!")
					return false;
				}
			}
		}
		return xmlHttp;
	}

	function MakeRequest() {
		var xmlHttp = getXMLHttp();
		// alert("hi");
		xmlHttp.onreadystatechange = function() {
				if (xmlHttp.readyState == 4) {
					HandleResponse(xmlHttp.responseText);
				}
			}
			// alert("hi2");
		var str = escape(document.getElementById('cid').value);
		//alert(str);
		xmlHttp.open("GET", "getDetailsale.php?barcode=" + str, false);
		xmlHttp.send(null);
	}

	function HandleResponse(response) {
		//alert(response);
		document.getElementById('detail').innerHTML = response;
	}
	////remove div
	function removeElement(divNum) {
		var d = document.getElementById('detail');
		var olddiv = document.getElementById(divNum);
		d.removeChild(olddiv);
	}
</script>
<div style="text-align: center;"> <font size="+1">
<a href="app_return.php">Back</a></font>
	<table width="788" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" align="center">
		<tr>
			<td valign="top" align="center">
				<?php
//  include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

 
$result5=mysqli_query($con,"select * from   `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($result5,10);

$row7=mysqli_fetch_array($result5);

?> <img src="bill.png" width="408" height="165" />
					<br/>
					<br/> Sales Return
					<br/>
					<br/>
					<center>
						<form action="approval_detail.php" id="frm1" name="frm1" method="POST"> &nbsp;&nbsp;&nbsp;
							<br/>
							<br/>
							<table width="778" border="0" cellpadding="4" cellspacing="0">
								<tr>
									<td>
										<div id="detail">
											<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include('config.php');

       $id=$_GET['id'];

$batotal=0;
$rnttotal=0;
$nettotal=0;

$qry="SELECT * FROM  `approval` where cust_id='$id' and status='S' ";
$res=mysqli_query($con,$qry);   

$num=mysqli_num_rows($res);
$qry_new="SELECT * FROM  `approval` where cust_id='$id' and status='A' ";
$res_new=mysqli_query($con,$qry_new);                
$num=mysqli_num_rows($res_new);

?>
												<table width="795">
													<tr>
														<td width="281"><a href="Sale_detail1.php?id=<?php echo $id; ?>"><font style="font-size:18px;"><B>Sale's Return</B></font></a></td>
														<td width="260">
															<a href="sold_qty.php?id=<?php echo $id; ?>"></a>
														</td>
														<td width="238"><a href="payapp_detail1.php?id=<?php echo $id; ?>" target="_new"><font style="font-size:18px;"><B>Paid Amount Detail</B></font></a></td>
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

$a=round(((float)$row4[7]/(float)$row4[2])*(float)$row4[4]);
$a1+=$a;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba+=$row4[7];
}

$pd=$row[4];
$na=$ba;
$s1=(float)$ba-(float)$a1;
//echo "cust=".$row[11]."paidamt=".$pd."bal amt=".$na."return".$a1."net amt=".$s1."<br/>";
$s=(float)$row3[0]-(float)$row2[0];
//echo $row2[0]."&&".$s;
while($row_new = mysqli_fetch_row($res_new)) 
{
$qry10="SELECT *  FROM `approval_detail` WHERE bill_id ='$row_new[0]'";
$res10=mysqli_query($con,$qry10);

while($row10=mysqli_fetch_row($res10)){

$a10=round(((float)$row10[7]/(float)$row10[2])*(float)$row10[4]);
$a11+=$a10;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba10+=$row10[7];
}

$s10=(float)$ba10-(float)$a11;
$app_amount=null;
$app_amount+=$s10;
}
?>
														<tr>
															<td width="75">
																<?php echo $i; ?>
															</td>
															<td width="75">
																
																<a href="sales_report_detail.php?id=<?php echo $row[0] ;?>">
																<?php echo $row['new_bill_number'] ? $row['new_bill_number'] : $row[0] ; ?>																    
																</a>

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
																<td align="left" width="135"><a href="get_sale.php?id=<?php echo $row[0]; ?>" target="_new">Return Detail</a></td>
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
															</tr>
															<tr>
																<td colspan="8" align="right">&nbsp;</td>
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
															</tr>
															<tr>
																<td colspan="6" align="right"><b>Total Return Amount :</b></td>
																<!--<td width="103"><?php ///echo $pd; ?></td>-->
																<td width="136">
																	<?php echo $rnttotal; ?>
																</td>
																<?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
																	<td align="left" width="135"></td>
															</tr>
															<tr>
																<td colspan="6" align="right"><b>Total Sales Amount :</b></td>
																<!--<td width="103"><?php ///echo $pd; ?></td>-->
																<td width="136">
																	<?php $sales_amount=$batotal-$rnttotal; echo $sales_amount;?>
																</td>
																<?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
																	<td align="left" width="135"></td>
															</tr>
															<tr>
																<td colspan="6" align="right"><b>Total Approval and Sales Amount :</b></td>
																<!--<td width="103"><?php ///echo $pd; ?></td>-->
																<td width="136">
																	<?php /*echo $app_amount;*/$net=$sales_amount+$app_amount; echo $net;?>
																</td>
																<?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
																	<td align="left" width="135"></td>
															</tr>
															<tr>
																<?php
	 /* $qry41="SELECT sum(amount) FROM `paid_amount` WHERE `bill_id`='$id' and amt_of='S'";
$res41=mysqli_query($con,$qry41);
$num411=mysqli_num_rows($res41);
$row41=mysqli_fetch_row($res41);*/
///echo $id."/".$num411."<br/>";

 $qry42="SELECT SUM( paid_amount ) FROM  `approval` WHERE  `cust_id` ='$id'";
$res42=mysqli_query($con,$qry42);
$row42=mysqli_fetch_row($res42);
//////echo $num411;
?>
																	<td colspan="6" align="right"><b>Total Paid Amount :</b></td>
																	<td width="136">
																		<?php /*if($num411==0 || $num411=="" || $row41[0]=="") {  $pd11=$row42[0]; }else{  $pd11=$row41[0];  } echo $pd11; */echo $row42[0];?>
																	</td>
																	<td align="left" width="135"></td>
															</tr>
															<tr>
																<td colspan="6" align="right"><b>Total Balance Amount :</b></td>
																<!--<td width="103"><?php ///echo $pd; ?></td>-->
																<td width="136">
																	<?php echo $net-$row42[0]." /-"; ?>
																</td>
																<?php ///echo $s."/".$a."/".$row2[0]."<br/>"; ?>
																	<td align="left" width="135"></td>
															</tr>
												</table>
										</div>
									</td>
								</tr>
							</table>
							<br/> </form>
					</center>
			</td>
		</tr>
	</table>
</div>

	<div align="center">You are using Point Of Sale Version 10.5 .</div>