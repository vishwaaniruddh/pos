<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

$raw_bill_id = isset($_REQUEST['bill_id']) ? trim($_REQUEST['bill_id']) : (isset($_REQUEST['id']) ? trim($_REQUEST['id']) : '');
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$msg = isset($_GET['msg']) ? trim($_GET['msg']) : '';

// Lookup rent bill
$numeric_bill_id = 0;
$bill = null;

if (!empty($raw_bill_id)) {
    $stmt = $con->prepare("
        SELECT r.*, p.first_name, p.last_name, p.phone_number, p.address_1, p.city 
        FROM phppos_rent r 
        LEFT JOIN phppos_people p ON r.cust_id = p.person_id 
        WHERE r.bill_id = ? OR r.new_bill_number = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $raw_bill_id, $raw_bill_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $bill = $res->fetch_assoc();
        $numeric_bill_id = (int)$bill['bill_id'];
    }
}

// If no bill specified or not found, try to fetch the latest rent bill for convenience
if (!$bill) {
    $res_latest = mysqli_query($con, "SELECT bill_id FROM phppos_rent ORDER BY bill_id DESC LIMIT 1");
    if ($res_latest && $row_latest = mysqli_fetch_assoc($res_latest)) {
        $latest_id = $row_latest['bill_id'];
        $stmt = $con->prepare("
            SELECT r.*, p.first_name, p.last_name, p.phone_number, p.address_1, p.city 
            FROM phppos_rent r 
            LEFT JOIN phppos_people p ON r.cust_id = p.person_id 
            WHERE r.bill_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $latest_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $bill = $res->fetch_assoc();
            $numeric_bill_id = (int)$bill['bill_id'];
        }
    }
}

if (!$bill) {
    die("Error: No rent bill found to edit.");
}

// Extract bill details
$new_bill_number = $bill['new_bill_number'] ?? '';
$display_invoice = $new_bill_number ? $new_bill_number : "BILL-{$numeric_bill_id}";
$cust_id = $bill['cust_id'] ?? '';
$customer_name = trim(($bill['first_name'] ?? '') . ' ' . ($bill['last_name'] ?? ''));
if (empty($customer_name) && !empty($bill['cust_name'])) {
    $customer_name = $bill['cust_name'];
}
$cust_phone = $bill['phone_number'] ?? '';
$bill_date = $bill['bill_date'] ?? '';
$company_name = $bill['company_name'] ?? 'SAKAR TRADE LINK';
$bill_madeby = $bill['bill_madeby'] ?? '';

$pick = $bill['pick'] ?? 'Customer';
$delivery = $bill['delivery'] ?? 'Customer Return';
$pick_date = $bill['pick_date'] ?? '';
$delivery_date = $bill['delivery_date'] ?? '';

$is_pick_cust = ($pick === 'Customer');
$is_pick_com = ($pick === 'Company Delivery');

$cust_pick_val = ($is_pick_cust && !empty($pick_date) && $pick_date !== '0000-00-00') ? date('d/m/Y', strtotime($pick_date)) : '';
$comdel_date_val = ($is_pick_com && !empty($pick_date) && $pick_date !== '0000-00-00') ? date('d/m/Y', strtotime($pick_date)) : '';

$is_del_cust = ($delivery === 'Customer Return');
$is_del_com = ($delivery === 'Company Pickup');

$cust_del_val = ($is_del_cust && !empty($delivery_date) && $delivery_date !== '0000-00-00') ? date('d/m/Y', strtotime($delivery_date)) : '';
$compick_date_val = ($is_del_com && !empty($delivery_date) && $delivery_date !== '0000-00-00') ? date('d/m/Y', strtotime($delivery_date)) : '';

$person_Name = $bill['person_Name'] ?? '';
$person_contact = ($bill['person_contact'] && $bill['person_contact'] != '0') ? $bill['person_contact'] : '';
$throught = $bill['throught'] ?? '';
$throught_phone = ($bill['throught_phone'] && $bill['throught_phone'] != '0') ? $bill['throught_phone'] : '';
$through_area = '';

if (!empty($throught)) {
    $thr_sql = mysqli_query($con, "SELECT phone_number, city, address_1 FROM phppos_people WHERE person_id = '" . mysqli_real_escape_string($con, $throught) . "'");
    if ($thr_sql && $thr_row = mysqli_fetch_assoc($thr_sql)) {
        if (empty($throught_phone)) {
            $throught_phone = $thr_row['phone_number'];
        }
        $through_area = $thr_row['city'] ? $thr_row['city'] : $thr_row['address_1'];
    }
}

$bill_date_val = (!empty($bill_date) && $bill_date !== '0000-00-00') ? date('d/m/Y', strtotime($bill_date)) : '';
$comm_by = $bill['comm_by'] ?? 'Rs.';
$comm_amount = $bill['comm_amount'] ?? '0';

$pstatus = $bill['pstatus'] ?? 'Paid';
$delivery_status = $bill['delivery_status'] ?? 'Paid';
$bal_amount = $bill['bal_amount'] ?? '0';
$rent_amount = $bill['rent_amount'] ?? '0';
$amount = $bill['amount'] ?? '0';
$card_perc = $bill['card_perc'] ?? '0';
$card_amt = $bill['card_amt'] ?? '0';

$note = $bill['note'] ?? '';
$trail_date = $bill['trail_date'] ?? '';
$trail_date_val = (!empty($trail_date) && $trail_date !== '0000-00-00') ? date('d/m/Y', strtotime($trail_date)) : '';
$measurement = $bill['measurement'] ?? '';
$measurement_note = $bill['measurement_note'] ?? '';
$is_delivery = $bill['is_delivery'] ?? '';
$transaction_id = $bill['transaction_id'] ?? '';

