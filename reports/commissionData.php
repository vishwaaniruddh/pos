<?php
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

// Current month and year defaults
$current_month = date('M');
$current_year  = date('Y');

$selectFrachise = isset($_REQUEST['selectFrachise']) ? trim($_REQUEST['selectFrachise']) : '0';
$selected_month = isset($_REQUEST['month']) ? trim($_REQUEST['month']) : $current_month;
$selected_year  = isset($_REQUEST['year']) ? trim($_REQUEST['year']) : $current_year;

$month_num_map = [
    'Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4,
    'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8,
    'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12,
    '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6,
    '7' => 7, '8' => 8, '9' => 9, '10' => 10, '11' => 11, '12' => 12
];

// Base SQL query
$sql = "
SELECT 
    a.item_id AS sku,
    a.total_amount AS totalProductAmount,
    a.qty,
    a.rent AS single_rent,
    r.throught,
    r.total_comm, 
    r.bill_date,
    r.cust_id,
    r.pick_date,
    r.delivery_date, 
    r.new_bill_number, 
    r.bill_id,
    r.comm_amount,
    (SELECT COUNT(*) FROM order_detail od WHERE od.bill_id = r.bill_id) AS total_records,
    r.comm_by,
    a.is_new_with_gst
FROM order_detail a
JOIN phppos_rent r ON a.bill_id = r.bill_id
WHERE r.company_name IN ('SS', 'Sri Shringarr')
";

if (!empty($selected_month) && $selected_month !== 'all' && isset($month_num_map[$selected_month])) {
    $m_num = (int)$month_num_map[$selected_month];
    $sql .= " AND MONTH(r.bill_date) = $m_num";
}

if (!empty($selected_year) && $selected_year !== 'all') {
    $y_num = (int)$selected_year;
    if ($y_num > 0) {
        $sql .= " AND YEAR(r.bill_date) = $y_num";
    }
}

$sql .= " ORDER BY a.bill_id DESC";

$query = mysqli_query($con, $sql);

// Totals accumulator
$totals = [
    'rentAmount'               => 0.0,
    'beauticianDiscountAmount' => 0.0,
    'netAmount'                => 0.0,
    'flyrobeCommissionAmount'  => 0.0,
    'thisProductTotalGst'      => 0.0,
    'thisssfsAmount'           => 0.0
];

// Helper caches
$item_cache = [];
$people_cache = [];

