<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$id=$_GET['id'];


$result2 = mysqli_query($con,"SELECT * FROM  `phppos_rent` where bill_id='$id'");	
$ss_rowordno = mysqli_fetch_assoc($result2);



$ss_orderid = $ss_rowordno['order_id'];




$ss_con = OpenNewSrishringarrCon();

$result1 = mysqli_query($con,"SELECT * FROM  `phppos_rent` where bill_id='$id'");
$rowordno = mysqli_fetch_array($result1);

$company_name = $rowordno['company_name'];
$throught = $rowordno['throught'];
// echo '<pre>';

// print_r($rowordno);
// echo '<pre>';


$is_online = $rowordno['is_online'];

if($is_online!=1){ 
    
    $row = mysqli_fetch_row($result1);


$sql2=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$rowordno[1]'");
$row2=mysqli_fetch_row($sql2); 


$result2 = mysqli_query($con,"SELECT * FROM  `phppos_rent` where bill_id='$id'");	

// echo "SELECT * FROM  phppos_people  WHERE `person_id`='$rowordno[1]' and first_name LIKE 'B %' order by first_name" ; 
$bresult = mysqli_query($con,"SELECT * FROM  phppos_people  WHERE `person_id`='$throught' and first_name LIKE 'B %' order by first_name");
$brow = mysqli_fetch_row($bresult); 



function get_state($id){
    global $ss_con;
    $order_sql = mysqli_query($ss_con,"select * from Order_ent where id='".$id."'") ; 
    if($order_sql_result = mysqli_fetch_assoc($order_sql)){
    $del_id = $order_sql_result['delivery_add'];
    $sql = mysqli_query($ss_con,"select * from shippingInfo where id='".$del_id."'");
    if($sql_result = mysqli_fetch_assoc($sql)){
        $state = $sql_result['state'];
        return  $state ;        
    }else{
        return ;
    }        
    }
    return ;


}
$state = get_state($ss_orderid);
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">

	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>SHRINGAAR</title>
	</head>
	<script type="text/javascript" src="paging.js"></script>
	<script type="text/javascript">
		function PrintDiv() {
			var divToPrint = document.getElementById('bill');
			divToPrint.style.fontSize = "12px";
			var popupWin = window.open('', '_blank', 'width=800,height=500');
			popupWin.document.open();
			popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
			popupWin.document.close();
		}
	</script>

	<body>
		<div id="bill" style="font-size:12px;">
			<p style="text-align:center;"><B><U> INVOICE </U></B></font></p>
			
			<table width="825" border="0" align="center">
				<tr>
					<td width="819" height="42">
					    <table width="100%">
       <tr>
          
          <td style="padding:0px; margin:0px;">
              <div><u><b style="font-size:11px;">We Rent, Sell And Customise </b></u></div>
              <ul style="margin:0;font-size:10px;">
                  <li>Bridal Jewellery & Accessories</li>
                  <li>Lehenga, Evening Gowns, Blouse</li>
                  <li>All Kinds Of Jewellery & Outfits</li>
              </ul>
              <br>
              
              <div><b><u style="font-size:11px;">Bank Account Details</u></b></div>
              
              
              <?php if($company_name == 'SAKAR TRADE LINK'){ ?>
        <div style="font-size:10px;">SAKAR TRADE LINK</div>
              <div style="font-size:11px;">Account number: 50231010008836</div>
              <div style="font-size:11px;">IFSC: CNRB0015023</div>
              <div style="font-size:11px;">Bank name: CANARA BANK</div>
              <div style="font-size:11px;">Branch: VILE PARLE EAST BRANCH</div>
    <?php }else{ ?>
        <div style="font-size:10px;">SRI SHRINGARR FASHION STUDIO</div>
              <div style="font-size:11px;">Account number: 756305000529</div>
              <div style="font-size:11px;">IFSC: ICICI0007563</div>
              <div style="font-size:11px;">Bank name:  ICICI BANK</div>
              <div style="font-size:11px;">Branch: VILE PARLE EAST BRANCH</div>
    <?php } ?>




              
          </td>
          
              <!--<img src="sri_logo.jpg" width="250px"/ style="padding-right:70px">-->
              
              
                 <?php 
                 

                 
                 if($company_name == 'SAKAR TRADE LINK'){ ?>
                 
          <td style=" padding: 0px; margin:0px; padding-left: 40px; ">
<img src="./img/ss_fly.png" width="250px">
</td>
<?php }else{  ?> 

          <td style=" padding: 0px; margin:0px; padding-left: 145px; ">

                      <img src="sri_logo.jpg" width="250px"/ style="padding-right:70px">
                      </td>
<?php
} ?> 

          
          <td style="padding:0px; margin:0px; " style="font-size:11px;">
              <div>Shyamkamal Building B,</div>
              <div>Wing B/1, Flat No.104,1st Floor,</div>
              <div>Agarwal Market, Vile Parle (East),</div>
              <div>Mumbai-400057, India.</div>
              <div>Phone - +91-9324243011/ +91-7400413163</div>
              <div>Email - rajanipodar@gmail.com</div>
          </td>
       
       </tr>
       </table>
       
       <hr style="margin:3px;border-top: 1px solid #000;">

    
    <table width="100%">
        <tr>
            <div class="col-md-3">
                <td>
                    
                     <?php if($company_name == 'SAKAR TRADE LINK'){ 
                     }
                     else{
                         ?>
                    
                    
                    <div style="text-align:left;font-size:14px;"> <b>UPI ID: srishringarrfashionstudio@icici</b> </div><br/><br/>
                    <div style="text-align:left"> <img src="img/sri_qr_icici.jpg" width="80px"> </div>
                    <br/>
                    
                         <?php
                     }
                     
              ?>
                    
                    <div style="width: 300px; height:30px;"><b> Name :</b><?php echo $row2[0] . " ".$row2[1]; ?></div>
                    <div style="height:30px;"><b>Contact No: </b><?php echo $row2[2]; ?></div>
                    <div><b> Address : </b><?php echo $row2[4]; ?></div>
                    <div style="height:30px;"><b> 2nd Person Name : </b>&nbsp;&nbsp;&nbsp; <?php echo $rowordno[19]; ?></div>
                    <div style="height:30px;"><b> 2nd Contact No.: </b>&nbsp;&nbsp;&nbsp; <?php echo $rowordno[20]; ?></div>
               </td>
            </div>
            
            
            <div class="col-md-9">
                <td><br/><br/><br/><br/>
               <div style="width: 220px;height:30px;"><b> Through Name: </b>
               <?php 
                    
                    echo $brow[0]??''; 
               ?>
               
               </b></div><br/>
               <div><b> Through Contact No:</b> <?php echo $brow[2]??''; ?></div><br/>
               <!--<div style="height:30px;"><b> Pick-Up By: </b>&nbsp;<?php //echo $rowordno[6]; ?></div>-->
                <div style="height:30px;"><b> Pick-Up By: </b>&nbsp;</div>
               <div style="height:30px;">
               <?php echo "<b>Delivery By</b>"; ?> :<b></b></div><br/> 
               
               
            <div style="height:30px;"> <b> Pick-Up Date : </b>
    	      <b>
    	          <?php if(isset($rowordno[11]) and $rowordno[11]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[11]));?>
    	      </b>
            </div>
        	             
                <div style="height:30px;"><?php echo "<b>Delivery Date : </b>";?> &nbsp;<b><?php if(isset($rowordno[12]) and $rowordno[12]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[12])); ?></b></div>     
           </td>
           <td>
               <div style="width: 320px;"><b> Bill. No. </b><?php echo $rowordno[0]; ?><br/><b> Date: </b><?php if(isset($rowordno[2]) and $rowordno[2]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[2])); ?></div>
               <div style="width: 320px;">
                   <br><br><br>
                   <b><u>TERMS & CONDITION:</u></b>
            <ul style="padding: 0;">
              
              <li><b>Once an order Is Booked, it will not be changed, exchange or cancelled.</b></li>
              <li>Rental is for 3 days only, 5% extra for each day.</li>
              <li>Security deposit is compulsory.</li>
              <li>Any damage done will be deducted from the security deposit.</li>
              <li><b>No money will be refunded under any circumstances.</b></li>
              <li>Subject to Mumbai Jurisdiction.</li>
            </ul>
               </div>
           </td>
            </div>
        </tr>
        
       