// Check bank transaction
$bank_id = 0;
$bank_stmt = $con->prepare("SELECT bank_id FROM bank_transaction WHERE bill_id = ? ORDER BY trans_id DESC LIMIT 1");
$bank_stmt->bind_param("i", $numeric_bill_id);
$bank_stmt->execute();
$bank_res = $bank_stmt->get_result();
if ($bank_row = $bank_res->fetch_assoc()) {
    $bank_id = (int)$bank_row['bank_id'];
}

// Check rent_amount for payment mode and paid amount
$pay_by = $bill['payment_mode_name'] ?? '';
$rent_amt_stmt = $con->prepare("SELECT payment_by, amount FROM rent_amount WHERE bill_id = ? ORDER BY id DESC LIMIT 1");
$rent_amt_stmt->bind_param("i", $numeric_bill_id);
$rent_amt_stmt->execute();
$rent_amt_res = $rent_amt_stmt->get_result();
if ($rent_amt_row = $rent_amt_res->fetch_assoc()) {
    if (empty($pay_by)) {
        $pay_by = $rent_amt_row['payment_by'];
    }
    if (empty($bal_amount) || $bal_amount == '0') {
        $bal_amount = $rent_amt_row['amount'];
    }
}
if (empty($pay_by)) {
    $pay_by = ($company_name === 'Sri Shringarr') ? 'Bank' : 'SAK-Bank';
}

