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

                .pm-review-container {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                /* Header */
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

                /* Buttons */
                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border: 1px solid var(--pm-slate-900);
                    font-size: 13px;
                    font-weight: 600;
                    border-radius: 6px;
                    padding: 8px 20px;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
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
                    padding: 6px 14px;
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

                /* Cards */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 18px;
                    overflow: hidden;
                }

                .pm-card-header {
                    padding: 12px 16px;
                    background: #ffffff;
                    border-bottom: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 8px;
                }

                .pm-card-title {
                    font-size: 14px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                }

                .pm-badge-neutral {
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
                    font-size: 11.5px;
                    font-weight: 600;
                    padding: 3px 10px;
                    border-radius: 9999px;
                }

                .pm-card-body {
                    padding: 16px;
                }

                /* Angle Box */
                .angle-box {
                    transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
                    border: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                    border-radius: 8px;
                    padding: 10px;
                    cursor: pointer;
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                }

                .angle-box:hover {
                    border-color: #94a3b8;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                    transform: translateY(-2px);
                }

                .angle-box.selected {
                    border: 2px solid var(--pm-slate-900) !important;
                    background: var(--pm-slate-50) !important;
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                .angle-thumb-wrap {
                    height: 180px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                    background: var(--pm-slate-100);
                    border-radius: 6px;
                    margin-bottom: 8px;
                }

                .angle-thumb-img {
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                    transition: transform 0.2s ease;
                }

                .angle-box:hover .angle-thumb-img {
                    transform: scale(1.03);
                }

                .angle-footer {
                    padding-top: 8px;
                    border-top: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                }

                .angle-radio-label {
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--pm-slate-800);
                    margin: 0;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }

                .angle-radio-input {
                    accent-color: var(--pm-slate-900);
                    width: 15px;
                    height: 15px;
                    cursor: pointer;
                }

                /* Bottom Sticky Toolbar */
                .pm-sticky-bottom {
                    position: sticky;
                    bottom: 0;
                    background: rgba(255, 255, 255, 0.95);
                    backdrop-filter: blur(8px);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 14px 20px;
                    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.04);
                    margin-top: 24px;
                    margin-bottom: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 12px;
                    z-index: 10;
                }
            </style>

            <div class="pm-review-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa fa-picture-o text-muted" style="font-size: 18px;"></i> 
                            PDF Catalog — Image Angle Review
                        </h1>
                        <p class="pm-page-subtitle">Select your preferred camera view for each SKU before rendering the final catalog PDF.</p>
                    </div>
                    <div>
                        <a href="pdfmaker.php" class="pm-btn-outline">
                            <i class="fa fa-arrow-left"></i> Back to PDF Maker
                        </a>
                    </div>
                </div>

                <form action="process_pdfmaker.php" method="POST" id="catalogForm">

                    <!-- PDF Meta Settings Card -->
                    <div class="pm-card">
                        <div class="pm-card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-lg-6 col-md-8 col-sm-12">
                                    <label class="form-label font-weight-bold" style="font-size: 12.5px; color: var(--pm-slate-700); margin-bottom: 4px;">
                                        PDF Document Name
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text" style="background: var(--pm-slate-50); border-color: var(--pm-slate-200); color: var(--pm-slate-500); font-size: 13px;">
                                            <i class="fa fa-file-text-o"></i>
                                        </span>
                                        <input type="text" name="pdfName" class="form-control" 
                                               style="height: 38px; border-color: var(--pm-slate-200); font-size: 13px; color: var(--pm-slate-900);" 
                                               placeholder="e.g. Catalog_Wedding_Collection" 
                                               required 
                                               value="Catalog_<?php echo date('Ymd_His'); ?>" />
                                        <span class="input-group-text" style="background: var(--pm-slate-50); border-color: var(--pm-slate-200); color: var(--pm-slate-500); font-size: 13px;">.pdf</span>
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--pm-slate-500); margin-top: 4px;">
                                        This filename will be assigned to the generated catalog upon download.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    $raw_sku_input = isset($_REQUEST['sku']) ? trim($_REQUEST['sku']) : '';
                    $raw_sku_input = str_replace(array("\r\n", "\r", "\n"), ',', $raw_sku_input);
                    $skuar = array_filter(array_map('trim', explode(',', $raw_sku_input)));

                    if (empty($skuar)) {
                        ?>
                        <div class="pm-card text-center p-5">
                            <i class="fa fa-exclamation-circle fa-2x text-muted mb-2"></i>
                            <h4 style="font-size: 16px; font-weight: 700; color: var(--pm-slate-800);">No SKUs Submitted for Review</h4>
                            <p style="font-size: 13px; color: var(--pm-slate-500);">Please navigate back to the PDF Maker tray and select at least one product.</p>
                            <div class="mt-3">
                                <a href="pdfmaker.php" class="pm-btn-primary">
                                    <i class="fa fa-arrow-left"></i> Go to PDF Maker
                                </a>
                            </div>
                        </div>
                        <?php
                    } else {
                        $pathmain = 'https://srishringarr.com/yn/';
                        $totalSkus = count($skuar);
                        $skuIndex = 0;

                        foreach ($skuar as $k => $v) {
                            $v = trim($v);
                            if (empty($v)) continue;
                            $skuIndex++;

                            $parts = explode('-', $v);
                            if (count($parts) > 1) {
                                $product_id = array_pop($parts);
                                $sku = implode('-', $parts);
                            } else {
                                $sku = $v;
                                $product_id = $v;
                            }

                            $qryimg = false;
                            if ($web_con) {
                                $cleanSku = mysqli_real_escape_string($web_con, $sku);
                                $numId = (is_numeric($product_id) && (int)$product_id > 0) ? (int)$product_id : 0;
                                
                                $whereClauses = ["`pro_code` = '$cleanSku'", "`prod_name` = '$cleanSku'"];
                                if ($numId > 0) {
                                    $whereClauses[] = "`gproduct_id` = $numId";
                                    $whereClauses[] = "`product_id` = $numId";
                                }
                                $whereSql = implode(' OR ', $whereClauses);
                                $sqlimg = "SELECT img_name FROM `product_images_new` WHERE $whereSql ORDER BY rank ASC, id ASC";
                                $qryimg = mysqli_query($web_con, $sqlimg);
                            }

                            $num_rows = ($qryimg && mysqli_num_rows($qryimg) > 0) ? mysqli_num_rows($qryimg) : 0;
                            ?>
                            <div class="pm-card">
                                <div class="pm-card-header">
                                    <div class="pm-card-title">
                                        <span style="color: var(--pm-slate-500); font-weight: 500; font-size: 12.5px;">#<?php echo $skuIndex; ?></span>
                                        <strong><?php echo htmlspecialchars($sku); ?></strong>
                                        <?php if ($product_id && $product_id !== $sku): ?>
                                            <span style="color: var(--pm-slate-400); font-size: 11.5px; font-weight: 500;">(ID: <?php echo htmlspecialchars($product_id); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="pm-badge-neutral">
                                            <i class="fa fa-camera text-muted me-1"></i>
                                            <?php echo $num_rows; ?> Angle<?php echo ($num_rows === 1) ? '' : 's'; ?> Available
                                        </span>
                                    </div>
                                </div>
                                <div class="pm-card-body">
                                    <div class="row g-3">
                                        <?php
                                        if ($num_rows > 0) {
                                            $imgCount = 0;
                                            while ($rowimg23 = mysqli_fetch_array($qryimg)) {
                                                $imgCount++;
                                                $raw_img = str_replace("/ ", "/", $rowimg23[0]);
                                                $raw_img = ltrim(trim($raw_img), '/');

                                                if (strpos($raw_img, 'uploads/') === 0) {
                                                    $zoom_img = $pathmain . $raw_img;
                                                } else {
                                                    $zoom_img = $pathmain . "uploads/" . $raw_img;
                                                }

                                                $angle_img = $pathmain . "thumbs/" . basename($raw_img);
                                                $radio_id = 'img_' . md5($v . '_' . $imgCount);
                                                $radio_name = htmlspecialchars($sku . '-' . $product_id);
                                                $radio_value = htmlspecialchars($v . '-' . basename($angle_img));
                                                $checked = ($imgCount === 1) ? 'checked' : '';
                                                ?>
                                                <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6 col-sm-6">
                                                    <div class="angle-box <?php echo ($imgCount === 1) ? 'selected' : ''; ?>" onclick="selectCardRadio(this)">
                                                        <div class="angle-thumb-wrap">
                                                            <img src="<?php echo htmlspecialchars($zoom_img); ?>" 
                                                                 alt="<?php echo htmlspecialchars($sku); ?> - Angle <?php echo $imgCount; ?>" 
                                                                 class="angle-thumb-img" 
                                                                 loading="lazy"
                                                                 onerror="this.onerror=null; this.src='https://srishringarr.com/yn/uploads/no-image.jpg';" />
                                                        </div>
                                                        <div class="angle-footer">
                                                            <label class="angle-radio-label" for="<?php echo $radio_id; ?>">
                                                                <input type="radio" 
                                                                       id="<?php echo $radio_id; ?>"
                                                                       class="angle-radio-input" 
                                                                       name="<?php echo $radio_name; ?>" 
                                                                       value="<?php echo $radio_value; ?>" 
                                                                       <?php echo $checked; ?> 
                                                                       onclick="event.stopPropagation(); updateBoxStyle(this);" />
                                                                <span>Angle <?php echo $imgCount; ?></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <div class="col-12">
                                                <div class="p-3 text-center" style="background: var(--pm-slate-50); border: 1px dashed var(--pm-slate-200); border-radius: 6px;">
                                                    <i class="fa fa-info-circle text-muted mb-1"></i>
                                                    <div style="font-size: 13px; font-weight: 600; color: var(--pm-slate-700);">No specific camera angles found for SKU <?php echo htmlspecialchars($sku); ?></div>
                                                    <div style="font-size: 11.5px; color: var(--pm-slate-500);">The catalog generator will attempt to fall back to the default storefront product thumbnail.</div>
                                                    
                                                    <!-- Invisible radio so the SKU is still passed to the generator -->
                                                    <input type="hidden" name="<?php echo htmlspecialchars($sku . '-' . $product_id); ?>" value="<?php echo htmlspecialchars($v); ?>-default" />
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>

                        <!-- Sticky Bottom Toolbar -->
                        <div class="pm-sticky-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="pm-badge-neutral" style="font-size: 12.5px; padding: 6px 14px;">
                                    <i class="fa fa-check-square-o text-muted me-1"></i>
                                    <strong><?php echo $totalSkus; ?></strong> Products Ready
                                </span>
                                <span style="font-size: 12px; color: var(--pm-slate-500);">Review complete. Click to render PDF catalog.</span>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="pdfmaker.php" class="pm-btn-outline">
                                    <i class="fa fa-arrow-left"></i> Back
                                </a>
                                <button type="submit" name="submit" class="pm-btn-primary" style="height: 38px;">
                                    <i class="fa fa-file-pdf-o"></i> Generate Final PDF
                                </button>
                            </div>
                        </div>

                        <?php
                    }
                    ?>
                </form>

            </div>

            <script>
                function selectCardRadio(boxElement) {
                    var radioBtn = boxElement.querySelector('input[type="radio"]');
                    if (radioBtn) {
                        radioBtn.checked = true;
                        updateBoxStyle(radioBtn);
                    }
                }

                function updateBoxStyle(radioBtn) {
                    var cardBody = radioBtn.closest('.pm-card-body');
                    if (cardBody) {
                        var boxes = cardBody.querySelectorAll('.angle-box');
                        boxes.forEach(function(b) { b.classList.remove('selected'); });
                    }
                    var currentBox = radioBtn.closest('.angle-box');
                    if (currentBox) {
                        currentBox.classList.add('selected');
                    }
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