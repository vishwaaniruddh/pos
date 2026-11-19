<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../db_connection.php');
$con = OpenSrishringarrCon();

$bill_id = isset($_REQUEST['bill_id']) ? trim($_REQUEST['bill_id']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

if (empty($bill_id)) {
    die("Error: No Bill ID provided.");
}

// Fetch approval bill record
$stmt = $con->prepare("
    SELECT a.*, p.first_name, p.last_name, p.phone_number, p.address_1, p.city
    FROM approval a
    LEFT JOIN phppos_people p ON a.cust_id = p.person_id
    WHERE a.bill_id = ? OR a.new_bill_number = ?
    LIMIT 1
");
$stmt->bind_param("ss", $bill_id, $bill_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Error: Approval bill not found for ID: " . htmlspecialchars($bill_id));
}

$bill = $res->fetch_assoc();
$numeric_bill_id = $bill['bill_id'];
$new_bill_number = $bill['new_bill_number'] ?? '';
$display_invoice = $new_bill_number ? $new_bill_number : $numeric_bill_id;
$customer_name = trim(($bill['first_name'] ?? '') . ' ' . ($bill['last_name'] ?? ''));
if ($customer_name === '') $customer_name = 'Customer #' . ($bill['cust_id'] ?? '-');

// Fetch line items
$items_stmt = $con->prepare("
    SELECT ad.*, pi.unit_price as catalog_price, pi.quantity as stock_quantity
    FROM approval_detail ad
    LEFT JOIN phppos_items pi ON ad.item_id = pi.name
    WHERE ad.bill_id = ?
    ORDER BY ad.aid ASC
");
$items_stmt->bind_param("i", $numeric_bill_id);
$items_stmt->execute();
$items_res = $items_stmt->get_result();
$items = [];
while ($row = $items_res->fetch_assoc()) {
    $items[] = $row;
}

// Fetch payment records
$pay_stmt = $con->prepare("SELECT * FROM paid_amount WHERE bid = ? OR (new_bill_number = ? AND new_bill_number != '')");
$pay_stmt->bind_param("is", $numeric_bill_id, $new_bill_number);
$pay_stmt->execute();
$payments = $pay_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch bank accounts
$banks_res = mysqli_query($con, "SELECT bank_id, bank_name FROM banks ORDER BY bank_name ASC");
$banks = [];
while ($b = mysqli_fetch_assoc($banks_res)) {
    $banks[] = $b;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Approval Bill - <?= htmlspecialchars($display_invoice) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --primary-hover: #1e293b;
            --background: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --badge-bg: #f1f5f9;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --success: #10b981;
        }
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--background);
            color: var(--text-main);
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .header-title h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
        }
        .header-title p {
            margin: 4px 0 0 0;
            font-size: 13px;
            color: var(--text-muted);
        }
        .btn-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }
        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
        .btn-primary:hover { background-color: var(--primary-hover); }
        .btn-outline {
            background-color: #ffffff;
            color: #334155;
            border-color: var(--border);
        }
        .btn-outline:hover { background-color: #f8fafc; }
        .btn-danger {
            background-color: var(--danger);
            color: #ffffff;
            border-color: var(--danger);
        }
        .btn-danger:hover { background-color: var(--danger-hover); }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin: 0 0 16px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        input[type="text"], input[type="date"], input[type="number"], select, textarea {
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: #ffffff;
            color: var(--text-main);
            outline: none;
            transition: border-color 0.15s ease;
            width: 100%;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tr:hover td {
            background-color: #fbfcfe;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            background: var(--badge-bg);
            color: #475569;
            border: 1px solid var(--border);
        }
        .status-alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .status-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .status-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-bar">
        <div class="header-title">
            <h1>Edit Approval Bill</h1>
            <p>Invoice #<strong><?= htmlspecialchars($display_invoice) ?></strong> &bull; Bill ID: #<?= $numeric_bill_id ?></p>
        </div>
        <div class="btn-group">
            <a href="sales_report_detail.php?id=<?= $numeric_bill_id ?>" target="_blank" class="btn btn-outline">
                <i class="fas fa-eye"></i> View Invoice
            </a>
            <button type="button" class="btn btn-danger" id="btnDeleteBill">
                <i class="fas fa-trash"></i> Delete Bill
            </button>
            <a href="../ssfs_format_sales_view.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Sales View
            </a>
        </div>
    </div>

    <?php if ($status === 'success'): ?>
        <div class="status-alert status-success">
            <i class="fas fa-check-circle"></i>
            <span><?= $msg ? $msg : 'Approval bill details updated successfully!' ?></span>
        </div>
    <?php elseif ($status === 'error'): ?>
        <div class="status-alert status-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= $msg ? $msg : 'An error occurred while updating the approval bill.' ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="updateApprovalBillDetails.php" id="formEditApproval">
        <input type="hidden" name="bill_id" value="<?= $numeric_bill_id ?>">
        <input type="hidden" name="new_bill_number" value="<?= htmlspecialchars($new_bill_number) ?>">

        <!-- Customer & Bill Master Info -->
        <div class="card">
            <div class="card-title">
                <span><i class="fas fa-file-invoice"></i> Bill Header Details</span>
                <span class="badge"><?= htmlspecialchars($bill['company_name'] ?: 'Sri Shringarr') ?></span>
            </div>

            <div class="grid-4" style="margin-bottom: 16px;">
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" value="<?= htmlspecialchars($customer_name) ?>" readonly style="background:#f1f5f9; cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label>Customer ID</label>
                    <input type="text" value="<?= htmlspecialchars($bill['cust_id'] ?? '') ?>" readonly style="background:#f1f5f9; cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label>Bill Date</label>
                    <input type="date" name="bill_date" value="<?= htmlspecialchars($bill['bill_date'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Bill Prepare By</label>
                    <select name="bill_prepare_by">
                        <?php 
                        $preparers = ['Rajni Podar', 'Nipa Agrawal', 'Ajay', 'Laxmikant'];
                        $cur_prep = $bill['bill_prepare_by'] ?? '';
                        if (!in_array($cur_prep, $preparers) && !empty($cur_prep)) {
                            $preparers[] = $cur_prep;
                        }
                        foreach ($preparers as $p): ?>
                            <option value="<?= htmlspecialchars($p) ?>" <?= ($cur_prep === $p) ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-4">
                <div class="form-group">
                    <label>Bill By / Type</label>
                    <select name="bill_by">
                        <option value="APPROVAL RECEIPT" <?= ($bill['bill_by'] === 'APPROVAL RECEIPT') ? 'selected' : '' ?>>APPROVAL RECEIPT</option>
                        <option value="QUOTATION" <?= ($bill['bill_by'] === 'QUOTATION') ? 'selected' : '' ?>>QUOTATION</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="pay_by">
                        <option value="Cash" <?= ($bill['pay_by'] === 'Cash') ? 'selected' : '' ?>>Cash</option>
                        <option value="Bank" <?= ($bill['pay_by'] === 'Bank') ? 'selected' : '' ?>>Bank</option>
                        <option value="Card" <?= ($bill['pay_by'] === 'Card') ? 'selected' : '' ?>>Card</option>
                        <option value="Cheque" <?= ($bill['pay_by'] === 'Cheque') ? 'selected' : '' ?>>Cheque</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Total Amount (₹)</label>
                    <input type="number" step="0.01" name="amountTotal" id="amountTotal" value="<?= htmlspecialchars($bill['amountTotal'] ?? '0') ?>">
                </div>
                <div class="form-group">
                    <label>Paid Amount (₹)</label>
                    <input type="number" step="0.01" name="paid_amount" id="paid_amount" value="<?= htmlspecialchars($bill['paid_amount'] ?? '0') ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top: 16px;">
                <label>Bill Notes / Memo</label>
                <textarea name="note" rows="2"><?= htmlspecialchars($bill['note'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Billed Items Section -->
        <div class="card">
            <div class="card-title">
                <span><i class="fas fa-boxes"></i> Line Items (<?= count($items) ?> items)</span>
                <span class="badge">Items Inventory Synced</span>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>SKU / Item Code</th>
                            <th style="width: 100px;">Quantity</th>
                            <th style="width: 140px;">Unit Price (₹)</th>
                            <th style="width: 120px;">Discount</th>
                            <th style="width: 140px;">Amount (₹)</th>
                            <th style="width: 80px; text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; color:#94a3b8; padding:20px;">No items attached to this approval bill.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $idx => $it): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($it['item_id'] ?? '') ?></strong>
                                        <input type="hidden" name="item_aid[]" value="<?= (int)$it['aid'] ?>">
                                        <input type="hidden" name="item_sku[]" value="<?= htmlspecialchars($it['item_id'] ?? '') ?>">
                                        <input type="hidden" name="item_old_qty[]" value="<?= (int)($it['qty'] ?? 0) ?>">
                                    </td>
                                    <td>
                                        <input type="number" name="item_qty[]" class="item-qty-input" min="0" value="<?= (int)($it['qty'] ?? 0) ?>" style="width:80px;">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="item_unit_price[]" class="item-price-input" value="<?= htmlspecialchars($it['final_amount'] ?: ($it['price'] ?: '0')) ?>" style="width:110px;">
                                    </td>
                                    <td>
                                        <input type="text" name="item_discount[]" value="<?= htmlspecialchars($it['discount'] ?? '0') ?>" style="width:90px;">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="item_amount[]" class="item-amount-input" value="<?= htmlspecialchars($it['amount'] ?? '0') ?>" style="width:110px;">
                                    </td>
                                    <td style="text-align:center;">
                                        <label style="font-size:11px; cursor:pointer; color:#ef4444; font-weight:normal;">
                                            <input type="checkbox" name="delete_item_aid[]" value="<?= (int)$it['aid'] ?>"> Remove
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Submit Toolbar -->
        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:16px;">
            <a href="../ssfs_format_sales_view.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary" style="padding:10px 28px; font-size:14px;">
                <i class="fas fa-save"></i> Save Bill Changes
            </button>
        </div>
    </form>
</div>

<script>
// Confirm & Execute Deletion
document.getElementById('btnDeleteBill').addEventListener('click', function() {
    const billId = "<?= $numeric_bill_id ?>";
    const billNum = "<?= htmlspecialchars($display_invoice) ?>";

    if (confirm(`Are you sure you want to delete Approval Bill (${billNum})?\n\nThis will safely ARCHIVE the full bill into the audit table, restore item stock quantities, and remove the bill.`)) {
        fetch('approval_report_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'bill_id=' + encodeURIComponent(billId) + '&reason=' + encodeURIComponent('Deleted from edit bill details screen')
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                window.location.href = '../ssfs_format_sales_view.php';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            alert('Request failed: ' + err.message);
        });
    }
});
</script>
</body>
</html>