</table>
						<font size="2">
  
  <table width="100%" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
  <th style="padding:3px;" width="96"><font size="2"><center>SR NO.</center></font></th>
    <th style="padding:3px;" width="96"><font size="2"><center>ITEM CODE</center></font></th>
    <th style="padding:3px;" width="130"><font size="2"><center>PARTICULARS</center></font></th>
    <th style="padding:3px;" width="96"><font size="2"><center>DESCRIPTION</center></font></th>
    <th style="padding:3px;" width="86"><font size="2"><center>MRP</center></font></th>
    <th style="padding:3px;" width="86"><font size="2"><center>QTY</center></font></th>
    <th style="padding:3px;" width="110"><font size="2"><center>RENT</center></font></th>
    <th style="padding:3px;" width="119"><font size="2"><center>DEPOSIT</center></font></th>
    <th style="padding:3px;" width="88"><font size="2"><center>TAXABLE RENT</center></font></th>
				</tr>
				<?php
  $total=0;
  $total1=0;
  $totalq = 0;
  $i=1;
$sql2=mysqli_query($con,"SELECT * FROM  `order_detail` where bill_id='$id'");

$totalCGST = 0 ; 
$totalGST = 0 ; 
$totalTaxableAmount = 0 ; 


while($row2=mysqli_fetch_row($sql2)){ 


$sq="SELECT * FROM phppos_items WHERE name='$row2[1]' and is_deleted = 0";
$res2 = mysqli_query($con,$sq);
$num2=mysqli_num_rows($res2);
$row1=mysqli_fetch_row($res2);



// Get product type 
// echo "Select * from phppos_items where name='".$row2[1]."'" ; 
$productsql = mysqli_query($con,"Select * from phppos_items where name='".$row2[1]."'");
$productsqlResult = mysqli_fetch_assoc($productsql);

$categoryName = $productsqlResult['category'];
$categorysql = mysqli_query($con,"select * from categories where category='".$categoryName."'");
$categorysqlResult = mysqli_fetch_assoc($categorysql);
$productType = $categorysqlResult['typ'];

$totalProductAmount = $row2[6] ; 
// echo '$productType = ' . $productType  ; 
if($productType==1){
    // Jewellery 
    // calculate 3% total GST
    
    $thisProductTotalTaxable = $totalProductAmount/1.03 ; 
    $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.03 ; 
    $cgst = $sgst = $thisProductTotalGst / 2 ;  
    
    
}else if($productType==2){
    // Apparel 
    // calculate 12% total GST
    
    $thisProductTotalTaxable = $totalProductAmount/1.12 ;
    $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.12 ; 
    $cgst = $sgst = $thisProductTotalGst / 2 ;  
    
}

    $roundCGST = round($cgst,0) ; 

$totalTaxableAmount = $totalTaxableAmount + $thisProductTotalTaxable ; 
$totalCGST = $totalCGST + $cgst ; 
$totalGST = $totalGST + $thisProductTotalGst  ; 



?>
					<tr>
						<td align="center">
							<?php echo $i; ?>
						</td>
						<td align="center">
							<?php echo $row1[0]; ?>
						</td>
						<td align="center">
							<?php echo $row1[1]; ?>
						</td>
						<td align="center">
							<?php echo $row2[7] ?>
						</td>
						<td align="center">
							<?php echo $row1[6] ?>
						</td>
						
						<td align="center">
							<?php echo $row2[9] ?>
						</td>
						<td align="center">
							<?php echo $row2[2] ?>
						</td>
						<td align="center">
							<?php echo $row2[3]; ?>
						</td>
						
						<td align="center">
							<?php 
							echo $row2[6] ; 
							echo '<br/>';
							echo $row2[6] - ($roundCGST*2); ?>
						</td>
					</tr>
					
					
					
					<?php 
                      $total+=$row2[2]+$row2[3];
                      $total1+=$row2[6]; 
                      $i++; 
                       $totalq+=$row2[9]; 
                      } 
                    ?>
						<?php if($rowordno['card_perc']>0)
 {
     ?>
							<!--<tr>-->
							<!--	<td colspan="6" align="right"></td>-->
							<!--	<td colspan="" align="right"><b> Card <?php echo $rowordno['card_perc']?>%</b></td>-->
							<!--	<td colspan="" align="right"> <b><?php echo $rowordno['card_amt'];$total1+=$rowordno['card_amt']; ?></b></td>-->
							<!--</tr>-->
							<?php
 }
 
 
 ?>
								<tr>
								    <td colspan="3" align="left"><b> Bill Made By: </b><?php echo $rowordno[41]; ?></td>
									<td colspan="3" align="right"><b> Total Quantity: </b><?php echo $totalq; ?></td>
									<?php
                                        $ap= $total1-$rowordno[10];
                                    ?>
										<td align="right" colspan="3"> <b> Total Rent (Including GST):</b>&nbsp;&nbsp;
											<?php echo $total1; ?>
										</td>
								</tr>
								
								
								
                                <?php if($company_name == 'SAKAR TRADE LINK'){ 
              
             
              ?>
              
              
                    <tr>
                        <td colspan="3"><b>CGST : </b> <?php echo round($totalCGST,0) ;  ?> </td>
                        <td colspan="3"><b>SGST : </b> <?php echo round($totalCGST,0) ;  ?> </td>
                        <td colspan="3"><b>Total GST : </b> <?php echo round($totalCGST,0) *2 ;  ?> </td>
                    </tr>
              <?php } ?>

								
								
								
								
								<tr>
								    <?php 
								        $mode = $rowordno[37];
								        
								        if($mode==0)
								        {
								            $mode_status = "Cash";
								        }
								        else if($mode==1)
								        {
								            $mode_status = "Cheque";
								        }
								        else if($mode==2)
								        {
								            $mode_status = "Card";
								        } else {
								            echo " ";
								        }
								    
								    
								    
								    ?>
								    
                                    <td colspan="2.8" align="left"><b> Mode of Payment :  </b><?php echo $mode_status; ?></td>
                                    <td colspan="2.5" align="left"><b> Deposit : </b><?php echo $rowordno[13]; ?></td>
                                    <td colspan="3" align="left"> <b>Paid Amount :</b><b><?php echo "Rs.".$rowordno[10];?></b></td>
                                    <td colspan="3" align="right"> <b>Balance : </b><b><?php echo $ap; ?></b></td>
                                 </tr>
                                 
                                  <tr>
                                    <td colspan="11"><b>Trial Date : </b> <?php echo $rowordno[14];?></td>     
                                 </tr>
                                 
                                  <tr>
                                    <td colspan="3" align="left"><b> Measurement :  </b><?php echo $rowordno[15]; ?></td>
                                    <td colspan="8" align="left"><b> Measurement Note : </b><?php echo $rowordno[16]; ?></td>
                                
                                 </tr>
                                 
                                  <tr>
                                    <td colspan="11"><b>Delivery : </b> <?php echo $rowordno[17];?></td>     
                                 </tr>
                                 
                                 <tr>
                                    <td colspan="11"><b>Note : </b> <?php echo $rowordno[25];?></td>     
                                 </tr>
                                 
                                 <tr>
                                    <td colspan="4" align="left"><b> Given By :  </b><?php ?></td>
                                    <td colspan="10" align="left"><b> Taken By : </b><?php  ?></td>
                                
                                 </tr>
			</table>
			</font>
			</td>
			</tr>
			<tr>
				<td align="right"><font size="2">&nbsp;&nbsp;<?php 
  
    ?></font></td>
			</tr>

			<tr>
				<td>
					<hr/>
					
				</td>
			</tr>
			</table>
			<div style="width:826px; margin:auto; padding-top: 15px; text-align: right; padding-bottom: 11px;">
    <p><b>SRI SHRINGARR FASHION STUDIO</b></p>