// Fetch line items
$items_stmt = $con->prepare("
    SELECT od.*, pi.category, pi.unit_price, pi.quantity as stock_qty, pi.item_number, pi.category_type 
    FROM order_detail od 
    LEFT JOIN phppos_items pi ON od.item_id = pi.name 
    WHERE od.bill_id = ? 
    ORDER BY od.id ASC
");
$items_stmt->bind_param("i", $numeric_bill_id);
$items_stmt->execute();
$items_res = $items_stmt->get_result();
$items = [];
$total_qty = 0;
$total_calculated_amt = 0;
$total_calculated_dep = 0;

while ($item = $items_res->fetch_assoc()) {
    $items[] = $item;
    $total_qty += (int)($item['qty'] ?? 1);
    $total_calculated_amt += (float)($item['total_amount'] ?? 0);
    $total_calculated_dep += (float)($item['deposit'] ?? 0);
}

$display_total = ($rent_amount > 0) ? $rent_amount : $total_calculated_amt;
$display_deposit = $total_calculated_dep;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Rent Bill - <?php echo htmlspecialchars($display_invoice); ?></title>
    <script type="text/javascript" src="datepick_js.js"></script>
    <link rel="stylesheet" type="text/css" href="date_css.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 10px 0;
        }
        .top-nav-bar {
            max-width: 1320px;
            margin: 0 auto 12px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 10px 18px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .top-nav-bar a {
            color: #0f172a;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #f1f5f9;
            transition: all 0.15s ease;
        }
        .top-nav-bar a:hover {
            background: #e2e8f0;
        }
        .bill-badge {
            background: #0f172a;
            color: #ffffff;
            padding: 5px 14px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .alert-box {
            max-width: 1320px;
            margin: 0 auto 12px auto;
            padding: 12px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        #submitLoaderOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.65);
            z-index: 9999999;
            backdrop-filter: blur(3px);
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        @keyframes posSubmitSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
    var isCtrl = false;
    document.onkeyup = function(e) {
        if (e.which == 17) isCtrl = false;
    };
    document.onkeydown = function(e) {
        if (e.which == 17) isCtrl = true;
        if (e.which == 66 && isCtrl == true) {
            document.getElementById("barcode").focus(); 
            return false;
        }
    };

    function loadXMLDoc() {
        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var s = xmlhttp.responseText;
                document.getElementById("tphone").value = s;
            }
        };
        var str = document.getElementById('name').value;
        xmlhttp.open("GET", "get_mobile.php?cid=" + encodeURIComponent(str), true);
        xmlhttp.send();
    }

    function findbook() {
        var str = document.getElementById('itmval').value;
        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                document.getElementById("bookdetail").innerHTML = xmlhttp.responseText;
            }
        };
        xmlhttp.open("GET", "get_booking.php?itmname=" + encodeURIComponent(str), true);
        xmlhttp.send();
    }

    var isFormSubmitting = false;

    function formSubmit() {
        if (isFormSubmitting) {
            console.warn("Bill update already in progress.");
            return false;
        }

        if (document.getElementById('cid').value == "-1" || document.getElementById('cid').value == "") {
            alert("Please Select Customer to continue.");
            document.getElementById('cid').focus();
            return false;
        } else if (document.getElementById('bill_date').value == "") {
            alert("Please Select Bill Date to continue.");
            document.getElementById('bill_date').focus();
            return false;
        } else {
            var form = document.getElementById("frm1");
            form.action = "update_editrent123.php";

            // Ensure date inputs are enabled before submitting so POST receives them
            document.getElementById('cust_pick').disabled = false;
            document.getElementById('compick_date').disabled = false;
            document.getElementById('cust_del').disabled = false;
            document.getElementById('comdel_date').disabled = false;

            isFormSubmitting = true;

            var btn = document.getElementById("btnRentSubmit");
            if (btn) {
                btn.disabled = true;
                btn.value = "Updating Bill, please wait...";
                btn.style.opacity = "0.6";
                btn.style.cursor = "not-allowed";
            }

            var overlay = document.getElementById("submitLoaderOverlay");
            if (overlay) {
                overlay.style.display = "flex";
            }

            setTimeout(function() {
                isFormSubmitting = false;
                if (btn) {
                    btn.disabled = false;
                    btn.value = "Update Rent Bill";
                    btn.style.opacity = "1";
                    btn.style.cursor = "pointer";
                }
                if (overlay) {
                    overlay.style.display = "none";
                }
            }, 30000);

            form.submit();
            return true;
        }
    }

    function getXMLHttp() {
        var xmlHttp;
        try {
            xmlHttp = new XMLHttpRequest();
        } catch(e) {
            try {
                xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
            } catch(e) {
                try {
                    xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
                } catch(e) {
                    alert("Your browser does not support AJAX!");
                    return false;
                }
            }
        }
        return xmlHttp;
    }

    function MakeRequest() {
        var arr = document.getElementsByClassName('design');
        var barc = document.getElementsByClassName('barc');
        var qty = document.getElementsByClassName('qty');
        var total_qty = document.getElementsByClassName('total_qty');
        document.getElementById('itmval').value = document.getElementById('barcode').value;
        var bar = document.getElementById('barcode').value.toUpperCase();
        var bar2 = document.getElementById('barcode2').value.toUpperCase();
        var flag = 0;

        for (var i = 0; i < arr.length; i++) {
            if (arr[i].value.toUpperCase() == bar || (barc[i] && barc[i].value.toUpperCase() == bar2)) {
                if (parseInt(qty[i].value) < parseInt(total_qty[i].value)) {
                    qty[i].value = parseInt(qty[i].value) + 1;
                    checkTotalqty(); checkTotal1(); checkTotal(); checkTotalDeposit();
                    document.getElementById('barcode').value = '';
                    document.getElementById('barcode2').value = '';
                    flag = 1;
                    break;
                } else {
                    alert('Insufficient Item Quantity');
                    flag = 1;
                    break;
                }
            }
        }

        if (flag == 0) {
            var xmlHttp = getXMLHttp();
            xmlHttp.onreadystatechange = function() {
                if (xmlHttp.readyState == 4) {
                    HandleResponse(xmlHttp.responseText);
                }
            };
            var pdt = "";
            var ddt = "";

            if (frm1.pick[0].checked == true) {
                pdt = document.getElementById('cust_pick').value;
            }
            if (frm1.del[1].checked == true) {
                pdt = document.getElementById('compick_date').value;
            }
            if (frm1.del[0].checked == true) {
                ddt = document.getElementById('cust_del').value;
            }
            if (frm1.pick[1].checked == true) {
                ddt = document.getElementById('comdel_date').value;
            }

            if ((frm1.pick[0].checked == false && frm1.pick[1].checked == false) && (pdt == "" && ddt == "")) {
                alert("Please Select Pick Up And Delivery Type And Date.");
            } else {
                var str = escape(document.getElementById('barcode').value);
                var str1 = document.getElementById('barcode2').value;
                var arr = document.getElementsByClassName('amt');
                xmlHttp.open("GET", "getbarcode.php?barcode=" + str + "&barcode2=" + str1 + "&cnt=" + arr.length + "&pdt=" + pdt + "&ddt=" + ddt, false);
                xmlHttp.send(null);
            }
        }
    }

    function HandleResponse(response) {
        if (response == 0) {
            alert('Insufficient Item Quantity or Item Not Found');
        } else {
            var ni = document.getElementById('detail');
            var numi = document.getElementById('theValue');
            var num = parseInt(numi.value) + 1;
            numi.value = num;

            var newdiv = document.createElement('div');
            var divIdName = 'dyn_item_' + num;
            newdiv.setAttribute('id', divIdName);
            newdiv.style.display = 'flex';
            newdiv.style.alignItems = 'stretch';
            newdiv.style.marginBottom = '2px';

            // Add hidden order_detail_id set to 0 for newly added items
            var modifiedResponse = response.replace('<input name="barc[]"', '<input name="order_detail_id[]" type="hidden" value="0"/><input name="barc[]"');

            newdiv.innerHTML = modifiedResponse + "<table border='0' height='200' valign='middle' style='margin-left:4px;'><tr><td valign='middle'><a href=\"javascript:;\" onclick=\"removeElement(\'" + divIdName + "\')\" style='color:red; font-weight:bold;'>Delete</a></td></tr></table>";

            ni.appendChild(newdiv);

            checkTotalqty(); checkTotal1(); checkTotal(); checkTotalDeposit();

            document.getElementById('barcode').value = '';
            document.getElementById('barcode2').value = '';
        }
    }

    function removeElement(divNum) {
        var d = document.getElementById('detail');
        var olddiv = document.getElementById(divNum);
        if (olddiv) {
            d.removeChild(olddiv);
            checkTotalqty(); checkTotal(); checkTotalDeposit();
        }
    }

    function deleteExistingItem(divId, orderDetailId) {
        if (confirm("Are you sure you want to remove this item from the rent bill?")) {
            if (orderDetailId) {
                var input = document.createElement("input");
                input.type = "hidden";
                input.name = "deleted_detail_id[]";
                input.value = orderDetailId;
                document.getElementById("frm1").appendChild(input);
            }
            var div = document.getElementById(divId);
            if (div) {
                div.parentNode.removeChild(div);
            }
            checkTotalqty(); checkTotal(); checkTotalDeposit();
        }
    }

    function ch() {
        if (frm1.pick[0].checked == true) {
            document.getElementById('cust_pick').disabled = false;
            document.getElementById('comdel_date').disabled = true;
        }
        if (frm1.pick[1].checked == true) {
            document.getElementById('comdel_date').disabled = false;
            document.getElementById('cust_pick').disabled = true;
        }
    }

    function ch1() {
        if (frm1.del[0].checked == true) {
            document.getElementById('cust_del').disabled = false;
            document.getElementById('compick_date').disabled = true;
        }
        if (frm1.del[1].checked == true) {
            document.getElementById('compick_date').disabled = false;
            document.getElementById('cust_del').disabled = true;
        }
    }

    function checkTotal() {
        document.listForm.total.value = '';
        var sum = 0;
        var arr = document.getElementsByClassName('amt');

        for (var i = 0; i < arr.length; i++) {
            sum = sum + Number(arr[i].value);
        }

        var radioValue = $("input[name='pay_By']:checked").val();
        var cprtmt = 0;
        if (radioValue == "Card" || radioValue == "SAK-Card") {
            var cprt = Number(2) / Number(100);
            cprtmt = Number(sum) * Number(cprt);
            sum = Number(sum) + Number(cprtmt);
        }

        document.getElementById("cardpercamt").value = cprtmt;
        document.listForm.total.value = sum;
        checkTotalDeposit();
    }

    function checkTotalDeposit() {
        document.listForm.totalDeposit.value = '';
        var sum = 0;
        var arr = document.getElementsByClassName('dep');

        for (var i = 0; i < arr.length; i++) {
            sum = sum + Number(arr[i].value);
        }
        document.listForm.totalDeposit.value = sum;
    }

    function checkTotal1() {
        var arr = document.getElementsByClassName('prz');
        var qty = document.getElementsByClassName('qty');
        var discount = document.getElementsByClassName('discount');
        var amt = document.getElementsByClassName('amt');
        var og_dep = document.getElementsByClassName('txt');
        var dep = document.getElementsByClassName('dep');

        for (var i = 0; i < arr.length; i++) {
            amt[i].value = (Number(arr[i].value) * Number(qty[i].value)) - Number(discount[i].value);
            dep[i].value = Number(og_dep[i].value) * Number(qty[i].value);
        }
    }

    function checkTotalqty() {
        document.listForm.qty11.value = '';
        var sum = 0;
        var arr = document.getElementsByClassName('qty');

        for (var i = 0; i < arr.length; i++) {
            sum = sum + Number(arr[i].value);
        }
        document.listForm.qty11.value = sum;
    }

    function showbills() {}

    function loadPhoneNo() {
        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var s = xmlhttp.responseText;
                var s1 = s.split('&&');
                if (s1[0] == "0") {
                    alert("No such Phone Number"); 
                } else {
                    document.getElementById("cid").value = s1[1];
                    $.ajax({
                        type: "POST",
                        url: 'get_people_data_by_phone.php',
                        data: { userid: s1[1] },
                        success: function (msg) {
                            if (msg != 0) {
                                var obj = JSON.parse(msg);
                                $('#cid').val(obj.person_id);
                                $('#people').val(obj.personName);
                            } else {
                                alert('No Info With This Name');
                                $('#people').val('');
                            }
                        },
                        error: function () {
                            alert('An error occurred while fetching data.');
                            $('#people').val('');
                        }
                    });
                    showbills();
                }
            }
        };
        var str = document.getElementById('phoneNo').value;
        xmlhttp.open("GET", "getbyphone.php?cid=" + encodeURIComponent(str), true);
        xmlhttp.send();
    }

    function loadPhoneNo2() {
        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var s = xmlhttp.responseText;
                var s1 = s.split('&&');
                if (s1[0] == "0") {
                    alert("No such Phone Number"); 
                } else {
                    document.getElementById("name").value = s1[1];
                    showbills();
                }
            }
        };
        var str = document.getElementById('tphone').value;
        xmlhttp.open("GET", "getbyphone2.php?cid=" + encodeURIComponent(str), true);
        xmlhttp.send();
    }
    </script>
