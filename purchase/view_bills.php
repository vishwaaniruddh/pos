<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (file_exists(__DIR__ . '/../top-header.php')) include_once(__DIR__ . '/../top-header.php');
else if (file_exists('../top-header.php')) include_once('../top-header.php');

if (file_exists(__DIR__ . '/../top-navbar.php')) include_once(__DIR__ . '/../top-navbar.php');
else if (file_exists('../top-navbar.php')) include_once('../top-navbar.php');
?>
<div class="container-fluid page-body-wrapper">
    <?php
    if (file_exists(__DIR__ . '/../navbar.php')) include_once(__DIR__ . '/../navbar.php');
    else if (file_exists('../navbar.php')) include_once('../navbar.php');

    if (file_exists(__DIR__ . '/../db_connection.php')) include_once(__DIR__ . '/../db_connection.php');
    else if (file_exists('../db_connection.php')) include_once('../db_connection.php');

    $con = OpenSrishringarrCon();

    function safe_str($v) {
        return trim((string)($v ?? ''));
    }

    // KPI Metrics calculation for overall purchase bills
    $kpi_total_bills = 0;
    $kpi_gross_sum = 0;
    $kpi_paid_sum = 0;
    $kpi_outstanding_sum = 0;

    $q_kpi = mysqli_query($con, "SELECT 
                COUNT(*) as total_bills, 
                COALESCE(SUM(totalamt), 0) as gross_sum, 
                COALESCE(SUM(payamt), 0) as paid_sum, 
                COALESCE(SUM(outstanding), 0) as outstanding_sum 
            FROM phppos_purchase");
    if ($q_kpi && $r_kpi = mysqli_fetch_assoc($q_kpi)) {
        $kpi_total_bills = intval($r_kpi['total_bills']);
        $kpi_gross_sum = floatval($r_kpi['gross_sum']);
        $kpi_paid_sum = floatval($r_kpi['paid_sum']);
        $kpi_outstanding_sum = floatval($r_kpi['outstanding_sum']);
    }

    // Query active suppliers for dropdown
    $qry_supp = mysqli_query($con, "SELECT person_id, company_name FROM `phppos_suppliers` WHERE TRIM(COALESCE(company_name, '')) != '' ORDER BY company_name ASC");
    $suppliers = [];
    if ($qry_supp) {
        while ($r = mysqli_fetch_assoc($qry_supp)) {
            $suppliers[] = $r;
        }
    }
    ?>

    <!-- Main Panel -->
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">

            <!-- Load External Assets -->
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
                    --pm-slate-300: #cbd5e1;
                    --pm-slate-200: #e2e8f0;
                    --pm-slate-100: #f1f5f9;
                    --pm-slate-50:  #f8fafc;
                }

                body {
                    background-color: var(--pm-slate-50);
                    color: var(--pm-slate-900);
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                .bills-container {
                    max-width: 1440px;
                    margin: 0 auto;
                }

                /* Page Header */
                .pm-page-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    flex-wrap: wrap;
                    gap: 16px;
                    margin-bottom: 24px;
                    padding-bottom: 16px;
                    border-bottom: 1px solid var(--pm-slate-200);
                }

                .pm-page-title {
                    font-size: 20px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0 0 4px 0;
                    letter-spacing: -0.02em;
                    display: flex;
                    align-items: center;
                }

                .pm-page-subtitle {
                    font-size: 13px;
                    color: var(--pm-slate-500);
                    margin: 0;
                }

                /* KPI Metric Cards */
                .pm-kpi-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                    gap: 16px;
                    margin-bottom: 24px;
                }

                .pm-kpi-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 16px 18px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-kpi-card:hover {
                    border-color: var(--pm-slate-300);
                    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
                }

                .pm-kpi-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 10px;
                }

                .pm-kpi-label {
                    font-size: 11.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    color: var(--pm-slate-500);
                }

                .pm-kpi-icon {
                    width: 32px;
                    height: 32px;
                    border-radius: 6px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--pm-slate-600);
                }

                .pm-kpi-value {
                    font-size: 22px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    line-height: 1.1;
                }

                .pm-kpi-subtext {
                    font-size: 11.5px;
                    color: var(--pm-slate-400);
                    margin-top: 5px;
                }

                /* Standard Card */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 20px;
                    overflow: hidden;
                }

                .pm-card-header {
                    padding: 14px 18px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    background: #ffffff;
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
                }

                .pm-card-body {
                    padding: 18px 20px;
                }

                /* Buttons */
                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff !important;
                    border: 1px solid var(--pm-slate-900);
                    border-radius: 6px;
                    padding: 7px 16px;
                    font-size: 13px;
                    font-weight: 500;
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
                    color: #ffffff !important;
                }

                .pm-btn-outline {
                    background: #ffffff;
                    color: var(--pm-slate-700) !important;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    padding: 7px 14px;
                    font-size: 13px;
                    font-weight: 500;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                }

                .pm-btn-outline:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900) !important;
                    border-color: var(--pm-slate-300);
                }

                .pm-action-btn {
                    width: 30px;
                    height: 30px;
                    border-radius: 6px;
                    border: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                    color: var(--pm-slate-600);
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                }

                .pm-action-btn:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900);
                    border-color: var(--pm-slate-300);
                }

                /* Form Controls */
                .pm-form-group {
                    margin-bottom: 0;
                }

                .pm-form-label {
                    display: block;
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--pm-slate-700);
                    margin-bottom: 5px;
                }

                .pm-control {
                    width: 100%;
                    height: 36px;
                    padding: 6px 12px;
                    font-size: 13px;
                    color: var(--pm-slate-900);
                    background-color: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-control:focus {
                    outline: none;
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.08);
                }

                /* Preset Filter Chips */
                .pm-chip-group {
                    display: flex;
                    gap: 6px;
                    flex-wrap: wrap;
                }

                .pm-chip {
                    padding: 4px 10px;
                    font-size: 11.5px;
                    border-radius: 4px;
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-600);
                    cursor: pointer;
                    transition: all 0.15s ease;
                }

                .pm-chip:hover, .pm-chip.active {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                /* Bills Data Table */
                .pm-bills-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 0;
                }

                .pm-bills-table th {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-600);
                    font-size: 11.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                    padding: 10px 12px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    border-top: none;
                    white-space: nowrap;
                }

                .pm-bills-table td {
                    padding: 10px 12px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    vertical-align: middle;
                    font-size: 13px;
                }

                .pm-bills-table tbody tr:hover {
                    background-color: #fafbfc;
                }

                .pm-table-totals-row td {
                    background: var(--pm-slate-50);
                    font-size: 13px;
                    padding: 12px;
                    border-top: 2px solid var(--pm-slate-200);
                    border-bottom: none;
                }

                /* Badges */
                .pm-code-badge {
                    display: inline-block;
                    padding: 2px 7px;
                    font-size: 11px;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-weight: 600;
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 4px;
                }

                .pm-supplier-badge {
                    display: inline-block;
                    max-width: 220px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                    font-weight: 600;
                    color: var(--pm-slate-800);
                    font-size: 12.5px;
                }

                .pm-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    padding: 3px 8px;
                    font-size: 11px;
                    font-weight: 500;
                    border-radius: 9999px;
                    border: 1px solid transparent;
                }

                .pm-badge-paid {
                    background: #f1f5f9;
                    color: #334155;
                    border-color: #cbd5e1;
                }

                .pm-badge-unpaid {
                    background: #ffffff;
                    color: #0f172a;
                    border-color: #94a3b8;
                    font-weight: 600;
                }

                .pm-badge-partial {
                    background: #f8fafc;
                    color: #475569;
                    border-color: #cbd5e1;
                }

                .pm-dot {
                    width: 6px;
                    height: 6px;
                    border-radius: 50%;
                }

                .pm-dot-paid { background: #64748b; }
                .pm-dot-unpaid { background: #0f172a; }
                .pm-dot-partial { background: #94a3b8; }

                /* Floating Batch Payment Bar */
                .pm-sticky-pay-bar {
                    position: fixed;
                    bottom: 24px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    padding: 12px 24px;
                    border-radius: 9999px;
                    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.4), 0 8px 10px -6px rgba(15, 23, 42, 0.3);
                    display: flex;
                    align-items: center;
                    gap: 20px;
                    z-index: 1050;
                    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
                }

                .pm-sticky-pay-bar.hidden {
                    transform: translate(-50%, 100px);
                    opacity: 0;
                    pointer-events: none;
                }

                .pm-sticky-pay-bar-btn {
                    background: #ffffff;
                    color: var(--pm-slate-900) !important;
                    border: none;
                    border-radius: 9999px;
                    padding: 7px 18px;
                    font-size: 13px;
                    font-weight: 600;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                }

                .pm-sticky-pay-bar-btn:hover {
                    background: var(--pm-slate-100);
                }

                /* Loading Skeleton / Spinner */
                .pm-loading-state {
                    padding: 48px;
                    text-align: center;
                    color: var(--pm-slate-500);
                }

                .pm-spinner {
                    width: 28px;
                    height: 28px;
                    border: 3px solid var(--pm-slate-200);
                    border-top-color: var(--pm-slate-900);
                    border-radius: 50%;
                    animation: spin 0.75s linear infinite;
                    margin: 0 auto 12px;
                }

                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
            </style>

            <div class="bills-container">

                <!-- 1. Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa fa-file-text-o text-muted" style="font-size: 18px; margin-right: 8px;"></i>
                            Supplier Bills & Purchases
                        </h1>
                        <p class="pm-page-subtitle">Track supplier bills, view historical invoice lines, and manage batch vendor payments.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="purchase_entry.php" class="pm-btn-primary" title="Record a new inventory purchase bill">
                            <i class="fa fa-plus"></i> New Purchase Entry
                        </a>
                        <a href="view_supplier.php" class="pm-btn-outline" title="View suppliers directory">
                            <i class="fa fa-users"></i> Supplier Directory
                        </a>
                        <a href="bank_report.php" class="pm-btn-outline" title="View banking transactions">
                            <i class="fa fa-bank"></i> Bank Report
                        </a>
                    </div>
                </div>

                <!-- 2. KPI Metrics Grid -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Bills</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value"><?php echo number_format($kpi_total_bills); ?></div>
                        <div class="pm-kpi-subtext">Recorded supplier invoices</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Gross Purchases</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value">₹ <?php echo number_format($kpi_gross_sum, 2); ?></div>
                        <div class="pm-kpi-subtext">Cumulative invoiced value</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Paid</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value">₹ <?php echo number_format($kpi_paid_sum, 2); ?></div>
                        <div class="pm-kpi-subtext">Settled vendor payments</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Outstanding Balance</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value">₹ <?php echo number_format($kpi_outstanding_sum, 2); ?></div>
                        <div class="pm-kpi-subtext">Current pending balance</div>
                    </div>
                </div>

                <!-- 3. Filter & Search Panel Card -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <h3 class="pm-card-title">
                            <i class="fa fa-filter text-muted" style="margin-right: 6px;"></i>
                            Filter & Search Bills
                        </h3>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="pm-btn-outline" style="height: 32px; padding: 4px 10px; font-size: 12px;" onclick="exportTableToCSV()">
                                <i class="fa fa-download"></i> Export CSV
                            </button>
                            <button type="button" class="pm-btn-outline" style="height: 32px; padding: 4px 10px; font-size: 12px;" onclick="resetFilters()">
                                <i class="fa fa-refresh"></i> Reset
                            </button>
                        </div>
                    </div>
                    <div class="pm-card-body">
                        <form id="filterForm" onsubmit="event.preventDefault(); showbills();">
                            <div class="row g-3">
                                
                                <!-- Supplier Selection -->
                                <div class="col-xl-4 col-lg-4 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="supp_id">Supplier Name</label>
                                        <select name="supp_id" id="supp_id" class="pm-control" onchange="showbills()">
                                            <option value="-1">All Suppliers (Overview)</option>
                                            <?php foreach ($suppliers as $s) { ?>
                                                <option value="<?php echo $s['person_id']; ?>">
                                                    <?php echo htmlspecialchars($s['company_name']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Phone Lookup -->
                                <div class="col-xl-3 col-lg-4 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="phoneNo">Find by Supplier Phone</label>
                                        <div class="input-group">
                                            <input type="text" name="phoneNo" id="phoneNo" class="pm-control" 
                                                   placeholder="Enter phone digits..." autocomplete="off" />
                                            <button type="button" class="pm-btn-outline" style="border-top-left-radius: 0; border-bottom-left-radius: 0;" onclick="loadPhoneNo();">
                                                Lookup
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Item / SKU Search -->
                                <div class="col-xl-3 col-lg-4 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="itnm">Filter by Item Name / SKU</label>
                                        <input type="text" name="itnm" id="itnm" class="pm-control" 
                                               placeholder="Type item / SKU name..." autocomplete="off" />
                                    </div>
                                </div>

                                <!-- Bill Status Type -->
                                <div class="col-xl-2 col-lg-3 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="type">Bill Payment Status</label>
                                        <select name="type" id="type" class="pm-control" onchange="showbills()">
                                            <option value="2" selected>Both (All Status)</option>
                                            <option value="0">Un-Paid Bills Only</option>
                                            <option value="1">Paid Bills Only</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Date From -->
                                <div class="col-xl-3 col-lg-3 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="frmdate">Date From</label>
                                        <input type="date" name="frmdate" id="frmdate" class="pm-control" />
                                    </div>
                                </div>

                                <!-- Date To -->
                                <div class="col-xl-3 col-lg-3 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="todate">Date To</label>
                                        <input type="date" name="todate" id="todate" class="pm-control" />
                                    </div>
                                </div>

                                <!-- Quick Presets & Search Action -->
                                <div class="col-xl-6 col-lg-6 col-md-12 d-flex align-items-end justify-content-between flex-wrap gap-2">
                                    <div>
                                        <label class="pm-form-label">Date Presets</label>
                                        <div class="pm-chip-group">
                                            <button type="button" class="pm-chip" onclick="applyDatePreset('month')">This Month</button>
                                            <button type="button" class="pm-chip" onclick="applyDatePreset('last30')">Last 30 Days</button>
                                            <button type="button" class="pm-chip" onclick="applyDatePreset('year')">This Year</button>
                                            <button type="button" class="pm-chip" onclick="applyDatePreset('all')">All Time</button>
                                        </div>
                                    </div>

                                    <button type="submit" class="pm-btn-primary" style="height: 36px; padding: 0 20px;">
                                        <i class="fa fa-search"></i> Apply Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 4. Real-time Search In Results & Action Bar -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                    <div class="d-flex align-items-center gap-2" style="min-width: 260px; max-width: 380px; width: 100%;">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-color: var(--pm-slate-200); color: var(--pm-slate-400);">
                                <i class="fa fa-search"></i>
                            </span>
                            <input type="text" id="liveTableSearch" class="form-control border-start-0" 
                                   style="border-color: var(--pm-slate-200); font-size: 13px; height: 36px;"
                                   placeholder="Quick search within loaded table..." 
                                   onkeyup="filterTableRows(this.value);" />
                        </div>
                    </div>
                    <div id="selectedCountIndicator" style="font-size: 13px; color: var(--pm-slate-500);">
                        Select unpaid bills below to generate a vendor payment voucher.
                    </div>
                </div>

                <!-- 5. Form & Dynamic Results Container -->
                <form id="paymentForm" action="payment_supp.php" method="POST" onsubmit="return validatePaymentSubmission();">
                    <input type="hidden" name="supp_id" id="form_supp_id" value="-1" />
                    <input type="hidden" name="payamt" id="form_payamt" value="0" />

                    <!-- Results Container -->
                    <div id="back">
                        <div class="pm-loading-state">
                            <div class="pm-spinner"></div>
                            <div>Loading purchase bills directory...</div>
                        </div>
                    </div>
                </form>

            </div><!-- .bills-container -->

            <!-- 6. Floating Sticky Payment Action Dock -->
            <div id="stickyPayDock" class="pm-sticky-pay-bar hidden">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 8px; height: 8px; border-radius: 50%; background: #ffffff;"></div>
                    <span style="font-size: 13px; font-weight: 500;">
                        <strong id="dockSelectedCount">0</strong> bill(s) selected
                    </span>
                    <span style="color: var(--pm-slate-400);">&bull;</span>
                    <span style="font-size: 14px; font-weight: 700;">
                        Total: ₹ <span id="dockPayAmount">0.00</span>
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="pm-sticky-pay-bar-btn" onclick="submitBatchPayment();">
                        <i class="fa fa-check-circle text-muted"></i> Proceed to Pay Supplier
                    </button>
                    <button type="button" style="background: transparent; border: none; color: var(--pm-slate-400); font-size: 12px; cursor: pointer;" onclick="clearSelection();">
                        Clear
                    </button>
                </div>
            </div>

            <!-- Edit Bill Modal (Iframe Container) -->
            <div class="modal fade" id="editBillModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 10px; overflow: hidden; border: 1px solid var(--pm-slate-200);">
                        <div class="modal-header py-2 px-3" style="background: var(--pm-slate-50); border-bottom: 1px solid var(--pm-slate-200);">
                            <h5 class="modal-title" style="font-size: 14px; font-weight: 700; color: var(--pm-slate-900);">
                                <i class="fa fa-pencil-square-o text-muted me-1"></i> Edit Purchase Bill
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeEditBillModal();"></button>
                        </div>
                        <div class="modal-body p-0" style="height: 75vh;">
                            <iframe id="editBillFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Load bills on page load
                $(document).ready(function() {
                    showbills();
                });

                // Main AJAX bills loader
                function showbills() {
                    var supp_id = document.getElementById('supp_id').value;
                    var frmdate = document.getElementById('frmdate').value;
                    var todate  = document.getElementById('todate').value;
                    var type    = document.getElementById('type').value;
                    var itnm    = document.getElementById('itnm').value.trim();

                    // Update hidden form input
                    document.getElementById('form_supp_id').value = supp_id;

                    $('#back').html(
                        '<div class="pm-loading-state">' +
                        '  <div class="pm-spinner"></div>' +
                        '  <div>Fetching purchase bills...</div>' +
                        '</div>'
                    );

                    // Reset selected state dock
                    hidePayDock();

                    $.ajax({
                        url: 'getbills.php',
                        type: 'GET',
                        data: {
                            supp_id: supp_id,
                            frmdate: frmdate,
                            todate: todate,
                            type: type,
                            itnm: itnm
                        },
                        success: function(response) {
                            $('#back').html(response);
                            recalculateSelected();
                        },
                        error: function(xhr, status, error) {
                            $('#back').html(
                                '<div class="pm-card text-center py-5">' +
                                '  <div class="text-danger mb-2"><i class="fa fa-exclamation-triangle fa-2x"></i></div>' +
                                '  <h5 style="color: #0f172a; font-weight: 700;">Error Loading Bills</h5>' +
                                '  <p style="color: #64748b; font-size: 13px;">Unable to fetch bills. Please check your network connection and try again.</p>' +
                                '  <button type="button" class="pm-btn-outline" onclick="showbills();">Retry</button>' +
                                '</div>'
                            );
                        }
                    });
                }

                // Phone number quick lookup
                function loadPhoneNo() {
                    var phoneStr = document.getElementById('phoneNo').value.trim();
                    if (!phoneStr) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Phone Required',
                            text: 'Please enter a phone number to search.',
                            confirmButtonColor: '#0f172a'
                        });
                        return;
                    }

                    $.ajax({
                        url: 'getbyphone.php',
                        type: 'GET',
                        data: { cid: phoneStr },
                        success: function(res) {
                            var parts = res.trim().split('&&');
                            if (parts[0] === '1' && parts[1] && parts[1] !== '0') {
                                document.getElementById('supp_id').value = parts[1];
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Supplier Found',
                                    text: 'Supplier auto-selected successfully.',
                                    timer: 1200,
                                    showConfirmButton: false
                                });
                                showbills();
                            } else {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Not Found',
                                    text: 'No supplier found matching phone: ' + phoneStr,
                                    confirmButtonColor: '#0f172a'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lookup Failed',
                                text: 'Failed to search by phone number.',
                                confirmButtonColor: '#0f172a'
                            });
                        }
                    });
                }

                // Preset date range helpers
                function applyDatePreset(preset) {
                    var now = new Date();
                    var fromInput = document.getElementById('frmdate');
                    var toInput   = document.getElementById('todate');

                    function fmt(d) {
                        var y = d.getFullYear();
                        var m = String(d.getMonth() + 1).padStart(2, '0');
                        var day = String(d.getDate()).padStart(2, '0');
                        return y + '-' + m + '-' + day;
                    }

                    $('.pm-chip').removeClass('active');

                    if (preset === 'month') {
                        var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                        fromInput.value = fmt(firstDay);
                        toInput.value = fmt(now);
                    } else if (preset === 'last30') {
                        var past = new Date();
                        past.setDate(now.getDate() - 30);
                        fromInput.value = fmt(past);
                        toInput.value = fmt(now);
                    } else if (preset === 'year') {
                        var yearStart = new Date(now.getFullYear(), 0, 1);
                        fromInput.value = fmt(yearStart);
                        toInput.value = fmt(now);
                    } else if (preset === 'all') {
                        fromInput.value = '';
                        toInput.value = '';
                    }

                    showbills();
                }

                // Reset all filter fields
                function resetFilters() {
                    document.getElementById('supp_id').value = '-1';
                    document.getElementById('phoneNo').value = '';
                    document.getElementById('itnm').value = '';
                    document.getElementById('type').value = '2';
                    document.getElementById('frmdate').value = '';
                    document.getElementById('todate').value = '';
                    document.getElementById('liveTableSearch').value = '';
                    $('.pm-chip').removeClass('active');
                    showbills();
                }

                // Client-side quick filter on table rows
                function filterTableRows(query) {
                    var q = query.toLowerCase().trim();
                    var rows = document.querySelectorAll('#billsDataTable tbody tr.bill-row');
                    rows.forEach(function(row) {
                        var text = row.textContent.toLowerCase();
                        if (!q || text.indexOf(q) !== -1) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }

                // Toggle Select All Unpaid Checkboxes
                function toggleSelectAll(masterCheckbox) {
                    var isChecked = masterCheckbox.checked;
                    var checks = document.querySelectorAll('.payment-check');
                    checks.forEach(function(ck) {
                        // Only check visible rows
                        var row = ck.closest('tr');
                        if (row && row.style.display !== 'none') {
                            ck.checked = isChecked;
                        }
                    });
                    recalculateSelected();
                }

                // Recalculate selected sum and update sticky payment dock
                function recalculateSelected() {
                    var checks = document.querySelectorAll('.payment-check:checked');
                    var sum = 0;
                    var count = checks.length;
                    var suppIds = {};

                    checks.forEach(function(ck) {
                        var out = parseFloat(ck.getAttribute('data-outstanding')) || 0;
                        sum += out;
                        var sId = ck.getAttribute('data-suppid');
                        if (sId) suppIds[sId] = true;
                    });

                    document.getElementById('form_payamt').value = sum.toFixed(2);

                    if (count > 0) {
                        document.getElementById('dockSelectedCount').textContent = count;
                        document.getElementById('dockPayAmount').textContent = sum.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        document.getElementById('selectedCountIndicator').innerHTML = 
                            '<strong style="color: #0f172a;">' + count + '</strong> bill(s) selected &bull; Total Amount: <strong>₹ ' + sum.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</strong>';
                        showPayDock();
                    } else {
                        document.getElementById('selectedCountIndicator').textContent = 
                            'Select unpaid bills below to generate a vendor payment voucher.';
                        hidePayDock();
                    }
                }

                function showPayDock() {
                    $('#stickyPayDock').removeClass('hidden');
                }

                function hidePayDock() {
                    $('#stickyPayDock').addClass('hidden');
                }

                function clearSelection() {
                    document.querySelectorAll('.payment-check').forEach(function(ck) {
                        ck.checked = false;
                    });
                    var selectAll = document.getElementById('selectAllCheckbox');
                    if (selectAll) selectAll.checked = false;
                    recalculateSelected();
                }

                // Submit Batch Payment Voucher
                function submitBatchPayment() {
                    if (validatePaymentSubmission()) {
                        document.getElementById('paymentForm').submit();
                    }
                }

                function validatePaymentSubmission() {
                    var checks = document.querySelectorAll('.payment-check:checked');
                    if (checks.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Bills Selected',
                            text: 'Please select at least one unpaid bill to proceed with payment.',
                            confirmButtonColor: '#0f172a'
                        });
                        return false;
                    }

                    // Check if selected bills belong to multiple distinct suppliers
                    var distinctSuppliers = {};
                    var lastSuppId = '';
                    checks.forEach(function(ck) {
                        var sId = ck.getAttribute('data-suppid');
                        if (sId) {
                            distinctSuppliers[sId] = ck.getAttribute('data-suppname') || sId;
                            lastSuppId = sId;
                        }
                    });

                    var supplierCount = Object.keys(distinctSuppliers).length;
                    if (supplierCount > 1) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Multiple Suppliers Selected',
                            text: 'A payment voucher can only be created for one supplier at a time. Please select bills from a single supplier, or use the supplier filter above.',
                            confirmButtonColor: '#0f172a'
                        });
                        return false;
                    }

                    // Ensure form_supp_id matches the selected bills' supplier
                    if (lastSuppId) {
                        document.getElementById('form_supp_id').value = lastSuppId;
                    }

                    return true;
                }

                // Edit Bill Modal / Popup Helper
                function openEditBillModal(billId) {
                    var url = 'edit_bill.php?bill_id=' + encodeURIComponent(billId);
                    // Open in centered modal popup
                    var w = 1200;
                    var h = 700;
                    var left = (window.screen.width - w) / 2;
                    var top = (window.screen.height - h) / 2;
                    window.open(url, 'edit_bill_win', 'width=' + w + ',height=' + h + ',top=' + top + ',left=' + left + ',scrollbars=yes,resizable=yes');
                }

                function closeEditBillModal() {
                    $('#editBillFrame').attr('src', '');
                    showbills(); // Refresh bills in case amounts changed
                }

                // Export Table Data to CSV
                function exportTableToCSV() {
                    var table = document.getElementById('billsDataTable');
                    if (!table) {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Data',
                            text: 'No bill records loaded to export.',
                            confirmButtonColor: '#0f172a'
                        });
                        return;
                    }

                    var csv = [];
                    var rows = table.querySelectorAll('tr');

                    rows.forEach(function(row) {
                        // Skip hidden rows from live search
                        if (row.style.display === 'none') return;

                        var cols = row.querySelectorAll('th, td');
                        var rowData = [];

                        cols.forEach(function(col, idx) {
                            // Skip checkbox column (idx 0) and actions column (last idx)
                            if (idx === 0 || idx === cols.length - 1) return;

                            var text = col.innerText.replace(/"/g, '""').trim();
                            // Clean up formatted currency signs
                            text = text.replace(/₹/g, '').trim();
                            rowData.push('"' + text + '"');
                        });

                        if (rowData.length > 0) {
                            csv.push(rowData.join(','));
                        }
                    });

                    var csvBlob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
                    var downloadLink = document.createElement('a');
                    var suppName = $('#supp_id option:selected').text().trim().replace(/[^a-zA-Z0-9]/g, '_');
                    downloadLink.href = URL.createObjectURL(csvBlob);
                    downloadLink.download = 'purchase_bills_' + suppName + '_' + new Date().toISOString().slice(0, 10) + '.csv';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                }
            </script>

        </div><!-- .content-wrapper -->
    </div><!-- .main-panel -->
</div><!-- .page-body-wrapper -->

<?php
CloseCon($con);
if (file_exists(__DIR__ . '/../footer.php')) include_once(__DIR__ . '/../footer.php');
else if (file_exists('../footer.php')) include_once('../footer.php');
?>