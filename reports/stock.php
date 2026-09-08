<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

$is_export = (isset($_GET['export']) && $_GET['export'] == '1') || (isset($_REQUEST['export']) && $_REQUEST['export'] == '1');

$con = null;
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
    if (function_exists('OpenSrishringarrCon')) {
        $con = OpenSrishringarrCon();
    }
} elseif (file_exists('../db_connection.php')) {
    include_once('../db_connection.php');
    if (function_exists('OpenSrishringarrCon')) {
        $con = OpenSrishringarrCon();
    }
}

if (!function_exists('safe_str')) {
    function safe_str($v) {
        return trim((string)($v ?? ''));
    }
}

// Request parameters (support legacy icode, cid, barid and modern params)
$item_code_param   = safe_str($_REQUEST['icode'] ?? ($_REQUEST['q'] ?? ''));
$category_param    = safe_str($_REQUEST['category'] ?? ($_REQUEST['cid'] ?? ''));
$barcode_param     = safe_str($_REQUEST['barcode'] ?? ($_REQUEST['barid'] ?? ''));
$stock_level_param = safe_str($_REQUEST['stock_level'] ?? 'all'); // 'all', 'in_stock', 'low_stock', 'out_of_stock'

$limit_param       = safe_str($_REQUEST['limit'] ?? '50');
$is_unlimited      = ($limit_param === 'all');
$per_page          = $is_unlimited ? 999999 : intval($limit_param);
if ($per_page <= 0) $per_page = 50;
if ($per_page > 500 && !$is_unlimited) $per_page = 250;

$current_page      = max(1, intval($_REQUEST['page'] ?? 1));
$offset            = ($current_page - 1) * $per_page;

// Build SQL WHERE Conditions
$where_clauses = ["i.is_deleted = 0"];

if ($item_code_param !== '') {
    $item_esc = mysqli_real_escape_string($con, $item_code_param);
    $where_clauses[] = "(i.name LIKE '%$item_esc%' OR i.item_number LIKE '%$item_esc%' OR i.description LIKE '%$item_esc%')";
}

if ($category_param !== '' && $category_param !== 'all') {
    $cat_esc = mysqli_real_escape_string($con, $category_param);
    $where_clauses[] = "i.category = '$cat_esc'";
}

if ($barcode_param !== '') {
    $bar_esc = mysqli_real_escape_string($con, $barcode_param);
    $where_clauses[] = "(i.item_number LIKE '%$bar_esc%' OR i.item_id = '$bar_esc')";
}

if ($stock_level_param === 'in_stock') {
    $where_clauses[] = "i.quantity > 0";
} elseif ($stock_level_param === 'low_stock') {
    $where_clauses[] = "(i.quantity > 0 AND i.quantity <= i.reorder_level)";
} elseif ($stock_level_param === 'out_of_stock') {
    $where_clauses[] = "i.quantity <= 0";
}

$where_sql = implode(" AND ", $where_clauses);

// Scope Summary & KPI Metrics
$kpi = [
    'total_skus'     => 0,
    'total_qty'      => 0,
    'total_cost_val' => 0,
    'total_mrp_val'  => 0,
    'out_of_stock'   => 0,
    'in_stock_skus'  => 0
];

if ($con) {
    $sql_kpi = "
        SELECT 
            COUNT(i.item_id) as total_skus,
            COALESCE(SUM(i.quantity), 0) as total_qty,
            COALESCE(SUM(i.cost_price * i.quantity), 0) as total_cost_val,
            COALESCE(SUM(i.unit_price * i.quantity), 0) as total_mrp_val,
            COALESCE(SUM(CASE WHEN i.quantity <= 0 THEN 1 ELSE 0 END), 0) as out_of_stock,
            COALESCE(SUM(CASE WHEN i.quantity > 0 THEN 1 ELSE 0 END), 0) as in_stock_skus
        FROM phppos_items i
        WHERE $where_sql
    ";
    $res_kpi = mysqli_query($con, $sql_kpi);
    if ($res_kpi && $rkpi = mysqli_fetch_assoc($res_kpi)) {
        $kpi['total_skus']     = intval($rkpi['total_skus'] ?? 0);
        $kpi['total_qty']      = floatval($rkpi['total_qty'] ?? 0);
        $kpi['total_cost_val'] = floatval($rkpi['total_cost_val'] ?? 0);
        $kpi['total_mrp_val']  = floatval($rkpi['total_mrp_val'] ?? 0);
        $kpi['out_of_stock']   = intval($rkpi['out_of_stock'] ?? 0);
        $kpi['in_stock_skus']  = intval($rkpi['in_stock_skus'] ?? 0);
    }
}

