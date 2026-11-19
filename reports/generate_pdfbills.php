<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

$id = $_GET['id'];

$result2 = mysqli_query($con, "SELECT * FROM `phppos_rent` WHERE bill_id='$id'");
$ss_rowordno = mysqli_fetch_assoc($result2);

$ss_orderid = $ss_rowordno['order_id'];

$ss_con = OpenNewSrishringarrCon();

$result1 = mysqli_query($con, "SELECT * FROM `phppos_rent` WHERE bill_id='$id'");
$rowordno = mysqli_fetch_array($result1);

$company_name = $rowordno['company_name'];
$throught = $rowordno['throught'];


$sql2=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$rowordno[1]'");
$row2=mysqli_fetch_row($sql2); 


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

ini_set("memory_limit", "-1");
set_time_limit(0);

// Include TCPDF library
require '../vendor/autoload.php'; // Include necessary libraries



// Set document information

// Set default header data

$pdf = new TCPDF();
$pdf->AddPage();


$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('Invoice '.$id);
$pdf->SetSubject('HTML to PDF');


// Set the document title
$pdf->SetTitle('Invoice');

// Set font
$pdf->SetFont('helvetica', '', 12);

if ($company_name == 'SAKAR TRADE LINK') {
    $qr_image = 'img/sakarQRICICI.jpeg';
    $upi_id = 'MSSAKARTRADELINK.eazypay@icici';
} else {
    $qr_image = 'img/sri_qr_icici.jpg';
    $upi_id = 'srishringarrfashionstudio@icici';
}


$html = '
<div id="bill" style="font-size:12px;">
    <p style="text-align:center;"><b><u>INVOICE</u></b></p>
    
    <table width="825" border="0" align="center">
        <tr>
            <td width="819" height="42">
                <table width="100%">
                    <tr>
                        <td style="padding:0; margin:0; width:33%;">';

// Handle conditional content based on $company_name
if ($company_name == 'SAKAR TRADE LINK') {
    $html .= '
                            <div style="text-align:left;">
                                <img src="img/sakarQRICICI.jpeg" width="120px">
                            </div>
                            <div style="text-align:left;font-size:14px;">
                                <b>UPI ID: MSSAKARTRADELINK.eazypay@icici</b>
                            </div>
                            <br>';
} else {
    $html .= '
                            <div style="text-align:left;">
                                <img src="img/sri_qr_icici.jpg" width="120px">
                            </div>
                            <br>
                            <div style="text-align:left;font-size:14px;">
                                <b>UPI ID: srishringarrfashionstudio@icici</b>
                            </div>
                            <br>';
}

$html .= '
                        </td>';

if ($company_name == 'SAKAR TRADE LINK') {
    $html .= '
                        <td style="padding:0; margin:0; text-align:center; width:33%;">
                            <h1 style="text-align:center;letter-spacing:8px;margin:0;white-space:nowrap;">SAKAR TRADE LINK</h1>
                            <span>GSTIN: 27AAGPP0302A1ZH</span>
                            <hr>
                            <img src="./img/ss_fly.png" width="250px">
                        </td>';
} else {
    $html .= '
                        <td style="padding:0; margin:0; text-align:center; letter-spacing:1px; width:33%;">
                            <h3 style="text-align:center;margin:0;white-space:nowrap;">SRI SHRINGARR FASHION STUDIO</h3>
                            <span>GSTIN: 27ADRPP9888P1ZW</span>
                            <hr>
                            <img src="sri_logo.jpg" width="160px">
                        </td>';
}

$html .= '
                        <td style="padding:0; margin:0; text-align:right; width:33%;">
                            <div>Shyamkamal Building B,</div>
                            <div>Wing B/1, Flat No.104, 1st Floor,</div>
                            <div>Agarwal Market, Vile Parle (East),</div>
                            <div>Mumbai-400057, India.</div>
                            <div>Phone - +91-9324243011/ +91-7400413163</div>
                            <div>Email - rajanipodar@gmail.com</div>
                        </td>
                    </tr>
                </table>
                <br>
                <hr style="margin:3px;border-top:1px solid #000;">
                
                <table width="100%">
                    <tr>
                        <td>';

if ($company_name != 'SAKAR TRADE LINK') {
    $html .= '
                            <div><b><u>Bank Account Details</u></b></div>
                            <br>
                            <div style="font-size:10px;">SRI SHRINGARR FASHION STUDIO</div>
                            <div>Account number: 756305000529</div>
                            <div>IFSC: ICICI0007563</div>
                            <div>Bank name: ICICI BANK</div>
                            <div>Branch: VILE PARLE EAST</div>';
}

