<?php
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

    if (!isset($web_con) || !$web_con || (isset($web_con->connect_error) && $web_con->connect_error)) {
        if (function_exists('OpenNewSrishringarrCon')) {
            $web_con = OpenNewSrishringarrCon();
        }
    }

    if (!$web_con || $web_con->connect_error) {
        $is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);
        $dbuser = $is_local ? "root" : "u464193275_srishrinjuser";
        $dbpass = $is_local ? "" : "9b@hMgk!=zI";
        $web_con = @new mysqli("localhost", $dbuser, $dbpass, "u464193275_srishrinjewels");
    }
    ?>

    <!-- partial -->
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">

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

                .pm-container {
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
                    margin: 2px 0 0 0;
                }

                .pm-badge-counter {
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-800);
                    font-size: 12.5px;
                    font-weight: 600;
                    padding: 6px 14px;
                    border-radius: 9999px;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                }

                /* Shadcn Card */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 16px;
                    overflow: hidden;
                }

                .pm-card-body {
                    padding: 16px;
                }

                /* Selected Tray */
                .pm-tray-box {
                    background: var(--pm-slate-50);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 14px 16px;
                    margin-bottom: 16px;
                }

                .pm-sku-textarea {
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-size: 12.5px;
                    color: var(--pm-slate-900);
                    background: #ffffff;
                    padding: 8px 12px;
                    resize: vertical;
                    min-height: 52px;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-sku-textarea:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                    outline: none;
                }

                .pm-chip {
                    display: inline-flex;
                    align-items: center;
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-800);
                    border: 1px solid var(--pm-slate-200);
                    font-size: 11.5px;
                    font-weight: 600;
                    padding: 3px 8px;
                    border-radius: 6px;
                    margin: 3px 4px 3px 0;
                }

                .pm-chip .remove-chip {
                    margin-left: 6px;
                    color: var(--pm-slate-400);
                    cursor: pointer;
                    font-size: 11px;
                    transition: color 0.15s;
                }

                .pm-chip .remove-chip:hover {
                    color: #ef4444;
                }

                /* Shadcn Segmented Tab Switcher */
                .pm-tab-bar {
                    display: flex;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    padding: 4px;
                    border-radius: 8px;
                    margin-bottom: 14px;
                    gap: 4px;
                }

                .pm-tab-btn {
                    flex: 1;
                    padding: 8px 14px;
                    border: none;
                    border-radius: 6px;
                    font-weight: 500;
                    font-size: 13px;
                    color: var(--pm-slate-500);
                    background: transparent;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    transition: all 0.15s ease;
                }

                .pm-tab-btn.active {
                    background: #ffffff;
                    color: var(--pm-slate-900);
                    font-weight: 600;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
                }

                /* Inputs & Controls */
                .pm-form-label {
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--pm-slate-700);
                    margin-bottom: 5px;
                    display: block;
                }

                .pm-select {
                    height: 38px;
                    font-size: 13px;
                    color: var(--pm-slate-800);
                    border-radius: 6px;
                    border: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                    padding: 6px 12px;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-select:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                    outline: none;
                }

                .pm-filter-toolbar {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 12px 14px;
                    margin-bottom: 14px;
                }

                .pm-input-sm {
                    height: 34px;
                    padding: 4px 10px;
                    font-size: 12.5px;
                    border-radius: 6px;
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-900);
                }

                .pm-input-sm:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                    outline: none;
                }

                /* Buttons */
                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border: 1px solid var(--pm-slate-900);
                    font-size: 12.5px;
                    font-weight: 600;
                    border-radius: 6px;
                    padding: 6px 14px;
                    height: 34px;
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
                    padding: 6px 12px;
                    height: 34px;
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

                .pm-btn-outline.is-active {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                /* Stats / Counter Bar */
                .pm-stats-bar {
                    background: var(--pm-slate-50);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 10px 14px;
                    margin-bottom: 16px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 10px;
                }

                .pm-stat-pill {
                    font-size: 12.5px;
                    font-weight: 500;
                    color: var(--pm-slate-600);
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                }

                .pm-stat-tag {
                    padding: 2px 8px;
                    border-radius: 6px;
                    font-size: 11.5px;
                    font-weight: 600;
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-900);
                }

                /* Product Grid Cards */
                .compact-product-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    overflow: hidden;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                }

                .compact-product-card:hover {
                    border-color: #94a3b8;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                    transform: translateY(-2px);
                }

                .compact-product-card.is-selected {
                    border: 2px solid var(--pm-slate-900) !important;
                    background: var(--pm-slate-50);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                .card-thumb-box {
                    position: relative;
                    width: 100%;
                    height: 180px;
                    background: var(--pm-slate-100);
                    overflow: hidden;
                }

                .card-thumb-img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    transition: transform 0.25s ease;
                }

                .compact-product-card:hover .card-thumb-img {
                    transform: scale(1.04);
                }

                .card-qty-badge {
                    position: absolute;
                    top: 6px;
                    left: 6px;
                    font-size: 10px;
                    font-weight: 600;
                    padding: 2px 6px;
                    border-radius: 4px;
                    backdrop-filter: blur(4px);
                    z-index: 1;
                }

                .card-qty-badge.in-stock {
                    background: rgba(15, 23, 42, 0.85);
                    color: #ffffff;
                }

                .card-qty-badge.out-stock {
                    background: rgba(239, 68, 68, 0.85);
                    color: #ffffff;
                }

                .card-info-box {
                    padding: 10px;
                    display: flex;
                    flex-direction: column;
                    flex: 1;
                }

                .card-sku {
                    font-size: 12px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                }

                .card-mrp {
                    font-size: 10.5px;
                    color: var(--pm-slate-500);
                    background: var(--pm-slate-100);
                    padding: 1px 6px;
                    border-radius: 4px;
                    font-weight: 600;
                }

                .card-title-text {
                    font-size: 11.5px;
                    color: var(--pm-slate-600);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    margin-bottom: 8px;
                }

                .card-prices-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 6px;
                    background: var(--pm-slate-50);
                    padding: 6px 8px;
                    border-radius: 6px;
                    border: 1px solid var(--pm-slate-200);
                    margin-bottom: 10px;
                }

                .card-price-col {
                    display: flex;
                    flex-direction: column;
                }

                .card-price-col .col-lbl {
                    font-size: 9.5px;
                    text-transform: uppercase;
                    font-weight: 700;
                    color: var(--pm-slate-400);
                    line-height: 1;
                }

                .card-price-col .col-val {
                    font-size: 12.5px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    line-height: 1.3;
                    margin-top: 2px;
                }

                .btn-card-action {
                    height: 30px;
                    padding: 0;
                    font-size: 11.5px;
                    font-weight: 600;
                    border-radius: 6px;
                    border: 1px solid var(--pm-slate-900);
                    background: #ffffff;
                    color: var(--pm-slate-900);
                    transition: all 0.15s ease;
                    width: 100%;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 5px;
                }

                .btn-card-action:hover {
                    background: var(--pm-slate-100);
                }

                .is-selected .btn-card-action {
                    background: var(--pm-slate-900) !important;
                    border-color: var(--pm-slate-900) !important;
                    color: #ffffff !important;
                }

                /* Empty state container */
                .pm-empty-state {
                    text-align: center;
                    padding: 48px 20px;
                    background: #ffffff;
                    border: 1px dashed var(--pm-slate-200);
                    border-radius: 8px;
                }
            </style>

            <div class="pm-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa fa-file-pdf-o text-muted" style="font-size: 18px;"></i> 
                            PDF Catalog Generator
                        </h1>
                        <p class="pm-page-subtitle">Select inventory, apply price filters, and prepare SKUs for catalog generation.</p>
                    </div>
                    <div class="pm-badge-counter" id="selectedCountBadge">
                        <i class="fa fa-check-circle text-muted"></i> 0 Products Selected
                    </div>
                </div>

                <!-- Main Card -->
                <div class="pm-card">
                    <div class="pm-card-body">

                        <!-- Selected Tray -->
                        <div class="pm-tray-box">
                            <form action="review_pdfmaker.php" method="POST" id="pdfForm">
                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                    <label class="pm-form-label m-0">
                                        <i class="fa fa-shopping-bag text-muted me-1"></i> 
                                        Selected Products Tray (SKUs)
                                    </label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="pm-btn-outline" style="height:28px; font-size:11.5px;" onclick="cleanSkus()" title="Remove duplicates & clean commas">
                                            <i class="fa fa-magic"></i> Clean & Deduplicate
                                        </button>
                                        <button type="button" class="pm-btn-outline" style="height:28px; font-size:11.5px;" onclick="clearSkus()" title="Clear tray">
                                            <i class="fa fa-trash text-muted"></i> Clear All
                                        </button>
                                    </div>
                                </div>

                                <textarea id="skus" name="sku" class="form-control pm-sku-textarea w-100" rows="2" 
                                          placeholder="Products will appear here automatically when added from below, or paste comma-separated SKUs..."></textarea>
                                
                                <div id="skuChips" class="mt-1"></div>

                                <div class="d-flex justify-content-end mt-2 pt-1">
                                    <button type="submit" class="pm-btn-primary" style="height: 36px; padding: 0 18px;">
                                        Proceed to Image Selection & Review <i class="fa fa-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Category Selector Segmented Tabs -->
                        <div class="pm-tab-bar">
                            <button class="pm-tab-btn active" id="apparelTab" onclick="switchCategoryTab('apparel')">
                                <i class="fa fa-female"></i> Apparel Categories
                            </button>
                            <button class="pm-tab-btn" id="jewelTab" onclick="switchCategoryTab('jewellery')">
                                <i class="fa fa-diamond"></i> Jewellery Categories
                            </button>
                        </div>

                        <!-- Category Select Dropdowns -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6 col-sm-12" id="apparelDropdownSection">
                                <label class="pm-form-label"><i class="fa fa-tag text-muted me-1"></i> Select Apparel Category</label>
                                <select id="garmentid" name="garmentid" class="form-select form-control pm-select w-100">
                                    <option value="">-- Choose Apparel Category --</option>
                                    <?php
                                    if ($web_con) {
                                        $garsql = mysqli_query($web_con, "SELECT * FROM `garments` WHERE `Main_id`=1 OR `Main_id`=3 ORDER BY name ASC");
                                        if ($garsql) {
                                            while ($garsql_result = mysqli_fetch_assoc($garsql)) {
                                                $name = $garsql_result['name'];
                                                $garment_id = $garsql_result['garment_id'];
                                                echo '<option value="' . htmlspecialchars($garment_id) . '">' . htmlspecialchars(ucwords(strtolower($name))) . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6 col-sm-12" id="jewelleryDropdownSection" style="display:none;">
                                <label class="pm-form-label"><i class="fa fa-tag text-muted me-1"></i> Select Jewellery Category</label>
                                <select id="jewelid" name="jewelid" class="form-select form-control pm-select w-100">
                                    <option value="">-- Choose Jewellery Category --</option>
                                    <?php
                                    $apiAutoload = dirname(dirname(__DIR__)) . '/API/autoload.php';
                                    if (file_exists($apiAutoload)) {
                                        require_once $apiAutoload;
                                    }

                                    if (class_exists('API\Models\CategoryModel')) {
                                        $categoryModel = new \API\Models\CategoryModel();
                                        $allCats = $categoryModel->getAllCategories();
                                        foreach ($allCats as $catGroup) {
                                            if ($catGroup['category'] === 'Jewellery') {
                                                foreach ($catGroup['items'] as $item) {
                                                    $parentName = htmlspecialchars($item['name']);
                                                    $parentId = htmlspecialchars($item['id']);
                                                    $children = $item['children'] ?? [];

                                                    if (!empty($children)) {
                                                        echo '<optgroup label="' . $parentName . '">';
                                                        echo '<option value="' . $parentId . '">All ' . $parentName . '</option>';
                                                        foreach ($children as $child) {
                                                            echo '<option value="' . htmlspecialchars($child['id']) . '">&nbsp;&nbsp;↳ ' . htmlspecialchars($child['name']) . '</option>';
                                                        }
                                                        echo '</optgroup>';
                                                    } else {
                                                        echo '<option value="' . $parentId . '">' . $parentName . '</option>';
                                                    }
                                                }
                                            }
                                        }
                                    } else if ($web_con) {
                                        $mainQry = mysqli_query($web_con, "SELECT subcat_id, categories_name FROM jewel_subcat WHERE mcat_id IN (1, 3) ORDER BY categories_name");
                                        if ($mainQry) {
                                            while ($mRow = mysqli_fetch_assoc($mainQry)) {
                                                $mId = $mRow['subcat_id'];
                                                $mName = ucwords(strtolower($mRow['categories_name']));
                                                $subQry = mysqli_query($web_con, "SELECT subcat_id, name FROM subcat1 WHERE maincat_id = $mId AND status = 1 ORDER BY name");
                                                if ($subQry && mysqli_num_rows($subQry) > 0) {
                                                    echo '<optgroup label="' . htmlspecialchars($mName) . '">';
                                                    echo '<option value="jewel_main:' . $mId . '">All ' . htmlspecialchars($mName) . '</option>';
                                                    while ($sRow = mysqli_fetch_assoc($subQry)) {
                                                        echo '<option value="jewel_sub:' . $sRow['subcat_id'] . '">&nbsp;&nbsp;↳ ' . htmlspecialchars(ucwords(strtolower($sRow['name']))) . '</option>';
                                                    }
                                                    echo '</optgroup>';
                                                } else {
                                                    echo '<option value="jewel_main:' . $mId . '">' . htmlspecialchars($mName) . '</option>';
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Filter Controls Bar -->
                        <div class="pm-filter-toolbar">
                            <div class="row g-2 align-items-center">
                                <!-- Price Bounds -->
                                <div class="col-xl-4 col-lg-5 col-md-6 col-sm-12">
                                    <div class="input-group">
                                        <span class="input-group-text py-0 px-2" style="font-size:12px; background:var(--pm-slate-50); border-color:var(--pm-slate-200); color:var(--pm-slate-600);">₹</span>
                                        <input type="number" id="minPrice" name="minPrice" class="form-control pm-input-sm" placeholder="Min Price">
                                        <span class="input-group-text py-0 px-2" style="font-size:12px; background:var(--pm-slate-50); border-color:var(--pm-slate-200); color:var(--pm-slate-500);">to</span>
                                        <input type="number" id="maxPrice" name="maxPrice" class="form-control pm-input-sm" placeholder="Max Price">
                                    </div>
                                </div>

                                <!-- Filter Buttons -->
                                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 d-flex gap-1">
                                    <button type="button" id="rentFilter" class="pm-btn-outline flex-fill justify-content-center">
                                        <i class="fa fa-filter"></i> Rent Filter
                                    </button>
                                    <button type="button" id="sellFilter" class="pm-btn-outline flex-fill justify-content-center">
                                        <i class="fa fa-filter"></i> Sell Filter
                                    </button>
                                    <button type="button" class="pm-btn-outline px-2" onclick="resetPriceFilter()" title="Reset Filters">
                                        <i class="fa fa-refresh"></i>
                                    </button>
                                </div>

                                <!-- Instant Search Box -->
                                <div class="col-xl-4 col-lg-3 col-md-12 col-sm-12">
                                    <div class="input-group">
                                        <span class="input-group-text py-0 px-2" style="font-size:12px; background:var(--pm-slate-50); border-color:var(--pm-slate-200);"><i class="fa fa-search text-muted"></i></span>
                                        <input type="text" id="liveSearchInput" class="form-control pm-input-sm" placeholder="Search SKU or name...">
                                        <button class="btn btn-outline-secondary pm-input-sm py-0 px-2" type="button" onclick="$('#liveSearchInput').val('').trigger('input');" title="Clear">
                                            <i class="fa fa-times text-muted"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Realtime Filter Count Bar -->
                        <div class="pm-stats-bar" id="statsBarContainer" style="display:none;">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="pm-stat-pill">
                                    Category Total: 
                                    <span class="pm-stat-tag" id="statCategoryTotal">0</span>
                                </span>

                                <span class="pm-stat-pill">
                                    After Filter: 
                                    <span class="pm-stat-tag" id="statVisibleCount">0 Visible</span>
                                </span>

                                <span class="pm-stat-pill">
                                    In Tray: 
                                    <span class="pm-stat-tag" id="statSelectedCount">0</span>
                                </span>

                                <span class="text-muted font-italic" id="filterStatusText" style="font-size: 12px;"></span>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="pm-btn-outline" style="height:28px; font-size:11.5px;" onclick="selectAllFiltered()">
                                    <i class="fa fa-check-square-o"></i> Select All Filtered
                                </button>
                                <button type="button" class="pm-btn-outline" style="height:28px; font-size:11.5px;" onclick="deselectAllFiltered()">
                                    <i class="fa fa-square-o"></i> Deselect Filtered
                                </button>
                            </div>
                        </div>

                        <!-- Products Grid Container -->
                        <div id="categoryProductsContainer">
                            <div class="pm-empty-state">
                                <i class="fa fa-th-large fa-2x mb-2 text-muted" style="opacity: 0.4;"></i>
                                <div style="font-size:14px; font-weight:600; color:var(--pm-slate-800);">Select a category above to load inventory</div>
                                <div style="font-size:12px; color:var(--pm-slate-500); margin-top: 4px;">Live inventory, high-resolution product imagery, and stock counts will populate here.</div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Scripts -->
            <script>
                var uniqueSkus = new Set();
                var currentActiveTab = 'apparel';
                var currentFilterType = null;

                $(document).ready(function () {
                    syncSkusFromTextarea();

                    $('#skus').on('input blur', function () {
                        syncSkusFromTextarea();
                    });

                    $('#liveSearchInput').on('input', function () {
                        applyAllFilters();
                    });
                });

                // Toggle Selection Handler
                $(document).on('click', '.addInExclusiveCollection', function () {
                    var productId = $(this).data("productid");
                    var sku = $(this).data("sku");
                    var combinedString = sku + "-" + productId;

                    if (uniqueSkus.has(combinedString)) {
                        uniqueSkus.delete(combinedString);
                        updateSkusUI();
                        Swal.fire({
                            toast: true,
                            position: 'bottom-end',
                            icon: 'info',
                            title: 'Removed: ' + sku,
                            showConfirmButton: false,
                            timer: 1000
                        });
                    } else {
                        uniqueSkus.add(combinedString);
                        updateSkusUI();
                        Swal.fire({
                            toast: true,
                            position: 'bottom-end',
                            icon: 'success',
                            title: 'Added: ' + sku,
                            showConfirmButton: false,
                            timer: 1000
                        });
                    }
                    refreshCardButtonsState();
                });

                function syncSkusFromTextarea() {
                    var val = $("#skus").val().trim();
                    uniqueSkus.clear();
                    if (val) {
                        var items = val.split(/[\n,]+/);
                        items.forEach(function (item) {
                            var clean = item.trim();
                            if (clean) uniqueSkus.add(clean);
                        });
                    }
                    updateChipsUI();
                    refreshCardButtonsState();
                }

                function updateSkusUI() {
                    var arr = Array.from(uniqueSkus);
                    $("#skus").val(arr.join(', '));
                    updateChipsUI();
                    refreshCardButtonsState();
                }

                function updateChipsUI() {
                    var arr = Array.from(uniqueSkus);
                    $("#selectedCountBadge").html('<i class="fa fa-check-circle text-muted"></i> ' + arr.length + ' Products Selected');
                    $("#statSelectedCount").text(arr.length);

                    var chipsHtml = '';
                    arr.forEach(function (item) {
                        chipsHtml += '<span class="pm-chip">' +
                            escapeHtml(item) +
                            ' <i class="fa fa-times remove-chip" onclick="removeSku(\'' + escapeJsString(item) + '\')" title="Remove"></i>' +
                            '</span>';
                    });
                    $("#skuChips").html(chipsHtml);
                }

                function removeSku(item) {
                    uniqueSkus.delete(item);
                    updateSkusUI();
                }

                function clearSkus() {
                    if (uniqueSkus.size === 0) return;
                    Swal.fire({
                        title: 'Clear Selected Products?',
                        text: "Remove all " + uniqueSkus.size + " products from the tray?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#0f172a',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Yes, Clear All'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            uniqueSkus.clear();
                            updateSkusUI();
                        }
                    });
                }

                function cleanSkus() {
                    syncSkusFromTextarea();
                    updateSkusUI();
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'SKUs formatted and deduplicated!',
                        showConfirmButton: false,
                        timer: 1200
                    });
                }

                function switchCategoryTab(tabName) {
                    currentActiveTab = tabName;
                    $('.pm-tab-btn').removeClass('active');

                    if (tabName === 'apparel') {
                        $('#apparelTab').addClass('active');
                        $('#apparelDropdownSection').show();
                        $('#jewelleryDropdownSection').hide();
                        $('#jewelid').val('');
                    } else {
                        $('#jewelTab').addClass('active');
                        $('#apparelDropdownSection').hide();
                        $('#jewelleryDropdownSection').show();
                        $('#garmentid').val('');
                    }
                    $('#statsBarContainer').hide();
                    $('#categoryProductsContainer').html(
                        '<div class="pm-empty-state">' +
                        '  <i class="fa fa-th-large fa-2x mb-2 text-muted" style="opacity: 0.4;"></i>' +
                        '  <div style="font-size:14px; font-weight:600; color:var(--pm-slate-800);">Select a category above to load inventory</div>' +
                        '  <div style="font-size:12px; color:var(--pm-slate-500); margin-top: 4px;">Live inventory, high-resolution product imagery, and stock counts will populate here.</div>' +
                        '</div>'
                    );
                }

                // AJAX Loaders
                $(document).on('change', '#garmentid', function () {
                    var garmentid = $(this).val();
                    if (!garmentid) {
                        $('#statsBarContainer').hide();
                        $("#categoryProductsContainer").html(
                            '<div class="pm-empty-state">' +
                            '  <i class="fa fa-th-large fa-2x mb-2 text-muted" style="opacity: 0.4;"></i>' +
                            '  <div style="font-size:14px; font-weight:600; color:var(--pm-slate-800);">Select an apparel category</div>' +
                            '</div>'
                        );
                        return;
                    }
                    loadProductsAjax('./garmentdestailsShowPDF.php', { garmentid: garmentid });
                });

                $(document).on('change', '#jewelid', function () {
                    var jewelid = $(this).val();
                    if (!jewelid) {
                        $('#statsBarContainer').hide();
                        $("#categoryProductsContainer").html(
                            '<div class="pm-empty-state">' +
                            '  <i class="fa fa-th-large fa-2x mb-2 text-muted" style="opacity: 0.4;"></i>' +
                            '  <div style="font-size:14px; font-weight:600; color:var(--pm-slate-800);">Select a jewellery category</div>' +
                            '</div>'
                        );
                        return;
                    }
                    loadProductsAjax('./jeweldestailsShowPDF.php', { jewelid: jewelid });
                });

                function loadProductsAjax(url, data) {
                    $("#categoryProductsContainer").html(
                        '<div class="text-center py-5">' +
                        '  <i class="fa fa-spinner fa-spin fa-2x mb-2 text-muted"></i>' +
                        '  <div style="font-size:13px; font-weight:600; color:var(--pm-slate-800);">Loading Inventory...</div>' +
                        '  <div style="font-size:12px; color:var(--pm-slate-500);">Fetching live inventory & prices from ProductService...</div>' +
                        '</div>'
                    );
                    $('#statsBarContainer').hide();

                    $.ajax({
                        url: url,
                        data: data,
                        success: function (response) {
                            $("#categoryProductsContainer").html(response);
                            $('#statsBarContainer').show();
                            resetPriceFilter(false);
                            updateProductCountsAfterFilter();
                            refreshCardButtonsState();
                        },
                        error: function() {
                            $("#categoryProductsContainer").html('<div class="alert alert-danger my-3 text-center py-2" style="font-size:13px;"><i class="fa fa-exclamation-triangle"></i> Failed to load products. Please check database connection.</div>');
                            $('#statsBarContainer').hide();
                        }
                    });
                }

                // Filter Actions
                $(document).on('click', '#rentFilter', function () {
                    if (currentFilterType === 'rent') {
                        currentFilterType = null;
                        $(this).removeClass('is-active');
                    } else {
                        currentFilterType = 'rent';
                        $('#rentFilter').addClass('is-active');
                        $('#sellFilter').removeClass('is-active');
                    }
                    applyAllFilters();
                });

                $(document).on('click', '#sellFilter', function () {
                    if (currentFilterType === 'sell') {
                        currentFilterType = null;
                        $(this).removeClass('is-active');
                    } else {
                        currentFilterType = 'sell';
                        $('#sellFilter').addClass('is-active');
                        $('#rentFilter').removeClass('is-active');
                    }
                    applyAllFilters();
                });

                function resetPriceFilter(triggerUpdate = true) {
                    currentFilterType = null;
                    $('#rentFilter, #sellFilter').removeClass('is-active');
                    $("#minPrice").val('');
                    $("#maxPrice").val('');
                    $("#liveSearchInput").val('');
                    if (triggerUpdate) {
                        applyAllFilters();
                    }
                }

                function applyAllFilters() {
                    var minPrice = parseFloat($("#minPrice").val());
                    var maxPrice = parseFloat($("#maxPrice").val());
                    var searchQuery = $("#liveSearchInput").val().trim().toLowerCase();

                    var hasPriceFilter = currentFilterType && (!isNaN(minPrice) || !isNaN(maxPrice));
                    var hasSearch = searchQuery.length > 0;

                    var filterDescriptions = [];
                    if (hasPriceFilter) {
                        var rangeText = '';
                        if (!isNaN(minPrice) && !isNaN(maxPrice)) rangeText = '₹' + minPrice + ' – ₹' + maxPrice;
                        else if (!isNaN(minPrice)) rangeText = '≥ ₹' + minPrice;
                        else if (!isNaN(maxPrice)) rangeText = '≤ ₹' + maxPrice;
                        filterDescriptions.push((currentFilterType === 'rent' ? 'Rent ' : 'Sell ') + rangeText);
                    }
                    if (hasSearch) {
                        filterDescriptions.push('Search: "' + searchQuery + '"');
                    }

                    if (filterDescriptions.length > 0) {
                        $('#filterStatusText').text('(' + filterDescriptions.join(', ') + ')');
                    } else {
                        $('#filterStatusText').text('');
                    }

                    $('.product_grid').each(function () {
                        var $card = $(this);
                        var show = true;

                        if (hasPriceFilter) {
                            var price = (currentFilterType === 'rent') 
                                ? parseFloat($card.data('rent_price')) 
                                : parseFloat($card.data('selling_price'));

                            if (!isNaN(minPrice) && price < minPrice) show = false;
                            if (!isNaN(maxPrice) && price > maxPrice) show = false;
                        }

                        if (show && hasSearch) {
                            var cardName = ($card.data('name') || '').toString().toLowerCase();
                            var cardSku = ($card.data('sku') || '').toString().toLowerCase();
                            if (cardName.indexOf(searchQuery) === -1 && cardSku.indexOf(searchQuery) === -1) {
                                show = false;
                            }
                        }

                        if (show) {
                            $card.show();
                        } else {
                            $card.hide();
                        }
                    });

                    updateProductCountsAfterFilter();
                }

                function updateProductCountsAfterFilter() {
                    var total = $('.product_grid').length;
                    var visible = $('.product_grid:visible').length;

                    $("#statCategoryTotal").text(total);
                    $("#statVisibleCount").text(visible + ' Visible');
                    $("#statSelectedCount").text(uniqueSkus.size);
                }

                function refreshCardButtonsState() {
                    $('.product_grid').each(function () {
                        var $card = $(this);
                        var combined = $card.data('combined');
                        var $btn = $card.find('.btn-card-action');

                        if (uniqueSkus.has(combined)) {
                            $card.find('.compact-product-card').addClass('is-selected');
                            $btn.html('<i class="fa fa-check"></i> Added');
                        } else {
                            $card.find('.compact-product-card').removeClass('is-selected');
                            $btn.html('<i class="fa fa-plus"></i> Add');
                        }
                    });
                }

                function selectAllFiltered() {
                    var addedCount = 0;
                    $('.product_grid:visible').each(function () {
                        var combined = $(this).data('combined');
                        if (combined && !uniqueSkus.has(combined)) {
                            uniqueSkus.add(combined);
                            addedCount++;
                        }
                    });
                    updateSkusUI();
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: addedCount + ' products added!',
                        showConfirmButton: false,
                        timer: 1200
                    });
                }

                function deselectAllFiltered() {
                    var removedCount = 0;
                    $('.product_grid:visible').each(function () {
                        var combined = $(this).data('combined');
                        if (combined && uniqueSkus.has(combined)) {
                            uniqueSkus.delete(combined);
                            removedCount++;
                        }
                    });
                    updateSkusUI();
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: removedCount + ' products removed.',
                        showConfirmButton: false,
                        timer: 1200
                    });
                }

                function escapeHtml(string) {
                    return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                }

                function escapeJsString(str) {
                    return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
                }
            </script>

        </div>
    </div>
</div>

<?php if (file_exists('../footer.php')) include_once('../footer.php'); ?>
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