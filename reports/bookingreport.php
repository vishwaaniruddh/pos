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

    $con = null;
    if (file_exists('../db_connection.php')) {
        include_once('../db_connection.php');
        if (function_exists('OpenSrishringarrCon')) {
            $con = OpenSrishringarrCon();
        }
    }

    function safe_str($v) {
        return trim((string)($v ?? ''));
    }

    // KPI Metrics calculation for booking reports
    $kpi_total_rent   = 0;
    $kpi_future_rent  = 0;
    $kpi_next7_rent   = 0;
    $kpi_future_trail = 0;

    if ($con) {
        $q_kpi = mysqli_query($con, "SELECT 
                    COUNT(*) as total_rent, 
                    SUM(CASE WHEN pick_date >= CURDATE() THEN 1 ELSE 0 END) as future_rent, 
                    SUM(CASE WHEN pick_date >= CURDATE() AND pick_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as next7_rent, 
                    SUM(CASE WHEN trail_date >= CURDATE() THEN 1 ELSE 0 END) as future_trail 
                FROM phppos_rent");
        if ($q_kpi && $r_kpi = mysqli_fetch_assoc($q_kpi)) {
            $kpi_total_rent   = intval($r_kpi['total_rent'] ?? 0);
            $kpi_future_rent  = intval($r_kpi['future_rent'] ?? 0);
            $kpi_next7_rent   = intval($r_kpi['next7_rent'] ?? 0);
            $kpi_future_trail = intval($r_kpi['future_trail'] ?? 0);
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

                .booking-report-container {
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

                /* View Switcher Tabs */
                .pm-view-tab {
                    padding: 4px 12px;
                    font-size: 12px;
                    border-radius: 6px;
                    background: transparent;
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-600);
                    cursor: pointer;
                    transition: all 0.15s ease;
                }

                .pm-view-tab:hover, .pm-view-tab.active {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                /* Booking Table */
                .pm-booking-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 0;
                }

                .pm-booking-table th {
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

                .pm-booking-table td {
                    padding: 10px 12px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    vertical-align: middle;
                    font-size: 13px;
                }

                .pm-booking-table tbody tr:hover {
                    background-color: #fafbfc;
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

                .pm-item-chip {
                    display: inline-block;
                    padding: 2px 6px;
                    font-size: 11px;
                    font-weight: 600;
                    background: #ffffff;
                    color: var(--pm-slate-800);
                    border: 1px solid var(--pm-slate-300);
                    border-radius: 4px;
                }

                .pm-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    padding: 3px 8px;
                    font-size: 11px;
                    font-weight: 600;
                    border-radius: 9999px;
                    border: 1px solid transparent;
                }

                .pm-badge-neutral {
                    background: #f1f5f9;
                    color: #0f172a;
                    border-color: #cbd5e1;
                }

                .pm-badge-outline {
                    background: #ffffff;
                    color: #475569;
                    border-color: #cbd5e1;
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

                /* Print Styles */
                @media print {
                    body {
                        background: #ffffff !important;
                        color: #000000 !important;
                    }
                    .top-navbar, #sidebar, .pm-page-header, .pm-kpi-grid, .pm-card-header button, 
                    #filterCard, #searchControlsBar, .footer {
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
                        border: 1px solid #000 !important;
                        box-shadow: none !important;
                    }
                    .pm-booking-table th, .pm-booking-table td {
                        border: 1px solid #000 !important;
                        font-size: 11px !important;
                        padding: 6px !important;
                    }
                    @page {
                        size: landscape;
                        margin: 10mm;
                    }
                }
            </style>

            <div class="booking-report-container">

                <!-- 1. Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa fa-calendar-check-o text-muted" style="font-size: 18px; margin-right: 8px;"></i>
                            Booking Status & Pick-up Report
                        </h1>
                        <p class="pm-page-subtitle">Track rental reservations, pickup and return schedules, customer fittings, and garment availability.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="pm-btn-primary" onclick="printFullReport();">
                            <i class="fa fa-print"></i> Print Report
                        </button>
                        <button type="button" class="pm-btn-outline" onclick="exportBookingTableToCSV();">
                            <i class="fa fa-download"></i> Export CSV
                        </button>
                        <a href="/pos/home_dashboard.php" class="pm-btn-outline">
                            <i class="fa fa-home"></i> POS Dashboard
                        </a>
                    </div>
                </div>

                <!-- 2. KPI Metrics Grid -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Upcoming Bookings</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value"><?php echo number_format($kpi_future_rent); ?></div>
                        <div class="pm-kpi-subtext">Active future pick-ups scheduled</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">This Week Pick-ups</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value"><?php echo number_format($kpi_next7_rent); ?></div>
                        <div class="pm-kpi-subtext">Due within next 7 days</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Upcoming Trials</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"></path>
                                    <line x1="16" y1="8" x2="2" y2="22"></line>
                                    <line x1="17.5" y1="15" x2="9" y2="15"></line>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value"><?php echo number_format($kpi_future_trail); ?></div>
                        <div class="pm-kpi-subtext">Scheduled garment fittings</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Reservations</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value"><?php echo number_format($kpi_total_rent); ?></div>
                        <div class="pm-kpi-subtext">Historical bookings on record</div>
                    </div>
                </div>

                <!-- 3. Filter & Search Panel Card -->
                <div class="pm-card" id="filterCard">
                    <div class="pm-card-header">
                        <h3 class="pm-card-title">
                            <i class="fa fa-filter text-muted" style="margin-right: 6px;"></i>
                            Filter Booking Schedules
                        </h3>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="pm-btn-outline" style="height: 32px; padding: 4px 10px; font-size: 12px;" onclick="resetFilters()">
                                <i class="fa fa-refresh"></i> Reset Filters
                            </button>
                        </div>
                    </div>
                    <div class="pm-card-body">
                        <form id="bookingFilterForm" onsubmit="event.preventDefault(); showdetails();">
                            <div class="row g-3">
                                
                                <!-- From Pick Date -->
                                <div class="col-xl-3 col-lg-3 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="from_date">Select From Pick Date</label>
                                        <input type="date" name="from_date" id="from_date" class="pm-control" />
                                    </div>
                                </div>

                                <!-- To Pick Date -->
                                <div class="col-xl-3 col-lg-3 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="to_date">Select To Pick Date</label>
                                        <input type="date" name="to_date" id="to_date" class="pm-control" />
                                    </div>
                                </div>

                                <!-- Item Code / SKU -->
                                <div class="col-xl-3 col-lg-3 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="barcode">Item Code / SKU</label>
                                        <input type="text" name="barcode" id="barcode" class="pm-control" 
                                               placeholder="Type item code / SKU..." autocomplete="off" />
                                    </div>
                                </div>

                                <!-- Barcode Scanner Input -->
                                <div class="col-xl-3 col-lg-3 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="barcode2">
                                            Barcode Scan <span style="font-size: 11px; color: var(--pm-slate-400);">(Ctrl + B)</span>
                                        </label>
                                        <input type="text" name="barcode2" id="barcode2" class="pm-control" 
                                               placeholder="Scan barcode..." autocomplete="off" />
                                    </div>
                                </div>

                                <!-- Date Preset Chips & Action Button -->
                                <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-2 pt-1">
                                    <div>
                                        <label class="pm-form-label mb-1">Pick Date Quick Presets</label>
                                        <div class="pm-chip-group">
                                            <button type="button" class="pm-chip" onclick="applyPreset('next7')">Next 7 Days</button>
                                            <button type="button" class="pm-chip" onclick="applyPreset('thisMonth')">This Month</button>
                                            <button type="button" class="pm-chip" onclick="applyPreset('next30')">Next 30 Days</button>
                                            <button type="button" class="pm-chip" onclick="applyPreset('futureAll')">All Future</button>
                                            <button type="button" class="pm-chip" onclick="applyPreset('all')">All Time</button>
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

                <!-- 4. Table Controls & Quick Search -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3" id="searchControlsBar">
                    <div class="d-flex align-items-center gap-2" style="min-width: 280px; max-width: 400px; width: 100%;">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-color: var(--pm-slate-200); color: var(--pm-slate-400);">
                                <i class="fa fa-search"></i>
                            </span>
                            <input type="text" id="liveTableSearch" class="form-control border-start-0" 
                                   style="border-color: var(--pm-slate-200); font-size: 13px; height: 36px;"
                                   placeholder="Filter by customer, phone, SKU, or bill..." 
                                   onkeyup="filterBookingRows(this.value);" />
                        </div>
                    </div>
                    <div style="font-size: 12.5px; color: var(--pm-slate-500);">
                        <i class="fa fa-info-circle text-muted me-1"></i> Use <strong>Ctrl + B</strong> to focus the barcode scanner input instantly.
                    </div>
                </div>

                <!-- 5. Dynamic Report Output Container -->
                <div id="back">
                    <div class="pm-loading-state">
                        <div class="pm-spinner"></div>
                        <div>Loading booking schedules...</div>
                    </div>
                </div>

            </div><!-- .booking-report-container -->

            <script>
                // Keyboard shortcut: Ctrl + B focuses barcode
                var isCtrl = false;
                document.addEventListener('keyup', function(e) {
                    if (e.which === 17) isCtrl = false;
                });
                document.addEventListener('keydown', function(e) {
                    if (e.which === 17) isCtrl = true;
                    if ((e.which === 66 || e.key === 'b' || e.key === 'B') && isCtrl === true) {
                        e.preventDefault();
                        var b2 = document.getElementById("barcode2");
                        if (b2) {
                            b2.focus();
                            b2.select();
                        }
                    }
                });

                // Auto-submit on barcode scanner Enter
                $('#barcode2').on('keypress', function(e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        showdetails();
                    }
                });

                // Load initial results on mount
                $(document).ready(function() {
                    showdetails();
                });

                var currentTabMode = 'both';

                function switchBookingTab(mode) {
                    currentTabMode = mode;
                    $('.pm-view-tab').removeClass('active');
                    if (mode === 'both') {
                        $('.booking-section-pane').removeClass('d-none');
                    } else if (mode === 'future') {
                        $('#futureBookingsWrapper').removeClass('d-none');
                        $('#pastBookingsWrapper').addClass('d-none');
                    } else if (mode === 'past') {
                        $('#futureBookingsWrapper').addClass('d-none');
                        $('#pastBookingsWrapper').removeClass('d-none');
                    }
                    $('button.pm-view-tab:contains("' + (mode === 'both' ? 'All' : (mode === 'future' ? 'Upcoming' : 'Past')) + '")').addClass('active');
                }

                // Main AJAX loader
                function showdetails() {
                    var from_date = document.getElementById('from_date').value;
                    var to_date   = document.getElementById('to_date').value;
                    var barcode   = document.getElementById('barcode').value.trim();
                    var barcode2  = document.getElementById('barcode2').value.trim();

                    // If one date is entered but not the other, validate
                    if ((from_date && !to_date) || (!from_date && to_date)) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Date Range Incomplete',
                            text: 'Please select both From pick date and To pick date, or leave both empty.',
                            confirmButtonColor: '#0f172a'
                        });
                        return;
                    }

                    $('#back').html(
                        '<div class="pm-loading-state">' +
                        '  <div class="pm-spinner"></div>' +
                        '  <div>Retrieving rental reservation records...</div>' +
                        '</div>'
                    );

                    $.ajax({
                        url: 'booking_ajax.php',
                        type: 'GET',
                        data: {
                            barcode: barcode,
                            barcode2: barcode2,
                            fromdate: from_date,
                            todate: to_date,
                            tab_mode: currentTabMode
                        },
                        success: function(response) {
                            $('#barcode2').val('');
                            $('#back').html(response);
                            switchBookingTab(currentTabMode);
                        },
                        error: function() {
                            $('#back').html(
                                '<div class="pm-card text-center py-5">' +
                                '  <div class="text-danger mb-2"><i class="fa fa-exclamation-triangle fa-2x"></i></div>' +
                                '  <h5 style="color: #0f172a; font-weight: 700;">Error Loading Bookings</h5>' +
                                '  <p style="color: #64748b; font-size: 13px;">Unable to fetch records. Please verify your connection and try again.</p>' +
                                '  <button type="button" class="pm-btn-outline" onclick="showdetails();">Retry</button>' +
                                '</div>'
                            );
                        }
                    });
                }

                // Quick Date Presets
                function applyPreset(preset) {
                    var now = new Date();
                    var fromInput = document.getElementById('from_date');
                    var toInput   = document.getElementById('to_date');

                    function fmt(d) {
                        var y = d.getFullYear();
                        var m = String(d.getMonth() + 1).padStart(2, '0');
                        var day = String(d.getDate()).padStart(2, '0');
                        return y + '-' + m + '-' + day;
                    }

                    $('.pm-chip').removeClass('active');

                    if (preset === 'next7') {
                        var future7 = new Date();
                        future7.setDate(now.getDate() + 7);
                        fromInput.value = fmt(now);
                        toInput.value = fmt(future7);
                    } else if (preset === 'thisMonth') {
                        var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                        var lastDay  = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                        fromInput.value = fmt(firstDay);
                        toInput.value = fmt(lastDay);
                    } else if (preset === 'next30') {
                        var future30 = new Date();
                        future30.setDate(now.getDate() + 30);
                        fromInput.value = fmt(now);
                        toInput.value = fmt(future30);
                    } else if (preset === 'futureAll') {
                        fromInput.value = fmt(now);
                        toInput.value = '2099-12-31';
                    } else if (preset === 'all') {
                        fromInput.value = '';
                        toInput.value = '';
                    }

                    showdetails();
                }

                // Reset Filters
                function resetFilters() {
                    document.getElementById('from_date').value = '';
                    document.getElementById('to_date').value = '';
                    document.getElementById('barcode').value = '';
                    document.getElementById('barcode2').value = '';
                    document.getElementById('liveTableSearch').value = '';
                    $('.pm-chip').removeClass('active');
                    currentTabMode = 'both';
                    showdetails();
                }

                // Client-side instant filter on table rows
                function filterBookingRows(query) {
                    var q = query.toLowerCase().trim();
                    var rows = document.querySelectorAll('#back tr.booking-row');
                    rows.forEach(function(row) {
                        var text = row.textContent.toLowerCase();
                        if (!q || text.indexOf(q) !== -1) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }

                // Print Helpers
                function printDiv(divId, title) {
                    var content = document.getElementById(divId);
                    if (!content) return;

                    var printWindow = window.open('', '_blank', 'width=1100,height=750');
                    printWindow.document.write('<!DOCTYPE html><html><head><title>' + title + '</title>');
                    printWindow.document.write('<style>');
                    printWindow.document.write('body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; padding: 20px; color: #000; }');
                    printWindow.document.write('h2 { margin: 0 0 14px 0; font-size: 16px; font-weight: bold; border-bottom: 2px solid #000; padding-bottom: 6px; }');
                    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 10px; }');
                    printWindow.document.write('th, td { border: 1px solid #333; padding: 6px 8px; font-size: 11px; text-align: left; }');
                    printWindow.document.write('th { background: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 10px; }');
                    printWindow.document.write('@page { size: landscape; margin: 10mm; }');
                    printWindow.document.write('</style></head><body>');
                    printWindow.document.write('<h2>' + title + ' &bull; Sri Shringarr POS</h2>');
                    printWindow.document.write(content.innerHTML);
                    printWindow.document.write('</body></html>');
                    printWindow.document.close();
                    printWindow.focus();
                    setTimeout(function() {
                        printWindow.print();
                        printWindow.close();
                    }, 350);
                }

                function printFullReport() {
                    window.print();
                }

                // Export Table to CSV
                function exportBookingTableToCSV() {
                    var tables = document.querySelectorAll('#back table.pm-booking-table');
                    if (tables.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Data',
                            text: 'No booking records available to export.',
                            confirmButtonColor: '#0f172a'
                        });
                        return;
                    }

                    var csv = [];
                    tables.forEach(function(table, tableIndex) {
                        var sectionTitle = (tableIndex === 0) ? "UPCOMING BOOKINGS" : "PAST BOOKINGS";
                        csv.push('"' + sectionTitle + '"');

                        var rows = table.querySelectorAll('tr');
                        rows.forEach(function(row) {
                            if (row.style.display === 'none') return;
                            var cols = row.querySelectorAll('th, td');
                            var rowData = [];
                            cols.forEach(function(col) {
                                var text = col.innerText.replace(/"/g, '""').replace(/\r?\n|\r/g, ' ').trim();
                                rowData.push('"' + text + '"');
                            });
                            if (rowData.length > 0) csv.push(rowData.join(','));
                        });
                        csv.push(''); // blank line separator
                    });

                    var csvBlob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
                    var downloadLink = document.createElement('a');
                    downloadLink.href = URL.createObjectURL(csvBlob);
                    downloadLink.download = 'booking_status_report_' + new Date().toISOString().slice(0, 10) + '.csv';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                }
            </script>

        </div><!-- .content-wrapper -->
    </div><!-- .main-panel -->
</div><!-- .page-body-wrapper -->

<?php
if ($con) CloseCon($con);
if (file_exists('../footer.php')) include_once('../footer.php');
?>