$html .= '
                            <br>
                            <div style="width:300px; height:15px;"><b>Name:</b> ' . htmlspecialchars($row2[0]) . ' ' . htmlspecialchars($row2[1]) . '</div>
                            <div style="height:15px;"><b>Contact No:</b> ' . htmlspecialchars($row2[2]) . '</div>
                            <div><b>Address:</b> ' . htmlspecialchars($row2[4]) . '</div>
                            <div style="height:15px;"><b>2nd Person Name:</b> ' . htmlspecialchars($rowordno[19]) . '</div>
                            <div style="height:15px;"><b>2nd Contact No.:</b> ' . htmlspecialchars($rowordno[20]) . '</div>
                        </td>
                        <td>
                            <div style="width:220px;height:15px;"><b>Through Name:</b> ' . htmlspecialchars($brow[0] ?? '') . '</div>
                            <br>
                            <div><b>Through Contact No:</b> ' . htmlspecialchars($brow[2] ?? '') . '</div>
                            <br>
                            <div style="height:15px;"><b>Pick-Up By:</b></div>
                            <div style="height:15px;"><b>Delivery By:</b></div>
                            <br>
                            <div style="height:15px;"><b>Pick-Up Date:</b> ';

if (isset($rowordno[11]) && $rowordno[11] != '0000-00-00') {
    $html .= date('d/m/Y', strtotime($rowordno[11]));
}

$html .= '
                            </div>
                        </td>
                        <td>
                            <div style="width:320px;"><b>Bill No.:</b> ' . htmlspecialchars($rowordno[0]) . '<br><b>Date:</b> ';

if (isset($rowordno[2]) && $rowordno[2] != '0000-00-00') {
    $html .= date('d/m/Y', strtotime($rowordno[2]));
}

$html .= '
                            </div>
                            <div style="width:320px;">
                                <br>
                                <b style="font-size:11px;"><u>TERMS & CONDITION:</u></b>
                                <ul style="padding:0;">
                                    <li><b>Once an order is booked, it cannot be changed, exchanged, or canceled.</b></li>
                                    <li>Rental is for 3 days only, with a 5% extra charge for each additional day.</li>
                                    <li>Security deposit is compulsory.</li>
                                    <li>Any damage done will be deducted from the security deposit.</li>
                                    <li><b>No money will be refunded under any circumstances.</b></li>
                                    <li>Subject to Mumbai Jurisdiction.</li>
                                </ul>
                            </div>
                        </td>
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
                        <th style="padding:3px;" width="119"><center>DEPOSIT</center></th>
                        <th style="padding:3px;" width="88"><center>TAXABLE RENT</center></th>
                    </tr>';

$total = 0;
$total1 = 0;
$totalq = 0;
$i = 1;
$sql2 = mysqli_query($con, "SELECT * FROM `order_detail` WHERE bill_id='$id'");

$totalCGST = 0;
$totalGST = 0;
$totalTaxableAmount = 0;
$totalDeposit = 0;

while ($row2 = mysqli_fetch_array($sql2)) {
    $sq = "SELECT * FROM phppos_items WHERE name='" . $row2[1] . "' AND is_deleted = 0";
    $res2 = mysqli_query($con, $sq);
    $row1 = mysqli_fetch_row($res2);
    
    $productsql = mysqli_query($con, "SELECT * FROM phppos_items WHERE name='" . $row2[1] . "'");
    $productsqlResult = mysqli_fetch_assoc($productsql);
    
    $categoryName = $productsqlResult['category'];
    $categorysql = mysqli_query($con, "SELECT * FROM categories WHERE category='" . $categoryName . "'");
    $categorysqlResult = mysqli_fetch_assoc($categorysql);

    $item_code = $row2[1];
    $item_qty = $row2[3];
    $item_mrp = $row2[4];
    $item_deposit = $row2[5];
    $item_total = $row2[6];

    $total += $item_total;
    $totalDeposit += $item_deposit;
    $totalCGST += $item_total * 0.09;
    $totalGST += $item_total * 0.09;
    $totalTaxableAmount += $item_total;

   
    $i++;
}

$html .= '
        <tr>
            <td colspan="6" style="text-align:right;">Total</td>
            <td>' . htmlspecialchars($total) . '</td>
            <td>' . htmlspecialchars($totalDeposit) . '</td>
            <td>' . htmlspecialchars($totalTaxableAmount) . '</td>
        </tr>
    </table>
</div>';

// echo $html ; 

$pdf->writeHTML($html, true, false, true, false, '');

// Output the PDF (you can also save it to a file)
$pdf->Output('invoice.pdf', 'I');
?>