// =========================================================================
// 1. AJAX / HTML Table Fragment Output
// =========================================================================
if (!isset($_REQUEST['export'])) {
    ob_start();
    ?>
    <thead>
        <tr>
            <th style="width: 40px;" class="text-center">#</th>
            <th style="white-space: nowrap !important;">Bill Date</th>
            <th style="white-space: nowrap !important;">Product Type</th>
            <th style="white-space: nowrap !important;">SKU</th>
            <th style="white-space: nowrap !important;">Invoice No</th>
            <th style="white-space: nowrap !important;">Customer Name</th>
            <th style="white-space: nowrap !important;">Pick-up Date</th>
            <th style="white-space: nowrap !important;">Return Date</th>
            <th class="text-right" style="white-space: nowrap !important;">Rent (₹)</th>
            <th class="text-center" style="white-space: nowrap !important;">GST Rate</th>
            <th class="text-right" style="white-space: nowrap !important;">GST (₹)</th>
            <th class="text-center" style="white-space: nowrap !important;">Beautician</th>
            <th class="text-center" style="white-space: nowrap !important;">Comm Rate</th>
            <th class="text-right" style="white-space: nowrap !important;">Beautician (₹)</th>
            <th class="text-right" style="white-space: nowrap !important;">Net Amount (₹)</th>
            <th class="text-center" style="white-space: nowrap !important;">Flyrobe</th>
            <th class="text-right" style="white-space: nowrap !important;">SSFS Share (₹)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($query && mysqli_num_rows($query) > 0) {
            $srno = 1;
            while ($row = mysqli_fetch_assoc($query)) {
                $is_new_with_gst    = (int)$row['is_new_with_gst'];
                $sku                = trim($row["sku"]);
                $billDate           = $row['bill_date'];
                $bid                = (int)$row["bill_id"];
                $new_bill_number    = !empty($row['new_bill_number']) ? $row['new_bill_number'] : ('#' . $bid);
                $cust_id            = (int)$row['cust_id'];
                $qty                = max(1, (int)$row['qty']);
                $single_rent        = (float)$row['single_rent'];
                $totalProductAmount = (float)$row["totalProductAmount"];
                $total_records      = max(1, (int)$row['total_records']);

                // Customer Lookup (Cached)
                if (!isset($people_cache[$cust_id])) {
                    $peoplesql = mysqli_query($con, "SELECT first_name, last_name FROM phppos_people WHERE person_id='$cust_id' LIMIT 1");
                    if ($peoplesql && $pRes = mysqli_fetch_assoc($peoplesql)) {
                        $cName = trim(($pRes['first_name'] ?? '') . ' ' . ($pRes['last_name'] ?? ''));
                        $people_cache[$cust_id] = $cName ?: ('Customer #' . $cust_id);
                    } else {
                        $people_cache[$cust_id] = 'Customer #' . $cust_id;
                    }
                }
                $customerName = $people_cache[$cust_id];

                // Dates formatting
                $pick_date     = ($row['pick_date'] && $row['pick_date'] !== '0000-00-00') ? date('d-m-Y', strtotime($row['pick_date'])) : '—';
                $delivery_date = ($row['delivery_date'] && $row['delivery_date'] !== '0000-00-00') ? date('d-m-Y', strtotime($row['delivery_date'])) : '—';

                // Item Details Lookup (Cached)
                if (!isset($item_cache[$sku])) {
                    $safe_sku = mysqli_real_escape_string($con, $sku);
                    $item_sql = mysqli_query($con, "SELECT category_type, supplier_id FROM phppos_items WHERE name='$safe_sku' LIMIT 1");
                    if ($item_sql && $iRes = mysqli_fetch_assoc($item_sql)) {
                        $item_cache[$sku] = [
                            'category_type' => (int)($iRes['category_type'] ?? 1),
                            'supplier_id'   => trim($iRes['supplier_id'] ?? '')
                        ];
                    } else {
                        $item_cache[$sku] = [
                            'category_type' => 1,
                            'supplier_id'   => ''
                        ];
                    }
                }
                $item_info        = $item_cache[$sku];
                $supplier_id      = $item_info['supplier_id'];
                $isFlyrobeProduct = ($supplier_id === '242875') ? 'Yes' : 'No';
                $category_type    = ($item_info['category_type'] === 1) ? 'Jewellery' : 'Apparel';

                // GST & Taxable Calculations
                if ($is_new_with_gst === 1) {
                    if ($item_info['category_type'] === 1) {
                        $thisProductTotalTaxable = $single_rent / 1.03;
                        $thisProductTotalGst     = $thisProductTotalTaxable * 0.03;
                        $cgst = $sgst            = $thisProductTotalGst / 2;
                        $gst_rate                = '3%';
                    } else {
                        $thisProductTotalTaxable = $single_rent / 1.12;
                        $thisProductTotalGst     = $thisProductTotalTaxable * 0.12;
                        $cgst = $sgst            = $thisProductTotalGst / 2;
                        $gst_rate                = '12%';
                    }
                } else {
                    $gst_rate = '0%';
                    $cgst = $sgst = $thisProductTotalGst = 0.0;
                }

                $single_taxable = $single_rent - ($cgst + $sgst);

                // Beautician Commission Calculation
                $beautician_discountAmount = 0.0;
                if (!empty($row["throught"])) {
                    $total_comm_val = (float)$row["total_comm"];
                    $comm_amt_val   = (float)$row['comm_amount'];

                    if ($total_comm_val > 0) {
                        $beautician_discountAmount = $total_comm_val / $total_records;
                    } else if ($total_comm_val == 0) {
                        if ($row['comm_by'] === '%') {
                            $beautician_discountAmount = $single_taxable * ($comm_amt_val / 100);
                        } else {
                            $beautician_discountAmount = max(0, $single_taxable - $comm_amt_val);
                        }
                    }
                }

                $netAmount = $single_taxable - $beautician_discountAmount;
                $ssfs      = $netAmount;

                $comm_amt_raw = (float)$row['comm_amount'];
                $comm_by      = ($comm_amt_raw > 0) ? ($row['comm_amount'] . $row['comm_by']) : '—';
                $has_comm     = ($comm_amt_raw > 0 || (float)$row['total_comm'] > 0);

                // Accumulate totals
                $totals['rentAmount']               += $totalProductAmount;
                $totals['beauticianDiscountAmount'] += $beautician_discountAmount;
                $totals['netAmount']                += $netAmount;
                $totals['thisProductTotalGst']      += $thisProductTotalGst;
                $totals['thisssfsAmount']           += $ssfs;
                ?>
                <tr>
                    <td class="text-center" style="color: var(--pm-slate-400); font-weight: 500;"><?= $srno ?></td>
                    <td style="white-space: nowrap !important; font-family: ui-monospace, monospace; font-size: 11.5px;">
                        <?= date('d-m-Y', strtotime($billDate)) ?>
                    </td>
                    <td>
                        <span class="pm-badge <?= ($category_type === 'Jewellery') ? 'pm-badge-slate' : 'pm-badge-neutral' ?>">
                            <?= $category_type ?>
                        </span>
                    </td>
                    <td style="white-space: nowrap !important;">
                        <span class="pm-badge pm-badge-sku"><?= htmlspecialchars($sku) ?></span>
                    </td>
                    <td style="white-space: nowrap !important;">
                        <a href="./rent_report_detail.php?id=<?= $bid ?>" target="_blank" class="pm-bill-link" title="Open Rent Bill">
                            <?= htmlspecialchars($new_bill_number) ?>
                        </a>
                    </td>
                    <td style="white-space: nowrap !important; font-weight: 600; color: var(--pm-slate-900);">
                        <span style="white-space: nowrap !important;"><?= htmlspecialchars($customerName) ?></span>
                    </td>
                    <td style="white-space: nowrap !important; color: var(--pm-slate-600); font-size: 11.5px;"><?= $pick_date ?></td>
                    <td style="white-space: nowrap !important; color: var(--pm-slate-600); font-size: 11.5px;"><?= $delivery_date ?></td>
                    <td class="text-right pm-amount">₹ <?= number_format($totalProductAmount, 2) ?></td>
                    <td class="text-center"><span class="pm-badge pm-badge-rate"><?= $gst_rate ?></span></td>
                    <td class="text-right pm-amount" style="color: var(--pm-slate-600);">₹ <?= number_format($thisProductTotalGst, 2) ?></td>
                    <td class="text-center">
                        <span class="pm-badge <?= $has_comm ? 'pm-badge-slate' : 'pm-badge-neutral' ?>">
                            <?= $has_comm ? 'Yes' : 'No' ?>
                        </span>
                    </td>
                    <td class="text-center" style="font-family: monospace; font-size: 11px;"><?= htmlspecialchars($comm_by) ?></td>
                    <td class="text-right pm-amount" style="color: var(--pm-slate-600);">₹ <?= number_format($beautician_discountAmount, 2) ?></td>
                    <td class="text-right pm-amount" style="color: var(--pm-slate-700);">₹ <?= number_format($netAmount, 2) ?></td>
                    <td class="text-center">
                        <span class="pm-badge <?= ($isFlyrobeProduct === 'Yes') ? 'pm-badge-slate' : 'pm-badge-neutral' ?>">
                            <?= $isFlyrobeProduct ?>
                        </span>
                    </td>
                    <td class="text-right pm-amount" style="font-weight: 700; color: var(--pm-slate-900);">₹ <?= number_format($ssfs, 2) ?></td>
                </tr>
                <?php
                $srno++;
            }
            ?>
            <tr id="kpi-data-meta" style="display:none;"
                data-count="<?= ($srno - 1) ?>"
                data-rent="<?= number_format($totals['rentAmount'], 2, '.', '') ?>"
                data-gst="<?= number_format($totals['thisProductTotalGst'], 2, '.', '') ?>"
                data-beautician="<?= number_format($totals['beauticianDiscountAmount'], 2, '.', '') ?>"
                data-ssfs="<?= number_format($totals['thisssfsAmount'], 2, '.', '') ?>">
            </tr>
            <?php
        } else {
            ?>
            <tr>
                <td colspan="17">
                    <div class="pm-empty-state">
                        <div class="pm-empty-icon"><i class="fa-solid fa-inbox"></i></div>
                        <div class="pm-empty-title">No Commission Records Found</div>
                        <p class="pm-empty-desc">No rental items match the selected month and year criteria.</p>
                    </div>
                </td>
            </tr>
            <tr id="kpi-data-meta" style="display:none;" data-count="0" data-rent="0" data-gst="0" data-beautician="0" data-ssfs="0"></tr>
            <?php
        }
        ?>
    </tbody>
    <?php if ($query && mysqli_num_rows($query) > 0): ?>
    <tfoot>
        <tr style="background: #f8fafc; font-weight: 600;">
            <th colspan="8" class="text-right" style="padding: 10px 12px; font-weight: 700;">Total (<?= ($srno - 1) ?> items):</th>
            <th class="text-right pm-amount" style="padding: 10px 12px;">₹ <?= number_format($totals['rentAmount'], 2) ?></th>
            <th style="padding: 10px 12px;"></th>
            <th class="text-right pm-amount" style="padding: 10px 12px; color: var(--pm-slate-600);">₹ <?= number_format($totals['thisProductTotalGst'], 2) ?></th>
            <th style="padding: 10px 12px;"></th>
            <th style="padding: 10px 12px;"></th>
            <th class="text-right pm-amount" style="padding: 10px 12px; color: var(--pm-slate-600);">₹ <?= number_format($totals['beauticianDiscountAmount'], 2) ?></th>
            <th class="text-right pm-amount" style="padding: 10px 12px;">₹ <?= number_format($totals['netAmount'], 2) ?></th>
            <th style="padding: 10px 12px;"></th>
            <th class="text-right pm-amount" style="padding: 10px 12px; font-weight: 700; color: var(--pm-slate-900);">₹ <?= number_format($totals['thisssfsAmount'], 2) ?></th>
        </tr>
    </tfoot>
    <?php endif; ?>
    <?php
    $output = ob_get_clean();
    echo $output;
    exit();
}

