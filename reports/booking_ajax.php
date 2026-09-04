<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

// Retrieve and sanitize input
$barcode = isset($_REQUEST['barcode']) && $_REQUEST['barcode'] !== '' ? $_REQUEST['barcode'] : (isset($_REQUEST['barcode2']) && $_REQUEST['barcode2'] !== '' ? $_REQUEST['barcode2'] : '');
$fromdate = isset($_REQUEST['fromdate']) && $_REQUEST['fromdate'] !== '' ? date('Y-m-d', strtotime($_REQUEST['fromdate'])) : '';
$todate = isset($_REQUEST['todate']) && $_REQUEST['todate'] !== '' ? date('Y-m-d', strtotime($_REQUEST['todate'])) : '';

// ✅ Simple and correct query with proper comma separation
$future_qry = "
    SELECT 
        a.bill_id,
        GROUP_CONCAT(a.item_id ORDER BY a.item_id SEPARATOR ', ') AS item_ids,
        b.pick_date AS rentpickdate,
        b.delivery_date AS rentdeliverydate,
        b.new_bill_number,
        b.cust_id,
        b.trail_date,
        b.measurement,
        b.is_delivery
    FROM order_detail a
    INNER JOIN phppos_rent b ON a.bill_id = b.bill_id
";

// Build conditions
$conditions = [];

if ($barcode !== '') {
    $conditions[] = "a.item_id = '$barcode'";
}

if ($fromdate !== '' && $todate !== '') {
    $conditions[] = "b.pick_date BETWEEN '$fromdate' AND '$todate'";
} elseif ($fromdate !== '') {
    $conditions[] = "b.pick_date >= '$fromdate'";
} elseif ($todate !== '') {
    $conditions[] = "b.pick_date <= '$todate'";
}

if (count($conditions) > 0) {
    $future_qry .= " WHERE " . implode(' AND ', $conditions);
}

// ✅ Group by bill so multiple items combine correctly
$future_qry .= " AND b.pick_date >= CURDATE() GROUP BY a.bill_id ORDER BY b.pick_date";
$res_future = mysqli_query($con, $future_qry);
$num_future = mysqli_num_rows($res_future);

// Past bookings query
$past_qry = str_replace("b.pick_date >= CURDATE()", "b.pick_date < CURDATE()", $future_qry);
$res_past = mysqli_query($con, $past_qry);
$num_past = mysqli_num_rows($res_past);
?>

<style>
    td, th {
        border: 1px solid black;
        font-size: 12px;
        padding: 4px;
        vertical-align: top;
    }
    table {
        margin: auto;
        border-collapse: collapse;
        width: 98%;
    }
    th {
        text-align: center;
    }

    @media print {
        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        table, th, td {
            border: 1px solid #000 !important;
            border-collapse: collapse !important;
        }
        th {
            background: #f2f2f2 !important;
        }
        @page {
            size: landscape;
            margin: 10mm;
        }
    }
</style>

<!-- FUTURE BOOKINGS -->
<div id="futureBookings">
    <h3 align="center">Future Bookings <?php echo ($barcode ? ' - ' . $barcode : ''); ?></h3>
    <?php if ($num_future > 0) { ?>
        <div class="table-responsive">
            <table border="1">
                <tr>
                    <th>Sr. No</th>
                    <th>Bill No.</th>
                    <th>Item Codes</th>
                    <th>Customer Name</th>
                    <th>Contact No.</th>
                    <th>Pick Date</th>
                    <th>Delivery Date</th>
                    <th>Trail Date</th>
                    <th>Measurement</th>
                    <th>Is Delivery</th>
                </tr>
                <?php
                $i = 1;
                while ($row = mysqli_fetch_assoc($res_future)) {
                    $cust_id = $row['cust_id'];
                    $cust = mysqli_fetch_assoc(mysqli_query($con, "SELECT first_name, last_name, phone_number FROM phppos_people WHERE person_id='$cust_id'"));
                ?>
                    <tr align="center">
                        <td><?php echo $i++; ?></td>
                        <td><?php echo $row['new_bill_number'] ?: $row['bill_id']; ?></td>
                        <td style="text-align:left; white-space:pre-wrap;">
    <?php
    $items = explode(', ', $row['item_ids']);
    $chunks = array_chunk($items, 4);
    $formatted = [];
    foreach ($chunks as $chunk) {
        $formatted[] = implode(', ', $chunk);
    }
    echo implode("<br>", $formatted);
    ?>
</td>

                        <td><?php echo trim($cust['first_name'] . ' ' . $cust['last_name']); ?></td>
                        <td><?php echo $cust['phone_number']; ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['rentpickdate'])); ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['rentdeliverydate'])); ?></td>
                        <td>
                            <?php 
                            if ($row['trail_date'] == '0000-00-00' || !$row['trail_date']) echo '';
                            else echo date('d-m-Y', strtotime($row['trail_date']));
                            ?>
                        </td>
                        <td><?php echo $row['measurement']; ?></td>
                        <td><?php echo $row['is_delivery']; ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } else { ?>
        <p align="center">No Future Bookings</p>
    <?php } ?>
</div>

<br>

<div align="center">
    <button onclick="printFuture()" class="btn btn-primary">Print Future Bookings</button>
</div>

<hr>

<!-- PAST BOOKINGS -->
<div id="pastBookings">
    <h3 align="center">Past Bookings <?php echo ($barcode ? ' - ' . $barcode : ''); ?></h3>
    <?php if ($num_past > 0) { ?>
        <div class="table-responsive">
            <table border="1">
                <tr>
                    <th>Sr. No</th>
                    <th>Bill No.</th>
                    <th>Item Codes</th>
                    <th>Customer Name</th>
                    <th>Contact No.</th>
                    <th>Pick Date</th>
                    <th>Delivery Date</th>
                    <th>Trail Date</th>
                    <th>Measurement</th>
                    <th>Is Delivery</th>
                </tr>
                <?php
                $i = 1;
                while ($row = mysqli_fetch_assoc($res_past)) {
                    $cust_id = $row['cust_id'];
                    $cust = mysqli_fetch_assoc(mysqli_query($con, "SELECT first_name, last_name, phone_number FROM phppos_people WHERE person_id='$cust_id'"));
                ?>
                    <tr align="center">
                        <td><?php echo $i++; ?></td>
                        <td><?php echo $row['new_bill_number'] ?: $row['bill_id']; ?></td>
                        <td style="text-align:left; white-space:pre-wrap;">
    <?php
    $items = explode(', ', $row['item_ids']);
    $chunks = array_chunk($items, 4);
    $formatted = [];
    foreach ($chunks as $chunk) {
        $formatted[] = implode(', ', $chunk);
    }
    echo implode("<br>", $formatted);
    ?>
</td>

                        <td><?php echo trim($cust['first_name'] . ' ' . $cust['last_name']); ?></td>
                        <td><?php echo $cust['phone_number']; ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['rentpickdate'])); ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['rentdeliverydate'])); ?></td>
                        <td>
                            <?php 
                            if ($row['trail_date'] == '0000-00-00' || !$row['trail_date']) echo '';
                            else echo date('d-m-Y', strtotime($row['trail_date']));
                            ?>
                        </td>
                        <td><?php echo $row['measurement']; ?></td>
                        <td><?php echo $row['is_delivery']; ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } else { ?>
        <p align="center">No Past Bookings</p>
    <?php } ?>
</div>

<br>
<div align="center">
    <button onclick="printPast()" class="btn btn-primary">Print Past Bookings</button>
</div>
<?php
CloseCon($con);
?>
