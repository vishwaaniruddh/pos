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
        <div class="content-wrapper" style="padding: 1.25rem 1.5rem !important; background: #f4f6fa;">

            <script src="https://code.jquery.com/jquery-3.7.1.min.js"
                integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            <style>
                :root {
                    --pm-primary: #4f46e5;
                    --pm-primary-dark: #4338ca;
                    --pm-success: #10b981;
                    --pm-danger: #ef4444;
                    --pm-border: #e2e8f0;
                    --pm-bg-subtle: #f8fafc;
                }

                /* Compact Wrapper & Card */
                .pm-container {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-border);
                    border-radius: 12px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                    margin-bottom: 16px;
                    overflow: hidden;
                }

                .pm-card-header {
                    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
                    color: #ffffff;
                    padding: 12px 20px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }

                .pm-card-header h1, .pm-card-header h2, .pm-card-header h3 {
                    font-size: 15px;
                    font-weight: 700;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    color: #ffffff;
                }

                .pm-badge-count {
                    background: rgba(255, 255, 255, 0.2);
                    color: #ffffff;
                    font-size: 12px;
                    font-weight: 600;
                    padding: 4px 12px;
                    border-radius: 20px;
                    border: 1px solid rgba(255, 255, 255, 0.25);
                }

                /* Compact Selected Tray */
                .pm-tray-box {
                    background: var(--pm-bg-subtle);
                    border: 1px solid var(--pm-border);
                    border-radius: 10px;
                    padding: 12px 16px;
                    margin-bottom: 14px;
                }

                .pm-sku-textarea {
                    border: 1px solid #cbd5e1;
                    border-radius: 6px;
                    font-family: 'Consolas', 'Courier New', monospace;
                    font-size: 12px;
                    background: #ffffff;
                    padding: 6px 10px;
                    resize: vertical;
                    min-height: 48px;
                }

                .pm-sku-textarea:focus {
                    border-color: var(--pm-primary);
                    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
                    outline: none;
                }

                .pm-chip {
                    display: inline-flex;
                    align-items: center;
                    background: #eef2ff;
                    color: #3730a3;
                    border: 1px solid #c7d2fe;
                    font-size: 11px;
                    font-weight: 600;
                    padding: 2px 8px;
                    border-radius: 12px;
                    margin: 2px 3px 2px 0;
                }

                .pm-chip .remove-chip {
                    margin-left: 4px;
                    color: #ef4444;
                    cursor: pointer;
                    font-size: 11px;
                }

                /* Compact Tabs */
                .pm-tab-bar {
                    display: flex;
                    gap: 6px;
                    background: #f1f5f9;
                    padding: 4px;
                    border-radius: 8px;
                    margin-bottom: 12px;
                }

                .pm-tab-btn {
                    flex: 1;
                    padding: 8px 14px;
                    border: none;
                    border-radius: 6px;
                    font-weight: 600;
                    font-size: 13px;
                    color: #64748b;
                    background: transparent;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                    transition: all 0.15s;
                }

                .pm-tab-btn.active {
                    background: #ffffff;
                    color: var(--pm-primary);
                    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
                }

                /* Compact Filter Bar */
                .pm-filter-bar {
                    background: #ffffff;
                    border: 1px solid var(--pm-border);
                    border-radius: 10px;
                    padding: 10px 14px;
                    margin-bottom: 12px;
                }

                .pm-form-label {
                    font-size: 12px;
                    font-weight: 700;
                    color: #334155;
                    margin-bottom: 4px;
                    display: block;
                }

                .pm-input-sm {
                    height: 32px;
                    padding: 4px 10px;
                    font-size: 12.5px;
                    border-radius: 6px;
                    border: 1px solid #cbd5e1;
                }

                .pm-input-sm:focus {
                    border-color: var(--pm-primary);
                    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
                    outline: none;
                }

                .pm-btn-sm {
                    height: 32px;
                    padding: 0 12px;
                    font-size: 12px;
                    font-weight: 600;
                    border-radius: 6px;
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    white-space: nowrap;
                }

                /* Realtime Counter Bar */
                .pm-stats-bar {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 8px 14px;
                    margin-bottom: 14px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 8px;
                }

                .pm-stat-pill {
                    font-size: 12.5px;
                    font-weight: 600;
                    color: #1e293b;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                }

                .pm-stat-tag {
                    padding: 2px 8px;
                    border-radius: 12px;
                    font-size: 11.5px;
                    font-weight: 700;
                }

                .pm-stat-tag.total {
                    background: #64748b;
                    color: #ffffff;
                }

                .pm-stat-tag.visible {
                    background: #2563eb;
                    color: #ffffff;
                }

                .pm-stat-tag.tray {
                    background: #10b981;
                    color: #ffffff;
                }

                /* Compact Product Card */
                .compact-product-card {
                    background: #ffffff;
                    border: 1.5px solid #e2e8f0;
                    border-radius: 8px;
                    overflow: hidden;
                    transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                }

                .compact-product-card:hover {
                    border-color: #94a3b8;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
                }

                .compact-product-card.is-selected {
                    border-color: #10b981 !important;
                    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3) !important;
                    background: #f0fdf4 !important;
                }

                .card-thumb-box {
                    position: relative;
                    width: 100%;
                    height: 180px;
                    background: #f1f5f9;
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
                    font-weight: 700;
                    padding: 2px 6px;
                    border-radius: 4px;
                    backdrop-filter: blur(4px);
                    z-index: 1;
                }

                .card-qty-badge.in-stock {
                    background: rgba(16, 185, 129, 0.9);
                    color: #ffffff;
                }

                .card-qty-badge.out-stock {
                    background: rgba(239, 68, 68, 0.9);
                    color: #ffffff;
                }

                .card-info-box {
                    padding: 8px 10px 10px 10px;
                    display: flex;
                    flex-direction: column;
                    flex: 1;
                }

                .card-sku {
                    font-size: 12px;
                    font-weight: 700;
                    color: #0f172a;
                    font-family: 'Consolas', 'Courier New', monospace;
                }

                .card-mrp {
                    font-size: 10.5px;
                    color: #64748b;
                    background: #f1f5f9;
                    padding: 1px 5px;
                    border-radius: 3px;
                    font-weight: 600;
                }

                .card-title-text {
                    font-size: 11px;
                    color: #475569;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    margin-bottom: 6px;
                }

                .card-prices-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 4px;
                    background: #f8fafc;
                    padding: 5px 6px;
                    border-radius: 6px;
                    border: 1px solid #f1f5f9;
                    margin-bottom: 8px;
                }

                .card-price-col {
                    display: flex;
                    flex-direction: column;
                }

                .card-price-col .col-lbl {
                    font-size: 9.5px;
                    text-transform: uppercase;
                    font-weight: 700;
                    color: #94a3b8;
                    line-height: 1;
                }

                .card-price-col.rent .col-val {
                    font-size: 12px;
                    font-weight: 700;
                    color: #2563eb;
                    line-height: 1.3;
                }

                .card-price-col.sell .col-val {
                    font-size: 12px;
                    font-weight: 700;
                    color: #dc2626;
                    line-height: 1.3;
                }

                .btn-card-action {
                    height: 28px;
                    padding: 0;
                    font-size: 11.5px;
                    font-weight: 600;
                    border-radius: 6px;
                    border: 1px solid var(--pm-primary);
                    background: #ffffff;
                    color: var(--pm-primary);
                    transition: all 0.15s;
                    width: 100%;
                }

                .btn-card-action:hover {
                    background: var(--pm-primary);
                    color: #ffffff;
                }

                .is-selected .btn-card-action {
                    background: #10b981 !important;
                    border-color: #10b981 !important;
                    color: #ffffff !important;
                }
            </style>

            <div class="pm-container">

                <!-- Main Card -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <h2>
                            <i class="fa fa-file-pdf-o"></i> 
                            PDF Catalog Generator
                        </h2>
                        <span class="pm-badge-count" id="selectedCountBadge">
                            <i class="fa fa-check-circle"></i> 0 Selected
                        </span>
                    </div>

                    <div class="p-3">

                        <!-- Selected Tray -->
                        <div class="pm-tray-box">
                            <form action="review_pdfmaker.php" method="POST" id="pdfForm">
                                <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                                    <label class="pm-form-label m-0">
                                        <i class="fa fa-shopping-bag text-primary"></i> 
                                        Selected Products Tray (SKUs)
                                    </label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary pm-btn-sm" style="height:26px; font-size:11px;" onclick="cleanSkus()">
                                            <i class="fa fa-magic"></i> Clean & Deduplicate
                                        </button>
                                        <button type="button" class="btn btn-outline-danger pm-btn-sm" style="height:26px; font-size:11px;" onclick="clearSkus()">
                                            <i class="fa fa-trash"></i> Clear All
                                        </button>
                                    </div>
                                </div>

                                <textarea id="skus" name="sku" class="form-control pm-sku-textarea w-100" rows="2" 
                                          placeholder="Products will appear here automatically when added from below, or paste comma-separated SKUs..."></textarea>
                                
                                <div id="skuChips" class="mt-1"></div>

                                <div class="d-flex justify-content-end mt-2">
                                    <button type="submit" class="btn btn-success pm-btn-sm" style="height: 34px; padding: 0 16px; font-weight: 700;">
                                        Proceed to Image Selection & Review <i class="fa fa-arrow-right ml-1"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Category Selector Tabs -->
                        <div class="pm-tab-bar">
                            <button class="pm-tab-btn active" id="apparelTab" onclick="switchCategoryTab('apparel')">
                                <i class="fa fa-female"></i> Apparel Categories
                            </button>
                            <button class="pm-tab-btn" id="jewelTab" onclick="switchCategoryTab('jewellery')">
                                <i class="fa fa-gem"></i> Jewellery Categories
                            </button>
                        </div>

                        <!-- Category Select Dropdowns -->
                        <div class="row g-2 mb-2">
                            <div class="col-md-6 col-sm-12" id="apparelDropdownSection">
                                <label class="pm-form-label"><i class="fa fa-tag text-primary"></i> Select Apparel Category</label>
                                <select id="garmentid" name="garmentid" class="form-select form-control pm-input-sm">
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
                                <label class="pm-form-label"><i class="fa fa-tag text-primary"></i> Select Jewellery Category</label>
                                <select id="jewelid" name="jewelid" class="form-select form-control pm-input-sm">
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
                        <div class="pm-filter-bar">
                            <div class="row g-2 align-items-center">
                                <!-- Price Bounds -->
                                <div class="col-xl-4 col-lg-5 col-md-6 col-sm-12">
                                    <div class="input-group">
                                        <span class="input-group-text py-0 px-2" style="font-size:12px; background:#f8fafc;">₹</span>
                                        <input type="number" id="minPrice" name="minPrice" class="form-control pm-input-sm" placeholder="Min Price">
                                        <span class="input-group-text py-0 px-2" style="font-size:12px; background:#f8fafc;">to</span>
                                        <input type="number" id="maxPrice" name="maxPrice" class="form-control pm-input-sm" placeholder="Max Price">
                                    </div>
                                </div>

                                <!-- Filter Buttons -->
                                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 d-flex gap-1">
                                    <button type="button" id="rentFilter" class="btn btn-primary pm-btn-sm flex-fill">
                                        <i class="fa fa-filter"></i> Rent Filter
                                    </button>
                                    <button type="button" id="sellFilter" class="btn btn-danger pm-btn-sm flex-fill">
                                        <i class="fa fa-filter"></i> Sell Filter
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary pm-btn-sm" onclick="resetPriceFilter()" title="Reset Filters">
                                        <i class="fa fa-refresh"></i>
                                    </button>
                                </div>

                                <!-- Instant Search Box -->
                                <div class="col-xl-5 col-lg-3 col-md-12 col-sm-12">
                                    <div class="input-group">
                                        <span class="input-group-text py-0 px-2" style="font-size:12px; background:#f8fafc;"><i class="fa fa-search text-muted"></i></span>
                                        <input type="text" id="liveSearchInput" class="form-control pm-input-sm" placeholder="Search SKU or name...">
                                        <button class="btn btn-outline-secondary pm-btn-sm py-0 px-2" type="button" onclick="$('#liveSearchInput').val('').trigger('input');" title="Clear">
                                            <i class="fa fa-times"></i>
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
                                    <span class="pm-stat-tag total" id="statCategoryTotal">0</span>
                                </span>

                                <span class="pm-stat-pill">
                                    After Filter: 
                                    <span class="pm-stat-tag visible" id="statVisibleCount">0 Visible</span>
                                </span>

                                <span class="pm-stat-pill">
                                    In Tray: 
                                    <span class="pm-stat-tag tray" id="statSelectedCount">0</span>
                                </span>

                                <span class="text-muted font-italic" id="filterStatusText" style="font-size: 12px;"></span>
                            </div>

                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-outline-primary pm-btn-sm" style="height:28px; font-size:11.5px;" onclick="selectAllFiltered()">
                                    <i class="fa fa-check-square-o"></i> Select All Filtered
                                </button>
                                <button type="button" class="btn btn-outline-secondary pm-btn-sm" style="height:28px; font-size:11.5px;" onclick="deselectAllFiltered()">
                                    <i class="fa fa-square-o"></i> Deselect Filtered
                                </button>
                            </div>
                        </div>

                        <!-- Products Grid Container -->
                        <div id="categoryProductsContainer">
                            <div class="text-center py-5 text-muted">
                                <i class="fa fa-th-large fa-2x mb-2 text-secondary" style="opacity: 0.35;"></i>
                                <div style="font-size:13.5px; font-weight:600;">Select a category above to load products</div>
                                <div style="font-size:11.5px; color:#94a3b8;">Products match the live storefront catalog with real-time stock and prices.</div>
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
                    $("#selectedCountBadge").html('<i class="fa fa-check-circle"></i> ' + arr.length + ' Selected');
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
                        confirmButtonColor: '#ef4444',
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
                    $('#categoryProductsContainer').html('<div class="text-center py-5 text-muted"><i class="fa fa-th-large fa-2x mb-2 text-secondary" style="opacity: 0.35;"></i><div style="font-size:13px;">Select a category above to view products</div></div>');
                }

                // AJAX Loaders
                $(document).on('change', '#garmentid', function () {
                    var garmentid = $(this).val();
                    if (!garmentid) {
                        $('#statsBarContainer').hide();
                        $("#categoryProductsContainer").html('<div class="text-center py-5 text-muted"><i class="fa fa-th-large fa-2x mb-2 text-secondary" style="opacity: 0.35;"></i><div style="font-size:13px;">Select an apparel category</div></div>');
                        return;
                    }
                    loadProductsAjax('./garmentdestailsShowPDF.php', { garmentid: garmentid });
                });

                $(document).on('change', '#jewelid', function () {
                    var jewelid = $(this).val();
                    if (!jewelid) {
                        $('#statsBarContainer').hide();
                        $("#categoryProductsContainer").html('<div class="text-center py-5 text-muted"><i class="fa fa-th-large fa-2x mb-2 text-secondary" style="opacity: 0.35;"></i><div style="font-size:13px;">Select a jewellery category</div></div>');
                        return;
                    }
                    loadProductsAjax('./jeweldestailsShowPDF.php', { jewelid: jewelid });
                });

                function loadProductsAjax(url, data) {
                    $("#categoryProductsContainer").html(
                        '<div class="text-center py-5">' +
                        '  <i class="fa fa-spinner fa-spin fa-2x text-primary mb-2"></i>' +
                        '  <div style="font-size:13px; font-weight:600;">Loading Products...</div>' +
                        '  <div style="font-size:11.5px; color:#94a3b8;">Fetching live inventory & prices directly from ProductService...</div>' +
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
                    currentFilterType = 'rent';
                    applyAllFilters();
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Filtered by Rent Price!',
                        showConfirmButton: false,
                        timer: 1200
                    });
                });

                $(document).on('click', '#sellFilter', function () {
                    currentFilterType = 'sell';
                    applyAllFilters();
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Filtered by Sell Price!',
                        showConfirmButton: false,
                        timer: 1200
                    });
                });

                function resetPriceFilter(triggerUpdate = true) {
                    currentFilterType = null;
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