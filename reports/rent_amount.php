<?php
ini_set("display_errors", 0);
include('../db_connection.php');
$con = OpenSrishringarrCon();

$total2 = 0;
$alph = isset($_POST['alph']) ? $_POST['alph'] : '';

if (isset($_POST['submit'])) {
    $qry = "SELECT * FROM `phppos_people`";
    if ($alph != "") {
        $qry .= " WHERE first_name LIKE '$alph%'";
    }
    $qry .= " ORDER BY `first_name` ASC";
    $res = mysqli_query($con, $qry);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Balance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 80%;
            margin: auto;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        select, input[type="submit"] {
            padding: 8px;
            margin: 10px;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #007bff;
            color: white;
        }
        a {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
        .btn {
            padding: 10px 15px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
    <script>
        function PrintDiv() {
            var divToPrint = document.getElementById('bill');
            var popupWin = window.open('', '_blank', 'width=800,height=500');
            popupWin.document.open();
            popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</body></html>');
            popupWin.document.close();
        }
    </script>
</head>
<body>

<div class="container">
    <h2>Customer Balance Amount Report</h2>

    <a href="/pos/home_dashboard.php" class="btn">Back</a>
    <button class="btn" onclick="PrintDiv();">Print</button>

    <form method="post">
        <label for="alph">Search Name Starting with:</label>
        <select name="alph" id="alph">
            <?php
            foreach (range('A', 'Z') as $char) {
                echo "<option value='$char' " . ($alph == $char ? 'selected' : '') . ">$char</option>";
            }
            ?>
        </select>
        <input type="submit" name="submit" value="Search" class="btn">
    </form>

    <div id="bill">
        <table>
            <tr>
                <th>Sr.No.</th>
                <th>Customer Name</th>
                <th>Mobile No.</th>
                <th>Invoice - Amount</th>
                <th>Net Amount</th>
            </tr>
            <?php
            $i = 1;
            while ($row = mysqli_fetch_row($res)) {
                $sql4 = mysqli_query($con, "SELECT SUM(rent_amount), SUM(sgstamt), SUM(cgstamt), SUM(igstamt), SUM(card_amt) FROM `phppos_rent` WHERE `cust_id`='$row[11]'");
                $row4 = mysqli_fetch_row($sql4);
                $sql5 = mysqli_query($con, "SELECT SUM(amount) FROM `rent_amount` WHERE `cust_id`='$row[11]'");
                $row5 = mysqli_fetch_row($sql5);

                $sm = array_sum($row4);
                $ab = round($sm - $row5[0], 2);

                if ($ab == 0) {
                    continue;
                }
                ?>
                <tr>
                    <td><?php echo $i; ?></td>
                    <td><?php echo $row[0] . " " . $row[1]; ?></td>
                    <td><?php echo $row[2]; ?></td>
                    <td>
                        <?php
                        $entries = [];
                        $required_total_balance_amount = 0;
                        $getbillsql = mysqli_query($con, "SELECT * FROM `phppos_rent` WHERE `cust_id`='$row[11]' ORDER BY bill_id DESC");

                        while ($getbillsql_result = mysqli_fetch_assoc($getbillsql)) {
                            $rent_amount = $getbillsql_result['rent_amount'];
                            $billID = $getbillsql_result['bill_id'];
                            $new_bill_number = $getbillsql_result['new_bill_number'];
                            $bal_amount = $getbillsql_result['bal_amount'];
                            $balanceAmount = $rent_amount - $bal_amount;
                            
                            $bill_no = ($new_bill_number ? $new_bill_number : $billID) ;

                            if ($balanceAmount > 0 && $required_total_balance_amount < $ab) {
                                $required_total_balance_amount += $balanceAmount;
                                if ($required_total_balance_amount > $ab) {
                                    break;
                                }
                                $entries[] = "<a href='./rent_report_detail.php?id=$billID' target='_blank'> $bill_no </a> - " . number_format($balanceAmount, 2) . " - <a href='rent_payment.php?cid=$row[11]&amt=$balanceAmount&bill_id=$billID'><b>Payment</b></a>";
                            }
                        }
                        echo implode('<br>', $entries);
                        ?>
                    </td>
                    <td><?php echo "Rs. " . $ab . "/-"; ?></td>
                </tr>
                <?php
                $i++;
                $total2 += $ab;
            }
            ?>
            <tr>
                <td colspan="3"></td>
                <td colspan="1" align="right"><b>Total Amount: <?php echo "Rs. " . $total2 . "/-"; ?></b></td>
                <td colspan="1"></td>
            </tr>
        </table>
    </div>
</div>

<?php CloseCon($con); ?>

</body>
</html>
