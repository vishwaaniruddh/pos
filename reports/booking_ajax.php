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

// Retrieve and sanitize inputs
$barcode  = isset($_REQUEST['barcode']) && $_REQUEST['barcode'] !== '' ? safe_str($_REQUEST['barcode']) : (isset($_REQUEST['barcode2']) && $_REQUEST['barcode2'] !== '' ? safe_str($_REQUEST['barcode2']) : '');
$fromdate = isset($_REQUEST['fromdate']) ? parse_date_to_db($_REQUEST['fromdate']) : '';
$todate   = isset($_REQUEST['todate']) ? parse_date_to_db($_REQUEST['todate']) : '';
$tab_mode = isset($_REQUEST['tab_mode']) ? safe_str($_REQUEST['tab_mode']) : 'both'; // 'both', 'future', 'past'

$conditions = [];

if ($barcode !== '') {
    $barcode_safe = mysqli_real_escape_string($con, $barcode);
    $conditions[] = "(a.item_id LIKE '%$barcode_safe%')";
}

if ($fromdate !== '' && $todate !== '') {
    $conditions[] = "(b.pick_date BETWEEN '$fromdate' AND '$todate')";
} elseif ($fromdate !== '') {
    $conditions[] = "(b.pick_date >= '$fromdate')";
} elseif ($todate !== '') {
    $conditions[] = "(b.pick_date <= '$todate')";
}

$base_where = count($conditions) > 0 ? " WHERE " . implode(' AND ', $conditions) : " WHERE 1=1";

$select_fields = "
    a.bill_id,
    GROUP_CONCAT(DISTINCT a.item_id ORDER BY a.item_id SEPARATOR ', ') AS item_ids,
    COUNT(DISTINCT a.item_id) as total_items_count,
    b.pick_date AS rentpickdate,
    b.delivery_date AS rentdeliverydate,
    b.new_bill_number,
    b.cust_id,
    b.trail_date,
    b.measurement,
    b.is_delivery,
    p.first_name,
    p.last_name,
    p.phone_number
";

// Future Bookings Query
$future_qry = "
    SELECT $select_fields
    FROM order_detail a
    INNER JOIN phppos_rent b ON a.bill_id = b.bill_id
    LEFT JOIN phppos_people p ON b.cust_id = p.person_id
    $base_where AND b.pick_date >= CURDATE()
    GROUP BY a.bill_id
    ORDER BY b.pick_date ASC, a.bill_id ASC
";
$res_future = mysqli_query($con, $future_qry);
$num_future = $res_future ? mysqli_num_rows($res_future) : 0;

// Past Bookings Query (with limit if no search criteria given)
$past_limit_applied = ($fromdate === '' && $todate === '' && $barcode === '');
$past_limit = $past_limit_applied ? " LIMIT 250" : "";
$past_qry = "
    SELECT $select_fields
    FROM order_detail a
    INNER JOIN phppos_rent b ON a.bill_id = b.bill_id
    LEFT JOIN phppos_people p ON b.cust_id = p.person_id
    $base_where AND b.pick_date < CURDATE()
    GROUP BY a.bill_id
    ORDER BY b.pick_date DESC, a.bill_id DESC
    $past_limit
";
$res_past = mysqli_query($con, $past_qry);
$num_past = $res_past ? mysqli_num_rows($res_past) : 0;
?>

<!-- Results Summary Header -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
    <div class="d-flex align-items-center gap-2">
        <span style="font-size: 13px; font-weight: 600; color: #0f172a;">
            Search Results:
        </span>
        <span class="pm-badge pm-badge-neutral">
            <strong><?php echo $num_future; ?></strong> Upcoming
        </span>
        <span class="pm-badge pm-badge-neutral">
            <strong><?php echo $num_past; ?></strong> Past Records
        </span>
        <?php if ($barcode !== '') { ?>
            <span class="pm-badge pm-badge-neutral">
                SKU: <strong><?php echo htmlspecialchars($barcode); ?></strong>
            </span>
        <?php } ?>
    </div>

    <!-- Section Switcher Tabs -->
    <div class="d-flex align-items-center gap-1">
        <button type="button" class="pm-view-tab <?php echo $tab_mode === 'both' ? 'active' : ''; ?>" onclick="switchBookingTab('both')">All</button>
        <button type="button" class="pm-view-tab <?php echo $tab_mode === 'future' ? 'active' : ''; ?>" onclick="switchBookingTab('future')">Upcoming (<?php echo $num_future; ?>)</button>
        <button type="button" class="pm-view-tab <?php echo $tab_mode === 'past' ? 'active' : ''; ?>" onclick="switchBookingTab('past')">Past (<?php echo $num_past; ?>)</button>
    </div>
</div>