</div>


<div style="width:826px; margin:auto; text-align: right; padding: 40px;"> 
    <p><b>AUTH. SIGNATORY</b></p>
</div>
		</div>
		<br/>
		<br/>
		<div id="pageNavPosition"></div>
		<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="/rentReport.php">Back</a></center>
	</body>

	</html>
	<?php }else{ 

//-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//   Sri Shringaar DB Functions Starts  ONLINE PAYMENTS BILL
//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------



function get_shippingaddress($id){
    global $ss_con;
    

$order_sql = mysqli_query($ss_con,"select * from Order_ent where id='".$id."'") ; 
$order_sql_result = mysqli_fetch_assoc($order_sql);

$del_id = $order_sql_result['delivery_add'];

    $sql = mysqli_query($ss_con,"select * from shippingInfo where id='".$del_id."'");
    $sql_result = mysqli_fetch_assoc($sql);
    
    $person_name = $sql_result['person_name'];
    $person_contact = $sql_result['person_contact'];
    $address = $sql_result['address'];
    $landmark = $sql_result['landmark'];
    $city = $sql_result['city'];
    $state = $sql_result['state'];
    $pincode = $sql_result['pincode'];
    $country = $sql_result['country'];
    
    return 
    $address . ', ' . $landmark . ', ' . $city . ', ' . $state . ', ' . $pincode .', ' .$country ;    

}




function get_order_ent($id,$parameter){
        global $ss_con;
        
        $sql = mysqli_query($ss_con,"select $parameter from Order_ent where id='".$id."'");
        $sql_result = mysqli_fetch_assoc($sql);
        
        return $sql_result[$parameter];

}

function get_order_details_arr($id,$parameter){
        global $ss_con;
        $sql = mysqli_query($ss_con,"select $parameter from order_details where order_id='".$id."'");
        $sql_result = mysqli_fetch_assoc($sql);
        return $sql_result;
}

//-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//   Sri Shringaar DB Functions End 
//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    
$row = mysqli_fetch_row($result1);


$sql2=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$rowordno[1]'");
$row2=mysqli_fetch_row($sql2); 



if($ss_orderid > 0 ){

    $ss_sql = mysqli_query($ss_con, "SELECT * FROM `Registration` where registration_id = '$rowordno[1]'");
    $ss_sql_result = mysqli_fetch_assoc($ss_sql);
    $row[1] = $ss_sql_result['Firstname'] .' '. $ss_sql_result['Lastname'];
    $row[2] = $ss_sql_result['Mobile'];
}

$address = get_shippingaddress($ss_orderid);


?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">

		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title>SHRINGAAR</title>
		</head>
		<script type="text/javascript" src="paging.js"></script>
		<script type="text/javascript">
			function PrintDiv() {
				var divToPrint = document.getElementById('bill');
				divToPrint.style.fontSize = "12px";
				var popupWin = window.open('', '_blank', 'width=800,height=500');
				popupWin.document.open();
				popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
				popupWin.document.close();
			}
		</script>

		<body>
			<div id="bill" style="font-size:12px;">
				<table width="825" border="0" align="center">
					<tr>
						<td width="819" height="42">
							<table width="100%">
								<tr>
									<td colspan="3" align="center"> <font size="2">
            <B><U> CONFIRMATION MEMO </U></B></font></td>
								</tr>
								<tr>
									<td width="382" align="left" valign="top"><b><font size="-1" >MANUFACTURERS AND RETAILERS</font> <font size="-1">OF BRIDAL SETS</font>,<br />
      <font size="-1">HAIR ACCESSORIES AND  BROOCHES,</font><br/>
      <font size="-1">BRIDAL DUPATTAS</font>,<br />
      <font size="-1">CHANIYA CHOLI,<br/>
      &amp; ALL KINDS OF ACCESSORIES.</font></b>
										<br />
									</td>
									<td width="425" height="42" colspan="2" align="left" valign="bottom" style="padding-left:10px;"><img src="bill.PNG" width="408" height="165" /></td>
								</tr>
								<tr>
									<td colspan="2"></td>
								</tr>
								<tr>
									<td height="70" colspan="2">
										<table width="100%">
											<tr>
												<td width="269" height="43">M/s.&nbsp;:&nbsp;&nbsp;<b>
                                                    <?php echo $row2[0] . " ".$row2[1]; ?></b></td>
												<td width="216">Through Name: <b>
												    <?php 
                                                        
                                                        echo $brow[0]; 
                                                   ?>
												    
												    </b>
													<br/> Through Contact No: <b><?php echo $brow[2]; ?></b></td>
												<td width="94" rowspan="5">Bill. No. <b><?php echo $rowordno[0]; ?></b>
													<br/>Date: <b><?php if(isset($rowordno[2]) and $rowordno[2]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[2])); ?></b></td>
											</tr>
											<tr>
												<td>Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $row2[2]; ?></b></td>
												<td width="216">Pick-Up. :&nbsp;<b><?php echo $rowordno[6]; ?></b></td>
											</tr>
											<tr>
												<td height="45">Address.: &nbsp;&nbsp;&nbsp;
													<?php echo $address ;  ?>
														<br/>
														<?php echo $row[6]; ?>
															<?php echo $row[8]; ?>
																<?php echo $row[9]; ?>
												</td>
												<td>Delivery :<b><?php echo $rowordno[7]; ?></b></td>
											</tr>
											<tr>
												<td>2nd Person Name.: &nbsp;&nbsp;&nbsp; <b><?php echo $rowordno[15]; ?></b></td>
												<?php if($is_online!=1){ ?>
													<td>Pick-Up Date :<b>
	  <?php if(isset($rowordno[11]) and $rowordno[11]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[11]));
 ?></b></td>
													<?php } ?>
														<!-- <td width="141">Commission : <?php //echo $rowordno[17]; ?><?php //echo $rowordno[18]; ?></td>-->
											</tr>
											<tr>
												<td>2nd Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $rowordno[16]; ?></b></td>
												<?php if($is_online!=1){ ?>
													<td>Delivery Date :<b><?php if(isset($rowordno[12]) and $rowordno[12]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[12])); ?></b></td>
													<?php } ?>
														<!--  <td>Total Commission : <?php //echo $rowordno[19]; ?></td>-->
											</tr>
										</table>
									</td>
								</tr>
							</table> <font size="2">
  
  <table width="100%" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
  <th width="104"><font size="2">SR.NO.</font></th>
							<th width="104"><font size="2">ITEM CODE</font></th>
							<th width="161"><font size="2">PARTICULARS</font></th>
							<th width="86"><font size="2">PRICE</font></th>
							<th width="86"><font size="2">QTY</font></th>
							<th width="93"><font size="2">RENT</font></th>
							<?php if($is_online==1){ ?>
								<th width="93"><font size="2">PICKUP DATE</font></th>
								<th width="93"><font size="2">DELIVERY DATE</font></th>
								<?php } ?>
									<th width="113"><font size="2">DEPOSIT</font></th>
									<!-- <th width="114"><font size="2">C0MMISSION</font></th>-->
									<th width="76"><font size="2">TOTAL RENT</font></th>
					</tr>
					<?php
  $total=0;
  $total1=0;
  $i=1;
