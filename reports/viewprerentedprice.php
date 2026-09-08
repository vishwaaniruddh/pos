<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../db_connection.php');

$con = null;
if (function_exists('OpenSrishringarrCon')) {
    $con = OpenSrishringarrCon();
}
if (!$con) {
    $is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);
    if ($is_local) {
        $con = @mysqli_connect("localhost", "root", "", "u464193275_srishringarr");
    } else {
        $con = @mysqli_connect("localhost", "u464193275_sarmicropos", "Mypos1234", "u464193275_srishringarr");
    }
}

if (!function_exists('round_amount')) {
    function round_amount($amount)
    {
        $amount = (int)$amount;
        $add_amount = 0;
        $round_num = substr($amount, -2);
        if ($round_num < 50 && $round_num != 0) {
            $add_amount = 50 - $round_num;
        }
        if ($round_num > 50 && $round_num != 0) {
            $add_amount = 100 - $round_num;
        }
        return $amount + $add_amount;
    }
}

// Extract SKU list safely
$raw_sku = $_REQUEST['sku'] ?? [];
$sku = [];
if (is_string($raw_sku)) {
    $sku = preg_split('/[\s,]+/', trim($raw_sku), -1, PREG_SPLIT_NO_EMPTY);
} elseif (is_array($raw_sku)) {
    foreach ($raw_sku as $item) {
        if (is_string($item)) {
            $parts = preg_split('/[\s,]+/', trim($item), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($parts as $p) {
                if ($p !== '') $sku[] = $p;
            }
        }
    }
}
$sku = array_values(array_unique(array_filter(array_map('trim', $sku))));

$todaysdt = date("Y-m-d");

// Include POS shell header & navbars
if (file_exists(__DIR__ . '/../top-header.php')) include_once(__DIR__ . '/../top-header.php');
if (file_exists(__DIR__ . '/../top-navbar.php')) include_once(__DIR__ . '/../top-navbar.php');
?>

<div class="container-fluid page-body-wrapper">
    <?php if (file_exists(__DIR__ . '/../navbar.php')) include_once(__DIR__ . '/../navbar.php'); ?>
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.25rem 1.5rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
            <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

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

                /* Header Toolbar */
                .pm-header-toolbar {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 12px;
                    margin-bottom: 16px;
                }

                .pm-page-title {
                    font-size: 19px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 9px;
                    letter-spacing: -0.02em;
                }

                .pm-page-subtitle {
                    font-size: 12.5px;
                    color: var(--pm-slate-500);
                    margin-top: 2px;
                    margin-bottom: 0;
                }

                .pm-toolbar-actions {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    flex-wrap: wrap;
                }

                /* 5 Compact KPI Grid across a single row */
                .pm-kpi-grid {
                    display: grid;
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                    gap: 10px;
                    margin-bottom: 16px;
                }

                @media (max-width: 1200px) {
                    .pm-kpi-grid {
                        grid-template-columns: repeat(3, minmax(0, 1fr));
                    }
                }

                @media (max-width: 768px) {
                    .pm-kpi-grid {
                        grid-template-columns: repeat(1, minmax(0, 1fr));
                    }
                }

                .pm-kpi-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 11px 14px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    transition: border-color 0.15s ease;
                }

                .pm-kpi-card:hover {
                    border-color: var(--pm-slate-300);
                }

                .pm-kpi-top {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 6px;
                }

                .pm-kpi-label {
                    font-size: 10.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    color: var(--pm-slate-500);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .pm-kpi-icon {
                    width: 26px;
                    height: 26px;
                    border-radius: 6px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--pm-slate-600);
                    font-size: 11px;
                    flex-shrink: 0;
                }

                .pm-kpi-value {
                    font-size: 18px;
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
                    margin-top: 3px;
                    line-height: 1.2;
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
                    margin-bottom: 16px;
                    overflow: hidden;
                }

                .pm-card-header {
                    padding: 10px 16px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 10px;
                }

                .pm-card-title {
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 7px;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                }

                .pm-card-body {
                    padding: 12px 16px;
                }

                /* Inputs & Buttons */
                .pm-input-group {
                    display: flex;
                    gap: 8px;
                    align-items: center;
                    max-width: 600px;
                }

                .pm-input {
                    height: 36px;
                    padding: 0 12px;
                    font-size: 13px;
                    color: var(--pm-slate-900);
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    outline: none;
                    transition: all 0.15s ease;
                    flex: 1;
                }

                .pm-input:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                .pm-btn {
                    height: 36px;
                    padding: 0 14px;
                    font-size: 12.5px;
                    font-weight: 500;
                    border-radius: 6px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                    white-space: nowrap;
                    border: 1px solid transparent;
                }

                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff !important;
                    border-color: var(--pm-slate-900);
                }

                .pm-btn-primary:hover {
                    background: var(--pm-slate-800);
                    border-color: var(--pm-slate-800);
                }

                .pm-btn-outline {
                    background: #ffffff;
                    color: var(--pm-slate-700) !important;
                    border-color: var(--pm-slate-200);
                }

                .pm-btn-outline:hover {
                    background: var(--pm-slate-50);
                    border-color: var(--pm-slate-300);
                    color: var(--pm-slate-900) !important;
                }

                /* Chips Container */
                .pm-chips-tray {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 6px;
                    margin-top: 10px;
                    padding-top: 10px;
                    border-top: 1px solid var(--pm-slate-100);
                }

                .pm-chip {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 3px 8px;
                    border-radius: 5px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-800);
                    font-family: ui-monospace, monospace;
                    font-size: 11.5px;
                    font-weight: 600;
                }

                .pm-chip-remove {
                    cursor: pointer;
                    color: var(--pm-slate-400);
                    font-size: 12px;
                    transition: color 0.15s ease;
                }

                .pm-chip-remove:hover {
                    color: #e11d48;
                }

                /* Table Styling */
                .pm-table-container {
                    overflow-x: auto;
                    background: #ffffff;
                }

                .pm-table {
                    width: 100%;
                    border-collapse: separate;
                    border-spacing: 0;
                    font-size: 12px;
                    text-align: left;
                }

                .pm-table th {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    font-weight: 600;
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    padding: 9px 12px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    white-space: nowrap !important;
                }

                .pm-table td {
                    padding: 9px 12px;
                    border-bottom: 1px solid var(--pm-slate-100);
                    vertical-align: middle;
                    color: var(--pm-slate-800);
                }

                .pm-table tr:hover td {
                    background-color: var(--pm-slate-50);
                }

                .pm-table th.text-right, .pm-table td.text-right {
                    text-align: right;
                }

                .pm-table th.text-center, .pm-table td.text-center {
                    text-align: center;
                }

                .pm-amount {
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-weight: 600;
                    white-space: nowrap !important;
                    color: var(--pm-slate-900);
                }

                /* Editable cell styling */
                .pm-editable {
                    background: #ffffff;
                    border: 1px dashed var(--pm-slate-300);
                    border-radius: 4px;
                    padding: 3px 6px;
                    display: inline-block;
                    min-width: 50px;
                    outline: none;
                    transition: all 0.15s ease;
                    font-family: ui-monospace, SFMono-Regular, monospace;
                    font-weight: 600;
                }

                .pm-editable:focus {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                /* Badges */
                .pm-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    padding: 2px 6px;
                    font-size: 10.5px;
                    font-weight: 500;
                    border-radius: 4px;
                    white-space: nowrap !important;
                }

                .pm-badge-sku {
                    font-family: ui-monospace, monospace;
                    font-weight: 600;
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-900);
                    padding: 2px 6px;
                }

                .pm-badge-booking {
                    background: #f1f5f9;
                    border: 1px solid #cbd5e1;
                    color: #0f172a;
                    font-size: 11px;
                    font-weight: 600;
                }

                .pm-btn-icon-del {
                    background: transparent;
                    border: 1px solid transparent;
                    color: var(--pm-slate-400);
                    cursor: pointer;
                    padding: 4px 6px;
                    border-radius: 4px;
                    transition: all 0.15s ease;
                }

                .pm-btn-icon-del:hover {
                    background: #fee2e2;
                    border-color: #fecaca;
                    color: #dc2626;
                }

                /* Empty state */
                .pm-empty-state {
                    padding: 45px 20px;
                    text-align: center;
                }

                .pm-empty-icon {
                    width: 44px;
                    height: 44px;
                    border-radius: 50%;
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-400);
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 18px;
                    margin-bottom: 10px;
                }

                .pm-empty-title {
                    font-size: 14px;
                    font-weight: 600;
                    color: var(--pm-slate-800);
                    margin-bottom: 4px;
                }

                .pm-empty-desc {
                    font-size: 12.5px;
                    color: var(--pm-slate-500);
                    margin: 0;
                }

                /* Print Styles */
                @media print {
                    .top-header, .top-navbar, #sidebar, .pm-header-toolbar, .pm-kpi-grid, 
                    .pm-card-filter, .no-print, .pm-btn-icon-del {
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
                    .pm-card {
                        border: none !important;
                        box-shadow: none !important;
                    }
                    .pm-table th {
                        background: #f1f5f9 !important;
                        color: #000000 !important;
                        border: 1px solid #cbd5e1 !important;
                    }
                    .pm-table td {
                        border: 1px solid #cbd5e1 !important;
                        color: #000000 !important;
                    }
                    .pm-editable {
                        border: none !important;
                        padding: 0 !important;
                    }
                }
            </style>

            <!-- Page Header Toolbar -->
            <div class="pm-header-toolbar">
                <div>
                    <h1 class="pm-page-title">
                        <i class="fa-solid fa-calculator" style="color: var(--pm-slate-700);"></i>
                        Pre-Rented Price Evaluator
                    </h1>
                    <p class="pm-page-subtitle">Interactive rent, selling price, and deposit calculator with historical bookings and live margin breakdown</p>
                </div>
                <div class="pm-toolbar-actions no-print">
                    <?php if (!empty($sku)): ?>
                        <button type="button" class="pm-btn pm-btn-primary" onclick="window.print();">
                            <i class="fa-solid fa-print"></i>
                            Print Table
                        </button>
                        <a href="new_viewprerentedprice_print.php?<?= http_build_query(['sku' => $sku]) ?>" target="_blank" class="pm-btn pm-btn-outline" title="Open minimal print slip">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            Print Slip
                        </a>
                        <button type="button" class="pm-btn pm-btn-outline" onclick="clearAllSkus();">
                            <i class="fa-solid fa-trash-can"></i>
                            Clear All
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 5 Compact KPI Metric Cards (Single Row) -->
            <div class="pm-kpi-grid">
                <!-- Card 1: Selected SKUs -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Selected SKUs</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                    </div>
                    <div class="pm-kpi-value" id="kpi-total-skus"><?= count($sku) ?></div>
                    <div class="pm-kpi-sub">Evaluated products</div>
                </div>

                <!-- Card 2: Total Rent Value -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Total Rent Value</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    </div>
                    <div class="pm-kpi-value" id="kpi-total-rent">₹ 0.00</div>
                    <div class="pm-kpi-sub">Sum of rent prices</div>
                </div>

                <!-- Card 3: Total Selling Price -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Total Selling Price</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-tag"></i></div>
                    </div>
                    <div class="pm-kpi-value" id="kpi-total-selling">₹ 0.00</div>
                    <div class="pm-kpi-sub">Pre-rented sale price</div>
                </div>

                <!-- Card 4: Total MRP Value -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Total MRP Base</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-barcode"></i></div>
                    </div>
                    <div class="pm-kpi-value" id="kpi-total-mrp">₹ 0.00</div>
                    <div class="pm-kpi-sub">Original retail value</div>
                </div>

                <!-- Card 5: Total Deposit Required -->
                <div class="pm-kpi-card">
                    <div class="pm-kpi-top">
                        <span class="pm-kpi-label">Security Deposits</span>
                        <div class="pm-kpi-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    </div>
                    <div class="pm-kpi-value" id="kpi-total-deposit">₹ 0.00</div>
                    <div class="pm-kpi-sub">Refundable customer deposit</div>
                </div>
            </div>

            <!-- SKU Input Card -->
            <div class="pm-card pm-card-filter no-print">
                <div class="pm-card-header">
                    <h3 class="pm-card-title">
                        <i class="fa-solid fa-plus-circle"></i>
                        Add Products to Evaluation Tray
                    </h3>
                    <div style="font-size: 11.5px; color: var(--pm-slate-500);">
                        Enter or paste single/multiple SKU codes separated by commas or spaces
                    </div>
                </div>
                <div class="pm-card-body">
                    <form id="skuForm" method="GET">
                        <div class="pm-input-group">
                            <input type="text" id="newSkuInput" class="pm-input" placeholder="e.g. SET880, FM5210-3, YNL347..." autofocus />
                            <button type="submit" class="pm-btn pm-btn-primary">
                                <i class="fa-solid fa-plus"></i> Add Products
                            </button>
                        </div>
                    </form>

                    <?php if (!empty($sku)): ?>
                        <div class="pm-chips-tray">
                            <span style="font-size: 11px; color: var(--pm-slate-500); align-self: center; margin-right: 4px;">Active SKUs:</span>
                            <?php foreach ($sku as $s): ?>
                                <span class="pm-chip">
                                    <?= htmlspecialchars($s) ?>
                                    <i class="fa-solid fa-xmark pm-chip-remove" onclick="removeSku('<?= htmlspecialchars(addslashes($s)) ?>');" title="Remove <?= htmlspecialchars($s) ?>"></i>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pre-Rented Price Table Card -->
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-card-title">
                        <i class="fa-solid fa-table"></i>
                        Pre-Rented Price & Booking Breakdown
                    </h3>
                    <div style="font-size: 11.5px; color: var(--pm-slate-500);" class="no-print">
                        <i class="fa-solid fa-pen-to-square"></i> Rent, Selling, Discount, and Deposit cells are directly editable
                    </div>
                </div>

                <div class="pm-table-container">
                    <table class="pm-table" id="printableDiv">
                        <thead>
                            <tr>
                                <th class="no-print text-center" style="width: 40px;">Action</th>
                                <th style="white-space: nowrap !important;">Product SKU</th>
                                <th class="text-right" style="white-space: nowrap !important;">Rent Price (₹)</th>
                                <th class="text-right" style="white-space: nowrap !important;">Selling Price (₹)</th>
                                <th class="text-right" style="white-space: nowrap !important;">MRP (₹)</th>
                                <th class="no-print text-right" style="white-space: nowrap !important;">Cost Price (₹)</th>
                                <th class="text-center" style="white-space: nowrap !important; width: 80px;">Discount (%)</th>
                                <th class="text-right" style="white-space: nowrap !important;">Disc Selling (₹)</th>
                                <th class="text-right" style="white-space: nowrap !important;">Deposit (₹)</th>
                                <th class="no-print text-right" style="white-space: nowrap !important;">Total Rent Recd (₹)</th>
                                <th class="no-print text-right" style="white-space: nowrap !important;">MRP - Total Rent (₹)</th>
                                <th class="no-print text-right" style="white-space: nowrap !important;">Cost - Total Rent (₹)</th>
                                <th class="no-print text-right" style="white-space: nowrap !important;">MRP - Cost (₹)</th>
                                <th class="no-print" style="white-space: nowrap !important;">Upcoming Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sku)): ?>
                                <tr>
                                    <td colspan="14">
                                        <div class="pm-empty-state">
                                            <div class="pm-empty-icon"><i class="fa-solid fa-barcode"></i></div>
                                            <div class="pm-empty-title">Evaluation Tray is Empty</div>
                                            <p class="pm-empty-desc">Please enter product SKUs above to evaluate pre-rented pricing and booking history.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $totalrent = 0.0;
                                $totalselling = 0.0;
                                $totalmrp = 0.0;
                                $totalCostPrice = 0.0;
                                $totalDeposits = 0.0;
                                $totalCommissionAmount = 0.0;
                                $totalmrpMinusTotalRent = 0.0;
                                $totalcostMinusTotalRent = 0.0;
                                $totalmrpMinusCostPrice = 0.0;

                                foreach ($sku as $skuval):
                                    $safe_sku = mysqli_real_escape_string($con, $skuval);

                                    // Product details lookup
                                    $productsql = mysqli_query($con, "SELECT unit_price, quantity, category_type, cost_price FROM phppos_items WHERE name='$safe_sku' LIMIT 1");
                                    $pRow = $productsql ? mysqli_fetch_assoc($productsql) : null;

                                    $mrp        = (float)($pRow['unit_price'] ?? 0);
                                    $cost_price = (float)($pRow['cost_price'] ?? 0);
                                    $productType= (int)($pRow['category_type'] ?? 1);

                                    // Commission Amount lookup (past non-booked rents)
                                    $commissionAmount = 0.0;
                                    $re1 = mysqli_query($con, "
                                        SELECT SUM(CAST(REPLACE(commission_amt, ',', '') AS DECIMAL(10,2))) 
                                        FROM order_detail 
                                        WHERE item_id='$safe_sku' 
                                          AND bill_id IN (SELECT bill_id FROM phppos_rent WHERE booking_status != 'Booked')
                                    ");
                                    if ($re1 && $rero1 = mysqli_fetch_row($re1)) {
                                        $commissionAmount = (float)($rero1[0] ?? 0);
                                    }

                                    $currentsp = $mrp - $commissionAmount;
                                    $mrpMinusTotalRent = $mrp - $commissionAmount;
                                    $costMinusTotalRent = $cost_price - $commissionAmount;
                                    $mrpMinusCostPrice  = $mrp - $cost_price;

                                    $courier = 0;
                                    $lastSellingPrice = 0;
                                    $sellingPriceCalc = $mrpMinusTotalRent;
                                    $sellingPriceCalc -= ($sellingPriceCalc * 0.4);

                                    if ($productType === 1) {
                                        // Jewellery
                                        if ($mrp <= 2000) $courier = 100;
                                        elseif ($mrp <= 5000) $courier = 250;
                                        elseif ($mrp <= 10000) $courier = 500;
                                        else $courier = 1000;

                                        if ($mrp >= 10000) {
                                            $lastSellingPrice = ($sellingPriceCalc < 5000) ? 5000 : $sellingPriceCalc;
                                        } else {
                                            $lastSellingPrice = $mrp - ($mrp * 0.5);
                                        }

                                        if (isset($pRow['sales_price']) && (float)$pRow['sales_price'] > 0) {
                                            $lastSellingPrice = (float)$pRow['sales_price'];
                                        }

                                        if ($currentsp > 0) {
                                            if ($mrp <= 10000) {
                                                $rentprice = $mrp * 0.20;
                                                $addedRentPrice = $courier + $rentprice;
                                                $deposit = $mrp * 0.35;
                                            } else {
                                                if ($currentsp <= 40000) $rentprice = $currentsp * 0.20;
                                                elseif ($currentsp <= 60000) $rentprice = $currentsp * 0.17;
                                                else $rentprice = $currentsp * 0.15;

                                                $addedRentPrice = max(3000, $courier + $rentprice);
                                                $deposit = max(3000, $currentsp * 0.35);
                                            }
                                        } else {
                                            if ($mrp <= 10000) {
                                                $addedRentPrice = $courier + ($mrp * 0.20);
                                                $deposit = $mrp * 0.35;
                                            } else {
                                                $addedRentPrice = 3000;
                                                $deposit = 3000;
                                            }
                                        }
                                    } else {
                                        // Apparel
                                        if ($mrp >= 10000) {
                                            $lastSellingPrice = ($sellingPriceCalc < 5000) ? 5000 : $sellingPriceCalc;
                                        } else {
                                            $lastSellingPrice = $mrp - ($mrp * 0.5);
                                        }

                                        if (isset($pRow['sales_price']) && (float)$pRow['sales_price'] > 0) {
                                            $lastSellingPrice = (float)$pRow['sales_price'];
                                        }

                                        if ($currentsp > 0) {
                                            if ($mrp <= 10000) {
                                                $courier = 1000;
                                                $addedRentPrice = $courier + ($mrp * 0.20);
                                                $deposit = $mrp * 0.35;
                                            } else {
                                                $courier = 2000;
                                                if ($currentsp <= 40000) $rentprice = $currentsp * 0.20;
                                                elseif ($currentsp <= 60000) $rentprice = $currentsp * 0.17;
                                                else $rentprice = $currentsp * 0.15;

                                                $addedRentPrice = max(3000, $courier + $rentprice);
                                                $deposit = max(3000, $currentsp * 0.35);
                                            }
                                        } else {
                                            if ($mrp <= 10000) {
                                                $addedRentPrice = 1000 + ($mrp * 0.20);
                                                $deposit = $mrp * 0.35;
                                            } else {
                                                $addedRentPrice = 3000;
                                                $deposit = 3000;
                                            }
                                        }
                                    }

                                    $deposit        = round_amount($deposit);
                                    $addedRentPrice = round_amount($addedRentPrice);

                                    // Active Upcoming Bookings
                                    $booking_html = '<span style="color: var(--pm-slate-400); font-size: 11.5px;">No upcoming bookings</span>';
                                    $order_sql = mysqli_query($con, "
                                        SELECT r.bill_id, r.pick_date, r.delivery_date, r.booking_status 
                                        FROM order_detail od 
                                        INNER JOIN phppos_rent r ON od.bill_id = r.bill_id 
                                        WHERE od.item_id='$safe_sku' 
                                          AND (r.pick_date >= '$todaysdt' OR r.delivery_date >= '$todaysdt') 
                                          AND r.booking_status != 'Returned' 
                                        GROUP BY r.bill_id 
                                        ORDER BY r.pick_date ASC
                                    ");

                                    if ($order_sql && ($totalBookings = mysqli_num_rows($order_sql)) > 0) {
                                        $dates_arr = [];
                                        while ($bRow = mysqli_fetch_assoc($order_sql)) {
                                            $pDate = $bRow['pick_date'];
                                            $dDate = $bRow['delivery_date'];
                                            if ($pDate !== '0000-00-00' && $dDate !== '0000-00-00' && $pDate !== '' && $dDate !== '') {
                                                $dates_arr[] = date("d/m/y", strtotime($pDate)) . ' → ' . date("d/m/y", strtotime($dDate));
                                            }
                                        }
                                        $booking_html = '<span class="pm-badge pm-badge-booking">' . $totalBookings . ' Booking' . ($totalBookings > 1 ? 's' : '') . '</span>';
                                        if (!empty($dates_arr)) {
                                            $booking_html .= '<div style="font-size: 10.5px; color: var(--pm-slate-600); font-family: monospace; margin-top: 2px;">' . implode(', ', $dates_arr) . '</div>';
                                        }
                                    }

                                    $totalrent               += $addedRentPrice;
                                    $totalselling            += $lastSellingPrice;
                                    $totalmrp                += $mrp;
                                    $totalCostPrice          += $cost_price;
                                    $totalDeposits           += $deposit;
                                    $totalCommissionAmount   += $commissionAmount;
                                    $totalmrpMinusTotalRent  += $mrpMinusTotalRent;
                                    $totalcostMinusTotalRent += $costMinusTotalRent;
                                    $totalmrpMinusCostPrice  += $mrpMinusCostPrice;
                                ?>
                                    <tr data-sku="<?= htmlspecialchars($skuval) ?>">
                                        <td class="no-print text-center">
                                            <button type="button" class="pm-btn-icon-del" onclick="removeSku('<?= htmlspecialchars(addslashes($skuval)) ?>');" title="Remove Product">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                        <td style="white-space: nowrap !important;">
                                            <span class="pm-badge pm-badge-sku"><?= htmlspecialchars($skuval) ?></span>
                                        </td>
                                        <td class="text-right pm-amount">
                                            <span class="pm-editable rentPrice" contenteditable="true"><?= $addedRentPrice ?></span>
                                        </td>
                                        <td class="text-right pm-amount">
                                            <span class="pm-editable sellingPrice" contenteditable="true"><?= $lastSellingPrice ?></span>
                                        </td>
                                        <td class="text-right pm-amount mrpPrice">
                                            <?= number_format($mrp, 2, '.', '') ?>
                                        </td>
                                        <td class="no-print text-right pm-amount" style="color: var(--pm-slate-600);">
                                            <?= number_format($cost_price, 2, '.', '') ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="pm-editable discount" contenteditable="true">0</span>
                                        </td>
                                        <td class="text-right pm-amount discountedSellingPrice" style="color: var(--pm-slate-700);">
                                            <?= number_format($mrp, 2, '.', '') ?>
                                        </td>
                                        <td class="text-right pm-amount">
                                            <span class="pm-editable deposit" contenteditable="true"><?= $deposit ?></span>
                                        </td>
                                        <td class="no-print text-right pm-amount" style="color: var(--pm-slate-600);">
                                            <?= number_format($commissionAmount, 2, '.', '') ?>
                                        </td>
                                        <td class="no-print text-right pm-amount" style="color: var(--pm-slate-700);">
                                            <?= number_format($mrpMinusTotalRent, 2, '.', '') ?>
                                        </td>
                                        <td class="no-print text-right pm-amount" style="color: var(--pm-slate-700);">
                                            <?= number_format($costMinusTotalRent, 2, '.', '') ?>
                                        </td>
                                        <td class="no-print text-right pm-amount" style="color: var(--pm-slate-700);">
                                            <?= number_format($mrpMinusCostPrice, 2, '.', '') ?>
                                        </td>
                                        <td class="no-print" style="white-space: nowrap !important;">
                                            <?= $booking_html ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($sku)): ?>
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: 600;">
                                    <th class="no-print"></th>
                                    <th style="font-weight: 700;">Total (<?= count($sku) ?>):</th>
                                    <th class="text-right pm-amount" id="totalRent">₹ <?= number_format($totalrent, 2) ?></th>
                                    <th class="text-right pm-amount" id="totalSelling">₹ <?= number_format($totalselling, 2) ?></th>
                                    <th class="text-right pm-amount" id="totalMrp">₹ <?= number_format($totalmrp, 2) ?></th>
                                    <th class="no-print text-right pm-amount" id="totalCostPrice">₹ <?= number_format($totalCostPrice, 2) ?></th>
                                    <th></th>
                                    <th class="text-right pm-amount" id="totalDiscountedSellingPrice">₹ <?= number_format($totalmrp, 2) ?></th>
                                    <th class="text-right pm-amount" id="totalDeposit">₹ <?= number_format($totalDeposits, 2) ?></th>
                                    <th class="no-print text-right pm-amount">₹ <?= number_format($totalCommissionAmount, 2) ?></th>
                                    <th class="no-print text-right pm-amount">₹ <?= number_format($totalmrpMinusTotalRent, 2) ?></th>
                                    <th class="no-print text-right pm-amount">₹ <?= number_format($totalcostMinusTotalRent, 2) ?></th>
                                    <th class="no-print text-right pm-amount">₹ <?= number_format($totalmrpMinusCostPrice, 2) ?></th>
                                    <th class="no-print"></th>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Interactivity Script -->
            <script>
                function removeSku(skuToRemove) {
                    var url = new URL(window.location.href);
                    var skus = url.searchParams.getAll("sku[]");
                    var updated = skus.filter(function(s) {
                        return s.trim() !== skuToRemove.trim();
                    });

                    url.searchParams.delete("sku[]");
                    updated.forEach(function(s) {
                        url.searchParams.append("sku[]", s);
                    });

                    window.location.href = url.pathname + (updated.length ? '?' + url.searchParams.toString() : '');
                }

                function clearAllSkus() {
                    var url = new URL(window.location.href);
                    window.location.href = url.pathname;
                }

                function recalculateTableTotals() {
                    var totalRent = 0;
                    var totalSelling = 0;
                    var totalMrp = 0;
                    var totalDiscSelling = 0;
                    var totalDeposit = 0;

                    var rows = document.querySelectorAll('#printableDiv tbody tr[data-sku]');
                    var rowCount = rows.length;

                    rows.forEach(function(row) {
                        var rentCell = row.querySelector('.rentPrice');
                        var sellingCell = row.querySelector('.sellingPrice');
                        var mrpCell = row.querySelector('.mrpPrice');
                        var discountCell = row.querySelector('.discount');
                        var discSellingCell = row.querySelector('.discountedSellingPrice');
                        var depositCell = row.querySelector('.deposit');

                        var rentVal = parseFloat((rentCell ? rentCell.textContent : '0').replace(/[^0-9.-]/g, '')) || 0;
                        var sellingVal = parseFloat((sellingCell ? sellingCell.textContent : '0').replace(/[^0-9.-]/g, '')) || 0;
                        var mrpVal = parseFloat((mrpCell ? mrpCell.textContent : '0').replace(/[^0-9.-]/g, '')) || 0;
                        var discPerc = parseFloat((discountCell ? discountCell.textContent : '0').replace(/[^0-9.-]/g, '')) || 0;
                        var depositVal = parseFloat((depositCell ? depositCell.textContent : '0').replace(/[^0-9.-]/g, '')) || 0;

                        // Calculate discounted selling price: MRP * (1 - disc/100)
                        var calculatedDiscSelling = mrpVal * (1 - (discPerc / 100));
                        if (discSellingCell) {
                            discSellingCell.textContent = calculatedDiscSelling.toFixed(2);
                        }

                        totalRent += rentVal;
                        totalSelling += sellingVal;
                        totalMrp += mrpVal;
                        totalDiscSelling += calculatedDiscSelling;
                        totalDeposit += depositVal;
                    });

                    // Update footer cells
                    var elTotalRent = document.getElementById('totalRent');
                    var elTotalSelling = document.getElementById('totalSelling');
                    var elTotalMrp = document.getElementById('totalMrp');
                    var elTotalDisc = document.getElementById('totalDiscountedSellingPrice');
                    var elTotalDeposit = document.getElementById('totalDeposit');

                    if (elTotalRent) elTotalRent.textContent = '₹ ' + totalRent.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    if (elTotalSelling) elTotalSelling.textContent = '₹ ' + totalSelling.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    if (elTotalMrp) elTotalMrp.textContent = '₹ ' + totalMrp.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    if (elTotalDisc) elTotalDisc.textContent = '₹ ' + totalDiscSelling.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    if (elTotalDeposit) elTotalDeposit.textContent = '₹ ' + totalDeposit.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                    // Update 5 KPI Cards
                    $('#kpi-total-skus').text(rowCount);
                    $('#kpi-total-rent').text('₹ ' + totalRent.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#kpi-total-selling').text('₹ ' + totalSelling.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#kpi-total-mrp').text('₹ ' + totalMrp.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#kpi-total-deposit').text('₹ ' + totalDeposit.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                }

                document.addEventListener('DOMContentLoaded', function() {
                    // Handle SKU input form
                    var skuForm = document.getElementById('skuForm');
                    if (skuForm) {
                        skuForm.addEventListener('submit', function(e) {
                            e.preventDefault();
                            var input = document.getElementById('newSkuInput');
                            var rawVal = input ? input.value.trim() : '';
                            if (!rawVal) return;

                            var url = new URL(window.location.href);
                            var existingSkus = url.searchParams.getAll('sku[]');

                            // Split comma or whitespace separated values
                            var newTokens = rawVal.split(/[\s,]+/).filter(Boolean);
                            var combined = existingSkus.concat(newTokens);

                            // Deduplicate while preserving order
                            var unique = [];
                            combined.forEach(function(item) {
                                var clean = item.trim();
                                if (clean && unique.indexOf(clean) === -1) {
                                    unique.push(clean);
                                }
                            });

                            url.searchParams.delete('sku[]');
                            unique.forEach(function(s) {
                                url.searchParams.append('sku[]', s);
                            });

                            window.location.href = url.pathname + '?' + url.searchParams.toString();
                        });
                    }

                    // Attach input listeners to editable cells
                    document.querySelectorAll('.pm-editable').forEach(function(cell) {
                        cell.addEventListener('input', function() {
                            recalculateTableTotals();
                        });
                    });

                    // Initial recalculation to synchronize KPI cards
                    recalculateTableTotals();
                });
            </script>

        </div><!-- /content-wrapper -->
    </div><!-- /main-panel -->
</div><!-- /container-fluid page-body-wrapper -->

<?php
if ($con && function_exists('CloseCon')) {
    CloseCon($con);
}
?>
</body>
</html>