<!-- ========================================================= -->
<!-- 1. FUTURE BOOKINGS SECTION                                -->
<!-- ========================================================= -->
<div id="futureBookingsWrapper" class="booking-section-pane <?php echo ($tab_mode === 'past') ? 'd-none' : ''; ?> mb-4">
    <div class="pm-card" id="futureBookings">
        <div class="pm-card-header">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 class="pm-card-title">
                    Upcoming & Future Bookings
                </h3>
                <span class="pm-badge pm-badge-neutral">
                    <?php echo $num_future; ?> reservation(s)
                </span>
            </div>
            <div>
                <button type="button" class="pm-btn-outline" style="height: 32px; padding: 4px 10px; font-size: 12px;" onclick="printDiv('futureBookingsTableWrapper', 'Upcoming Bookings Report')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Print Upcoming
                </button>
            </div>
        </div>

        <?php if ($num_future > 0) { ?>
            <div class="table-responsive" id="futureBookingsTableWrapper">
                <table class="table pm-booking-table mb-0" id="futureBookingsTable">
                    <thead>
                        <tr>
                            <th style="width: 46px; text-align: center;">#</th>
                            <th style="width: 140px;">Bill Number</th>
                            <th>Rented Item Codes / SKUs</th>
                            <th style="min-width: 160px;">Customer Name</th>
                            <th style="width: 125px;">Contact Phone</th>
                            <th style="width: 110px;">Pick Date</th>
                            <th style="width: 110px;">Delivery Date</th>
                            <th style="width: 105px;">Trial Date</th>
                            <th style="width: 100px; text-align: center;">Measurement</th>
                            <th style="width: 100px; text-align: center;">Delivery Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $fi = 1;
                        while ($r = mysqli_fetch_assoc($res_future)) {
                            $bill_no = $r['new_bill_number'] ?: ('#' . $r['bill_id']);
                            $items_arr = array_filter(array_map('trim', explode(',', $r['item_ids'] ?? '')));
                            $c_name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                            if ($c_name === '') $c_name = 'Walk-in Customer';
                            $phone = safe_str($r['phone_number'] ?? '');
                            $p_date = !empty($r['rentpickdate']) ? date('d M Y', strtotime($r['rentpickdate'])) : '-';
                            $d_date = !empty($r['rentdeliverydate']) ? date('d M Y', strtotime($r['rentdeliverydate'])) : '-';
                            
                            $t_date = '-';
                            if (!empty($r['trail_date']) && $r['trail_date'] !== '0000-00-00' && $r['trail_date'] !== '1970-01-01') {
                                $t_date = date('d M Y', strtotime($r['trail_date']));
                            }

                            $measure = strtolower(safe_str($r['measurement'] ?? 'no'));
                            $is_del = strtolower(safe_str($r['is_delivery'] ?? ''));
                        ?>
                        <tr class="booking-row">
                            <td style="text-align: center; color: #64748b; font-size: 12px; font-weight: 600;">
                                <?php echo $fi++; ?>
                            </td>
                            <td>
                                <span class="pm-code-badge"><?php echo htmlspecialchars($bill_no); ?></span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($items_arr as $itCode) { ?>
                                        <span class="pm-item-chip" title="<?php echo htmlspecialchars($itCode); ?>">
                                            <?php echo htmlspecialchars($itCode); ?>
                                        </span>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #0f172a; font-size: 13px;">
                                    <?php echo htmlspecialchars($c_name); ?>
                                </div>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php if ($phone !== '') { ?>
                                    <a href="tel:<?php echo htmlspecialchars($phone); ?>" style="color: #334155; font-size: 12.5px; text-decoration: none;">
                                        <i class="fa fa-phone text-muted me-1"></i><?php echo htmlspecialchars($phone); ?>
                                    </a>
                                <?php } else { ?>
                                    <span style="color: #94a3b8;">&mdash;</span>
                                <?php } ?>
                            </td>
                            <td style="color: #0f172a; font-weight: 600; font-size: 12.5px; white-space: nowrap;">
                                <?php echo $p_date; ?>
                            </td>
                            <td style="color: #475569; font-size: 12.5px; white-space: nowrap;">
                                <?php echo $d_date; ?>
                            </td>
                            <td style="color: #475569; font-size: 12.5px; white-space: nowrap;">
                                <?php echo $t_date; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($measure === 'yes') { ?>
                                    <span class="pm-badge pm-badge-neutral">Yes</span>
                                <?php } else { ?>
                                    <span style="color: #94a3b8; font-size: 12px;">No</span>
                                <?php } ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($is_del === 'yes' || $is_del === 'delivery') { ?>
                                    <span class="pm-badge pm-badge-neutral">Delivery</span>
                                <?php } else { ?>
                                    <span class="pm-badge pm-badge-outline">Pickup</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="text-center py-4 text-muted" style="font-size: 13px;">
                <div class="mb-2"><i class="fa fa-calendar-o fa-2x text-muted"></i></div>
                No upcoming bookings match the selected criteria.
            </div>
        <?php } ?>
    </div>
</div>

<!-- ========================================================= -->
<!-- 2. PAST BOOKINGS SECTION                                  -->
<!-- ========================================================= -->
<div id="pastBookingsWrapper" class="booking-section-pane <?php echo ($tab_mode === 'future') ? 'd-none' : ''; ?>">
    <div class="pm-card" id="pastBookings">
        <div class="pm-card-header">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #64748b;"></div>
                <h3 class="pm-card-title">
                    Past Bookings Archive
                </h3>
                <span class="pm-badge pm-badge-neutral">
                    <?php echo $num_past; ?> record(s)
                </span>
                <?php if ($past_limit_applied) { ?>
                    <span style="font-size: 11.5px; color: #94a3b8;">(showing latest 250 &bull; filter dates for full history)</span>
                <?php } ?>
            </div>
            <div>
                <button type="button" class="pm-btn-outline" style="height: 32px; padding: 4px 10px; font-size: 12px;" onclick="printDiv('pastBookingsTableWrapper', 'Past Bookings Archive Report')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Print Past Archive
                </button>
            </div>
        </div>

        <?php if ($num_past > 0) { ?>
            <div class="table-responsive" id="pastBookingsTableWrapper">
                <table class="table pm-booking-table mb-0" id="pastBookingsTable">
                    <thead>
                        <tr>
                            <th style="width: 46px; text-align: center;">#</th>
                            <th style="width: 140px;">Bill Number</th>
                            <th>Rented Item Codes / SKUs</th>
                            <th style="min-width: 160px;">Customer Name</th>
                            <th style="width: 125px;">Contact Phone</th>
                            <th style="width: 110px;">Pick Date</th>
                            <th style="width: 110px;">Delivery Date</th>
                            <th style="width: 105px;">Trial Date</th>
                            <th style="width: 100px; text-align: center;">Measurement</th>
                            <th style="width: 100px; text-align: center;">Delivery Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $pi = 1;
                        while ($r = mysqli_fetch_assoc($res_past)) {
                            $bill_no = $r['new_bill_number'] ?: ('#' . $r['bill_id']);
                            $items_arr = array_filter(array_map('trim', explode(',', $r['item_ids'] ?? '')));
                            $c_name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                            if ($c_name === '') $c_name = 'Walk-in Customer';
                            $phone = safe_str($r['phone_number'] ?? '');
                            $p_date = !empty($r['rentpickdate']) ? date('d M Y', strtotime($r['rentpickdate'])) : '-';
                            $d_date = !empty($r['rentdeliverydate']) ? date('d M Y', strtotime($r['rentdeliverydate'])) : '-';
                            
                            $t_date = '-';
                            if (!empty($r['trail_date']) && $r['trail_date'] !== '0000-00-00' && $r['trail_date'] !== '1970-01-01') {
                                $t_date = date('d M Y', strtotime($r['trail_date']));
                            }

                            $measure = strtolower(safe_str($r['measurement'] ?? 'no'));
                            $is_del = strtolower(safe_str($r['is_delivery'] ?? ''));
                        ?>
                        <tr class="booking-row">
                            <td style="text-align: center; color: #64748b; font-size: 12px; font-weight: 600;">
                                <?php echo $pi++; ?>
                            </td>
                            <td>
                                <span class="pm-code-badge"><?php echo htmlspecialchars($bill_no); ?></span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($items_arr as $itCode) { ?>
                                        <span class="pm-item-chip" title="<?php echo htmlspecialchars($itCode); ?>">
                                            <?php echo htmlspecialchars($itCode); ?>
                                        </span>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #0f172a; font-size: 13px;">
                                    <?php echo htmlspecialchars($c_name); ?>
                                </div>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php if ($phone !== '') { ?>
                                    <a href="tel:<?php echo htmlspecialchars($phone); ?>" style="color: #334155; font-size: 12.5px; text-decoration: none;">
                                        <i class="fa fa-phone text-muted me-1"></i><?php echo htmlspecialchars($phone); ?>
                                    </a>
                                <?php } else { ?>
                                    <span style="color: #94a3b8;">&mdash;</span>
                                <?php } ?>
                            </td>
                            <td style="color: #0f172a; font-weight: 600; font-size: 12.5px; white-space: nowrap;">
                                <?php echo $p_date; ?>
                            </td>
                            <td style="color: #475569; font-size: 12.5px; white-space: nowrap;">
                                <?php echo $d_date; ?>
                            </td>
                            <td style="color: #475569; font-size: 12.5px; white-space: nowrap;">
                                <?php echo $t_date; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($measure === 'yes') { ?>
                                    <span class="pm-badge pm-badge-neutral">Yes</span>
                                <?php } else { ?>
                                    <span style="color: #94a3b8; font-size: 12px;">No</span>
                                <?php } ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($is_del === 'yes' || $is_del === 'delivery') { ?>
                                    <span class="pm-badge pm-badge-neutral">Delivery</span>
                                <?php } else { ?>
                                    <span class="pm-badge pm-badge-outline">Pickup</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="text-center py-4 text-muted" style="font-size: 13px;">
                <div class="mb-2"><i class="fa fa-archive fa-2x text-muted"></i></div>
                No past bookings found for the selected criteria.
            </div>
        <?php } ?>
    </div>
</div>

<?php
CloseCon($con);
?>
