<?php
include('../db_connection.php');

$con = OpenSrishringarrCon();
$this_con = OpenNewSrishringarrCon();

function getshipping_address($userid)
{
    global $this_con;
    $userid = mysqli_real_escape_string($this_con, $userid);
    $sql = mysqli_query($this_con, "SELECT * FROM shippingInfo WHERE userid = '$userid' ORDER BY id DESC");
    $sql_result = mysqli_fetch_assoc($sql);
    return $sql_result['address'];
}

// Input validation
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    die("Invalid Bill ID.");
}

// Fetch bill approval record
$result1 = mysqli_query($con, "SELECT * FROM approval WHERE bill_id = '$id'");
$rowordno = mysqli_fetch_array($result1);
if (!$rowordno) {
    die("No approval record found.");
}

// Fetch customer details
$person_id = $rowordno[1];

$bill_by = $rowordno['bill_by'];
$bill_prepare_by = $rowordno['bill_prepare_by'];
$pay_by = $rowordno['pay_by'];

$company_name = $rowordno['company_name'];
$result = mysqli_query($con, "SELECT * FROM phppos_people WHERE person_id = '$person_id'");
$row = mysqli_fetch_row($result);

$result_as = mysqli_query($con, "SELECT * FROM phppos_people WHERE person_id = '$person_id'");
$row_assoc = mysqli_fetch_assoc($result_as);
$acc_type = $row_assoc['acc_type'];

$address = ($acc_type == 2) ? getshipping_address($person_id) : $row[4] . '<br/>' . $row[6] . ' ' . $row[8] . ' ' . $row[9];

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>SHRINGAAR</title>
    <script type="text/javascript">
        function PrintDiv(divId) {
            var divToPrint = document.getElementById(divId);
            var popupWin = window.open('', '_blank', 'width=800,height=500');
            popupWin.document.open();
            popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</body></html>');
            popupWin.document.close();
        }
    </script>
    <style>
        /*#results td{*/
        /*    text-align:center;*/
        /*}*/
    </style>
</head>

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
    /*.discount_label{*/
    /*    color: red;*/
    /*    font-size:12px;*/
    /*}*/
</style>
<body>
    <div id="bill">



<?php 
$bill_by_text = '';
if($bill_by=='QUOTATION'){
    $bill_by_text = 'Quotation';
}else if($bill_by=='APPROVAL RECEIPT'){
    $bill_by_text = 'Approval';
}

?>

