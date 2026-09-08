<?php
session_start();

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

    // Input parameters
    $thr_param = isset($_GET['through']) ? safe_str($_GET['through']) : '';
    $throgh_parts = explode("/", $thr_param);
    $selected_through = $throgh_parts[0] ?? '';

    $from_date = isset($_GET['from']) ? parse_date_to_db($_GET['from']) : '';
    $to_date   = isset($_GET['to']) ? parse_date_to_db($_GET['to']) : '';
    $has_search = isset($_GET['search']) || $selected_through !== '';

    // Overall KPI Metrics
    $kpi_total_bookings = 0;
    $kpi_total_rent     = 0;
    $kpi_total_comm     = 0;
    $kpi_total_paid     = 0;

    if ($con) {
        $q_kpi1 = mysqli_query($con, "SELECT 
                    COUNT(bill_id) as total_bookings, 
                    COALESCE(SUM(rent_amount), 0) as total_rent, 
                    COALESCE(SUM(total_comm), 0) as total_commission 
                FROM phppos_rent 
                WHERE throught != '' AND throught != '-1' AND throught != '0' AND throught IS NOT NULL");
        if ($q_kpi1 && $r_kpi1 = mysqli_fetch_assoc($q_kpi1)) {
            $kpi_total_bookings = intval($r_kpi1['total_bookings'] ?? 0);
            $kpi_total_rent     = floatval($r_kpi1['total_rent'] ?? 0);
            $kpi_total_comm     = floatval($r_kpi1['total_commission'] ?? 0);
        }

        $q_kpi2 = mysqli_query($con, "SELECT COALESCE(SUM(amount), 0) as total_paid FROM commission_paid");
        if ($q_kpi2 && $r_kpi2 = mysqli_fetch_assoc($q_kpi2)) {
            $kpi_total_paid = floatval($r_kpi2['total_paid'] ?? 0);
        }
    }

    // Query referring agents for dropdown
    $agents_list = [];
    if ($con) {
        $q_agents = mysqli_query($con, "
            SELECT p.person_id, p.first_name, p.last_name, p.phone_number, COUNT(r.bill_id) as booking_count
            FROM phppos_rent r
            INNER JOIN phppos_people p ON r.throught = p.person_id
            WHERE r.throught != '-1' AND r.throught != '' AND r.throught != '0' AND r.throught IS NOT NULL
            GROUP BY p.person_id
            ORDER BY p.first_name ASC, p.last_name ASC
        ");
        if ($q_agents) {
            while ($ra = mysqli_fetch_assoc($q_agents)) {
                $agents_list[] = $ra;
            }
        }
    }

    // Agent Details (if specific agent selected)
    $agent_info = null;
    $agent_paid_comm = 0;
    if ($con && $selected_through !== '' && $selected_through !== 'all' && $selected_through !== '-1') {
        $thr_safe = mysqli_real_escape_string($con, $selected_through);
        $q_agent_info = mysqli_query($con, "SELECT first_name, last_name, phone_number, email FROM phppos_people WHERE person_id = '$thr_safe' LIMIT 1");
        if ($q_agent_info && $row_ai = mysqli_fetch_assoc($q_agent_info)) {
            $agent_info = $row_ai;
        }

        $q_ap = mysqli_query($con, "SELECT COALESCE(SUM(amount), 0) FROM commission_paid WHERE name = '$thr_safe'");
        if ($q_ap && $row_ap = mysqli_fetch_row($q_ap)) {
            $agent_paid_comm = floatval($row_ap[0] ?? 0);
        }
    }

    // Main Report Query Execution
    $report_rows = [];
    $total_rent_sum = 0;
    $total_comm_sum = 0;

    if ($con && $has_search) {
        $where_clauses = ["1=1"];

        if ($selected_through !== '' && $selected_through !== 'all' && $selected_through !== '-1' && $selected_through !== '0') {
            $thr_safe = mysqli_real_escape_string($con, $selected_through);
            $where_clauses[] = "r.throught = '$thr_safe'";
        } else {
            $where_clauses[] = "r.throught != '' AND r.throught != '-1' AND r.throught != '0' AND r.throught IS NOT NULL";
        }

        if (!empty($from_date) && !empty($to_date)) {
            $where_clauses[] = "(r.bill_date BETWEEN '$from_date' AND '$to_date')";
        } else if (!empty($from_date)) {
            $where_clauses[] = "r.bill_date >= '$from_date'";
        } else if (!empty($to_date)) {
            $where_clauses[] = "r.bill_date <= '$to_date'";
        }

        // Limit if viewing all agents with no date range
        $limit_clause = "";
        if (($selected_through === '' || $selected_through === 'all' || $selected_through === '-1') && empty($from_date) && empty($to_date)) {
            $limit_clause = " LIMIT 300";
        }

        $main_sql = "
            SELECT 
                r.bill_id,
                r.new_bill_number,
                r.cust_id,
                r.bill_date,
                r.rent_amount,
                r.amount as net_amount,
                r.throught,
                r.comm_by,
                r.comm_amount,
                r.total_comm,
                cust.first_name as cust_first_name,
                cust.last_name as cust_last_name,
                cust.phone_number as cust_phone,
                thr.first_name as thr_first_name,
                thr.last_name as thr_last_name,
                thr.phone_number as thr_phone,
                item.category_type
            FROM phppos_rent r
            LEFT JOIN phppos_people cust ON r.cust_id = cust.person_id
            LEFT JOIN phppos_people thr ON r.throught = thr.person_id
            LEFT JOIN order_detail od ON r.bill_id = od.bill_id
            LEFT JOIN phppos_items item ON od.item_id = item.name
            WHERE " . implode(' AND ', $where_clauses) . "
            GROUP BY r.bill_id
            ORDER BY r.bill_date DESC, r.bill_id DESC
            $limit_clause
        ";

        $res_main = mysqli_query($con, $main_sql);
        if ($res_main) {
            while ($row = mysqli_fetch_assoc($res_main)) {
                $report_rows[] = $row;
                $total_rent_sum += floatval($row['rent_amount'] ?? 0);
                $total_comm_sum += floatval($row['total_comm'] ?? 0);
            }
        }
    }
    ?>

    <!-- Main Panel -->
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">

            <!-- Load External Assets -->
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
                    --pm-slate-300: #cbd5e1;
                    --pm-slate-200: #e2e8f0;
                    --pm-slate-100: #f1f5f9;
                    --pm-slate-50:  #f8fafc;
                }

                body {
                    background-color: var(--pm-slate-50);
                    color: var(--pm-slate-900);
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                .comm-container {
                    max-width: 1440px;
                    margin: 0 auto;
                }

                /* Page Header */
                .pm-page-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    flex-wrap: wrap;
                    gap: 16px;
                    margin-bottom: 24px;
                    padding-bottom: 16px;
                    border-bottom: 1px solid var(--pm-slate-200);
                }

                .pm-page-title {
                    font-size: 20px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0 0 4px 0;
                    letter-spacing: -0.02em;
                    display: flex;
                    align-items: center;
                }

                .pm-page-subtitle {
                    font-size: 13px;
                    color: var(--pm-slate-500);
                    margin: 0;
                }

                /* KPI Metric Cards */
                .pm-kpi-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                    gap: 16px;
                    margin-bottom: 24px;
                }

                .pm-kpi-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 16px 18px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-kpi-card:hover {
                    border-color: var(--pm-slate-300);
                    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
                }

                .pm-kpi-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 10px;
                }

                .pm-kpi-label {
                    font-size: 11.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    color: var(--pm-slate-500);
                }

                .pm-kpi-icon {
                    width: 32px;
                    height: 32px;
                    border-radius: 6px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--pm-slate-600);
                }

                .pm-kpi-value {
                    font-size: 22px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    line-height: 1.1;
                }

                .pm-kpi-subtext {
                    font-size: 11.5px;
                    color: var(--pm-slate-400);
                    margin-top: 5px;
                }

                /* Standard Card */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 20px;
                    overflow: hidden;
                }

                .pm-card-header {
                    padding: 14px 18px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    background: #ffffff;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 10px;
                }

                .pm-card-title {
                    font-size: 14px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                }

                .pm-card-body {
                    padding: 18px 20px;
                }

                /* Buttons */
                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff !important;
                    border: 1px solid var(--pm-slate-900);
                    border-radius: 6px;
                    padding: 7px 16px;
                    font-size: 13px;
                    font-weight: 500;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                }

                .pm-btn-primary:hover {
                    background: var(--pm-slate-800);
                    border-color: var(--pm-slate-800);
                }

                .pm-btn-outline {
                    background: #ffffff;
                    color: var(--pm-slate-700) !important;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    padding: 7px 14px;
                    font-size: 13px;
                    font-weight: 500;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                }

                .pm-btn-outline:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900) !important;
                    border-color: var(--pm-slate-300);
                }

                /* Form Controls */
                .pm-form-group {
                    margin-bottom: 0;
                }

                .pm-form-label {
                    display: block;
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--pm-slate-700);
                    margin-bottom: 5px;
                }

                .pm-control {
                    width: 100%;
                    height: 36px;
                    padding: 6px 12px;
                    font-size: 13px;
                    color: var(--pm-slate-900);
                    background-color: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-control:focus {
                    outline: none;
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.08);
                }

                /* Preset Filter Chips */
                .pm-chip-group {
                    display: flex;
                    gap: 6px;
                    flex-wrap: wrap;
                }

                .pm-chip {
                    padding: 4px 10px;
                    font-size: 11.5px;
                    border-radius: 4px;
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-600);
                    cursor: pointer;
                    transition: all 0.15s ease;
                }

                .pm-chip:hover, .pm-chip.active {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                /* Table Styling */
                .pm-comm-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 0;
                }

                .pm-comm-table th {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-600);
                    font-size: 11.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                    padding: 10px 12px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    border-top: none;
                    white-space: nowrap;
                }

                .pm-comm-table td {
                    padding: 10px 12px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    vertical-align: middle;
                    font-size: 13px;
                }

                .pm-comm-table tbody tr:hover {
                    background-color: #fafbfc;
                }

                .pm-table-totals-row td {
                    background: var(--pm-slate-50);
                    font-size: 13px;
                    padding: 12px;
                    border-top: 2px solid var(--pm-slate-200);
                    border-bottom: none;
                }

                /* Badges */
                .pm-code-badge {
                    display: inline-block;
                    padding: 2px 7px;
                    font-size: 11px;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-weight: 600;
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 4px;
                }

                .pm-badge-neutral {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    padding: 3px 8px;
                    font-size: 11px;
                    font-weight: 600;
                    border-radius: 9999px;
                    background: #f1f5f9;
                    color: #0f172a;
                    border: 1px solid #cbd5e1;
                }

                /* Print Styles */
                @media print {
                    body {
                        background: #ffffff !important;
                        color: #000000 !important;
                    }
                    .top-navbar, #sidebar, .pm-page-header, .pm-kpi-grid, #filterCard, 
                    #tableControlsBar, .footer, .pm-agent-action-btns {
                        display: none !important;
                    }
                    .page-body-wrapper {
                        padding-top: 0 !important;
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
                        border: 1px solid #000 !important;
                        box-shadow: none !important;
                    }
                    .pm-comm-table th, .pm-comm-table td {
                        border: 1px solid #000 !important;
                        font-size: 11px !important;
                        padding: 6px !important;
                    }
                    @page {
                        size: landscape;
                        margin: 10mm;
                    }
                }
            </style>

            <div class="comm-container">

                <!-- 1. Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa fa-handshake-o text-muted" style="font-size: 18px; margin-right: 8px;"></i>
                            Referral & Commission Report
                        </h1>
                        <p class="pm-page-subtitle">Track referral agent commissions, rental volumes, and settled payment disbursements.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="pm-btn-primary" onclick="window.print();">
                            <i class="fa fa-print"></i> Print Report
                        </button>
                        <button type="button" class="pm-btn-outline" onclick="exportCommTableToCSV();">
                            <i class="fa fa-download"></i> Export CSV
                        </button>
                        <a href="/pos/home_dashboard.php" class="pm-btn-outline">
                            <i class="fa fa-home"></i> POS Dashboard
                        </a>
                    </div>
                </div>

                <!-- 2. Overall KPI Metrics Grid -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Referred Bookings</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value"><?php echo number_format($kpi_total_bookings); ?></div>
                        <div class="pm-kpi-subtext">Bookings via referral partners</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Referred Rent Volume</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value">₹ <?php echo number_format($kpi_total_rent, 2); ?></div>
                        <div class="pm-kpi-subtext">Total generated rental revenue</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total Commission</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M8 12h8"></path>
                                    <path d="M12 8v8"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value">₹ <?php echo number_format($kpi_total_comm, 2); ?></div>
                        <div class="pm-kpi-subtext">Cumulative earned commissions</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Settled / Pending</span>
                            <div class="pm-kpi-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="pm-kpi-value" style="font-size: 18px;">
                            ₹ <?php echo number_format($kpi_total_paid, 0); ?>
                            <span style="font-size: 12px; color: var(--pm-slate-500); font-weight: 500;">/ ₹ <?php echo number_format($kpi_total_comm - $kpi_total_paid, 0); ?> bal</span>
                        </div>
                        <div class="pm-kpi-subtext">Paid vs outstanding payout</div>
                    </div>
                </div>

                <!-- 3. Filter & Search Panel Card -->
                <div class="pm-card" id="filterCard">
                    <div class="pm-card-header">
                        <h3 class="pm-card-title">
                            <i class="fa fa-filter text-muted" style="margin-right: 6px;"></i>
                            Filter Commission Ledger
                        </h3>
                        <div class="d-flex align-items-center gap-2">
                            <a href="commReport.php" class="pm-btn-outline" style="height: 32px; padding: 4px 10px; font-size: 12px;">
                                <i class="fa fa-refresh"></i> Reset Filters
                            </a>
                        </div>
                    </div>
                    <div class="pm-card-body">
                        <form action="commReport.php" method="GET" id="commFilterForm">
                            <div class="row g-3">
                                
                                <!-- Through Agent Selection -->
                                <div class="col-xl-4 col-lg-5 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="through">Referring Agent ("Through Name")</label>
                                        <select name="through" id="through" class="pm-control" onchange="this.form.submit()">
                                            <option value="-1">All Referring Agents (Overview)</option>
                                            <?php foreach ($agents_list as $ag) { 
                                                $fullName = trim($ag['first_name'] . ' ' . $ag['last_name']);
                                                $phoneStr = safe_str($ag['phone_number']);
                                                $display = $fullName . ($phoneStr !== '' ? " ($phoneStr)" : "") . " [" . $ag['booking_count'] . "]";
                                                $is_sel = ($selected_through == $ag['person_id']) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $ag['person_id']; ?>" <?php echo $is_sel; ?>>
                                                    <?php echo htmlspecialchars($display); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Phone Lookup -->
                                <div class="col-xl-3 col-lg-4 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="phoneNo">Find Agent by Phone</label>
                                        <div class="input-group">
                                            <input type="text" name="phoneNo" id="phoneNo" class="pm-control" 
                                                   placeholder="Enter phone digits..." autocomplete="off" />
                                            <button type="button" class="pm-btn-outline" style="border-top-left-radius: 0; border-bottom-left-radius: 0;" onclick="loadPhoneNo();">
                                                Lookup
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- From Bill Date -->
                                <div class="col-xl-2 col-lg-3 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="from">From Date</label>
                                        <input type="date" name="from" id="from" class="pm-control" value="<?php echo htmlspecialchars($from_date); ?>" />
                                    </div>
                                </div>

                                <!-- To Bill Date -->
                                <div class="col-xl-2 col-lg-3 col-md-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="to">To Date</label>
                                        <input type="date" name="to" id="to" class="pm-control" value="<?php echo htmlspecialchars($to_date); ?>" />
                                    </div>
                                </div>

                                <!-- Submit Action -->
                                <div class="col-xl-1 col-lg-2 col-md-6 d-flex align-items-end">
                                    <button type="submit" name="search" value="Search" class="pm-btn-primary w-100" style="height: 36px; justify-content: center;">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>

                                <!-- Quick Date Presets -->
                                <div class="col-12 pt-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span style="font-size: 11.5px; color: var(--pm-slate-500); font-weight: 600;">Presets:</span>
                                        <button type="button" class="pm-chip" onclick="applyDatePreset('month')">This Month</button>
                                        <button type="button" class="pm-chip" onclick="applyDatePreset('last30')">Last 30 Days</button>
                                        <button type="button" class="pm-chip" onclick="applyDatePreset('year')">This Year</button>
                                        <button type="button" class="pm-chip" onclick="applyDatePreset('all')">All Time</button>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

                <!-- 4. Selected Agent Dossier / Action Card (if specific agent chosen) -->
                <?php if ($agent_info && $selected_through !== '' && $selected_through !== '-1') { 
                    $agent_name = trim($agent_info['first_name'] . ' ' . $agent_info['last_name']);
                    $agent_phone = safe_str($agent_info['phone_number']);
                    $agent_bal = $total_comm_sum - $agent_paid_comm;
                ?>
                    <div class="pm-card" style="border-left: 4px solid var(--pm-slate-900);">
                        <div class="pm-card-body p-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <div style="font-size: 11.5px; color: var(--pm-slate-500); text-transform: uppercase; font-weight: 600;">Selected Referral Partner</div>
                                    <div style="font-size: 16px; font-weight: 700; color: var(--pm-slate-900); margin-top: 2px;">
                                        <?php echo htmlspecialchars($agent_name); ?>
                                        <?php if ($agent_phone !== '') { ?>
                                            <span style="font-size: 13px; font-weight: normal; color: var(--pm-slate-500); margin-left: 8px;">
                                                <i class="fa fa-phone me-1"></i><?php echo htmlspecialchars($agent_phone); ?>
                                            </span>
                                        <?php } ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--pm-slate-600); margin-top: 4px;">
                                        Earned Commission: <strong>₹ <?php echo number_format($total_comm_sum, 2); ?></strong>
                                        &bull; Paid: <strong>₹ <?php echo number_format($agent_paid_comm, 2); ?></strong>
                                        &bull; Balance Payable: <strong style="color: <?php echo $agent_bal > 0 ? '#0f172a' : '#64748b'; ?>;">₹ <?php echo number_format($agent_bal, 2); ?></strong>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 pm-agent-action-btns">
                                    <a href="comm_paid.php?name=<?php echo urlencode($selected_through); ?>" class="pm-btn-primary" style="font-size: 12.5px;">
                                        <i class="fa fa-money"></i> Record Commission Payout
                                    </a>
                                    <a href="commPaid_report.php?name=<?php echo urlencode($selected_through); ?>" target="_blank" class="pm-btn-outline" style="font-size: 12.5px;">
                                        <i class="fa fa-history"></i> Payout History
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <!-- 5. Search Controls & Table Toolbar -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3" id="tableControlsBar">
                    <div class="d-flex align-items-center gap-2" style="min-width: 280px; max-width: 400px; width: 100%;">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-color: var(--pm-slate-200); color: var(--pm-slate-400);">
                                <i class="fa fa-search"></i>
                            </span>
                            <input type="text" id="liveTableSearch" class="form-control border-start-0" 
                                   style="border-color: var(--pm-slate-200); font-size: 13px; height: 36px;"
                                   placeholder="Filter within loaded table..." 
                                   onkeyup="filterCommRows(this.value);" />
                        </div>
                    </div>
                    <div style="font-size: 12.5px; color: var(--pm-slate-500);">
                        Showing <strong><?php echo count($report_rows); ?></strong> commission ledger record(s)
                    </div>
                </div>

                <!-- 6. Commission Records Data Table -->
                <div class="pm-card" id="printableTableArea">
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="table pm-comm-table mb-0" id="commDataTable">
                            <thead>
                                <tr>
                                    <th style="width: 46px; text-align: center;">#</th>
                                    <th style="width: 140px;">Bill Number</th>
                                    <th style="min-width: 160px;">Customer Name</th>
                                    <th style="width: 110px;">Bill Date</th>
                                    <th style="width: 130px; text-align: right;">Rent Amount</th>
                                    <th style="min-width: 160px;">Referring Agent</th>
                                    <th style="width: 125px;">Agent Contact</th>
                                    <th style="width: 110px; text-align: center;">Commission Rate</th>
                                    <th style="width: 90px; text-align: center;">GST %</th>
                                    <th style="width: 140px; text-align: right;">Commission (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (count($report_rows) > 0) {
                                    $sr = 1;
                                    foreach ($report_rows as $row) {
                                        $bill_no = $row['new_bill_number'] ?: ('#' . $row['bill_id']);
                                        $c_name = trim(($row['cust_first_name'] ?? '') . ' ' . ($row['cust_last_name'] ?? ''));
                                        if ($c_name === '') $c_name = 'Walk-in Customer';
                                        $b_date = !empty($row['bill_date']) ? date('d M Y', strtotime($row['bill_date'])) : '-';
                                        $r_amt = floatval($row['rent_amount'] ?? 0);
                                        $c_amt = floatval($row['total_comm'] ?? 0);
                                        
                                        $thr_name = trim(($row['thr_first_name'] ?? '') . ' ' . ($row['thr_last_name'] ?? ''));
                                        if ($thr_name === '') $thr_name = 'Unknown Agent';
                                        $thr_phone = safe_str($row['thr_phone'] ?? '');

                                        $comm_rate_display = '';
                                        $c_by = $row['comm_by'] ?? '';
                                        $c_rate = $row['comm_amount'] ?? '';
                                        if ($c_by === '%') $comm_rate_display = $c_rate . ' %';
                                        else if ($c_rate !== '') $comm_rate_display = '₹ ' . $c_rate;
                                        else $comm_rate_display = '-';

                                        $p_type = intval($row['category_type'] ?? 0);
                                        $gst_val = ($p_type === 1) ? 3 : (($p_type === 2) ? 18 : 0);
                                ?>
                                    <tr class="comm-row">
                                        <td style="text-align: center; color: #64748b; font-size: 12px; font-weight: 600;">
                                            <?php echo $sr++; ?>
                                        </td>
                                        <td>
                                            <span class="pm-code-badge"><?php echo htmlspecialchars($bill_no); ?></span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #0f172a; font-size: 13px;">
                                                <?php echo htmlspecialchars($c_name); ?>
                                            </div>
                                            <?php if (!empty($row['cust_phone'])) { ?>
                                                <div style="font-size: 11px; color: #64748b;">
                                                    <?php echo htmlspecialchars($row['cust_phone']); ?>
                                                </div>
                                            <?php } ?>
                                        </td>
                                        <td style="color: #475569; font-size: 12.5px; white-space: nowrap;">
                                            <?php echo $b_date; ?>
                                        </td>
                                        <td style="text-align: right; color: #0f172a; font-size: 13px;">
                                            ₹ <?php echo number_format($r_amt, 2); ?>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #334155; font-size: 13px;">
                                                <?php echo htmlspecialchars($thr_name); ?>
                                            </div>
                                        </td>
                                        <td style="white-space: nowrap;">
                                            <?php if ($thr_phone !== '') { ?>
                                                <a href="tel:<?php echo htmlspecialchars($thr_phone); ?>" style="color: #334155; font-size: 12.5px; text-decoration: none;">
                                                    <i class="fa fa-phone text-muted me-1"></i><?php echo htmlspecialchars($thr_phone); ?>
                                                </a>
                                            <?php } else { ?>
                                                <span style="color: #94a3b8;">&mdash;</span>
                                            <?php } ?>
                                        </td>
                                        <td style="text-align: center; color: #64748b; font-size: 12px;">
                                            <span class="pm-badge-neutral"><?php echo htmlspecialchars($comm_rate_display); ?></span>
                                        </td>
                                        <td style="text-align: center; color: #64748b; font-size: 12px;">
                                            <?php echo $gst_val > 0 ? ($gst_val . ' %') : '-'; ?>
                                        </td>
                                        <td style="text-align: right; font-weight: 700; color: #0f172a; font-size: 13.5px;">
                                            ₹ <?php echo number_format($c_amt, 2); ?>
                                        </td>
                                    </tr>
                                <?php 
                                    }
                                } else { 
                                ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">
                                            <div class="mb-2"><i class="fa fa-handshake-o fa-2x text-muted"></i></div>
                                            No commission records found matching the chosen criteria.
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr class="pm-table-totals-row">
                                    <td colspan="4" style="text-align: right; font-weight: 700; color: #0f172a;">
                                        Total Rent Volume:
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: #0f172a; font-size: 13.5px;">
                                        ₹ <?php echo number_format($total_rent_sum, 2); ?>
                                    </td>
                                    <td colspan="4" style="text-align: right; font-weight: 700; color: #0f172a;">
                                        Total Earned Commission:
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: #0f172a; font-size: 14px;">
                                        ₹ <?php echo number_format($total_comm_sum, 2); ?>
                                    </td>
                                </tr>
                                <?php if ($selected_through !== '' && $selected_through !== '-1' && $selected_through !== 'all') { ?>
                                    <tr style="background: #ffffff;">
                                        <td colspan="9" style="text-align: right; font-weight: 600; color: #475569;">
                                            Less: Commission Paid to Agent:
                                        </td>
                                        <td style="text-align: right; font-weight: 700; color: #475569;">
                                            ₹ <?php echo number_format($agent_paid_comm, 2); ?>
                                        </td>
                                    </tr>
                                    <tr style="background: var(--pm-slate-100); border-top: 1px solid var(--pm-slate-300);">
                                        <td colspan="9" style="text-align: right; font-weight: 800; color: #0f172a; font-size: 13.5px;">
                                            Net Balance Commission Due:
                                        </td>
                                        <td style="text-align: right; font-weight: 800; color: #0f172a; font-size: 15px;">
                                            ₹ <?php echo number_format($total_comm_sum - $agent_paid_comm, 2); ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div><!-- .comm-container -->

            <script>
                // Phone number quick lookup
                function loadPhoneNo() {
                    var phoneStr = document.getElementById('phoneNo').value.trim();
                    if (!phoneStr) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Phone Required',
                            text: 'Please enter an agent phone number to search.',
                            confirmButtonColor: '#0f172a'
                        });
                        return;
                    }

                    $.ajax({
                        url: 'getbyphone.php',
                        type: 'GET',
                        data: { cid: phoneStr },
                        success: function(res) {
                            var parts = res.trim().split('&&');
                            if (parts[0] === '1' && parts[1] && parts[1] !== '0') {
                                document.getElementById('through').value = parts[1];
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Agent Found',
                                    text: 'Referring agent auto-selected successfully.',
                                    timer: 1200,
                                    showConfirmButton: false
                                });
                                document.getElementById('commFilterForm').submit();
                            } else {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Not Found',
                                    text: 'No referring agent found matching phone: ' + phoneStr,
                                    confirmButtonColor: '#0f172a'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lookup Failed',
                                text: 'Failed to search by phone number.',
                                confirmButtonColor: '#0f172a'
                            });
                        }
                    });
                }

                // Date Presets
                function applyDatePreset(preset) {
                    var now = new Date();
                    var fromInput = document.getElementById('from');
                    var toInput   = document.getElementById('to');

                    function fmt(d) {
                        var y = d.getFullYear();
                        var m = String(d.getMonth() + 1).padStart(2, '0');
                        var day = String(d.getDate()).padStart(2, '0');
                        return y + '-' + m + '-' + day;
                    }

                    $('.pm-chip').removeClass('active');

                    if (preset === 'month') {
                        var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                        fromInput.value = fmt(firstDay);
                        toInput.value = fmt(now);
                    } else if (preset === 'last30') {
                        var past = new Date();
                        past.setDate(now.getDate() - 30);
                        fromInput.value = fmt(past);
                        toInput.value = fmt(now);
                    } else if (preset === 'year') {
                        var yearStart = new Date(now.getFullYear(), 0, 1);
                        fromInput.value = fmt(yearStart);
                        toInput.value = fmt(now);
                    } else if (preset === 'all') {
                        fromInput.value = '';
                        toInput.value = '';
                    }

                    document.getElementById('commFilterForm').submit();
                }

                // Client-side quick filter on table rows
                function filterCommRows(query) {
                    var q = query.toLowerCase().trim();
                    var rows = document.querySelectorAll('#commDataTable tbody tr.comm-row');
                    rows.forEach(function(row) {
                        var text = row.textContent.toLowerCase();
                        if (!q || text.indexOf(q) !== -1) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }

                // Export Table Data to CSV
                function exportCommTableToCSV() {
                    var table = document.getElementById('commDataTable');
                    if (!table) {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Data',
                            text: 'No commission records loaded to export.',
                            confirmButtonColor: '#0f172a'
                        });
                        return;
                    }

                    var csv = [];
                    var rows = table.querySelectorAll('tr');

                    rows.forEach(function(row) {
                        if (row.style.display === 'none') return;

                        var cols = row.querySelectorAll('th, td');
                        var rowData = [];

                        cols.forEach(function(col) {
                            var text = col.innerText.replace(/"/g, '""').replace(/\r?\n|\r/g, ' ').trim();
                            text = text.replace(/₹/g, '').trim();
                            rowData.push('"' + text + '"');
                        });

                        if (rowData.length > 0) {
                            csv.push(rowData.join(','));
                        }
                    });

                    var csvBlob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
                    var downloadLink = document.createElement('a');
                    var agentName = $('#through option:selected').text().trim().replace(/[^a-zA-Z0-9]/g, '_');
                    downloadLink.href = URL.createObjectURL(csvBlob);
                    downloadLink.download = 'commission_report_' + agentName + '_' + new Date().toISOString().slice(0, 10) + '.csv';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                }
            </script>

        </div><!-- .content-wrapper -->
    </div><!-- .main-panel -->
</div><!-- .page-body-wrapper -->

<?php
if ($con) CloseCon($con);
if (file_exists('../footer.php')) include_once('../footer.php');
?>