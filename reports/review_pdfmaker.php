<?php
// session_start() ; 
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
                $web_con = @mysqli_connect("localhost", "root", "", "u464193275_srishrinjewels");
            } else {
                $web_con = ($con && $con !== true) ? $con : @mysqli_connect("localhost", "u464193275_sarmicropos", "Mypos1234", "u464193275_srishrinjewels");
            }
        }
    }
    ?>

    <!-- partial -->
    <div class="main-panel">
        <div class="content-wrapper">

            <script src="https://code.jquery.com/jquery-3.7.1.min.js"
                integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
            <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            <style>
                .img-box {
                    transition: all 0.2s ease;
                    border: 2px solid #e0e0e0 !important;
                    background: #fff;
                    border-radius: 8px !important;
                }
                .img-box:hover {
                    border-color: #3085d6 !important;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                .img-box.selected {
                    border-color: #28a745 !important;
                    background: #f0fff4 !important;
                }
            </style>

            <div class="card mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h3 class="m-0" style="font-size: 1.25rem;">PDF Generator - Image Review & Selection</h3>
                    <a href="pdfmaker.php" class="btn btn-sm btn-outline-light"><i class="fa fa-arrow-left"></i> Back to PDF Maker</a>
                </div>
                <div class="card-body">
                    <form action="process_pdfmaker.php" method="POST">
                        <div class="row mb-4">
                            <div class="col-md-6 col-sm-12">
                                <label class="form-label font-weight-bold">PDF Document Name</label>
                                <input type="text" name="pdfName" class="form-control form-control-lg" placeholder="e.g. Catalog_July_2026" required value="Catalog_<?php echo date('Ymd_His'); ?>" />
                            </div>
                        </div>

                        <hr class="mb-4" />

                        <?php
                        $raw_sku_input = isset($_REQUEST['sku']) ? trim($_REQUEST['sku']) : '';
                        $raw_sku_input = str_replace(array("\r\n", "\r", "\n"), ',', $raw_sku_input);
                        $skuar = array_filter(array_map('trim', explode(',', $raw_sku_input)));

                        if (empty($skuar)) {
                            echo '<div class="alert alert-warning">No SKUs or products submitted for review. Please go back to <a href="pdfmaker.php">PDF Maker</a> and select products.</div>';
                        } else {
                            $pathmain = 'https://srishringarr.com/yn/';

                            foreach ($skuar as $k => $v) {
                                $v = trim($v);
                                if (empty($v)) continue;

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
                                    $sqlimg = "SELECT img_name FROM `product_images_new` WHERE `gproduct_id`='" . mysqli_real_escape_string($web_con, $product_id) . "'";
                                    $qryimg = mysqli_query($web_con, $sqlimg);

                                    if (!$qryimg || mysqli_num_rows($qryimg) == 0) {
                                        $sqlimg = "SELECT img_name FROM `product_images_new` WHERE `product_id`='" . mysqli_real_escape_string($web_con, $product_id) . "'";
                                        $qryimg = mysqli_query($web_con, $sqlimg);
                                    }

                                    if (!$qryimg || mysqli_num_rows($qryimg) == 0) {
                                        $sqlimg = "SELECT img_name FROM `product_images_new` WHERE `product_code`='" . mysqli_real_escape_string($web_con, $sku) . "' OR `sku`='" . mysqli_real_escape_string($web_con, $sku) . "'";
                                        $qryimg = mysqli_query($web_con, $sqlimg);
                                    }
                                }

                                $num_rows = ($qryimg && mysqli_num_rows($qryimg) > 0) ? mysqli_num_rows($qryimg) : 0;
                                ?>
                                <div class="card mb-4 shadow-sm">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h5 class="m-0 text-dark">
                                            <strong>SKU: <?php echo htmlspecialchars($sku); ?></strong> 
                                            <span class="text-muted" style="font-size: 13px;">(ID: <?php echo htmlspecialchars($product_id); ?>)</span>
                                        </h5>
                                        <span class="badge badge-<?php echo $num_rows > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo $num_rows; ?> Image(s) Found
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
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
                                                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                                        <div class="img-box p-2 text-center <?php echo $imgCount === 1 ? 'selected' : ''; ?>" onclick="selectCardRadio(this)">
                                                            <div style="height: 180px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #fff; border-radius: 4px;">
                                                                <img src="<?php echo htmlspecialchars($zoom_img); ?>" 
                                                                     alt="<?php echo htmlspecialchars($sku); ?>" 
                                                                     style="max-width:100%; max-height:100%; object-fit:contain;" 
                                                                     onerror="this.onerror=null; this.src='https://srishringarr.com/yn/uploads/no-image.jpg';" />
                                                            </div>
                                                            <div class="mt-2 pt-2 border-top">
                                                                <div class="custom-control custom-radio custom-control-inline">
                                                                    <input type="radio" 
                                                                           id="<?php echo $radio_id; ?>"
                                                                           class="radio-group custom-control-input" 
                                                                           name="<?php echo $radio_name; ?>" 
                                                                           value="<?php echo $radio_value; ?>" 
                                                                           <?php echo $checked; ?> 
                                                                           onclick="event.stopPropagation(); updateBoxStyle(this);" />
                                                                    <label class="custom-control-label font-weight-bold" for="<?php echo $radio_id; ?>" style="cursor:pointer;">
                                                                        Angle <?php echo $imgCount; ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                            } else {
                                                echo '<div class="col-12"><div class="alert alert-warning mb-0"><i class="fa fa-exclamation-triangle"></i> No images found in database for SKU <strong>' . htmlspecialchars($sku) . '</strong> (ID: ' . htmlspecialchars($product_id) . ').</div></div>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                            <div class="text-end text-right mt-4 mb-4">
                                <button type="submit" name="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fa fa-file-pdf-o"></i> Generate Final PDF
                                </button>
                            </div>
                            <?php
                        }
                        ?>
                    </form>
                </div>
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
                    var cardBody = radioBtn.closest('.card-body');
                    if (cardBody) {
                        var boxes = cardBody.querySelectorAll('.img-box');
                        boxes.forEach(function(b) { b.classList.remove('selected'); });
                    }
                    var currentBox = radioBtn.closest('.img-box');
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

<script src="vendors/js/vendor.bundle.base.js"></script>
<script src="vendors/js/vendor.bundle.addons.js"></script>
<script src="js/off-canvas.js"></script>
<script src="js/hoverable-collapse.js"></script>
<script src="js/misc.js"></script>
<script src="js/settings.js"></script>
<script src="js/todolist.js"></script>

</body>
</html>