// Server-side CSV Export
if ($is_export) {
    if ($con) {
        $export_sql = "
            SELECT i.*
            FROM phppos_items i
            WHERE $where_sql
            ORDER BY i.name ASC
        ";
        $export_res = mysqli_query($con, $export_sql);
        if ($export_res) {
            $filename = "Stock_Report_" . date('Ymd_His') . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Item Code / Name', 'Category', 'Barcode / Item Number', 'Cost Price (₹)',
                'Retail MRP (₹)', 'Available Qty', 'Reorder Level', 'Inventory Cost Valuation (₹)',
                'Inventory MRP Valuation (₹)', 'Stock Status'
            ]);

            while ($erow = mysqli_fetch_assoc($export_res)) {
                $qty  = floatval($erow['quantity'] ?? 0);
                $cost = floatval($erow['cost_price'] ?? 0);
                $mrp  = floatval($erow['unit_price'] ?? 0);
                $reorder = floatval($erow['reorder_level'] ?? 0);
                $cost_val = $cost * $qty;
                $mrp_val  = $mrp * $qty;

                $st = ($qty <= 0) ? 'Out of Stock' : (($qty <= $reorder) ? 'Low Stock' : 'In Stock');

                fputcsv($output, [
                    $erow['name'] ?? '',
                    $erow['category'] ?? 'General',
                    $erow['item_number'] ?? '—',
                    $cost,
                    $mrp,
                    $qty,
                    $reorder,
                    $cost_val,
                    $mrp_val,
                    $st
                ]);
            }
            fclose($output);
            exit;
        }
    }
}

// Active Categories List for Filter Dropdown
$category_options = [];
if ($con) {
    $q_cat = mysqli_query($con, "SELECT DISTINCT category FROM phppos_items WHERE is_deleted = 0 AND category != '' ORDER BY category ASC");
    if ($q_cat) {
        while ($rcat = mysqli_fetch_row($q_cat)) {
            $category_options[] = $rcat[0];
        }
    }
}

// Pagination & Query Execution
$total_records = $kpi['total_skus'];
$total_pages   = $is_unlimited ? 1 : max(1, ceil($total_records / $per_page));
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $per_page;
}

$rows = [];
if ($con && $total_records > 0) {
    $sql_data = "
        SELECT i.*
        FROM phppos_items i
        WHERE $where_sql
        ORDER BY i.name ASC
        LIMIT $offset, $per_page
    ";
    $res_data = mysqli_query($con, $sql_data);
    if ($res_data) {
        while ($r = mysqli_fetch_assoc($res_data)) {
            $rows[] = $r;
        }
    }
}

// Calculate Page Row Subtotals
$page_qty_sum      = 0;
$page_cost_val_sum = 0;
$page_mrp_val_sum  = 0;
foreach ($rows as $r) {
    $q   = floatval($r['quantity'] ?? 0);
    $c_p = floatval($r['cost_price'] ?? 0);
    $m_p = floatval($r['unit_price'] ?? 0);

    $page_qty_sum      += $q;
    $page_cost_val_sum += ($c_p * $q);
    $page_mrp_val_sum  += ($m_p * $q);
}

if (file_exists(__DIR__ . '/../top-header.php')) include_once(__DIR__ . '/../top-header.php');
elseif (file_exists('../top-header.php')) include_once('../top-header.php');

if (file_exists(__DIR__ . '/../top-navbar.php')) include_once(__DIR__ . '/../top-navbar.php');
elseif (file_exists('../top-navbar.php')) include_once('../top-navbar.php');
?>

