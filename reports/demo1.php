<?php
include('../db_connection.php');

$con = OpenSrishringarrCon();
$this_con = OpenNewSrishringarrCon();

function getshipping_address($userid) {
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
<body>
<div id="bill">
    <table width="787" border="0" align="center">
        <tr>
            <td width="781" height="42">
                <table width="780">
                    <tr>
                        <td colspan="3" align="center" style="padding-left:100px;">
                            <font size="2"><b><u><?php echo $rowordno[8]; ?></u></b></font>
                        </td>
                    </tr>
                    <tr>
                        <td width="346" align="left" valign="top">
                            <b><p>
                                <font size="-1">MANUFACTURERS AND RETAILERS OF BRIDAL SETS</font><br />
                                <font size="-1">HAIR ACCESSORIES AND BROOCHES</font><br />
                                <font size="-1">BRIDAL DUPATTAS</font><br />
                                <font size="-1">CHANIYA CHOLI &amp; ALL KINDS OF ACCESSORIES.</font>
                            </p></b>
                        </td>
                        <td colspan="2" align="right"><img src="bill.PNG" width="408" height="165" /></td>
                    </tr>
                    <tr>
                        <td colspan="2"><font size="2">M/s.&nbsp;&nbsp;<b><?php echo $row[0] . " " . $row[1]; ?></b></font></td>
                        <td>Date: <b><?php if ($rowordno[2] != '0000-00-00') echo date('d/m/Y', strtotime($rowordno[2])); ?></b></td>
                    </tr>
                    <tr>
                        <td colspan="2"><font size="2">Address: &nbsp;&nbsp;<b><?php echo $address; ?></b></font></td>
                        <td>Bill No: <b><?php echo $rowordno['new_bill_number']; ?></b></td>
                    </tr>
                    <tr>
                        <td><font size="2">Contact No.: <b><?php echo $row[2]; ?></b></font></td>
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
                            <th>TAXABLE VALUE</th>
                            <th>GST RATE</th>
                            <th>CGST</th>
                            <th>SGST</th>
                            <th>TOTAL</th>
                        </tr>
                        <?php
                        $s2 = 0; $k = 1; $total = 0; $totalq = 0;
                        $sql2 = mysqli_query($con, "SELECT * FROM approval_detail WHERE bill_id = '$id'");
                        while ($row2 = mysqli_fetch_array($sql2)) {
                            
                            
                            
                            
                            
                            
                            
                            
                            
                            $item_name = $sku = mysqli_real_escape_string($con, $row2[1]);
                            $res2 = mysqli_query($con, "SELECT * FROM phppos_items WHERE name = '$item_name' AND is_deleted = 0");
                            $row1 = mysqli_fetch_array($res2);

                            $pz = ($row1[6] == "") ? round(($row2[6] + $row2[7]) / $row2[2]) . ".00" : $row1[6];
                            $amount = $row2[2] * $pz;


        $category_type = ($row1['category_type'] == 1) ? 'Jewellery' : 'Apparel';
        $qty = $row2[2] ; 
        $single_rent = $row2['amount'];
        // echo $category_type ; 
        
        $gst_rate = ($category_type == 'Jewellery') ? 3 : 12;

        
        
        if($category_type=='Jewellery'){
            $thisProductTotalTaxable = $single_rent/1.03 ; 
            $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.03 ; 
            $cgst = $sgst = $thisProductTotalGst / 2 ;
        }else if($category_type=='Apparel'){
            $thisProductTotalTaxable = $single_rent/1.12 ;
            $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.12 ; 
            $cgst = $sgst = $thisProductTotalGst / 2 ;
        }
        
        $single_taxable = $single_rent - ($cgst + $sgst);
        $total_rent = $single_rent * $qty;
        

        $totalGST += ($cgst + $sgst);
        
        $total_taxable = $single_taxable * $qty  ; 
        $totalCGST += $cgst;
        $totalSGST += $sgst;
        
        if (str_contains(strtolower($sku), strtolower($_REQUEST['sku'])) && $_REQUEST['sku'] ) {
            $skuFound = true;
        } else {
            $skuFound = false;
        }

$signle_cgst = $signle_sgst = round($qty*$cgst, 2) ; 
$thisTotal = $signle_cgst + $signle_sgst ;

$taxableAmount = $row2[7] - $thisTotal ; 

$totaltaxableAmount += $taxableAmount;
$grandTotalRent += $total_rent ;  

                            echo "<tr align='center'>
                                    <td>$k</td>
                                    <td>" . ($row1[0] ?: $row2[1]) . "</td>
                                    <td>$row1[1]</td>
                                    <td>$pz</td>
                                    <td>$row2[2]</td>
                                    <td>$amount</td>
                                    <td>$row2[6]</td>
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
    <td colspan="2" align="right"><b>Gross Total:</b></td>
    <td colspan="2"><?php echo number_format($totaltaxableAmount, 2); ?></td>
</tr>

<tr align="center">
    <td colspan="8"></td>
    <td colspan="2" align="right"><b>+ CGST:</b></td>
    <td colspan="2"><?php echo number_format($totalCGST, 2); ?></td>
</tr>

<tr align="center">
    <td colspan="8"></td>
    <td colspan="2" align="right"><b>+ SGST:</b></td>
    <td colspan="2"><?php echo number_format($totalSGST, 2); ?></td>
</tr>

<tr align="center">

    <td colspan="4" align="left"><b>Total Quantity: <?php echo $totalq; ?></b></td>
    <td colspan="4" align="left"><b>Gross Total: Rs. <?php echo $s2; ?></b></td>
                            
    <td colspan="2" align="right"><b>Net Payable:</b></td>
    <td colspan="2"><?php echo number_format($grandTotalRent, 2); ?></td>
</tr>

                        
                        <tr align="center">
                            
                            

                        </tr>

                        <?php if ($rowordno['card_perc'] > 0): 
                            $total += $rowordno['card_amt'];
                        ?>
                            <tr align="center">
                                <td colspan="6"></td>
                                <td colspan="2" align="left"><b>Card <?php echo $rowordno['card_perc']; ?>%</b></td>
                                <td colspan="4" align="left"><b><?php echo $rowordno['card_amt']; ?></b></td>
                            </tr>
                            <tr align="center">
                                <td colspan="6"></td>
                                <td align="left">Net Payable:</td>
                                <td align="left"><b>Rs. <?php echo $total; ?></b></td>
                            </tr>
                        <?php endif; ?>

                        <tr align="center">
                            <td colspan="4" align="left"><b>Date: <?php echo date('d/m/Y', strtotime($rowordno[2])); ?></b></td>
                            <td colspan="4" align="left"><b>Amount Paid: Rs. <?php echo $rowordno[4]; ?></b></td>
                            <td colspan="2" align="right"><b>Balance:</td>
                            <td colspan="2" align="">Rs. <?php echo number_format(($total - $rowordno[4]),2); ?></b></td>
                        </tr>
                        <tr align="left">
                            <td colspan="12"><b>Note: <?php echo $rowordno[11]; ?></b></td>

                        </tr>
                    </table>
                </font>
            </td>
        </tr>

        <tr><td>
            <hr />
            <table width="784" border="0">
                <tr>
                    <td width="419" valign="top">
                        <ul>
                            <li>Subject to Mumbai jurisdiction &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>E. & O . E</b></li>
                            <li>Time 11 a.m. to 6 p.m.</li>
                        </ul>
                    </td>
                    <td width="355" align="right" valign="top">
                        <img src="shringaar.png" width="163" height="57" /><br /><br /><br />
                        <font>Auth. Signatory</font>
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>

    <h3 style="text-align:center;">Payment History</h3>
    <hr />
    <?php
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
</div>
</body>
</html>
