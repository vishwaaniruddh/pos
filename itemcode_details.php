<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

$con = null;
if (file_exists(__DIR__ . '/db_connection.php')) {
    include_once(__DIR__ . '/db_connection.php');
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

// Global KPI Metrics (Single fast aggregated query)
$kpi = [
    'total_items'     => 0,
    'instock_items'   => 0,
    'total_quantity'  => 0,
    'inventory_value' => 0,
    'total_cats'      => 0
];

$categories_list = [];

if ($con) {
    // 1. KPI Stats
    $q_kpi = mysqli_query($con, "
        SELECT 
            COUNT(*) as total_items,
            COALESCE(SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END), 0) as instock_items,
            COALESCE(SUM(quantity), 0) as total_quantity,
            COALESCE(SUM(CASE WHEN quantity > 0 THEN quantity * unit_price ELSE 0 END), 0) as inventory_value,
            COUNT(DISTINCT category) as total_cats
        FROM phppos_items 
        WHERE is_deleted = 0
    ");
    if ($q_kpi && $rkpi = mysqli_fetch_assoc($q_kpi)) {
        $kpi['total_items']     = intval($rkpi['total_items']);
        $kpi['instock_items']   = intval($rkpi['instock_items']);
        $kpi['total_quantity']  = floatval($rkpi['total_quantity']);
        $kpi['inventory_value'] = floatval($rkpi['inventory_value']);
        $kpi['total_cats']      = intval($rkpi['total_cats']);
    }

    // 2. Distinct Categories for Filter Dropdown
    $q_cat = mysqli_query($con, "
        SELECT DISTINCT category 
        FROM phppos_items 
        WHERE is_deleted = 0 AND category != '' AND category IS NOT NULL 
        ORDER BY category ASC
    ");
    if ($q_cat) {
        while ($rc = mysqli_fetch_assoc($q_cat)) {
            $categories_list[] = $rc['category'];
        }
    }
}

// Request Parameters
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$cat_filter   = isset($_GET['category']) ? trim($_GET['category']) : '';
$stock_filter = isset($_GET['stock_status']) ? trim($_GET['stock_status']) : 'all'; // all, instock, outstock, lowstock
$limit        = isset($_GET['limit']) ? max(10, min(500, intval($_GET['limit']))) : 50;
$page         = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset       = ($page - 1) * $limit;

// Build WHERE Clause
$where_clauses = ["i.is_deleted = 0"];

if ($search !== '') {
    $search_esc = mysqli_real_escape_string($con, $search);
    $where_clauses[] = "(i.name LIKE '%$search_esc%' OR i.description LIKE '%$search_esc%' OR s.company_name LIKE '%$search_esc%' OR i.category LIKE '%$search_esc%')";
}

if ($cat_filter !== '') {
    $cat_esc = mysqli_real_escape_string($con, $cat_filter);
    $where_clauses[] = "i.category = '$cat_esc'";
}

if ($stock_filter === 'instock') {
    $where_clauses[] = "i.quantity > 0";
} else if ($stock_filter === 'outstock') {
    $where_clauses[] = "i.quantity <= 0";
} else if ($stock_filter === 'lowstock') {
    $where_clauses[] = "i.quantity > 0 AND i.quantity <= 2";
}

$where_sql = implode(' AND ', $where_clauses);

// Count Total Filtered Records
$total_filtered = 0;
if ($con) {
    $q_count = mysqli_query($con, "
        SELECT COUNT(*) as cnt 
        FROM phppos_items i 
        LEFT JOIN phppos_suppliers s ON i.supplier_id = s.person_id 
        WHERE $where_sql
    ");
    if ($q_count && $rcnt = mysqli_fetch_assoc($q_count)) {
        $total_filtered = intval($rcnt['cnt']);
    }
}

$total_pages = ($total_filtered > 0) ? ceil($total_filtered / $limit) : 1;
if ($page > $total_pages) $page = $total_pages;

// Fetch Page Records with Supplier JOIN
$items_list = [];
if ($con && $total_filtered > 0) {
    $q_items = mysqli_query($con, "
        SELECT i.*, s.company_name as supplier_name 
        FROM phppos_items i 
        LEFT JOIN phppos_suppliers s ON i.supplier_id = s.person_id 
        WHERE $where_sql 
        ORDER BY i.item_id DESC 
        LIMIT $limit OFFSET $offset
    ");
    if ($q_items) {
        while ($row = mysqli_fetch_assoc($q_items)) {
            $items_list[] = $row;
        }
    }
}

if (file_exists(__DIR__ . '/top-header.php')) include_once(__DIR__ . '/top-header.php');
if (file_exists(__DIR__ . '/top-navbar.php')) include_once(__DIR__ . '/top-navbar.php');
?>

<div class="container-fluid page-body-wrapper">
    <?php
    if (file_exists(__DIR__ . '/navbar.php')) include_once(__DIR__ . '/navbar.php');
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

                /* Filter Card */
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
                    padding: 16px 20px;
                }

                /* Form Controls */
                .pm-filter-row {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 12px;
                    align-items: flex-end;
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

                .pm-select, .pm-input {
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

                .pm-select:focus, .pm-input:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                /* Buttons */
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

                /* Table Styling */
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
                    padding: 11px 14px;
                    border-bottom: 1px solid var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    vertical-align: middle;
                }

                .pm-table tr:hover td {
                    background-color: var(--pm-slate-50);
                }

                /* Status Pills */
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

                .pm-badge-instock {
                    background: #f0fdf4;
                    color: #15803d;
                    border: 1px solid #bbf7d0;
                }

                .pm-badge-outstock {
                    background: #fef2f2;
                    color: #b91c1c;
                    border: 1px solid #fecaca;
                }

                .pm-badge-lowstock {
                    background: #fffbeb;
                    color: #b45309;
                    border: 1px solid #fde68a;
                }

                .pm-badge-neutral {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
                }

                /* Pagination */
                .pm-pagination-bar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 12px;
                    padding: 14px 20px;
                    border-top: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                }

                .pm-page-link {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    height: 32px;
                    min-width: 32px;
                    padding: 0 8px;
                    font-size: 12px;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 4px;
                    background: #ffffff;
                    color: var(--pm-slate-700);
                    text-decoration: none;
                    transition: all 0.15s ease;
                }

                .pm-page-link:hover, .pm-page-link.active {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                .pm-page-link.disabled {
                    opacity: 0.4;
                    pointer-events: none;
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
                    max-width: 480px;
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

                /* Alert */
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

                /* Print */
                @media print {
                    .navbar, #sidebar, .pm-filter-row, .pm-btn, .pm-pagination-bar, .no-print {
                        display: none !important;
                    }
                    .main-panel {
                        margin-left: 0 !important;
                        width: 100% !important;
                    }
                    .content-wrapper {
                        padding: 0 !important;
                        background: #ffffff !important;
                    }
                    .pm-kpi-grid {
                        grid-template-columns: repeat(5, 1fr) !important;
                        gap: 8px !important;
                    }
                }
            </style>

            <div class="itemcode-details-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-barcode" style="margin-right: 10px; font-size: 20px; color: var(--pm-slate-700);"></i>
                            Item Code & Inventory Master
                        </h1>
                        <p class="pm-page-subtitle">
                            Browse, search, edit product SKUs, verify on-hand stock quantities, and audit purchase supplier records.
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;" class="no-print">
                        <button type="button" class="pm-btn pm-btn-secondary" onclick="exportToCSV();">
                            <i class="fa fa-file-csv"></i> Export Visible CSV
                        </button>
                        <button type="button" class="pm-btn pm-btn-secondary" onclick="window.print();">
                            <i class="fa fa-print"></i> Print
                        </button>
                        <a href="reports/stock.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-boxes-stacked"></i> Stock Report
                        </a>
                    </div>
                </div>

                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                    <div class="pm-alert-success">
                        <i class="fa fa-circle-check"></i>
                        <span>Item code details and quantity updated successfully!</span>
                    </div>
                <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
                    <div class="pm-alert-success">
                        <i class="fa fa-circle-check"></i>
                        <span>Item code removed from catalog successfully.</span>
                    </div>
                <?php endif; ?>

                <!-- 5 KPI Metric Cards - 1 Single Row -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Catalog SKUs</span>
                            <div class="pm-kpi-icon"><i class="fa fa-tag"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['total_items']) ?></div>
                        <div class="pm-kpi-sub">Active registered item codes</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">In-Stock SKUs</span>
                            <div class="pm-kpi-icon"><i class="fa fa-box-open"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['instock_items']) ?></div>
                        <div class="pm-kpi-sub"><?= number_format($kpi['total_items'] - $kpi['instock_items']) ?> out of stock</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Stock Units on Hand</span>
                            <div class="pm-kpi-icon"><i class="fa fa-cubes"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['total_quantity']) ?></div>
                        <div class="pm-kpi-sub">Cumulative inventory pieces</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Inventory Valuation</span>
                            <div class="pm-kpi-icon"><i class="fa fa-inr"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['inventory_value']) ?></div>
                        <div class="pm-kpi-sub">Retail selling worth</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Product Categories</span>
                            <div class="pm-kpi-icon"><i class="fa fa-layer-group"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['total_cats']) ?></div>
                        <div class="pm-kpi-sub">Distinct active lines</div>
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="pm-card no-print">
                    <div class="pm-card-header">
                        <h2 class="pm-card-title">
                            <i class="fa-solid fa-filter"></i> Search & Inventory Filters
                        </h2>
                    </div>
                    <div class="pm-card-body">
                        <form method="GET" action="itemcode_details.php" id="filterForm">
                            <div class="pm-filter-row">
                                <!-- Search Input -->
                                <div class="pm-form-group" style="flex: 2; min-width: 200px;">
                                    <label class="pm-label" for="search">Keyword Search</label>
                                    <div style="position: relative;">
                                        <i class="fa fa-search" style="position: absolute; left: 10px; top: 11px; color: var(--pm-slate-400); font-size: 12px;"></i>
                                        <input type="text" name="search" id="search" class="pm-input" style="padding-left: 30px; width: 100%;" placeholder="Search ItemCode, Supplier, Date..." value="<?= htmlspecialchars($search) ?>">
                                    </div>
                                </div>

                                <!-- Category Filter -->
                                <div class="pm-form-group" style="flex: 1.5; min-width: 180px;">
                                    <label class="pm-label" for="category">Category</label>
                                    <select name="category" id="category" class="pm-select">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories_list as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($cat_filter === $cat) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Stock Status Filter -->
                                <div class="pm-form-group" style="width: 150px;">
                                    <label class="pm-label" for="stock_status">Stock Status</label>
                                    <select name="stock_status" id="stock_status" class="pm-select">
                                        <option value="all" <?= ($stock_filter === 'all') ? 'selected' : '' ?>>All Inventory</option>
                                        <option value="instock" <?= ($stock_filter === 'instock') ? 'selected' : '' ?>>In Stock (>0)</option>
                                        <option value="outstock" <?= ($stock_filter === 'outstock') ? 'selected' : '' ?>>Zero Stock (0)</option>
                                        <option value="lowstock" <?= ($stock_filter === 'lowstock') ? 'selected' : '' ?>>Low Stock (1-2)</option>
                                    </select>
                                </div>

                                <!-- Rows Per Page -->
                                <div class="pm-form-group" style="width: 110px;">
                                    <label class="pm-label" for="limit">Page Size</label>
                                    <select name="limit" id="limit" class="pm-select">
                                        <option value="25" <?= ($limit === 25) ? 'selected' : '' ?>>25</option>
                                        <option value="50" <?= ($limit === 50) ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= ($limit === 100) ? 'selected' : '' ?>>100</option>
                                        <option value="250" <?= ($limit === 250) ? 'selected' : '' ?>>250</option>
                                    </select>
                                </div>

                                <!-- Action Buttons -->
                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="pm-btn pm-btn-primary">
                                        <i class="fa fa-magnifying-glass"></i> Filter
                                    </button>
                                    <a href="itemcode_details.php" class="pm-btn pm-btn-secondary">
                                        <i class="fa fa-rotate-left"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Items Table Card -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <h2 class="pm-card-title">
                                <i class="fa-solid fa-table-list"></i> Catalog Items
                            </h2>
                            <span class="pm-badge pm-badge-neutral">
                                <?= number_format($total_filtered) ?> Matching SKUs
                            </span>
                        </div>
                        <div style="font-size: 12px; color: var(--pm-slate-500);">
                            Page <?= $page ?> of <?= $total_pages ?>
                        </div>
                    </div>

                    <div class="pm-table-container">
                        <table class="pm-table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th style="width: 120px;">Item Code / SKU</th>
                                    <th>Category</th>
                                    <th>Supplier</th>
                                    <th style="width: 130px;">Date of Purchase</th>
                                    <th style="width: 110px; text-align: right;">Unit Price (₹)</th>
                                    <th style="width: 100px; text-align: right;">Quantity</th>
                                    <th style="width: 110px; text-align: center;">Stock Status</th>
                                    <th style="width: 100px; text-align: center;" class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items_list)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 40px 20px; color: var(--pm-slate-400);">
                                            <i class="fa fa-folder-open" style="font-size: 28px; margin-bottom: 8px; display: block; color: var(--pm-slate-300);"></i>
                                            No item codes found matching the filter criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $i = $offset + 1;
                                    foreach ($items_list as $item): 
                                        $id        = intval($item['item_id']);
                                        $item_code = htmlspecialchars($item['name'] ?: '—');
                                        $cat_name  = htmlspecialchars($item['category'] ?: 'Uncategorized');
                                        $supp_name = htmlspecialchars($item['supplier_name'] ?: '—');
                                        $pur_date  = htmlspecialchars($item['description'] ?: '—');
                                        $price     = floatval($item['unit_price']);
                                        $qty       = floatval($item['quantity']);

                                        if ($qty > 2) {
                                            $stock_badge = '<span class="pm-badge pm-badge-instock"><i class="fa fa-circle-check"></i> In Stock</span>';
                                        } else if ($qty > 0) {
                                            $stock_badge = '<span class="pm-badge pm-badge-lowstock"><i class="fa fa-triangle-exclamation"></i> Low Stock</span>';
                                        } else {
                                            $stock_badge = '<span class="pm-badge pm-badge-outstock"><i class="fa fa-circle-xmark"></i> Out of Stock</span>';
                                        }
                                    ?>
                                        <tr id="row_<?= $id ?>">
                                            <td style="font-family: monospace; color: var(--pm-slate-400);"><?= $i ?></td>
                                            
                                            <!-- Item Code -->
                                            <td>
                                                <span style="font-family: ui-monospace, monospace; font-weight: 700; color: var(--pm-slate-900);">
                                                    <?= $item_code ?>
                                                </span>
                                            </td>

                                            <!-- Category -->
                                            <td>
                                                <span class="pm-badge pm-badge-neutral" id="cat_disp_<?= $id ?>">
                                                    <?= $cat_name ?>
                                                </span>
                                            </td>

                                            <!-- Supplier -->
                                            <td style="color: var(--pm-slate-700);">
                                                <?= $supp_name ?>
                                            </td>

                                            <!-- Purchase Date / Memo -->
                                            <td style="font-family: monospace; font-size: 12px; color: var(--pm-slate-600);">
                                                <?= $pur_date ?>
                                            </td>

                                            <!-- Unit Price -->
                                            <td style="text-align: right; font-family: monospace; font-weight: 600; color: var(--pm-slate-900);">
                                                ₹ <?= number_format($price, 2) ?>
                                            </td>

                                            <!-- Quantity -->
                                            <td style="text-align: right; font-family: monospace; font-weight: 700; color: <?= ($qty > 0) ? 'var(--pm-slate-900)' : '#b91c1c' ?>;" id="qty_disp_<?= $id ?>">
                                                <?= number_format($qty) ?>
                                            </td>

                                            <!-- Stock Status -->
                                            <td style="text-align: center;">
                                                <?= $stock_badge ?>
                                            </td>

                                            <!-- Action Buttons -->
                                            <td style="text-align: center;" class="no-print">
                                                <div style="display: inline-flex; gap: 6px;">
                                                    <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" title="Quick Edit Item" onclick="openEditModal(<?= $id ?>, '<?= addslashes($item_code) ?>', '<?= addslashes($cat_name) ?>', <?= $qty ?>);">
                                                        <i class="fa fa-pen-to-square"></i>
                                                    </button>
                                                    <a href="itemcode_delete.php?id=<?= $id ?>" class="pm-btn pm-btn-danger pm-btn-sm" title="Delete Item" onclick="return confirm('Are you sure you want to delete item <?= addslashes($item_code) ?>?');">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        $i++;
                                    endforeach; 
                                    ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Bar -->
                    <?php if ($total_pages > 1): 
                        $query_params = $_GET;
                        function get_page_url($p, $params) {
                            $params['page'] = $p;
                            return 'itemcode_details.php?' . http_build_query($params);
                        }
                    ?>
                        <div class="pm-pagination-bar no-print">
                            <div style="font-size: 13px; color: var(--pm-slate-500);">
                                Showing <strong><?= $offset + 1 ?></strong> to <strong><?= min($total_filtered, $offset + $limit) ?></strong> of <strong><?= number_format($total_filtered) ?></strong> SKUs
                            </div>
                            <div style="display: flex; gap: 4px; align-items: center;">
                                <a href="<?= get_page_url(1, $query_params) ?>" class="pm-page-link <?= ($page <= 1) ? 'disabled' : '' ?>" title="First Page">
                                    <i class="fa fa-angles-left"></i>
                                </a>
                                <a href="<?= get_page_url($page - 1, $query_params) ?>" class="pm-page-link <?= ($page <= 1) ? 'disabled' : '' ?>" title="Previous Page">
                                    <i class="fa fa-angle-left"></i>
                                </a>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page   = min($total_pages, $page + 2);
                                for ($p = $start_page; $p <= $end_page; $p++):
                                ?>
                                    <a href="<?= get_page_url($p, $query_params) ?>" class="pm-page-link <?= ($p === $page) ? 'active' : '' ?>">
                                        <?= $p ?>
                                    </a>
                                <?php endfor; ?>

                                <a href="<?= get_page_url($page + 1, $query_params) ?>" class="pm-page-link <?= ($page >= $total_pages) ? 'disabled' : '' ?>" title="Next Page">
                                    <i class="fa fa-angle-right"></i>
                                </a>
                                <a href="<?= get_page_url($total_pages, $query_params) ?>" class="pm-page-link <?= ($page >= $total_pages) ? 'disabled' : '' ?>" title="Last Page">
                                    <i class="fa fa-angles-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Quick Edit Modal -->
            <div id="editModal" class="pm-modal-backdrop" style="display: none;">
                <div class="pm-modal">
                    <div class="pm-modal-header">
                        <h3 class="pm-card-title">
                            <i class="fa-solid fa-pen-to-square"></i> Edit Item Code: <span id="modalItemCode" style="font-family: monospace;"></span>
                        </h3>
                        <button type="button" style="background: none; border: none; font-size: 16px; cursor: pointer; color: var(--pm-slate-400);" onclick="closeEditModal();">
                            <i class="fa fa-xmark"></i>
                        </button>
                    </div>
                    <form id="editItemForm" onsubmit="submitEditForm(event);">
                        <input type="hidden" name="itemid" id="modalItemId" value="">
                        <input type="hidden" name="is_ajax" value="1">
                        
                        <div class="pm-modal-body" style="display: flex; flex-direction: column; gap: 14px;">
                            <div class="pm-form-group">
                                <label class="pm-label" for="modalQuantity">Quantity / Stock Units <span style="color: #ef4444;">*</span></label>
                                <input type="number" step="any" name="quantity" id="modalQuantity" class="pm-input" required>
                            </div>
                            
                            <div class="pm-form-group">
                                <label class="pm-label" for="modalCategory">Category <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="category" id="modalCategory" class="pm-input" required list="modalCatList">
                                <datalist id="modalCatList">
                                    <?php foreach ($categories_list as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <div class="pm-modal-footer">
                            <button type="button" class="pm-btn pm-btn-secondary" onclick="closeEditModal();">
                                Cancel
                            </button>
                            <button type="submit" class="pm-btn pm-btn-primary" id="modalSubmitBtn">
                                <i class="fa fa-check"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function openEditModal(itemId, itemCode, category, quantity) {
                    document.getElementById('modalItemId').value = itemId;
                    document.getElementById('modalItemCode').innerText = itemCode;
                    document.getElementById('modalCategory').value = category;
                    document.getElementById('modalQuantity').value = quantity;
                    document.getElementById('editModal').style.display = 'flex';
                }

                function closeEditModal() {
                    document.getElementById('editModal').style.display = 'none';
                }

                function submitEditForm(e) {
                    e.preventDefault();
                    const btn = document.getElementById('modalSubmitBtn');
                    btn.disabled = true;

                    const formData = $(e.target).serialize();
                    const itemId = document.getElementById('modalItemId').value;

                    $.ajax({
                        url: 'ItemCodeEdit_process.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(res) {
                            btn.disabled = false;
                            if (res.success) {
                                // Update row directly
                                const qtyCell = document.getElementById('qty_disp_' + itemId);
                                const catCell = document.getElementById('cat_disp_' + itemId);
                                if (qtyCell) qtyCell.innerText = res.quantity;
                                if (catCell) catCell.innerText = res.category;
                                closeEditModal();
                                alert("Item updated successfully!");
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

                function exportToCSV() {
                    const rows = document.querySelectorAll('#itemsTable tr');
                    let csv = [];
                    rows.forEach(row => {
                        let rowData = [];
                        const cols = row.querySelectorAll('th, td');
                        cols.forEach(col => {
                            if (col.classList.contains('no-print')) return;
                            let text = col.innerText.replace(/(\r\n|\n|\r)/gm, ' ').trim();
                            text = text.replace(/"/g, '""');
                            rowData.push(`"${text}"`);
                        });
                        if (rowData.length > 0) csv.push(rowData.join(','));
                    });

                    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
                    const downloadLink = document.createElement('a');
                    downloadLink.download = `itemcode_catalog_${new Date().toISOString().slice(0, 10)}.csv`;
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