<p style="text-align:center;"><b><?php echo $bill_by_text ; ?></b></p>

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






                                <?php if ($company_name == 'SAKAR TRADE LINK') { ?>


                                    <!--<div style="text-align:left"> <img src="img/sakarQR.jpg" width="120px"> </div>-->
                                    <!--<div style="text-align:left;font-size:14px;"> <b>UPI ID: 237382406008836@cnrb</b> </div>-->

                                    <div style="text-align:left"> <img src="img/sakarQRICICI.jpeg" width="120px"> </div>
                                    <div style="text-align:left;font-size:14px;"> <b>UPI ID:
                                            MSSAKARTRADELINK.eazypay@icici</b> </div>

                                    <br />

                                <?php } else {
                                    ?>
                                    <div style="text-align:left"> <img src="img/sri_qr_icici.jpg" width="120px"> </div>
                                    <br>
                                    <div style="text-align:left;font-size:14px;"> <b>UPI ID:
                                            srishringarrfashionstudio@icici</b> </div>
                                    <br />

                                    <?php
                                }

                                ?>











                            </td>

                            <!--<img src="sri_logo.jpg" width="250px"/ style="padding-right:70px">-->


                            <?php



                            if ($company_name == 'SAKAR TRADE LINK') { ?>


                                <td style=" padding: 0px; margin:0px; text-align: center;     width: 33%;">
                                    <h1 style="text-align:center;letter-spacing: 8px;margin:0;white-space: nowrap;">SAKAR
                                        TRADE LINK</h1>
                                    <span>GSTIN:27AAGPP0302A1ZH</span>
                                    <hr>
                                    <img src="./img/ss_fly.png" width="250px">
                                </td>
                            <?php } else { ?>



                                <td style=" padding: 0px; margin:0px; text-align: center; 
    letter-spacing: 1px;    width: 33%;">
                                    <h3 style="text-align:center;margin:0;white-space: nowrap;">SRI SHRINGARR FASHION STUDIO
                                    </h3>
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



                    <table width="787" border="0" align="center">
                        <tr>
                            <td width="781" height="42">
                                <table width="780">

                                    <tr>
                                        <td colspan="2">
                                            <font size="2">M/s.&nbsp;&nbsp;<b><?php echo $row[0] . " " . $row[1]; ?></b>
                                            </font>
                                        </td>
                                        <td>Date:
                                            <b><?php if ($rowordno[2] != '0000-00-00')
                                                echo date('d/m/Y', strtotime($rowordno[2])); ?></b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <font size="2">Address: &nbsp;&nbsp;<b><?php echo $address; ?></b></font>
                                        </td>
                                        <td>Bill No: <b><?php echo $rowordno['new_bill_number'] ? $rowordno['new_bill_number'] : $rowordno['bill_id']; ?></b></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <font size="2">Contact No.: <b><?php echo $row[2]; ?></b></font>
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                </table>

                                <font size="2">
                                    <table width="780" border="1" cellpadding="4" cellspacing="0" id="results">
                                        <tr>
                                            <th>SR. NO.</th>
                                            <th>ITEM CODE</th>
                                            <th>PARTICULARS</th>
                                            <th>PRICE</th>
                                            <th>QTY</th>
                                            <th>AMOUNT</th>
                                            <th>DISCOUNT</th>
                                            <th>DISCOUNT(%)</th>
                                            <th>TAXABLE VALUE</th>
                                            <th>GST RATE</th>
                                            <th>CGST</th>
                                            <th>SGST</th>
                                            <th>TOTAL</th>
                                        </tr>
                                        <?php
                                        $s2 = 0;
                                        $k = 1;
                                        $total = 0;
                                        $totalq = 0;
                                        // echo "SELECT * FROM approval_detail WHERE bill_id = '$id'" ; 
                                        $sql2 = mysqli_query($con, "SELECT * FROM approval_detail WHERE bill_id = '$id'");
                                        while ($row2 = mysqli_fetch_array($sql2)) {






$item_per = $row2['item_per'];
$discount = $row2['discount'];
$dis_amount = $row2['dis_amount'];

$status_type = $row2['status_type'];
$final_amount = $row2['final_amount'];



                                            $item_name = $sku = mysqli_real_escape_string($con, $row2[1]);
                                            $res2 = mysqli_query($con, "SELECT * FROM phppos_items WHERE name = '$item_name' AND is_deleted = 0");
                                            $row1 = mysqli_fetch_array($res2);
                                            
                                            

                                            $pz = ($row1[6] == "") ? round(($row2[6] + $row2[7]) / $row2[2]) . ".00" : $row1[6];
                                            $amount = $row2[2] * $pz;


                                            $category_type = ($row1['category_type'] == 1) ? 'Jewellery' : 'Apparel';
                                            $qty = $row2[2];
                                            
                                            $single_rent = $row2['amount'];
                                            
                                            
                                            // echo $category_type ; 
                                        
                                            // $gst_rate = ($category_type == 'Jewellery') ? 3 : 12;
                                            
                                            if($category_type=='Jewellery'){
                                                $gst_rate= 3 ;
                                                
                                                $thisProductTotalTaxable = $single_rent / 1.03;
                                                $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.03;
                                                $cgst = $sgst = $thisProductTotalGst / 2;
                                            }else{
                                                
                                                
                                                if(strtotime($rowordno[2]) < strtotime('2025-09-22')){
                                                    // Before 22-09-2025, GST = 12%
                                                    $gst_rate = 12;
                                                    $thisProductTotalTaxable = $single_rent / 1.12;
                                                    $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.12;
                                                }else{
                                                    
                                                if($single_rent > 2500){
                                                    $gst_rate= 18 ;
                                                    $thisProductTotalTaxable = $single_rent / 1.18;
                                                    $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.18;
                                                }else{
                                                    $gst_rate= 5 ;
                                                    $thisProductTotalTaxable = $single_rent / 1.05;
                                                    $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.05;
                                                }
                                                }
                                                
                                                
                                                $cgst = $sgst = $thisProductTotalGst / 2;
                                                
                                            }




                                            // if ($category_type == 'Jewellery') {
                                                
                                                
                                            // } else if ($category_type == 'Apparel') {
                                            //     $thisProductTotalTaxable = $single_rent / 1.12;
                                            //     $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.12;
                                            //     $cgst = $sgst = $thisProductTotalGst / 2;
                                            // }

                                            $single_taxable = $single_rent - ($cgst + $sgst);
                                            
                                            $total_rent = $single_rent * $qty;


                                            $totalGST += ($cgst + $sgst);

                                            $total_taxable = $single_taxable * $qty;
                                            $totalCGST += $cgst;
                                            $totalSGST += $sgst;

                                            if (str_contains(strtolower($sku), strtolower($_REQUEST['sku'])) && $_REQUEST['sku']) {
                                                $skuFound = true;
                                            } else {
                                                $skuFound = false;
                                            }

                                            $signle_cgst = $signle_sgst = round( $cgst, 2);
                                            $thisTotal = $signle_cgst + $signle_sgst;

                                            $taxableAmount = $row2[7] - $thisTotal;

                                            $totaltaxableAmount += $taxableAmount;
                                            
                                            
                                        
                                            
                                            $total_rent = $amount - $row2[6] ; 
                                            $grandTotalRent += $total_rent;


if($item_per=='%'){
    $discountlabel = '<span class="discount_label">'  . $discount.$item_per   . '</span> ' ; 
}else{
    $discountlabel='';
}

                                            echo "<tr align='center'>
                                    <td>$k</td>
                                    <td>" . ($row1[0] ?: $row2[1]) . "</td>
                                    <td>$row1[1]</td>
                                    <td>$pz</td>
                                    <td>$row2[2]</td>
                                    <td>$amount</td>
                                    <td style='white-space: nowrap;'>$row2[6]</td>
                                    <td>$discountlabel</td>
                                    <td>$taxableAmount</td>
                                    <td>$gst_rate%</td>
                                    <td>$signle_cgst</td>
                                    <td>$signle_sgst</td>
                                    <td>$total_rent</td>
                                    
                                  </tr>";

                                            $s2 += $row2[7];
                                            $total += $row2[7];
                                            $totalq += $row2[2];
                                            $k++;
                                        }
                                        ?>

                                        <tr align="center">
                                            <td colspan="8"></td>
                                            <td colspan="3" align="right"><b>Total Taxable Value:</b></td>
                                            <td colspan="2"><?php echo number_format($totaltaxableAmount, 2); ?></td>
                                        </tr>

                                        <tr align="center">
                                            <td colspan="8"></td>
                                            <td colspan="3" align="right"><b>Total CGST:</b></td>
                                            <td colspan="2"><?php echo number_format($totalCGST, 2); ?></td>
                                        </tr>

                                        <tr align="center">
                                            
                                            <td colspan="5"  align="left"><b>Mode of Payment:</b>  <?php echo $pay_by ; ?> </td>
                                            <td colspan="3"></td>
                                            <td colspan="3" align="right"><b>Total SGST:</b></td>
                                            <td colspan="2"><?php echo number_format($totalSGST, 2); ?></td>
                                            
                                        </tr>

                                        <tr align="center">

                                            <td colspan="5" align="left"><b>Total Quantity: <?php echo $totalq; ?></b>
                                            </td>
                                            <td colspan="3" align="left"><b>
                                                
                                                <!--Gross Total: Rs. -->
                                                
                                                <?php
                                                // echo $s2;
                                                
                                                ?></b></td>

                                            <td colspan="3" align="right"><b>Net Payable:</b></td>
                                            <td colspan="2"><?php echo number_format($grandTotalRent, 0); ?></td>
                                        </tr>


                                        <tr align="center">



                                        </tr>

                                        <?php if ($rowordno['card_perc'] > 0):
                                            $total += $rowordno['card_amt'];
                                            ?>
                                            <tr align="center">
                                                <td colspan="6"></td>
                                                <td colspan="2" align="left"><b>Card
                                                        <?php echo $rowordno['card_perc']; ?>%</b></td>
                                                <td colspan="4" align="left"><b><?php echo $rowordno['card_amt']; ?></b>
                                                </td>
                                            </tr>
                                            <tr align="center">
                                                <td colspan="6"></td>
                                                <td align="left">Net Payable:</td>
                                                <td align="left"><b>Rs. <?php echo $total; ?></b></td>
                                            </tr>
                                        <?php endif; ?>

                                        <tr align="center">
                                            <td colspan="5" align="left">
                                                
                                                <b>Bill Prepare by:</b>
                                                    <?php echo  $bill_prepare_by ; ?>
                                                    
                                                    
                                                </td>
                                            
                                            
                                            
                                            
                                            <td colspan="3" align="left">
                                                <b>Amount Paid: </b>
                                                <?php echo number_format($rowordno[4],0); ?></td>
                                            <td colspan="3" align="right"><b>Balance:</td>
                                            <td colspan="2" align="">
                                            <?php 
                                            
                                            
                                            $balance = $grandTotalRent - $rowordno[4];
                                            // echo  number_format($total,0) . '</br>';
                                            echo  number_format($balance,0) . '</br>';
                                            
                                            
                                            ?>
    </b></td>
                                        </tr>
                                        <tr align="left">
                                            <td colspan="13"><b>Note:</b> <?php echo $rowordno['note']; ?></td>

                                        </tr>
                                    </table>
                                </font>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <hr />

                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>

        <div style="width:826px; margin:auto; padding-top: 15px; text-align: right; padding-bottom: 11px;">


            <?php if ($company_name == 'SAKAR TRADE LINK') { ?>
                <p><b>SAKAR TRADE LINK</b></p>
            <?php } else { ?>
                <p><b>SRI SHRINGARR FASHION STUDIO</b></p>
            <?php } ?>
        </div>


        <div style="width:826px; margin:auto; text-align: right; padding: 40px;">
            <p><b>AUTH. SIGNATORY</b></p>
        </div>
    </div>

    <h3 style="text-align:center;">Payment History</h3>
    <hr />
    <?php
    
    // echo "SELECT * FROM paid_amount WHERE bid = '$id' ORDER BY id ASC";
    $getpaymentssql = mysqli_query($con, "SELECT * FROM paid_amount WHERE bid = '$id' ORDER BY id ASC");

    if ($getpaymentssql && mysqli_num_rows($getpaymentssql) > 0) {
        echo "<table width='825' border='1' align='center'>
                <tr>
                    <th>Sr No</th>
                    <th>Amount</th>
                    <th>Payment Date</th>
                    <th>Payment Mode</th>
                </tr>";
        $counter = 1;
        while ($getpayment = mysqli_fetch_assoc($getpaymentssql)) {
            echo "<tr align='center'>
                    <td>{$counter}</td>
                    <td>Rs. " . number_format($getpayment['amount'], 2) . "</td>
                    <td>{$getpayment['return_date']}</td>
                    <td>{$getpayment['payment_by']}</td>
                  </tr>";
            $counter++;
        }
        echo "</table>";
    } else {
        echo "<p style='text-align:center;'>No payment records found.</p>";
    }
    ?>
    
    <hr />
    <br />
    
<table width="538" border="1" cellpadding="4" cellspacing="0">
	<tbody><tr>
    	<td colspan="4">Sales Return Quantity Details</td>
    </tr>
    <tr>
    	<td width="73">Sr. No.</td>
        <td width="133">Item Code</td>
        <td width="126">Return Quantity</td>
        <td width="156">Return Date</td>
    </tr>
    
    <?php 
    $return_sr= 1;
    $total_qty = 0 ;
    $return_sql = mysqli_query($con,"select * from return_qty where bill_id='".$id."'");
    while($return_sql_result = mysqli_fetch_assoc($return_sql)){
        $item_code = $return_sql_result['item_code'];
        $qty = $return_sql_result['qty'];
        $return_date = $return_sql_result['return_date'];
        
        $total_qty = $total_qty + $qty ; 
        
        ?>
            <tr>
        	<td><font size="2"><?php echo $return_sr; ?></font></td>
            <td><font size="2"><?php echo $item_code;  ?></font></td>
            <td><font size="2"><?php echo $qty; ?></font></td>
            <td><font size="2"><?php echo $return_date ; ?></font></td>
        </tr>
        
    <?php }
    
    ?>
        
		    <tr><td colspan="2" align="right">Total Qty : </td><td><?php echo $total_qty ; ?></td></tr>
</tbody></table>
    
    





    
    <div id="pageNavPosition"></div>
    <center><a href="#"
            onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a
            href="http://sarmicrosystems.in/shringaar/application/views/reports/rentReport.php">Back</a></center>
</body>

</html>