// =========================================================================
// 2. CSV Export Handler
// =========================================================================
if (isset($_REQUEST['export'])) {
    $filename = "ssfs_commission_records_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Sr No',
        'Bill Date',
        'Product Type',
        'SKU',
        'Invoice No',
        'Customer Name',
        'Pick-up Date',
        'Return Date',
        'Rent Amount',
        'GST Rate',
        'GST Amount',
        'Beautician Commission Given',
        'Beautician Comm Rate',
        'Beautician Comm Amount',
        'Net Amount',
        'Is Flyrobe Product',
        'SSFS Share'
    ]);

    if ($query && mysqli_num_rows($query) > 0) {
        $srno = 1;
        while ($row = mysqli_fetch_assoc($query)) {
            $is_new_with_gst    = (int)$row['is_new_with_gst'];
            $sku                = trim($row["sku"]);
            $billDate           = $row['bill_date'];
            $bid                = (int)$row["bill_id"];
            $new_bill_number    = !empty($row['new_bill_number']) ? $row['new_bill_number'] : ('#' . $bid);
            $cust_id            = (int)$row['cust_id'];
            $qty                = max(1, (int)$row['qty']);
            $single_rent        = (float)$row['single_rent'];
            $totalProductAmount = (float)$row["totalProductAmount"];
            $total_records      = max(1, (int)$row['total_records']);

            if (!isset($people_cache[$cust_id])) {
                $peoplesql = mysqli_query($con, "SELECT first_name, last_name FROM phppos_people WHERE person_id='$cust_id' LIMIT 1");
                if ($peoplesql && $pRes = mysqli_fetch_assoc($peoplesql)) {
                    $cName = trim(($pRes['first_name'] ?? '') . ' ' . ($pRes['last_name'] ?? ''));
                    $people_cache[$cust_id] = $cName ?: ('Customer #' . $cust_id);
                } else {
                    $people_cache[$cust_id] = 'Customer #' . $cust_id;
                }
            }
            $customerName = $people_cache[$cust_id];

            $pick_date     = ($row['pick_date'] && $row['pick_date'] !== '0000-00-00') ? date('d-m-Y', strtotime($row['pick_date'])) : '—';
            $delivery_date = ($row['delivery_date'] && $row['delivery_date'] !== '0000-00-00') ? date('d-m-Y', strtotime($row['delivery_date'])) : '—';

            if (!isset($item_cache[$sku])) {
                $safe_sku = mysqli_real_escape_string($con, $sku);
                $item_sql = mysqli_query($con, "SELECT category_type, supplier_id FROM phppos_items WHERE name='$safe_sku' LIMIT 1");
                if ($item_sql && $iRes = mysqli_fetch_assoc($item_sql)) {
                    $item_cache[$sku] = [
                        'category_type' => (int)($iRes['category_type'] ?? 1),
                        'supplier_id'   => trim($iRes['supplier_id'] ?? '')
                    ];
                } else {
                    $item_cache[$sku] = [
                        'category_type' => 1,
                        'supplier_id'   => ''
                    ];
                }
            }
            $item_info        = $item_cache[$sku];
            $supplier_id      = $item_info['supplier_id'];
            $isFlyrobeProduct = ($supplier_id === '242875') ? 'Yes' : 'No';
            $category_type    = ($item_info['category_type'] === 1) ? 'Jewellery' : 'Apparel';

            if ($is_new_with_gst === 1) {
                if ($item_info['category_type'] === 1) {
                    $thisProductTotalTaxable = $single_rent / 1.03;
                    $thisProductTotalGst     = $thisProductTotalTaxable * 0.03;
                    $cgst = $sgst            = $thisProductTotalGst / 2;
                    $gst_rate                = '3%';
                } else {
                    $thisProductTotalTaxable = $single_rent / 1.12;
                    $thisProductTotalGst     = $thisProductTotalTaxable * 0.12;
                    $cgst = $sgst            = $thisProductTotalGst / 2;
                    $gst_rate                = '12%';
                }
            } else {
                $gst_rate = '0%';
                $cgst = $sgst = $thisProductTotalGst = 0.0;
            }

            $single_taxable = $single_rent - ($cgst + $sgst);

            $beautician_discountAmount = 0.0;
            if (!empty($row["throught"])) {
                $total_comm_val = (float)$row["total_comm"];
                $comm_amt_val   = (float)$row['comm_amount'];

                if ($total_comm_val > 0) {
                    $beautician_discountAmount = $total_comm_val / $total_records;
                } else if ($total_comm_val == 0) {
                    if ($row['comm_by'] === '%') {
                        $beautician_discountAmount = $single_taxable * ($comm_amt_val / 100);
                    } else {
                        $beautician_discountAmount = max(0, $single_taxable - $comm_amt_val);
                    }
                }
            }

            $netAmount = $single_taxable - $beautician_discountAmount;
            $ssfs      = $netAmount;

            $comm_amt_raw = (float)$row['comm_amount'];
            $comm_by      = ($comm_amt_raw > 0) ? ($row['comm_amount'] . $row['comm_by']) : '—';
            $has_comm     = ($comm_amt_raw > 0 || (float)$row['total_comm'] > 0);

            $totals['rentAmount']               += $totalProductAmount;
            $totals['beauticianDiscountAmount'] += $beautician_discountAmount;
            $totals['netAmount']                += $netAmount;
            $totals['thisProductTotalGst']      += $thisProductTotalGst;
            $totals['thisssfsAmount']           += $ssfs;

            fputcsv($output, [
                $srno,
                date('d-m-Y', strtotime($billDate)),
                $category_type,
                $sku,
                $new_bill_number,
                $customerName,
                $pick_date,
                $delivery_date,
                number_format($totalProductAmount, 2, '.', ''),
                $gst_rate,
                number_format($thisProductTotalGst, 2, '.', ''),
                $has_comm ? 'Yes' : 'No',
                $comm_by,
                number_format($beautician_discountAmount, 2, '.', ''),
                number_format($netAmount, 2, '.', ''),
                $isFlyrobeProduct,
                number_format($ssfs, 2, '.', '')
            ]);
            $srno++;
        }

        // Summary footer row in CSV
        fputcsv($output, []);
        fputcsv($output, [
            'Total',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            number_format($totals['rentAmount'], 2, '.', ''),
            '',
            number_format($totals['thisProductTotalGst'], 2, '.', ''),
            '',
            '',
            number_format($totals['beauticianDiscountAmount'], 2, '.', ''),
            number_format($totals['netAmount'], 2, '.', ''),
            '',
            number_format($totals['thisssfsAmount'], 2, '.', '')
        ]);
    } else {
        fputcsv($output, ['No commission data found for selected period']);
    }

    fclose($output);
    exit();
}