<?php
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}

$con = OpenSrishringarrCon();

function safe_str($v) {
    return trim((string)($v ?? ''));
}

function parse_date_to_db($d) {
    $d = trim((string)$d);
    if ($d === '') return '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        return $d;
    }
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $d, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    $ts = strtotime($d);
    return $ts ? date('Y-m-d', $ts) : '';
}

$supp_id = isset($_GET['supp_id']) ? safe_str($_GET['supp_id']) : '-1';
$frmdate = isset($_GET['frmdate']) ? parse_date_to_db($_GET['frmdate']) : '';
$todate  = isset($_GET['todate']) ? parse_date_to_db($_GET['todate']) : '';
$type    = isset($_GET['type']) ? safe_str($_GET['type']) : '2';
$itnm    = isset($_GET['itnm']) ? safe_str($_GET['itnm']) : '';

$where_clauses = ["1=1"];

// Supplier filter
$supp_name_title = 'All Suppliers';
if ($supp_id !== '-1' && $supp_id !== '' && $supp_id !== '0') {
    $supp_id_safe = mysqli_real_escape_string($con, $supp_id);
    $where_clauses[] = "p.supp_id = '$supp_id_safe'";
    
    $q_supp = mysqli_query($con, "SELECT company_name FROM phppos_suppliers WHERE person_id = '$supp_id_safe' LIMIT 1");
    if ($q_supp && $r_supp = mysqli_fetch_row($q_supp)) {
        $supp_name_title = $r_supp[0];
    }
}

// Date range filter
if (!empty($frmdate) && !empty($todate)) {
    $where_clauses[] = "(p.date BETWEEN '$frmdate' AND '$todate')";
} else if (!empty($frmdate)) {
    $where_clauses[] = "p.date >= '$frmdate'";
} else if (!empty($todate)) {
    $where_clauses[] = "p.date <= '$todate'";
}

// Bill type filter: 0 = Unpaid (outstanding > 0), 1 = Paid (outstanding <= 0), 2 = Both
if ($type === '0') {
    $where_clauses[] = "p.outstanding > 0";
} else if ($type === '1') {
    $where_clauses[] = "p.outstanding <= 0";
}

// Item name / SKU search filter
if (!empty($itnm)) {
    $itnm_safe = mysqli_real_escape_string($con, $itnm);
    $where_clauses[] = "p.pur_id IN (
        SELECT pd.pur_id 
        FROM phppos_purchase_details pd 
        INNER JOIN phppos_items i ON pd.item_id = i.item_id 
        WHERE i.name LIKE '%$itnm_safe%'
    )";
}

// Add performance limit if viewing all suppliers with no date range
$limit_clause = "";
$is_limited = false;
if ($supp_id === '-1' && empty($frmdate) && empty($todate) && empty($itnm)) {
    $limit_clause = " LIMIT 300";
    $is_limited = true;
}

$sql = "SELECT 
            p.pur_id,
            p.bill_id,
            p.supp_id,
            p.date,
            p.totalqty,
            p.totalamt,
            p.outstanding,
            p.discount,
            p.payamt,
            p.dis_type,
            COALESCE(s.company_name, 'Unknown Vendor') as company_name
        FROM phppos_purchase p
        LEFT JOIN phppos_suppliers s ON p.supp_id = s.person_id
        WHERE " . implode(' AND ', $where_clauses) . "
        ORDER BY p.date DESC, p.pur_id DESC" . $limit_clause;

$trans = mysqli_query($con, $sql);
$total_rows = $trans ? mysqli_num_rows($trans) : 0;

