<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

include('../db_connection.php');
$con = OpenSrishringarrCon();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['bill_id']) || isset($_GET['bill_id']))) {
    $raw_bill_id = trim($_POST['bill_id'] ?? $_GET['bill_id'] ?? '');
    $delete_reason = trim($_POST['reason'] ?? 'Deleted via admin panel');

    if ($raw_bill_id === '') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Bill ID is required."]);
        exit;
    }

    // Find the approval bill
    $bill_stmt = $con->prepare("SELECT * FROM approval WHERE bill_id = ? OR new_bill_number = ? LIMIT 1");
    $bill_stmt->bind_param("ss", $raw_bill_id, $raw_bill_id);
    $bill_stmt->execute();
    $bill_res = $bill_stmt->get_result();

    if ($bill_res->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Approval bill with ID '{$raw_bill_id}' not found."]);
        exit;
    }

    $bill = $bill_res->fetch_assoc();
    $bill_id = (int)$bill['bill_id'];
    $new_bill_number = $bill['new_bill_number'] ?? '';
    $cust_id = (int)($bill['cust_id'] ?? 0);

    // Fetch customer name
    $customer_name = '-';
    if ($cust_id > 0) {
        $cust_stmt = $con->prepare("SELECT first_name, last_name, phone_number FROM phppos_people WHERE person_id = ? LIMIT 1");
        $cust_stmt->bind_param("i", $cust_id);
        $cust_stmt->execute();
        $cust_res = $cust_stmt->get_result();
        if ($cust_row = $cust_res->fetch_assoc()) {
            $customer_name = trim(($cust_row['first_name'] ?? '') . ' ' . ($cust_row['last_name'] ?? ''));
        }
    }

    // Fetch all items from approval_detail
    $items = [];
    $item_stmt = $con->prepare("SELECT * FROM approval_detail WHERE bill_id = ?");
    $item_stmt->bind_param("i", $bill_id);
    $item_stmt->execute();
    $items_res = $item_stmt->get_result();
    while ($row = $items_res->fetch_assoc()) {
        $items[] = $row;
    }

    // Fetch payment records from paid_amount
    $payments = [];
    $pay_stmt = $con->prepare("SELECT * FROM paid_amount WHERE bid = ? OR (new_bill_number = ? AND new_bill_number != '')");
    $pay_stmt->bind_param("is", $bill_id, $new_bill_number);
    $pay_stmt->execute();
    $pay_res = $pay_stmt->get_result();
    while ($row = $pay_res->fetch_assoc()) {
        $payments[] = $row;
    }

    // Fetch bank transactions
    $bank_trans = [];
    $bank_stmt = $con->prepare("SELECT * FROM bank_transaction WHERE bill_id = ? OR (new_bill_number = ? AND new_bill_number != '')");
    $bank_stmt->bind_param("is", $bill_id, $new_bill_number);
    $bank_stmt->execute();
    $bank_res = $bank_stmt->get_result();
    while ($row = $bank_res->fetch_assoc()) {
        $bank_trans[] = $row;
    }

    // Serialize data for audit
    $bill_data_json = json_encode($bill, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $items_data_json = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $payments_data_json = json_encode([
        "paid_amount" => $payments,
        "bank_transactions" => $bank_trans
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $deleted_by = $_SESSION['username'] ?? 'admin';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

    // Begin database transaction for safe archival, stock restore, and deletion
    mysqli_begin_transaction($con);

    try {
        // 1. Insert snapshot into audit table
        $audit_stmt = $con->prepare("
            INSERT INTO approval_deleted_audit (
                bill_id, new_bill_number, cust_id, customer_name, bill_date, status,
                amount_total, paid_amount, bill_by, pay_by, company_name, bill_prepare_by,
                note, bill_data_json, items_data_json, payments_data_json, deleted_by,
                deleted_at, delete_reason, ip_address
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
        ");

        $audit_stmt->bind_param(
            "isissssssssssssssss",
            $bill_id,
            $new_bill_number,
            $cust_id,
            $customer_name,
            $bill['bill_date'],
            $bill['status'],
            $bill['amountTotal'],
            $bill['paid_amount'],
            $bill['bill_by'],
            $bill['pay_by'],
            $bill['company_name'],
            $bill['bill_prepare_by'],
            $bill['note'],
            $bill_data_json,
            $items_data_json,
            $payments_data_json,
            $deleted_by,
            $delete_reason,
            $ip_address
        );

        if (!$audit_stmt->execute()) {
            throw new Exception("Failed to insert audit record: " . $audit_stmt->error);
        }

        // 2. Restore item stock in phppos_items
        foreach ($items as $item) {
            $item_sku = $item['item_id'] ?? '';
            $item_qty = (int)($item['qty'] ?? 0);
            if ($item_sku !== '' && $item_qty > 0) {
                $stock_stmt = $con->prepare("UPDATE phppos_items SET quantity = quantity + ? WHERE name = ?");
                $stock_stmt->bind_param("is", $item_qty, $item_sku);
                $stock_stmt->execute();
            }
        }

        // 3. Delete from approval_detail
        $del_details = $con->prepare("DELETE FROM approval_detail WHERE bill_id = ?");
        $del_details->bind_param("i", $bill_id);
        $del_details->execute();

        // 4. Delete payments and bank transactions
        $del_payments = $con->prepare("DELETE FROM paid_amount WHERE bid = ? OR (new_bill_number = ? AND new_bill_number != '')");
        $del_payments->bind_param("is", $bill_id, $new_bill_number);
        $del_payments->execute();

        $del_bank = $con->prepare("DELETE FROM bank_transaction WHERE bill_id = ? OR (new_bill_number = ? AND new_bill_number != '')");
        $del_bank->bind_param("is", $bill_id, $new_bill_number);
        $del_bank->execute();

        // 5. Delete master bill record from approval
        $del_bill = $con->prepare("DELETE FROM approval WHERE bill_id = ?");
        $del_bill->bind_param("i", $bill_id);
        $del_bill->execute();

        // Commit transaction
        mysqli_commit($con);

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Approval bill (" . ($new_bill_number ?: $bill_id) . ") deleted and safely archived to audit log."
        ]);

    } catch (Exception $e) {
        mysqli_rollback($con);
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }

} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid request method or missing bill_id."]);
}

mysqli_close($con);
?>