$sql2=mysqli_query($con,"SELECT * FROM  `order_detail` where bill_id='$id'");
while($row2=mysqli_fetch_row($sql2)){ 
//echo "SELECT * FROM phppos_items WHERE name=".$design[$i];

$sq="SELECT * FROM phppos_items WHERE name='$row2[1]' and is_deleted = 0";
$res2 = mysqli_query($con,$sq);
$num2=mysqli_num_rows($res2);
$row1=mysqli_fetch_row($res2);
?>
						<tr>
							<td align="center">
								<?php echo $i; ?>
							</td>
							<td align="center">
								<?php echo $row1[0]; ?>
							</td>
							<td align="center">
								<?php echo $row1[1]; ?>
							</td>
							<td align="center">
								<?php echo $row1[6] ?>
							</td>
							<td align="center">
								<?php echo $row2[9] ?>
							</td>
							<td align="center">
								<?php echo $row2[2] ?>
							</td>
							<?php if($is_online==1){ ?>
								<td align="center">
									<?php echo $row2[11] ?>
								</td>
								<td align="center">
									<?php echo $row2[12] ?>
								</td>
								<?php } ?>
									<td align="center">
										<?php echo $row2[3]; ?>
									</td>
									<!-- <td align="center"><?php //echo $row2[5]; ?></td>-->
									<td align="center">
										<?php echo $row2[6]; ?>
									</td>
						</tr>
						<?php 
  $total+=$row2[2]+$row2[3];
  $total1+=$row2[6]; 
  $i++; 
   $totalq+=$row2[9]; 
  } 
  ?>
							<?php if($rowordno['card_perc']>0)
 {
     ?>
								<!--<tr>-->
								<!--	<td colspan="6" align="right"></td>-->
								<!--	<td colspan="" align="right"><b> Card <?php echo $rowordno['card_perc']?>%</b></td>-->
								<!--	<td colspan="" align="right"> <b><?php echo $rowordno['card_amt'];$total1+=$rowordno['card_amt']; ?></b></td>-->
								<!--</tr>-->
								<?php
 }
 
 
 ?>
									<tr>
										<td colspan="5" align="right"><b> Total Quantity: </b>
											<?php echo $totalq; ?>
										</td>
										<?php

