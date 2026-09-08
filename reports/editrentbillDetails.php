<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

include('../db_connection.php');
$con = OpenSrishringarrCon();

$bill_id = isset($_REQUEST['bill_id']) ? intval($_REQUEST['bill_id']) : 0;
$status  = isset($_GET['status']) ? trim($_GET['status']) : '';

$sql_result = null;
if ($con && $bill_id > 0) {
    $query = "
        SELECT r.bill_id, r.cust_id, r.bill_date, r.pick_date, r.delivery_date, r.measurement, r.measurement_note, r.delivery, r.new_bill_number,
               p.first_name, p.last_name, p.phone_number
        FROM phppos_rent r
        LEFT JOIN phppos_people p ON r.cust_id = p.person_id
        WHERE r.bill_id = '$bill_id'
    ";
    $sql = mysqli_query($con, $query);
    if ($sql) {
        $sql_result = mysqli_fetch_assoc($sql);
    }
}

$bill_date        = $sql_result['bill_date'] ?? '';
$pick_date        = $sql_result['pick_date'] ?? '';
$delivery_date    = $sql_result['delivery_date'] ?? '';
$measurement      = strtolower($sql_result['measurement'] ?? '');
$measurement_note = $sql_result['measurement_note'] ?? '';
$delivery         = strtolower($sql_result['delivery'] ?? '');
$new_bill_number  = $sql_result['new_bill_number'] ?? '';
$first_name       = $sql_result['first_name'] ?? '';
$last_name        = $sql_result['last_name'] ?? '';
$phone_number     = $sql_result['phone_number'] ?? '';

// Fetch line items from order_detail
$items = [];
if ($con && $bill_id > 0) {
    $detailssql = mysqli_query($con, "SELECT * FROM order_detail WHERE bill_id = '$bill_id'");
    if ($detailssql) {
        while ($row = mysqli_fetch_assoc($detailssql)) {
            $items[] = $row;
        }
    }
}

if (file_exists('../top-header.php')) include_once('../top-header.php');
if (file_exists('../top-navbar.php')) include_once('../top-navbar.php');
?>

