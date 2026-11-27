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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: editRentBill.php");
    exit;
}

$bill_id = isset($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
if ($bill_id <= 0) {
    die("Error: Invalid Bill ID.");
}

// Convert dates from DD/MM/YYYY or YYYY-MM-DD to YYYY-MM-DD
function parse_dmy($date_str) {
    $date_str = trim($date_str ?? '');
    if (empty($date_str) || $date_str === '0000-00-00' || $date_str === '00/00/0000') {
        return '0000-00-00';
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_str, $m)) {
        return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
        return $date_str;
    }
    $ts = strtotime($date_str);
    return $ts ? date('Y-m-d', $ts) : '0000-00-00';
}

$bill_date = parse_dmy($_POST['bill_date'] ?? '');
$pick = $_POST['pick'] ?? '';
$del = $_POST['del'] ?? '';

if ($pick === "Customer") {
    $pick_date = parse_dmy($_POST['cust_pick'] ?? '');
} else {
    $pick_date = parse_dmy($_POST['comdel_date'] ?? '');
}

if ($del === "Customer Return") {
    $del_date = parse_dmy($_POST['cust_del'] ?? '');
} else {
    $del_date = parse_dmy($_POST['compick_date'] ?? '');
}

$trail_date = parse_dmy($_POST['trail_date'] ?? '');

$cid = (int)($_POST['cid'] ?? 0);
$company_name = trim($_POST['company_name'] ?? '');
$bill_madeby = trim($_POST['bill_by'] ?? '');
$pname = trim($_POST['pname'] ?? '');
$pcontact = trim($_POST['pcontact'] ?? '');
$throught = trim($_POST['name'] ?? '');
$tphone = trim($_POST['tphone'] ?? '');
$commis = trim($_POST['commis'] ?? 'Rs.');
$comm = (float)($_POST['comm'] ?? 0);

$rentpaid = trim($_POST['rentpaid'] ?? '');
$paid = trim($_POST['paid'] ?? '');
$pamount = (float)($_POST['pamount'] ?? 0);
$total = (float)($_POST['total'] ?? 0);
$totalDeposit = (float)($_POST['totalDeposit'] ?? 0);
$cardpercamt = (float)($_POST['cardpercamt'] ?? 0);
$acc = (int)($_POST['acc'] ?? 0);
$pay_By = trim($_POST['pay_By'] ?? '');
$note = trim($_POST['note'] ?? '');
$measurement = trim($_POST['measurement'] ?? '');
$measurement_note = trim($_POST['measurement_note'] ?? '');
$delivery_select = trim($_POST['delivery'] ?? '');
$transaction_id = trim($_POST['transaction_id'] ?? '');

$card_perc = ($pay_By === "Card" || $pay_By === "SAK-Card") ? 2.0 : 0.0;

mysqli_begin_transaction($con);

