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

    // Query all suppliers joined with their contact profile, filtering out empty records
    $sql = "SELECT 
                p.person_id,
                p.first_name,
                p.last_name,
                p.phone_number,
                p.email,
                p.address_1,
                p.address_2,
                p.city,
                p.state,
                p.zip,
                p.country,
                p.comments,
                s.company_name,
                s.account_number
            FROM `phppos_suppliers` s
            INNER JOIN `phppos_people` p ON s.person_id = p.person_id
            WHERE TRIM(COALESCE(s.company_name, '')) != '' OR TRIM(COALESCE(p.first_name, '')) != ''
            ORDER BY s.company_name ASC, p.first_name ASC";

    $res = mysqli_query($con, $sql);
    $suppliers = [];
    $phone_count = 0;
    $city_set = [];

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $suppliers[] = $row;
            $p_num = safe_str($row['phone_number'] ?? '');
            if ($p_num !== '') $phone_count++;
            $c_val = safe_str($row['city'] ?? '');
            if ($c_val !== '') $city_set[strtolower($c_val)] = true;
        }
    }

    $total_suppliers = count($suppliers);
    $total_cities = count($city_set);

    // Count suppliers with recorded purchases
    $purch_supp_count = 0;
    $q_purch = mysqli_query($con, "SELECT COUNT(DISTINCT supp_id) FROM phppos_purchase WHERE supp_id > 0");
    if ($q_purch && $r_purch = mysqli_fetch_row($q_purch)) {
        $purch_supp_count = $r_purch[0];
    }
    ?>

    <!-- Main Panel -->
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">

            <!-- Load FontAwesome 4.7 and 6 fallback CDNs -->
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

                .supp-view-container {
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

                /* KPI Grid */
                .pm-kpi-grid {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 16px;
                    margin-bottom: 20px;
                }

                @media (max-width: 768px) {
                    .pm-kpi-grid {
                        grid-template-columns: 1fr;
                    }
                }

                .pm-kpi-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 16px 18px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                }

                .pm-kpi-label {
                    font-size: 11.5px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    color: var(--pm-slate-500);
                    margin-bottom: 4px;
                }

                .pm-kpi-value {
                    font-size: 22px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    line-height: 1.2;
                }

                .pm-kpi-sub {
                    font-size: 12px;
                    color: var(--pm-slate-500);
                    margin-top: 4px;
                }

                .pm-kpi-icon {
                    width: 38px;
                    height: 38px;
                    border-radius: 8px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-600);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    margin-left: 12px;
                }

                .pm-kpi-icon svg {
                    stroke: var(--pm-slate-600);
                }

                /* Card Table Container */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    overflow: hidden;
                    margin-bottom: 20px;
                }

                .pm-card-header {
                    padding: 14px 20px;
                    background: #ffffff;
                    border-bottom: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 12px;
                }

                .pm-card-title {
                    font-size: 14.5px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .pm-badge-count {
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
                    font-size: 11.5px;
                    font-weight: 600;
                    padding: 2px 8px;
                    border-radius: 12px;
                }

                /* Buttons */
                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border: 1px solid var(--pm-slate-900);
                    font-size: 12.5px;
                    font-weight: 600;
                    border-radius: 6px;
                    padding: 7px 15px;
                    height: 36px;
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
                    padding: 6px 13px;
                    height: 36px;
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

                .pm-btn-primary i, .pm-btn-outline i, .pm-btn-primary svg, .pm-btn-outline svg {
                    margin-right: 4px;
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
                    text-decoration: none;
                    margin-right: 4px;
                }

                .pm-action-btn svg {
                    stroke: var(--pm-slate-600);
                    transition: stroke 0.15s ease;
                }

                .pm-action-btn:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900);
                    border-color: #cbd5e1;
                }

                .pm-action-btn:hover svg {
                    stroke: var(--pm-slate-900);
                }

                .pm-action-btn.btn-delete:hover {
                    background: #fef2f2;
                    color: #ef4444;
                    border-color: #fecaca;
                }

                .pm-action-btn.btn-delete:hover svg {
                    stroke: #ef4444;
                }

                /* Search Input */
                .pm-search-box {
                    position: relative;
                    width: 250px;
                }

                .pm-search-input {
                    height: 36px;
                    font-size: 12.5px;
                    color: var(--pm-slate-900);
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    padding: 6px 12px 6px 32px;
                    width: 100%;
                    transition: all 0.15s ease;
                }

                .pm-search-input:focus {
                    border-color: var(--pm-slate-900);
                    outline: none;
                }

                .pm-search-icon {
                    position: absolute;
                    left: 10px;
                    top: 10px;
                    font-size: 13px;
                    color: var(--pm-slate-400);
                    pointer-events: none;
                }

                /* Data Table */
                .pm-data-table {
                    width: 100%;
                    margin: 0;
                    border-collapse: collapse;
                }

                .pm-data-table th {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-600);
                    font-size: 11.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                    padding: 10px 14px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    border-top: none;
                    white-space: nowrap;
                }

                .pm-data-table td {
                    padding: 11px 14px;
                    font-size: 13px;
                    color: var(--pm-slate-900);
                    border-bottom: 1px solid var(--pm-slate-200);
                    vertical-align: middle;
                }

                .pm-data-table tbody tr:hover {
                    background-color: #fafbfc;
                }

                .pm-code-badge {
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-size: 11.5px;
                    font-weight: 600;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
                    padding: 2px 6px;
                    border-radius: 4px;
                    white-space: nowrap;
                }

                /* Print Styles */
                @media print {
                    .navbar, #sidebar, .pm-page-header, .pm-kpi-grid, .pm-search-box, .d-print-none, .footer {
                        display: none !important;
                    }
                    .page-body-wrapper {
                        padding-top: 0 !important;
                    }
                    .main-panel {
                        margin-left: 0 !important;
                        width: 100% !important;
                    }
                    .content-wrapper {
                        padding: 0 !important;
                        background: #ffffff !important;
                    }
                    .pm-card {
                        border: none !important;
                        box-shadow: none !important;
                    }
                }
            </style>

            <div class="supp-view-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <!-- Delivery / Vendor Truck Icon -->
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                                <rect x="1" y="3" width="15" height="13"></rect>
                                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                <circle cx="18.5" cy="18.5" r="2.5"></circle>
                            </svg>
                            Suppliers Directory
                        </h1>
                        <p class="pm-page-subtitle">Manage registered vendors, primary contacts, account codes, and purchase history.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="add_supplier.php" class="pm-btn-primary" title="Register a new supplier">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add New Supplier
                        </a>
                        <a href="purchase_entry.php" class="pm-btn-outline" title="Record a purchase invoice">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            Purchase Entry
                        </a>
                        <button type="button" class="pm-btn-outline" onclick="window.print()" title="Print supplier directory">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            Print Directory
                        </button>
                    </div>
                </div>

                <!-- KPI Overview Grid -->
                <div class="pm-kpi-grid">
                    <!-- Total Suppliers -->
                    <div class="pm-kpi-card">
                        <div>
                            <div class="pm-kpi-label">Registered Suppliers</div>
                            <div class="pm-kpi-value" id="kpiTotalSupp"><?php echo $total_suppliers; ?></div>
                            <div class="pm-kpi-sub">Active vendor accounts</div>
                        </div>
                        <div class="pm-kpi-icon" title="Suppliers">
                            <!-- Building SVG -->
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                                <line x1="9" y1="6" x2="9.01" y2="6"></line>
                                <line x1="15" y1="6" x2="15.01" y2="6"></line>
                                <line x1="9" y1="10" x2="9.01" y2="10"></line>
                                <line x1="15" y1="10" x2="15.01" y2="10"></line>
                                <line x1="9" y1="14" x2="9.01" y2="14"></line>
                                <line x1="15" y1="14" x2="15.01" y2="14"></line>
                                <line x1="9" y1="18" x2="15" y2="18"></line>
                            </svg>
                        </div>
                    </div>

                    <!-- Active Invoices -->
                    <div class="pm-kpi-card">
                        <div>
                            <div class="pm-kpi-label">With Purchase Bills</div>
                            <div class="pm-kpi-value"><?php echo $purch_supp_count; ?></div>
                            <div class="pm-kpi-sub">Suppliers with billed history</div>
                        </div>
                        <div class="pm-kpi-icon" title="Bills">
                            <!-- Invoice / File SVG -->
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                    </div>

                    <!-- Contact Details Available -->
                    <div class="pm-kpi-card">
                        <div>
                            <div class="pm-kpi-label">With Phone Numbers</div>
                            <div class="pm-kpi-value"><?php echo $phone_count; ?></div>
                            <div class="pm-kpi-sub">Direct contact phone on file</div>
                        </div>
                        <div class="pm-kpi-icon" title="Phone">
                            <!-- Phone SVG -->
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Supplier Table Card -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <div>
                            <h3 class="pm-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                                    <line x1="8" y1="6" x2="21" y2="6"></line>
                                    <line x1="8" y1="12" x2="21" y2="12"></line>
                                    <line x1="8" y1="18" x2="21" y2="18"></line>
                                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                </svg>
                                Vendor Directory
                                <span class="pm-badge-count" id="badgeCount"><?php echo $total_suppliers; ?> Vendors</span>
                            </h3>
                        </div>
                        <div class="d-flex align-items-center gap-2 d-print-none flex-wrap">
                            <div class="pm-search-box">
                                <i class="fa fa-search pm-search-icon"></i>
                                <input type="text" id="supplierSearchInput" class="pm-search-input" 
                                       placeholder="Search company, contact, city..." onkeyup="filterSuppliersTable()" />
                            </div>
                            <button type="button" class="pm-btn-outline" style="height: 36px; padding: 4px 12px;" onclick="exportSuppliersCSV()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Export CSV
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table pm-data-table" id="suppliersTable">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">#</th>
                                    <th>Supplier / Company Name</th>
                                    <th style="width: 120px;">Vendor Code</th>
                                    <th>Primary Contact</th>
                                    <th style="width: 140px;">Phone / Mobile</th>
                                    <th style="width: 170px;">Email</th>
                                    <th style="width: 150px;">Location / City</th>
                                    <th style="width: 90px; text-align: center;" class="d-print-none">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($total_suppliers == 0) {
                                ?>
                                    <tr class="pm-empty-row">
                                        <td colspan="8" class="text-center py-5">
                                            <div style="font-size: 14px; font-weight: 600; color: var(--pm-slate-700); margin-bottom: 4px;">
                                                No suppliers registered yet
                                            </div>
                                            <div style="font-size: 12px; color: var(--pm-slate-500); margin-bottom: 12px;">
                                                Click the button below to add your first supplier.
                                            </div>
                                            <a href="add_supplier.php" class="pm-btn-primary">
                                                <i class="fa fa-plus"></i> Add Supplier
                                            </a>
                                        </td>
                                    </tr>
                                <?php
                                } else {
                                    $sr = 1;
                                    foreach ($suppliers as $supp) {
                                        $pid       = $supp['person_id'];
                                        $cname     = safe_str($supp['company_name'] ?? '');
                                        $accno     = safe_str($supp['account_number'] ?? '');
                                        $fname     = safe_str($supp['first_name'] ?? '');
                                        $lname     = safe_str($supp['last_name'] ?? '');
                                        $contact   = safe_str($fname . ' ' . $lname);
                                        $phone     = safe_str($supp['phone_number'] ?? '');
                                        $email     = safe_str($supp['email'] ?? '');
                                        $city      = safe_str($supp['city'] ?? '');
                                        $state     = safe_str($supp['state'] ?? '');
                                        $location  = $city !== '' ? ($state !== '' ? "$city, $state" : $city) : ($state !== '' ? $state : '—');
                                ?>
                                    <tr class="pm-supp-row" id="supp-row-<?php echo $pid; ?>">
                                        <td class="text-center text-muted" style="font-size: 12px;"><?php echo $sr; ?></td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--pm-slate-900); display: flex; align-items: center; gap: 6px;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; flex-shrink: 0;">
                                                    <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                                                    <line x1="9" y1="6" x2="9.01" y2="6"></line>
                                                    <line x1="15" y1="6" x2="15.01" y2="6"></line>
                                                    <line x1="9" y1="10" x2="9.01" y2="10"></line>
                                                    <line x1="15" y1="10" x2="15.01" y2="10"></line>
                                                    <line x1="9" y1="14" x2="9.01" y2="14"></line>
                                                    <line x1="15" y1="14" x2="15.01" y2="14"></line>
                                                    <line x1="9" y1="18" x2="15" y2="18"></line>
                                                </svg>
                                                <?php echo htmlspecialchars($cname ?: '—'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($accno) { ?>
                                                <span class="pm-code-badge">#<?php echo htmlspecialchars($accno); ?></span>
                                            <?php } else { ?>
                                                <span class="text-muted">—</span>
                                            <?php } ?>
                                        </td>
                                        <td style="color: var(--pm-slate-800); font-weight: 500;">
                                            <?php echo htmlspecialchars($contact ?: '—'); ?>
                                        </td>
                                        <td>
                                            <?php if ($phone) { ?>
                                                <a href="tel:<?php echo htmlspecialchars($phone); ?>" style="color: var(--pm-slate-700); text-decoration: none; font-weight: 500; display: inline-flex; align-items: center;">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; flex-shrink: 0;">
                                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                                    </svg>
                                                    <?php echo htmlspecialchars($phone); ?>
                                                </a>
                                            <?php } else { ?>
                                                <span class="text-muted">—</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($email) { ?>
                                                <a href="mailto:<?php echo htmlspecialchars($email); ?>" style="color: var(--pm-slate-700); text-decoration: none; display: inline-flex; align-items: center;" title="<?php echo htmlspecialchars($email); ?>">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; flex-shrink: 0;">
                                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                        <polyline points="22,6 12,13 2,6"></polyline>
                                                    </svg>
                                                    <?php echo htmlspecialchars(strlen($email) > 22 ? substr($email, 0, 20) . '...' : $email); ?>
                                                </a>
                                            <?php } else { ?>
                                                <span class="text-muted">—</span>
                                            <?php } ?>
                                        </td>
                                        <td style="color: var(--pm-slate-700); font-size: 12.5px;">
                                            <?php echo htmlspecialchars($location); ?>
                                        </td>
                                        <td class="text-center d-print-none" style="white-space: nowrap;">
                                            <!-- Edit SVG Button -->
                                            <a href="edit_supplier.php?id=<?php echo $pid; ?>" class="pm-action-btn" title="Edit Supplier">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </a>
                                            <!-- Delete SVG Button -->
                                            <button type="button" class="pm-action-btn btn-delete" 
                                                    onclick="confirmDeleteSupplier(<?php echo $pid; ?>, '<?php echo htmlspecialchars(addslashes($cname ?: $contact)); ?>')" 
                                                    title="Delete Supplier">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php
                                        $sr++;
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Scripts -->
            <script>
                // Instant client-side search
                function filterSuppliersTable() {
                    var input = document.getElementById('supplierSearchInput');
                    if (!input) return;
                    var filter = input.value.toLowerCase().trim();
                    var rows = document.querySelectorAll('#suppliersTable tbody tr.pm-supp-row');
                    var visibleCount = 0;

                    rows.forEach(function(row) {
                        var text = row.innerText.toLowerCase();
                        var matches = (text.indexOf(filter) > -1);
                        row.style.display = matches ? '' : 'none';
                        if (matches) visibleCount++;
                    });

                    var badge = document.getElementById('badgeCount');
                    if (badge) {
                        badge.innerText = visibleCount + ' Vendors';
                    }
                }

                // Delete supplier with SweetAlert2 & AJAX
                function confirmDeleteSupplier(personId, name) {
                    Swal.fire({
                        title: 'Delete Supplier?',
                        text: 'Are you sure you want to delete "' + name + '"? This will remove the supplier credentials from active records.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Yes, Delete Supplier'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: 'delSupplier.php',
                                type: 'GET',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                data: { id: personId },
                                dataType: 'json',
                                success: function(res) {
                                    if (res && res.status === 'success') {
                                        var row = document.getElementById('supp-row-' + personId);
                                        if (row) {
                                            $(row).fadeOut(250, function() {
                                                $(this).remove();
                                                // Update count
                                                var currentRows = document.querySelectorAll('#suppliersTable tbody tr.pm-supp-row').length;
                                                var badge = document.getElementById('badgeCount');
                                                if (badge) badge.innerText = currentRows + ' Vendors';
                                                var kpi = document.getElementById('kpiTotalSupp');
                                                if (kpi) kpi.innerText = currentRows;
                                            });
                                        }

                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end',
                                            icon: 'success',
                                            title: 'Supplier removed successfully',
                                            showConfirmButton: false,
                                            timer: 1500
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Delete Failed',
                                            text: 'Could not delete supplier. Please try again.',
                                            confirmButtonColor: '#0f172a'
                                        });
                                    }
                                },
                                error: function() {
                                    // Fallback to GET redirect
                                    window.location.href = 'delSupplier.php?id=' + personId;
                                }
                            });
                        }
                    });
                }

                // Export table to CSV
                function exportSuppliersCSV() {
                    var table = document.getElementById('suppliersTable');
                    if (!table) return;

                    var csv = [];
                    var rows = table.querySelectorAll('tr');

                    rows.forEach(function(row) {
                        if (row.classList.contains('pm-empty-row') || row.style.display === 'none') return;
                        var cols = row.querySelectorAll('th, td');
                        var rowData = [];
                        cols.forEach(function(col) {
                            if (col.classList.contains('d-print-none')) return;
                            var text = col.innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/"/g, '""').trim();
                            rowData.push('"' + text + '"');
                        });
                        if (rowData.length > 0) {
                            csv.push(rowData.join(','));
                        }
                    });

                    var csvContent = "data:text/csv;charset=utf-8," + encodeURIComponent(csv.join("\n"));
                    var link = document.createElement("a");
                    link.setAttribute("href", csvContent);
                    link.setAttribute("download", "Suppliers_Directory_" + (new Date().toISOString().split('T')[0]) + ".csv");
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
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
