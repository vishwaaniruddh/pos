<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

$con = null;
$web_con = null;

if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
    if (function_exists('OpenSrishringarrCon')) {
        $con = OpenSrishringarrCon();
    }
    if (function_exists('OpenNewSrishringarrCon')) {
        $web_con = OpenNewSrishringarrCon();
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

if (!$web_con) {
    $is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);
    if ($is_local) {
        $web_con = @mysqli_connect("localhost", "root", "", "u464193275_srishrinjewels");
    } else {
        $web_con = @mysqli_connect("localhost", "u464193275_srishrinjuser", "9b@hMgk!=zI", "u464193275_srishrinjewels");
    }
}

// 5 KPI Metrics
$kpi = [
    'categories_count' => 0,
    'configured_skus'  => 0,
    'total_specs'      => 0,
    'active_types'     => 0,
    'total_products'   => 0
];

// 1. Garment Categories count
if ($web_con) {
    $q_cat_cnt = mysqli_query($web_con, "SELECT COUNT(*) as cnt FROM `garments` WHERE `Main_id`=1 OR `Main_id`=3");
    if ($q_cat_cnt && $rc = mysqli_fetch_assoc($q_cat_cnt)) {
        $kpi['categories_count'] = intval($rc['cnt']);
    }

    $q_prod_cnt = mysqli_query($web_con, "SELECT COUNT(*) as cnt FROM `garment_product`");
    if ($q_prod_cnt && $rp = mysqli_fetch_assoc($q_prod_cnt)) {
        $kpi['total_products'] = intval($rp['cnt']);
    }
}

