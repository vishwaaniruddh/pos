<?php



include('../db_connection.php') ;
$con=OpenSrishringarrCon();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


$id=$_GET['id'];


$result2 = mysqli_query($con,"SELECT * FROM  `phppos_rent` where bill_id='$id'");	
$ss_rowordno = mysqli_fetch_assoc($result2);

$transaction_id = $ss_rowordno['transaction_id'];



$ss_orderid = $ss_rowordno['order_id'];
$new_bill_number = $ss_rowordno['new_bill_number'];




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

<style>
    th,td{
        padding:5px;
    }
</style>

	<body>
	    
	    
		<div id="bill" style="font-size:12px;">
			<p style="text-align:center;"><B><U> INVOICE </U></B></p>
			
			<table width="825" border="0" align="center">
				<tr>
					<td width="819" height="42">
					    <table width="100%">
       <tr>
          
          <td style="padding:0px; margin:0px;    width: 33%;">
              <!--<div><u><b style="">We Rent, Sell And Customise </b></u></div>-->
              <!--<ul style="margin:0;font-size:10px;">-->
              <!--    <li>Bridal Jewellery & Accessories</li>-->
              <!--    <li>Lehenga, Evening Gowns, Blouse</li>-->
              <!--    <li>All Kinds Of Jewellery & Outfits</li>-->
              <!--</ul>-->
              <!--<br>-->
              
              
              
              
              
                    
                     <?php if($company_name == 'SAKAR TRADE LINK'){  ?>
                     
                     
                    <!--<div style="text-align:left"> <img src="img/sakarQR.jpg" width="120px"> </div>-->
                    <!--<div style="text-align:left;font-size:14px;"> <b>UPI ID: 237382406008836@cnrb</b> </div>-->
                    
                    <div style="text-align:left"> <img src="img/sakarQRICICI.jpeg" width="120px"> </div>
                    <div style="text-align:left;font-size:14px;"> <b>UPI ID: MSSAKARTRADELINK.eazypay@icici</b> </div>
                    
                    <br/>
                    
                     <?php }
                     else{
                         ?>
                    <div style="text-align:left"> <img src="img/sri_qr_icici.jpg" width="120px"> </div>
                    <br>
                    <div style="text-align:left;font-size:14px;"> <b>UPI ID: srishringarrfashionstudio@icici</b> </div>
                    <br/>
                    
                         <?php
                     }
                     
              ?>
                    
              
              
              
              
              
              



              
          </td>
          
              <!--<img src="sri_logo.jpg" width="250px"/ style="padding-right:70px">-->
              
              
                 <?php 
                 

                 
                 if($company_name == 'SAKAR TRADE LINK'){ ?>
                 
                 
          <td style=" padding: 0px; margin:0px; text-align: center;     width: 33%;">
              <h1 style="text-align:center;letter-spacing: 8px;margin:0;white-space: nowrap;">SAKAR TRADE LINK</h1>
              <span>GSTIN:27AAGPP0302A1ZH</span>
              <hr>
<img src="./img/ss_fly.png" width="250px">
</td>
<?php }else{  ?> 



          <td style=" padding: 0px; margin:0px; text-align: center; 
    letter-spacing: 1px;    width: 33%;">
            <h3 style="text-align:center;margin:0;white-space: nowrap;">SRI SHRINGARR FASHION STUDIO</h3>
                          <span>GSTIN:27ADRPP9888P1ZW</span>
              <hr>
                      <img src="sri_logo.jpg" width="160px" />
                      </td>
<?php
} ?> 

          
          <td style="padding:0px; margin:0px; text-align: right;    width: 33%;">
              <div>Shyamkamal Building B,</div>
              <div>Wing B/1, Flat No.104,1st Floor,</div>
              <div>Agarwal Market, Vile Parle (East),</div>
              <div>Mumbai-400057, India.</div>
              <div>Phone - +91-9324243011/ +91-7400413163</div>
              <div>Email - rajanipodar@gmail.com</div>
          </td>
       
       </tr>
       </table>
       <br>
       <hr style="margin:3px;border-top: 1px solid #000;">

    
    <table width="100%">
        <tr>
            <div class="col-md-3">
                
                
                
            



                <td>
              
              
              
              <?php if($company_name == 'SAKAR TRADE LINK'){ ?>
        <!--<div><b><u>Bank Account Details</u></b></div><br>-->
        <!--<div >SAKAR TRADE LINK</div>-->
        <!--      <div style="">Account number: 50231010008836</div>-->
        <!--      <div style="">IFSC: CNRB0015023</div>-->
        <!--      <div style="">Bank name: CANARA BANK</div>-->
        <!--      <div style="">Branch: VILE PARLE EAST </div>-->
    <?php }else{ ?>
                      <div><b><u>Bank Account Details</u></b></div><br>

        <div style="font-size:10px;">SRI SHRINGARR FASHION STUDIO</div>
              <div style="">Account number: 756305000529</div>
              <div style="">IFSC: ICICI0007563</div>
              <div style="">Bank name:  ICICI BANK</div>
              <div style="">Branch: VILE PARLE EAST </div>
    <?php } ?>
