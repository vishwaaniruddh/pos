<?php
$apiAutoload = dirname(dirname(__DIR__)) . '/API/autoload.php';
if (file_exists($apiAutoload)) {
    require_once $apiAutoload;
}
include_once(__DIR__ . '/../db_connection.php');

$jewelid = isset($_REQUEST['jewelid']) ? trim($_REQUEST['jewelid']) : '';

if (empty($jewelid)) {
    echo '<div class="alert alert-warning my-2 text-center py-2" style="font-size: 13px;"><i class="fa fa-info-circle"></i> Please select a jewellery category.</div>';
    exit;
}

// Fetch directly from the unified ProductService engine
$service = new \API\Services\ProductService();
$result = $service->fetchProducts([
    'category' => 'jewel_sub:' . (int)$jewelid,
    'type' => 'jewellery',
    'limit' => 2000,
    'page' => 1,
    'min_price' => 0,
    'max_price' => 10000000
]);

$products = $result['products'] ?? [];
if (empty($products)) {
    // Fallback to check main category
    $result = $service->fetchProducts([
        'category' => 'jewel_main:' . (int)$jewelid,
        'type' => 'jewellery',
        'limit' => 2000,
        'page' => 1,
        'min_price' => 0,
        'max_price' => 10000000
    ]);
    $products = $result['products'] ?? [];
}

$total_products = count($products);

if ($total_products === 0) {
    echo '<div id="categoryProductStats" data-total="0" style="display:none;"></div>';
    echo '<div class="alert alert-info my-2 text-center py-2" style="font-size: 13px;"><i class="fa fa-info-circle"></i> No products found in this category.</div>';
    exit;
}
?>

<div id="categoryProductStats" data-total="<?php echo $total_products; ?>" style="display:none;"></div>

<div class="row g-2" id="productsGridContainer">
    <?php
    foreach ($products as $p) {
        $product_id = (int)$p['id'];
        $sku = trim($p['code']);
        $product_name = trim($p['name'] ?? '');
        $details = $p['details'] ?? [];

        $rentPrice = (float)($details['rent_price'] ?? 0);
        $salePrice = (float)($details['sale_price'] ?? 0);
        $mrp = (float)($details['mrp'] ?? 0);
        $inventory = (int)($details['inventory'] ?? 0);

        $imgUrl = !empty($p['images'][0]) ? $p['images'][0] : ($details['image_path'] ?? 'https://srishringarr.com/static/images/default.jpg');
        $combinedString = $sku . '-' . $product_id;
        $link = 'jewel_detail.php?id=' . $product_id . '&days=3';
    ?>
        <div class="col-xxl-2 col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-2 product_grid"
             id="card-<?php echo htmlspecialchars($product_id); ?>"
             data-productid="<?php echo htmlspecialchars($product_id); ?>"
             data-sku="<?php echo htmlspecialchars($sku); ?>"
             data-combined="<?php echo htmlspecialchars($combinedString); ?>"
             data-name="<?php echo htmlspecialchars(strtolower($product_name . ' ' . $sku)); ?>"
             data-selling_price="<?php echo $salePrice; ?>"
             data-rent_price="<?php echo $rentPrice; ?>"
             data-qty="<?php echo $inventory; ?>">
            
            <div class="compact-product-card">
                <div class="card-thumb-box">
                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" 
                         alt="<?php echo htmlspecialchars($sku); ?>"
                         loading="lazy" 
                         class="card-thumb-img" 
                         onerror="this.src='https://srishringarr.com/static/images/default.jpg';" />
                    
                    <span class="card-qty-badge <?php echo ($inventory > 0) ? 'in-stock' : 'out-stock'; ?>">
                        Qty: <?php echo $inventory; ?>
                    </span>
                </div>

                <div class="card-info-box">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="card-sku" title="<?php echo htmlspecialchars($sku); ?>">
                            <?php echo htmlspecialchars($sku); ?>
                        </span>
                        <?php if ($mrp > 0): ?>
                            <span class="card-mrp" title="MRP">₹<?php echo number_format($mrp, 0); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="card-title-text" title="<?php echo htmlspecialchars($product_name); ?>">
                        <?php echo htmlspecialchars($product_name); ?>
                    </div>

                    <div class="card-prices-grid">
                        <div class="card-price-col rent">
                            <span class="col-lbl">Rent</span>
                            <span class="col-val">₹<?php echo number_format($rentPrice, 0); ?></span>
                        </div>
                        <div class="card-price-col sell">
                            <span class="col-lbl">Sell</span>
                            <span class="col-val">₹<?php echo number_format($salePrice, 0); ?></span>
                        </div>
                    </div>

                    <button type="button" 
                            class="btn btn-card-action addInExclusiveCollection" 
                            data-productid="<?php echo htmlspecialchars($product_id); ?>" 
                            data-image="<?php echo htmlspecialchars($imgUrl); ?>" 
                            data-sku="<?php echo htmlspecialchars($sku); ?>" 
                            data-link="<?php echo htmlspecialchars($link); ?>">
                        <i class="fa fa-plus"></i> Add
                    </button>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<script>
    if (typeof updateProductCountsAfterFilter === 'function') {
        updateProductCountsAfterFilter();
    }
    if (typeof refreshCardButtonsState === 'function') {
        refreshCardButtonsState();
    }
</script>