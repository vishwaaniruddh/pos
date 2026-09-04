<?php
ob_start();
// stock_scan_records.php - Stock Scan Sheet Records Dashboard
// Lists all saved scan sheets with create/open/delete actions
session_start();

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

// Create tables if they don't exist
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
            // Get original sheet
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Scan Records - Srishringarr POS</title>
    <meta name="description" content="Manage and view all stock scan sheets for Srishringarr POS inventory auditing.">

    <!-- Font Awesome & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --green-50: #ecfdf5;
            --green-100: #d1fae5;
            --green-200: #a7f3d0;
            --green-400: #34d399;
            --green-500: #10b981;
            --green-600: #059669;
            --green-700: #047857;
            --green-800: #065f46;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --red-500: #ef4444;
            --red-600: #dc2626;
            --amber-500: #f59e0b;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.04);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--slate-50);
            color: var(--slate-800);
            min-height: 100vh;
        }

        /* ─── Top Navigation Bar ─── */
        .nav-bar {
            background: linear-gradient(135deg, var(--green-700) 0%, var(--green-800) 100%);
            padding: 0 32px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .nav-brand-icon {
            background: rgba(255,255,255,0.2);
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            backdrop-filter: blur(4px);
        }

        .nav-brand h1 {
            font-size: 18px;
            font-weight: 700;
            color: white;
            letter-spacing: -0.3px;
        }

        .nav-brand p {
            font-size: 11px;
            color: rgba(255,255,255,0.7);
            font-weight: 400;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ─── Buttons ─── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary {
            background: white;
            color: var(--green-700);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background: var(--green-50);
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .btn-ghost {
            background: rgba(255,255,255,0.15);
            color: white;
            backdrop-filter: blur(4px);
        }

        .btn-ghost:hover {
            background: rgba(255,255,255,0.25);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
        }

        .btn-outline {
            background: white;
            color: var(--slate-600);
            border: 1px solid var(--slate-200);
            box-shadow: var(--shadow-sm);
        }

        .btn-outline:hover {
            background: var(--slate-50);
            border-color: var(--slate-300);
        }

        .btn-danger-outline {
            background: white;
            color: var(--red-600);
            border: 1px solid #fecaca;
        }

        .btn-danger-outline:hover {
            background: #fef2f2;
            border-color: var(--red-500);
        }

        .btn-open {
            background: var(--green-600);
            color: white;
        }

        .btn-open:hover {
            background: var(--green-700);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* ─── Main Content ─── */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        /* ─── Stats Bar ─── */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--slate-200);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.green { background: var(--green-100); color: var(--green-700); }
        .stat-icon.blue { background: #dbeafe; color: var(--blue-600); }
        .stat-icon.amber { background: #fef3c7; color: #b45309; }

        .stat-info h3 {
            font-size: 24px;
            font-weight: 800;
            color: var(--slate-900);
            letter-spacing: -0.5px;
        }

        .stat-info p {
            font-size: 12px;
            color: var(--slate-500);
            font-weight: 500;
            margin-top: 2px;
        }

        /* ─── Toolbar ─── */
        .toolbar-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .search-wrapper {
            position: relative;
            width: 320px;
        }

        .search-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--slate-400);
            font-size: 14px;
        }

        .search-wrapper input {
            width: 100%;
            padding: 10px 14px 10px 40px;
            border: 1px solid var(--slate-200);
            border-radius: 10px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            background: white;
            color: var(--slate-800);
            box-shadow: var(--shadow-sm);
            outline: none;
            transition: all 0.2s ease;
        }

        .search-wrapper input:focus {
            border-color: var(--green-400);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
        }

        .view-toggles {
            display: flex;
            gap: 4px;
            background: var(--slate-100);
            border-radius: 8px;
            padding: 3px;
        }

        .view-toggle {
            padding: 6px 12px;
            border: none;
            background: transparent;
            color: var(--slate-500);
            font-size: 13px;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .view-toggle.active {
            background: white;
            color: var(--slate-800);
            box-shadow: var(--shadow-sm);
            font-weight: 600;
        }

        /* ─── Sheet Cards Grid ─── */
        .sheets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 16px;
        }

        .sheet-card {
            background: white;
            border-radius: 14px;
            border: 1px solid var(--slate-200);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: all 0.25s ease;
            cursor: default;
            position: relative;
        }

        .sheet-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-3px);
            border-color: var(--green-200);
        }

        .sheet-card-header {
            padding: 20px 20px 0;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .sheet-card-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--green-400), var(--green-600));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
        }

        .sheet-card-menu {
            position: relative;
        }

        .sheet-card-menu-btn {
            background: transparent;
            border: none;
            color: var(--slate-400);
            padding: 4px 6px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.15s ease;
        }

        .sheet-card-menu-btn:hover {
            background: var(--slate-100);
            color: var(--slate-600);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid var(--slate-200);
            border-radius: 10px;
            box-shadow: var(--shadow-xl);
            min-width: 180px;
            z-index: 50;
            padding: 6px;
            animation: fadeIn 0.15s ease;
        }

        .dropdown-menu.show { display: block; }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            font-size: 13px;
            color: var(--slate-700);
            border: none;
            background: transparent;
            width: 100%;
            cursor: pointer;
            border-radius: 6px;
            transition: background 0.1s ease;
            text-align: left;
        }

        .dropdown-item:hover {
            background: var(--slate-50);
        }

        .dropdown-item.danger {
            color: var(--red-600);
        }

        .dropdown-item.danger:hover {
            background: #fef2f2;
        }

        .dropdown-divider {
            height: 1px;
            background: var(--slate-200);
            margin: 4px 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .sheet-card-body {
            padding: 14px 20px 16px;
        }

        .sheet-card-body h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--slate-900);
            margin-bottom: 6px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            letter-spacing: -0.2px;
        }

        .sheet-card-dates {
            font-size: 11px;
            color: var(--slate-400);
            display: flex;
            gap: 14px;
            margin-bottom: 14px;
        }

        .sheet-card-dates span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .sheet-card-stats {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }

        .mini-stat {
            background: var(--slate-50);
            border-radius: 8px;
            padding: 8px 12px;
            flex: 1;
            text-align: center;
        }

        .mini-stat-value {
            font-size: 16px;
            font-weight: 800;
            color: var(--slate-900);
        }

        .mini-stat-label {
            font-size: 10px;
            font-weight: 500;
            color: var(--slate-500);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .mini-stat.green-tint { background: var(--green-50); }
        .mini-stat.green-tint .mini-stat-value { color: var(--green-700); }

        .sheet-card-footer {
            padding: 0 20px 18px;
            display: flex;
            gap: 8px;
        }

        .sheet-card-footer .btn {
            flex: 1;
            justify-content: center;
        }

        /* ─── List View ─── */
        .sheets-list {
            display: none;
            flex-direction: column;
            gap: 6px;
        }

        .sheets-list.active-view { display: flex; }
        .sheets-grid.active-view { display: grid; }

        .sheet-list-item {
            background: white;
            border: 1px solid var(--slate-200);
            border-radius: 10px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }

        .sheet-list-item:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--green-200);
        }

        .sheet-list-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--green-400), var(--green-600));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 15px;
            flex-shrink: 0;
        }

        .sheet-list-info {
            flex: 1;
            min-width: 0;
        }

        .sheet-list-info h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--slate-800);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sheet-list-info p {
            font-size: 11px;
            color: var(--slate-400);
        }

        .sheet-list-stats {
            display: flex;
            gap: 20px;
            flex-shrink: 0;
        }

        .sheet-list-stats .mini-stat {
            min-width: 70px;
        }

        .sheet-list-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }

        /* ─── Empty State ─── */
        .empty-state {
            text-align: center;
            padding: 80px 30px;
            grid-column: 1 / -1;
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: var(--green-50);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
            color: var(--green-400);
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--slate-700);
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            color: var(--slate-500);
            max-width: 360px;
            margin: 0 auto 24px;
            line-height: 1.6;
        }

        /* ─── Toast ─── */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .toast {
            background: var(--slate-800);
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: toastIn 0.25s ease-out;
        }

        .toast-success { border-left: 4px solid var(--green-500); }
        .toast-error { border-left: 4px solid var(--red-500); }

        @keyframes toastIn {
            from { transform: translateX(30px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* ─── Confirm Dialog ─── */
        .confirm-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }

        .confirm-overlay.show {
            display: flex;
        }

        .confirm-dialog {
            background: white;
            border-radius: 16px;
            padding: 28px;
            width: 400px;
            max-width: 90vw;
            box-shadow: var(--shadow-xl);
            animation: dialogIn 0.2s ease;
        }

        @keyframes dialogIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .confirm-dialog h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--slate-900);
            margin-bottom: 8px;
        }

        .confirm-dialog p {
            font-size: 13px;
            color: var(--slate-500);
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .confirm-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .confirm-actions .btn {
            min-width: 90px;
            justify-content: center;
        }

        .btn-danger {
            background: var(--red-600);
            color: white;
        }

        .btn-danger:hover {
            background: var(--red-500);
        }

        /* ─── Loading ─── */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 40px;
            grid-column: 1 / -1;
        }

        .spinner {
            width: 36px;
            height: 36px;
            border: 3px solid var(--slate-200);
            border-top-color: var(--green-500);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            margin: 0 auto 12px;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .nav-bar { padding: 0 16px; }
            .main-content { padding: 20px 16px; }
            .stats-bar { grid-template-columns: 1fr; }
            .sheets-grid { grid-template-columns: 1fr; }
            .search-wrapper { width: 100%; }
            .toolbar-section { flex-direction: column; align-items: stretch; }
            .sheet-list-stats { display: none; }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="nav-bar">
        <div class="nav-brand">
            <div class="nav-brand-icon"><i class="fa-solid fa-table-cells-large"></i></div>
            <div>
                <h1>Stock Scan Records</h1>
                <p>Srishringarr POS &bull; Inventory Audit Sheets</p>
            </div>
        </div>
        <div class="nav-actions">
            <button class="btn btn-ghost btn-sm" onclick="loadSheets()">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh
            </button>
            <button class="btn btn-primary" onclick="createNewSheet()">
                <i class="fa-solid fa-plus"></i> New Sheet
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Stats -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-file-lines"></i></div>
                <div class="stat-info">
                    <h3 id="statTotalSheets">0</h3>
                    <p>Total Sheets</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-cubes"></i></div>
                <div class="stat-info">
                    <h3 id="statTotalItems">0</h3>
                    <p>Total Items Scanned</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amber"><i class="fa-solid fa-barcode"></i></div>
                <div class="stat-info">
                    <h3 id="statTotalScanned">0</h3>
                    <p>Total Scanned Qty</p>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar-section">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchSheets" placeholder="Search sheets by name..." autocomplete="off">
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="view-toggles">
                    <button class="view-toggle active" data-view="grid" onclick="setView('grid')">
                        <i class="fa-solid fa-grid-2"></i>
                    </button>
                    <button class="view-toggle" data-view="list" onclick="setView('list')">
                        <i class="fa-solid fa-list"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
            <p style="color: var(--slate-500); font-size: 13px;">Loading sheets...</p>
        </div>

        <!-- Grid View -->
        <div class="sheets-grid active-view" id="sheetsGrid"></div>

        <!-- List View -->
        <div class="sheets-list" id="sheetsList"></div>
    </div>

    <!-- Confirm Delete Dialog -->
    <div class="confirm-overlay" id="confirmOverlay">
        <div class="confirm-dialog">
            <h3><i class="fa-solid fa-triangle-exclamation" style="color: var(--red-500);"></i>&nbsp; Delete Scan Sheet?</h3>
            <p id="confirmMsg">This will permanently delete this sheet and all its scanned items. This action cannot be undone.</p>
            <div class="confirm-actions">
                <button class="btn btn-outline" onclick="closeConfirm()">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        let allSheets = [];
        let currentView = 'grid';
        let deleteTargetId = null;

        // ─── Init ───
        document.addEventListener('DOMContentLoaded', function () {
            loadSheets();

            document.getElementById('searchSheets').addEventListener('input', function () {
                renderSheets(filterSheets(this.value.trim()));
            });

            // Close dropdowns on outside click
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.sheet-card-menu')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
                }
            });
        });

        // ─── Load Sheets from Server ───
        function loadSheets() {
            const grid = document.getElementById('sheetsGrid');
            const list = document.getElementById('sheetsList');
            const spinner = document.getElementById('loadingSpinner');

            grid.innerHTML = '';
            list.innerHTML = '';
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

        // ─── Update Stats ───
        function updateStats() {
            document.getElementById('statTotalSheets').innerText = allSheets.length;
            let totalItems = 0, totalScanned = 0;
            allSheets.forEach(s => {
                totalItems += parseInt(s.total_items || 0);
                totalScanned += parseInt(s.total_scanned_qty || 0);
            });
            document.getElementById('statTotalItems').innerText = totalItems;
            document.getElementById('statTotalScanned').innerText = totalScanned;
        }

        // ─── Filter ───
        function filterSheets(query) {
            if (!query) return allSheets;
            const q = query.toLowerCase();
            return allSheets.filter(s => (s.sheet_name || '').toLowerCase().includes(q));
        }

        // ─── Render Sheets ───
        function renderSheets(sheets) {
            const grid = document.getElementById('sheetsGrid');
            const list = document.getElementById('sheetsList');

            if (sheets.length === 0) {
                const empty = `
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fa-solid fa-folder-open"></i></div>
                        <h3>No Scan Sheets Found</h3>
                        <p>Create your first stock scan sheet to start auditing inventory with barcode scanning.</p>
                        <button class="btn btn-open" onclick="createNewSheet()">
                            <i class="fa-solid fa-plus"></i> Create First Sheet
                        </button>
                    </div>`;
                grid.innerHTML = empty;
                list.innerHTML = empty;
                return;
            }

            // Grid cards
            grid.innerHTML = sheets.map(s => `
                <div class="sheet-card" id="card_${s.id}">
                    <div class="sheet-card-header">
                        <div class="sheet-card-icon"><i class="fa-solid fa-file-spreadsheet"></i></div>
                        <div class="sheet-card-menu">
                            <button class="sheet-card-menu-btn" onclick="toggleMenu(event, ${s.id})">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <div class="dropdown-menu" id="menu_${s.id}">
                                <button class="dropdown-item" onclick="openSheet(${s.id})">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Sheet
                                </button>
                                <button class="dropdown-item" onclick="duplicateSheet(${s.id})">
                                    <i class="fa-solid fa-copy"></i> Duplicate
                                </button>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item danger" onclick="confirmDelete(${s.id}, '${escapeHtml(s.sheet_name)}')">
                                    <i class="fa-solid fa-trash-can"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="sheet-card-body">
                        <h3 title="${escapeHtml(s.sheet_name)}">${escapeHtml(s.sheet_name)}</h3>
                        <div class="sheet-card-dates">
                            <span><i class="fa-regular fa-calendar"></i> ${formatDate(s.created_at)}</span>
                            <span><i class="fa-regular fa-clock"></i> ${timeAgo(s.updated_at)}</span>
                        </div>
                        <div class="sheet-card-stats">
                            <div class="mini-stat">
                                <div class="mini-stat-value">${s.total_items || 0}</div>
                                <div class="mini-stat-label">Items</div>
                            </div>
                            <div class="mini-stat">
                                <div class="mini-stat-value">${parseFloat(s.total_system_qty || 0).toFixed(0)}</div>
                                <div class="mini-stat-label">System Qty</div>
                            </div>
                            <div class="mini-stat green-tint">
                                <div class="mini-stat-value">${s.total_scanned_qty || 0}</div>
                                <div class="mini-stat-label">Scanned</div>
                            </div>
                        </div>
                    </div>
                    <div class="sheet-card-footer">
                        <button class="btn btn-open btn-sm" onclick="openSheet(${s.id})">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Open
                        </button>
                        <button class="btn btn-outline btn-sm" onclick="duplicateSheet(${s.id})">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                        <button class="btn btn-danger-outline btn-sm" onclick="confirmDelete(${s.id}, '${escapeHtml(s.sheet_name)}')">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            `).join('');

            // List items
            list.innerHTML = sheets.map(s => `
                <div class="sheet-list-item" id="list_${s.id}">
                    <div class="sheet-list-icon"><i class="fa-solid fa-file-spreadsheet"></i></div>
                    <div class="sheet-list-info">
                        <h4 title="${escapeHtml(s.sheet_name)}">${escapeHtml(s.sheet_name)}</h4>
                        <p>Created ${formatDate(s.created_at)} &bull; Modified ${timeAgo(s.updated_at)}</p>
                    </div>
                    <div class="sheet-list-stats">
                        <div class="mini-stat">
                            <div class="mini-stat-value">${s.total_items || 0}</div>
                            <div class="mini-stat-label">Items</div>
                        </div>
                        <div class="mini-stat green-tint">
                            <div class="mini-stat-value">${s.total_scanned_qty || 0}</div>
                            <div class="mini-stat-label">Scanned</div>
                        </div>
                    </div>
                    <div class="sheet-list-actions">
                        <button class="btn btn-open btn-sm" onclick="openSheet(${s.id})">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Open
                        </button>
                        <button class="btn btn-danger-outline btn-sm" onclick="confirmDelete(${s.id}, '${escapeHtml(s.sheet_name)}')">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // ─── View Toggle ───
        function setView(view) {
            currentView = view;
            document.querySelectorAll('.view-toggle').forEach(b => b.classList.remove('active'));
            document.querySelector(`.view-toggle[data-view="${view}"]`).classList.add('active');

            const grid = document.getElementById('sheetsGrid');
            const list = document.getElementById('sheetsList');

            if (view === 'grid') {
                grid.classList.add('active-view');
                list.classList.remove('active-view');
            } else {
                grid.classList.remove('active-view');
                list.classList.add('active-view');
            }
        }

        // ─── Create New Sheet ───
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

        // ─── Open Sheet ───
        function openSheet(id) {
            window.location.href = 'stock_scan.php?sheet_id=' + id;
        }

        // ─── Duplicate Sheet ───
        function duplicateSheet(id) {
            const formData = new FormData();
            formData.append('action', 'duplicate_sheet');
            formData.append('sheet_id', id);

            fetch('stock_scan_records.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast('Sheet duplicated successfully!', 'success');
                        loadSheets();
                    } else {
                        showToast(data.message || 'Failed to duplicate', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error duplicating sheet', 'error');
                });
        }

        // ─── Delete Confirmation ───
        function confirmDelete(id, name) {
            deleteTargetId = id;
            document.getElementById('confirmMsg').innerHTML = `This will permanently delete <strong>"${name}"</strong> and all its scanned items. This action cannot be undone.`;
            document.getElementById('confirmOverlay').classList.add('show');
            document.getElementById('confirmDeleteBtn').onclick = function () {
                deleteSheet(id);
            };
        }

        function closeConfirm() {
            document.getElementById('confirmOverlay').classList.remove('show');
            deleteTargetId = null;
        }

        function deleteSheet(id) {
            closeConfirm();
            const formData = new FormData();
            formData.append('action', 'delete_sheet');
            formData.append('sheet_id', id);

            fetch('stock_scan_records.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast('Sheet deleted successfully', 'success');
                        loadSheets();
                    } else {
                        showToast(data.message || 'Failed to delete', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error deleting sheet', 'error');
                });
        }

        // ─── Dropdown Menu Toggle ───
        function toggleMenu(e, id) {
            e.stopPropagation();
            document.querySelectorAll('.dropdown-menu.show').forEach(m => {
                if (m.id !== `menu_${id}`) m.classList.remove('show');
            });
            document.getElementById(`menu_${id}`).classList.toggle('show');
        }

        // ─── Helpers ───
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function timeAgo(dateStr) {
            if (!dateStr) return '-';
            const now = new Date();
            const d = new Date(dateStr);
            const diff = Math.floor((now - d) / 1000);

            if (diff < 60) return 'Just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
            return formatDate(dateStr);
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `<i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(30px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>

</html>
