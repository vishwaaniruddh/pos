<?php
ob_start();
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Database Connection
$con = null;
if (file_exists('../db_connection.php')) {
    include_once('../db_connection.php');
    if (function_exists('OpenSrishringarrCon')) {
        $con = OpenSrishringarrCon();
    }
}

if (!$con) {
    $is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);
    if ($is_local) {
        $con = @mysqli_connect("localhost", "root", "", "u464193275_srishringarr");
    } else {
        $con = @mysqli_connect("localhost", "u464193275_sarmicropos", "Mypos1234", "u464193275_srishringarr");
    }
}

if (!$con) {
    die("Database Connection Error: " . mysqli_connect_error());
}

mysqli_set_charset($con, "utf8");

// Ensure tables exist
mysqli_query($con, "CREATE TABLE IF NOT EXISTS stock_scan_sheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sheet_name VARCHAR(255) DEFAULT 'Untitled Scan Sheet',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    total_items INT DEFAULT 0,
    total_system_qty DECIMAL(10,2) DEFAULT 0,
    total_scanned_qty INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8");

mysqli_query($con, "CREATE TABLE IF NOT EXISTS stock_scan_sheet_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sheet_id INT NOT NULL,
    item_number VARCHAR(255) DEFAULT '',
    name VARCHAR(255) DEFAULT '',
    category VARCHAR(255) DEFAULT '',
    unit_price DECIMAL(10,2) DEFAULT 0,
    available_qty DECIMAL(10,2) DEFAULT 0,
    scanned_qty INT DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sheet_id) REFERENCES stock_scan_sheets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8");

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // List all sheets
    if ($action === 'list_sheets') {
        $result = mysqli_query($con, "SELECT * FROM stock_scan_sheets ORDER BY updated_at DESC");
        $sheets = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $sheets[] = $row;
        }
        echo json_encode(['status' => 'success', 'sheets' => $sheets]);
        exit;
    }

    // Create new sheet
    if ($action === 'create_sheet') {
        $name = 'Scan Sheet - ' . date('d M Y, h:i A');
        $stmt = mysqli_prepare($con, "INSERT INTO stock_scan_sheets (sheet_name) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        $id = mysqli_insert_id($con);
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'success', 'sheet_id' => $id, 'sheet_name' => $name]);
        exit;
    }

    // Delete sheet
    if ($action === 'delete_sheet') {
        $id = intval($_POST['sheet_id'] ?? 0);
        if ($id > 0) {
            $stmt = mysqli_prepare($con, "DELETE FROM stock_scan_sheets WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid sheet ID']);
        }
        exit;
    }

    // Duplicate sheet
    if ($action === 'duplicate_sheet') {
        $id = intval($_POST['sheet_id'] ?? 0);
        if ($id > 0) {
            $stmt = mysqli_prepare($con, "SELECT sheet_name FROM stock_scan_sheets WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $original = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if ($original) {
                $newName = $original['sheet_name'] . ' (Copy)';
                $stmt = mysqli_prepare($con, "INSERT INTO stock_scan_sheets (sheet_name) VALUES (?)");
                mysqli_stmt_bind_param($stmt, "s", $newName);
                mysqli_stmt_execute($stmt);
                $newId = mysqli_insert_id($con);
                mysqli_stmt_close($stmt);

                // Copy items
                $stmt = mysqli_prepare($con, "INSERT INTO stock_scan_sheet_items (sheet_id, item_number, name, category, unit_price, available_qty, scanned_qty, sort_order) SELECT ?, item_number, name, category, unit_price, available_qty, scanned_qty, sort_order FROM stock_scan_sheet_items WHERE sheet_id = ?");
                mysqli_stmt_bind_param($stmt, "ii", $newId, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Update totals on new sheet
                $stmt = mysqli_prepare($con, "UPDATE stock_scan_sheets SET total_items = (SELECT COUNT(*) FROM stock_scan_sheet_items WHERE sheet_id = ?), total_system_qty = (SELECT COALESCE(SUM(available_qty),0) FROM stock_scan_sheet_items WHERE sheet_id = ?), total_scanned_qty = (SELECT COALESCE(SUM(scanned_qty),0) FROM stock_scan_sheet_items WHERE sheet_id = ?) WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "iiii", $newId, $newId, $newId, $newId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                echo json_encode(['status' => 'success', 'sheet_id' => $newId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Sheet not found']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid sheet ID']);
        }
        exit;
    }
}

// Server-side CSV Export
if (isset($_GET['export']) && $_GET['export'] == '1') {
    if (ob_get_length()) ob_clean();
    $export_res = mysqli_query($con, "SELECT * FROM stock_scan_sheets ORDER BY updated_at DESC");
    if ($export_res) {
        $filename = "Stock_Scan_Records_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Sheet ID', 'Sheet Name', 'Created Date', 'Last Updated',
            'Distinct Items', 'System Qty', 'Scanned Qty', 'Variance (Scanned - System)', 'Status'
        ]);

        while ($erow = mysqli_fetch_assoc($export_res)) {
            $sys = floatval($erow['total_system_qty'] ?? 0);
            $scn = intval($erow['total_scanned_qty'] ?? 0);
            $var = $scn - $sys;
            $st  = ($var === 0) ? 'Matched' : (($var > 0) ? 'Surplus' : 'Shortage');

            fputcsv($output, [
                $erow['id'],
                $erow['sheet_name'],
                $erow['created_at'],
                $erow['updated_at'],
                $erow['total_items'],
                $sys,
                $scn,
                $var,
                $st
            ]);
        }
        fclose($output);
        exit;
    }
}

// Scope Summary & KPI Metrics
$kpi = [
    'total_sheets'   => 0,
    'total_items'    => 0,
    'total_scanned'  => 0,
    'total_system'   => 0,
    'total_variance' => 0
];

if ($con) {
    $res_kpi = mysqli_query($con, "
        SELECT 
            COUNT(id) as sheet_cnt,
            COALESCE(SUM(total_items), 0) as items_sum,
            COALESCE(SUM(total_scanned_qty), 0) as scanned_sum,
            COALESCE(SUM(total_system_qty), 0) as system_sum
        FROM stock_scan_sheets
    ");
    if ($res_kpi && $rkpi = mysqli_fetch_assoc($res_kpi)) {
        $kpi['total_sheets']   = intval($rkpi['sheet_cnt'] ?? 0);
        $kpi['total_items']    = intval($rkpi['items_sum'] ?? 0);
        $kpi['total_scanned']  = intval($rkpi['scanned_sum'] ?? 0);
        $kpi['total_system']   = floatval($rkpi['system_sum'] ?? 0);
        $kpi['total_variance'] = $kpi['total_scanned'] - $kpi['total_system'];
    }
}

if (file_exists(__DIR__ . '/../top-header.php')) include_once(__DIR__ . '/../top-header.php');
elseif (file_exists('../top-header.php')) include_once('../top-header.php');

if (file_exists(__DIR__ . '/../top-navbar.php')) include_once(__DIR__ . '/../top-navbar.php');
elseif (file_exists('../top-navbar.php')) include_once('../top-navbar.php');
?>

<div class="container-fluid page-body-wrapper">
    <?php
    if (file_exists(__DIR__ . '/../navbar.php')) include_once(__DIR__ . '/../navbar.php');
    elseif (file_exists('../navbar.php')) include_once('../navbar.php');
    ?>
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"
                integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

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

                /* Header */
                .pm-page-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 16px;
                    margin-bottom: 20px;
                    padding-bottom: 16px;
                    border-bottom: 1px solid var(--pm-slate-200);
                }

                .pm-page-title {
                    font-size: 22px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    letter-spacing: -0.02em;
                    margin: 0;
                }

                .pm-page-subtitle {
                    font-size: 13px;
                    color: var(--pm-slate-500);
                    margin-top: 4px;
                    margin-bottom: 0;
                }

                /* 5 KPI Metric Cards - 1 Single Row */
                .pm-kpi-grid {
                    display: grid;
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                    gap: 12px;
                    margin-bottom: 20px;
                }

                @media (max-width: 1200px) {
                    .pm-kpi-grid {
                        grid-template-columns: repeat(3, minmax(0, 1fr));
                    }
                }

                @media (max-width: 768px) {
                    .pm-kpi-grid {
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                    }
                }

                @media (max-width: 480px) {
                    .pm-kpi-grid {
                        grid-template-columns: 1fr;
                    }
                }

                .pm-kpi-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 14px 16px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    min-width: 0;
                }

                .pm-kpi-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 6px;
                    margin-bottom: 6px;
                }

                .pm-kpi-label {
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                    color: var(--pm-slate-500);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .pm-kpi-icon {
                    width: 28px;
                    height: 28px;
                    border-radius: 6px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--pm-slate-600);
                    font-size: 12px;
                    flex-shrink: 0;
                }

                .pm-kpi-value {
                    font-size: 20px;
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
                    margin-top: 4px;
                    line-height: 1.3;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                /* Card Surface */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 20px;
                    overflow: hidden;
                }

                .pm-card-body {
                    padding: 16px 20px;
                }

                /* Buttons */
                .pm-btn {
                    height: 36px;
                    padding: 0 14px;
                    font-size: 13px;
                    font-weight: 500;
                    border-radius: 6px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                    white-space: nowrap;
                    border: 1px solid transparent;
                    box-sizing: border-box;
                }

                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                .pm-btn-primary:hover {
                    background: var(--pm-slate-800);
                    color: #ffffff;
                }

                .pm-btn-secondary {
                    background: #ffffff;
                    color: var(--pm-slate-700);
                    border-color: var(--pm-slate-200);
                }

                .pm-btn-secondary:hover {
                    background: var(--pm-slate-50);
                    border-color: var(--pm-slate-300);
                    color: var(--pm-slate-900);
                }

                .pm-btn-sm {
                    height: 28px;
                    padding: 0 8px;
                    font-size: 11px;
                    border-radius: 4px;
                    gap: 4px;
                }

                .pm-btn-danger {
                    background: #ffffff;
                    color: #dc2626;
                    border-color: #fecaca;
                }

                .pm-btn-danger:hover {
                    background: #fef2f2;
                    border-color: #dc2626;
                }

                /* Toolbar */
                .pm-toolbar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 12px;
                    margin-bottom: 16px;
                }

                .pm-search-input {
                    height: 36px;
                    padding: 0 12px 0 34px;
                    font-size: 13px;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    background: #ffffff;
                    width: 280px;
                    outline: none;
                }

                .pm-search-input:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                }

                .pm-search-wrapper {
                    position: relative;
                    display: inline-block;
                }

                .pm-search-wrapper i {
                    position: absolute;
                    left: 11px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: var(--pm-slate-400);
                    font-size: 13px;
                }

                .view-toggles {
                    display: flex;
                    gap: 2px;
                    background: var(--pm-slate-100);
                    border-radius: 6px;
                    padding: 2px;
                    border: 1px solid var(--pm-slate-200);
                }

                .view-toggle {
                    height: 30px;
                    padding: 0 10px;
                    border: none;
                    background: transparent;
                    color: var(--pm-slate-600);
                    font-size: 12px;
                    cursor: pointer;
                    border-radius: 4px;
                    transition: all 0.15s ease;
                }

                .view-toggle.active {
                    background: #ffffff;
                    color: var(--pm-slate-900);
                    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                    font-weight: 600;
                }

                /* Grid Cards */
                .sheets-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                    gap: 16px;
                }

                .sheet-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
                    padding: 16px;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .sheet-card:hover {
                    border-color: var(--pm-slate-300);
                    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
                }

                .sheet-card-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 10px;
                }

                .sheet-title {
                    font-size: 14px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .sheet-dates {
                    font-size: 11px;
                    color: var(--pm-slate-400);
                    margin-top: 3px;
                }

                .sheet-stats-row {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 6px;
                    margin: 12px 0;
                    padding: 10px;
                    background: var(--pm-slate-50);
                    border-radius: 6px;
                    border: 1px solid var(--pm-slate-200);
                }

                .mini-metric {
                    text-align: center;
                }

                .mini-metric-label {
                    font-size: 10px;
                    font-weight: 600;
                    text-transform: uppercase;
                    color: var(--pm-slate-500);
                }

                .mini-metric-val {
                    font-size: 15px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    font-family: ui-monospace, monospace;
                    margin-top: 2px;
                }

                .sheet-card-actions {
                    display: flex;
                    gap: 6px;
                    align-items: center;
                    margin-top: 6px;
                }

                /* Table View */
                .sheets-list-table {
                    display: none;
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 12px;
                    text-align: left;
                    white-space: nowrap;
                }

                .sheets-list-table th {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-700);
                    font-weight: 600;
                    padding: 11px 14px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    font-size: 11px;
                    text-transform: uppercase;
                }

                .sheets-list-table td {
                    padding: 11px 14px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
                    vertical-align: middle;
                }

                .sheets-list-table tbody tr:hover {
                    background-color: #f1f5f9;
                }

                /* Badges */
                .pm-badge-neutral {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    padding: 2px 7px;
                    font-size: 11px;
                    font-weight: 500;
                    border-radius: 4px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-600);
                }

                .pm-badge-var {
                    display: inline-flex;
                    align-items: center;
                    padding: 2px 6px;
                    font-size: 11px;
                    font-weight: 600;
                    border-radius: 4px;
                    font-family: ui-monospace, monospace;
                }

                .pm-badge-var.zero {
                    background: #f1f5f9;
                    color: #475569;
                    border: 1px solid #e2e8f0;
                }

                .pm-badge-var.positive {
                    background: #f0fdf4;
                    color: #15803d;
                    border: 1px solid #bbf7d0;
                }

                .pm-badge-var.negative {
                    background: #fef2f2;
                    color: #b91c1c;
                    border: 1px solid #fecaca;
                }

                /* Confirm Dialog Modal */
                .confirm-overlay {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(15, 23, 42, 0.4);
                    backdrop-filter: blur(2px);
                    z-index: 99999;
                    align-items: center;
                    justify-content: center;
                }

                .confirm-dialog {
                    background: #ffffff;
                    border-radius: 8px;
                    border: 1px solid var(--pm-slate-200);
                    padding: 24px;
                    width: 400px;
                    max-width: 90vw;
                    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
                }

                .confirm-dialog h3 {
                    font-size: 16px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin-bottom: 8px;
                }

                .confirm-dialog p {
                    font-size: 13px;
                    color: var(--pm-slate-500);
                    margin-bottom: 20px;
                    line-height: 1.5;
                }

                .confirm-actions {
                    display: flex;
                    gap: 8px;
                    justify-content: flex-end;
                }

                /* Toast */
                .toast-container {
                    position: fixed;
                    bottom: 24px;
                    right: 24px;
                    z-index: 99999;
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }

                .toast-msg {
                    padding: 10px 16px;
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-radius: 6px;
                    font-size: 13px;
                    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
                    animation: toastIn 0.2s ease;
                }

                @keyframes toastIn {
                    from { opacity: 0; transform: translateY(8px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                /* Print Styles */
                @media print {
                    .navbar, #sidebar, .pm-page-header .pm-btn, .pm-toolbar, .view-toggles, .pm-btn, .sheet-card-actions {
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
                    .pm-kpi-grid {
                        grid-template-columns: repeat(5, 1fr) !important;
                        gap: 8px !important;
                    }
                    .sheets-list-table {
                        display: table !important;
                    }
                    .sheets-grid {
                        display: none !important;
                    }
                }
            </style>

            <div class="records-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa-solid fa-table-cells-large" style="margin-right: 10px; font-size: 20px; color: var(--pm-slate-700);"></i>
                            Stock Scan Records
                        </h1>
                        <p class="pm-page-subtitle">
                            Inventory barcode audit sheets, physical vs system reconciliation logs, and variance dockets.
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button type="button" class="pm-btn pm-btn-secondary" onclick="window.print();">
                            <i class="fa fa-print"></i> Print Report
                        </button>
                        <a href="stock_scan_records.php?export=1" class="pm-btn pm-btn-secondary" id="btnExportCSV">
                            <i class="fa fa-download"></i> Export CSV
                        </a>
                        <button type="button" class="pm-btn pm-btn-primary" onclick="createNewSheet()">
                            <i class="fa fa-plus"></i> New Scan Sheet
                        </button>
                    </div>
                </div>

                <!-- 5 KPI Metric Cards across in a single row -->
                <div class="pm-kpi-grid">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Audit Scan Sheets</span>
                            <div class="pm-kpi-icon"><i class="fa fa-file-lines"></i></div>
                        </div>
                        <div class="pm-kpi-value" id="statTotalSheets"><?= number_format($kpi['total_sheets']) ?></div>
                        <div class="pm-kpi-sub">Saved audit batches</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Total SKUs Audited</span>
                            <div class="pm-kpi-icon"><i class="fa fa-cubes"></i></div>
                        </div>
                        <div class="pm-kpi-value" id="statTotalItems"><?= number_format($kpi['total_items']) ?></div>
                        <div class="pm-kpi-sub">Total distinct line items</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Scanned Physical Units</span>
                            <div class="pm-kpi-icon"><i class="fa fa-barcode"></i></div>
                        </div>
                        <div class="pm-kpi-value" id="statTotalScanned"><?= number_format($kpi['total_scanned']) ?></div>
                        <div class="pm-kpi-sub">Physically verified count</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Expected System Qty</span>
                            <div class="pm-kpi-icon"><i class="fa fa-database"></i></div>
                        </div>
                        <div class="pm-kpi-value" id="statTotalSystem"><?= number_format($kpi['total_system']) ?></div>
                        <div class="pm-kpi-sub">ERP recorded inventory</div>
                    </div>

                    <div class="pm-kpi-card">
                        <div class="pm-kpi-header">
                            <span class="pm-kpi-label">Net Audit Variance</span>
                            <div class="pm-kpi-icon"><i class="fa fa-scale-balanced"></i></div>
                        </div>
                        <div class="pm-kpi-value" id="statTotalVariance" style="color: <?= ($kpi['total_variance'] === 0) ? 'var(--pm-slate-900)' : (($kpi['total_variance'] > 0) ? '#15803d' : '#b91c1c') ?>;">
                            <?= ($kpi['total_variance'] > 0 ? '+' : '') . number_format($kpi['total_variance']) ?>
                        </div>
                        <div class="pm-kpi-sub">Physical vs System delta</div>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="pm-toolbar">
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <div class="pm-search-wrapper">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="searchSheets" class="pm-search-input" placeholder="Search scan sheets...">
                        </div>
                        <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" onclick="loadSheets()" title="Refresh Sheet Records">
                            <i class="fa-solid fa-arrows-rotate"></i> Refresh
                        </button>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="pm-badge-neutral" id="visibleSheetCount">
                            <?= number_format($kpi['total_sheets']) ?> sheets
                        </span>
                        <div class="view-toggles">
                            <button class="view-toggle active" data-view="grid" onclick="setView('grid')">
                                <i class="fa-solid fa-grid-2"></i> Grid
                            </button>
                            <button class="view-toggle" data-view="list" onclick="setView('list')">
                                <i class="fa-solid fa-list"></i> Table
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="loadingSpinner" style="display: none; text-align: center; padding: 40px; color: var(--pm-slate-500);">
                    <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 24px; margin-bottom: 8px;"></i>
                    <p style="font-size: 13px;">Loading scan records...</p>
                </div>

                <!-- Grid View -->
                <div class="sheets-grid" id="sheetsGrid"></div>

                <!-- Table List View -->
                <div class="pm-card" id="sheetsListCard" style="display: none;">
                    <div style="overflow-x: auto;">
                        <table class="sheets-list-table" id="sheetsTable" style="display: table;">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th>Sheet Name</th>
                                    <th>Created Date</th>
                                    <th>Last Updated</th>
                                    <th style="text-align: right;">Items</th>
                                    <th style="text-align: right;">System Qty</th>
                                    <th style="text-align: right;">Scanned Qty</th>
                                    <th style="text-align: right;">Variance</th>
                                    <th style="text-align: center; width: 140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sheetsTableBody"></tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Confirm Delete Dialog -->
            <div class="confirm-overlay" id="confirmOverlay">
                <div class="confirm-dialog">
                    <h3><i class="fa-solid fa-triangle-exclamation" style="color: #dc2626; margin-right: 6px;"></i> Delete Scan Sheet?</h3>
                    <p id="confirmMsg">This will permanently delete this sheet and all its scanned line items. This action cannot be undone.</p>
                    <div class="confirm-actions">
                        <button class="pm-btn pm-btn-secondary" onclick="closeConfirm()">Cancel</button>
                        <button class="pm-btn pm-btn-danger" id="confirmDeleteBtn">Delete</button>
                    </div>
                </div>
            </div>

            <!-- Toast Container -->
            <div class="toast-container" id="toastContainer"></div>

            <script>
                let allSheets = [];
                let currentView = 'grid';
                let deleteTargetId = null;

                document.addEventListener('DOMContentLoaded', function () {
                    loadSheets();

                    document.getElementById('searchSheets').addEventListener('input', function () {
                        renderSheets(filterSheets(this.value.trim()));
                    });
                });

                function loadSheets() {
                    const grid = document.getElementById('sheetsGrid');
                    const spinner = document.getElementById('loadingSpinner');

                    grid.innerHTML = '';
                    spinner.style.display = 'block';

                    const formData = new FormData();
                    formData.append('action', 'list_sheets');

                    fetch('stock_scan_records.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            spinner.style.display = 'none';
                            if (data.status === 'success') {
                                allSheets = data.sheets;
                                updateStats();
                                renderSheets(allSheets);
                            }
                        })
                        .catch(err => {
                            spinner.style.display = 'none';
                            console.error(err);
                            showToast('Failed to load sheets', 'error');
                        });
                }

                function updateStats() {
                    document.getElementById('statTotalSheets').innerText = allSheets.length;
                    let totalItems = 0, totalScanned = 0, totalSystem = 0;
                    allSheets.forEach(s => {
                        totalItems   += parseInt(s.total_items || 0);
                        totalScanned += parseInt(s.total_scanned_qty || 0);
                        totalSystem  += parseFloat(s.total_system_qty || 0);
                    });
                    document.getElementById('statTotalItems').innerText   = totalItems.toLocaleString();
                    document.getElementById('statTotalScanned').innerText = totalScanned.toLocaleString();
                    document.getElementById('statTotalSystem').innerText  = totalSystem.toLocaleString();

                    const diff = totalScanned - totalSystem;
                    const diffEl = document.getElementById('statTotalVariance');
                    diffEl.innerText = (diff > 0 ? '+' : '') + diff.toLocaleString();
                    diffEl.style.color = (diff === 0) ? 'var(--pm-slate-900)' : ((diff > 0) ? '#15803d' : '#b91c1c');
                }

                function filterSheets(query) {
                    if (!query) return allSheets;
                    const q = query.toLowerCase();
                    return allSheets.filter(s => (s.sheet_name || '').toLowerCase().includes(q));
                }

                function renderSheets(sheets) {
                    const grid = document.getElementById('sheetsGrid');
                    const tbody = document.getElementById('sheetsTableBody');
                    const countBadge = document.getElementById('visibleSheetCount');

                    if (countBadge) {
                        countBadge.innerText = sheets.length + ' sheets';
                    }

                    if (sheets.length === 0) {
                        const empty = `
                            <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; background: #ffffff; border: 1px solid var(--pm-slate-200); border-radius: 8px;">
                                <i class="fa-solid fa-folder-open" style="font-size: 28px; color: var(--pm-slate-300); margin-bottom: 8px; display: block;"></i>
                                <h3 style="font-size: 15px; font-weight: 700; color: var(--pm-slate-900);">No Scan Sheets Found</h3>
                                <p style="font-size: 13px; color: var(--pm-slate-500); margin: 6px 0 16px;">Create a new stock scan sheet to audit inventory via barcode scanner.</p>
                                <button class="pm-btn pm-btn-primary pm-btn-sm" onclick="createNewSheet()">
                                    <i class="fa-solid fa-plus"></i> Create New Sheet
                                </button>
                            </div>`;
                        grid.innerHTML = empty;
                        if (tbody) tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 30px; color: var(--pm-slate-400);">No matching sheets</td></tr>`;
                        return;
                    }

                    // Grid render
                    grid.innerHTML = sheets.map(s => {
                        const sys = parseFloat(s.total_system_qty || 0);
                        const scn = parseInt(s.total_scanned_qty || 0);
                        const diff = scn - sys;
                        const diffClass = (diff === 0) ? 'zero' : (diff > 0 ? 'positive' : 'negative');
                        const diffSign = diff > 0 ? '+' : '';

                        return `
                        <div class="sheet-card" id="card_${s.id}">
                            <div>
                                <div class="sheet-card-header">
                                    <div style="min-width: 0; flex: 1;">
                                        <h3 class="sheet-title" title="${escapeHtml(s.sheet_name)}">${escapeHtml(s.sheet_name)}</h3>
                                        <div class="sheet-dates">
                                            <i class="fa-regular fa-calendar"></i> ${formatDate(s.created_at)} &bull; ${timeAgo(s.updated_at)}
                                        </div>
                                    </div>
                                    <span class="pm-badge-var ${diffClass}" style="margin-left: 8px;">
                                        ${diffSign}${diff}
                                    </span>
                                </div>

                                <div class="sheet-stats-row">
                                    <div class="mini-metric">
                                        <div class="mini-metric-label">Items</div>
                                        <div class="mini-metric-val">${s.total_items || 0}</div>
                                    </div>
                                    <div class="mini-metric">
                                        <div class="mini-metric-label">System</div>
                                        <div class="mini-metric-val">${sys.toFixed(0)}</div>
                                    </div>
                                    <div class="mini-metric">
                                        <div class="mini-metric-label">Scanned</div>
                                        <div class="mini-metric-val" style="color: var(--pm-slate-900);">${scn}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="sheet-card-actions">
                                <button class="pm-btn pm-btn-primary pm-btn-sm" style="flex: 1;" onclick="openSheet(${s.id})">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Sheet
                                </button>
                                <button class="pm-btn pm-btn-secondary pm-btn-sm" title="Duplicate Sheet" onclick="duplicateSheet(${s.id})">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                                <button class="pm-btn pm-btn-danger pm-btn-sm" title="Delete Sheet" onclick="confirmDelete(${s.id}, '${escapeHtml(s.sheet_name)}')">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                        `;
                    }).join('');

                    // Table List render
                    if (tbody) {
                        tbody.innerHTML = sheets.map(s => {
                            const sys = parseFloat(s.total_system_qty || 0);
                            const scn = parseInt(s.total_scanned_qty || 0);
                            const diff = scn - sys;
                            const diffClass = (diff === 0) ? 'zero' : (diff > 0 ? 'positive' : 'negative');
                            const diffSign = diff > 0 ? '+' : '';

                            return `
                            <tr>
                                <td style="font-family: monospace; color: var(--pm-slate-400);">#${s.id}</td>
                                <td>
                                    <a href="javascript:void(0)" onclick="openSheet(${s.id})" style="font-weight: 600; color: var(--pm-slate-900); text-decoration: none;">
                                        ${escapeHtml(s.sheet_name)}
                                    </a>
                                </td>
                                <td>${formatDate(s.created_at)}</td>
                                <td style="color: var(--pm-slate-500);">${timeAgo(s.updated_at)}</td>
                                <td style="text-align: right; font-family: monospace;">${s.total_items || 0}</td>
                                <td style="text-align: right; font-family: monospace;">${sys.toFixed(0)}</td>
                                <td style="text-align: right; font-family: monospace; font-weight: 600;">${scn}</td>
                                <td style="text-align: right;">
                                    <span class="pm-badge-var ${diffClass}">${diffSign}${diff}</span>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 4px;">
                                        <button class="pm-btn pm-btn-primary pm-btn-sm" onclick="openSheet(${s.id})">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Open
                                        </button>
                                        <button class="pm-btn pm-btn-secondary pm-btn-sm" onclick="duplicateSheet(${s.id})">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                        <button class="pm-btn pm-btn-danger pm-btn-sm" onclick="confirmDelete(${s.id}, '${escapeHtml(s.sheet_name)}')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            `;
                        }).join('');
                    }
                }

                function setView(view) {
                    currentView = view;
                    document.querySelectorAll('.view-toggle').forEach(b => b.classList.remove('active'));
                    const btn = document.querySelector(`.view-toggle[data-view="${view}"]`);
                    if (btn) btn.classList.add('active');

                    const grid = document.getElementById('sheetsGrid');
                    const listCard = document.getElementById('sheetsListCard');

                    if (view === 'grid') {
                        grid.style.display = 'grid';
                        listCard.style.display = 'none';
                    } else {
                        grid.style.display = 'none';
                        listCard.style.display = 'block';
                    }
                }

                function createNewSheet() {
                    const formData = new FormData();
                    formData.append('action', 'create_sheet');

                    fetch('stock_scan_records.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                window.location.href = 'stock_scan.php?sheet_id=' + data.sheet_id;
                            } else {
                                showToast('Failed to create sheet', 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            showToast('Error creating sheet', 'error');
                        });
                }

                function openSheet(id) {
                    window.location.href = 'stock_scan.php?sheet_id=' + id;
                }

                function duplicateSheet(id) {
                    const formData = new FormData();
                    formData.append('action', 'duplicate_sheet');
                    formData.append('sheet_id', id);

                    fetch('stock_scan_records.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                showToast('Sheet duplicated successfully');
                                loadSheets();
                            } else {
                                showToast(data.message || 'Failed to duplicate sheet', 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            showToast('Error duplicating sheet', 'error');
                        });
                }

                function confirmDelete(id, name) {
                    deleteTargetId = id;
                    document.getElementById('confirmMsg').innerText = `Are you sure you want to permanently delete "${name}"? This action cannot be undone.`;
                    document.getElementById('confirmOverlay').style.display = 'flex';

                    document.getElementById('confirmDeleteBtn').onclick = function () {
                        executeDelete(deleteTargetId);
                    };
                }

                function closeConfirm() {
                    deleteTargetId = null;
                    document.getElementById('confirmOverlay').style.display = 'none';
                }

                function executeDelete(id) {
                    const formData = new FormData();
                    formData.append('action', 'delete_sheet');
                    formData.append('sheet_id', id);

                    fetch('stock_scan_records.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            closeConfirm();
                            if (data.status === 'success') {
                                showToast('Sheet deleted successfully');
                                loadSheets();
                            } else {
                                showToast(data.message || 'Failed to delete sheet', 'error');
                            }
                        })
                        .catch(err => {
                            closeConfirm();
                            console.error(err);
                            showToast('Error deleting sheet', 'error');
                        });
                }

                function showToast(msg) {
                    const container = document.getElementById('toastContainer');
                    const toast = document.createElement('div');
                    toast.className = 'toast-msg';
                    toast.innerText = msg;
                    container.appendChild(toast);
                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                }

                function formatDate(dt) {
                    if (!dt) return '—';
                    const d = new Date(dt.replace(' ', 'T'));
                    if (isNaN(d)) return dt;
                    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
                }

                function timeAgo(dt) {
                    if (!dt) return '';
                    const d = new Date(dt.replace(' ', 'T'));
                    if (isNaN(d)) return '';
                    const now = new Date();
                    const sec = Math.floor((now - d) / 1000);
                    if (sec < 60) return 'Just now';
                    const min = Math.floor(sec / 60);
                    if (min < 60) return `${min}m ago`;
                    const hr = Math.floor(min / 60);
                    if (hr < 24) return `${hr}h ago`;
                    const days = Math.floor(hr / 24);
                    if (days < 30) return `${days}d ago`;
                    return formatDate(dt);
                }

                function escapeHtml(str) {
                    if (!str) return '';
                    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                }
            </script>

        </div><!-- /content-wrapper -->
    </div><!-- /main-panel -->
</div><!-- /container-fluid page-body-wrapper -->

<?php
if ($con) {
    CloseCon($con);
}
?>
</body>
</html>