try {
    // 1. Fetch current rent record
    $curr_stmt = $con->prepare("SELECT * FROM phppos_rent WHERE bill_id = ?");
    $curr_stmt->bind_param("i", $bill_id);
    $curr_stmt->execute();
    $curr_bill = $curr_stmt->get_result()->fetch_assoc();
    if (!$curr_bill) {
        throw new Exception("Rent bill record #{$bill_id} not found.");
    }
    $new_bill_number = $curr_bill['new_bill_number'] ?? '';

    // 2. Update phppos_rent
    $upd_rent_sql = "
        UPDATE phppos_rent SET
            cust_id = ?,
            bill_date = ?,
            pick = ?,
            delivery = ?,
            throught = ?,
            pstatus = ?,
            bal_amount = ?,
            rent_amount = ?,
            amount = ?,
            pick_date = ?,
            delivery_date = ?,
            delivery_status = ?,
            trail_date = ?,
            measurement = ?,
            measurement_note = ?,
            is_delivery = ?,
            throught_phone = ?,
            person_Name = ?,
            person_contact = ?,
            comm_by = ?,
            comm_amount = ?,
            note = ?,
            card_perc = ?,
            card_amt = ?,
            bill_madeby = ?,
            company_name = ?,
            transaction_id = ?,
            payment_mode_name = ?
        WHERE bill_id = ?
    ";
    $upd_stmt = $con->prepare($upd_rent_sql);
    $bal_str = (string)$pamount;
    $rent_amt_int = (int)$total;
    $amt_str = (string)$total;
    $comm_amt_int = (int)$comm;

    $upd_stmt->bind_param(
        "issssssissssssssssssisddssssi",
        $cid, $bill_date, $pick, $del, $throught, $rentpaid,
        $bal_str, $rent_amt_int, $amt_str, $pick_date, $del_date,
        $paid, $trail_date, $measurement, $measurement_note, $delivery_select,
        $tphone, $pname, $pcontact, $commis, $comm_amt_int,
        $note, $card_perc, $cardpercamt, $bill_madeby,
        $company_name, $transaction_id, $pay_By, $bill_id
    );
    if (!$upd_stmt->execute()) {
        throw new Exception("Error updating rent header: " . $upd_stmt->error);
    }

    // 3. Handle deleted line items
    if (!empty($_POST['deleted_detail_id']) && is_array($_POST['deleted_detail_id'])) {
        foreach ($_POST['deleted_detail_id'] as $del_id) {
            $del_id = (int)$del_id;
            if ($del_id > 0) {
                // Delete line item
                $del_stmt = $con->prepare("DELETE FROM order_detail WHERE id = ? AND bill_id = ?");
                $del_stmt->bind_param("ii", $del_id, $bill_id);
                $del_stmt->execute();
            }
        }
    }

    // 4. Update existing items and insert new items
    $order_detail_ids = $_POST['order_detail_id'] ?? [];
    $designs = $_POST['design'] ?? [];
    $items_detail = $_POST['items'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $przs = $_POST['prz'] ?? [];
    $discounts = $_POST['discount'] ?? [];
    $amts = $_POST['amt'] ?? [];
    $deps = $_POST['dep'] ?? [];

    $tol1 = $total;

    for ($i = 0; $i < count($designs); $i++) {
        $sku = trim($designs[$i] ?? '');
        if ($sku === '') continue;

        $od_id = isset($order_detail_ids[$i]) ? (int)$order_detail_ids[$i] : 0;
        $it_detail = trim($items_detail[$i] ?? '');
        $it_qty = (int)($qtys[$i] ?? 1);
        $it_rent = (int)($przs[$i] ?? 0);
        $it_discount = (int)($discounts[$i] ?? 0);
        $it_amt = (int)($amts[$i] ?? ($it_rent * $it_qty - $it_discount));
        $it_dep = (int)($deps[$i] ?? 0);

        // Calculate commission
        if ($commis === "%") {
            $p2 = round((float)$it_amt * ((float)$comm / 100.0));
        } else {
            $c = ($tol1 > 0) ? ((float)$comm / (float)$tol1) : 0;
            $b = $c * 100;
            $p2 = round((float)$it_amt * ((float)$b / 100.0));
        }
        $rpt = (float)$it_rent - (float)$p2;
        $rpt_str = (string)round($rpt);

        if ($od_id > 0) {
            // Update existing row
            $item_upd = $con->prepare("
                UPDATE order_detail SET
                    item_id = ?,
                    rent = ?,
                    deposit = ?,
                    discount = ?,
                    total_amount = ?,
                    item_detail = ?,
                    qty = ?,
                    commission_amt = ?
                WHERE id = ? AND bill_id = ?
            ");
            $item_upd->bind_param("siiiisisii", $sku, $it_rent, $it_dep, $it_discount, $it_amt, $it_detail, $it_qty, $rpt_str, $od_id, $bill_id);
            if (!$item_upd->execute()) {
                throw new Exception("Error updating line item {$sku}: " . $item_upd->error);
            }
        } else {
            // Insert newly added item row
            $item_ins = $con->prepare("
                INSERT INTO order_detail (bill_id, item_id, rent, deposit, discount, total_amount, item_detail, commission_amt, qty, new_bill_number, is_new_with_gst)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $item_ins->bind_param("isiiiisiss", $bill_id, $sku, $it_rent, $it_dep, $it_discount, $it_amt, $it_detail, $rpt_str, $it_qty, $new_bill_number);
            if (!$item_ins->execute()) {
                throw new Exception("Error inserting line item {$sku}: " . $item_ins->error);
            }
        }
    }

    // 5. Update rent_amount
    $check_ra = $con->prepare("SELECT id FROM rent_amount WHERE bill_id = ? LIMIT 1");
    $check_ra->bind_param("i", $bill_id);
    $check_ra->execute();
    $ra_res = $check_ra->get_result();
    if ($ra_row = $ra_res->fetch_assoc()) {
        $upd_ra = $con->prepare("UPDATE rent_amount SET amount = ?, payment_by = ?, return_date = ?, cust_id = ? WHERE id = ?");
        $upd_ra->bind_param("dssii", $pamount, $pay_By, $bill_date, $cid, $ra_row['id']);
        $upd_ra->execute();
    } elseif ($pamount > 0) {
        $ins_ra = $con->prepare("INSERT INTO rent_amount (cust_id, amount, return_date, payment_by, bill_id) VALUES (?, ?, ?, ?, ?)");
        $ins_ra->bind_param("idssi", $cid, $pamount, $bill_date, $pay_By, $bill_id);
        $ins_ra->execute();
    }

    // 6. Update bank_transaction
    $check_bt = $con->prepare("SELECT trans_id FROM bank_transaction WHERE bill_id = ? LIMIT 1");
    $check_bt->bind_param("i", $bill_id);
    $check_bt->execute();
    $bt_res = $check_bt->get_result();
    if ($bt_row = $bt_res->fetch_assoc()) {
        $upd_bt = $con->prepare("UPDATE bank_transaction SET bank_id = ?, trans_amt = ?, trans_date = ?, company_name = ? WHERE trans_id = ?");
        $upd_bt->bind_param("idssi", $acc, $pamount, $bill_date, $company_name, $bt_row['trans_id']);
        $upd_bt->execute();
    } elseif ($acc > 0 && $pamount > 0) {
        $customer_query = mysqli_query($con, "SELECT first_name, last_name FROM phppos_people WHERE person_id = '{$cid}'");
        $cust_row = mysqli_fetch_assoc($customer_query);
        $cust_full_name = trim(($cust_row['first_name'] ?? '') . ' ' . ($cust_row['last_name'] ?? ''));
        $memo = "rent payment from customer " . $cust_full_name;

        $ins_bt = $con->prepare("INSERT INTO bank_transaction (bank_id, trans_type, trans_amt, trans_date, trans_memo, reconcile, enrty_date, company_name, bill_id) VALUES (?, 'receit', ?, ?, ?, 'NO', NOW(), ?, ?)");
        $ins_bt->bind_param("idsssi", $acc, $pamount, $bill_date, $memo, $company_name, $bill_id);
        $ins_bt->execute();
    }

    mysqli_commit($con);
    header("Location: editrent123.php?bill_id=" . urlencode($bill_id) . "&status=success&msg=" . urlencode("Rent bill #" . ($new_bill_number ? $new_bill_number : $bill_id) . " updated successfully!"));
    exit;

} catch (Throwable $e) {
    if ($con && mysqli_ping($con)) {
        mysqli_rollback($con);
    }
    header("Location: editrent123.php?bill_id=" . urlencode($bill_id) . "&status=error&msg=" . urlencode($e->getMessage()));
    exit;
}
?>