</head>
<body onLoad="">

<!-- Top Navigation & Jump Bar -->
<div class="top-nav-bar">
    <div style="display: flex; gap: 8px; align-items: center;">
        <a href="editRentBill.php"><i class="fas fa-arrow-left"></i> &larr; Back to Rent Bills</a>
        <a href="/pos/home_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="rent_report_detail.php?id=<?php echo $numeric_bill_id; ?>" target="_blank"><i class="fas fa-print"></i> View / Print Bill</a>
    </div>

    <div>
        <span class="bill-badge">Editing Rent Bill: <?php echo htmlspecialchars($display_invoice); ?> (ID: <?php echo $numeric_bill_id; ?>)</span>
    </div>

    <div>
        <form method="GET" action="editrent123.php" style="display: flex; gap: 6px; align-items: center; margin: 0;">
            <input type="text" name="bill_id" placeholder="Bill ID or Invoice #" value="<?php echo htmlspecialchars($raw_bill_id); ?>" style="padding: 5px 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px;" />
            <button type="submit" style="padding: 5px 12px; background: #0f172a; color: #ffffff; border: 1px solid #0f172a; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer;">Load</button>
        </form>
    </div>
</div>

<?php if ($status === 'success'): ?>
    <div class="alert-box alert-success">
        ✓ <?php echo !empty($msg) ? htmlspecialchars($msg) : "Rent bill details updated successfully!"; ?>
    </div>
<?php elseif ($status === 'error'): ?>
    <div class="alert-box alert-error">
        ⚠ <?php echo !empty($msg) ? htmlspecialchars($msg) : "There was an error updating the rent bill."; ?>
    </div>
<?php endif; ?>

<div style="text-align: center;">
<table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" align="center">
<tr>
<td align="center"> 

<img src="bill.PNG" width="408" height="165"/><br/><br/>
<b>CONFIRMATION MEMO (EDIT BILL)</b>
<div style="font-size: 13px; color: #64748b; margin-top: 4px;">
    Invoice Number: <strong><?php echo htmlspecialchars($display_invoice); ?></strong> | Bill ID: <strong><?php echo $numeric_bill_id; ?></strong>
</div>
</td></tr>

