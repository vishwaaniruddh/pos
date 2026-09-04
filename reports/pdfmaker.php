<?php
// session_start(); 
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

    if (!isset($web_con) || !$web_con) {
        $dbhost = "localhost";
        $dbuser = "u464193275_srishrinjuser";
        $dbpass = "9b@hMgk!=zI";
        $db = "u464193275_srishrinjewels";
        
        $web_con = @new mysqli($dbhost, $dbuser, $dbpass, $db);
        if (!$web_con || $web_con->connect_error) {
            $is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);
            if ($is_local) {
                $web_con = @mysqli_connect("localhost", "root", "", "u464193275_srishringarr");
            } else {
                $web_con = ($con && $con !== true) ? $con : @mysqli_connect("localhost", "u464193275_sarmicropos", "Mypos1234", "u464193275_srishringarr");
            }
        }
    }
    ?>

    <!-- partial -->
    <div class="main-panel">
        <div class="content-wrapper">

            <script src="https://code.jquery.com/jquery-3.7.1.min.js"
                integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            <style>
                :root {
                    --primary-blue: #4b49ac;
                    --primary-hover: #3f3d91;
                    --sheet-green: #10b981;
                    --slate-bg: #f5f7ff;
                    --border-color: #e2e8f0;
                }

                .catalog-card {
                    border: none;
                    border-radius: 12px;
                    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.05);
                    background: #ffffff;
                    margin-bottom: 24px;
                }

                .catalog-card-header {
                    background: linear-gradient(135deg, #4b49ac 0%, #6865d1 100%);
                    color: white;
                    border-radius: 12px 12px 0 0 !important;
                    padding: 18px 24px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }

                .catalog-card-header h2 {
                    font-size: 1.25rem;
                    font-weight: 700;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .nav-pills-custom {
                    display: flex;
                    gap: 10px;
                    background: #f1f5f9;
                    padding: 5px;
                    border-radius: 10px;
                    margin-bottom: 20px;
                }

                .nav-pills-custom .tab-btn {
                    flex: 1;
                    padding: 10px 18px;
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 14px;
                    color: #64748b;
                    background: transparent;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                }

                .nav-pills-custom .tab-btn.active {
                    background: #ffffff;
                    color: var(--primary-blue);
                    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
                }

                .tab-pane-content {
                    display: none;
                    animation: fadeIn 0.25s ease;
                }

                .tab-pane-content.active {
                    display: block;
                }

                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(4px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                .sku-textarea {
                    font-family: 'JetBrains Mono', monospace;
                    font-size: 13px;
                    border: 2px solid var(--border-color);
                    border-radius: 10px;
                    padding: 12px 16px;
                    transition: border-color 0.2s ease;
                    resize: vertical;
                }

                .sku-textarea:focus {
                    border-color: var(--primary-blue);
                    box-shadow: 0 0 0 3px rgba(75, 73, 172, 0.15);
                    outline: none;
                }

                .filter-box {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 10px;
                    padding: 16px;
                    margin-bottom: 20px;
                }

                .badge-count {
                    background: rgba(255, 255, 255, 0.25);
                    color: white;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 13px;
                    font-weight: 600;
                }

                .sku-chip {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    background: #eef2ff;
                    color: #4338ca;
                    border: 1px solid #c7d2fe;
                    padding: 4px 10px;
                    border-radius: 16px;
                    font-size: 12px;
                    font-weight: 600;
                    margin-right: 6px;
                    margin-bottom: 6px;
                }

                .sku-chip .remove-chip {
                    cursor: pointer;
                    color: #818cf8;
                    transition: color 0.15s ease;
                }

                .sku-chip .remove-chip:hover {
                    color: #dc2626;
                }

                .select-custom {
                    border-radius: 8px;
                    border: 1px solid #cbd5e1;
                    padding: 10px 14px;
                    font-size: 14px;
                    font-weight: 500;
                }
            </style>

            <!-- Main Catalog Dashboard Card -->
            <div class="card catalog-card">
                <div class="catalog-card-header">
                    <h2><i class="fa fa-file-pdf-o"></i> PDF Catalog Generator</h2>
                    <span class="badge-count" id="selectedCountBadge">0 Products Selected</span>
                </div>
                <div class="card-body p-4">

                    <form action="review_pdfmaker.php" method="POST" id="pdfForm">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="font-weight-bold text-dark m-0" style="font-size: 15px;">
                                    <i class="fa fa-list"></i> Selected Products / SKUs List
                                </label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="cleanSkus()">
                                        <i class="fa fa-magic"></i> Clean & Deduplicate
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearSkus()">
                                        <i class="fa fa-trash"></i> Clear All
                                    </button>
                                </div>
                            </div>
                            <textarea id="skus" name="sku" class="form-control sku-textarea" rows="4" 
                                      placeholder="Selected SKUs will appear here automatically when clicked from the category list below, or you can paste SKUs/Product IDs directly (e.g. FM4197-1-1234, FM3473-3-5678)..."></textarea>
                            
                            <!-- Chips container -->
                            <div id="skuChips" class="mt-2"></div>
                        </div>

                        <div class="d-flex justify-content-end mb-4">
                            <button type="submit" class="btn btn-success btn-lg px-4" style="border-radius: 8px;">
                                <i class="fa fa-arrow-right"></i> Proceed to Image Selection & Review
                            </button>
                        </div>
                    </form>

                    <hr class="my-4" />

                    <!-- Category Selector & Filters -->
                    <div class="nav nav-pills-custom">
                        <button class="tab-btn active" id="apparelTab" onclick="switchCategoryTab('apparel')">
                            <i class="fa fa-female"></i> Apparel Categories
                        </button>
                        <button class="tab-btn" id="jewelTab" onclick="switchCategoryTab('jewellery')">
                            <i class="fa fa-gem"></i> Jewellery Categories
                        </button>
                    </div>

                    <!-- Price Filter Controls -->
                    <div class="filter-box">
                        <div class="row align-items-center">
                            <div class="col-md-5 col-sm-12 mb-2 mb-md-0">
                                <label class="font-weight-bold text-dark mb-1" style="font-size: 13px;">Price Range (₹)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" id="minPrice" name="minPrice" class="form-control" placeholder="Min price">
                                    <span class="input-group-text">to</span>
                                    <input type="number" id="maxPrice" name="maxPrice" class="form-control" placeholder="Max price">
                                </div>
                            </div>
                            <div class="col-md-7 col-sm-12 d-flex gap-2 align-items-end mt-2 mt-md-0">
                                <button type="button" id="rentFilter" class="btn btn-primary flex-fill">
                                    <i class="fa fa-filter"></i> Apply Rent Price Filter
                                </button>
                                <button type="button" id="sellFilter" class="btn btn-danger flex-fill">
                                    <i class="fa fa-filter"></i> Apply Sell Price Filter
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetPriceFilter()">
                                    <i class="fa fa-refresh"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Panes -->
                    <div id="apparel" class="tab-pane-content active">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold text-dark"><i class="fa fa-tag"></i> Select Apparel Sub-Category</label>
                            <select id="garmentid" name="garmentid" class="form-select form-control select-custom">
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
                        <div id="garmentdestailsShow" class="mt-3"></div>
                    </div>

                    <div id="jewellery" class="tab-pane-content">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold text-dark"><i class="fa fa-tag"></i> Select Jewellery Sub-Category</label>
                            <select id="jewelid" name="jewelid" class="form-select form-control select-custom">
                                <option value="">-- Choose Jewellery Category --</option>
                                <?php
                                if ($web_con) {
                                    $garsql = mysqli_query($web_con, "SELECT * FROM subcat1 ORDER BY name ASC");
                                    if ($garsql) {
                                        while ($garsql_result = mysqli_fetch_assoc($garsql)) {
                                            $name = $garsql_result['name'];
                                            $subcat_id = $garsql_result['subcat_id'];
                                            echo '<option value="' . htmlspecialchars($subcat_id) . '">' . htmlspecialchars(ucwords(strtolower($name))) . '</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div id="jewelDetailsShow" class="mt-3"></div>
                    </div>

                </div>
            </div>

            <!-- Scripts -->
            <script>
                var uniqueSkus = new Set();

                $(document).ready(function () {
                    syncSkusFromTextarea();

                    $('#skus').on('input blur', function () {
                        syncSkusFromTextarea();
                    });
                });

                // Add to collection click handler
                $(document).on('click', '.addInExclusiveCollection', function () {
                    var productId = $(this).data("productid");
                    var sku = $(this).data("sku");
                    var combinedString = sku + "-" + productId;

                    if (!uniqueSkus.has(combinedString)) {
                        uniqueSkus.add(combinedString);
                        updateSkusUI();
                        
                        Swal.fire({
                            toast: true,
                            position: 'bottom-end',
                            icon: 'success',
                            title: 'Added: ' + sku,
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        Swal.fire({
                            toast: true,
                            position: 'bottom-end',
                            icon: 'info',
                            title: 'Already in collection: ' + sku,
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
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
                }

                function updateSkusUI() {
                    var arr = Array.from(uniqueSkus);
                    $("#skus").val(arr.join(', '));
                    updateChipsUI();
                }

                function updateChipsUI() {
                    var arr = Array.from(uniqueSkus);
                    $("#selectedCountBadge").text(arr.length + ' Product' + (arr.length === 1 ? '' : 's') + ' Selected');

                    var chipsHtml = '';
                    arr.forEach(function (item) {
                        chipsHtml += '<span class="sku-chip">' +
                            escapeHtml(item) +
                            ' <i class="fa fa-times-circle remove-chip" onclick="removeSku(\'' + escapeJsString(item) + '\')"></i>' +
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
                        title: 'Clear Collection?',
                        text: "Are you sure you want to remove all selected products?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc2626',
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
                        timer: 1500
                    });
                }

                function switchCategoryTab(tabName) {
                    $('.tab-btn').removeClass('active');
                    $('.tab-pane-content').removeClass('active');

                    if (tabName === 'apparel') {
                        $('#apparelTab').addClass('active');
                        $('#apparel').addClass('active');
                    } else {
                        $('#jewelTab').addClass('active');
                        $('#jewellery').addClass('active');
                    }
                }

                // AJAX Category Loaders
                $(document).on('change', '#garmentid', function () {
                    var garmentid = $(this).val();
                    $("#garmentdestailsShow").html('<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">Loading apparel products...</p></div>');
                    if (!garmentid) {
                        $("#garmentdestailsShow").html('');
                        return;
                    }
                    $.ajax({
                        url: "./garmentdestailsShowPDF.php",
                        data: 'garmentid=' + garmentid,
                        success: function (response) {
                            $("#garmentdestailsShow").html(response);
                        },
                        error: function() {
                            $("#garmentdestailsShow").html('<div class="alert alert-warning">Could not load apparel details. Ensure server is connected.</div>');
                        }
                    });
                });

                $(document).on('change', '#jewelid', function () {
                    var jewelid = $(this).val();
                    $("#jewelDetailsShow").html('<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">Loading jewellery products...</p></div>');
                    if (!jewelid) {
                        $("#jewelDetailsShow").html('');
                        return;
                    }
                    $.ajax({
                        url: "./jeweldestailsShowPDF.php",
                        data: 'jewelid=' + jewelid,
                        success: function (response) {
                            $("#jewelDetailsShow").html(response);
                        },
                        error: function() {
                            $("#jewelDetailsShow").html('<div class="alert alert-warning">Could not load jewellery details. Ensure server is connected.</div>');
                        }
                    });
                });

                // Price Filters
                $(document).on('click', '#rentFilter', function () {
                    if ($('.product_grid').length === 0) {
                        Swal.fire({ icon: 'warning', title: 'Select a category first!' });
                        return;
                    }
                    var minPrice = parseFloat($("#minPrice").val());
                    var maxPrice = parseFloat($("#maxPrice").val());

                    if (isNaN(minPrice) && isNaN(maxPrice)) {
                        Swal.fire({ icon: 'info', title: 'Enter min or max rent price value!' });
                        return;
                    }

                    $('.product_grid').each(function () {
                        var rentPrice = parseFloat($(this).data('rent_price'));
                        if ((isNaN(minPrice) || rentPrice >= minPrice) && (isNaN(maxPrice) || rentPrice <= maxPrice)) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Filtered by Rent Price!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                });

                $(document).on('click', '#sellFilter', function () {
                    if ($('.product_grid').length === 0) {
                        Swal.fire({ icon: 'warning', title: 'Select a category first!' });
                        return;
                    }
                    var minPrice = parseFloat($("#minPrice").val());
                    var maxPrice = parseFloat($("#maxPrice").val());

                    if (isNaN(minPrice) && isNaN(maxPrice)) {
                        Swal.fire({ icon: 'info', title: 'Enter min or max sell price value!' });
                        return;
                    }

                    $('.product_grid').each(function () {
                        var sellingPrice = parseFloat($(this).data('selling_price'));
                        if ((isNaN(minPrice) || sellingPrice >= minPrice) && (isNaN(maxPrice) || sellingPrice <= maxPrice)) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Filtered by Sell Price!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                });

                function resetPriceFilter() {
                    $("#minPrice").val('');
                    $("#maxPrice").val('');
                    $('.product_grid').show();
                }

                function escapeHtml(text) {
                    return String(text)
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;")
                        .replace(/"/g, "&quot;")
                        .replace(/'/g, "&#039;");
                }

                function escapeJsString(str) {
                    return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                }
            </script>

        </div>
    </div>

    <?php if (file_exists('../footer.php')) include_once('../footer.php'); ?>
</div>
</div>
</div>

<script src="vendors/js/vendor.bundle.base.js"></script>
<script src="vendors/js/vendor.bundle.addons.js"></script>
<script src="js/off-canvas.js"></script>
<script src="js/hoverable-collapse.js"></script>
<script src="js/misc.js"></script>
<script src="js/settings.js"></script>
<script src="js/todolist.js"></script>

</body>
</html>