<div class="container-fluid page-body-wrapper">
    <?php
    if (file_exists(__DIR__ . '/../navbar.php')) include_once(__DIR__ . '/../navbar.php');
    elseif (file_exists('../navbar.php')) include_once('../navbar.php');
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

                /* Header */
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

                /* Card Surface */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 20px;
                    overflow: hidden;
                }

                .pm-card-body {
                    padding: 16px 20px;
                }

                /* Filter Toolbar */
                .pm-filter-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                    gap: 12px;
                    align-items: flex-end;
                }

                .pm-form-group {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }

                .pm-label {
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    color: var(--pm-slate-600);
                    margin: 0;
                }

                .pm-input, .pm-select {
                    height: 36px;
                    padding: 0 10px;
                    font-size: 13px;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    background: #ffffff;
                    color: var(--pm-slate-800);
                    outline: none;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                    width: 100%;
                    box-sizing: border-box;
                }

                .pm-input:focus, .pm-select:focus {
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
                    height: 28px;
                    padding: 0 8px;
                    font-size: 11px;
                    border-radius: 4px;
                    gap: 4px;
                }

                /* Table Toolbar */
                .pm-table-toolbar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 12px;
                    padding: 12px 20px;
                    background: var(--pm-slate-50);
                    border-bottom: 1px solid var(--pm-slate-200);
                }

                .pm-search-input {
                    height: 34px;
                    padding: 0 12px 0 34px;
                    font-size: 13px;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    background: #ffffff;
                    width: 280px;
                    outline: none;
                }

                .pm-search-input:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                .pm-search-wrapper {
                    position: relative;
                    display: inline-block;
                }

                .pm-search-wrapper i {
                    position: absolute;
                    left: 11px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: var(--pm-slate-400);
                    font-size: 13px;
                }

                /* Table Styles */
                .pm-table-container {
                    overflow-x: auto;
                    background: #ffffff;
                }

                .pm-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 12px;
                    text-align: left;
                    white-space: nowrap;
                }

                .pm-table th {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-700);
                    font-weight: 600;
                    padding: 11px 14px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                }

                .pm-table td {
                    padding: 11px 14px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
                    vertical-align: middle;
                }

                .pm-table tbody tr {
                    transition: background-color 0.1s ease;
                }

                .pm-table tbody tr:hover {
                    background-color: #f1f5f9 !important;
                }

                .pm-table-footer {
                    background: var(--pm-slate-50);
                    font-weight: 700;
                    border-top: 2px solid var(--pm-slate-200);
                    color: var(--pm-slate-900);
                }

                .pm-table-footer td {
                    padding: 12px 14px;
                }

                /* Badges & Pills */
                .pm-bill-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-weight: 600;
                    font-size: 12px;
                    color: var(--pm-slate-900);
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    padding: 2px 7px;
                    border-radius: 4px;
                }

                .pm-badge-neutral {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    padding: 2px 7px;
                    font-size: 11px;
                    font-weight: 500;
                    border-radius: 4px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-600);
                }

                .pm-status-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    padding: 2px 8px;
                    font-size: 11px;
                    font-weight: 500;
                    border-radius: 4px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
                }

                .pm-status-dot {
                    width: 6px;
                    height: 6px;
                    border-radius: 50%;
                    background: var(--pm-slate-400);
                }

                .pm-status-dot.in-stock {
                    background: #10b981;
                }

                .pm-status-dot.low-stock {
                    background: #f59e0b;
                }

                .pm-status-dot.out-of-stock {
                    background: #ef4444;
                }

                /* Pagination Bar */
                .pm-pagination-bar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 12px 20px;
                    background: #ffffff;
                    border-top: 1px solid var(--pm-slate-200);
                    flex-wrap: wrap;
                    gap: 12px;
                }

                .pm-pagination-info {
                    font-size: 12px;
                    color: var(--pm-slate-500);
                }

                .pm-pagination-links {
                    display: flex;
                    gap: 4px;
                    align-items: center;
                }

                .pm-page-link {
                    min-width: 32px;
                    height: 32px;
                    padding: 0 8px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: 500;
                    border-radius: 6px;
                    border: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                    color: var(--pm-slate-700);
                    text-decoration: none;
                    transition: all 0.15s ease;
                }

                .pm-page-link:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900);
                }

                .pm-page-link.active {
                    background: var(--pm-slate-900);
                    border-color: var(--pm-slate-900);
                    color: #ffffff;
                }

                .pm-page-link.disabled {
                    opacity: 0.4;
                    pointer-events: none;
                    cursor: not-allowed;
                }

                /* Print Styles */
                @media print {
                    .navbar, #sidebar, .pm-page-header .pm-btn, .pm-card, .pm-table-toolbar, .pm-pagination-bar, .pm-btn {
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
                    .pm-table th, .pm-table td {
                        padding: 5px 8px !important;
                        font-size: 10px !important;
                    }
                    .pm-kpi-grid {
                        grid-template-columns: repeat(5, 1fr) !important;
                        gap: 8px !important;
                    }
                    .stock-container {
                        max-width: 100% !important;
                    }
                }
            </style>

            <div class="stock-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-boxes-stacked" style="margin-right: 10px; font-size: 20px; color: var(--pm-slate-700);"></i>
                            Stock & Inventory Report
                        </h1>
                        <p class="pm-page-subtitle">
                            Real-time warehouse stock ledger, valuation at cost & MRP, reorder levels, and catalog availability.
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button type="button" class="pm-btn pm-btn-secondary" onclick="window.print();">
                            <i class="fa fa-print"></i> Print Report
                        </button>
                        <a href="stock.php?export=1&<?= http_build_query($_GET) ?>" class="pm-btn pm-btn-primary" id="btnExportCSV">
                            <i class="fa fa-download"></i> Export CSV
                        </a>
                    </div>
                </div>

                <!-- 5 KPI Metric Cards - 1 Single Row -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Catalog SKUs</span>
                            <div class="pm-kpi-icon"><i class="fa fa-layer-group"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['total_skus']) ?></div>
                        <div class="pm-kpi-sub">Active inventory items</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">In-Stock SKUs</span>
                            <div class="pm-kpi-icon"><i class="fa fa-cubes"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['in_stock_skus']) ?></div>
                        <div class="pm-kpi-sub"><?= number_format($kpi['total_qty']) ?> total units in hand</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Valuation (Cost)</span>
                            <div class="pm-kpi-icon"><i class="fa fa-inr"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['total_cost_val']) ?></div>
                        <div class="pm-kpi-sub">Total stock value at cost</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Valuation (MRP)</span>
                            <div class="pm-kpi-icon"><i class="fa fa-tags"></i></div>
                        </div>
                        <div class="pm-kpi-value">₹ <?= number_format($kpi['total_mrp_val']) ?></div>
                        <div class="pm-kpi-sub">Total retail value at MRP</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Out of Stock SKUs</span>
                            <div class="pm-kpi-icon"><i class="fa fa-triangle-exclamation"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['out_of_stock']) ?></div>
                        <div class="pm-kpi-sub">Items needing replenishment</div>
                    </div>
                </div>

                <!-- Filter Toolbar Card -->
                <div class="pm-card">
                    <div class="pm-card-body">
                        <form id="filterForm" method="GET" action="stock.php">
                            <div class="pm-filter-grid">

                                <!-- Item Code / Keyword -->
                                <div class="pm-form-group" style="min-width: 160px;">
                                    <label class="pm-label" for="icode">Item Code / Keyword</label>
                                    <input type="text" class="pm-input" id="icode" name="icode" 
                                           placeholder="e.g. YNG278 or Aynl40" 
                                           value="<?= htmlspecialchars($item_code_param) ?>">
                                </div>

                                <!-- Category Dropdown -->
                                <div class="pm-form-group" style="min-width: 150px;">
                                    <label class="pm-label" for="category">Category</label>
                                    <select class="pm-select" id="category" name="category">
                                        <option value="all" <?= ($category_param === '' || $category_param === 'all') ? 'selected' : '' ?>>All Categories</option>
                                        <?php foreach ($category_options as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($category_param === $cat) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Barcode / Item Number -->
                                <div class="pm-form-group">
                                    <label class="pm-label" for="barcode">Barcode / SKU No</label>
                                    <input type="text" class="pm-input" id="barcode" name="barcode" 
                                           placeholder="e.g. SIPT or 22269" 
                                           value="<?= htmlspecialchars($barcode_param) ?>">
                                </div>

                                <!-- Stock Level -->
                                <div class="pm-form-group" style="max-width: 140px;">
                                    <label class="pm-label" for="stock_level">Stock Status</label>
                                    <select class="pm-select" id="stock_level" name="stock_level">
                                        <option value="all" <?= ($stock_level_param === 'all') ? 'selected' : '' ?>>All Items</option>
                                        <option value="in_stock" <?= ($stock_level_param === 'in_stock') ? 'selected' : '' ?>>In Stock (> 0)</option>
                                        <option value="low_stock" <?= ($stock_level_param === 'low_stock') ? 'selected' : '' ?>>Low Stock</option>
                                        <option value="out_of_stock" <?= ($stock_level_param === 'out_of_stock') ? 'selected' : '' ?>>Out of Stock (0)</option>
                                    </select>
                                </div>

                                <!-- Rows Per Page -->
                                <div class="pm-form-group" style="max-width: 110px;">
                                    <label class="pm-label" for="limit">Rows</label>
                                    <select class="pm-select" id="limit" name="limit">
                                        <option value="50" <?= ($limit_param === '50') ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= ($limit_param === '100') ? 'selected' : '' ?>>100</option>
                                        <option value="250" <?= ($limit_param === '250') ? 'selected' : '' ?>>250</option>
                                        <option value="all" <?= ($limit_param === 'all') ? 'selected' : '' ?>>All (<?= number_format($total_records) ?>)</option>
                                    </select>
                                </div>

                                <!-- Actions -->
                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="pm-btn pm-btn-primary" style="flex: 1;" name="submit" value="Search">
                                        <i class="fa fa-filter"></i> Apply
                                    </button>
                                    <a href="stock.php" class="pm-btn pm-btn-secondary" title="Clear all filters">
                                        <i class="fa fa-rotate-left"></i>
                                    </a>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

                <!-- Main Table Card -->
                <div class="pm-card">

                    <!-- Table Toolbar -->
                    <div class="pm-table-toolbar">
                        <div class="pm-search-wrapper">
                            <i class="fa fa-magnifying-glass"></i>
                            <input type="text" id="tableSearch" class="pm-search-input" placeholder="Quick filter loaded rows...">
                        </div>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <span class="pm-badge-neutral">
                                Scope: <?= number_format($total_records) ?> SKUs
                            </span>
                            <span class="pm-badge-neutral" id="visibleRowCountBadge">
                                Showing <?= count($rows) ?> rows
                            </span>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="pm-table-container">
                        <table class="pm-table" id="stockTable">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Item Code / SKU</th>
                                    <th>Category</th>
                                    <th>Barcode</th>
                                    <th style="text-align: right;">Cost Price (₹)</th>
                                    <th style="text-align: right;">Retail MRP (₹)</th>
                                    <th style="text-align: right;">Available Qty</th>
                                    <th style="text-align: right;">Cost Valuation (₹)</th>
                                    <th style="text-align: right;">MRP Valuation (₹)</th>
                                    <th>Stock Status</th>
                                    <th style="text-align: center; width: 110px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr>
                                        <td colspan="11" style="text-align: center; padding: 40px 20px; color: var(--pm-slate-500);">
                                            <i class="fa fa-boxes-stacked" style="font-size: 28px; color: var(--pm-slate-300); margin-bottom: 8px; display: block;"></i>
                                            No stock records found matching the current filter criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $row_idx = $offset + 1;
                                    foreach ($rows as $row):
                                        $name    = $row['name'];
                                        $cat     = $row['category'] ?: 'General';
                                        $barcode = $row['item_number'] ?: '—';
                                        $cost    = floatval($row['cost_price'] ?? 0);
                                        $mrp     = floatval($row['unit_price'] ?? 0);
                                        $qty     = floatval($row['quantity'] ?? 0);
                                        $reorder = floatval($row['reorder_level'] ?? 0);

                                        $cost_val = $cost * $qty;
                                        $mrp_val  = $mrp * $qty;

                                        $is_out = ($qty <= 0);
                                        $is_low = (!$is_out && $reorder > 0 && $qty <= $reorder);
                                    ?>
                                    <tr class="stock-row">
                                        <td style="color: var(--pm-slate-400); font-family: ui-monospace, monospace;"><?= $row_idx ?></td>
                                        <td>
                                            <span class="pm-bill-pill">
                                                <i class="fa fa-tag" style="font-size: 9px; opacity: 0.6;"></i> <?= htmlspecialchars($name) ?>
                                            </span>
                                            <span style="font-size: 10px; color: var(--pm-slate-400); margin-left: 4px;">(#<?= $row['item_id'] ?>)</span>
                                        </td>
                                        <td>
                                            <span class="pm-badge-neutral"><?= htmlspecialchars($cat) ?></span>
                                        </td>
                                        <td style="font-family: ui-monospace, monospace; color: var(--pm-slate-600);">
                                            <?= htmlspecialchars($barcode) ?>
                                        </td>
                                        <td style="text-align: right; font-family: ui-monospace, monospace; color: var(--pm-slate-700);">
                                            ₹ <?= number_format($cost) ?>
                                        </td>
                                        <td style="text-align: right; font-family: ui-monospace, monospace; font-weight: 600; color: var(--pm-slate-900);">
                                            ₹ <?= number_format($mrp) ?>
                                        </td>
                                        <td style="text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: <?= $is_out ? '#ef4444' : ($is_low ? '#f59e0b' : 'var(--pm-slate-900)') ?>;">
                                            <?= number_format($qty) ?>
                                        </td>
                                        <td style="text-align: right; font-family: ui-monospace, monospace; color: var(--pm-slate-700);">
                                            ₹ <?= number_format($cost_val) ?>
                                        </td>
                                        <td style="text-align: right; font-family: ui-monospace, monospace; font-weight: 600; color: var(--pm-slate-900);">
                                            ₹ <?= number_format($mrp_val) ?>
                                        </td>
                                        <td>
                                            <?php if ($is_out): ?>
                                                <span class="pm-status-pill">
                                                    <span class="pm-status-dot out-of-stock"></span> Out of Stock
                                                </span>
                                            <?php elseif ($is_low): ?>
                                                <span class="pm-status-pill">
                                                    <span class="pm-status-dot low-stock"></span> Low Stock
                                                </span>
                                            <?php else: ?>
                                                <span class="pm-status-pill">
                                                    <span class="pm-status-dot in-stock"></span> In Stock
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="item_sales_report.php?item_code=<?= urlencode($name) ?>" class="pm-btn pm-btn-secondary pm-btn-sm" title="View Sales & Rent Ledger">
                                                <i class="fa fa-chart-line"></i> Ledger
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                        $row_idx++;
                                    endforeach;
                                    ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($rows)): ?>
                            <tfoot>
                                <tr class="pm-table-footer">
                                    <td colspan="6" style="text-align: right; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">
                                        Page Subtotal (<?= count($rows) ?> items):
                                    </td>
                                    <td style="text-align: right; font-family: ui-monospace, monospace; font-size: 13px;">
                                        <?= number_format($page_qty_sum) ?>
                                    </td>
                                    <td style="text-align: right; font-family: ui-monospace, monospace; font-size: 13px;">
                                        ₹ <?= number_format($page_cost_val_sum) ?>
                                    </td>
                                    <td style="text-align: right; font-family: ui-monospace, monospace; font-size: 13px;">
                                        ₹ <?= number_format($page_mrp_val_sum) ?>
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr class="pm-table-footer" style="background: var(--pm-slate-100); border-top: 1px solid var(--pm-slate-300);">
                                    <td colspan="6" style="text-align: right; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--pm-slate-800);">
                                        Scope Grand Total (<?= number_format($total_records) ?> SKUs):
                                    </td>
                                    <td style="text-align: right; font-family: ui-monospace, monospace; font-size: 14px; font-weight: 800; color: var(--pm-slate-900);">
                                        <?= number_format($kpi['total_qty']) ?>
                                    </td>
                                    <td style="text-align: right; font-family: ui-monospace, monospace; font-size: 14px; font-weight: 800; color: var(--pm-slate-900);">
                                        ₹ <?= number_format($kpi['total_cost_val']) ?>
                                    </td>
                                    <td style="text-align: right; font-family: ui-monospace, monospace; font-size: 14px; font-weight: 800; color: var(--pm-slate-900);">
                                        ₹ <?= number_format($kpi['total_mrp_val']) ?>
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>

                    <!-- Pagination Bar -->
                    <?php if (!$is_unlimited && $total_pages > 1): ?>
                    <div class="pm-pagination-bar">
                        <div class="pm-pagination-info">
                            Showing <?= number_format($offset + 1) ?> to <?= number_format(min($total_records, $offset + $per_page)) ?> of <?= number_format($total_records) ?> entries
                        </div>
                        <div class="pm-pagination-links">
                            <?php
                            $query_params = $_GET;

                            // Previous Button
                            $prev_page = max(1, $current_page - 1);
                            $query_params['page'] = $prev_page;
                            $prev_url = 'stock.php?' . http_build_query($query_params);
                            ?>
                            <a href="<?= $prev_url ?>" class="pm-page-link <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                <i class="fa fa-chevron-left" style="font-size: 10px;"></i>
                            </a>

                            <?php
                            $start_p = max(1, $current_page - 2);
                            $end_p   = min($total_pages, $current_page + 2);

                            if ($start_p > 1) {
                                $query_params['page'] = 1;
                                echo '<a href="stock.php?' . http_build_query($query_params) . '" class="pm-page-link">1</a>';
                                if ($start_p > 2) echo '<span class="pm-page-link disabled">&hellip;</span>';
                            }

                            for ($p = $start_p; $p <= $end_p; $p++) {
                                $query_params['page'] = $p;
                                $p_url = 'stock.php?' . http_build_query($query_params);
                                $act = ($p == $current_page) ? 'active' : '';
                                echo '<a href="' . $p_url . '" class="pm-page-link ' . $act . '">' . $p . '</a>';
                            }

                            if ($end_p < $total_pages) {
                                if ($end_p < $total_pages - 1) echo '<span class="pm-page-link disabled">&hellip;</span>';
                                $query_params['page'] = $total_pages;
                                echo '<a href="stock.php?' . http_build_query($query_params) . '" class="pm-page-link">' . $total_pages . '</a>';
                            }

                            // Next Button
                            $next_page = min($total_pages, $current_page + 1);
                            $query_params['page'] = $next_page;
                            $next_url = 'stock.php?' . http_build_query($query_params);
                            ?>
                            <a href="<?= $next_url ?>" class="pm-page-link <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                <i class="fa fa-chevron-right" style="font-size: 10px;"></i>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

            </div>

            <script>
                // Instant Client-side Table Search (Debounced 200ms)
                (function() {
                    const searchInput = document.getElementById('tableSearch');
                    const rows = document.querySelectorAll('#stockTable tbody tr.stock-row');
                    const countBadge = document.getElementById('visibleRowCountBadge');
                    let timeout = null;

                    if (!searchInput) return;

                    searchInput.addEventListener('input', function() {
                        clearTimeout(timeout);
                        timeout = setTimeout(function() {
                            const term = searchInput.value.toLowerCase().trim();
                            let visibleCount = 0;

                            rows.forEach(function(row) {
                                const text = row.textContent.toLowerCase();
                                if (term === '' || text.indexOf(term) !== -1) {
                                    row.style.display = '';
                                    visibleCount++;
                                } else {
                                    row.style.display = 'none';
                                }
                            });

                            if (countBadge) {
                                countBadge.textContent = 'Showing ' + visibleCount + ' rows';
                            }
                        }, 200);
                    });
                })();
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