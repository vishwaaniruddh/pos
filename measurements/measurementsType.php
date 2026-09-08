<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

$con = null;
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
    if (function_exists('OpenSrishringarrCon')) {
        $con = OpenSrishringarrCon();
    }
}

if (!$con) {
    $is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);
    if ($is_local) {
        $con = @mysqli_connect("localhost", "root", "", "u464193275_srishringarr");
    } else {
        $con = @mysqli_connect("localhost", "u464193275_sarmicropos", "Mypos1234", "u464193275_srishringarr");
    }
}

$alert_msg = '';
$alert_type = '';

// Handle POST Add New Measurement
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $measure_name = trim($_POST['measure_name'] ?? '');
    
    if ($measure_name !== '' && $con) {
        $safe_name = $con->real_escape_string($measure_name);
        
        // Check if exists as Active
        $check_sql = "SELECT * FROM `measurements` WHERE `measure_name` = '$safe_name' AND `activityStatus` = 'Active'";
        $check_res = $con->query($check_sql);
        
        if ($check_res && $check_res->num_rows > 0) {
            $alert_msg = "Measurement attribute '$measure_name' already exists in active status.";
            $alert_type = 'error';
        } else {
            // Check if exists as Deleted, if so reactivate
            $check_del = $con->query("SELECT * FROM `measurements` WHERE `measure_name` = '$safe_name' AND `activityStatus` = 'Deleted'");
            if ($check_del && $check_del->num_rows > 0) {
                $row_del = $check_del->fetch_assoc();
                $del_id = $row_del['measure_id'];
                $con->query("UPDATE `measurements` SET `activityStatus` = 'Active' WHERE `measure_id` = $del_id");
                $alert_msg = "Measurement attribute '$measure_name' was restored to active status!";
                $alert_type = 'success';
            } else {
                $ins_sql = "INSERT INTO `measurements` (`measure_name`, `activityStatus`) VALUES ('$safe_name', 'Active')";
                if ($con->query($ins_sql)) {
                    $alert_msg = "Measurement attribute '$measure_name' added successfully!";
                    $alert_type = 'success';
                } else {
                    $alert_msg = "Database error: " . $con->error;
                    $alert_type = 'error';
                }
            }
        }
    }
}

// KPI Metrics
$kpi = [
    'active_count'   => 0,
    'products_count' => 0,
    'specs_count'    => 0,
    'archived_count' => 0,
    'total_count'    => 0
];

$measurements_list = [];

