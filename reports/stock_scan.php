<?php
ob_start();
// stock_scan.php - Stock Scan Sheet for Srishringarr POS
// Connected to u464193275_srishringarr database & phppos_items table
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
    if (ob_get_length()) ob_clean(); // Purge any HTML output emitted by db_connection.php or headers
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // Fetch Item by item_number or barcode name from database phppos_items
    if ($action === 'get_item') {
        $item_number = trim($_POST['item_number'] ?? '');
        if ($item_number === '') {
            echo json_encode(['status' => 'error', 'message' => 'Please enter item_number or barcode']);
            exit;
        }

        $stmt = mysqli_prepare($con, "SELECT item_number, name, category, unit_price, quantity FROM phppos_items WHERE item_number = ? OR name = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $item_number, $item_number);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                echo json_encode(['status' => 'success', 'data' => $row]);
            } else {
                echo json_encode(['status' => 'error', 'message' => "Item with item_number/barcode '$item_number' not found in database!"]);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database query failed: ' . mysqli_error($con)]);
        }
        exit;
    }

    // Save entire sheet (auto-save)
    if ($action === 'save_sheet') {
        $sheet_id = intval($_POST['sheet_id'] ?? 0);
        $items_json = $_POST['items'] ?? '[]';
        $items = json_decode($items_json, true);

        if ($sheet_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid sheet ID']);
            exit;
        }

        // Delete existing items for this sheet
        $stmt = mysqli_prepare($con, "DELETE FROM stock_scan_sheet_items WHERE sheet_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $sheet_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Insert all current items
        $totalSystemQty = 0;
        $totalScannedQty = 0;
        if (is_array($items) && count($items) > 0) {
            $stmt = mysqli_prepare($con, "INSERT INTO stock_scan_sheet_items (sheet_id, item_number, name, category, unit_price, available_qty, scanned_qty, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($items as $idx => $item) {
                $iNum = $item['item_number'] ?? '';
                $iName = $item['name'] ?? '';
                $iCat = $item['category'] ?? '';
                $iPrice = floatval($item['unit_price'] ?? 0);
                $iAvail = floatval($item['available_qty'] ?? 0);
                $iScanned = intval($item['scanned_qty'] ?? 0);
                $iSort = $idx;
                mysqli_stmt_bind_param($stmt, "isssddii", $sheet_id, $iNum, $iName, $iCat, $iPrice, $iAvail, $iScanned, $iSort);
                mysqli_stmt_execute($stmt);
                $totalSystemQty += $iAvail;
                $totalScannedQty += $iScanned;
            }
            mysqli_stmt_close($stmt);
        }

        // Update sheet totals
        $totalItems = is_array($items) ? count($items) : 0;
        $stmt = mysqli_prepare($con, "UPDATE stock_scan_sheets SET total_items = ?, total_system_qty = ?, total_scanned_qty = ?, updated_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "idii", $totalItems, $totalSystemQty, $totalScannedQty, $sheet_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode(['status' => 'success', 'saved_items' => $totalItems]);
        exit;
    }

    // Load sheet data
    if ($action === 'load_sheet') {
        $sheet_id = intval($_POST['sheet_id'] ?? 0);
        if ($sheet_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid sheet ID']);
            exit;
        }

        // Get sheet metadata
        $stmt = mysqli_prepare($con, "SELECT * FROM stock_scan_sheets WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $sheet_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $sheet = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$sheet) {
            echo json_encode(['status' => 'error', 'message' => 'Sheet not found']);
            exit;
        }

        // Get sheet items
        $stmt = mysqli_prepare($con, "SELECT * FROM stock_scan_sheet_items WHERE sheet_id = ? ORDER BY sort_order ASC");
        mysqli_stmt_bind_param($stmt, "i", $sheet_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
        }
        mysqli_stmt_close($stmt);

        echo json_encode(['status' => 'success', 'sheet' => $sheet, 'items' => $items]);
        exit;
    }

    // Rename sheet
    if ($action === 'rename_sheet') {
        $sheet_id = intval($_POST['sheet_id'] ?? 0);
        $new_name = trim($_POST['sheet_name'] ?? '');
        if ($sheet_id <= 0 || $new_name === '') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            exit;
        }
        $stmt = mysqli_prepare($con, "UPDATE stock_scan_sheets SET sheet_name = ?, updated_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_name, $sheet_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // Create new sheet (from scanner page)
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
}

// ─── Sheet ID Handling for Page Rendering ───
$sheet_id = isset($_GET['sheet_id']) ? intval($_GET['sheet_id']) : 0;
$sheet_name = 'Untitled Scan Sheet';

if ($sheet_id > 0) {
    $stmt = mysqli_prepare($con, "SELECT id, sheet_name FROM stock_scan_sheets WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $sheet_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $sheet_name = $row['sheet_name'];
    } else {
        $sheet_id = 0;
    }
    mysqli_stmt_close($stmt);
}

if ($sheet_id === 0) {
    // Create new sheet and redirect
    $default_name = 'Scan Sheet - ' . date('d M Y, h:i A');
    $stmt = mysqli_prepare($con, "INSERT INTO stock_scan_sheets (sheet_name) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $default_name);
    mysqli_stmt_execute($stmt);
    $sheet_id = mysqli_insert_id($con);
    mysqli_stmt_close($stmt);
    header("Location: stock_scan.php?sheet_id=" . $sheet_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Scan Sheet - Srishringarr POS</title>

    <!-- Font Awesome & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- SheetJS for Excel Export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <style>
        :root {
            --sheet-green: #0f9d58;
            --sheet-dark-green: #0b8043;
            --sheet-border: #d0d7de;
            --sheet-header-bg: #f6f8fa;
            --sheet-row-hover: #f2f7fe;
            --text-dark: #1f2328;
            --text-muted: #656d76;
            --accent-blue: #0969da;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f6f8fa;
            color: var(--text-dark);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Top Google Sheet Header Bar */
        .header-bar {
            background-color: #ffffff;
            border-bottom: 1px solid var(--sheet-border);
            padding: 10px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .brand-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sheet-icon {
            background-color: var(--sheet-green);
            color: white;
            padding: 8px 10px;
            border-radius: 6px;
            font-size: 18px;
        }

        .title-container h1 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .title-container p {
            font-size: 12px;
            color: var(--text-muted);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            border: 1px solid #d0d7de;
            background: white;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn:hover {
            background-color: #f3f4f6;
            border-color: #b1b8c0;
        }

        .btn-blue {
            background-color: var(--accent-blue);
            color: white;
            border-color: var(--accent-blue);
        }

        .btn-blue:hover {
            background-color: #0856b6;
        }

        /* Toolbar & Barcode Scanner Bar */
        .toolbar {
            background-color: #ffffff;
            border-bottom: 1px solid var(--sheet-border);
            padding: 8px 18px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .scanner-box {
            display: flex;
            align-items: center;
            background: #e6f4ea;
            border: 2px solid var(--sheet-green);
            border-radius: 8px;
            padding: 6px 12px;
            width: 380px;
            transition: all 0.2s ease;
        }

        .scanner-box:focus-within {
            box-shadow: 0 0 0 3px rgba(15, 157, 88, 0.25);
        }

        .scanner-box i {
            color: var(--sheet-green);
            font-size: 18px;
            margin-right: 10px;
        }

        .scanner-input {
            border: none;
            outline: none;
            background: transparent;
            font-family: 'JetBrains Mono', monospace;
            font-size: 15px;
            font-weight: 700;
            width: 100%;
            color: var(--text-dark);
        }

        .scanner-shortcut-tag {
            font-size: 10px;
            background: #ceead6;
            color: #0d652d;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: #f6f8fa;
            border: 1px solid #d0d7de;
            border-radius: 6px;
            padding: 4px 10px;
            width: 260px;
        }

        .search-box i {
            color: var(--text-muted);
            margin-right: 8px;
            font-size: 13px;
        }

        .search-input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 13px;
            width: 100%;
        }

        .stats-summary {
            margin-left: auto;
            display: flex;
            gap: 12px;
            font-size: 12px;
        }

        .stat-badge {
            background: #f6f8fa;
            border: 1px solid #e1e4e8;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 500;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .stat-badge strong {
            color: var(--text-dark);
            font-weight: 700;
        }

        /* Sheet Table Container */
        .sheet-container {
            flex: 1;
            overflow: auto;
            background-color: #ffffff;
            position: relative;
        }

        table.sheet-grid {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
        }

        /* Excel Header Styling */
        table.sheet-grid tr.col-letter-row th {
            background-color: #eaeef2;
            color: #484f58;
            font-weight: 700;
            font-size: 11px;
            text-align: center;
            padding: 4px;
            border-right: 1px solid var(--sheet-border);
            border-bottom: 1px solid #d8dee4;
            position: sticky;
            top: 0;
            z-index: 14;
            user-select: none;
        }

        table.sheet-grid tr.col-title-row th {
            background-color: var(--sheet-header-bg);
            color: var(--text-dark);
            font-weight: 600;
            font-size: 12px;
            padding: 8px 12px;
            border-right: 1px solid var(--sheet-border);
            border-bottom: 2px solid #b1b8c0;
            position: sticky;
            top: 25px;
            z-index: 13;
            user-select: none;
            text-align: left;
        }

        /* Column Filter Row */
        table.sheet-grid tr.col-filter-row th {
            background-color: #fff8e1;
            padding: 4px 6px;
            border-right: 1px solid var(--sheet-border);
            border-bottom: 2px solid #e0c860;
            position: sticky;
            top: 57px;
            z-index: 12;
        }

        .col-filter-input {
            width: 100%;
            padding: 4px 8px;
            font-size: 12px;
            font-family: 'Inter', sans-serif;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #ffffff;
            color: var(--text-dark);
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .col-filter-input::placeholder {
            color: #b0a060;
            font-style: italic;
        }

        .col-filter-input:focus {
            border-color: #d4a017;
            box-shadow: 0 0 0 2px rgba(212, 160, 23, 0.2);
        }

        .col-filter-input:not(:placeholder-shown) {
            background-color: #fffde7;
            border-color: #d4a017;
        }

        table.sheet-grid th.col-row-no {
            width: 50px;
            text-align: center !important;
            background: #e1e5ea !important;
            left: 0;
            z-index: 15 !important;
        }

        table.sheet-grid td {
            padding: 6px 12px;
            border-right: 1px solid var(--sheet-border);
            border-bottom: 1px solid var(--sheet-border);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 34px;
        }

        table.sheet-grid td.cell-row-no {
            background-color: var(--sheet-header-bg);
            color: var(--text-muted);
            text-align: center;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            font-weight: 600;
            position: sticky;
            left: 0;
            z-index: 5;
            border-right: 2px solid #d8dee4;
        }

        table.sheet-grid tr:hover td {
            background-color: var(--sheet-row-hover);
        }

        table.sheet-grid tr.row-just-scanned td {
            animation: pulseGreen 1s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes pulseGreen {
            0% { background-color: #a8dab5; }
            100% { background-color: #e6f4ea; }
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .scanned-input {
            width: 85px;
            padding: 4px 8px;
            border: 1px solid #0969da;
            background-color: #f0f7ff;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            text-align: center;
            font-size: 14px;
            color: #0969da;
        }

        .scanned-input:focus {
            outline: none;
            border-color: var(--sheet-green);
            background-color: #ffffff;
            box-shadow: 0 0 0 2px rgba(15, 157, 88, 0.3);
            color: var(--text-dark);
        }

        /* Delete Button */
        .btn-delete {
            background: transparent;
            border: none;
            color: #cf222e;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.15s ease;
        }

        .btn-delete:hover {
            background-color: #ffebe9;
            color: #a40e26;
        }

        .empty-placeholder {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }

        .empty-placeholder i {
            font-size: 54px;
            color: #d0d7de;
            margin-bottom: 14px;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background: #24292f;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideUp 0.2s ease-out;
        }

        .toast-success { border-left: 4px solid var(--sheet-green); }
        .toast-error { border-left: 4px solid #cf222e; }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Save Status Indicator */
        .save-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 12px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .save-status.saving {
            color: #b45309;
            background: #fef3c7;
        }

        .save-status.saved {
            color: #047857;
            background: #d1fae5;
        }

        .save-status.error {
            color: #dc2626;
            background: #fee2e2;
        }

        .save-spinner {
            width: 12px;
            height: 12px;
            border: 2px solid #fbbf24;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Editable Sheet Name */
        .sheet-name-input {
            border: 1px solid transparent;
            background: transparent;
            font-size: 18px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            padding: 2px 8px;
            border-radius: 4px;
            outline: none;
            max-width: 360px;
            width: 100%;
            transition: all 0.15s ease;
        }

        .sheet-name-input:hover {
            border-color: #d0d7de;
            background: #f6f8fa;
        }

        .sheet-name-input:focus {
            border-color: #0969da;
            background: #ffffff;
            box-shadow: 0 0 0 2px rgba(9, 105, 218, 0.2);
        }

        .btn-back {
            background: transparent;
            border: 1px solid #d0d7de;
            color: var(--text-muted);
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
        }

        .btn-back:hover {
            background: #f3f4f6;
            color: var(--text-dark);
        }

        /* Print Media Styles */
        @media print {
            .header-bar, .toolbar, .toast-container, .btn-action {
                display: none !important;
            }
            body { background: white; height: auto; overflow: visible; }
            .sheet-container { overflow: visible; }
            table.sheet-grid th, table.sheet-grid td { border: 1px solid #000 !important; font-size: 10pt; }
        }
    </style>
</head>

<body>

    <!-- Top Header Bar -->
    <div class="header-bar">
        <div class="brand-section">
            <button class="btn-back" onclick="window.location.href='stock_scan_records.php'" title="Back to Records">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="sheet-icon"><i class="fa-solid fa-file-excel"></i></div>
            <div class="title-container">
                <input type="text" class="sheet-name-input" id="sheetNameInput" 
                       value="<?php echo htmlspecialchars($sheet_name); ?>" 
                       placeholder="Untitled Scan Sheet"
                       onblur="renameSheet()" 
                       onkeydown="if(event.key==='Enter'){this.blur();}">
                <p>
                    <span class="save-status saved" id="saveStatus">
                        <i class="fa-solid fa-cloud-check"></i> Saved
                    </span>
                    &bull; Sheet #<?php echo $sheet_id; ?>
                </p>
            </div>
        </div>
        <div class="header-actions">
            <button class="btn btn-blue" onclick="exportToExcel()">
                <i class="fa-solid fa-file-export"></i> Export Excel
            </button>
            <button class="btn" onclick="exportToCSV()">
                <i class="fa-solid fa-file-csv"></i> CSV
            </button>
            <button class="btn" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Print
            </button>
            <button class="btn" onclick="clearSheet()">
                <i class="fa-solid fa-rotate-left"></i> Clear Sheet
            </button>
        </div>
    </div>

    <!-- Toolbar with Barcode Reader Input & Search -->
    <div class="toolbar">
        <div class="scanner-box">
            <i class="fa-solid fa-barcode"></i>
            <input type="text" id="barcode_input" class="scanner-input" placeholder="Scan or enter item_number..." autofocus autocomplete="off">
            <span class="scanner-shortcut-tag">Ctrl+B</span>
        </div>

        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="search_input" class="search-input" placeholder="Filter sheet SKU, barcode...">
        </div>

        <div class="stats-summary">
            <div class="stat-badge">Scanned Rows: <strong id="stat_total_items">0</strong></div>
            <div class="stat-badge">System Qty: <strong id="stat_system_qty">0</strong></div>
            <div class="stat-badge">Scanned Qty: <strong id="stat_scanned_qty" style="color: var(--sheet-green);">0</strong></div>
            <div class="stat-badge">Available - Scanned: <strong id="stat_diff_qty" style="color: #d97706;">0</strong></div>
        </div>
    </div>

    <!-- Spreadsheet Data Container -->
    <div class="sheet-container">
        <table class="sheet-grid" id="stockTable">
            <thead>
                <!-- Row 1: Excel Column Letters -->
                <tr class="col-letter-row">
                    <th class="col-row-no">#</th>
                    <th>A</th>
                    <th>B</th>
                    <th>C</th>
                    <th>D</th>
                    <th>E</th>
                    <th>F</th>
                    <th>G</th>
                    <th class="btn-action"></th>
                </tr>
                <!-- Row 2: Table Column Titles -->
                <tr class="col-title-row">
                    <th class="col-row-no"></th>
                    <th>SKU / Item Code</th>
                    <th>Barcode #</th>
                    <th>Category</th>
                    <th style="text-align: right;">Price (₹)</th>
                    <th style="text-align: right;">Available Qty</th>
                    <th style="text-align: center;">Scanned Qty</th>
                    <th style="text-align: right;">Available - Scanned</th>
                    <th class="btn-action" style="text-align: center;">Action</th>
                </tr>
                <!-- Row 3: Column Filters -->
                <tr class="col-filter-row">
                    <th class="col-row-no" style="background: #fff8e1;">
                        <i class="fa-solid fa-filter" style="color: #c9a825; font-size: 11px;" title="Column filters"></i>
                    </th>
                    <th><input type="text" class="col-filter-input" data-col="sku" placeholder="Filter SKU..." autocomplete="off"></th>
                    <th><input type="text" class="col-filter-input" data-col="barcode" placeholder="Filter Barcode..." autocomplete="off"></th>
                    <th><input type="text" class="col-filter-input" data-col="category" placeholder="Filter Category..." autocomplete="off"></th>
                    <th><input type="text" class="col-filter-input" data-col="price" placeholder="Filter Price..." autocomplete="off"></th>
                    <th><input type="text" class="col-filter-input" data-col="availqty" placeholder="Filter Qty..." autocomplete="off"></th>
                    <th><input type="text" class="col-filter-input" data-col="scannedqty" placeholder="Filter Scanned..." autocomplete="off"></th>
                    <th><input type="text" class="col-filter-input" data-col="diffqty" placeholder="Filter Diff..." autocomplete="off"></th>
                    <th class="btn-action" style="text-align: center;">
                        <button class="btn-delete" onclick="clearAllColumnFilters()" title="Clear all filters" style="color: #c9a825;">
                            <i class="fa-solid fa-filter-circle-xmark"></i>
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <tr id="emptyRow">
                    <td colspan="9">
                        <div class="empty-placeholder">
                            <i class="fa-solid fa-barcode"></i>
                            <h3>Ready for Barcode Scanning</h3>
                            <p style="font-size: 13px; margin-top: 4px;">
                                Scan or enter <code>phppos_items.item_number</code> or SKU barcode in the green box above to fetch and add item to this audit sheet.
                            </p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        const currentSheetId = <?php echo $sheet_id; ?>;
        let rowCounter = 0;
        let scannedMap = {}; // Map of clean item_number/sku -> rowId
        let autoSaveTimer = null;
        let isSaving = false;
        let isLoadingSheet = false;

        // Audio Context for Beep Feedback
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

        function playBeep(freq = 880, type = 'sine', duration = 0.08) {
            try {
                if (audioCtx.state === 'suspended') audioCtx.resume();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = type;
                osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
                gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + duration);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + duration);
            } catch (e) {}
        }

        function playErrorBeep() {
            playBeep(300, 'sawtooth', 0.15);
            setTimeout(() => playBeep(220, 'sawtooth', 0.15), 100);
        }

        // Shortcuts: Ctrl+B to focus scanner input
        document.addEventListener('keydown', function (e) {
            if (e.ctrlKey && (e.key === 'b' || e.key === 'B')) {
                e.preventDefault();
                const scannerInput = document.getElementById('barcode_input');
                scannerInput.focus();
                scannerInput.select();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const scannerInput = document.getElementById('barcode_input');
            const searchInput = document.getElementById('search_input');

            // Barcode Machine Reader Handler
            scannerInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const code = this.value.trim();
                    if (code !== '') {
                        handleItemNumberInput(code);
                        this.value = '';
                    }
                }
            });

            // Live Search filter
            searchInput.addEventListener('input', function () {
                applyAllFilters();
            });

            // Column-wise filter inputs
            document.querySelectorAll('.col-filter-input').forEach(input => {
                input.addEventListener('input', function () {
                    applyAllFilters();
                });
            });

            // Load saved sheet data
            if (currentSheetId > 0) {
                loadSheetFromServer();
            }
        });

        // Handle item_number scan / input
        function handleItemNumberInput(code) {
            const cleanCode = code.toLowerCase();

            // 1. Check if item is already present in table
            if (scannedMap[cleanCode]) {
                const rowId = scannedMap[cleanCode];
                incrementScan(rowId);
                const row = document.getElementById(`row_${rowId}`);
                if (row) {
                    playBeep(880, 'sine', 0.1);
                    row.classList.remove('row-just-scanned');
                    void row.offsetWidth;
                    row.classList.add('row-just-scanned');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    showToast(`Scanned again: <strong>${row.cells[1].innerText}</strong> (Item #${row.cells[2].innerText})`, 'success');
                }
                document.getElementById('barcode_input').focus();
                return;
            }

            // 2. Fetch item from database phppos_items via item_number or name
            fetchItemFromDB(code);
        }

        // Fetch item details from phppos_items
        function fetchItemFromDB(itemNumber) {
            const formData = new FormData();
            formData.append('action', 'get_item');
            formData.append('item_number', itemNumber);

            fetch('stock_scan.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.text())
                .then(text => {
                    let data;
                    try {
                        const jsonStart = text.indexOf('{');
                        const jsonEnd = text.lastIndexOf('}');
                        if (jsonStart !== -1 && jsonEnd !== -1) {
                            data = JSON.parse(text.substring(jsonStart, jsonEnd + 1));
                        } else {
                            data = JSON.parse(text);
                        }
                    } catch (e) {
                        throw new Error('Invalid JSON response: ' + text);
                    }

                    if (data.status === 'success' && data.data) {
                        addItemRowToSheet(data.data);
                        playBeep(880, 'sine', 0.1);
                        showToast(`Loaded: <strong>${data.data.name}</strong> (Available Qty: ${data.data.quantity})`, 'success');
                    } else {
                        playErrorBeep();
                        showToast(data.message || `item_number '${itemNumber}' not found!`, 'error');
                    }
                })
                .catch(err => {
                    playErrorBeep();
                    showToast('Error querying item from database', 'error');
                    console.error(err);
                })
                .finally(() => {
                    document.getElementById('barcode_input').focus();
                });
        }

        // Difference badge renderer
        function renderDiffBadge(diff) {
            const num = parseFloat(diff.toFixed(2));
            if (num === 0) {
                return `<span style="color: #059669; font-weight: 600;">0</span>`;
            } else if (num > 0) {
                return `<span style="color: #d97706; font-weight: 600;">+${num}</span>`;
            } else {
                return `<span style="color: #dc2626; font-weight: 600;">${num}</span>`;
            }
        }

        // Update row difference cell
        function updateRowDiff(row) {
            const availQty = parseFloat(row.getAttribute('data-qty') || '0');
            const scannedQty = parseInt(row.getAttribute('data-scanned') || '0');
            const diff = parseFloat((availQty - scannedQty).toFixed(2));
            const diffCell = row.querySelector('.col-diff');
            if (diffCell) {
                diffCell.innerHTML = renderDiffBadge(diff);
            }
        }

        // Dynamically append new item row to sheet
        function addItemRowToSheet(item, initialScanned = 1) {
            const emptyRow = document.getElementById('emptyRow');
            if (emptyRow) emptyRow.remove();

            rowCounter++;
            const rowId = rowCounter;

            const sku = item.name || '';
            const itemNumber = item.item_number || '';
            const category = item.category || '';
            const price = parseFloat(item.unit_price || 0).toFixed(2);
            const availQty = parseFloat(item.quantity !== undefined ? item.quantity : (item.available_qty || 0));
            const diffQty = parseFloat((availQty - initialScanned).toFixed(2));

            const cleanSku = sku.toLowerCase();
            const cleanItemNum = itemNumber.toLowerCase();

            // Map identifiers to rowId
            if (cleanSku) scannedMap[cleanSku] = rowId;
            if (cleanItemNum) scannedMap[cleanItemNum] = rowId;

            const tr = document.createElement('tr');
            tr.id = `row_${rowId}`;
            tr.setAttribute('data-sku', cleanSku);
            tr.setAttribute('data-barcode', cleanItemNum);
            tr.setAttribute('data-category', category.toLowerCase());
            tr.setAttribute('data-qty', availQty);
            tr.setAttribute('data-scanned', initialScanned);

            tr.innerHTML = `
                <td class="cell-row-no">${rowId}</td>
                <td class="mono" style="font-weight: 600; color: #0969da;">${escapeHtml(sku)}</td>
                <td class="mono" style="font-weight: 600;">${escapeHtml(itemNumber)}</td>
                <td>${escapeHtml(category)}</td>
                <td class="mono" style="text-align: right;">${price}</td>
                <td class="mono col-qty" style="text-align: right; font-weight: 600;">${availQty}</td>
                <td class="mono" style="text-align: center;">
                    <input type="number" 
                           class="scanned-input" 
                           value="${initialScanned}" 
                           min="0" 
                           onchange="updateRowScanned(${rowId}, this.value)"
                           onfocus="this.select()">
                </td>
                <td class="mono col-diff" style="text-align: right; font-weight: 600;">${renderDiffBadge(diffQty)}</td>
                <td class="btn-action" style="text-align: center;">
                    <button class="btn-delete" onclick="deleteRow(${rowId})" title="Delete row">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </td>
            `;

            document.getElementById('tableBody').appendChild(tr);

            tr.classList.add('row-just-scanned');
            tr.scrollIntoView({ behavior: 'smooth', block: 'center' });

            renumberRows();
            calculateTotals();
            triggerAutoSave();
        }

        // Increment scan count (+1)
        function incrementScan(rowId) {
            const row = document.getElementById(`row_${rowId}`);
            if (!row) return;

            let currentScanned = parseInt(row.getAttribute('data-scanned') || '0');
            currentScanned++;
            row.setAttribute('data-scanned', currentScanned);

            const input = row.querySelector('.scanned-input');
            if (input) input.value = currentScanned;

            updateRowDiff(row);
            calculateTotals();
            triggerAutoSave();
        }

        // Manual scan edit
        function updateRowScanned(rowId, value) {
            const row = document.getElementById(`row_${rowId}`);
            if (!row) return;

            let scannedVal = parseInt(value) || 0;
            if (scannedVal < 0) scannedVal = 0;

            row.setAttribute('data-scanned', scannedVal);
            updateRowDiff(row);
            calculateTotals();
            triggerAutoSave();
        }

        // Delete Row from Sheet
        function deleteRow(rowId) {
            const row = document.getElementById(`row_${rowId}`);
            if (!row) return;

            const sku = row.getAttribute('data-sku');
            const barcode = row.getAttribute('data-barcode');

            if (sku) delete scannedMap[sku];
            if (barcode) delete scannedMap[barcode];

            row.remove();

            renumberRows();
            calculateTotals();

            showToast('Row removed from sheet', 'success');

            const remaining = document.querySelectorAll('#tableBody tr:not(#emptyRow)');
            if (remaining.length === 0) {
                clearSheet(false);
            } else {
                triggerAutoSave();
            }

            document.getElementById('barcode_input').focus();
        }

        // Renumber Row Indices (1, 2, 3...)
        function renumberRows() {
            const rows = document.querySelectorAll('#tableBody tr:not(#emptyRow)');
            rows.forEach((r, idx) => {
                const cellNo = r.querySelector('.cell-row-no');
                if (cellNo) cellNo.innerText = idx + 1;
            });
        }

        // Calculate Sheet Totals
        function calculateTotals() {
            const rows = document.querySelectorAll('#tableBody tr:not(#emptyRow)');
            let totalSystemQty = 0;
            let totalScannedQty = 0;

            rows.forEach(row => {
                const sysQty = parseFloat(row.getAttribute('data-qty') || '0');
                const scanQty = parseInt(row.getAttribute('data-scanned') || '0');

                totalSystemQty += sysQty;
                totalScannedQty += scanQty;
            });

            const totalDiff = parseFloat((totalSystemQty - totalScannedQty).toFixed(2));

            document.getElementById('stat_total_items').innerText = rows.length;
            document.getElementById('stat_system_qty').innerText = totalSystemQty;
            document.getElementById('stat_scanned_qty').innerText = totalScannedQty;
            const diffEl = document.getElementById('stat_diff_qty');
            if (diffEl) {
                diffEl.innerText = (totalDiff > 0 ? '+' : '') + totalDiff;
                if (totalDiff === 0) {
                    diffEl.style.color = '#059669';
                } else if (totalDiff > 0) {
                    diffEl.style.color = '#d97706';
                } else {
                    diffEl.style.color = '#dc2626';
                }
            }
        }

        // Combined Search + Column Filter
        function applyAllFilters() {
            const searchTerm = document.getElementById('search_input').value.toLowerCase().trim();

            // Gather column filter values
            const colFilters = {};
            document.querySelectorAll('.col-filter-input').forEach(input => {
                const col = input.getAttribute('data-col');
                const val = input.value.toLowerCase().trim();
                if (val) colFilters[col] = val;
            });

            const rows = document.querySelectorAll('#tableBody tr:not(#emptyRow)');

            rows.forEach(row => {
                const sku = (row.getAttribute('data-sku') || '');
                const barcode = (row.getAttribute('data-barcode') || '');
                const category = (row.getAttribute('data-category') || '');
                const price = (row.cells[4] ? row.cells[4].innerText.trim().toLowerCase() : '');
                const availQty = (row.cells[5] ? row.cells[5].innerText.trim().toLowerCase() : '');
                const scannedInput = row.querySelector('.scanned-input');
                const scannedQty = scannedInput ? scannedInput.value.trim().toLowerCase() : '';
                const diffCell = row.querySelector('.col-diff');
                const diffQty = diffCell ? diffCell.innerText.trim().toLowerCase() : '';

                // Global search check
                let matchesGlobal = true;
                if (searchTerm) {
                    matchesGlobal = sku.includes(searchTerm) || barcode.includes(searchTerm) || category.includes(searchTerm);
                }

                // Column filter checks (AND logic — all active filters must match)
                let matchesColumns = true;
                if (colFilters.sku && !sku.includes(colFilters.sku)) matchesColumns = false;
                if (colFilters.barcode && !barcode.includes(colFilters.barcode)) matchesColumns = false;
                if (colFilters.category && !category.includes(colFilters.category)) matchesColumns = false;
                if (colFilters.price && !price.includes(colFilters.price)) matchesColumns = false;
                if (colFilters.availqty && !availQty.includes(colFilters.availqty)) matchesColumns = false;
                if (colFilters.scannedqty && !scannedQty.includes(colFilters.scannedqty)) matchesColumns = false;
                if (colFilters.diffqty && !diffQty.includes(colFilters.diffqty)) matchesColumns = false;

                row.style.display = (matchesGlobal && matchesColumns) ? '' : 'none';
            });
        }

        // Clear all column filters
        function clearAllColumnFilters() {
            document.querySelectorAll('.col-filter-input').forEach(input => {
                input.value = '';
            });
            applyAllFilters();
            showToast('Column filters cleared', 'success');
        }

        // Legacy wrapper for backward compatibility
        function applySearchFilter() {
            applyAllFilters();
        }

        // Clear Sheet
        function clearSheet(confirmReq = true) {
            if (confirmReq && !confirm('Are you sure you want to clear the current scan sheet?')) {
                return;
            }
            document.getElementById('tableBody').innerHTML = `
                <tr id="emptyRow">
                    <td colspan="9">
                        <div class="empty-placeholder">
                            <i class="fa-solid fa-barcode"></i>
                            <h3>Ready for Barcode Scanning</h3>
                            <p style="font-size: 13px; margin-top: 4px;">
                                Scan or enter <code>phppos_items.item_number</code> or SKU barcode in the green box above to fetch and add item to this audit sheet.
                            </p>
                        </div>
                    </td>
                </tr>
            `;
            rowCounter = 0;
            scannedMap = {};
            calculateTotals();
            triggerAutoSave();
            showToast('Sheet cleared.', 'success');
        }

        // Export to Excel using SheetJS
        function exportToExcel() {
            const rows = document.querySelectorAll('#stockTable tbody tr:not(#emptyRow)');
            if (rows.length === 0) {
                showToast('No scanned data to export.', 'error');
                return;
            }

            const data = [
                ['#', 'SKU / Item Code', 'Barcode #', 'Category', 'Price (₹)', 'Available Qty', 'Scanned Qty', 'Available - Scanned']
            ];

            rows.forEach((row, idx) => {
                if (row.style.display === 'none') return;
                const rowNo = idx + 1;
                const sku = row.cells[1].innerText.trim();
                const itemNumber = row.cells[2].innerText.trim();
                const category = row.cells[3].innerText.trim();
                const price = parseFloat(row.cells[4].innerText.trim() || 0);
                const availQty = parseFloat(row.cells[5].innerText.trim() || 0);

                const input = row.querySelector('.scanned-input');
                const scannedQty = input ? parseInt(input.value || 0) : 0;
                const diffQty = parseFloat((availQty - scannedQty).toFixed(2));

                data.push([rowNo, sku, itemNumber, category, price, availQty, scannedQty, diffQty]);
            });

            const ws = XLSX.utils.aoa_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Stock Audit");

            XLSX.writeFile(wb, `Stock_Scan_Audit_${new Date().toISOString().slice(0, 10)}.xlsx`);
            showToast('Excel report downloaded with Scanned Qty!', 'success');
        }

        // Export to CSV
        function exportToCSV() {
            const rows = document.querySelectorAll('#stockTable tbody tr:not(#emptyRow)');
            if (rows.length === 0) {
                showToast('No scanned data to export.', 'error');
                return;
            }

            let csvLines = [
                '"#","SKU / Item Code","Barcode #","Category","Price (₹)","Available Qty","Scanned Qty","Available - Scanned"'
            ];

            rows.forEach((row, idx) => {
                if (row.style.display === 'none') return;
                const rowNo = idx + 1;
                const sku = row.cells[1].innerText.trim().replace(/"/g, '""');
                const itemNumber = row.cells[2].innerText.trim().replace(/"/g, '""');
                const category = row.cells[3].innerText.trim().replace(/"/g, '""');
                const price = row.cells[4].innerText.trim();
                const availQty = parseFloat(row.cells[5].innerText.trim() || 0);

                const input = row.querySelector('.scanned-input');
                const scannedQty = input ? parseInt(input.value || 0) : 0;
                const diffQty = parseFloat((availQty - scannedQty).toFixed(2));

                csvLines.push(`"${rowNo}","${sku}","${itemNumber}","${category}","${price}","${availQty}","${scannedQty}","${diffQty}"`);
            });

            const blob = new Blob([csvLines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.setAttribute('download', `Stock_Scan_Report_${new Date().toISOString().slice(0, 10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showToast('CSV report downloaded.', 'success');
        }

        // Toast Helper
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `<i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i> ${message}`;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function escapeHtml(text) {
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // ═══════════════════════════════════════════
        // SHEET PERSISTENCE (Auto-Save / Load / Rename)
        // ═══════════════════════════════════════════

        // Debounced auto-save trigger
        function triggerAutoSave() {
            if (isLoadingSheet) return; // Don't save while loading
            if (autoSaveTimer) clearTimeout(autoSaveTimer);
            updateSaveStatus('saving');
            autoSaveTimer = setTimeout(() => {
                saveSheetToServer();
            }, 800);
        }

        // Collect all row data and POST to server
        function saveSheetToServer() {
            if (isSaving) return;
            isSaving = true;

            const rows = document.querySelectorAll('#tableBody tr:not(#emptyRow)');
            const items = [];

            rows.forEach((row, idx) => {
                const scannedInput = row.querySelector('.scanned-input');
                items.push({
                    item_number: row.getAttribute('data-barcode') || '',
                    name: row.getAttribute('data-sku') || '',
                    category: row.getAttribute('data-category') || '',
                    unit_price: parseFloat(row.cells[4] ? row.cells[4].innerText.trim() : 0),
                    available_qty: parseFloat(row.getAttribute('data-qty') || 0),
                    scanned_qty: scannedInput ? parseInt(scannedInput.value || 0) : 0
                });
            });

            const formData = new FormData();
            formData.append('action', 'save_sheet');
            formData.append('sheet_id', currentSheetId);
            formData.append('items', JSON.stringify(items));

            fetch('stock_scan.php?sheet_id=' + currentSheetId, {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(text => {
                let data;
                try {
                    const jsonStart = text.indexOf('{');
                    const jsonEnd = text.lastIndexOf('}');
                    if (jsonStart !== -1 && jsonEnd !== -1) {
                        data = JSON.parse(text.substring(jsonStart, jsonEnd + 1));
                    } else {
                        data = JSON.parse(text);
                    }
                } catch (e) {
                    throw new Error('Invalid JSON: ' + text.substring(0, 200));
                }

                if (data.status === 'success') {
                    updateSaveStatus('saved');
                } else {
                    updateSaveStatus('error');
                    console.error('Save failed:', data.message);
                }
            })
            .catch(err => {
                updateSaveStatus('error');
                console.error('Auto-save error:', err);
            })
            .finally(() => {
                isSaving = false;
            });
        }

        // Load sheet items from server
        function loadSheetFromServer() {
            isLoadingSheet = true;
            const formData = new FormData();
            formData.append('action', 'load_sheet');
            formData.append('sheet_id', currentSheetId);

            fetch('stock_scan.php?sheet_id=' + currentSheetId, {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(text => {
                let data;
                try {
                    const jsonStart = text.indexOf('{');
                    const jsonEnd = text.lastIndexOf('}');
                    if (jsonStart !== -1 && jsonEnd !== -1) {
                        data = JSON.parse(text.substring(jsonStart, jsonEnd + 1));
                    } else {
                        data = JSON.parse(text);
                    }
                } catch (e) {
                    throw new Error('Invalid JSON: ' + text.substring(0, 200));
                }

                if (data.status === 'success' && data.items && data.items.length > 0) {
                    // Remove empty row placeholder
                    const emptyRow = document.getElementById('emptyRow');
                    if (emptyRow) emptyRow.remove();

                    // Load each item
                    data.items.forEach(item => {
                        const itemData = {
                            name: item.name || '',
                            item_number: item.item_number || '',
                            category: item.category || '',
                            unit_price: item.unit_price || 0,
                            quantity: item.available_qty || 0
                        };
                        const scannedQty = parseInt(item.scanned_qty || 1);
                        addItemRowToSheet(itemData, scannedQty);
                    });

                    updateSaveStatus('saved');
                }
            })
            .catch(err => {
                console.error('Load error:', err);
                showToast('Failed to load saved sheet data', 'error');
            })
            .finally(() => {
                isLoadingSheet = false;
                document.getElementById('barcode_input').focus();
            });
        }

        // Rename sheet
        function renameSheet() {
            const nameInput = document.getElementById('sheetNameInput');
            const newName = nameInput.value.trim();
            if (!newName) {
                nameInput.value = 'Untitled Scan Sheet';
            }

            const formData = new FormData();
            formData.append('action', 'rename_sheet');
            formData.append('sheet_id', currentSheetId);
            formData.append('sheet_name', nameInput.value.trim());

            updateSaveStatus('saving');

            fetch('stock_scan.php?sheet_id=' + currentSheetId, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    updateSaveStatus('saved');
                    document.title = nameInput.value.trim() + ' - Stock Scan Sheet';
                } else {
                    updateSaveStatus('error');
                }
            })
            .catch(() => {
                updateSaveStatus('error');
            });
        }

        // Update save status indicator
        function updateSaveStatus(status) {
            const el = document.getElementById('saveStatus');
            if (!el) return;

            el.classList.remove('saving', 'saved', 'error');
            el.classList.add(status);

            switch(status) {
                case 'saving':
                    el.innerHTML = '<div class="save-spinner"></div> Saving...';
                    break;
                case 'saved':
                    el.innerHTML = '<i class="fa-solid fa-cloud-check"></i> All changes saved';
                    break;
                case 'error':
                    el.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Save failed';
                    break;
            }
        }

        // Set page title from sheet name on load
        document.title = document.getElementById('sheetNameInput').value.trim() + ' - Stock Scan Sheet';
    </script>
</body>

</html>