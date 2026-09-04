<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../vendor/autoload.php'; // Include necessary libraries

use PHPMailer\PHPMailer\PHPMailer;
use Dompdf\Dompdf;

// Get today's date
$today = date('Y-m-d');
$today = '2024-09-01'; 

// Fetch all bill IDs for today's date
$billsql = "SELECT bill_id FROM phppos_rent WHERE DATE(bill_date) = '$today'";
$billsqlresult = mysqli_query($con, $billsql);

$bill_ids = [];
while ($billsqlrow = mysqli_fetch_assoc($billsqlresult)) {
    $bill_ids[] = $billsqlrow['bill_id'];
}

// Query to calculate total amounts
$query = "
    SELECT
        COUNT(*) AS total_bills,
        SUM(rent_amount) AS total_amount,
        SUM(rent_amount - bal_amount) AS total_balance_amount,
        SUM(bal_amount) AS total_paid_amount
    FROM phppos_rent
    WHERE DATE(bill_date) = '$today'
";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);

// Fetch payment type distribution
$query_cheque_cash = "
    SELECT
        SUM(CASE WHEN card_perc = 1 THEN rent_amount ELSE 0 END) AS total_amount_cheque,
        SUM(CASE WHEN card_perc = 0 THEN rent_amount ELSE 0 END) AS total_amount_cash,
        SUM(CASE WHEN card_perc = 2 THEN rent_amount ELSE 0 END) AS total_amount_card
    FROM phppos_rent
    WHERE DATE(bill_date) = '$today'
";
$result_cheque_cash = mysqli_query($con, $query_cheque_cash);
$row_cheque_cash = mysqli_fetch_assoc($result_cheque_cash);

// Chart URL using QuickChart.io API
$chartUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
    'type' => 'doughnut',
    'data' => [
        'labels' => ['Cheque', 'Cash', 'Card'],
        'datasets' => [[
            'label' => 'Amount Received',
            'data' => [
                $row_cheque_cash['total_amount_cheque'],
                $row_cheque_cash['total_amount_cash'],
                $row_cheque_cash['total_amount_card']
            ],
            'backgroundColor' => ['#36a2eb', '#ffcd56', '#ff6384'],
        ]]
    ],
    'options' => [
        'plugins' => [
            'legend' => ['position' => 'top']
        ]
    ]
]));

// Set up the email
$mail = new PHPMailer();
$mail->isSMTP();
$mail->Host = 'smtp.hostinger.com'; // Specify main and backup SMTP servers
$mail->SMTPAuth = true;
$mail->Username = 'support@srishringarr.com'; // SMTP username
$mail->Password = 'SSFSssfs@@2024'; // SMTP password
$mail->SMTPSecure = 'tls'; // Enable TLS encryption
$mail->Port = 587; // TCP port to connect to
$mail->setFrom('support@srishringarr.com', 'SSFS');
$mail->addAddress('vishwaaniruddh@gmail.com', 'ANiruddh');

// Email content
$mail->isHTML(true);
$mail->Subject = "Today's Rent Report - " . date('d-m-Y');
$mail->Body = "
    <h1>Today's Rent Report</h1>
    <table border='1' cellpadding='5' cellspacing='0'>
        <tr><th>Total Bills</th><td>{$row['total_bills']}</td></tr>
        <tr><th>Total Amount</th><td>" . number_format($row['total_amount'], 2) . "</td></tr>
        <tr><th>Total Paid Amount by Customers</th><td>" . number_format($row['total_paid_amount'], 2) . "</td></tr>
        <tr><th>Total Balance Amount</th><td>" . number_format($row['total_balance_amount'], 2) . "</td></tr>
        <tr><th>Total Amount Received Via Cheque</th><td>" . number_format($row_cheque_cash['total_amount_cheque'], 2) . "</td></tr>
        <tr><th>Total Amount Received Via Cash</th><td>" . number_format($row_cheque_cash['total_amount_cash'], 2) . "</td></tr>
        <tr><th>Total Amount Received Via Card</th><td>" . number_format($row_cheque_cash['total_amount_card'], 2) . "</td></tr>
    </table>
    <br/>
    <img src='{$chartUrl}' alt='Payment Type Distribution'/>
";

// Loop through each bill_id, fetch the corresponding invoice HTML, convert to PDF, and attach to the email
foreach ($bill_ids as $bill_id) {
    $invoice_url = "https://srishringarr.com/pos/reports/rent_report_detail.php?id=$bill_id";

    // Fetch invoice HTML using cURL
    $ch = curl_init($invoice_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Remove for production
    $invoice_html = curl_exec($ch);
    curl_close($ch);

    // Check if HTML content is fetched correctly
    if (!$invoice_html) {
        echo "Failed to fetch the invoice for bill ID: $bill_id";
        continue;
    }

    // Debug: Output the HTML content
    $tmp_file = tempnam(sys_get_temp_dir(), 'invoice_debug_'); // Use tempnam for temporary files
    file_put_contents($tmp_file, $invoice_html);

    // Convert HTML to PDF using Dompdf
    $dompdf = new Dompdf();
    $dompdf->loadHtml($invoice_html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Save PDF temporarily using tempnam
    $pdf_path = tempnam(sys_get_temp_dir(), 'invoice_');
    file_put_contents($pdf_path, $dompdf->output());

    // Debug: Check the PDF content
    $pdf_content = file_get_contents($pdf_path);
    if (empty($pdf_content)) {
        echo "PDF content is empty.";
        continue;
    }

    // Attach PDF to email
    $mail->addAttachment($pdf_path, "Invoice_$bill_id.pdf");
}

// Send the email with all attachments
if (!$mail->send()) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
} else {
    echo 'Message has been sent with all invoices attached.';
}

// Clean up temporary PDF files
foreach ($bill_ids as $bill_id) {
    // Adjust paths according to your system
    unlink(tempnam(sys_get_temp_dir(), 'invoice_')); // Remove temporary PDF files
}

// Close the database connection
mysqli_close($con);
?>
