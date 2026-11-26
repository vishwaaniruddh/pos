<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../db_connection.php');
require_once __DIR__ . '/../includes/yn_stock_sync.php';
$con = OpenSrishringarrCon();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = isset($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
    $new_bill_number = trim($_POST['new_bill_number'] ?? '');

    if ($bill_id <= 0) {
        die("Invalid Bill ID.");
    }

    $bill_date = trim($_POST['bill_date'] ?? date('Y-m-d'));
    $bill_prepare_by = trim($_POST['bill_prepare_by'] ?? '');
    $bill_by = trim($_POST['bill_by'] ?? 'APPROVAL RECEIPT');
    $pay_by = trim($_POST['pay_by'] ?? 'Cash');
    $amountTotal = trim($_POST['amountTotal'] ?? '0');
    $paid_amount = trim($_POST['paid_amount'] ?? '0');
    $note = trim($_POST['note'] ?? '');

    mysqli_begin_transaction($con);

    try {
        // 1. Update approval master table
        $stmt = $con->prepare("
            UPDATE approval 
            SET bill_date = ?, bill_prepare_by = ?, bill_by = ?, pay_by = ?, 
                amountTotal = ?, paid_amount = ?, note = ?
            WHERE bill_id = ?
        ");
        $stmt->bind_param("sssssssi", $bill_date, $bill_prepare_by, $bill_by, $pay_by, $amountTotal, $paid_amount, $note, $bill_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating approval bill: " . $stmt->error);
        }

        // 2. Handle line items in approval_detail
        $item_aids = $_POST['item_aid'] ?? [];
        $item_skus = $_POST['item_sku'] ?? [];
        $item_old_qtys = $_POST['item_old_qty'] ?? [];
        $item_qtys = $_POST['item_qty'] ?? [];
        $item_prices = $_POST['item_unit_price'] ?? [];
        $item_discounts = $_POST['item_discount'] ?? [];
        $item_amounts = $_POST['item_amount'] ?? [];
        $delete_item_aids = $_POST['delete_item_aid'] ?? [];

        $ynSyncItems = [];
        foreach ($item_aids as $idx => $raw_aid) {
            $aid = (int)$raw_aid;
            $sku = trim($item_skus[$idx] ?? '');
            $old_qty = (int)($item_old_qtys[$idx] ?? 0);
            $new_qty = (int)($item_qtys[$idx] ?? 0);
            $price = (float)($item_prices[$idx] ?? 0);
            $discount = trim($item_discounts[$idx] ?? '0');
            $amount = (float)($item_amounts[$idx] ?? 0);

            // Check if item is marked for removal
            if (in_array((string)$aid, array_map('strval', $delete_item_aids))) {
                // Restore stock back into phppos_items
                if ($sku !== '' && $old_qty > 0) {
                    $stock_restore = $con->prepare("UPDATE phppos_items SET quantity = quantity + ? WHERE name = ? OR item_number = ?");
                    $stock_restore->bind_param("iss", $old_qty, $sku, $sku);
                    $stock_restore->execute();
                    $ynSyncItems[] = ['sku' => $sku, 'delta_qty' => $old_qty];
                }

                // Delete line item
                $del_it = $con->prepare("DELETE FROM approval_detail WHERE aid = ? AND bill_id = ?");
                $del_it->bind_param("ii", $aid, $bill_id);
                $del_it->execute();
            } else {
                // Adjust stock if quantity changed
                $qty_diff = $new_qty - $old_qty;
                if ($qty_diff !== 0 && $sku !== '') {
                    // If increased, deduct stock; if decreased, add stock
                    $stock_adj = $con->prepare("UPDATE phppos_items SET quantity = quantity - ? WHERE name = ? OR item_number = ?");
                    $stock_adj->bind_param("iss", $qty_diff, $sku, $sku);
                    $stock_adj->execute();
                    $ynSyncItems[] = ['sku' => $sku, 'delta_qty' => -$qty_diff];
                }

                // Update line item details
                $upd_it = $con->prepare("
                    UPDATE approval_detail 
                    SET qty = ?, final_amount = ?, discount = ?, amount = ?
                    WHERE aid = ? AND bill_id = ?
                ");
                $upd_it->bind_param("idsdii", $new_qty, $price, $discount, $amount, $aid, $bill_id);
                $upd_it->execute();
            }
        }

        // 3. Update paid_amount if exists
        $upd_pay = $con->prepare("UPDATE paid_amount SET amount = ?, payment_by = ? WHERE bid = ?");
        $paid_int = (int)$paid_amount;
        $upd_pay->bind_param("isi", $paid_int, $pay_by, $bill_id);
        $upd_pay->execute();

        mysqli_commit($con);
        if (!empty($ynSyncItems)) {
            sync_pos_items_to_yn($con, $ynSyncItems, '', 0, 'Edited POS bill item quantities');
        }
        header("Location: ./editApprovalBillDetails.php?bill_id=" . urlencode($bill_id) . "&status=success&msg=" . urlencode("Bill #{$bill_id} details updated successfully."));
        exit;

    } catch (Exception $e) {
        mysqli_rollback($con);
        header("Location: ./editApprovalBillDetails.php?bill_id=" . urlencode($bill_id) . "&status=error&msg=" . urlencode($e->getMessage()));
        exit;
    }

} else {
    header("Location: ../ssfs_format_sales_view.php");
    exit;
}
?>