<br>
                    <div style="width: 300px; height:15px;"><b> Name :</b><?php echo $row2[0] . " ".$row2[1]; ?></div>
                    <div style="height:15px;"><b>Contact No: </b><?php echo $row2[2]; ?></div>
                    <div style=""><b> Address : </b><?php echo $row2[4]; ?></div>
                    <div style="height:15px;"><b> 2nd Person Name : </b>&nbsp;&nbsp;&nbsp; <?php echo $rowordno[19]; ?></div>
                    <div style="height:15px;"><b> 2nd Contact No.: </b>&nbsp;&nbsp;&nbsp; <?php echo $rowordno[20]; ?></div>
               </td>
            </div>
            
            
            <div class="col-md-9">
                <td>
               <div style="width: 220px;height:15px;"><b> Through Name: </b>
               <?php 
                    
                    echo $brow[0]??''; 
               ?>
               
               </b></div><br/>
               <div style=""><b> Through Contact No:</b> <?php echo $brow[2]??''; ?></div><br/>
               <!--<div style="height:15px;"><b> Pick-Up By: </b>&nbsp;<?php //echo $rowordno[6]; ?></div>-->
                <div style="height:15px;"><b> Pick-Up By: </b>&nbsp;</div>
               <div style="height:15px;">
               <?php echo "<b>Delivery By</b>"; ?> :<b></b></div><br/> 
               
               
            <div style="height:15px;"> <b> Pick-Up Date : </b>
    	      <b>
    	          <?php if(isset($rowordno[11]) and $rowordno[11]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[11]));?>
    	      </b>
            </div>
        	             
                <div style="height:15px;"><?php echo "<b>Delivery Date : </b>";?> &nbsp;<b><?php if(isset($rowordno[12]) and $rowordno[12]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[12])); ?></b></div>     
           </td>
           <td>
               <div style="width: 320px;"><b> Bill. No. </b>
               
               <?php echo  $new_bill_number ? $new_bill_number :  $rowordno[0]; ?><br/><b> Date: </b><?php if(isset($rowordno[2]) and $rowordno[2]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[2])); ?></div>
               <div style="width: 320px;">
                   <br>
                   <b font-size:11px;><u>TERMS & CONDITION:</u></b>
            <ul style="padding: 0;" >
              
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

  <table width="100%" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
  <th style="padding:3px;" width="96"><center>SR NO.</center></th>
    <th style="padding:3px;" width="96"><center>ITEM CODE</center></th>
    <th style="padding:3px;" width="130"><center>PARTICULARS</center></th>
    <th style="padding:3px;" width="96"><center>DESCRIPTION</center></th>
    <th style="padding:3px;" width="86"><center>MRP</center></th>
    <th style="padding:3px;" width="86"><center>QTY</center></th>
    <th style="padding:3px;" width="110"><center>RENT</center></th>
    <th style="padding:3px;" width="110"><center>GST</center></th>
    <th style="padding:3px;" width="119"><center>DEPOSIT</center></th>
    <th style="padding:3px;" width="88"><center>TAXABLE RENT</center></th>
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

