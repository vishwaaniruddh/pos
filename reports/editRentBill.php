<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . '/../base_url.php')) {
    include_once __DIR__ . '/../base_url.php';
}
include_once(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

include_once(__DIR__ . '/../top-header.php');
include_once(__DIR__ . '/../top-navbar.php');
?>

<div class="container-fluid page-body-wrapper">
    <?php
    include_once(__DIR__ . '/../navbar.php');
    if (!isset($con) || !$con) {
        $con = OpenSrishringarrCon();
    }

    // Pagination variables
    $allowed_limits = [25, 50, 100, 200];
    $limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowed_limits) ? (int)$_GET['limit'] : 50;
    $page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Search and filter variables
    $searchKeyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : (isset($_GET['bill_id']) ? trim($_GET['bill_id']) : '');
    $searchStatus  = isset($_GET['status']) ? trim($_GET['status']) : '';
    $searchStartDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
    $searchEndDate   = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

    // Overall KPI Stats
    $kpi_res = mysqli_query($con, "SELECT booking_status, COUNT(*) as cnt FROM phppos_rent GROUP BY booking_status");
    $kpi_counts = ['total' => 0, 'Booked' => 0, 'Picked' => 0, 'Returned' => 0];
    if ($kpi_res) {
        while ($kr = mysqli_fetch_assoc($kpi_res)) {
            $st = $kr['booking_status'];
            $cnt = (int)$kr['cnt'];
            $kpi_counts['total'] += $cnt;
            if (isset($kpi_counts[$st])) {
                $kpi_counts[$st] += $cnt;
            }
        }
    }

    // Base query with item count subquery
    $whereClauses = ["1=1"];
    $paramTypes = "";
    $paramValues = [];

    // Intelligent multi-field search (Bill ID, Bill Number, Customer Name, Mobile Phone)
    if ($searchKeyword !== '') {
        $searchLike = '%' . $searchKeyword . '%';
        $whereClauses[] = "(
            r.bill_id = ? 
            OR r.new_bill_number LIKE ? 
            OR p.first_name LIKE ? 
            OR p.last_name LIKE ? 
            OR CONCAT(p.first_name, ' ', p.last_name) LIKE ? 
            OR p.phone_number LIKE ?
            OR r.cust_name LIKE ?
            OR r.person_Name LIKE ?
        )";
        $paramTypes .= "ssssssss";
        $paramValues[] = $searchKeyword;
        $paramValues[] = $searchLike;
        $paramValues[] = $searchLike;
        $paramValues[] = $searchLike;
        $paramValues[] = $searchLike;
        $paramValues[] = $searchLike;
        $paramValues[] = $searchLike;
        $paramValues[] = $searchLike;
    }

    // Booking Status filter
    if ($searchStatus !== '') {
        $whereClauses[] = "r.booking_status = ?";
        $paramTypes .= "s";
        $paramValues[] = $searchStatus;
    }

    // Date range filter: independent Start Date & End Date guards
    if ($searchStartDate !== '' && $searchEndDate !== '') {
        $whereClauses[] = "r.bill_date BETWEEN ? AND ?";
        $paramTypes .= "ss";
        $paramValues[] = $searchStartDate;
        $paramValues[] = $searchEndDate;
    } elseif ($searchStartDate !== '') {
        $whereClauses[] = "r.bill_date >= ?";
        $paramTypes .= "s";
        $paramValues[] = $searchStartDate;
    } elseif ($searchEndDate !== '') {
        $whereClauses[] = "r.bill_date <= ?";
        $paramTypes .= "s";
        $paramValues[] = $searchEndDate;
    }

    $whereSQL = implode(" AND ", $whereClauses);

    // Count Total Matching Records for Pagination
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM phppos_rent r 
        LEFT JOIN phppos_people p ON r.cust_id = p.person_id 
        WHERE $whereSQL
    ";
    $countStmt = $con->prepare($countQuery);
    if (!empty($paramValues)) {
        $countStmt->bind_param($paramTypes, ...$paramValues);
    }
    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $totalRecords = $countRes ? (int)$countRes->fetch_assoc()['total'] : 0;
    $countStmt->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $limit;
    }

    // Fetch Paginated Records
    $dataQuery = "
        SELECT 
            r.bill_id, 
            r.cust_id, 
            r.bill_date, 
            r.new_bill_number,
            r.rent_amount,
            r.amount,
            r.bal_amount,
            r.booking_status,
            r.pstatus,
            r.pick_date,
            r.delivery_date,
            p.first_name, 
            p.last_name,
            p.phone_number,
            r.cust_name,
            r.person_Name,
            (SELECT COUNT(*) FROM order_detail od WHERE od.bill_id = r.bill_id) as item_count,
            (SELECT IFNULL(SUM(ra.amount), 0) FROM rent_amount ra WHERE ra.bill_id = r.bill_id) as ra_paid
        FROM phppos_rent r
        LEFT JOIN phppos_people p ON r.cust_id = p.person_id
        WHERE $whereSQL
        ORDER BY r.bill_id DESC 
        LIMIT ? OFFSET ?
    ";

    $dataParamTypes = $paramTypes . "ii";
    $dataParamValues = array_merge($paramValues, [$limit, $offset]);

    $dataStmt = $con->prepare($dataQuery);
    if (!empty($dataParamValues)) {
        $dataStmt->bind_param($dataParamTypes, ...$dataParamValues);
    }
    $dataStmt->execute();
    $result = $dataStmt->get_result();
    ?>

    <div class="main-panel">
        <div class="content-wrapper">
            
            <style>
                :root {
                    --color-slate-900: #0f172a;
                    --color-slate-800: #1e293b;
                    --color-slate-700: #334155;
                    --color-slate-600: #475569;
                    --color-slate-500: #64748b;
                    --color-slate-400: #94a3b8;
                    --color-slate-200: #e2e8f0;
                    --color-slate-100: #f1f5f9;
                    --color-slate-50:  #f8fafc;
                }
                body, .content-wrapper {
                    background-color: var(--color-slate-50) !important;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }
                .page-body-wrapper {
                    padding-top: 58px !important;
                }
                #sidebar {
                    position: fixed !important;
                    top: 58px;
                    left: 0;
                    bottom: 0;
                    height: calc(100vh - 58px);
                }
                .main-panel {
                    margin-left: 240px;
                }
                @media (max-width: 991px) {
                    .main-panel {
                        margin-left: 0;
                    }
                }
                .content-wrapper {
                    padding: 24px 28px !important;
                }
                .sh-card {
                    background: #ffffff;
                    border: 1px solid var(--color-slate-200);
                    border-radius: 10px;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
                    margin-bottom: 24px;
                }
                .sh-card-header {
                    padding: 16px 20px;
                    border-bottom: 1px solid var(--color-slate-200);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 12px;
                }
                .sh-card-body {
                    padding: 20px;
                }
                .btn-sh-primary {
                    background-color: var(--color-slate-900) !important;
                    color: #ffffff !important;
                    border: 1px solid var(--color-slate-900) !important;
                    border-radius: 6px;
                    padding: 8px 16px;
                    font-weight: 500;
                    font-size: 13px;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    text-decoration: none;
                    transition: all 0.15s ease-in-out;
                    cursor: pointer;
                }
                .btn-sh-primary:hover {
                    background-color: var(--color-slate-800) !important;
                    color: #ffffff !important;
                }
                .btn-sh-outline {
                    background-color: #ffffff !important;
                    color: var(--color-slate-700) !important;
                    border: 1px solid var(--color-slate-200) !important;
                    border-radius: 6px;
                    padding: 8px 14px;
                    font-weight: 500;
                    font-size: 13px;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    text-decoration: none;
                    transition: all 0.15s ease-in-out;
                    cursor: pointer;
                }
                .btn-sh-outline:hover {
                    background-color: var(--color-slate-50) !important;
                    color: var(--color-slate-900) !important;
                    border-color: var(--color-slate-400) !important;
                }
                .btn-sh-danger {
                    background-color: #ffffff !important;
                    color: #dc2626 !important;
                    border: 1px solid #fecaca !important;
                    border-radius: 6px;
                    padding: 4px 10px;
                    font-size: 12px;
                    font-weight: 500;
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                }
                .btn-sh-danger:hover {
                    background-color: #fee2e2 !important;
                    border-color: #fca5a5 !important;
                }
                .sh-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    padding: 4px 10px;
                    border-radius: 9999px;
                    font-size: 12px;
                    font-weight: 500;
                    border: 1px solid var(--color-slate-200);
                    background-color: var(--color-slate-100);
                    color: var(--color-slate-700);
                }
                .sh-badge-dot {
                    width: 6px;
                    height: 6px;
                    border-radius: 50%;
                    background-color: var(--color-slate-500);
                }
                .sh-badge-booked {
                    background-color: #fefce8;
                    border-color: #fef08a;
                    color: #854d0e;
                }
                .sh-badge-booked .sh-badge-dot {
                    background-color: #ca8a04;
                }
                .sh-badge-picked {
                    background-color: #f0fdf4;
                    border-color: #bbf7d0;
                    color: #166534;
                }
                .sh-badge-picked .sh-badge-dot {
                    background-color: #16a34a;
                }
                .sh-badge-returned {
                    background-color: #f8fafc;
                    border-color: #e2e8f0;
                    color: #475569;
                }
                .sh-badge-returned .sh-badge-dot {
                    background-color: #94a3b8;
                }
                .kpi-card {
                    background: #ffffff;
                    border: 1px solid var(--color-slate-200);
                    border-radius: 10px;
                    padding: 16px 20px;
                    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }
                .kpi-label {
                    font-size: 13px;
                    color: var(--color-slate-500);
                    font-weight: 500;
                    margin-bottom: 4px;
                }
                .kpi-value {
                    font-size: 24px;
                    font-weight: 700;
                    color: var(--color-slate-900);
                    line-height: 1.1;
                }
                .kpi-icon-box {
                    width: 42px;
                    height: 42px;
                    border-radius: 8px;
                    background-color: var(--color-slate-100);
                    border: 1px solid var(--color-slate-200);
                    color: var(--color-slate-600);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 16px;
                }
                .sh-table {
                    width: 100%;
                    border-collapse: separate;
                    border-spacing: 0;
                    font-size: 13px;
                }
                .sh-table th {
                    background-color: #f8fafc;
                    color: var(--color-slate-700);
                    font-weight: 600;
                    padding: 12px 14px;
                    border-bottom: 1px solid var(--color-slate-200);
                    border-top: 1px solid var(--color-slate-200);
                    text-align: left;
                    white-space: nowrap;
                }
                .sh-table td {
                    padding: 12px 14px;
                    border-bottom: 1px solid var(--color-slate-200);
                    color: var(--color-slate-700);
                    vertical-align: middle;
                }
                .sh-table tr:hover td {
                    background-color: #f8fafc;
                }
                .sh-form-control {
                    height: 38px;
                    padding: 6px 12px;
                    font-size: 13px;
                    border: 1px solid var(--color-slate-200);
                    border-radius: 6px;
                    color: var(--color-slate-800);
                    background-color: #ffffff;
                    outline: none;
                    transition: border-color 0.15s ease-in-out;
                }
                .sh-form-control:focus {
                    border-color: var(--color-slate-900);
                }
                .sh-pagination {
                    display: flex;
                    align-items: center;
                    gap: 4px;
                    list-style: none;
                    margin: 0;
                    padding: 0;
                }
                .sh-pagination a, .sh-pagination span {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 32px;
                    height: 32px;
                    padding: 0 8px;
                    font-size: 13px;
                    border-radius: 6px;
                    border: 1px solid var(--color-slate-200);
                    background-color: #ffffff;
                    color: var(--color-slate-700);
                    text-decoration: none;
                    font-weight: 500;
                    transition: all 0.15s ease;
                }
                .sh-pagination a:hover {
                    background-color: var(--color-slate-100);
                    color: var(--color-slate-900);
                }
                .sh-pagination a.active {
                    background-color: var(--color-slate-900);
                    color: #ffffff;
                    border-color: var(--color-slate-900);
                }
                .sh-pagination a.disabled {
                    opacity: 0.45;
                    pointer-events: none;
                    cursor: not-allowed;
                }
            </style>

            <!-- Page Title Section -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="mb-1" style="font-size: 22px; font-weight: 700; color: var(--color-slate-900);">Rent Bill Management</h2>
                    <p class="text-muted mb-0" style="font-size: 13px;">Search, view, filter, edit, and manage all store rent invoices.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="/pos/home_dashboard.php" class="btn-sh-outline">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>

            <!-- Metric Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Total Bills</div>
                            <div class="kpi-value"><?php echo number_format($kpi_counts['total']); ?></div>
                        </div>
                        <div class="kpi-icon-box">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Booked</div>
                            <div class="kpi-value" style="color: #854d0e;"><?php echo number_format($kpi_counts['Booked']); ?></div>
                        </div>
                        <div class="kpi-icon-box" style="background-color: #fefce8; border-color: #fef08a; color: #854d0e;">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Picked</div>
                            <div class="kpi-value" style="color: #166534;"><?php echo number_format($kpi_counts['Picked']); ?></div>
                        </div>
                        <div class="kpi-icon-box" style="background-color: #f0fdf4; border-color: #bbf7d0; color: #166534;">
                            <i class="fas fa-box-check"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Returned</div>
                            <div class="kpi-value"><?php echo number_format($kpi_counts['Returned']); ?></div>
                        </div>
                        <div class="kpi-icon-box">
                            <i class="fas fa-undo"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Filter Card -->
            <div class="sh-card">
                <div class="sh-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-filter text-muted"></i>
                        <span style="font-weight: 600; color: var(--color-slate-800); font-size: 14px;">Search & Filters</span>
                        <?php if ($searchKeyword !== '' || $searchStatus !== '' || $searchStartDate !== '' || $searchEndDate !== ''): ?>
                            <span class="badge" style="background: var(--color-slate-100); color: var(--color-slate-700); border: 1px solid var(--color-slate-200);">Active Filters Applied</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($searchKeyword !== '' || $searchStatus !== '' || $searchStartDate !== '' || $searchEndDate !== ''): ?>
                        <a href="editRentBill.php" class="btn-sh-outline" style="padding: 4px 10px; font-size: 12px;">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
                <div class="sh-card-body">
                    <form method="GET" action="editRentBill.php" class="row g-2 align-items-end">
                        
                        <div class="col-12 col-md-4">
                            <label class="form-label text-muted" style="font-size: 12px; font-weight: 500; margin-bottom: 4px;">Search Bill No / Customer / Mobile</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-color: var(--color-slate-200);">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" name="keyword" class="form-control sh-form-control border-start-0" 
                                       placeholder="e.g. 61, SAK-26, Nitin, 98210..." 
                                       value="<?php echo htmlspecialchars($searchKeyword); ?>">
                            </div>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label text-muted" style="font-size: 12px; font-weight: 500; margin-bottom: 4px;">Status</label>
                            <select name="status" class="form-control sh-form-control">
                                <option value="">All Statuses</option>
                                <option value="Booked" <?php echo $searchStatus === 'Booked' ? 'selected' : ''; ?>>Booked</option>
                                <option value="Picked" <?php echo $searchStatus === 'Picked' ? 'selected' : ''; ?>>Picked</option>
                                <option value="Returned" <?php echo $searchStatus === 'Returned' ? 'selected' : ''; ?>>Returned</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label text-muted" style="font-size: 12px; font-weight: 500; margin-bottom: 4px;">From Date</label>
                            <input type="date" name="start_date" class="form-control sh-form-control" 
                                   value="<?php echo htmlspecialchars($searchStartDate); ?>">
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label text-muted" style="font-size: 12px; font-weight: 500; margin-bottom: 4px;">To Date</label>
                            <input type="date" name="end_date" class="form-control sh-form-control" 
                                   value="<?php echo htmlspecialchars($searchEndDate); ?>">
                        </div>

                        <div class="col-6 col-md-1">
                            <label class="form-label text-muted" style="font-size: 12px; font-weight: 500; margin-bottom: 4px;">Show</label>
                            <select name="limit" class="form-control sh-form-control">
                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                <option value="200" <?php echo $limit == 200 ? 'selected' : ''; ?>>200</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-1 d-flex gap-1">
                            <button type="submit" class="btn-sh-primary w-100 justify-content-center" style="height: 38px;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Bill Records Table Card -->
            <div class="sh-card">
                <div class="sh-card-header">
                    <div>
                        <span style="font-weight: 600; color: var(--color-slate-900); font-size: 15px;">Rent Bill Records</span>
                        <span class="text-muted ms-2" style="font-size: 13px;">
                            (Found <?php echo number_format($totalRecords); ?> <?php echo $totalRecords === 1 ? 'bill' : 'bills'; ?>)
                        </span>
                    </div>
                    <?php if ($totalRecords > 0): ?>
                        <div class="text-muted" style="font-size: 12px;">
                            Showing <?php echo number_format($offset + 1); ?> - <?php echo number_format(min($offset + $limit, $totalRecords)); ?> of <?php echo number_format($totalRecords); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="sh-card-body p-0">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="sh-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">#</th>
                                        <th>Bill Number</th>
                                        <th>Bill Date</th>
                                        <th>Customer Details</th>
                                        <th style="text-align: center;">Items</th>
                                        <th style="text-align: right;">Rent Amount</th>
                                        <th style="text-align: right;">Balance</th>
                                        <th style="text-align: center;">Status</th>
                                        <th style="text-align: right; width: 180px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $srno = $offset + 1;
                                    while ($row = $result->fetch_assoc()):
                                        $displayBillNumber = !empty($row['new_bill_number']) ? $row['new_bill_number'] : ('# ' . $row['bill_id']);
                                        $customerFullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                        if ($customerFullName === '') {
                                            $customerFullName = !empty($row['cust_name']) ? trim($row['cust_name']) : (!empty($row['person_Name']) ? trim($row['person_Name']) : 'Walk-in Customer');
                                        }
                                        $phone = !empty($row['phone_number']) ? trim($row['phone_number']) : '';
                                        $status = trim($row['booking_status'] ?? '');
                                        if ($status === '') {
                                            $status = 'Returned';
                                        }

                                        $badgeClass = 'sh-badge-returned';
                                        if (strcasecmp($status, 'Booked') === 0) {
                                            $badgeClass = 'sh-badge-booked';
                                        } elseif (strcasecmp($status, 'Picked') === 0) {
                                            $badgeClass = 'sh-badge-picked';
                                        }

                                        $rentAmount = !empty($row['rent_amount']) ? (float)$row['rent_amount'] : (!empty($row['amount']) ? (float)$row['amount'] : 0);
                                        $raPaid     = isset($row['ra_paid']) ? (float)$row['ra_paid'] : 0;
                                        $balCol     = !empty($row['bal_amount']) ? (float)$row['bal_amount'] : 0;

                                        // In phppos_rent, 'bal_amount' represents the amount paid. If payments exist in rent_amount, use that sum.
                                        $paidAmount = ($raPaid > 0) ? $raPaid : $balCol;
                                        if (strcasecmp($row['pstatus'] ?? '', 'Paid') === 0 && $paidAmount == 0 && $rentAmount > 0) {
                                            $paidAmount = $rentAmount;
                                        }

                                        // True remaining balance is Rent Amount minus Paid Amount
                                        $balAmount = max(0, $rentAmount - $paidAmount);
                                    ?>
                                        <tr id="bill-row-<?php echo $row['bill_id']; ?>">
                                            <td style="text-align: center; color: var(--color-slate-400); font-size: 12px;">
                                                <?php echo $srno; ?>
                                            </td>
                                            <td>
                                                <a href="./editrent123.php?bill_id=<?php echo urlencode($row['bill_id']); ?>" 
                                                   style="font-weight: 600; color: var(--color-slate-900); text-decoration: none;"
                                                   title="Click to Edit Bill">
                                                    <?php echo htmlspecialchars($displayBillNumber); ?>
                                                </a>
                                                <?php if (!empty($row['new_bill_number'])): ?>
                                                    <div style="font-size: 11px; color: var(--color-slate-400);">ID: <?php echo $row['bill_id']; ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="font-weight: 500;">
                                                    <?php echo ($row['bill_date'] && $row['bill_date'] !== '0000-00-00') ? date('d M Y', strtotime($row['bill_date'])) : '-'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600; color: var(--color-slate-800);">
                                                    <?php echo htmlspecialchars($customerFullName); ?>
                                                </div>
                                                <?php if ($phone !== ''): ?>
                                                    <div style="font-size: 12px; color: var(--color-slate-500);">
                                                        <i class="fas fa-phone-alt me-1" style="font-size: 10px;"></i>
                                                        <a href="tel:<?php echo htmlspecialchars($phone); ?>" style="color: inherit; text-decoration: none;">
                                                            <?php echo htmlspecialchars($phone); ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="sh-badge">
                                                    <?php echo (int)$row['item_count']; ?> <?php echo (int)$row['item_count'] === 1 ? 'item' : 'items'; ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right; font-weight: 600; color: var(--color-slate-900);">
                                                ₹<?php echo number_format($rentAmount, 2); ?>
                                            </td>
                                            <td style="text-align: right; font-weight: 600;">
                                                <?php if ($balAmount > 0): ?>
                                                    <span style="color: #b91c1c;">₹<?php echo number_format($balAmount, 2); ?></span>
                                                <?php else: ?>
                                                    <span style="color: var(--color-slate-400);">₹0.00</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="sh-badge <?php echo $badgeClass; ?>">
                                                    <span class="sh-badge-dot"></span>
                                                    <?php echo htmlspecialchars(ucfirst($status)); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <a href="./editrent123.php?bill_id=<?php echo urlencode($row['bill_id']); ?>" 
                                                       class="btn-sh-primary" 
                                                       style="padding: 4px 10px; font-size: 12px;"
                                                       title="Edit Bill Details">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="./rent_report_detail.php?id=<?php echo urlencode($row['bill_id']); ?>" 
                                                       target="_blank" 
                                                       class="btn-sh-outline" 
                                                       style="padding: 4px 8px; font-size: 12px;"
                                                       title="View & Print Bill">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn-sh-danger delete_bill_btn" 
                                                            data-bill-id="<?php echo htmlspecialchars($row['bill_id']); ?>"
                                                            data-bill-no="<?php echo htmlspecialchars($displayBillNumber); ?>"
                                                            title="Delete Bill">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        $srno++;
                                    endwhile; 
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        <div class="d-flex justify-content-between align-items-center p-3 border-top flex-wrap gap-2">
                            <div class="text-muted" style="font-size: 13px;">
                                Page <strong><?php echo $page; ?></strong> of <strong><?php echo $totalPages; ?></strong>
                            </div>

                            <?php if ($totalPages > 1): ?>
                                <?php
                                $queryParams = [
                                    'keyword' => $searchKeyword,
                                    'status' => $searchStatus,
                                    'start_date' => $searchStartDate,
                                    'end_date' => $searchEndDate,
                                    'limit' => $limit
                                ];
                                $buildPageUrl = function($p) use ($queryParams) {
                                    $params = array_merge($queryParams, ['page' => $p]);
                                    return '?' . http_build_query($params);
                                };

                                $range = 2;
                                $startPage = max(1, $page - $range);
                                $endPage   = min($totalPages, $page + $range);
                                ?>

                                <ul class="sh-pagination">
                                    <li>
                                        <a href="<?php echo $buildPageUrl(1); ?>" class="<?php echo $page == 1 ? 'disabled' : ''; ?>" title="First Page">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo $buildPageUrl(max(1, $page - 1)); ?>" class="<?php echo $page == 1 ? 'disabled' : ''; ?>" title="Previous Page">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>

                                    <?php if ($startPage > 1): ?>
                                        <li><a href="<?php echo $buildPageUrl(1); ?>">1</a></li>
                                        <?php if ($startPage > 2): ?>
                                            <li><span class="text-muted border-0 bg-transparent">...</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li>
                                            <a href="<?php echo $buildPageUrl($i); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($endPage < $totalPages): ?>
                                        <?php if ($endPage < $totalPages - 1): ?>
                                            <li><span class="text-muted border-0 bg-transparent">...</span></li>
                                        <?php endif; ?>
                                        <li><a href="<?php echo $buildPageUrl($totalPages); ?>"><?php echo $totalPages; ?></a></li>
                                    <?php endif; ?>

                                    <li>
                                        <a href="<?php echo $buildPageUrl(min($totalPages, $page + 1)); ?>" class="<?php echo $page == $totalPages ? 'disabled' : ''; ?>" title="Next Page">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo $buildPageUrl($totalPages); ?>" class="<?php echo $page == $totalPages ? 'disabled' : ''; ?>" title="Last Page">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-receipt text-muted" style="font-size: 40px; opacity: 0.4;"></i>
                            </div>
                            <h4 style="font-size: 16px; font-weight: 600; color: var(--color-slate-800);">No Rent Bills Found</h4>
                            <p class="text-muted mb-3" style="font-size: 13px;">No bills matched your active search query or date range filters.</p>
                            <?php if ($searchKeyword !== '' || $searchStatus !== '' || $searchStartDate !== '' || $searchEndDate !== ''): ?>
                                <a href="editRentBill.php" class="btn-sh-outline">
                                    <i class="fas fa-undo"></i> Reset Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <!-- content-wrapper ends -->
        <?php include_once(__DIR__ . '/../footer.php'); ?>
    </div>
    <!-- main-panel ends -->
</div>
<!-- page-body-wrapper ends -->

<script>
document.querySelectorAll(".delete_bill_btn").forEach(button => {
    button.addEventListener("click", function () {
        const billId = this.getAttribute("data-bill-id");
        const billNo = this.getAttribute("data-bill-no");

        if (confirm(`Are you sure you want to delete rent bill ${billNo} (ID: ${billId})?\n\nThis will permanently delete the bill, line items, and related transactions.`)) {
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;

            fetch("./rent_report_delete.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `bill_id=${encodeURIComponent(billId)}`,
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === "success") {
                    const row = document.getElementById(`bill-row-${billId}`);
                    if (row) {
                        row.style.transition = "all 0.3s ease";
                        row.style.backgroundColor = "#fee2e2";
                        setTimeout(() => {
                            row.remove();
                        }, 300);
                    }
                } else {
                    alert(`Error: ${data.message || 'Could not delete bill.'}`);
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }
            })
            .catch(error => {
                alert(`Request failed: ${error.message}`);
                this.innerHTML = originalHtml;
                this.disabled = false;
            });
        }
    });
});
</script>