// 2. Measurement specs count from POS db
$product_measure_map = []; // [product_id => count of specs]
if ($con) {
    $q_specs = mysqli_query($con, "
        SELECT 
            COUNT(DISTINCT product_id) as conf_skus,
            COUNT(*) as tot_specs
        FROM `product_measurements`
    ");
    if ($q_specs && $rs = mysqli_fetch_assoc($q_specs)) {
        $kpi['configured_skus'] = intval($rs['conf_skus']);
        $kpi['total_specs']     = intval($rs['tot_specs']);
    }

    $q_types = mysqli_query($con, "SELECT COUNT(*) as cnt FROM `measurements` WHERE `activityStatus` = 'Active'");
    if ($q_types && $rt = mysqli_fetch_assoc($q_types)) {
        $kpi['active_types'] = intval($rt['cnt']);
    }

    // Load measurement counts map for fast lookup
    $q_map = mysqli_query($con, "SELECT product_id, COUNT(*) as cnt FROM `product_measurements` GROUP BY product_id");
    if ($q_map) {
        while ($rm = mysqli_fetch_assoc($q_map)) {
            $product_measure_map[strval($rm['product_id'])] = intval($rm['cnt']);
        }
    }
}

// Fetch Garment Categories for Dropdown
$garments_dropdown = [];
if ($web_con) {
    $q_garments = mysqli_query($web_con, "SELECT garment_id, name FROM `garments` WHERE `Main_id`=1 OR `Main_id`=3 ORDER BY name ASC");
    if ($q_garments) {
        while ($rg = mysqli_fetch_assoc($q_garments)) {
            $garments_dropdown[] = $rg;
        }
    }
}

// Filter Parameters
$sku_query   = isset($_REQUEST['query']) ? trim($_REQUEST['query']) : '';
$garment_id  = isset($_REQUEST['garmentid']) ? intval($_REQUEST['garmentid']) : 0;
$spec_filter = isset($_REQUEST['spec_status']) ? trim($_REQUEST['spec_status']) : 'all'; // all, configured, pending
$view_mode   = isset($_REQUEST['view']) ? trim($_REQUEST['view']) : 'grid'; // grid or table
$page        = isset($_REQUEST['page']) ? max(1, intval($_REQUEST['page'])) : 1;
$limit       = 24;
$offset      = ($page - 1) * $limit;

// Build Query for Garment Products
$products = [];
$total_products = 0;

if ($web_con) {
    $where_parts = ["1=1"];
    
    if ($garment_id > 0) {
        $where_parts[] = "gp.product_for = '$garment_id'";
    }

    if ($sku_query !== '') {
        $safe_query = mysqli_real_escape_string($web_con, $sku_query);
        $where_parts[] = "(gp.gproduct_code LIKE '%$safe_query%' OR gp.gproduct_name LIKE '%$safe_query%')";
    }

    $where_sql = implode(' AND ', $where_parts);

    // Count Total
    $q_total = mysqli_query($web_con, "
        SELECT COUNT(*) as cnt 
        FROM `garment_product` gp 
        WHERE $where_sql
    ");
    if ($q_total && $rtot = mysqli_fetch_assoc($q_total)) {
        $total_products = intval($rtot['cnt']);
    }

    $total_pages = ($total_products > 0) ? ceil($total_products / $limit) : 1;
    if ($page > $total_pages) $page = $total_pages;

    // Fetch Products with Images
    $q_prods = mysqli_query($web_con, "
        SELECT 
            gp.gproduct_id, 
            gp.gproduct_code, 
            gp.gproduct_name, 
            gp.sales_price, 
            gp.rent_price, 
            gp.product_for,
            g.name as category_name,
            (SELECT img_name FROM product_images_new WHERE gproduct_id = gp.gproduct_id LIMIT 1) as img_name
        FROM `garment_product` gp
        LEFT JOIN `garments` g ON gp.product_for = g.garment_id
        WHERE $where_sql
        ORDER BY gp.gproduct_id DESC
        LIMIT $limit OFFSET $offset
    ");

    if ($q_prods) {
        while ($p = mysqli_fetch_assoc($q_prods)) {
            $pid = strval($p['gproduct_id']);
            $specs_count = $product_measure_map[$pid] ?? 0;

            // Apply spec status filter if requested
            if ($spec_filter === 'configured' && $specs_count === 0) continue;
            if ($spec_filter === 'pending' && $specs_count > 0) continue;

            $p['specs_count'] = $specs_count;
            
            // Build image path
            $img_path = $p['img_name'];
            if ($img_path) {
                $p['full_img'] = "https://srishringarr.com/yn/uploads" . $img_path;
            } else {
                $p['full_img'] = "";
            }

            $products[] = $p;
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

                /* Inputs and Buttons */
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

                /* Product Card Grid */
                .pm-product-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                    gap: 16px;
                }

                .pm-product-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    display: flex;
                    flex-direction: column;
                    transition: transform 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-product-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
                    border-color: var(--pm-slate-300);
                }

                .pm-product-img-box {
                    position: relative;
                    width: 100%;
                    height: 260px;
                    background: var(--pm-slate-100);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                }

                .pm-product-img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    transition: transform 0.25s ease;
                }

                .pm-product-card:hover .pm-product-img {
                    transform: scale(1.03);
                }

                .pm-product-body {
                    padding: 14px 16px;
                    display: flex;
                    flex-direction: column;
                    flex: 1;
                    justify-content: space-between;
                }

                .pm-product-sku {
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-size: 13px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                }

                .pm-product-cat {
                    font-size: 11px;
                    font-weight: 600;
                    color: var(--pm-slate-500);
                    text-transform: uppercase;
                    letter-spacing: 0.02em;
                    margin-top: 2px;
                }

                .pm-price-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 8px 0;
                    margin: 8px 0;
                    border-top: 1px solid var(--pm-slate-100);
                    border-bottom: 1px solid var(--pm-slate-100);
                    font-size: 12px;
                }

                .pm-price-item {
                    display: flex;
                    flex-direction: column;
                }

                .pm-price-lbl {
                    font-size: 10px;
                    color: var(--pm-slate-400);
                    text-transform: uppercase;
                }

                .pm-price-val {
                    font-family: ui-monospace, monospace;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    font-size: 13px;
                }

                /* Status Badges */
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

                .pm-badge-configured {
                    background: #f0fdf4;
                    color: #15803d;
                    border: 1px solid #bbf7d0;
                }

                .pm-badge-pending {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-600);
                    border: 1px solid var(--pm-slate-200);
                }

                .pm-badge-neutral {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
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
                    padding: 10px 14px;
                    border-bottom: 1px solid var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    vertical-align: middle;
                }

                .pm-table tr:hover td {
                    background-color: var(--pm-slate-50);
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

                /* View Toggle Button */
                .pm-view-btn {
                    width: 32px;
                    height: 32px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    background: #ffffff;
                    color: var(--pm-slate-600);
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                }

                .pm-view-btn:hover, .pm-view-btn.active {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }
            </style>

            <div class="measurement-category-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-shirt" style="margin-right: 10px; font-size: 20px; color: var(--pm-slate-700);"></i>
                            Garment Fitting & Measurement Directory
                        </h1>
                        <p class="pm-page-subtitle">
                            Locate garments by bridal/designer category or SKU, inspect on-hand pricing, and manage custom tailoring dimensions.
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <a href="measurementsType.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-ruler-combined"></i> Attribute Master
                        </a>
                        <a href="garmentProduct.php" class="pm-btn pm-btn-secondary">
                            <i class="fa fa-tags"></i> Product Specs
                        </a>
                    </div>
                </div>

                <!-- 5 KPI Metric Cards - 1 Single Row -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Garment Categories</span>
                            <div class="pm-kpi-icon"><i class="fa fa-layer-group"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['categories_count']) ?></div>
                        <div class="pm-kpi-sub">Bridal & designer lines</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Garment Products</span>
                            <div class="pm-kpi-icon"><i class="fa fa-shirt"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['total_products']) ?></div>
                        <div class="pm-kpi-sub">Catalog apparel pieces</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Configured with Specs</span>
                            <div class="pm-kpi-icon"><i class="fa fa-circle-check"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['configured_skus']) ?></div>
                        <div class="pm-kpi-sub">Garments with custom sizes</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Recorded Dimension Points</span>
                            <div class="pm-kpi-icon"><i class="fa fa-list-ol"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['total_specs']) ?></div>
                        <div class="pm-kpi-sub">Individual sizing metrics</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Active Attributes</span>
                            <div class="pm-kpi-icon"><i class="fa fa-ruler"></i></div>
                        </div>
                        <div class="pm-kpi-value"><?= number_format($kpi['active_types']) ?></div>
                        <div class="pm-kpi-sub">Standard sizing fields</div>
                    </div>
                </div>

                <!-- Unified Search & Filter Card -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <h2 class="pm-card-title">
                            <i class="fa-solid fa-sliders"></i> Filter Garments by Category or SKU
                        </h2>
                        
                        <!-- View Switcher -->
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <span style="font-size: 11px; color: var(--pm-slate-400); text-transform: uppercase; font-weight: 600; margin-right: 4px;">View:</span>
                            <?php 
                            $query_copy = $_GET;
                            $query_copy['view'] = 'grid';
                            $grid_url = 'measurementCategory.php?' . http_build_query($query_copy);
                            $query_copy['view'] = 'table';
                            $table_url = 'measurementCategory.php?' . http_build_query($query_copy);
                            ?>
                            <a href="<?= $grid_url ?>" class="pm-view-btn <?= ($view_mode !== 'table') ? 'active' : '' ?>" title="Grid Card View">
                                <i class="fa fa-grip"></i>
                            </a>
                            <a href="<?= $table_url ?>" class="pm-view-btn <?= ($view_mode === 'table') ? 'active' : '' ?>" title="List Table View">
                                <i class="fa fa-list"></i>
                            </a>
                        </div>
                    </div>

                    <div class="pm-card-body">
                        <form method="GET" action="measurementCategory.php" id="filterForm">
                            <input type="hidden" name="view" value="<?= htmlspecialchars($view_mode) ?>">

                            <div class="pm-filter-row">
                                <!-- Option 1: Category Dropdown -->
                                <div class="pm-form-group" style="flex: 1.5; min-width: 220px;">
                                    <label class="pm-label" for="garmentid">Garment Category</label>
                                    <select id="garmentid" name="garmentid" class="pm-select" onchange="document.getElementById('filterForm').submit();">
                                        <option value="0">All Garment Categories</option>
                                        <?php foreach ($garments_dropdown as $g): ?>
                                            <option value="<?= $g['garment_id'] ?>" <?= ($garment_id === intval($g['garment_id'])) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars(ucwords(strtolower($g['name']))) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Option 2: SKU Search -->
                                <div class="pm-form-group" style="flex: 2; min-width: 220px;">
                                    <label class="pm-label" for="query">Search by SKU / Product Name</label>
                                    <div style="position: relative;">
                                        <i class="fa fa-search" style="position: absolute; left: 10px; top: 11px; color: var(--pm-slate-400); font-size: 12px;"></i>
                                        <input type="text" name="query" id="query" class="pm-input" style="padding-left: 30px; width: 100%;" placeholder="e.g. YNL012, L101, Lehenga, Evening..." value="<?= htmlspecialchars($sku_query) ?>">
                                    </div>
                                </div>

                                <!-- Option 3: Measurement Status Filter -->
                                <div class="pm-form-group" style="width: 170px;">
                                    <label class="pm-label" for="spec_status">Measurement Status</label>
                                    <select name="spec_status" id="spec_status" class="pm-select">
                                        <option value="all" <?= ($spec_filter === 'all') ? 'selected' : '' ?>>All Garments</option>
                                        <option value="configured" <?= ($spec_filter === 'configured') ? 'selected' : '' ?>>Specs Configured</option>
                                        <option value="pending" <?= ($spec_filter === 'pending') ? 'selected' : '' ?>>Specs Pending</option>
                                    </select>
                                </div>

                                <!-- Action Buttons -->
                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="pm-btn pm-btn-primary">
                                        <i class="fa fa-magnifying-glass"></i> Search
                                    </button>
                                    <a href="measurementCategory.php" class="pm-btn pm-btn-secondary">
                                        <i class="fa fa-rotate-left"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Products Display -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <h2 class="pm-card-title">
                                <i class="fa-solid fa-shirt"></i> Garment Catalog Results
                            </h2>
                            <span class="pm-badge pm-badge-neutral">
                                <?= number_format($total_products) ?> Total Products
                            </span>
                        </div>
                        <div style="font-size: 12px; color: var(--pm-slate-500);">
                            Showing page <?= $page ?> of <?= $total_pages ?>
                        </div>
                    </div>

                    <div class="pm-card-body" style="background: var(--pm-slate-50);">
                        <?php if (empty($products)): ?>
                            <div style="text-align: center; padding: 60px 20px; background: #ffffff; border: 1px solid var(--pm-slate-200); border-radius: 8px; color: var(--pm-slate-400);">
                                <i class="fa fa-shirt" style="font-size: 36px; margin-bottom: 12px; display: block; color: var(--pm-slate-300);"></i>
                                <h3 style="font-size: 15px; font-weight: 600; color: var(--pm-slate-700); margin-bottom: 4px;">No garments found</h3>
                                <p style="font-size: 13px; margin: 0;">Try selecting a different category or refining your SKU search query.</p>
                            </div>
                        <?php else: ?>

                            <?php if ($view_mode === 'table'): ?>
                                <!-- Table View -->
                                <div class="pm-table-container" style="background: #ffffff; border-radius: 6px; border: 1px solid var(--pm-slate-200);">
                                    <table class="pm-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 70px;">Thumbnail</th>
                                                <th style="width: 120px;">SKU Code</th>
                                                <th>Garment Category</th>
                                                <th style="width: 120px; text-align: right;">Rent Price (₹)</th>
                                                <th style="width: 120px; text-align: right;">Selling Price (₹)</th>
                                                <th style="width: 150px; text-align: center;">Measurement Status</th>
                                                <th style="width: 160px; text-align: center;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($products as $p): 
                                                $pid      = intval($p['gproduct_id']);
                                                $sku      = htmlspecialchars($p['gproduct_code'] ?: '—');
                                                $cat      = htmlspecialchars($p['category_name'] ?: 'Apparel');
                                                $rent_pr  = floatval($p['rent_price']);
                                                $sale_pr  = floatval($p['sales_price']);
                                                $specs    = intval($p['specs_count']);
                                                $img_url  = htmlspecialchars($p['full_img']);
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div style="width: 48px; height: 58px; background: var(--pm-slate-100); border-radius: 4px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                                            <?php if ($img_url): ?>
                                                                <img src="<?= $img_url ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src=''; this.parentElement.innerHTML='<i class=\'fa fa-image\' style=\'color:var(--pm-slate-400);\'></i>';">
                                                            <?php else: ?>
                                                                <i class="fa fa-image" style="color: var(--pm-slate-400);"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td style="font-family: monospace; font-weight: 700; color: var(--pm-slate-900);">
                                                        <?= $sku ?>
                                                    </td>
                                                    <td>
                                                        <span class="pm-badge pm-badge-neutral"><?= $cat ?></span>
                                                    </td>
                                                    <td style="text-align: right; font-family: monospace; font-weight: 600; color: var(--pm-slate-900);">
                                                        <?= ($rent_pr > 0) ? '₹ ' . number_format($rent_pr) : '—' ?>
                                                    </td>
                                                    <td style="text-align: right; font-family: monospace; font-weight: 600; color: var(--pm-slate-900);">
                                                        <?= ($sale_pr > 0) ? '₹ ' . number_format($sale_pr) : '—' ?>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <?php if ($specs > 0): ?>
                                                            <span class="pm-badge pm-badge-configured"><i class="fa fa-circle-check"></i> <?= $specs ?> Specs Configured</span>
                                                        <?php else: ?>
                                                            <span class="pm-badge pm-badge-pending"><i class="fa fa-clock"></i> Specs Needed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <a href="editMeasuments.php?productid=<?= $pid ?>&sku=<?= urlencode($sku) ?>&img=<?= urlencode($p['full_img']) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">
                                                            <i class="fa fa-ruler"></i> Edit Measurements
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php else: ?>
                                <!-- Grid Card View -->
                                <div class="pm-product-grid">
                                    <?php foreach ($products as $p): 
                                        $pid      = intval($p['gproduct_id']);
                                        $sku      = htmlspecialchars($p['gproduct_code'] ?: '—');
                                        $cat      = htmlspecialchars($p['category_name'] ?: 'Apparel');
                                        $rent_pr  = floatval($p['rent_price']);
                                        $sale_pr  = floatval($p['sales_price']);
                                        $specs    = intval($p['specs_count']);
                                        $img_url  = htmlspecialchars($p['full_img']);
                                    ?>
                                        <div class="pm-product-card">
                                            <!-- Image Box -->
                                            <div class="pm-product-img-box">
                                                <?php if ($img_url): ?>
                                                    <img src="<?= $img_url ?>" class="pm-product-img" loading="lazy" alt="<?= $sku ?>" onerror="this.onerror=null; this.src=''; this.parentElement.innerHTML='<div style=\'padding:40px; text-align:center; color:var(--pm-slate-400);\'><i class=\'fa fa-shirt\' style=\'font-size:32px; display:block; margin-bottom:8px;\'></i>No Image</div>';">
                                                <?php else: ?>
                                                    <div style="padding: 40px; text-align: center; color: var(--pm-slate-400);">
                                                        <i class="fa fa-shirt" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                                                        No Image
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Status Badge floating top-right -->
                                                <div style="position: absolute; top: 10px; right: 10px;">
                                                    <?php if ($specs > 0): ?>
                                                        <span class="pm-badge pm-badge-configured" style="background: rgba(240, 253, 244, 0.95); backdrop-filter: blur(4px);">
                                                            <i class="fa fa-circle-check"></i> <?= $specs ?> Specs
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="pm-badge pm-badge-pending" style="background: rgba(248, 250, 252, 0.95); backdrop-filter: blur(4px);">
                                                            <i class="fa fa-clock"></i> Specs Needed
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Body -->
                                            <div class="pm-product-body">
                                                <div>
                                                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                                        <span class="pm-product-sku"><?= $sku ?></span>
                                                        <span class="pm-product-cat"><?= $cat ?></span>
                                                    </div>

                                                    <div class="pm-price-row">
                                                        <div class="pm-price-item">
                                                            <span class="pm-price-lbl">Rent Price</span>
                                                            <span class="pm-price-val"><?= ($rent_pr > 0) ? '₹ ' . number_format($rent_pr) : '—' ?></span>
                                                        </div>
                                                        <div class="pm-price-item" style="text-align: right;">
                                                            <span class="pm-price-lbl">Selling Price</span>
                                                            <span class="pm-price-val"><?= ($sale_pr > 0) ? '₹ ' . number_format($sale_pr) : '—' ?></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Action Button -->
                                                <a href="editMeasuments.php?productid=<?= $pid ?>&sku=<?= urlencode($sku) ?>&img=<?= urlencode($p['full_img']) ?>" class="pm-btn pm-btn-primary pm-btn-sm" style="width: 100%; margin-top: 6px;">
                                                    <i class="fa fa-ruler-combined"></i> Add / Edit Measurements
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        <?php endif; ?>
                    </div>

                    <!-- Pagination Bar -->
                    <?php if ($total_pages > 1): 
                        $query_params = $_GET;
                        function get_page_link($p, $params) {
                            $params['page'] = $p;
                            return 'measurementCategory.php?' . http_build_query($params);
                        }
                    ?>
                        <div class="pm-pagination-bar">
                            <div style="font-size: 13px; color: var(--pm-slate-500);">
                                Showing <strong><?= $offset + 1 ?></strong> to <strong><?= min($total_products, $offset + $limit) ?></strong> of <strong><?= number_format($total_products) ?></strong> Garments
                            </div>
                            <div style="display: flex; gap: 4px; align-items: center;">
                                <a href="<?= get_page_link(1, $query_params) ?>" class="pm-page-link <?= ($page <= 1) ? 'disabled' : '' ?>" title="First Page">
                                    <i class="fa fa-angles-left"></i>
                                </a>
                                <a href="<?= get_page_link($page - 1, $query_params) ?>" class="pm-page-link <?= ($page <= 1) ? 'disabled' : '' ?>" title="Previous Page">
                                    <i class="fa fa-angle-left"></i>
                                </a>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page   = min($total_pages, $page + 2);
                                for ($p = $start_page; $p <= $end_page; $p++):
                                ?>
                                    <a href="<?= get_page_link($p, $query_params) ?>" class="pm-page-link <?= ($p === $page) ? 'active' : '' ?>">
                                        <?= $p ?>
                                    </a>
                                <?php endfor; ?>

                                <a href="<?= get_page_link($page + 1, $query_params) ?>" class="pm-page-link <?= ($page >= $total_pages) ? 'disabled' : '' ?>" title="Next Page">
                                    <i class="fa fa-angle-right"></i>
                                </a>
                                <a href="<?= get_page_link($total_pages, $query_params) ?>" class="pm-page-link <?= ($page >= $total_pages) ? 'disabled' : '' ?>" title="Last Page">
                                    <i class="fa fa-angles-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div><!-- /content-wrapper -->
    </div><!-- /main-panel -->
</div><!-- /container-fluid page-body-wrapper -->

<?php
if ($con) CloseCon($con);
if ($web_con) CloseCon($web_con);
?>
</body>
</html>