if ($con) {
    // 1. KPI Counts
    $q_kpi1 = $con->query("
        SELECT 
            COALESCE(SUM(CASE WHEN activityStatus = 'Active' THEN 1 ELSE 0 END), 0) as active_cnt,
            COALESCE(SUM(CASE WHEN activityStatus = 'Deleted' THEN 1 ELSE 0 END), 0) as archived_cnt,
            COUNT(*) as total_cnt
        FROM `measurements`
    ");
    if ($q_kpi1 && $rk = $q_kpi1->fetch_assoc()) {
        $kpi['active_count']   = intval($rk['active_cnt']);
        $kpi['archived_count'] = intval($rk['archived_cnt']);
        $kpi['total_count']    = intval($rk['total_cnt']);
    }

    $q_kpi2 = $con->query("
        SELECT 
            COUNT(DISTINCT product_id) as prod_cnt,
            COUNT(*) as spec_cnt
        FROM `product_measurements`
    ");
    if ($q_kpi2 && $rk2 = $q_kpi2->fetch_assoc()) {
        $kpi['products_count'] = intval($rk2['prod_cnt']);
        $kpi['specs_count']    = intval($rk2['spec_cnt']);
    }

    // 2. Fetch Measurements with Usage Count
    $q_list = $con->query("
        SELECT 
            m.measure_id, 
            m.measure_name, 
            m.activityStatus,
            COUNT(pm.product_measure_id) as usage_count
        FROM `measurements` m
        LEFT JOIN `product_measurements` pm ON m.measure_id = pm.measure_id
        GROUP BY m.measure_id, m.measure_name, m.activityStatus
        ORDER BY (m.activityStatus = 'Active') DESC, m.measure_name ASC
    ");
    if ($q_list) {
        while ($row = $q_list->fetch_assoc()) {
            $measurements_list[] = $row;
        }
    }
}

if (file_exists(__DIR__ . '/../top-header.php')) include_once(__DIR__ . '/../top-header.php');
if (file_exists(__DIR__ . '/../top-navbar.php')) include_once(__DIR__ . '/../top-navbar.php');
?>

<div class="container-fluid page-body-wrapper">
    <?php
    if (file_exists(__DIR__ . '/../navbar.php')) include_once(__DIR__ . '/../navbar.php');
    ?>
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"
                integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

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

                /* Page Header */
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

                .pm-page-subtitle {
                    font-size: 13px;
                    color: var(--pm-slate-500);
                    margin-top: 4px;
                    margin-bottom: 0;
                }

                /* 5 KPI Metric Cards - 1 Single Row */
                .pm-kpi-grid {
                    display: grid;
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                    gap: 12px;
                    margin-bottom: 20px;
                }

                @media (max-width: 1200px) {
                    .pm-kpi-grid {
                        grid-template-columns: repeat(3, minmax(0, 1fr));
                    }
                }

                @media (max-width: 768px) {
                    .pm-kpi-grid {
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                    }
                }

                @media (max-width: 480px) {
                    .pm-kpi-grid {
                        grid-template-columns: 1fr;
                    }
                }

                .pm-kpi-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 14px 16px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    min-width: 0;
                }

                .pm-kpi-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 6px;
                    margin-bottom: 6px;
                }

                .pm-kpi-label {
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                    color: var(--pm-slate-500);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .pm-kpi-icon {
                    width: 28px;
                    height: 28px;
                    border-radius: 6px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--pm-slate-600);
                    font-size: 12px;
                    flex-shrink: 0;
                }

                .pm-kpi-value {
                    font-size: 20px;
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
                    margin-top: 4px;
                    line-height: 1.3;
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
                    flex-wrap: wrap;
                    gap: 12px;
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
                    padding: 18px 20px;
                }

                /* Inputs and Buttons */
                .pm-input {
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

                .pm-input:focus {
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

                .pm-btn-sm {
                    height: 30px;
                    padding: 0 10px;
                    font-size: 12px;
                    border-radius: 4px;
                }

                .pm-btn-danger {
                    background: #ffffff;
                    color: #dc2626;
                    border-color: #fecaca;
                }

                .pm-btn-danger:hover {
                    background: #fef2f2;
                    border-color: #dc2626;
                }

                /* Table */
                .pm-table-container {
                    overflow-x: auto;
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
                    letter-spacing: 0.03em;
                    padding: 10px 14px;
                    text-align: left;
                    border-bottom: 1px solid var(--pm-slate-200);
                    white-space: nowrap;
                }

                .pm-table td {
                    padding: 12px 14px;
                    border-bottom: 1px solid var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    vertical-align: middle;
                }

                .pm-table tr:hover td {
                    background-color: var(--pm-slate-50);
                }

                /* Badges */
                .pm-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    padding: 2px 8px;
                    font-size: 11px;
                    font-weight: 500;
                    border-radius: 4px;
                    white-space: nowrap;
                }

                .pm-badge-active {
                    background: #f0fdf4;
                    color: #15803d;
                    border: 1px solid #bbf7d0;
                }

                .pm-badge-archived {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-500);
                    border: 1px solid var(--pm-slate-200);
                }

                .pm-badge-neutral {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
                }

                /* Filter Tabs */
                .pm-filter-tab {
                    padding: 5px 12px;
                    font-size: 12px;
                    font-weight: 500;
                    border-radius: 4px;
                    cursor: pointer;
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-600);
                    border: 1px solid var(--pm-slate-200);
                    transition: all 0.15s ease;
                }

                .pm-filter-tab.active {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                /* Alert */
                .pm-alert {
                    padding: 10px 16px;
                    border-radius: 6px;
                    font-size: 13px;
                    margin-bottom: 16px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .pm-alert-success {
                    background: #f0fdf4;
                    border: 1px solid #bbf7d0;
                    color: #15803d;
                }

                .pm-alert-error {
                    background: #fef2f2;
                    border: 1px solid #fecaca;
                    color: #b91c1c;
                }

                /* Modal */
                .pm-modal-backdrop {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(15, 23, 42, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    backdrop-filter: blur(2px);
                }

                .pm-modal {
                    background: #ffffff;
                    border-radius: 8px;
                    width: 100%;
                    max-width: 440px;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    border: 1px solid var(--pm-slate-200);
                }

                .pm-modal-header {
                    padding: 16px 20px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .pm-modal-body {
                    padding: 20px;
                }

                .pm-modal-footer {
                    padding: 14px 20px;
                    background: var(--pm-slate-50);
                    border-top: 1px solid var(--pm-slate-200);
                    display: flex;
                    justify-content: flex-end;
                    gap: 8px;
                }
            </style>

            <div class="measurements-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-ruler-combined" style="margin-right: 10px; font-size: 20px; color: var(--pm-slate-700);"></i>
                            Measurement Attributes Master
                        </h1>
                        <p class="pm-page-subtitle">
                            Configure standard measurement fields (Bust, Waist, Hips, Length, etc.) for garment customization and rental fittings.
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button type="button" class="pm-btn pm-btn-secondary" onclick="exportToCSV();">
                            <i class="fa fa-file-csv"></i> Export CSV
                        </button>
                        <a href="measurementCategory.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-shirt"></i> Garment Category Setup
                        </a>
                        <a href="garmentProduct.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-tags"></i> Product Specs
                        </a>
                    </div>
                </div>

                <?php if ($alert_msg): ?>
                    <div class="pm-alert pm-alert-<?= $alert_type ?>">
                        <i class="fa <?= ($alert_type === 'success') ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                        <span><?= htmlspecialchars($alert_msg) ?></span>
                    </div>
                <?php endif; ?>

                <!-- 5 KPI Metric Cards - 1 Single Row -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Active Attributes</span>
                            <div class="pm-kpi-icon"><i class="fa fa-check"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['active_count']) ?></div>
                        <div class="pm-kpi-sub">Ready for fittings</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Configured Products</span>
                            <div class="pm-kpi-icon"><i class="fa fa-shirt"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['products_count']) ?></div>
                        <div class="pm-kpi-sub">Products with custom specs</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Recorded Specs</span>
                            <div class="pm-kpi-icon"><i class="fa fa-list-ol"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['specs_count']) ?></div>
                        <div class="pm-kpi-sub">Item dimension data points</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Archived Attributes</span>
                            <div class="pm-kpi-icon"><i class="fa fa-box-archive"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['archived_count']) ?></div>
                        <div class="pm-kpi-sub">Hidden from active forms</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Catalog Defined</span>
                            <div class="pm-kpi-icon"><i class="fa fa-tags"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['total_count']) ?></div>
                        <div class="pm-kpi-sub">Cumulative attributes</div>
                    </div>
                </div>

                <!-- Add New Measurement Card -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <h2 class="pm-card-title">
                            <i class="fa-solid fa-plus"></i> Add New Measurement Attribute
                        </h2>
                    </div>
                    <div class="pm-card-body">
                        <form method="POST" action="measurementsType.php" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                            <input type="hidden" name="action" value="add">
                            
                            <div style="flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 5px;">
                                <label class="pm-label" for="measure_name" style="font-size: 12px; font-weight: 500; color: var(--pm-slate-700);">
                                    Measurement Field Name <span style="color: #ef4444;">*</span>
                                </label>
                                <input type="text" name="measure_name" id="measure_name" class="pm-input" placeholder="e.g. Underbust, Sleeve Length, Neck Depth, Waist..." required>
                            </div>

                            <button type="submit" class="pm-btn pm-btn-primary">
                                <i class="fa fa-check"></i> Add Measurement
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Measurements List Card -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <h2 class="pm-card-title">
                                <i class="fa-solid fa-table-list"></i> Defined Measurement Attributes
                            </h2>
                            <span class="pm-badge pm-badge-neutral" id="visibleCountBadge">
                                <?= count($measurements_list) ?> Attributes
                            </span>
                        </div>

                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <!-- Filter Tabs -->
                            <div style="display: flex; gap: 4px;">
                                <button type="button" class="pm-filter-tab active" onclick="filterByStatus('all', this);">All</button>
                                <button type="button" class="pm-filter-tab" onclick="filterByStatus('Active', this);">Active Only</button>
                                <button type="button" class="pm-filter-tab" onclick="filterByStatus('Deleted', this);">Archived</button>
                            </div>

                            <!-- Search Input -->
                            <div style="position: relative; width: 200px;">
                                <i class="fa fa-search" style="position: absolute; left: 10px; top: 11px; color: var(--pm-slate-400); font-size: 12px;"></i>
                                <input type="text" id="tableSearch" class="pm-input" style="padding-left: 28px; height: 32px; font-size: 12px; width: 100%;" placeholder="Search attributes..." oninput="filterTable();">
                            </div>
                        </div>
                    </div>

                    <div class="pm-table-container">
                        <table class="pm-table" id="measurementsTable">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Measurement Attribute</th>
                                    <th style="width: 180px; text-align: center;">Product Specifications</th>
                                    <th style="width: 120px; text-align: center;">Status</th>
                                    <th style="width: 140px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="measurementsTableBody">
                                <?php if (empty($measurements_list)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 40px 20px; color: var(--pm-slate-400);">
                                            No measurement attributes found in catalog.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $idx = 1;
                                    foreach ($measurements_list as $m): 
                                        $id        = intval($m['measure_id']);
                                        $name      = htmlspecialchars($m['measure_name'] ?: '—');
                                        $status    = $m['activityStatus'];
                                        $is_active = ($status === 'Active');
                                        $usage     = intval($m['usage_count']);
                                    ?>
                                        <tr id="row_<?= $id ?>" class="measure-row" data-name="<?= strtolower($name) ?>" data-status="<?= $status ?>">
                                            <td style="font-family: monospace; color: var(--pm-slate-400);"><?= $idx ?></td>
                                            
                                            <!-- Name -->
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <i class="fa fa-ruler-horizontal" style="color: var(--pm-slate-400); font-size: 12px;"></i>
                                                    <span style="font-weight: 600; color: var(--pm-slate-900);" id="name_disp_<?= $id ?>">
                                                        <?= $name ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- Linked Specs Count -->
                                            <td style="text-align: center;">
                                                <span class="pm-badge pm-badge-neutral" style="font-family: monospace;">
                                                    <i class="fa fa-layer-group" style="font-size: 10px;"></i>
                                                    <?= number_format($usage) ?> products
                                                </span>
                                            </td>

                                            <!-- Status -->
                                            <td style="text-align: center;" id="status_cell_<?= $id ?>">
                                                <?php if ($is_active): ?>
                                                    <span class="pm-badge pm-badge-active"><i class="fa fa-circle-check"></i> Active</span>
                                                <?php else: ?>
                                                    <span class="pm-badge pm-badge-archived"><i class="fa fa-box-archive"></i> Archived</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Actions -->
                                            <td style="text-align: center;">
                                                <div style="display: inline-flex; gap: 6px;" id="action_btns_<?= $id ?>">
                                                    <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" title="Edit Attribute" onclick="openEditModal(<?= $id ?>, '<?= addslashes($m['measure_name']) ?>');">
                                                        <i class="fa fa-pen-to-square"></i>
                                                    </button>
                                                    
                                                    <?php if ($is_active): ?>
                                                        <button type="button" class="pm-btn pm-btn-danger pm-btn-sm" title="Archive / Delete" onclick="toggleMeasurementStatus(<?= $id ?>, 'delete');">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" title="Restore to Active" onclick="toggleMeasurementStatus(<?= $id ?>, 'restore');">
                                                            <i class="fa fa-rotate-left"></i> Restore
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        $idx++;
                                    endforeach; 
                                    ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Quick Edit Modal -->
            <div id="editModal" class="pm-modal-backdrop" style="display: none;">
                <div class="pm-modal">
                    <div class="pm-modal-header">
                        <h3 class="pm-card-title">
                            <i class="fa-solid fa-pen-to-square"></i> Edit Measurement Attribute
                        </h3>
                        <button type="button" style="background: none; border: none; font-size: 16px; cursor: pointer; color: var(--pm-slate-400);" onclick="closeEditModal();">
                            <i class="fa fa-xmark"></i>
                        </button>
                    </div>
                    <form id="editForm" onsubmit="submitEdit(event);">
                        <input type="hidden" name="measure_id" id="edit_id" value="">
                        <input type="hidden" name="is_ajax" value="1">
                        
                        <div class="pm-modal-body">
                            <label class="pm-label" for="edit_measure_name" style="font-size: 12px; font-weight: 500; color: var(--pm-slate-700); margin-bottom: 6px; display: block;">
                                Measurement Name <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" class="pm-input" id="edit_measure_name" name="measure_name" style="width: 100%;" required>
                        </div>

                        <div class="pm-modal-footer">
                            <button type="button" class="pm-btn pm-btn-secondary" onclick="closeEditModal();">
                                Cancel
                            </button>
                            <button type="submit" class="pm-btn pm-btn-primary" id="editSubmitBtn">
                                <i class="fa fa-check"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                let currentStatusFilter = 'all';

                function filterByStatus(status, tab) {
                    currentStatusFilter = status;
                    document.querySelectorAll('.pm-filter-tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    filterTable();
                }

                function filterTable() {
                    const query = document.getElementById('tableSearch').value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#measurementsTableBody tr.measure-row');
                    let visibleCount = 0;

                    rows.forEach(tr => {
                        const name = tr.getAttribute('data-name') || '';
                        const status = tr.getAttribute('data-status') || '';

                        const matchesQuery = !query || name.includes(query);
                        const matchesStatus = (currentStatusFilter === 'all') || (status === currentStatusFilter);

                        if (matchesQuery && matchesStatus) {
                            tr.style.display = '';
                            visibleCount++;
                        } else {
                            tr.style.display = 'none';
                        }
                    });

                    document.getElementById('visibleCountBadge').innerText = `${visibleCount} Attributes`;
                }

                function openEditModal(id, name) {
                    document.getElementById('edit_id').value = id;
                    document.getElementById('edit_measure_name').value = name;
                    document.getElementById('editModal').style.display = 'flex';
                }

                function closeEditModal() {
                    document.getElementById('editModal').style.display = 'none';
                }

                function submitEdit(e) {
                    e.preventDefault();
                    const btn = document.getElementById('editSubmitBtn');
                    btn.disabled = true;

                    const id = document.getElementById('edit_id').value;
                    const newName = document.getElementById('edit_measure_name').value.trim();

                    $.ajax({
                        url: 'update_measurement.php',
                        type: 'POST',
                        data: {
                            is_ajax: 1,
                            measure_id: id,
                            measure_name: newName
                        },
                        dataType: 'json',
                        success: function(res) {
                            btn.disabled = false;
                            if (res.success) {
                                document.getElementById('name_disp_' + id).innerText = res.measure_name;
                                const tr = document.getElementById('row_' + id);
                                if (tr) tr.setAttribute('data-name', res.measure_name.toLowerCase());
                                closeEditModal();
                            } else {
                                alert("Update failed: " + (res.message || 'Unknown error'));
                            }
                        },
                        error: function() {
                            btn.disabled = false;
                            alert("Server error during update.");
                        }
                    });
                }

                function toggleMeasurementStatus(id, action) {
                    const label = (action === 'restore') ? 'restore' : 'archive / delete';
                    if (!confirm(`Are you sure you want to ${label} this measurement attribute?`)) {
                        return;
                    }

                    $.ajax({
                        url: 'delete_measurement.php',
                        type: 'POST',
                        data: {
                            is_ajax: 1,
                            measure_id: id,
                            action: action
                        },
                        dataType: 'json',
                        success: function(res) {
                            if (res.success) {
                                const tr = document.getElementById('row_' + id);
                                if (tr) {
                                    tr.setAttribute('data-status', res.status);
                                    const cell = document.getElementById('status_cell_' + id);
                                    const actCell = document.getElementById('action_btns_' + id);
                                    const name = document.getElementById('name_disp_' + id).innerText;

                                    if (res.status === 'Active') {
                                        cell.innerHTML = '<span class="pm-badge pm-badge-active"><i class="fa fa-circle-check"></i> Active</span>';
                                        actCell.innerHTML = `
                                            <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" title="Edit Attribute" onclick="openEditModal(${id}, '${name.replace(/'/g, "\\'")}');">
                                                <i class="fa fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="pm-btn pm-btn-danger pm-btn-sm" title="Archive / Delete" onclick="toggleMeasurementStatus(${id}, 'delete');">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        `;
                                    } else {
                                        cell.innerHTML = '<span class="pm-badge pm-badge-archived"><i class="fa fa-box-archive"></i> Archived</span>';
                                        actCell.innerHTML = `
                                            <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" title="Edit Attribute" onclick="openEditModal(${id}, '${name.replace(/'/g, "\\'")}');">
                                                <i class="fa fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" title="Restore to Active" onclick="toggleMeasurementStatus(${id}, 'restore');">
                                                <i class="fa fa-rotate-left"></i> Restore
                                            </button>
                                        `;
                                    }
                                    filterTable();
                                }
                            } else {
                                alert("Action failed: " + (res.message || 'Unknown error'));
                            }
                        },
                        error: function() {
                            alert("Server communication error.");
                        }
                    });
                }

                function exportToCSV() {
                    const rows = document.querySelectorAll('#measurementsTable tr');
                    let csv = [];
                    rows.forEach(row => {
                        if (row.style.display === 'none') return;
                        let rowData = [];
                        const cols = row.querySelectorAll('th, td');
                        cols.forEach((col, idx) => {
                            if (idx === cols.length - 1) return; // skip action column
                            let text = col.innerText.replace(/(\r\n|\n|\r)/gm, ' ').trim();
                            text = text.replace(/"/g, '""');
                            rowData.push(`"${text}"`);
                        });
                        if (rowData.length > 0) csv.push(rowData.join(','));
                    });

                    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
                    const downloadLink = document.createElement('a');
                    downloadLink.download = `measurements_attributes_${new Date().toISOString().slice(0, 10)}.csv`;
                    downloadLink.href = window.URL.createObjectURL(csvFile);
                    downloadLink.style.display = 'none';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                }
            </script>

        </div><!-- /content-wrapper -->
    </div><!-- /main-panel -->
</div><!-- /container-fluid page-body-wrapper -->

<?php
if ($con) {
    CloseCon($con);
}
?>
</body>
</html>