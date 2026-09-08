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
$mode    = isset($_GET['mode']) ? safe_str($_GET['mode']) : '';

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
    $where_clauses[] = "(pp.paid_date BETWEEN '$frmdate' AND '$todate')";
} else if (!empty($frmdate)) {
    $where_clauses[] = "pp.paid_date >= '$frmdate'";
} else if (!empty($todate)) {
    $where_clauses[] = "pp.paid_date <= '$todate'";
}

// Payment Mode filter
if (!empty($mode) && $mode !== 'all' && $mode !== '0') {
    $mode_safe = mysqli_real_escape_string($con, strtolower($mode));
    $where_clauses[] = "LOWER(pp.mode) = '$mode_safe'";
}

// Filter out $0 dummy records
$where_clauses[] = "pp.amt > 0";

$sql = "SELECT 
            pp.trans_id,
            pp.bill_no,
            pp.mode,
            pp.amt,
            pp.paid_date,
            p.bill_id as supplier_invoice_no,
            p.supp_id,
            COALESCE(s.company_name, 'Unknown Vendor') as company_name
        FROM phppos_purchase_payments pp
        LEFT JOIN phppos_purchase p ON pp.bill_no = p.pur_id
        LEFT JOIN phppos_suppliers s ON p.supp_id = s.person_id
        WHERE " . implode(' AND ', $where_clauses) . "
        ORDER BY pp.paid_date DESC, pp.trans_id DESC";

$trans = mysqli_query($con, $sql);
$total_rows = $trans ? mysqli_num_rows($trans) : 0;

if ($total_rows > 0) {
    $payments = [];
    $total_amount_sum = 0;
    $supplier_count_map = [];

    while ($r = mysqli_fetch_assoc($trans)) {
        $payments[] = $r;
        $total_amount_sum += floatval($r['amt'] ?? 0);
        $sName = $r['company_name'] ?? 'Unknown';
        $supplier_count_map[$sName] = true;
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
                Showing <strong><?php echo number_format($total_rows); ?></strong> disbursement record(s)
                across <strong><?php echo count($supplier_count_map); ?></strong> supplier(s)
            </div>
        </div>

        <!-- Quick Summary Badges -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div style="font-size: 12px; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 5px 12px; border-radius: 6px;">
                Total Disbursed: <strong style="color: #0f172a; font-size: 13px;">₹ <?php echo number_format($total_amount_sum, 2); ?></strong>
            </div>
        </div>
    </div>

    <!-- Interactive Data Table Card -->
    <div class="pm-card">
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table pm-payments-table mb-0" id="paymentsDataTable">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th style="width: 100px;">Trans ID</th>
                        <?php if ($supp_id === '-1') { ?>
                            <th style="min-width: 160px;">Supplier Name</th>
                        <?php } ?>
                        <th style="width: 120px;">Bill Ref</th>
                        <th style="width: 130px;">Payment Date</th>
                        <th style="width: 120px; text-align: center;">Payment Mode</th>
                        <th style="width: 140px; text-align: right;">Amount Paid (₹)</th>
                        <th style="width: 110px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sr = 1;
                    foreach ($payments as $p) {
                        $t_id = intval($p['trans_id']);
                        $bill_no = intval($p['bill_no']);
                        $amt_val = floatval($p['amt'] ?? 0);
                        $p_date = !empty($p['paid_date']) ? date('d M Y', strtotime($p['paid_date'])) : '-';
                        $mode_str = strtoupper(safe_str($p['mode'] ?? 'CASH'));
                        $inv_ref = safe_str($p['supplier_invoice_no'] ?? '');

                        // Badge styling based on mode
                        $mode_badge_class = 'pm-badge-neutral';
                        if ($mode_str === 'CASH') $mode_badge_class = 'pm-badge-cash';
                        else if ($mode_str === 'CHEQUE') $mode_badge_class = 'pm-badge-cheque';
                    ?>
                    <tr class="payment-row" id="row_<?php echo $t_id; ?>">
                        <td style="text-align: center; color: #64748b; font-size: 12px; font-weight: 600;">
                            <?php echo $sr++; ?>
                        </td>
                        <td>
                            <span class="pm-code-badge">#TRX-<?php echo $t_id; ?></span>
                        </td>
                        <?php if ($supp_id === '-1') { ?>
                            <td>
                                <span class="pm-supplier-badge" title="<?php echo htmlspecialchars($p['company_name']); ?>">
                                    <?php echo htmlspecialchars($p['company_name']); ?>
                                </span>
                            </td>
                        <?php } ?>
                        <td>
                            <div style="font-weight: 600; color: #0f172a; font-size: 12.5px;">
                                Bill #<?php echo $bill_no; ?>
                            </div>
                            <?php if ($inv_ref !== '') { ?>
                                <div style="font-size: 11px; color: #64748b;">
                                    Inv: <?php echo htmlspecialchars($inv_ref); ?>
                                </div>
                            <?php } ?>
                        </td>
                        <td style="color: #475569; font-size: 12.5px; white-space: nowrap;">
                            <?php echo $p_date; ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="pm-badge <?php echo $mode_badge_class; ?>">
                                <?php echo htmlspecialchars($mode_str); ?>
                            </span>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a; font-size: 13.5px;">
                            ₹ <?php echo number_format($amt_val, 2); ?>
                        </td>
                        <td style="text-align: center;">
                            <div class="d-inline-flex align-items-center gap-1">
                                <!-- Print / View Voucher -->
                                <a href="voucher.php?pur_id=<?php echo urlencode($bill_no); ?>&sup_name=<?php echo urlencode($p['company_name']); ?>" 
                                   target="_blank" 
                                   class="pm-action-btn" 
                                   title="View / Print Payment Voucher">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                        <rect x="6" y="14" width="12" height="8"></rect>
                                    </svg>
                                </a>

                                <!-- View Original Bill Details -->
                                <a href="Timeline/bill_his.php?sup_id=<?php echo urlencode($p['supp_id'] ?? ''); ?>&bill_id=<?php echo urlencode($bill_no); ?>&link=detail" 
                                   target="_blank" 
                                   class="pm-action-btn" 
                                   title="View Original Purchase Bill">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr class="pm-table-totals-row">
                        <td colspan="<?php echo ($supp_id === '-1') ? '6' : '5'; ?>" style="text-align: right; font-weight: 700; color: #0f172a;">
                            Total Disbursed Amount:
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a; font-size: 14px;">
                            ₹ <?php echo number_format($total_amount_sum, 2); ?>
                        </td>
                        <td></td>
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
        <h4 style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">No Payment Records Found</h4>
        <p style="font-size: 13px; color: #64748b; max-width: 440px; margin: 0 auto 16px;">
            No disbursement records match your current filter. Try adjusting the date range or selecting a different supplier.
        </p>
        <button type="button" class="pm-btn-outline" onclick="resetFilters();">
            Reset Filters
        </button>
    </div>

<?php } 

CloseCon($con);
?>
