<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../db_connection.php');

$con = null;
if (function_exists('OpenSrishringarrCon')) {
    $con = OpenSrishringarrCon();
}
if (!$con) {
    $is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);
    if ($is_local) {
        $con = @mysqli_connect("localhost", "root", "", "u464193275_srishringarr");
    } else {
        $con = @mysqli_connect("localhost", "u464193275_sarmicropos", "Mypos1234", "u464193275_srishringarr");
    }
}

// Current month and year defaults
$current_month = date('M');
$current_year  = date('Y');

$selected_month = $_GET['month'] ?? $current_month;
$selected_year  = $_GET['year'] ?? $current_year;

// Include POS shell navigation components
if (file_exists(__DIR__ . '/../top-header.php')) include_once(__DIR__ . '/../top-header.php');
if (file_exists(__DIR__ . '/../top-navbar.php')) include_once(__DIR__ . '/../top-navbar.php');
?>

<div class="container-fluid page-body-wrapper">
    <?php if (file_exists(__DIR__ . '/../navbar.php')) include_once(__DIR__ . '/../navbar.php'); ?>
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.25rem 1.5rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
            <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

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

                body {
                    background-color: var(--pm-slate-50);
                    color: var(--pm-slate-900);
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
                    z-index: 99;
                }

                .main-panel {
                    margin-left: 240px;
                    transition: margin-left 0.2s ease;
                }

                @media (max-width: 991px) {
                    .main-panel {
                        margin-left: 0 !important;
                    }
                }

                /* Header Toolbar */
                .pm-header-toolbar {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 12px;
                    margin-bottom: 16px;
                }

                .pm-page-title {
                    font-size: 19px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 9px;
                    letter-spacing: -0.02em;
                }

                .pm-page-subtitle {
                    font-size: 12.5px;
                    color: var(--pm-slate-500);
                    margin-top: 2px;
                    margin-bottom: 0;
                }

                .pm-toolbar-actions {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    flex-wrap: wrap;
                }

                /* 5 Compact KPI Grid across a single row */
                .pm-kpi-grid {
                    display: grid;
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                    gap: 10px;
                    margin-bottom: 16px;
                }

                @media (max-width: 1200px) {
                    .pm-kpi-grid {
                        grid-template-columns: repeat(3, minmax(0, 1fr));
                    }
                }

                @media (max-width: 768px) {
                    .pm-kpi-grid {
                        grid-template-columns: repeat(1, minmax(0, 1fr));
                    }
                }

                .pm-kpi-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 11px 14px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    transition: border-color 0.15s ease;
                }

                .pm-kpi-card:hover {
                    border-color: var(--pm-slate-300);
                }

                .pm-kpi-top {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 6px;
                }

                .pm-kpi-label {
                    font-size: 10.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    color: var(--pm-slate-500);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .pm-kpi-icon {
                    width: 26px;
                    height: 26px;
                    border-radius: 6px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--pm-slate-600);
                    font-size: 11px;
                    flex-shrink: 0;
                }

                .pm-kpi-value {
                    font-size: 18px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    letter-spacing: -0.02em;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    line-height: 1.2;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .pm-kpi-sub {
                    font-size: 11px;
                    color: var(--pm-slate-500);
                    margin-top: 3px;
                    line-height: 1.2;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                /* Cards */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 16px;
                    overflow: hidden;
                }

                .pm-card-header {
                    padding: 10px 16px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 10px;
                }

                .pm-card-title {
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 7px;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                }

                .pm-card-body {
                    padding: 12px 16px;
                }

                /* Filter Toolbar */
                .pm-filter-row {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    align-items: flex-end;
                }

                .pm-form-group {
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                    flex: 1;
                    min-width: 140px;
                }

                .pm-label {
                    font-size: 10.5px;
                    font-weight: 600;
                    color: var(--pm-slate-600);
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                }

                .pm-input, .pm-select {
                    height: 34px;
                    padding: 0 10px;
                    font-size: 12.5px;
                    color: var(--pm-slate-900);
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    outline: none;
                    transition: all 0.15s ease;
                    width: 100%;
                }

                .pm-input:focus, .pm-select:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                /* Buttons */
                .pm-btn {
                    height: 34px;
                    padding: 0 12px;
                    font-size: 12.5px;
                    font-weight: 500;
                    border-radius: 6px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                    white-space: nowrap;
                    border: 1px solid transparent;
                }

                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff !important;
                    border-color: var(--pm-slate-900);
                }

                .pm-btn-primary:hover {
                    background: var(--pm-slate-800);
                    border-color: var(--pm-slate-800);
                }

                .pm-btn-outline {
                    background: #ffffff;
                    color: var(--pm-slate-700) !important;
                    border-color: var(--pm-slate-200);
                }

                .pm-btn-outline:hover {
                    background: var(--pm-slate-50);
                    border-color: var(--pm-slate-300);
                    color: var(--pm-slate-900) !important;
                }

                /* Table Styling */
                .pm-table-container {
                    overflow-x: auto;
                    background: #ffffff;
                }

                .pm-table {
                    width: 100%;
                    border-collapse: separate;
                    border-spacing: 0;
                    font-size: 12px;
                    text-align: left;
                }

                .pm-table th {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    font-weight: 600;
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    padding: 9px 12px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    white-space: nowrap !important;
                }

                .pm-table td {
                    padding: 9px 12px;
                    border-bottom: 1px solid var(--pm-slate-100);
                    vertical-align: middle;
                    color: var(--pm-slate-800);
                }

                .pm-table tr:hover td {
                    background-color: var(--pm-slate-50);
                }

                .pm-table th.text-right, .pm-table td.text-right {
                    text-align: right;
                }

                .pm-table th.text-center, .pm-table td.text-center {
                    text-align: center;
                }

                .pm-amount {
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-weight: 600;
                    white-space: nowrap !important;
                    color: var(--pm-slate-900);
                }

                .pm-bill-link {
                    color: var(--pm-slate-900);
                    text-decoration: none;
                    white-space: nowrap !important;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-weight: 600;
                    padding: 2px 6px;
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 4px;
                    transition: all 0.15s ease;
                }

                .pm-bill-link:hover {
                    background: var(--pm-slate-200);
                    color: var(--pm-slate-900);
                    text-decoration: none;
                }

                /* Badges */
                .pm-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    padding: 2px 6px;
                    font-size: 10.5px;
                    font-weight: 500;
                    border-radius: 4px;
                    white-space: nowrap;
                }

                .pm-badge-slate {
                    background: var(--pm-slate-200);
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-300);
                }

                .pm-badge-neutral {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-600);
                    border: 1px solid var(--pm-slate-200);
                }

                .pm-badge-rate {
                    background: #f1f5f9;
                    color: #0f172a;
                    border: 1px solid #cbd5e1;
                    font-family: ui-monospace, monospace;
                    font-weight: 600;
                }

                .pm-badge-sku {
                    font-family: ui-monospace, monospace;
                    font-weight: 600;
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-800);
                    padding: 2px 6px;
                }

                /* Empty & Loading States */
                .pm-empty-state {
                    padding: 40px 20px;
                    text-align: center;
                }

                .pm-empty-icon {
                    width: 44px;
                    height: 44px;
                    border-radius: 50%;
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-400);
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 18px;
                    margin-bottom: 10px;
                }

                .pm-empty-title {
                    font-size: 14px;
                    font-weight: 600;
                    color: var(--pm-slate-800);
                    margin-bottom: 4px;
                }

                .pm-empty-desc {
                    font-size: 12.5px;
                    color: var(--pm-slate-500);
                    margin: 0;
                }

                .pm-loading-state {
                    padding: 50px 20px;
                    text-align: center;
                    color: var(--pm-slate-500);
                    font-size: 13px;
                }
            </style>

            <!-- Page Header Toolbar -->
            <div class="pm-header-toolbar">
                <div>
                    <h1 class="pm-page-title">
                        <i class="fa-solid fa-hand-holding-dollar" style="color: var(--pm-slate-700);"></i>
                        SSFS Rent Commission Records
                    </h1>
                    <p class="pm-page-subtitle">Monthly rental order commissions, GST breakdowns, beautician discount deductions, and net SSFS shares</p>
                </div>
                <div class="pm-toolbar-actions">
                    <button type="button" id="btnExportCsv" class="pm-btn pm-btn-primary">
                        <i class="fa-solid fa-file-csv"></i>
                        Export to CSV
                    </button>
                    <a href="commission.php" class="pm-btn pm-btn-outline" title="Reset Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                        Reset
                    </a>
                </div>
            </div>

            <!-- 5 Compact KPI Metric Cards (Single Row) -->
            <div class="pm-kpi-grid">
                <!-- Card 1: Total Orders / Items -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Line Items</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-receipt"></i></div>
                    </div>
                    <div class="pm-kpi-value" id="kpi-total-orders">0</div>
                    <div class="pm-kpi-sub">Filtered rented items</div>
                </div>

                <!-- Card 2: Gross Rent Revenue -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Gross Rent Revenue</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    </div>
                    <div class="pm-kpi-value" id="kpi-total-rent">₹ 0.00</div>
                    <div class="pm-kpi-sub">Base order gross sum</div>
                </div>

                <!-- Card 3: Total GST Output -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">GST Collected</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-percent"></i></div>
                    </div>
                    <div class="pm-kpi-value" id="kpi-total-gst">₹ 0.00</div>
                    <div class="pm-kpi-sub">Applicable output GST</div>
                </div>

                <!-- Card 4: Beautician Discount / Comm -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Beautician Comm</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-scissors"></i></div>
                    </div>
                    <div class="pm-kpi-value" id="kpi-total-beautician">₹ 0.00</div>
                    <div class="pm-kpi-sub">Commission deductions</div>
                </div>

                <!-- Card 5: Net SSFS Share -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Net SSFS Share</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-coins"></i></div>
                    </div>
                    <div class="pm-kpi-value" id="kpi-total-ssfs">₹ 0.00</div>
                    <div class="pm-kpi-sub">Rent minus GST & comm</div>
                </div>
            </div>

            <!-- Filter Toolbar Card -->
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-card-title">
                        <i class="fa-solid fa-filter"></i>
                        Filter Commission Period
                    </h3>
                    <div style="font-size: 11.5px; color: var(--pm-slate-500);">
                        Select month and year to view itemized commission calculations
                    </div>
                </div>
                <div class="pm-card-body">
                    <form id="filterForm" method="POST">
                        <div class="pm-filter-row">
                            <div class="pm-form-group">
                                <label class="pm-label">Month</label>
                                <select id="month" name="month" class="pm-select">
                                    <option value="all">All Months</option>
                                    <?php
                                    $months = [
                                        "Jan" => "January", "Feb" => "February", "Mar" => "March", "Apr" => "April",
                                        "May" => "May", "Jun" => "June", "Jul" => "July", "Aug" => "August",
                                        "Sep" => "September", "Oct" => "October", "Nov" => "November", "Dec" => "December"
                                    ];
                                    foreach ($months as $key => $value) {
                                        echo "<option value='$key' " . ($selected_month == $key ? 'selected' : '') . ">$value</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-label">Year</label>
                                <select id="year" name="year" class="pm-select">
                                    <option value="all">All Years</option>
                                    <?php 
                                    for ($i = date('Y') + 2; $i >= 2018; $i--) {
                                        echo "<option value=\"$i\"" . ($selected_year == $i ? ' selected' : '') . ">$i</option>";
                                    } 
                                    ?>
                                </select>
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-label">Franchise</label>
                                <select id="selectFrachise" name="selectFrachise" class="pm-select">
                                    <option value="0">Srishringarr</option>
                                </select>
                            </div>

                            <div style="display: flex; gap: 6px;">
                                <button type="submit" class="pm-btn pm-btn-primary" id="btnFilter">
                                    <i class="fa-solid fa-magnifying-glass"></i> Filter Records
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Commission Data Table Card -->
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-card-title">
                        <i class="fa-solid fa-table-list"></i>
                        Itemized Commission Report
                    </h3>
                    <div id="table-status-info" style="font-size: 11.5px; color: var(--pm-slate-500);">
                        Loading commission records...
                    </div>
                </div>
                <div class="pm-table-container">
                    <table class="pm-table" id="resultsTable">
                        <!-- Content dynamically loaded via AJAX -->
                    </table>
                </div>
            </div>

            <!-- Interactivity Script -->
            <script>
                function loadCommissionData() {
                    $('#table-status-info').text('Fetching data from database...');
                    $('#resultsTable').html('<tbody><tr><td colspan="17"><div class="pm-loading-state"><i class="fa-solid fa-circle-notch fa-spin fa-2x" style="color: var(--pm-slate-400); margin-bottom: 10px; display: block;"></i>Loading commission data...</div></td></tr></tbody>');

                    $.ajax({
                        url: 'commissionData.php',
                        type: 'POST',
                        data: $('#filterForm').serialize(),
                        success: function(response) {
                            $('#resultsTable').html(response);

                            // Live update 5 KPI metric cards
                            var meta = $('#resultsTable').find('#kpi-data-meta');
                            if (meta.length) {
                                var count = Number(meta.data('count')) || 0;
                                var rent  = Number(meta.data('rent')) || 0;
                                var gst   = Number(meta.data('gst')) || 0;
                                var beaut = Number(meta.data('beautician')) || 0;
                                var ssfs  = Number(meta.data('ssfs')) || 0;

                                $('#kpi-total-orders').text(count.toLocaleString('en-IN'));
                                $('#kpi-total-rent').text('₹ ' + rent.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                                $('#kpi-total-gst').text('₹ ' + gst.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                                $('#kpi-total-beautician').text('₹ ' + beaut.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                                $('#kpi-total-ssfs').text('₹ ' + ssfs.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                                $('#table-status-info').text(count.toLocaleString('en-IN') + ' item records displayed');
                            } else {
                                $('#table-status-info').text('0 items found');
                            }
                        },
                        error: function() {
                            $('#resultsTable').html('<tbody><tr><td colspan="17"><div class="pm-empty-state"><div class="pm-empty-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="pm-empty-title">Error Loading Data</div><p class="pm-empty-desc">An error occurred while communicating with the server. Please try again.</p></div></td></tr></tbody>');
                            $('#table-status-info').text('Error fetching data');
                        }
                    });
                }

                $(document).ready(function() {
                    // Form submit handler
                    $('#filterForm').on('submit', function(e) {
                        e.preventDefault();
                        loadCommissionData();
                    });

                    // Export to CSV handler
                    $('#btnExportCsv').on('click', function() {
                        window.location.href = 'commissionData.php?export=1&' + $('#filterForm').serialize();
                    });

                    // Auto-load on initial page render
                    loadCommissionData();
                });
            </script>

        </div><!-- /content-wrapper -->
    </div><!-- /main-panel -->
</div><!-- /container-fluid page-body-wrapper -->

<?php
if ($con && function_exists('CloseCon')) {
    CloseCon($con);
}
?>
</body>
</html>