<tr>
<td width="1308" valign="top"><center>
      
      <form name="listForm" method="POST" id="frm1" action="update_editrent123.php">
       <input type="hidden" name="bill_id" value="<?php echo $numeric_bill_id; ?>" />
       <input type="hidden" name="new_bill_number" value="<?php echo htmlspecialchars($new_bill_number); ?>" />
       <br/>
       
       <table style="width:100%;">
       <tr>
        <td height="36"><strong>Customer Pick-Up :</strong></td>
        <td><input name="pick" id="pick" type="radio" value="Customer" <?php if ($is_pick_cust) echo 'checked'; ?> onclick="ch();"></td>
        <td><strong>Customer Pick-Up Date :</strong><input type="text" name="cust_pick" id="cust_pick" value="<?php echo $cust_pick_val; ?>" onClick="displayDatePicker('cust_pick');" autocomplete="nope"/></td>  
        <td><strong>Company Delivery :</strong></td>
        <td><input name="pick" id="del2" type="radio" value="Company Delivery" <?php if ($is_pick_com) echo 'checked'; ?> onclick="ch();"></td>
        <td><strong>Company Delivery Date: </strong><input type="text" name="comdel_date" id="comdel_date" value="<?php echo $comdel_date_val; ?>" onClick="displayDatePicker('comdel_date');" autocomplete="nope"/></td>
       </tr>
      
       <tr>
        <td height="39"><strong>Customer Return :</strong></td>
        <td><input name="del" id="del" type="radio" value="Customer Return" <?php if ($is_del_cust) echo 'checked'; ?> onclick="ch1();"></td>
        <td><strong>Customer Return Date :</strong><input type="text" name="cust_del" id="cust_del" value="<?php echo $cust_del_val; ?>" onClick="displayDatePicker('cust_del');" autocomplete="nope"/></td>
        <td><strong>Company Pick-Up :</strong></td>
        <td><input name="del" id="pick2" type="radio" value="Company Pickup" <?php if ($is_del_com) echo 'checked'; ?> onclick="ch1();"></td>
        <td><strong>Company Pick-Up Date :</strong><input type="text" name="compick_date" id="compick_date" value="<?php echo $compick_date_val; ?>" onClick="displayDatePicker('compick_date');" autocomplete="nope"/></td>
       </tr>
      </table>
      
      <center>
      <hr>
      <table width="100%" height="110">
      <tr><td><a href="people.php?mode=rent" target="_new">New Customer</a></td></tr>
      <tr><td width="103">Phone Number:</td> <td colspan="2"> <input type="text" name="phoneNo" id="phoneNo" value="<?php echo htmlspecialchars($cust_phone); ?>" /> <a href="#" onClick="loadPhoneNo();">Find</a></td></tr>
      
      <tr>
          <td>Bill Made By</td>
          <td colspan="2"> 
          <select name="bill_by" required>
              <option value="">Select</option>
              <option value="Rajni Podar" <?php if($bill_madeby=='Rajni Podar') echo 'selected'; ?>>Rajni Podar</option>
              <option value="Akruti Manjrekar" <?php if($bill_madeby=='Akruti Manjrekar') echo 'selected'; ?>>Akruti Manjrekar</option>
              <option value="Nipa Agrawal" <?php if($bill_madeby=='Nipa Agrawal') echo 'selected'; ?>>Nipa Agrawal</option>
              <?php if (!empty($bill_madeby) && !in_array($bill_madeby, ['Rajni Podar', 'Akruti Manjrekar', 'Nipa Agrawal'])): ?>
                  <option value="<?php echo htmlspecialchars($bill_madeby); ?>" selected><?php echo htmlspecialchars($bill_madeby); ?></option>
              <?php endif; ?>
          </select>
          </td>
          <td>Company Name</td>
          <td colspan="2"> 
          <select name="company_name" required>
              <option value="">Select</option>
              <option value="Sri Shringarr" <?php if($company_name=='Sri Shringarr') echo 'selected'; ?>>Sri Shringarr</option>
              <option value="SAKAR TRADE LINK" <?php if($company_name=='SAKAR TRADE LINK') echo 'selected'; ?>>SAKAR TRADE LINK</option>
          </select>
          </td>
      </tr>
      <tr>
      <td height="34"><strong>Customer Name:&nbsp;</strong></td>
      <td>
        <input type="hidden" name="cid" id="cid" value="<?php echo htmlspecialchars($cust_id); ?>" />
        <input type="text" id="people" class="form-control" list="peopleOptions" name="person" value="<?php echo htmlspecialchars($customer_name); ?>" placeholder="Enter people ..." />
        <datalist id="peopleOptions"></datalist>
     </td>
	 
	 <td><strong>2nd Person's Name:</strong></td><td><input type="text" name="pname" id="pname" value="<?php echo htmlspecialchars($person_Name); ?>" /> <input type="hidden" name="myvar" value="<?php echo count($items); ?>" id="theValue" /></td>
     <td><strong>Through Name:</strong></td>
     <td>
      <select name="name" id="name" onChange="loadXMLDoc();">
         <option value="">select</option>
     <?php
	  $bresult = mysqli_query($con,"SELECT * FROM phppos_people WHERE first_name LIKE 'B %' order by first_name");
	  while($brow = mysqli_fetch_row($bresult)){ 
          $sel = ($brow[11] == $throught || ($brow[0] . "  " . $brow[1]) == $throught) ? "selected" : "";
	  ?>
         <option value="<?php echo $brow[11]; ?>" <?php echo $sel; ?>><?php echo $brow[0] ."  ". $brow[1]; ?></option>
     <?php } ?>
     </select>
      </td>
    </tr>
    
    <tr>
    <td height="35"><strong>Bill Date :</strong></td><td><input type="text" name="bill_date" id="bill_date" value="<?php echo $bill_date_val; ?>" onClick="displayDatePicker('bill_date');"/></td>
    <td><strong>2nd Contact No. :</strong> </td><td> <input type="text" name="pcontact" id="pcontact" value="<?php echo htmlspecialchars($person_contact); ?>" /></td>
    <td><strong>Through Phone No:</strong> </td><td> <input type="text" name="tphone" id="tphone" value="<?php echo htmlspecialchars($throught_phone); ?>" />  <a href="#" onClick="loadPhoneNo2();">Find</a></td>
    </tr>
    
    <tr>
    <td height="31">&nbsp;</td>
    <td>&nbsp;</td>
    <td><strong>Through Area:</strong></td><td><input type="text" name="area" id="area" value="<?php echo htmlspecialchars($through_area); ?>" /></td>
    </tr>
    </table>
    
    </center><hr>
    
      <table width="100%">
      <tr><td colspan="3"><div id="bookdetail"></div></td></tr>
      <tr>
      <td width="96"> <strong> Beautician Discount : </strong></td>
      <td width="280">
        <input type="radio" name="commis" id="commis1" value="Rs." <?php if ($comm_by !== '%') echo 'checked="checked"'; ?>/><label for="commis1">Rs.</label>
        <input type="radio" name="commis" id="commis2" value="%" <?php if ($comm_by === '%') echo 'checked="checked"'; ?>/><label for="commis2">%</label><br/>
        <input type="text" name="comm" id="comm" value="<?php echo htmlspecialchars($comm_amount); ?>"/>
      </td>
      <td width="493"><strong>Item code: </strong>
      <input type="text" name="barcode" onFocus="this.value=''" onClick="this.value=''" id="barcode" onChange="MakeRequest();findbook();"/> 
	  <input type="hidden" name="itmval" id="itmval"/>
       &nbsp;&nbsp;&nbsp;&nbsp; Barcode : 
       <input type="text" name="barcode2" onFocus="this.value=''" onClick="this.value=''" id="barcode2" onChange="MakeRequest();"/>
       <a href="javascript:void(0);" id="viewPrerentedPrice">View Prerented Price</a>
      </td>
      </tr>
      </table>
    <hr>
     
       <table width="1301" border="1" cellpadding="4" cellspacing="0">
         <tr>
            <td width='62'><U><strong>Sr.No.</strong></U></td>  
            <td width='59'><U><strong>Item Code</strong></U></td>
            <td width='135'><U><strong>Category</strong></U></td>
            <td width='120'><U><strong>Price</strong></U></td>
            <td width='104'><U><strong>Qty</strong></U></td>
            <td width='136'><U><strong>Rent</strong></U></td>
            <td width='137'><U><strong>Discount</strong></U></td>
            <td width='107'><U><strong>Amount</strong></U></td>
            <td width='139'><U><strong>Deposit</strong></U></td>
            <td width='120'><U><strong>Booking Status</strong></U></td>
            <td width='70'><U><strong>Delete</strong></U></td>
        </tr>
  
        <tr>
        <td colspan="11">
            <div id="detail">
                <?php
                $item_counter = 0;
                foreach ($items as $idx => $it):
                    $item_counter++;
                    $divId = "item_row_" . $item_counter;
                    $detail_id = $it['id'];
                    $item_name = $it['item_id'];
                    $item_number = $it['item_number'] ?? '';
                    $category = $it['category'] ?? '';
                    $item_detail = $it['item_detail'] ?? '';
                    $unit_price = $it['unit_price'] ?? '0';
                    $qty = $it['qty'] ?? 1;
                    $rent = $it['rent'] ?? 0;
                    $discount = $it['discount'] ?? 0;
                    $total_amount = $it['total_amount'] ?? ($rent * $qty - $discount);
                    $deposit = $it['deposit'] ?? 0;
                    $stock_qty = $it['stock_qty'] ?? 100;
                ?>
                <div id="<?php echo $divId; ?>" style="display: flex; align-items: stretch; margin-bottom: 2px;">
                    <table border="1" width="1223" align="left">
                        <tr>
                            <td width="55"><?php echo $item_counter; ?></td>
                            <td width="77"><?php echo htmlspecialchars($item_name); ?>
                                <input name="design[]" type="hidden" value="<?php echo htmlspecialchars($item_name); ?>" class="design"/>
                                <input name="barc[]" type="hidden" value="<?php echo htmlspecialchars($item_number); ?>" class="barc"/>
                                <input name="order_detail_id[]" type="hidden" value="<?php echo $detail_id; ?>" />
                            </td>
                            <td width="137"><?php echo htmlspecialchars($category); ?><br><br>
                                <textarea name="items[]" rows="10" cols="15"><?php echo htmlspecialchars($item_detail); ?></textarea>
                            </td>
                            <td width="38" align="left"><?php echo htmlspecialchars($unit_price); ?></td>

                            <td width="43">
                                <table border="0">
                                <?php 
                                $sql_hist = mysqli_query($con, "SELECT qty FROM `order_detail` WHERE `item_id`='" . mysqli_real_escape_string($con, $item_name) . "' AND bill_id IN (SELECT bill_id FROM phppos_rent WHERE `booking_status`='Picked' OR booking_status='' OR booking_status='Returned') LIMIT 5");
                                if ($sql_hist) {
                                    while ($hist_row = mysqli_fetch_row($sql_hist)) {
                                        echo "<tr><td>(" . htmlspecialchars($hist_row[0]) . ")</td></tr>";
                                    }
                                }
                                ?>
                                </table>
                            </td>

                            <td align="left" width="119">
                                <input name="qty[]" type="text" value="<?php echo htmlspecialchars($qty); ?>" class="qty" style="width:100px;" onKeyUp="checkTotalqty();checkTotal1();checkTotal();checkTotalDeposit();"/>
                            </td>

                            <td align="left" width="137">
                                <input name="prz[]" class="prz" type="text" style="width:100px;" value="<?php echo htmlspecialchars($rent); ?>" onKeyUp="checkTotalqty();checkTotal1();checkTotal();checkTotalDeposit();"/>
                            </td>
                            <td width="150">
                                <input type="text" name="discount[]" class="discount" style="width:100px;" value="<?php echo htmlspecialchars($discount); ?>" onKeyUp="checkTotalqty();checkTotal1();checkTotal();checkTotalDeposit();" />
                            </td>
                            <td width="130">
                                <input type="text" class="amt" name="amt[]" style="width:100px;" value="<?php echo htmlspecialchars($total_amount); ?>" onKeyUp="checkTotalqty();checkTotal1();checkTotal();checkTotalDeposit();"/>
                            </td>
                            <input name="og_dep[]" type="hidden" style="width:100px;" class="txt" value="<?php echo htmlspecialchars($deposit); ?>" onKeyUp="checkTotalDeposit();" />
                            <td width="125">
                                <input name="dep[]" type="text" style="width:100px;" class="dep" value="<?php echo htmlspecialchars($deposit); ?>" onKeyUp="checkTotalDeposit();" />
                            </td> 

                            <td width="142">
                                <?php 
                                $sql_bk = mysqli_query($con, "SELECT r.pick_date, r.delivery_date FROM `order_detail` od JOIN `phppos_rent` r ON od.bill_id = r.bill_id WHERE od.item_id='" . mysqli_real_escape_string($con, $item_name) . "' AND r.bill_id != '{$numeric_bill_id}' AND r.booking_status = 'Booked' GROUP BY r.bill_id ORDER BY r.pick_date ASC LIMIT 5");
                                if ($sql_bk && mysqli_num_rows($sql_bk) > 0) {
                                    while ($bk_row = mysqli_fetch_assoc($sql_bk)) {
                                        if (!empty($bk_row['pick_date']) && $bk_row['pick_date'] != '0000-00-00') {
                                            echo date('d/m/Y', strtotime($bk_row['pick_date'])) . " - " . date('d/m/Y', strtotime($bk_row['delivery_date'])) . "<br/>";
                                        }
                                    }
                                } else {
                                    echo '<span style="color:green; font-size:11px;">Available</span>';
                                }
                                ?>
                                <input type="hidden" name="total_qty[]" class="total_qty" value="<?php echo htmlspecialchars($stock_qty); ?>">
                            </td>
                        </tr>
                    </table>
                    <table border='0' height='200' valign='middle' style="margin-left: 4px;">
                        <tr>
                            <td valign='middle'>
                                <a href="javascript:;" onclick="deleteExistingItem('<?php echo $divId; ?>', '<?php echo $detail_id; ?>')" style="color:red; font-weight:bold;">Delete</a>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php endforeach; ?>
            </div>
        </td>
        </tr>
      </table><br/>
  
      <table width="1240" height="29" border="1" cellspacing="0">
      <tr>
      <td width="44"><strong>Rent:-</strong></td>
      <td width="173">
        <input type="radio" name="rentpaid" id="rentpaid1" value="Paid" <?php if($pstatus=='Paid') echo 'checked'; ?>>
        <strong>Paid Amount</strong><br/>
        <input type="radio" name="rentpaid" id="rentpaid2" value="Unpaid" <?php if($pstatus=='Unpaid') echo 'checked'; ?>>
        <strong>Unpaid Amount</strong><br/>
        <input type="radio" name="rentpaid" id="rentpaid3" value="Balance" <?php if($pstatus=='Balance') echo 'checked'; ?>>
        <strong>Balance Amount</strong>
      </td>
      <td width="90"><strong>Deposit :-</strong></td>
      <td width="171"><strong>
        <input type="radio" name="paid" id="paid1" value="Paid" <?php if($delivery_status=='Paid') echo 'checked'; ?>>
        Paid <br/>
        <input type="radio" name="paid" id="paid2" value="Unpaid" <?php if($delivery_status=='Unpaid') echo 'checked'; ?>>
        Unpaid </strong>
      </td>
      <td width='186'><strong>Total Qty :</strong> <input type="text" name="qty11" id="qty11" value="<?php echo $total_qty; ?>" style="width:100px;" readonly/></td>
      
      <td width="192"><strong>Amount Paid : </strong>&nbsp;<input type="text" name="pamount" id="pamount" value="<?php echo htmlspecialchars($bal_amount); ?>" size="11"></td>
      
      <td width="206"><strong>Total Amount : </strong><input type="text" size="10" name="total" id="total" value="<?php echo htmlspecialchars($display_total); ?>" /></td>
      <td width="206"><strong>Total Deposit : </strong><input type="text" size="10" name="totalDeposit" id="totalDeposit" value="<?php echo htmlspecialchars($display_deposit); ?>" />
        <input type="hidden" name="cardpercamt" id="cardpercamt" value="<?php echo htmlspecialchars($card_amt); ?>" style="width:100px;" readonly/>
      </td>
      </tr>
        
      <tr>
        <td><strong>In Account :</strong></td>
        <td>
            <select id="acc" name='acc'>
                <option value="0">Select </option>
                <?php 
                $qryitem = mysqli_query($con, "select bank_name, bank_id from banks ORDER BY bank_name ASC");
                while ($row = mysqli_fetch_row($qryitem)) {
                    $sel = ($row[1] == $bank_id) ? "selected" : "";
                    echo "<option value='".$row[1]."' $sel>".$row[0]."</option>";
                }
                ?>
            </select>
        </td>
			
        <td width="90"><strong>Payment By :</strong> </td>
        <td width="173">
            <p style="white-space: nowrap;padding: 0;margin: 0;"><input name="pay_By" type="radio" value="Bank" <?php if($pay_by=='Bank' || $pay_by=='SS-Bank') echo 'checked'; ?> onchange="checkTotal()">
            <strong>SS-Bank</strong><br></p>
            
            <p style="white-space: nowrap;padding: 0;margin: 0;"><input name="pay_By" type="radio" value="Cash" <?php if($pay_by=='Cash' || $pay_by=='SS-Cash') echo 'checked'; ?> onchange="checkTotal()">
            <strong>SS-Cash</strong></p>
            
            <p style="white-space: nowrap;padding: 0;margin: 0;"><input name="pay_By" type="radio" value="Card" <?php if($pay_by=='Card' || $pay_by=='SS-Card') echo 'checked'; ?> onchange="checkTotal()">
            <strong>SS-Card</strong></p>
            
            <p style="white-space: nowrap;padding: 0;margin: 0;"><input name="pay_By" type="radio" value="SAK-Bank" <?php if($pay_by=='SAK-Bank') echo 'checked'; ?> onchange="checkTotal()">
            <strong>SAK-Bank</strong><br></p>
            
            <p style="white-space: nowrap;padding: 0;margin: 0;"><input name="pay_By" type="radio" value="SAK-Cash" <?php if($pay_by=='SAK-Cash') echo 'checked'; ?> onchange="checkTotal()">
            <strong>SAK-Cash</strong></p>
            
            <p style="white-space: nowrap;padding: 0;margin: 0;"><input name="pay_By" type="radio" value="SAK-Card" <?php if($pay_by=='SAK-Card') echo 'checked'; ?> onchange="checkTotal()">
            <strong>SAK-Card</strong></p>
        </td>

        <td><strong>Note:</strong></td>
        <td><textarea name="note" id="note" rows="3" cols="55"><?php echo htmlspecialchars($note); ?></textarea></td>
      </tr>

      <tr>
        <td><strong>Trail Date :</strong></td>
        <td><input type="text" name="trail_date" id="trail_date" value="<?php echo $trail_date_val; ?>" onClick="displayDatePicker('trail_date');" autocomplete="nope"/></td> 
        <td width="90"><strong>Measurement :-</strong></td>
        <td width="171">
            <select id="measurement" name='measurement'>
                <option value="">Select </option>
                <option value="yes" <?php if(strtolower($measurement)=='yes') echo 'selected'; ?>>yes</option>
                <option value="no" <?php if(strtolower($measurement)=='no') echo 'selected'; ?>>no</option>
            </select>
        </td>
        <td><strong>Measurement Note:</strong></td>
        <td><textarea name="measurement_note" id="measurement_note" rows="3" cols="55"><?php echo htmlspecialchars($measurement_note); ?></textarea></td>
		
        <td width="90"><strong>Delivery :-</strong></td>
        <td width="171">
            <select id="delivery" name='delivery'>
                <option value="">Select </option>
                <option value="yes" <?php if(strtolower($is_delivery)=='yes' || strtolower($delivery)=='yes') echo 'selected'; ?>>yes</option>
                <option value="no" <?php if(strtolower($is_delivery)=='no' || strtolower($delivery)=='no') echo 'selected'; ?>>no</option>
            </select>
        </td>
      </tr>

      <tr>
        <td style="white-space: nowrap;"><strong>Transaction ID :</strong></td>
        <td><input type="text" name="transaction_id" value="<?php echo htmlspecialchars($transaction_id); ?>" /></td>
      </tr>
      </table><br/>
        
      <div style="display: flex; gap: 12px; justify-content: center; align-items: center; margin-top: 10px;">
        <input type="button" id="btnRentSubmit" onClick="formSubmit()" value="Update Rent Bill" style="padding: 12px 36px; font-weight: 700; font-size: 15px; cursor: pointer; background: #0f172a; color: #ffffff; border: 1px solid #0f172a; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.2s;" /> 
        <a href="editRentBill.php" style="padding: 11px 24px; font-size: 14px; font-weight: 600; color: #475569; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none;">Cancel</a>
      </div>