$ap= $total1-$rowordno[10];
  
?>
											<td align="right" colspan="9"> <b> Total Rent :</b>&nbsp;&nbsp;
												<?php echo $total1; ?>
											</td>
									</tr>
				</table>
				</font>
				</td>
				</tr>
				<tr>
					<td align="right"><font size="2">&nbsp;&nbsp;<?php 
  
    ?></font></td>
				</tr>
				<tr>
					<td height="31" align="center"><font size="3">
    
  
  
   Balance :<b>&nbsp;&nbsp;<?php echo  "Rs.".$ap;   ?>
   
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    Deposit&nbsp; :&nbsp;<b><?php echo $rowordno[13]; ?>&nbsp;&nbsp;<br/>Paid Amount <?php echo "Rs.".$rowordno[10];
  
    ?></b></font></td>
				</tr>
				<tr>
					<td height="31" align="center">
						<p><b>Onces an order is booked,it will not be changed and its money will not be returned.
      <br/>
      The full amount of Rent is to be given on the day of booking</b></p>
					</td>
				</tr>
				<tr>
					<td>
						<hr/>
						<table width="752" border="0">
							<tr>
								
								<td width="138">
									<br/>
									<br/>
									<br/>
									<br/>
									<hr /> <font>Receiver's Signature</font>&nbsp;</td>
								<td width="219" valign="top" align="right"> <img src="shringaar.png" width="163" height="57" />
									<br/>
									<br/>
									<br/> <font>Auth. Signatory</font>&nbsp;</td>
							</tr>
						</table>
					</td>
				</tr>
				</table>
			</div>
			<br/>
			<br/>
			<div id="pageNavPosition"></div>
			<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="http://sarmicrosystems.in/shringaar/application/views/reports/rentReport.php">Back</a></center>
		</body>

		</html>
		<?php  } 
CloseCon($ss_con);
CloseCon($con); ?>