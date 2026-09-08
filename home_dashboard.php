<?php 
session_start(); 
include('top-header.php');
include('top-navbar.php');
?>
<!-- partial -->
<div class="container-fluid page-body-wrapper">
    <!-- partial:sidebar -->
    <?php include('navbar.php'); ?>
    
    <?php   
    $con = OpenSrishringarrCon();
    $currentdate = date("Y-m-d");
    $a = date('Y-m-d', strtotime($currentdate . ' + 1 days'));
    $b = date('Y-m-d', strtotime($currentdate . ' + 2 days'));
    $c = date('Y-m-d', strtotime($currentdate . ' + 10 days'));
    $day = date('d');

    // Return query
    if (isset($_POST['cmdret']) && !empty($_POST['return'])) {
        $selectedReturn = mysqli_real_escape_string($con, $_POST['return']);
        $r = mysqli_query($con, "SELECT * FROM `phppos_rent` WHERE (booking_status='NULL' OR booking_status='Picked') AND delivery='" . $selectedReturn . "' ORDER BY bill_id DESC");
    } else {
        $r = mysqli_query($con, "SELECT * FROM `phppos_rent` WHERE (booking_status='NULL' OR booking_status='Picked') ORDER BY bill_id DESC");
    }
    $num_returns = $r ? mysqli_num_rows($r) : 0;
    
    // Pick query
    $r1 = mysqli_query($con, "SELECT * FROM `phppos_rent` WHERE (booking_status='Booked') ORDER BY pick_date ASC");
    $num_pickups = $r1 ? mysqli_num_rows($r1) : 0;
    ?>
    
    <!-- partial -->
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem !important; background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
            
            <style>
                /* Shadcn Inspired Dashboard Styles - Consistent Slate Palette */
                :root {
                    --dash-border: #e2e8f0;
                    --dash-card-bg: #ffffff;
                    --dash-text: #0f172a;
                    --dash-text-muted: #64748b;
                    --dash-primary: #0f172a;
                    --dash-primary-hover: #1e293b;
                    --dash-neutral-surface: #f1f5f9;
                }

                /* Consistent Buttons */
                .btn-primary, .btn-primary:active, .btn-primary:focus {
                    background-color: #0f172a !important;
                    border-color: #0f172a !important;
                    color: #ffffff !important;
                    box-shadow: none !important;
                }
                .btn-primary:hover {
                    background-color: #1e293b !important;
                    border-color: #1e293b !important;
                    color: #ffffff !important;
                }

                .btn-outline-secondary {
                    background-color: #ffffff !important;
                    border: 1px solid var(--dash-border) !important;
                    color: #334155 !important;
                }
                .btn-outline-secondary:hover {
                    background-color: #f8fafc !important;
                    border-color: #cbd5e1 !important;
                    color: #0f172a !important;
                }

                .dash-header-section {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 12px;
                    margin-bottom: 24px;
                }

                .dash-title {
                    font-size: 22px;
                    font-weight: 700;
                    color: var(--dash-text);
                    margin: 0;
                    letter-spacing: -0.02em;
                }

                .dash-subtitle {
                    font-size: 13px;
                    color: var(--dash-text-muted);
                    margin: 3px 0 0 0;
                }

                .dash-date-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    background: #ffffff;
                    border: 1px solid var(--dash-border);
                    padding: 6px 14px;
                    border-radius: 8px;
                    font-size: 12.5px;
                    font-weight: 500;
                    color: #334155;
                    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
                }

                .live-dot {
                    width: 7px;
                    height: 7px;
                    border-radius: 50%;
                    background: #10b981;
                    display: inline-block;
                }

                /* Metric Cards - Consistent Neutral Design */
                .dash-metric-card {
                    background: var(--dash-card-bg);
                    border: 1px solid var(--dash-border);
                    border-radius: 10px;
                    padding: 16px 18px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    height: 100%;
                }

                .dash-metric-card:hover {
                    border-color: #cbd5e1;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                }

                .dash-metric-label {
                    font-size: 11.5px;
                    font-weight: 600;
                    color: var(--dash-text-muted);
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    margin-bottom: 6px;
                }

                .dash-metric-val {
                    font-size: 26px;
                    font-weight: 700;
                    color: var(--dash-text);
                    line-height: 1;
                    margin-bottom: 4px;
                }

                .dash-metric-desc {
                    font-size: 12px;
                    color: var(--dash-text-muted);
                }

                .dash-metric-icon {
                    width: 38px;
                    height: 38px;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 15px;
                    flex-shrink: 0;
                    background: var(--dash-neutral-surface);
                    color: #475569;
                    border: 1px solid var(--dash-border);
                }

                .dash-metric-icon.returns,
                .dash-metric-icon.pickups,
                .dash-metric-icon.filter {
                    background: var(--dash-neutral-surface);
                    color: #475569;
                }

                /* Filter Toolbar Card */
                .dash-filter-card {
                    background: #ffffff;
                    border: 1px solid var(--dash-border);
                    border-radius: 10px;
                    padding: 14px 18px;
                    margin-bottom: 24px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
                }

                /* Data Cards & Tables */
                .dash-table-card {
                    background: #ffffff;
                    border: 1px solid var(--dash-border);
                    border-radius: 10px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 24px;
                    overflow: hidden;
                }

                .dash-table-header {
                    padding: 14px 20px;
                    border-bottom: 1px solid var(--dash-border);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    background: #ffffff;
                }

                .dash-table-title {
                    font-size: 14px;
                    font-weight: 600;
                    color: var(--dash-text);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .dash-table-title i {
                    color: #64748b;
                    font-size: 14px;
                }

                .dash-table-badge {
                    font-size: 11.5px;
                    font-weight: 600;
                    padding: 3px 10px;
                    border-radius: 9999px;
                    background: var(--dash-neutral-surface);
                    color: var(--dash-text);
                    border: 1px solid var(--dash-border);
                }

                .dash-table-badge.returns,
                .dash-table-badge.pickups {
                    background: var(--dash-neutral-surface);
                    color: var(--dash-text);
                }

                /* Modern Table Cleanliness */
                .dash-table-card .table {
                    margin-bottom: 0 !important;
                    font-size: 13px !important;
                }

                .dash-table-card .table thead th {
                    background: #f8fafc !important;
                    color: #475569 !important;
                    font-size: 11.5px !important;
                    font-weight: 700 !important;
                    text-transform: uppercase !important;
                    letter-spacing: 0.04em !important;
                    border-top: none !important;
                    border-bottom: 1px solid var(--dash-border) !important;
                    padding: 10px 14px !important;
                    white-space: nowrap !important;
                }

                .dash-table-card .table tbody td {
                    padding: 10px 14px !important;
                    vertical-align: middle !important;
                    border-top: 1px solid #f1f5f9 !important;
                    color: #1e293b !important;
                }

                .dash-table-card .table tbody tr:hover {
                    background-color: #f8fafc !important;
                }

                .bill-badge-link {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    background: #f1f5f9;
                    color: #2563eb !important;
                    padding: 4px 9px;
                    border-radius: 6px;
                    font-weight: 600;
                    font-size: 12px;
                    text-decoration: none !important;
                    border: 1px solid #e2e8f0;
                    transition: all 0.12s ease;
                }

                .bill-badge-link:hover {
                    background: #e0e7ff;
                    color: #1d4ed8 !important;
                    border-color: #c7d2fe;
                }

                .item-chip {
                    display: inline-block;
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    padding: 2px 7px;
                    border-radius: 4px;
                    font-size: 11.5px;
                    color: #334155;
                    margin: 1px 3px 2px 0;
                    white-space: nowrap;
                }

                .item-chip strong {
                    color: #0f172a;
                }

                .btn-picked-action {
                    padding: 4px 12px !important;
                    font-size: 12px !important;
                    font-weight: 600 !important;
                    border-radius: 6px !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    gap: 5px !important;
                    text-decoration: none !important;
                }

                /* DataTables input restyling */
                .dataTables_wrapper .dataTables_filter input {
                    border: 1px solid var(--dash-border) !important;
                    border-radius: 6px !important;
                    padding: 4px 10px !important;
                    font-size: 12.5px !important;
                }
                .dataTables_wrapper .dataTables_filter input:focus {
                    border-color: var(--dash-primary) !important;
                    outline: none;
                }
                .dataTables_wrapper .dataTables_length select {
                    border: 1px solid var(--dash-border) !important;
                    border-radius: 6px !important;
                    padding: 4px 8px !important;
                    font-size: 12.5px !important;
                }
            </style>

            <!-- Dashboard Page Header -->
            <div class="dash-header-section">
                <div>
                    <h1 class="dash-title">Operations Dashboard</h1>
                    <p class="dash-subtitle">Rental return alerts, pickup schedules, and delivery logistics.</p>
                </div>
                <div class="dash-date-badge">
                    <span class="live-dot" title="Live POS Database Connected"></span>
                    <i class="far fa-calendar-alt text-muted"></i>
                    <span><?php echo date('l, d M Y'); ?></span>
                </div>
            </div>

            <!-- KPI Metric Summary Row -->
            <div class="row g-3 mb-3">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="dash-metric-card">
                        <div>
                            <div class="dash-metric-label">Return Date Alerts</div>
                            <div class="dash-metric-val"><?php echo $num_returns; ?></div>
                            <div class="dash-metric-desc">Rentals awaiting return</div>
                        </div>
                        <div class="dash-metric-icon returns">
                            <i class="fas fa-undo-alt"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="dash-metric-card">
                        <div>
                            <div class="dash-metric-label">Pickup Date Alerts</div>
                            <div class="dash-metric-val"><?php echo $num_pickups; ?></div>
                            <div class="dash-metric-desc">Booked orders ready for dispatch</div>
                        </div>
                        <div class="dash-metric-icon pickups">
                            <i class="fas fa-box-open"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12 col-sm-12">
                    <div class="dash-metric-card">
                        <div>
                            <div class="dash-metric-label">Active Channel Filter</div>
                            <div class="dash-metric-val" style="font-size: 19px; line-height: 1.4;">
                                <?php echo !empty($_POST['return']) ? htmlspecialchars($_POST['return']) : 'All Deliveries'; ?>
                            </div>
                            <div class="dash-metric-desc">Filter by delivery route</div>
                        </div>
                        <div class="dash-metric-icon filter">
                            <i class="fas fa-filter"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Filter Bar -->
            <div class="dash-filter-card">
                <form id="forms" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-5 col-sm-12">
                            <div class="d-flex align-items-center gap-2">
                                <label class="m-0 font-weight-bold text-dark text-nowrap" style="font-size: 13px;">
                                    <i class="fas fa-truck text-muted mr-1"></i> Select Delivery Channel:
                                </label>
                                <select class="form-control form-control-sm" name="return" id="return" style="height: 32px; border-radius: 6px; font-size: 13px; border: 1px solid var(--dash-border);">
                                    <option value="">-- All Deliveries --</option>
                                    <?php
                                    $qry = mysqli_query($con, "SELECT DISTINCT(delivery) FROM phppos_rent ORDER BY delivery ASC");
                                    if ($qry && mysqli_num_rows($qry) > 0) {
                                        while ($qrro = mysqli_fetch_array($qry)) {
                                            if (!empty($qrro[0])) {
                                                $selected = (isset($_POST['return']) && $_POST['return'] == $qrro[0]) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($qrro[0]) . '" ' . $selected . '>' . htmlspecialchars($qrro[0]) . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-7 col-sm-12 d-flex gap-2">
                            <button class="btn btn-sm btn-primary" type="submit" name="cmdret" style="height: 32px; border-radius: 6px; font-weight: 600; padding: 0 14px;">
                                <i class="fas fa-filter mr-1"></i> Apply Filter
                            </button>
                            <?php if (isset($_POST['cmdret']) && !empty($_POST['return'])): ?>
                                <a href="home_dashboard.php" class="btn btn-sm btn-outline-secondary" style="height: 32px; border-radius: 6px; font-weight: 600; padding: 5px 12px;">
                                    <i class="fas fa-times mr-1"></i> Clear Filter
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table 1: Alert : Return Date -->
            <div class="dash-table-card">
                <div class="dash-table-header">
                    <h4 class="dash-table-title">
                        <i class="far fa-clock"></i>
                        <span>Alert: Return Date</span>
                    </h4>
                    <span class="dash-table-badge returns">
                        <?php echo $num_returns; ?> Pending Return<?php echo $num_returns === 1 ? '' : 's'; ?>
                    </span>
                </div>
                <div class="p-3">
                    <div class="table-responsive">
                        <table id="order-listing" class="table">
                            <thead>
                                <tr>
                                    <th>Bill No.</th>
                                    <th>Return By</th>
                                    <th>Return Date</th>
                                    <th>Customer Name</th>
                                    <th>Items & Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($r && mysqli_num_rows($r) > 0) {
                                    while ($r2 = mysqli_fetch_array($r)) {
                                        $new_bill_number = $r2['new_bill_number'];
                                        $cust = $r2[1];
                                        
                                        $customer = mysqli_query($con, "SELECT * FROM `phppos_people` WHERE person_id='$cust'");
                                        $cust1 = $customer ? mysqli_fetch_row($customer) : null;
                                        $custName = $cust1 ? ($cust1[0] . " " . $cust1[1]) : '-';
                                        
                                        $bill1 = mysqli_query($con, "SELECT * FROM `order_detail` WHERE bill_id='$r2[0]' AND is_status = 0");
                                        $bill11 = $bill1 ? mysqli_fetch_row($bill1) : null;
                                        
                                        if (!empty($bill11[0])) {
                                            $billAll = mysqli_query($con, "SELECT * FROM `order_detail` WHERE bill_id='$r2[0]'");
                                            $returnDateStr = (isset($r2[12]) && $r2[12] != '0000-00-00') ? date('d/m/Y', strtotime($r2[12])) . " " . ($r2[22] ?? '') : '-';
                                            $displayBillNo = !empty($new_bill_number) ? $new_bill_number : $bill11[0];
                                ?>
                                    <tr>
                                        <td>
                                            <a class="bill-badge-link" href="reports/rent_report_detail.php?id=<?php echo htmlspecialchars($bill11[0]); ?>" target="_blank" title="View Bill Details">
                                                <span>#<?php echo htmlspecialchars($displayBillNo); ?></span>
                                                <i class="fas fa-external-link-alt" style="font-size: 10px;"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($r2[7] ?? '-'); ?></span>
                                        </td>
                                        <td>
                                            <span class="text-nowrap"><i class="far fa-calendar-alt text-muted mr-1"></i><?php echo htmlspecialchars($returnDateStr); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($custName); ?></strong>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($billAll) {
                                                while ($bRow = mysqli_fetch_row($billAll)) {
                                                    $itemCode = $bRow[1];
                                                    $itemQty = $bRow[9] ?? 1;
                                                    $itemQry = mysqli_query($con, "SELECT name FROM `phppos_items` WHERE name='$itemCode'");
                                                    $itemRow = $itemQry ? mysqli_fetch_row($itemQry) : null;
                                                    $itemDisplayName = $itemRow ? $itemRow[0] : $itemCode;
                                                    echo '<span class="item-chip"><i class="fas fa-tag text-muted mr-1"></i>' . htmlspecialchars($itemDisplayName) . ' <strong>× ' . htmlspecialchars($itemQty) . '</strong></span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php 
                                        }
                                    }
                                } 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Table 2: Alert : Pick Date -->
            <div class="dash-table-card">
                <div class="dash-table-header">
                    <h4 class="dash-table-title">
                        <i class="far fa-calendar-check"></i>
                        <span>Alert: Pick Date</span>
                    </h4>
                    <span class="dash-table-badge pickups">
                        <?php echo $num_pickups; ?> Pending Pickup<?php echo $num_pickups === 1 ? '' : 's'; ?>
                    </span>
                </div>
                <div class="p-3">
                    <div class="table-responsive">
                        <table class="table" id="order-listing2">
                            <thead>
                                <tr>
                                    <th>Bill No.</th>
                                    <th>Pick By</th>
                                    <th>Pick Date</th>
                                    <th>Customer Name</th>
                                    <th>Items & Qty</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($r1 && mysqli_num_rows($r1) > 0) {
                                    while ($r3 = mysqli_fetch_array($r1)) {
                                        $new_bill_number = $r3['new_bill_number'];
                                        $cust = $r3[1];
                                        
                                        $customer = mysqli_query($con, "SELECT * FROM `phppos_people` WHERE person_id='$cust'");
                                        $cust1 = $customer ? mysqli_fetch_row($customer) : null;
                                        $custName = $cust1 ? ($cust1[0] . " " . $cust1[1]) : '-';
                                        
                                        $billPick = mysqli_query($con, "SELECT * FROM `order_detail` WHERE bill_id='$r3[0]'");
                                        $billPick1 = mysqli_query($con, "SELECT * FROM `order_detail` WHERE bill_id='$r3[0]'");
                                        $bill11 = $billPick1 ? mysqli_fetch_row($billPick1) : null;
                                        $billId = $bill11 ? $bill11[0] : $r3[0];
                                        
                                        $pickDateStr = (isset($r3[11]) && $r3[11] != '0000-00-00') ? date('d/m/Y', strtotime($r3[11])) : '-';
                                        $displayBillNo = !empty($new_bill_number) ? $new_bill_number : $billId;

                                        // Check inventory availability
                                        $p = 0;
                                        $bill2 = mysqli_query($con, "SELECT * FROM `order_detail` WHERE bill_id='$r3[0]'");
                                        if ($bill2) {
                                            while ($bill12 = mysqli_fetch_row($bill2)) {
                                                $qt = mysqli_query($con, "SELECT quantity FROM phppos_items WHERE name='$bill12[1]'");
                                                if ($qt && $qt1 = mysqli_fetch_row($qt)) {
                                                    if ($qt1[0] <= 0) {
                                                        $p = 1;
                                                    }
                                                }
                                            }
                                        }
                                ?>
                                    <tr>
                                        <td>
                                            <a class="bill-badge-link" href="reports/rent_report_detail.php?id=<?php echo htmlspecialchars($billId); ?>" target="_blank" title="View Bill Details">
                                                <span>#<?php echo htmlspecialchars($displayBillNo); ?></span>
                                                <i class="fas fa-external-link-alt" style="font-size: 10px;"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($r3[6] ?? '-'); ?></span>
                                        </td>
                                        <td>
                                            <span class="text-nowrap"><i class="far fa-calendar-alt text-muted mr-1"></i><?php echo htmlspecialchars($pickDateStr); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($custName); ?></strong>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($billPick) {
                                                while ($bRow = mysqli_fetch_row($billPick)) {
                                                    $itemCode = $bRow[1];
                                                    $itemQty = $bRow[9] ?? 1;
                                                    $itemQry = mysqli_query($con, "SELECT name FROM `phppos_items` WHERE name='$itemCode'");
                                                    $itemRow = $itemQry ? mysqli_fetch_row($itemQry) : null;
                                                    $itemDisplayName = $itemRow ? $itemRow[0] : $itemCode;
                                                    echo '<span class="item-chip"><i class="fas fa-tag text-muted mr-1"></i>' . htmlspecialchars($itemDisplayName) . ' <strong>× ' . htmlspecialchars($itemQty) . '</strong></span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($p == 1): ?>
                                                <button class="btn btn-secondary btn-picked-action" disabled title="Out of Stock">
                                                    <i class="fas fa-ban"></i> Picked
                                                </button>
                                            <?php else: ?>
                                                <a class="btn btn-primary btn-picked-action" href="reports/deliver.php?bid=<?php echo htmlspecialchars($billId); ?>">
                                                    <i class="fas fa-check"></i> Mark Picked
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    }
                                }
                                if ($con) {
                                    CloseCon($con);
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
        <!-- content-wrapper ends -->
        <?php include('footer.php'); ?>
    </div>
    <!-- main-panel ends -->
</div>
<!-- page-body-wrapper ends -->
</div>
<!-- container-scroller -->

<!-- plugins:js -->
<script src="vendors/js/vendor.bundle.base.js"></script>
<script src="vendors/js/vendor.bundle.addons.js"></script>
<!-- inject:js -->
<script src="js/off-canvas.js"></script>
<script src="js/hoverable-collapse.js"></script>
<script src="js/misc.js"></script>
<script src="js/settings.js"></script>
<script src="js/todolist.js"></script>
<!-- DataTables JS -->
<script src="js/data-table.js"></script>
<script src="js/data-table2.js"></script>
<script src="js/select2.js"></script>
            
</body>
</html>