if ($total_rows > 0) {
    $bills = [];
    $ttlpur = 0;
    $tnetamt = 0;
    $ttoutstanding = 0;
    $total_qty_sum = 0;
    $unpaid_bills_count = 0;

    while ($r = mysqli_fetch_assoc($trans)) {
        $bills[] = $r;
        $ttlpur += floatval($r['totalamt'] ?? 0);
        $tnetamt += floatval($r['payamt'] ?? 0);
        $cur_out = floatval($r['outstanding'] ?? 0);
        $ttoutstanding += $cur_out;
        $total_qty_sum += intval($r['totalqty'] ?? 0);
        if ($cur_out > 0) $unpaid_bills_count++;
    }
    ?>

    <!-- Results Overview Banner -->
    <div class="pm-results-banner d-flex flex-wrap align-items-center justify-content-between p-3 mb-3" 
         style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
        <div>
            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">
                <?php echo htmlspecialchars($supp_name_title); ?>
            </div>
            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                Showing <strong><?php echo number_format($total_rows); ?></strong> purchase bill(s)
                <?php if (!empty($is_limited)) { ?>
                    <span style="color: #94a3b8;">(latest 300 &bull; filter by date for full history)</span>
                <?php } ?>
                <?php if ($unpaid_bills_count > 0) { ?>
                    &bull; <span style="color: #475569; font-weight: 600;"><?php echo $unpaid_bills_count; ?> pending payment</span>
                <?php } ?>
            </div>
        </div>

        <!-- Quick Badges -->
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div style="font-size: 12px; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 5px 10px; border-radius: 6px;">
                Total Billed: <strong style="color: #0f172a;">₹ <?php echo number_format($ttlpur, 2); ?></strong>
            </div>
            <div style="font-size: 12px; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 5px 10px; border-radius: 6px;">
                Paid: <strong style="color: #0f172a;">₹ <?php echo number_format($tnetamt, 2); ?></strong>
            </div>
            <div style="font-size: 12px; color: #64748b; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 5px 10px; border-radius: 6px;">
                Outstanding: <strong style="color: #0f172a;">₹ <?php echo number_format($ttoutstanding, 2); ?></strong>
            </div>
        </div>
    </div>

    <!-- Interactive Data Table Card -->
    <div class="pm-card">
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table pm-bills-table mb-0" id="billsDataTable">
                <thead>
                    <tr>
                        <th style="width: 44px; text-align: center;">
                            <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)" title="Select all unpaid bills" />
                        </th>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th style="width: 85px;">Bill ID</th>
                        <th style="width: 140px;">Invoice / Bill No</th>
                        <?php if ($supp_id === '-1') { ?>
                            <th style="min-width: 150px;">Supplier</th>
                        <?php } ?>
                        <th style="width: 110px;">Bill Date</th>
                        <th style="width: 80px; text-align: center;">Qty</th>
                        <th style="width: 120px; text-align: right;">Gross Total</th>
                        <th style="width: 90px; text-align: center;">Discount</th>
                        <th style="width: 120px; text-align: right;">Net / Paid</th>
                        <th style="width: 130px; text-align: right;">Outstanding</th>
                        <th style="width: 95px; text-align: center;">Status</th>
                        <th style="width: 120px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sr = 1;
                    foreach ($bills as $b) {
                        $p_id = intval($b['pur_id']);
                        $out_val = floatval($b['outstanding'] ?? 0);
                        $gross_val = floatval($b['totalamt'] ?? 0);
                        $paid_val = floatval($b['payamt'] ?? 0);
                        $is_unpaid = ($out_val > 0);
                        $b_date = !empty($b['date']) ? date('d M Y', strtotime($b['date'])) : '-';

                        // Status pill
                        if ($out_val <= 0) {
                            $status_badge = '<span class="pm-badge pm-badge-paid"><span class="pm-dot pm-dot-paid"></span> Paid</span>';
                        } else if ($out_val < $paid_val) {
                            $status_badge = '<span class="pm-badge pm-badge-partial"><span class="pm-dot pm-dot-partial"></span> Partial</span>';
                        } else {
                            $status_badge = '<span class="pm-badge pm-badge-unpaid"><span class="pm-dot pm-dot-unpaid"></span> Unpaid</span>';
                        }
                    ?>
                    <tr class="bill-row <?php echo $is_unpaid ? 'is-unpaid-row' : 'is-paid-row'; ?>" id="row_<?php echo $p_id; ?>">
                        <td style="text-align: center;">
                            <?php if ($is_unpaid) { ?>
                                <input type="checkbox" name="payment[]" class="payment-check" 
                                       value="<?php echo $p_id; ?>" 
                                       data-suppid="<?php echo htmlspecialchars($b['supp_id']); ?>"
                                       data-suppname="<?php echo htmlspecialchars($b['company_name']); ?>"
                                       data-outstanding="<?php echo $out_val; ?>" 
                                       onchange="recalculateSelected();" />
                            <?php } else { ?>
                                <span style="color: #cbd5e1; font-size: 11px;">&mdash;</span>
                            <?php } ?>
                        </td>
                        <td style="text-align: center; color: #64748b; font-size: 12px; font-weight: 600;">
                            <?php echo $sr++; ?>
                        </td>
                        <td>
                            <span class="pm-code-badge">#<?php echo $p_id; ?></span>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: #0f172a; font-size: 13px;">
                                <?php echo htmlspecialchars($b['bill_id']); ?>
                            </span>
                        </td>
                        <?php if ($supp_id === '-1') { ?>
                            <td>
                                <span class="pm-supplier-badge" title="<?php echo htmlspecialchars($b['company_name']); ?>">
                                    <?php echo htmlspecialchars($b['company_name']); ?>
                                </span>
                            </td>
                        <?php } ?>
                        <td style="color: #475569; font-size: 12.5px; white-space: nowrap;">
                            <?php echo $b_date; ?>
                        </td>
                        <td style="text-align: center; font-weight: 600; color: #334155;">
                            <?php echo intval($b['totalqty']); ?>
                        </td>
                        <td style="text-align: right; color: #475569; font-size: 13px;">
                            ₹ <?php echo number_format($gross_val, 2); ?>
                        </td>
                        <td style="text-align: center; color: #64748b; font-size: 12px;">
                            <?php 
                            $disc = floatval($b['discount'] ?? 0);
                            if ($disc > 0) {
                                echo $disc . ' ' . (($b['dis_type'] === 'percentage') ? '%' : '₹');
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td style="text-align: right; font-weight: 600; color: #0f172a; font-size: 13px;">
                            ₹ <?php echo number_format($paid_val, 2); ?>
                        </td>
                        <td style="text-align: right; font-weight: 700; font-size: 13px; color: <?php echo $is_unpaid ? '#0f172a' : '#64748b'; ?>;">
                            ₹ <?php echo number_format($out_val, 2); ?>
                        </td>
                        <td style="text-align: center;">
                            <?php echo $status_badge; ?>
                        </td>
                        <td style="text-align: center;">
                            <div class="d-inline-flex align-items-center gap-1">
                                <!-- View Bill Timeline Detail -->
                                <a href="Timeline/bill_his.php?sup_id=<?php echo urlencode($b['supp_id']); ?>&bill_id=<?php echo urlencode($p_id); ?>&link=detail" 
                                   target="_blank" 
                                   class="pm-action-btn" 
                                   title="View Bill Details & History">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>

                                <!-- Edit Bill (if unpaid or viewing both) -->
                                <?php if ($type === '0' || $type === '2' || $is_unpaid) { ?>
                                    <button type="button" 
                                            class="pm-action-btn" 
                                            onclick="openEditBillModal('<?php echo $p_id; ?>');" 
                                            title="Edit Bill Details">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr class="pm-table-totals-row">
                        <td colspan="<?php echo ($supp_id === '-1') ? '6' : '5'; ?>" style="text-align: right; font-weight: 700; color: #0f172a;">
                            Grand Total Summary:
                        </td>
                        <td style="text-align: center; font-weight: 700; color: #0f172a;">
                            <?php echo number_format($total_qty_sum); ?>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a;">
                            ₹ <?php echo number_format($ttlpur, 2); ?>
                        </td>
                        <td></td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a;">
                            ₹ <?php echo number_format($tnetamt, 2); ?>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a;">
                            ₹ <?php echo number_format($ttoutstanding, 2); ?>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

<?php } else { ?>

    <!-- Clean Neutral Empty State -->
    <div class="pm-card text-center py-5">
        <div class="mb-3 d-inline-flex align-items-center justify-content-center" 
             style="width: 56px; height: 56px; border-radius: 50%; background: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                <line x1="6" y1="8" x2="10" y2="8"></line>
                <line x1="6" y1="12" x2="18" y2="12"></line>
                <line x1="6" y1="16" x2="14" y2="16"></line>
            </svg>
        </div>
        <h4 style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">No Purchase Bills Found</h4>
        <p style="font-size: 13px; color: #64748b; max-width: 440px; margin: 0 auto 16px;">
            No bills match your chosen criteria. Try adjusting the date range, changing bill type to "Both", or clearing the search keyword.
        </p>
        <button type="button" class="pm-btn-outline" onclick="resetFilters();">
            Reset Filters
        </button>
    </div>

<?php } 

CloseCon($con);
?>