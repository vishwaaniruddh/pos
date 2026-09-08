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

    // KPI Metrics calculation for supplier payments
    $kpi_total_vouchers = 0;
    $kpi_disbursed_sum  = 0;
    $kpi_suppliers_paid = 0;
    $kpi_payment_days   = 0;

    $q_kpi = mysqli_query($con, "SELECT 
                COUNT(*) as total_vouchers, 
                COALESCE(SUM(amt), 0) as disbursed_sum,
                COUNT(DISTINCT p.supp_id) as suppliers_paid,
                COUNT(DISTINCT pp.paid_date) as payment_days
            FROM phppos_purchase_payments pp
            LEFT JOIN phppos_purchase p ON pp.bill_no = p.pur_id
            WHERE pp.amt > 0");
    if ($q_kpi && $r_kpi = mysqli_fetch_assoc($q_kpi)) {
        $kpi_total_vouchers = intval($r_kpi['total_vouchers']);
        $kpi_disbursed_sum  = floatval($r_kpi['disbursed_sum']);
        $kpi_suppliers_paid = intval($r_kpi['suppliers_paid']);
        $kpi_payment_days   = intval($r_kpi['payment_days']);
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

                .payments-container {
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

                /* Payments Table */
                .pm-payments-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 0;
                }

                .pm-payments-table th {
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

                .pm-payments-table td {
                    padding: 10px 12px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    vertical-align: middle;
                    font-size: 13px;
                }

                .pm-payments-table tbody tr:hover {
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
                    font-weight: 600;
                    border-radius: 9999px;
                    border: 1px solid transparent;
                }

                .pm-badge-cash {
                    background: #f1f5f9;
                    color: #0f172a;
                    border-color: #cbd5e1;
                }

                .pm-badge-cheque {
                    background: #ffffff;
                    color: #334155;
                    border-color: #cbd5e1;
                }

                .pm-badge-neutral {
                    background: #f8fafc;
                    color: #475569;
                    border-color: #e2e8f0;
                }

                /* Loading Spinner */
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

            <div class="payments-container">

                <!-- 1. Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa fa-money text-muted" style="font-size: 18px; margin-right: 8px;"></i>
                            Supplier Payment History
                        </h1>
                        <p class="pm-page-subtitle">View disbursements, printable voucher slips, and historical payment transactions by vendor.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="view_bills.php" class="pm-btn-primary" title="View purchase bills and pending balances">
                            <i class="fa fa-file-text-o"></i> View Purchase Bills
                        </a>
                        <a href="purchase_entry.php" class="pm-btn-outline" title="Record a new inventory purchase bill">
                            <i class="fa fa-plus"></i> New Purchase Entry
                        </a>
                        <a href="view_supplier.php" class="pm-btn-outline" title="View suppliers directory">
                            <i class="fa fa-users"></i> Supplier Directory
                        </a>
                        <a href="bank_report.php" class="pm-btn-outline" title="View banking report">
                            <i class="fa fa-bank"></i> Bank Report
                        </a>
                    </div>
                </div>

                <!-- 2. KPI Metrics Grid -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Vouchers</span>
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
                        <div class="pm-kpi-value"><?php echo number_format($kpi_total_vouchers); ?></div>
                        <div class="pm-kpi-subtext">Issued settlement vouchers</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Disbursed</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value">₹ <?php echo number_format($kpi_disbursed_sum, 2); ?></div>
                        <div class="pm-kpi-subtext">Settled vendor payments</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Suppliers Settled</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value"><?php echo number_format($kpi_suppliers_paid); ?></div>
                        <div class="pm-kpi-subtext">Distinct paid vendors</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Active Days</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value"><?php echo number_format($kpi_payment_days); ?></div>
                        <div class="pm-kpi-subtext">Disbursement dates</div>
                    </div>
                </div>

                <!-- 3. Filter & Search Panel Card -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <h3 class="pm-card-title">
                            <i class="fa fa-filter text-muted" style="margin-right: 6px;"></i>
                            Filter Payment Records
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

                                <!-- Payment Mode Filter -->
                                <div class="col-xl-2 col-lg-4 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="mode">Payment Mode</label>
                                        <select name="mode" id="mode" class="pm-control" onchange="showbills()">
                                            <option value="all" selected>All Modes</option>
                                            <option value="cash">Cash Only</option>
                                            <option value="cheque">Cheque</option>
                                            <option value="neft">NEFT / RTGS</option>
                                            <option value="upi">UPI / Online</option>
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
                                <div class="col-xl-9 col-lg-9 col-md-12 d-flex align-items-end justify-content-between flex-wrap gap-2">
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

                <!-- 4. Quick Table Search Bar -->
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
                    <div style="font-size: 12.5px; color: var(--pm-slate-500);">
                        <i class="fa fa-info-circle text-muted me-1"></i> Click the print icon on any row to open the formal payment voucher slip.
                    </div>
                </div>

                <!-- 5. Dynamic Results Container -->
                <div id="back">
                    <div class="pm-loading-state">
                        <div class="pm-spinner"></div>
                        <div>Loading payment records...</div>
                    </div>
                </div>

            </div><!-- .payments-container -->

            <script>
                // Load payments on mount
                $(document).ready(function() {
                    showbills();
                });

                // Main AJAX payments loader
                function showbills() {
                    var supp_id = document.getElementById('supp_id').value;
                    var frmdate = document.getElementById('frmdate').value;
                    var todate  = document.getElementById('todate').value;
                    var mode    = document.getElementById('mode').value;

                    $('#back').html(
                        '<div class="pm-loading-state">' +
                        '  <div class="pm-spinner"></div>' +
                        '  <div>Fetching payment records...</div>' +
                        '</div>'
                    );

                    $.ajax({
                        url: 'getpayments.php',
                        type: 'GET',
                        data: {
                            supp_id: supp_id,
                            frmdate: frmdate,
                            todate: todate,
                            mode: mode
                        },
                        success: function(response) {
                            $('#back').html(response);
                        },
                        error: function() {
                            $('#back').html(
                                '<div class="pm-card text-center py-5">' +
                                '  <div class="text-danger mb-2"><i class="fa fa-exclamation-triangle fa-2x"></i></div>' +
                                '  <h5 style="color: #0f172a; font-weight: 700;">Error Loading Payments</h5>' +
                                '  <p style="color: #64748b; font-size: 13px;">Unable to fetch records. Please check your network connection and try again.</p>' +
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
                    document.getElementById('mode').value = 'all';
                    document.getElementById('frmdate').value = '';
                    document.getElementById('todate').value = '';
                    document.getElementById('liveTableSearch').value = '';
                    $('.pm-chip').removeClass('active');
                    showbills();
                }

                // Client-side quick filter on table rows
                function filterTableRows(query) {
                    var q = query.toLowerCase().trim();
                    var rows = document.querySelectorAll('#paymentsDataTable tbody tr.payment-row');
                    rows.forEach(function(row) {
                        var text = row.textContent.toLowerCase();
                        if (!q || text.indexOf(q) !== -1) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }

                // Export Table Data to CSV
                function exportTableToCSV() {
                    var table = document.getElementById('paymentsDataTable');
                    if (!table) {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Data',
                            text: 'No payment records loaded to export.',
                            confirmButtonColor: '#0f172a'
                        });
                        return;
                    }

                    var csv = [];
                    var rows = table.querySelectorAll('tr');

                    rows.forEach(function(row) {
                        if (row.style.display === 'none') return;

                        var cols = row.querySelectorAll('th, td');
                        var rowData = [];

                        cols.forEach(function(col, idx) {
                            // Skip actions column (last idx)
                            if (idx === cols.length - 1) return;

                            var text = col.innerText.replace(/"/g, '""').trim();
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
                    downloadLink.download = 'supplier_payments_' + suppName + '_' + new Date().toISOString().slice(0, 10) + '.csv';
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