<div class="container-fluid page-body-wrapper" style="padding-top: 58px !important;">
    <?php if (file_exists('../navbar.php')) include_once('../navbar.php'); ?>
    <div class="main-panel" style="margin-left: 240px;">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
            
            <style>
                :root {
                    --pm-slate-50: #f8fafc;
                    --pm-slate-100: #f1f5f9;
                    --pm-slate-200: #e2e8f0;
                    --pm-slate-300: #cbd5e1;
                    --pm-slate-400: #94a3b8;
                    --pm-slate-500: #64748b;
                    --pm-slate-600: #475569;
                    --pm-slate-700: #334155;
                    --pm-slate-800: #1e293b;
                    --pm-slate-900: #0f172a;
                }

                #sidebar {
                    position: fixed !important;
                    top: 58px;
                    left: 0;
                    bottom: 0;
                    height: calc(100vh - 58px);
                    z-index: 99;
                }

                .pm-page-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 16px;
                    margin-bottom: 20px;
                    padding-bottom: 16px;
                    border-bottom: 1px solid var(--pm-slate-200);
                }

                .pm-page-title {
                    font-size: 22px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    letter-spacing: -0.02em;
                    margin: 0;
                }

                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 20px;
                    overflow: hidden;
                }

                .pm-card-header {
                    padding: 14px 20px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .pm-card-title {
                    font-size: 14px;
                    font-weight: 600;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .pm-card-body {
                    padding: 20px;
                }

                .pm-form-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                    gap: 16px;
                }

                .pm-form-group {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }

                .pm-label {
                    font-size: 12px;
                    font-weight: 500;
                    color: var(--pm-slate-700);
                }

                .pm-input, .pm-select {
                    height: 36px;
                    padding: 0 12px;
                    font-size: 13px;
                    color: var(--pm-slate-900);
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    box-sizing: border-box;
                    outline: none;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-input:focus, .pm-select:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                .pm-btn {
                    height: 36px;
                    padding: 0 14px;
                    font-size: 13px;
                    font-weight: 500;
                    border-radius: 6px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                    white-space: nowrap;
                    border: 1px solid transparent;
                    box-sizing: border-box;
                }

                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                .pm-btn-primary:hover {
                    background: var(--pm-slate-800);
                    color: #ffffff;
                }

                .pm-btn-secondary {
                    background: #ffffff;
                    color: var(--pm-slate-700);
                    border-color: var(--pm-slate-200);
                }

                .pm-btn-secondary:hover {
                    background: var(--pm-slate-50);
                    border-color: var(--pm-slate-300);
                    color: var(--pm-slate-900);
                }

                .pm-alert-success {
                    padding: 10px 16px;
                    background: #f0fdf4;
                    border: 1px solid #bbf7d0;
                    border-radius: 6px;
                    color: #15803d;
                    font-size: 13px;
                    margin-bottom: 16px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .pm-alert-error {
                    padding: 10px 16px;
                    background: #fef2f2;
                    border: 1px solid #fecaca;
                    border-radius: 6px;
                    color: #b91c1c;
                    font-size: 13px;
                    margin-bottom: 16px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .pm-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 13px;
                }

                .pm-table th {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-600);
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    padding: 10px 14px;
                    text-align: left;
                    border-bottom: 1px solid var(--pm-slate-200);
                }

                .pm-table td {
                    padding: 10px 14px;
                    border-bottom: 1px solid var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    vertical-align: middle;
                }
            </style>

            <div class="edit-bill-details-container" style="max-width: 900px; margin: 0 auto;">

                <!-- Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-pen-to-square" style="margin-right: 10px; font-size: 20px; color: var(--pm-slate-700);"></i>
                            Modify Rent Invoice <?= htmlspecialchars($new_bill_number ? $new_bill_number : '#' . $bill_id) ?>
                        </h1>
                        <p style="font-size: 13px; color: var(--pm-slate-500); margin-top: 4px; margin-bottom: 0;">
                            Customer: <strong><?= htmlspecialchars(trim("$first_name $last_name") ?: 'Customer #' . $bill_id) ?></strong>
                            <?= $phone_number ? '• Mobile: ' . htmlspecialchars($phone_number) : '' ?>
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <a href="editRentBill.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Invoices
                        </a>
                        <a href="rent_report_detail.php?id=<?= $bill_id ?>" target="_blank" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-eye"></i> View Bill
                        </a>
                    </div>
                </div>

                <?php if ($status === 'success'): ?>
                    <div class="pm-alert-success">
                        <i class="fa fa-circle-check"></i>
                        <span>Rent invoice dates and line item details updated successfully!</span>
                    </div>
                <?php elseif ($status === 'error'): ?>
                    <div class="pm-alert-error">
                        <i class="fa fa-triangle-exclamation"></i>
                        <span>There was an error updating the invoice details. Please verify your inputs.</span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="updateRentBillDetails.php">
                    <input type="hidden" name="bill_id" value="<?= $bill_id ?>">

                    <!-- Bill Dates & Logistics Card -->
                    <div class="pm-card">
                        <div class="pm-card-header">
                            <h2 class="pm-card-title">
                                <i class="fa-solid fa-calendar-days"></i> Booking Dates & Logistics
                            </h2>
                        </div>
                        <div class="pm-card-body">
                            <div class="pm-form-grid">
                                <div class="pm-form-group">
                                    <label class="pm-label" for="bill_date">Invoice Date</label>
                                    <input type="date" name="bill_date" id="bill_date" class="pm-input" value="<?= htmlspecialchars($bill_date) ?>" required>
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="pick_date">Pickup Date</label>
                                    <input type="date" name="pick_date" id="pick_date" class="pm-input" value="<?= htmlspecialchars($pick_date) ?>">
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="delivery_date">Delivery / Return Date</label>
                                    <input type="date" name="delivery_date" id="delivery_date" class="pm-input" value="<?= htmlspecialchars($delivery_date) ?>">
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="measurement">Alteration / Measurement</label>
                                    <select id="measurement" name="measurement" class="pm-select">
                                        <option value="">Select Option</option>
                                        <option value="yes" <?= ($measurement === 'yes') ? 'selected' : '' ?>>Yes (Alterations Required)</option>
                                        <option value="no" <?= ($measurement === 'no') ? 'selected' : '' ?>>No (As Is)</option>
                                    </select>
                                </div>

                                <div class="pm-form-group">
                                    <label class="pm-label" for="delivery">Delivery Needed</label>
                                    <select id="delivery" name="delivery" class="pm-select">
                                        <option value="">Select Option</option>
                                        <option value="yes" <?= ($delivery === 'yes') ? 'selected' : '' ?>>Yes (Doorstep Delivery)</option>
                                        <option value="no" <?= ($delivery === 'no') ? 'selected' : '' ?>>No (Store Pickup)</option>
                                    </select>
                                </div>

                                <div class="pm-form-group" style="grid-column: 1 / -1;">
                                    <label class="pm-label" for="measurement_note">Measurement & Fitting Notes</label>
                                    <input type="text" name="measurement_note" id="measurement_note" class="pm-input" placeholder="Enter special alteration or tailoring notes..." value="<?= htmlspecialchars($measurement_note) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items Card -->
                    <div class="pm-card">
                        <div class="pm-card-header">
                            <h2 class="pm-card-title">
                                <i class="fa-solid fa-list"></i> Order Line Items & Descriptions
                            </h2>
                            <span style="font-size: 12px; color: var(--pm-slate-500);"><?= count($items) ?> Items</span>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="pm-table">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th style="width: 140px;">Item Code / SKU</th>
                                        <th>Item Description / Particulars</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($items)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align: center; padding: 24px; color: var(--pm-slate-400);">
                                                No line items recorded for this invoice.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                        $num = 1;
                                        foreach ($items as $it): 
                                        ?>
                                            <tr>
                                                <td style="font-family: monospace; color: var(--pm-slate-400);"><?= $num ?></td>
                                                <td>
                                                    <input type="hidden" name="detail_id[]" value="<?= intval($it['id']) ?>">
                                                    <span style="font-family: monospace; font-weight: 700; color: var(--pm-slate-900);">
                                                        <?= htmlspecialchars($it['item_id']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <input type="text" name="item_detail[]" class="pm-input" style="width: 100%;" value="<?= htmlspecialchars($it['item_detail'] ?? '') ?>" placeholder="Enter item particulars / alterations...">
                                                </td>
                                            </tr>
                                        <?php 
                                            $num++;
                                        endforeach; 
                                        ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="pm-card-header" style="justify-content: flex-end; background: var(--pm-slate-50);">
                            <button type="submit" name="submit" class="pm-btn pm-btn-primary">
                                <i class="fa fa-floppy-disk"></i> Update Invoice Details
                            </button>
                        </div>
                    </div>
                </form>

            </div>

        </div>
    </div>
</div>

<?php 
if ($con) CloseCon($con);
?>
</body>
</html>
