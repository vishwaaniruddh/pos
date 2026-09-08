<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (file_exists('../top-header.php')) include_once('../top-header.php');
if (file_exists('../top-navbar.php')) include_once('../top-navbar.php');
?>
<div class="container-fluid page-body-wrapper">
    <?php
    if (file_exists('../navbar.php')) include_once('../navbar.php');
    if (file_exists('../db_connection.php')) include_once('../db_connection.php');
    $con = OpenSrishringarrCon();

    // Next Purchase ID
    $qryid = mysqli_query($con, "SELECT MAX(pur_id) FROM phppos_purchase");
    $row1 = mysqli_fetch_row($qryid);
    $pur_id = ($row1 && $row1[0]) ? ($row1[0] + 1) : 1;

    // Last Bill Number reference
    $maxbillno = mysqli_query($con, "SELECT MAX(bill_id) FROM phppos_purchase");
    $maxbill = mysqli_fetch_row($maxbillno);
    $last_bill_no = ($maxbill && $maxbill[0]) ? $maxbill[0] : '';

    // Next item number calculation
    $res = mysqli_query($con, "SELECT item_number FROM phppos_items WHERE item_id = (SELECT MAX(item_id) FROM phppos_items)");
    $row = mysqli_fetch_row($res);
    $itm = ($row && $row[0]) ? $row[0] : 'AAA';

    if ($itm == '1A0' || empty($itm)) {
        $item_info = "AAA";
    } else {
        $f = substr($itm, 0, 1);
        $s = substr($itm, 1, 1);
        $t = substr($itm, 2, 1);

        if ($t == 'Z') {
            $t = 'A';
            if ($s == 'Z') {
                $s = 'A';
                $fc = ord($f) + 1;
                $f = chr($fc);
            } else {
                $sc = ord($s) + 1;
                $s = chr($sc);
            }
        } else {
            $tc = ord($t) + 1;
            $t = chr($tc);
        }
        $item_info = "" . $f . $s . $t;
    }

    // Categories query
    $qryitem = mysqli_query($con, "SELECT category FROM categories ORDER BY category ASC");
    $categories = [];
    if ($qryitem) {
        while ($cRow = mysqli_fetch_row($qryitem)) {
            $categories[] = $cRow[0];
        }
    }
    ?>

    <!-- Main Panel -->
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">

            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"
                integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            <style>
                :root {
                    --pm-slate-900: #0f172a;
                    --pm-slate-800: #1e293b;
                    --pm-slate-700: #334155;
                    --pm-slate-600: #475569;
                    --pm-slate-500: #64748b;
                    --pm-slate-400: #94a3b8;
                    --pm-slate-200: #e2e8f0;
                    --pm-slate-100: #f1f5f9;
                    --pm-slate-50: #f8fafc;
                }

                .purchase-container {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                /* Page Header */
                .pm-page-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 20px;
                    flex-wrap: wrap;
                    gap: 12px;
                }

                .pm-page-title {
                    font-size: 20px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    letter-spacing: -0.02em;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .pm-page-subtitle {
                    font-size: 13px;
                    color: var(--pm-slate-500);
                    margin: 3px 0 0 0;
                }

                /* Cards */
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
                    background: #ffffff;
                    border-bottom: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 10px;
                }

                .pm-card-title {
                    font-size: 14px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .pm-card-body {
                    padding: 20px;
                }

                /* Form Controls */
                .pm-form-group {
                    margin-bottom: 0;
                }

                .pm-form-label {
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--pm-slate-700);
                    margin-bottom: 6px;
                    display: block;
                }

                .pm-form-label .req {
                    color: #ef4444;
                    margin-left: 2px;
                }

                .pm-control {
                    height: 38px;
                    font-size: 13px;
                    color: var(--pm-slate-900);
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    padding: 6px 12px;
                    width: 100%;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-control:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                    outline: none;
                }

                /* Table Form Controls */
                .pm-table-control {
                    height: 34px;
                    font-size: 12.5px;
                    color: var(--pm-slate-900);
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    padding: 4px 8px;
                    width: 100%;
                    transition: border-color 0.15s ease;
                }

                .pm-table-control:focus {
                    border-color: var(--pm-slate-900);
                    outline: none;
                }

                .pm-code-badge-input {
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-weight: 600;
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-800);
                    border-color: var(--pm-slate-200);
                }

                /* Buttons */
                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border: 1px solid var(--pm-slate-900);
                    font-size: 13px;
                    font-weight: 600;
                    border-radius: 6px;
                    padding: 8px 18px;
                    height: 38px;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                }

                .pm-btn-primary:hover {
                    background: var(--pm-slate-800);
                    border-color: var(--pm-slate-800);
                    color: #ffffff;
                }

                .pm-btn-outline {
                    background: #ffffff;
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
                    font-size: 12.5px;
                    font-weight: 500;
                    border-radius: 6px;
                    padding: 7px 14px;
                    height: 38px;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                }

                .pm-btn-outline:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900);
                    border-color: #cbd5e1;
                }

                .pm-action-btn {
                    width: 32px;
                    height: 32px;
                    border-radius: 6px;
                    border: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                    color: var(--pm-slate-600);
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: all 0.15s ease;
                }

                .pm-action-btn:hover {
                    background: #fef2f2;
                    color: #ef4444;
                    border-color: #fecaca;
                }

                /* Ledger Table */
                .pm-items-table {
                    margin: 0;
                    width: 100%;
                    border-collapse: collapse;
                }

                .pm-items-table th {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-600);
                    font-size: 11.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                    padding: 10px 10px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    border-top: none;
                    white-space: nowrap;
                }

                .pm-items-table td {
                    padding: 8px 10px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    vertical-align: middle;
                }

                .pm-items-table tbody tr:hover {
                    background-color: #fafbfc;
                }

                /* Summary Area */
                .pm-calc-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 18px 20px;
                }

                .pm-calc-row {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 8px 0;
                    border-bottom: 1px solid var(--pm-slate-100);
                    font-size: 13px;
                }

                .pm-calc-row:last-child {
                    border-bottom: none;
                }

                .pm-calc-label {
                    color: var(--pm-slate-600);
                    font-weight: 500;
                }

                .pm-calc-val {
                    color: var(--pm-slate-900);
                    font-weight: 700;
                }

                .pm-grand-total-row {
                    background: var(--pm-slate-50);
                    padding: 12px 14px;
                    border-radius: 6px;
                    margin-top: 10px;
                    border: 1px solid var(--pm-slate-200);
                }

                /* Suggestion dropdown styling */
                #restul select.show {
                    width: 100%;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    background: #ffffff;
                    font-size: 12.5px;
                    padding: 4px;
                    margin-top: 4px;
                }
            </style>

            <div class="purchase-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa fa-shopping-bag text-muted" style="font-size: 18px; margin-right: 8px;"></i>
                            Supplier Bill & Purchase Entry
                        </h1>
                        <p class="pm-page-subtitle">Record inventory purchases, automatic barcode item code generation, and vendor billing totals.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="view_bills.php" class="pm-btn-outline" title="View historical purchase bills">
                            <i class="fa fa-list"></i> View Purchase Bills
                        </a>
                        <a href="add_supplier.php" class="pm-btn-outline" title="Add a new vendor/supplier">
                            <i class="fa fa-user-plus"></i> Add New Supplier
                        </a>
                    </div>
                </div>

                <!-- Main Purchase Form -->
                <form id="purchse" action="processPurchasetest.php" method="POST" onsubmit="return details();">
                    <input type="hidden" id="rowcnt" value="1" readonly />
                    <input type="hidden" name="theValue" id="theValue" value="5" />
                    <input type="hidden" name="myval" id="myval" value="" />
                    <input type="hidden" name="distype" id="distype" value="percentage" />

                    <!-- 1. Invoice & Supplier Details Card -->
                    <div class="pm-card">
                        <div class="pm-card-header">
                            <h3 class="pm-card-title">
                                <i class="fa fa-file-text-o text-muted" style="margin-right: 6px;"></i>
                                Bill & Supplier Particulars
                            </h3>
                            <span style="font-size: 12px; color: var(--pm-slate-500);">
                                Previous Bill Ref: <strong>#<?php echo htmlspecialchars($last_bill_no); ?></strong>
                            </span>
                        </div>
                        <div class="pm-card-body">
                            <div class="row g-3">

                                <!-- Purchase ID -->
                                <div class="col-xl-2 col-lg-3 col-md-6 col-sm-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="pur_id">Purchase ID</label>
                                        <input type="text" name="pur_id" id="pur_id" class="pm-control pm-code-badge-input" 
                                               value="<?php echo $pur_id; ?>" readonly />
                                    </div>
                                </div>

                                <!-- Bill No -->
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="bill_id">
                                            Supplier Bill / Invoice No <span class="req">*</span>
                                        </label>
                                        <input type="text" name="bill_id" id="bill_id" class="pm-control" 
                                               placeholder="e.g. INV-8821" autocomplete="off" required />
                                    </div>
                                </div>

                                <!-- Supplier Dropdown -->
                                <div class="col-xl-4 col-lg-3 col-md-6 col-sm-12">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="supp_id">
                                            Select Supplier <span class="req">*</span>
                                        </label>
                                        <select name="supp_id" id="supp_id" class="pm-control" required>
                                            <option value="0">-- Choose Vendor / Supplier --</option>
                                            <?php
                                            $qrysupp = mysqli_query($con, "SELECT person_id, company_name FROM phppos_suppliers ORDER BY company_name ASC");
                                            if ($qrysupp) {
                                                while ($sRow = mysqli_fetch_row($qrysupp)) {
                                                    echo "<option value='" . htmlspecialchars($sRow[0]) . "'>" . htmlspecialchars($sRow[1]) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Bill Date -->
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="bill_date">
                                            Bill Date <span class="req">*</span>
                                        </label>
                                        <input type="date" name="bill_date" id="bill_date" class="pm-control" 
                                               value="<?php echo date('Y-m-d'); ?>" required />
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- 2. Line Items Table Card -->
                    <div class="pm-card">
                        <div class="pm-card-header">
                            <div>
                                <h3 class="pm-card-title">
                                    <i class="fa fa-cubes text-muted" style="margin-right: 6px;"></i>
                                    Purchased Items & Inventory Stock
                                </h3>
                                <div style="font-size: 12px; color: var(--pm-slate-500); margin-top: 2px;">
                                    Type Item Name to auto-fetch existing rates, or automatically generate a new SKU code.
                                </div>
                            </div>
                            <button type="button" class="pm-btn-outline" style="height: 34px; padding: 4px 12px;" onclick="showrow()">
                                <i class="fa fa-plus"></i> Add Line Item
                            </button>
                        </div>

                        <!-- Real-time Alerts -->
                        <div id="targetDiv1" style="padding: 0 20px; font-size: 12.5px;"></div>
                        <div id="restul" style="padding: 0 20px;"></div>

                        <div class="table-responsive">
                            <table class="table pm-items-table" id="purchaseItemsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;">Item Number</th>
                                        <th>Item / SKU Name</th>
                                        <th style="width: 170px;">Category</th>
                                        <th style="width: 130px; text-align: right;">Cost Price (₹)</th>
                                        <th style="width: 130px; text-align: right;">Sales Price (₹)</th>
                                        <th style="width: 100px; text-align: center;">Qty</th>
                                        <th style="width: 140px; text-align: right;">Subtotal (₹)</th>
                                        <th style="width: 60px; text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="back">
                                    <!-- Dynamic rows loaded via getnewrow.php -->
                                </tbody>
                            </table>
                        </div>

                        <div id="image" class="text-center py-2"></div>

                        <div class="p-3 bg-light d-flex align-items-center justify-content-between border-top">
                            <button type="button" class="pm-btn-outline" onclick="showrow()">
                                <i class="fa fa-plus"></i> Add Another Row
                            </button>
                            <div style="font-size: 12px; color: var(--pm-slate-500);">
                                <i class="fa fa-info-circle text-muted"></i> Press <strong>Tab</strong> to move across inputs. Press <strong>Ctrl + B</strong> to focus barcode.
                            </div>
                        </div>
                    </div>

                    <!-- 3. Billing Summary & Action Toolbar -->
                    <div class="row g-3">
                        <div class="col-lg-6 col-md-12">
                            <div class="pm-card h-100">
                                <div class="pm-card-header">
                                    <h4 class="pm-card-title">
                                        <i class="fa fa-keyboard-o text-muted" style="margin-right: 6px;"></i>
                                        Billing Notes & Instructions
                                    </h4>
                                </div>
                                <div class="pm-card-body">
                                    <ul style="font-size: 12.5px; color: var(--pm-slate-600); line-height: 1.7; padding-left: 18px; margin-bottom: 0;">
                                        <li>If the Item Name already exists in inventory, its existing category, cost price, and unit price will be pre-filled automatically.</li>
                                        <li>When entering a new item, the system automatically computes the next batch sequential alphanumeric item code (e.g. <code><?php echo htmlspecialchars($item_info); ?></code>).</li>
                                        <li>Subtotals and grand totals update in real-time as quantities or unit costs change.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-12">
                            <div class="pm-card pm-calc-card">
                                
                                <!-- Total Qty -->
                                <div class="pm-calc-row">
                                    <span class="pm-calc-label">Total Quantity:</span>
                                    <span class="pm-calc-val">
                                        <input type="text" name="totalqty" id="totalqty" value="0" readonly 
                                               style="width: 100px; text-align: right; border: none; font-weight: 700; background: transparent;" /> pcs
                                    </span>
                                </div>

                                <!-- Total Gross Amount -->
                                <div class="pm-calc-row">
                                    <span class="pm-calc-label">Gross Total Amount:</span>
                                    <span class="pm-calc-val">
                                        ₹ <input type="text" name="totalamt" id="totalamt" value="0.00" readonly 
                                               style="width: 120px; text-align: right; border: none; font-weight: 700; background: transparent;" />
                                    </span>
                                </div>

                                <!-- Discount Row -->
                                <div class="pm-calc-row align-items-center">
                                    <span class="pm-calc-label">Discount:</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <label style="font-size: 12px; margin: 0; cursor: pointer;">
                                            <input type="radio" name="dis" class="dis" value="1" checked onclick="calcAmt()" /> %
                                        </label>
                                        <label style="font-size: 12px; margin: 0; cursor: pointer;">
                                            <input type="radio" name="dis" class="dis" value="0" onclick="calcAmt()" /> ₹
                                        </label>
                                        <input type="text" id="per" name="per" class="pm-control" value="0" 
                                               onkeypress="return isNumberKey(event)" onkeyup="calcAmt()" 
                                               style="width: 80px; height: 32px; text-align: right;" autocomplete="off" />
                                    </div>
                                </div>

                                <!-- Net Payable Row -->
                                <div class="pm-calc-row pm-grand-total-row">
                                    <span style="font-size: 14px; font-weight: 700; color: var(--pm-slate-900);">Net Payable Amount:</span>
                                    <span style="font-size: 18px; font-weight: 800; color: var(--pm-slate-900);">
                                        ₹ <input type="text" name="payamt" id="payamt" value="0.00" readonly 
                                                 style="width: 140px; text-align: right; border: none; font-weight: 800; background: transparent; font-size: 18px;" />
                                    </span>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex align-items-center justify-content-end gap-2 mt-3 pt-2">
                                    <button type="reset" class="pm-btn-outline" onclick="setTimeout(subtotal, 100)">
                                        <i class="fa fa-refresh"></i> Reset
                                    </button>
                                    <button type="submit" id="btnSubmitPurchase" class="pm-btn-primary">
                                        <i class="fa fa-check"></i> Submit Purchase Bill
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                </form>

            </div>

            <!-- Client Scripting & Calculations -->
            <script>
                var isCtrl = false;
                document.onkeyup = function(e) {
                    if (e.which == 17) isCtrl = false;
                };
                document.onkeydown = function(e) {
                    if (e.which == 17) isCtrl = true;
                    if (e.which == 66 && isCtrl == true) {
                        var bc = document.getElementById("barcode");
                        if (bc) bc.focus();
                        return false;
                    }
                };

                // Add dynamic row
                function showrow() {
                    var cnt = document.getElementById("rowcnt").value;
                    document.getElementById('image').innerHTML = '<div class="text-muted" style="font-size:12px;"><i class="fa fa-spinner fa-spin"></i> Adding new row...</div>';
                    document.getElementById("rowcnt").value = parseInt(cnt) + 1;

                    $.ajax({
                        type: "POST",
                        url: "getnewrow.php",
                        data: { cnt: cnt },
                        cache: false,
                        success: function(msg) {
                            var numi = document.getElementById('theValue');
                            var num = parseInt(numi.value) + 1;
                            numi.value = num;

                            var newTr = document.createElement("tr");
                            newTr.setAttribute('id', num);
                            newTr.innerHTML = msg + 
                                '<td class="text-center" style="vertical-align:middle;">' +
                                '  <button type="button" class="pm-action-btn text-danger" onclick="removeElement(' + num + ')" title="Remove item">' +
                                '    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>' +
                                '  </button>' +
                                '</td>';
                            
                            document.getElementById('back').appendChild(newTr);
                            document.getElementById('image').innerHTML = "";
                        },
                        error: function() {
                            document.getElementById('image').innerHTML = "";
                        }
                    });
                }

                // Remove element
                function removeElement(divNum) {
                    var d = document.getElementById('back');
                    var olddiv = document.getElementById(divNum);
                    if (olddiv) {
                        d.removeChild(olddiv);
                    }
                    subtotal();
                }

                // Subtotal calculation
                function subtotal() {
                    var elem = document.getElementsByClassName('qty');
                    var price = document.getElementsByClassName('cprice');
                    var subto = document.getElementsByClassName('subtotal');
                    var sumamt = 0;
                    var sumqty = 0;

                    for (var i = 0; i < elem.length; i++) {
                        var pVal = parseFloat(price[i].value) || 0;
                        var qVal = parseInt(elem[i].value) || 0;

                        if (qVal > 0 || pVal > 0) {
                            var rowTotal = pVal * qVal;
                            subto[i].value = rowTotal.toFixed(2);
                            sumamt += rowTotal;
                            sumqty += qVal;
                        } else {
                            subto[i].value = "0.00";
                        }
                    }

                    document.getElementById('totalamt').value = sumamt.toFixed(2);
                    document.getElementById('totalqty').value = sumqty;
                    calcAmt();
                }

                // Calculate Net Payable Amount
                function calcAmt() {
                    var amt = parseFloat(document.getElementById('totalamt').value) || 0;
                    var type = document.getElementsByClassName('dis');
                    var dis = parseFloat(document.getElementById('per').value) || 0;
                    var payamt = amt;

                    if (type[0] && type[0].checked) {
                        var disamt = (amt * dis) / 100;
                        payamt = Math.round(amt - disamt);
                        document.getElementById('distype').value = "percentage";
                    } else {
                        payamt = Math.round(amt - dis);
                        document.getElementById('distype').value = "Rupees";
                    }

                    if (payamt < 0) payamt = 0;
                    document.getElementById('payamt').value = payamt.toFixed(2);
                }

                // Item check autocompletion
                function checkUsername(keyEvent1) {
                    keyEvent1 = (keyEvent1) ? keyEvent1 : window.event;
                    var input = (keyEvent1.target) ? keyEvent1.target : keyEvent1.srcElement;
                    if (keyEvent1.type == "keyup") {
                        var targetDiv = document.getElementById("targetDiv1");
                        targetDiv.innerHTML = "";
                        var targetDiv1 = document.getElementById("restul");
                        targetDiv1.innerHTML = "";
                        if (input.value && input.value.trim().length > 1) {
                            getData("itemsearch.php?qu=" + encodeURIComponent(input.value.trim()));
                            getDataitem("search.php?searchdata=" + encodeURIComponent(input.value.trim()));
                        }
                    }
                }

                function getData(dataSource) {
                    $.ajax({
                        url: dataSource,
                        type: 'GET',
                        success: function(resp) {
                            var t = document.getElementById("targetDiv1");
                            if (!t) return;
                            if (resp.trim() === "taken") {
                                t.innerHTML = '<div class="alert alert-info py-1 px-2 my-1" style="font-size:12px;"><i class="fa fa-info-circle me-1"></i> This item name already exists in inventory. Existing details will be applied.</div>';
                            } else {
                                t.innerHTML = '<div class="alert alert-secondary py-1 px-2 my-1" style="font-size:12px;"><i class="fa fa-sparkles me-1"></i> New item detected. Sequential code will be created.</div>';
                            }
                        }
                    });
                }

                function getDataitem(dataSource) {
                    $.ajax({
                        url: dataSource,
                        type: 'GET',
                        success: function(resp) {
                            var r = document.getElementById("restul");
                            if (r) r.innerHTML = resp;
                        }
                    });
                }

                function item_num1(id) {
                    try {
                        var thenum = id.replace(/^\D+/g, '');
                        var elmnt = document.getElementById("item_no" + thenum);
                        var val = document.getElementById("textField" + thenum).value;
                        if (val && val.trim() !== "") {
                            $.ajax({
                                type: "POST",
                                url: "getmaxitemnumberdb.php",
                                data: { nm: val.trim() },
                                dataType: "json",
                                cache: false,
                                success: function(msg) {
                                    var jsdr = msg;
                                    if (typeof jsdr === 'string') {
                                        var start = jsdr.indexOf('{');
                                        var end = jsdr.lastIndexOf('}');
                                        if (start !== -1 && end !== -1) {
                                            try {
                                                jsdr = JSON.parse(jsdr.substring(start, end + 1));
                                            } catch (e) {
                                                console.error("JSON parse error:", e);
                                                return;
                                            }
                                        }
                                    }
                                    if (jsdr && jsdr["numrows"] && jsdr["numrows"] != "0") {
                                        elmnt.value = jsdr["item_num"] || '';
                                        if (document.getElementById("item_cat" + thenum)) document.getElementById("item_cat" + thenum).value = jsdr["category"] || '0';
                                        if (document.getElementById("cprice" + thenum)) document.getElementById("cprice" + thenum).value = jsdr["costprice"] || '0.00';
                                        if (document.getElementById("uprice" + thenum)) document.getElementById("uprice" + thenum).value = jsdr["unitprice"] || '0.00';
                                        if (document.getElementById("qty" + thenum)) document.getElementById("qty" + thenum).value = jsdr["qty"] || 1;
                                        subtotal();
                                    } else if (jsdr && jsdr["item_num"]) {
                                        elmnt.value = jsdr["item_num"];
                                        item_num1xx(elmnt, jsdr["item_num"]);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error("AJAX Error in item_num1:", status, error);
                                }
                            });
                        }
                    } catch (ex) {
                        console.error("item_num1 exception:", ex);
                    }
                }

                function item_num1xx(id, itmn) {
                    var itm = document.getElementById('myval').value;
                    if (!itm) {
                        itm = document.getElementById('myval').value = itmn;
                    }

                    var f = itm.substring(0, 1);
                    var s = itm.substring(1, 2);
                    var t = itm.substring(2, 3);
                    var u = itm.length > 3 ? itm.substring(3, 4) : '';

                    if (u === '') {
                        if (t == 'Z') {
                            t = 'A';
                            if (s == 'Z') {
                                s = 'A';
                                var fc = f.charCodeAt(0);
                                if (fc < 90) f = String.fromCharCode(fc + 1);
                                else f = 'A';
                            } else {
                                s = String.fromCharCode(s.charCodeAt(0) + 1);
                            }
                        } else {
                            t = String.fromCharCode(t.charCodeAt(0) + 1);
                        }
                        u = 'A';
                    } else {
                        var uc = u.charCodeAt(0);
                        if (uc < 90) {
                            u = String.fromCharCode(uc + 1);
                        } else {
                            u = 'A';
                            if (t == 'Z') {
                                t = 'A';
                                if (s == 'Z') {
                                    s = 'A';
                                    var fc = f.charCodeAt(0);
                                    if (fc < 90) f = String.fromCharCode(fc + 1);
                                    else f = 'A';
                                } else {
                                    s = String.fromCharCode(s.charCodeAt(0) + 1);
                                }
                            } else {
                                t = String.fromCharCode(t.charCodeAt(0) + 1);
                            }
                        }
                    }

                    var item_inff = "" + f + s + t + u;
                    id.value = item_inff;
                    document.getElementById('myval').value = item_inff;
                }

                // Number validator
                function isNumberKey(evt) {
                    var charCode = (evt.which) ? evt.which : event.keyCode;
                    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) return false;
                    return true;
                }

                // Form validation & submission
                function details() {
                    var bill = document.getElementById('bill_id').value.trim();
                    var supp = document.getElementById('supp_id').value;
                    var bill_date = document.getElementById('bill_date').value;

                    if (!bill) {
                        Swal.fire({ icon: 'warning', title: 'Missing Bill Number', text: 'Please enter the Supplier Bill / Invoice Number.', confirmButtonColor: '#0f172a' });
                        document.getElementById('bill_id').focus();
                        return false;
                    }
                    if (supp == "0" || !supp) {
                        Swal.fire({ icon: 'warning', title: 'Supplier Required', text: 'Please select a Supplier from the dropdown.', confirmButtonColor: '#0f172a' });
                        document.getElementById('supp_id').focus();
                        return false;
                    }
                    if (!bill_date) {
                        Swal.fire({ icon: 'warning', title: 'Bill Date Required', text: 'Please select the Bill Date.', confirmButtonColor: '#0f172a' });
                        document.getElementById('bill_date').focus();
                        return false;
                    }

                    var elem = document.getElementsByClassName('qty');
                    var item_id = document.getElementsByClassName('item_id');
                    var cat = document.getElementsByClassName('item_cat');
                    var price = document.getElementsByClassName('cprice');
                    var uprice = document.getElementsByClassName('uprice');
                    var validRows = 0;

                    for (var i = 0; i < elem.length; i++) {
                        var nameVal = item_id[i] ? item_id[i].value.trim() : '';
                        if (nameVal !== '') {
                            validRows++;
                            if (cat[i] && cat[i].value == "0") {
                                Swal.fire({ icon: 'warning', title: 'Category Required', text: 'Please select a category for item on Row #' + (i + 1), confirmButtonColor: '#0f172a' });
                                cat[i].focus();
                                return false;
                            }
                            if (!price[i] || parseFloat(price[i].value) <= 0) {
                                Swal.fire({ icon: 'warning', title: 'Cost Price Required', text: 'Please enter a valid cost price on Row #' + (i + 1), confirmButtonColor: '#0f172a' });
                                price[i].focus();
                                return false;
                            }
                            if (!elem[i] || parseInt(elem[i].value) <= 0) {
                                Swal.fire({ icon: 'warning', title: 'Quantity Required', text: 'Please enter a valid quantity on Row #' + (i + 1), confirmButtonColor: '#0f172a' });
                                elem[i].focus();
                                return false;
                            }
                        }
                    }

                    if (validRows === 0) {
                        Swal.fire({ icon: 'warning', title: 'No Line Items', text: 'Please fill in at least one item before submitting the purchase bill.', confirmButtonColor: '#0f172a' });
                        return false;
                    }

                    return true;
                }

                // AJAX submission handler
                $(document).ready(function() {
                    // Populate initial 4 rows
                    for (var a = 0; a < 4; a++) {
                        showrow();
                    }

                    $('#purchse').on('submit', function(e) {
                        if (!details()) {
                            e.preventDefault();
                            return;
                        }

                        e.preventDefault();
                        var $btn = $('#btnSubmitPurchase');
                        var origHtml = $btn.html();
                        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing Bill...');

                        $.ajax({
                            url: 'processPurchasetest.php',
                            type: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            data: $(this).serialize(),
                            dataType: 'json',
                            success: function(res) {
                                $btn.prop('disabled', false).html(origHtml);
                                if (res && res.status === 'success') {
                                    Swal.fire({
                                        title: 'Purchase Bill Recorded!',
                                        text: res.message || 'The purchase entry and stock items have been saved successfully.',
                                        icon: 'success',
                                        showCancelButton: true,
                                        confirmButtonColor: '#0f172a',
                                        cancelButtonColor: '#64748b',
                                        confirmButtonText: '<i class="fa fa-list"></i> View All Bills',
                                        cancelButtonText: '<i class="fa fa-plus"></i> New Purchase Entry'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = 'view_bills.php';
                                        } else {
                                            window.location.reload();
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Save Failed',
                                        text: (res && res.message) ? res.message : 'Could not process purchase entry.',
                                        confirmButtonColor: '#0f172a'
                                    });
                                }
                            },
                            error: function() {
                                $btn.prop('disabled', false).html(origHtml);
                                // Fallback submit normally
                                document.getElementById('purchse').submit();
                            }
                        });
                    });
                });
            </script>

        </div>
    </div>
</div>

<?php
if (file_exists('../footer.php')) include_once('../footer.php');
CloseCon($con);
?>
</div>
</div>
</div>

<script src="../vendors/js/vendor.bundle.base.js"></script>
<script src="../vendors/js/vendor.bundle.addons.js"></script>
<script src="../js/off-canvas.js"></script>
<script src="../js/hoverable-collapse.js"></script>
<script src="../js/misc.js"></script>
<script src="../js/settings.js"></script>
<script src="../js/todolist.js"></script>

</body>
</html>