</form>
</center>
</td>
</tr>
</table>
</div>

<script>
document.getElementById('viewPrerentedPrice').addEventListener('click', function() {
    var skus = [];
    document.querySelectorAll('.design').forEach(function(input) {
        skus.push(input.value);
    });
    var skuQuery = skus.map(sku => 'sku[]=' + encodeURIComponent(sku)).join('&');
    var url = './viewprerentedprice.php?' + skuQuery;
    window.open(url, '_blank');
});

$(document).ready(function() {
    $("#people").on('input', function () {
        var input = $(this).val();
        $.ajax({
            type: "POST",
            url: './get_suggestions_people.php',
            data: { input: input },
            success: function (response) {
                var datalist = $("#peopleOptions");
                datalist.empty();
                var suggestions = JSON.parse(response);
                suggestions.forEach(function (suggestion) {
                    datalist.append($("<option>").attr('value', suggestion));
                });
            }
        });
    });

    function populatePeopleData(people) {
        $.ajax({
            type: "POST",
            url: 'get_people_data.php',
            data: { people: people },
            success: function (msg) {
                if (msg != 0) {
                    var obj = JSON.parse(msg);
                    $('#cid').val(obj.person_id);
                } else {
                    alert('No Info With This Name');
                    $('#people').val('');
                }
            },
            error: function () {
                alert('An error occurred while fetching data.');
                $('#people').val('');
            }
        });
    }

    $("#people").on('change', function () {
        var people = $(this).val();
        populatePeopleData(people);
    });

    $("#frm1").on('submit', function (e) {
        if (isFormSubmitting) {
            e.preventDefault();
            return false;
        }
        return formSubmit();
    });

    // Auto-verify totals on page load
    checkTotalqty();
    checkTotal();
    checkTotalDeposit();
});
</script>

<div align="center" style="margin-top: 15px; font-size: 12px; color: #94a3b8;">Point Of Sale - Edit Rent Bill v1.0</div>

<!-- Multi-Click / Multi-Submit Prevention Loading Overlay -->
<div id="submitLoaderOverlay">
    <div style="background:#ffffff; color:#0f172a; padding:28px 36px; border-radius:12px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.25), 0 8px 10px -6px rgba(0,0,0,0.25); text-align:center; max-width:440px; width:90%; border:1px solid #e2e8f0;">
        <div style="width:44px; height:44px; border:4px solid #e2e8f0; border-top:4px solid #0f172a; border-radius:50%; margin:0 auto 16px auto; animation:posSubmitSpin 0.9s linear infinite;"></div>
        <div style="font-size:17px; font-weight:600; color:#0f172a; margin-bottom:6px;">Updating Rent Bill...</div>
        <div style="font-size:13px; color:#64748b; line-height:1.5;">Please wait while the invoice is being updated.<br/><strong>Do not refresh, go back, or click again.</strong></div>
    </div>
</div>

<?php CloseCon($con); ?>
</body>
</html>