$totalDeposit = 0 ;
while($row2=mysqli_fetch_array($sql2)){ 


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
    $gst_rate = 3;
    $thisProductTotalTaxable = $totalProductAmount/1.03 ; 
    $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.03 ; 
    $cgst = $sgst = $thisProductTotalGst / 2 ;  
    
    
}else if($productType==2){
    // Apparel 
    // calculate 12% total GST
    
    // $thisProductTotalTaxable = $totalProductAmount/1.12 ;
    // $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.12 ; 
    // $cgst = $sgst = $thisProductTotalGst / 2 ;
    
       if(strtotime($rowordno[2]) < strtotime('2025-09-22')){
                                                    // Before 22-09-2025, GST = 12%
            $gst_rate = 12;
            $thisProductTotalTaxable = $totalProductAmount/1.12 ;
            $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.12 ;         
        }else{
            if($totalProductAmount > 2500){
              $gst_rate = 18;
                $thisProductTotalTaxable = $totalProductAmount/1.18 ;
                $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.18 ;         
            }else{
                $gst_rate = 5;
                $thisProductTotalTaxable = $totalProductAmount/1.05 ;
                $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.05 ; 
            }
        }
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
						<td align="center"><?php echo $gst_rate.'%' ; ?></td>
						<td align="center">
							<?php echo $row2[3]; ?>
						</td>
						
						<td align="center">
							<?php 
				// 			echo $row2[6] ; 
				// 			echo '<br/>';
							echo $row2[6] - ($roundCGST*2); ?>
						</td>
					</tr>
					
					
					
					<?php 
                      $total+=$row2[2]+$row2[3];
                      $total1+=$row2[6]; 
                      $i++; 
                       $totalq+=$row2[9]; 
                       
                       $totalDeposit = $totalDeposit +  $row2['deposit'];
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
									<td colspan="2" align="right"><b> Total Quantity: </b></td>
									<td align="center"><?php echo $totalq; ?></td>
									<?php
                                        $ap= $total1-$rowordno[10];
                                    ?>
										<td align="right" colspan="3"> <b> Total Rent (Including GST):</b></td>
										<td align="center"><?php echo $total1; ?></td>
								</tr>
								
                   <tr>
                        <td colspan="2" align="right"><b>CGST : </b>  </td>
                        <td align="center"><?php echo round($totalCGST,0) ;  ?></td>
                        <td colspan="2" align="right"><b>SGST : </b>  </td>
                        <td align="center"><?php echo round($totalCGST,0) ;  ?></td>
                        <td colspan="3" align="right"><b>Total GST : </b>  </td>
                        <td align="center"><?php echo round($totalCGST,0) *2 ;  ?></td>
                    </tr>
								
								
              
              
                  


								
								
								
								
								<tr>
								    <?php 
								        $mode = $rowordno['card_perc'];
								        $payment_mode_name=$rowordno['payment_mode_name'];
								        if($mode==0)
								        {
								            $mode_status = "Cash";
								        }
								        else if($mode==1)
								        {
								            $mode_status = "Bank";
								        }
								        else if($mode==2)
								        {
								            $mode_status = "Card";
								        } else {
								            echo " ";
								        }
								    
								    
								    
								    ?>
								    
								        <td colspan="2" align="left"><b> Mode of Payment :  </b><?php echo $payment_mode_name ? $payment_mode_name : $mode_status; ?></td>
    <td colspan="1.5" align="center"><b> Deposit : </b><?php echo $rowordno[13]; ?></td>
    <td colspan="2" align="center"><b> Deposit Amount : </b><?php echo $totalDeposit; ?></td>
    <td colspan="2" align="center"><b>Paid Amount :</b><b><?php echo "Rs.".$rowordno[10];?></b></td>
    <td colspan="3" align="center"> <b>Balance : </b><b><?php echo $ap; ?></b></td>
    
								    

                                 </tr>
                                 
                                  <tr>
                                    <td colspan="11"><b>Trial Date : </b> <?php echo $rowordno[14];?></td>     
                                 </tr>
                                 
                                  <tr>
                                    <td colspan="3" align="left"><b> Measurement :  </b><?php echo $rowordno[15]; ?></td>
                                    <td colspan="8" align="left"><b> Measurement Note : </b><?php echo $rowordno[16]; ?></td>
                                
                                 </tr>
                                 
                                  <tr>
                                    <td colspan="11"><b>Delivery : </b> <?php echo $rowordno['delivery'] .' '. $rowordno[17];?></td>     
                                 </tr>
                                 
                                 <tr>
                                    <td colspan="11"><b>Note : </b> <?php echo $rowordno[25];?></td>     
                                 </tr>
                                 
                                 <tr>
                                    <td colspan="4" align="left"><b> Given By :  </b><?php ?></td>
                                    <td colspan="10" align="left"><b> Taken By : </b><?php  ?></td>
                                
                                 </tr>
                                 
                                 <tr>
                                        <td colspan="12" align="left"><b> Transaction ID : </b><?php echo $transaction_id ;  ?></td>
                                    </tr>
			</table>

			</td>
			</tr>
			<tr>
				<td align="right">&nbsp;&nbsp;<?php 
  
    ?></td>
			</tr>

			<tr>
				<td>
					<hr/>
					
				</td>
			</tr>
			</table>
			<div style="width:826px; margin:auto; padding-top: 15px; text-align: right; padding-bottom: 11px;">
    
    
              <?php if($company_name == 'SAKAR TRADE LINK'){ ?>
              <p><b>SAKAR TRADE LINK</b></p>
              <?php }else{  ?>
    <p><b>SRI SHRINGARR FASHION STUDIO</b></p>          
              <?php }?>
</div>


<div style="width:826px; margin:auto; text-align: right; padding: 40px;"> 
    <p><b>AUTH. SIGNATORY</b></p>
</div>
		</div>
		
		
		
		<h3 style="text-align:center;">Payment History</h3>
		<hr />
	<?php 

// Query to fetch payment details for the given bill_id
$getpaymentssql = mysqli_query($con, "SELECT * FROM rent_amount WHERE bill_id LIKE '".$id."' order by id asc");

if($getpaymentssql) {
    // Check if any payments are found
    if(mysqli_num_rows($getpaymentssql) > 0) {
        echo "<table width='825' border='1' align='center'>";
        echo "<tr>
                <th>Sr No</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Payment Mode</th>
              </tr>";
              
        // Loop through and fetch each row from the results
        $counter = 1 ; 
        while($getpaymentssqlResult = mysqli_fetch_assoc($getpaymentssql)) {
            $amount = $getpaymentssqlResult['amount'];
            $date = $getpaymentssqlResult['return_date'];
            $payment_by = $getpaymentssqlResult['payment_by'];
            
            // Display the payment details
            echo "<tr  align='center'>
                    <td>".$counter."</td>
                    <td> Rs. ".number_format($amount,2)."</td>
                    <td>".$date."</td>
                    <td>".$payment_by."</td>
                  </tr>";
                  $counter++; 
        }
        
        echo "</table>";
    } else {
        echo "No payments found for this bill.";
    }
} else {
    echo "Error in SQL query: " . mysqli_error($con);
}

?>

		
		
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
									<td colspan="3" align="center"> 
            <B><U> CONFIRMATION MEMO </U></B></td>
								</tr>
								<tr>
									<td width="382" align="left" valign="top"><b><font size="-1" >MANUFACTURERS AND RETAILERS <font size="-1">OF BRIDAL SETS,<br />
      <font size="-1">HAIR ACCESSORIES AND  BROOCHES,<br/>
      <font size="-1">BRIDAL DUPATTAS,<br />
      <font size="-1">CHANIYA CHOLI,<br/>
      &amp; ALL KINDS OF ACCESSORIES.</b>
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
													<br/> 
													
													<span style="">Through Contact No:</span> <b><?php echo $brow[2]; ?></b></td>
												<td style="" width="94" rowspan="5">Bill No. <b><?php echo $rowordno[0]; ?></b>
													<br/>
													<span style="">Date: </span> <b><?php if(isset($rowordno[2]) and $rowordno[2]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[2])); ?></b></td>
											</tr>
											<tr>
												<td style="">Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $row2[2]; ?></b></td>
												<td style=""width="216">Pick-Up. :&nbsp;<b><?php echo $rowordno[6]; ?></b></td>
											</tr>
											<tr>
												<td style="" height="45">Address.: &nbsp;&nbsp;&nbsp;
													<?php echo $address ;  ?>
														<br/>
														<?php echo $row[6]; ?>
															<?php echo $row[8]; ?>
																<?php echo $row[9]; ?>
												</td>
												<td style="">Delivery :<b><?php echo $rowordno[7]; ?></b></td>
											</tr>
											<tr>
												<td style="">2nd Person Name.: &nbsp;&nbsp;&nbsp; <b><?php echo $rowordno[15]; ?></b></td>
												<?php if($is_online!=1){ ?>
													<td style="">Pick-Up Date :<b>
	  <?php if(isset($rowordno[11]) and $rowordno[11]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[11]));
 ?></b></td>
													<?php } ?>
														<!-- <td width="141">Commission : <?php //echo $rowordno[17]; ?><?php //echo $rowordno[18]; ?></td>-->
											</tr>
											<tr>
												<td style="">2nd Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $rowordno[16]; ?></b></td>
												<?php if($is_online!=1){ ?>
													<td style="">Delivery Date :<b><?php if(isset($rowordno[12]) and $rowordno[12]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[12])); ?></b></td>
													<?php } ?>
														<!--  <td>Total Commission : <?php //echo $rowordno[19]; ?></td>-->
											</tr>
										</table>
									</td>
								</tr>
							</table> 
  
  <table width="100%" border="1" cellpadding="4" cellspacing="0" id="results" >
  <tr style="">
  <th width="104">SR.NO.</th>
							<th width="104"><font size="">ITEM CODE</th>
							<th width="161">PARTICULARS</th>
							<th width="86">PRICE</th>
							<th width="86">QTY</th>
							<th width="93">RENT</th>
							<?php if($is_online==1){ ?>
								<th width="93">PICKUP DATE</th>
								<th width="93">DELIVERY DATE</th>
								<?php } ?>
									<th width="113">DEPOSIT</th>
									<!-- <th width="114">C0MMISSION</th>-->
									<th width="76">TOTAL RENT</th>
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
				
				</td>
				</tr>
				<tr>
					<td align="right">&nbsp;&nbsp;<?php 
  
    ?></td>
				</tr>
				<tr>
					<td height="31" align="center"><font size="3">
    
  
  
   Balance :<b>&nbsp;&nbsp;<?php echo  "Rs.".$ap;   ?>
   
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    Deposit&nbsp; :&nbsp;<b><?php echo $rowordno[13]; ?>&nbsp;&nbsp;<br/>Paid Amount <?php echo "Rs.".$rowordno[10];
  
    ?></b></td>
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
									<hr /> Receiver's Signature&nbsp;</td>
								<td width="219" valign="top" align="right"> <img src="shringaar.png" width="163" height="57" />
									<br/>
									<br/>
									<br/> Auth. Signatory&nbsp;</td>
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