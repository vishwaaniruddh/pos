<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set("display_errors", 0);
include('../db_connection.php');
$con = OpenSrishringarrCon();

// ---------------------------------------------------------------------
// 1. Filter & Pagination Parameters
// ---------------------------------------------------------------------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$city_filter = isset($_GET['city']) ? trim($_GET['city']) : '';
$phone_filter = isset($_GET['phone_status']) ? trim($_GET['phone_status']) : 'all';
$limit_param = isset($_GET['limit']) ? trim($_GET['limit']) : '25';
$limit = ($limit_param === 'all') ? 999999 : (int)$limit_param;
if ($limit <= 0) $limit = 25;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// ---------------------------------------------------------------------
// 2. Base Query & Filter Construction
// ---------------------------------------------------------------------
$where_clauses = ["(p.first_name != '' OR s.company_name != '')"];
$params = [];
$types = "";

if ($search !== '') {
    $search_like = "%" . $search . "%";
    $where_clauses[] = "(
        s.company_name LIKE ? 
        OR s.account_number LIKE ? 
        OR p.first_name LIKE ? 
        OR p.last_name LIKE ? 
        OR p.phone_number LIKE ? 
        OR p.email LIKE ? 
        OR p.city LIKE ? 
        OR p.address_1 LIKE ?
    )";
    $types .= "ssssssss";
    for ($k = 0; $k < 8; $k++) {
        $params[] = $search_like;
    }
}

if ($city_filter !== '') {
    $where_clauses[] = "p.city = ?";
    $types .= "s";
    $params[] = $city_filter;
}

if ($phone_filter === 'has_phone') {
    $where_clauses[] = "(p.phone_number IS NOT NULL AND TRIM(p.phone_number) != '')";
} elseif ($phone_filter === 'no_phone') {
    $where_clauses[] = "(p.phone_number IS NULL OR TRIM(p.phone_number) = '')";
}

$where_sql = implode(" AND ", $where_clauses);

// ---------------------------------------------------------------------
// 3. Handle CSV Export
// ---------------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_sql = "
        SELECT 
            p.person_id,
            s.company_name,
            s.account_number,
            p.first_name,
            p.last_name,
            p.phone_number,
            p.email,
            p.address_1,
            p.address_2,
            p.city,
            p.state,
            p.zip,
            p.country,
            p.dob,
            p.comments
        FROM phppos_people p
        INNER JOIN phppos_suppliers s ON p.person_id = s.person_id
        WHERE {$where_sql}
        ORDER BY COALESCE(NULLIF(s.company_name, ''), p.first_name) ASC
    ";

    $stmt_export = $con->prepare($export_sql);
    if (!empty($params)) {
        $stmt_export->bind_param($types, ...$params);
    }
    $stmt_export->execute();
    $export_res = $stmt_export->get_result();

    $filename = "supplier_directory_" . date('Y-m-d_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Output UTF-8 BOM for Microsoft Excel compatibility
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');

    // Header row
    fputcsv($output, [
        'ID',
        'Company Name',
        'Account Number',
        'Contact Person',
        'Phone Number',
        'Email Address',
        'Address 1',
        'Address 2',
        'City',
        'State',
        'Pincode',
        'Country',
        'Date of Birth',
        'Comments / Notes'
    ]);

    while ($r = $export_res->fetch_assoc()) {
        $contact_name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        $dob_val = (!empty($r['dob']) && $r['dob'] != '0000-00-00') ? date('d-m-Y', strtotime($r['dob'])) : '';
        fputcsv($output, [
            $r['person_id'],
            $r['company_name'],
            $r['account_number'],
            $contact_name,
            $r['phone_number'],
            $r['email'],
            $r['address_1'],
            $r['address_2'],
            $r['city'],
            $r['state'],
            $r['zip'],
            $r['country'],
            $dob_val,
            $r['comments']
        ]);
    }
    fclose($output);
    CloseCon($con);
    exit;
}

// ---------------------------------------------------------------------
// 4. Metrics & Statistics (KPIs)
// ---------------------------------------------------------------------
$stats_sql = "
    SELECT 
        COUNT(DISTINCT s.person_id) AS total_suppliers,
        COUNT(DISTINCT NULLIF(TRIM(s.company_name), '')) AS total_companies,
        COUNT(DISTINCT CASE WHEN p.phone_number IS NOT NULL AND TRIM(p.phone_number) != '' THEN s.person_id END) AS with_phone,
        COUNT(DISTINCT NULLIF(TRIM(p.city), '')) AS total_cities
    FROM phppos_suppliers s
    LEFT JOIN phppos_people p ON s.person_id = p.person_id
    WHERE (p.first_name != '' OR s.company_name != '')
";
$stats_res = mysqli_query($con, $stats_sql);
$stats = mysqli_fetch_assoc($stats_res) ?: [
    'total_suppliers' => 0,
    'total_companies' => 0,
    'with_phone' => 0,
    'total_cities' => 0
];

// Distinct Cities List for Filter Dropdown
$cities_sql = "
    SELECT DISTINCT TRIM(p.city) AS city 
    FROM phppos_people p 
    INNER JOIN phppos_suppliers s ON p.person_id = s.person_id 
    WHERE p.city IS NOT NULL AND TRIM(p.city) != '' 
    ORDER BY city ASC
";
$cities_res = mysqli_query($con, $cities_sql);
$city_list = [];
while ($crow = mysqli_fetch_assoc($cities_res)) {
    $city_list[] = $crow['city'];
}

// ---------------------------------------------------------------------
// 5. Total Filtered Records Count (For Pagination)
// ---------------------------------------------------------------------
$count_sql = "
    SELECT COUNT(*) AS total_count 
    FROM phppos_people p
    INNER JOIN phppos_suppliers s ON p.person_id = s.person_id
    WHERE {$where_sql}
";
$stmt_count = $con->prepare($count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$count_res = $stmt_count->get_result();
$total_rows = $count_res->fetch_assoc()['total_count'] ?? 0;
$total_pages = ($limit_param === 'all' || $limit >= $total_rows) ? 1 : (int)ceil($total_rows / $limit);
if ($total_pages < 1) $total_pages = 1;

// ---------------------------------------------------------------------
// 6. Fetch Paginated Records
// ---------------------------------------------------------------------
$query_sql = "
    SELECT 
        p.person_id,
        p.first_name,
        p.last_name,
        p.phone_number,
        p.email,
        p.address_1,
        p.address_2,
        p.city,
        p.state,
        p.zip,
        p.country,
        p.comments,
        p.dob,
        COALESCE(s.company_name, '') AS company_name,
        COALESCE(s.account_number, '') AS account_number
    FROM phppos_people p
    INNER JOIN phppos_suppliers s ON p.person_id = s.person_id
    WHERE {$where_sql}
    ORDER BY COALESCE(NULLIF(s.company_name, ''), p.first_name) ASC
    LIMIT ? OFFSET ?
";

$fetch_params = $params;
$fetch_types = $types . "ii";
$fetch_params[] = $limit;
$fetch_params[] = $offset;

$stmt_main = $con->prepare($query_sql);
if (!empty($fetch_params)) {
    $stmt_main->bind_param($fetch_types, ...$fetch_params);
}
$stmt_main->execute();
$suppliers_result = $stmt_main->get_result();

$flash_msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Directory | Sri Shringarr POS</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --primary: #0f172a;
            --primary-hover: #1e293b;
            --background: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --badge-bg: #f1f5f9;
            --badge-text: #475569;
            --danger-bg: #fff1f2;
            --danger-border: #fecdd3;
            --danger-text: #e11d48;
            --danger-hover: #e11d48;
            --radius: 8px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--background);
            color: var(--text-main);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .layout-container {
            max-width: 1380px;
            margin: 0 auto;
            padding: 24px 28px;
        }

        /* Top Bar & Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .header-title-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .breadcrumb a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .breadcrumb a:hover {
            color: var(--primary);
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        /* Buttons (Shadcn Strict Palette) */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            color: #ffffff;
        }

        .btn-outline {
            background-color: #ffffff;
            color: #334155;
            border-color: var(--border);
        }

        .btn-outline:hover {
            background-color: #f8fafc;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* KPI Cards Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .stat-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background-color: var(--badge-bg);
            border: 1px solid var(--border);
            color: var(--badge-text);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .stat-content {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            line-height: 1.2;
        }

        /* Filter Toolbar Card */
        .filter-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1.2fr 1.2fr 120px auto;
            gap: 12px;
            align-items: flex-end;
        }

        @media (max-width: 992px) {
            .filter-form {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 600px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            height: 38px;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 13px;
            color: var(--text-main);
            background-color: #ffffff;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            width: 100%;
        }

        .form-control:focus {
            border-color: #94a3b8;
            box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.05);
        }

        .filter-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Table Card & Data Table */
        .table-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .table-toolbar {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            flex-wrap: wrap;
            gap: 10px;
        }

        .table-toolbar-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table.supplier-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }

        table.supplier-table th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.supplier-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            color: #334155;
        }

        table.supplier-table tbody tr {
            transition: background-color 0.1s ease;
        }

        table.supplier-table tbody tr:hover {
            background-color: #fbfcfe;
        }

        /* Entity cells & details */
        .company-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar-initial {
            width: 34px;
            height: 34px;
            border-radius: 6px;
            background: var(--badge-bg);
            border: 1px solid var(--border);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }

        .company-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .company-name {
            font-weight: 600;
            color: var(--primary);
            font-size: 13px;
        }

        .contact-name {
            font-weight: 500;
            color: #1e293b;
        }

        .contact-dob {
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .contact-link {
            color: #0f172a;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.15s ease;
        }

        .contact-link:hover {
            color: #334155;
            text-decoration: underline;
        }

        .copy-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 2px 4px;
            font-size: 11px;
            border-radius: 3px;
            transition: color 0.15s ease;
        }

        .copy-btn:hover {
            color: var(--primary);
            background-color: #f1f5f9;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            background-color: var(--badge-bg);
            color: var(--badge-text);
            border: 1px solid var(--border);
            white-space: nowrap;
        }

        .badge-code {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            letter-spacing: 0.2px;
        }

        .location-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 12px;
        }

        .location-city {
            font-weight: 600;
            color: #1e293b;
        }

        .location-address {
            color: var(--text-muted);
            font-size: 11px;
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Action Buttons Group */
        .actions-group {
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .action-icon-btn {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: #334155;
            text-decoration: none;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.15s ease;
        }

        .action-icon-btn:hover {
            background-color: #f8fafc;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .action-icon-btn.btn-delete {
            background-color: var(--danger-bg);
            border-color: var(--danger-border);
            color: var(--danger-text);
        }

        .action-icon-btn.btn-delete:hover {
            background-color: var(--danger-hover);
            color: #ffffff;
            border-color: var(--danger-hover);
        }

        /* Empty State */
        .empty-state {
            padding: 48px 24px;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 12px;
            color: #94a3b8;
        }

        .empty-state h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 6px;
        }

        /* Pagination */
        .pagination-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            background: #ffffff;
            flex-wrap: wrap;
            gap: 12px;
        }

        .pagination-info {
            font-size: 13px;
            color: var(--text-muted);
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .page-link {
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            text-decoration: none;
            background: #ffffff;
            transition: all 0.15s ease;
        }

        .page-link:hover:not(.active):not(.disabled) {
            background: #f1f5f9;
            color: #0f172a;
        }

        .page-link.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .page-link.disabled {
            opacity: 0.4;
            pointer-events: none;
            cursor: not-allowed;
        }

        /* Modals */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 10px;
            width: 100%;
            max-width: 580px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            animation: modalFadeIn 0.15s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.97); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 18px;
            cursor: pointer;
            padding: 4px;
            transition: color 0.15s ease;
        }

        .modal-close:hover {
            color: var(--primary);
        }

        .modal-body {
            padding: 20px;
            max-height: calc(85vh - 120px);
            overflow-y: auto;
        }

        .modal-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        /* Detail Modal Grid */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .detail-item.full-width {
            grid-column: span 2;
        }

        .detail-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .detail-val {
            font-size: 14px;
            color: var(--text-main);
            font-weight: 500;
            word-break: break-word;
        }

        /* Toast feedback */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1100;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .toast-msg {
            background: var(--primary);
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            animation: toastIn 0.2s ease;
        }

        @keyframes toastIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
            }

            .layout-container {
                max-width: 100% !important;
                padding: 0 !important;
            }

            .header-actions,
            .stats-grid,
            .filter-card,
            .table-toolbar,
            .pagination-bar,
            .actions-group,
            .copy-btn,
            .modal-overlay,
            .toast-container {
                display: none !important;
            }

            .table-card {
                border: none !important;
                box-shadow: none !important;
            }

            table.supplier-table {
                border: 1px solid #000000 !important;
                font-size: 10pt !important;
            }

            table.supplier-table th {
                background: #eeeeee !important;
                color: #000000 !important;
                border: 1px solid #000000 !important;
                padding: 6px 8px !important;
            }

            table.supplier-table td {
                border: 1px solid #cccccc !important;
                padding: 6px 8px !important;
                color: #000000 !important;
            }

            .print-only-header {
                display: block !important;
                margin-bottom: 20px;
                text-align: center;
            }

            .print-only-header h1 {
                font-size: 18pt;
                margin-bottom: 4px;
            }

            .print-only-header p {
                font-size: 10pt;
                color: #333333;
            }
        }

        .print-only-header {
            display: none;
        }
    </style>
</head>
<body>

<div class="layout-container">

    <!-- Print-Only Report Header -->
    <div class="print-only-header">
        <h1>Sri Shringarr Fashion Studio</h1>
        <p>Supplier Directory & Contact List</p>
        <p style="font-size: 9pt; margin-top: 4px;">Generated on <?= date('d M Y, h:i A') ?> | Total Records: <?= number_format($total_rows) ?></p>
    </div>

    <!-- Page Header & Action Bar -->
    <div class="page-header">
        <div class="header-title-group">
            <div class="breadcrumb">
                <a href="/pos/home_dashboard.php"><i class="fas fa-home"></i> Home</a>
                <span>/</span>
                <span>Purchase</span>
                <span>/</span>
                <span>Suppliers</span>
            </div>
            <h1 class="page-title">
                Supplier Directory
                <span class="badge badge-code"><?= number_format($total_rows) ?> Suppliers</span>
            </h1>
        </div>

        <div class="header-actions">
            <a href="add_supplier.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add Supplier
            </a>
            <a href="view_bills.php" class="btn btn-outline">
                <i class="fas fa-file-invoice"></i> View Bills
            </a>
            <a href="purchase_entry.php" class="btn btn-outline">
                <i class="fas fa-shopping-cart"></i> Purchase Entry
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
            <a href="/pos/home_dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- KPI Metric Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Total Suppliers</span>
                <span class="stat-value"><?= number_format($stats['total_suppliers']) ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Companies</span>
                <span class="stat-value"><?= number_format($stats['total_companies']) ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-phone-alt"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Verified Phones</span>
                <span class="stat-value"><?= number_format($stats['with_phone']) ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Cities Covered</span>
                <span class="stat-value"><?= number_format($stats['total_cities']) ?></span>
            </div>
        </div>
    </div>

    <!-- Search & Filters Toolbar -->
    <div class="filter-card">
        <form method="GET" action="" class="filter-form">
            <div class="form-group">
                <label for="search">Search Directory</label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       class="form-control" 
                       placeholder="Company, contact name, phone, code, city..." 
                       value="<?= htmlspecialchars($search) ?>" 
                       autocomplete="off">
            </div>

            <div class="form-group">
                <label for="city">City Filter</label>
                <select id="city" name="city" class="form-control">
                    <option value="">All Cities</option>
                    <?php foreach ($city_list as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= ($city_filter === $c) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="phone_status">Contact Availability</label>
                <select id="phone_status" name="phone_status" class="form-control">
                    <option value="all" <?= ($phone_filter === 'all') ? 'selected' : '' ?>>All Contacts</option>
                    <option value="has_phone" <?= ($phone_filter === 'has_phone') ? 'selected' : '' ?>>With Phone Number</option>
                    <option value="no_phone" <?= ($phone_filter === 'no_phone') ? 'selected' : '' ?>>Missing Phone</option>
                </select>
            </div>

            <div class="form-group">
                <label for="limit">Per Page</label>
                <select id="limit" name="limit" class="form-control">
                    <option value="15" <?= ($limit_param === '15') ? 'selected' : '' ?>>15</option>
                    <option value="25" <?= ($limit_param === '25') ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= ($limit_param === '50') ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= ($limit_param === '100') ? 'selected' : '' ?>>100</option>
                    <option value="all" <?= ($limit_param === 'all') ? 'selected' : '' ?>>All</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary" style="height: 38px;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <?php if ($search !== '' || $city_filter !== '' || $phone_filter !== 'all' || $limit_param !== '25'): ?>
                    <a href="view_supplier.php" class="btn btn-outline" style="height: 38px;" title="Reset Filters">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="table-card">
        <div class="table-toolbar">
            <div class="table-toolbar-title">
                <i class="fas fa-list"></i>
                <span>Supplier Records</span>
            </div>
            <div style="font-size: 13px; color: var(--text-muted);">
                Showing <strong><?= ($total_rows > 0) ? ($offset + 1) : 0 ?></strong> to 
                <strong><?= min($offset + $limit, $total_rows) ?></strong> of 
                <strong><?= number_format($total_rows) ?></strong> suppliers
            </div>
        </div>

        <div class="table-responsive">
            <table class="supplier-table" id="supplierTable">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">#</th>
                        <th>Supplier / Company</th>
                        <th>Contact Person</th>
                        <th>Phone / Mobile</th>
                        <th>Email</th>
                        <th>City / Address</th>
                        <th style="width: 140px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($suppliers_result && $suppliers_result->num_rows > 0): ?>
                        <?php 
                        $sr = $offset + 1;
                        while ($row = $suppliers_result->fetch_assoc()): 
                            $company = trim($row['company_name']);
                            $contact_name = trim($row['first_name'] . ' ' . $row['last_name']);
                            $display_title = $company !== '' ? $company : $contact_name;
                            $initial = strtoupper(substr($display_title ?: 'S', 0, 1));
                            $acc_no = trim($row['account_number']);
                            $phone = trim($row['phone_number']);
                            $email = trim($row['email']);
                            $city = trim($row['city']);
                            $state = trim($row['state']);
                            $address = trim($row['address_1'] . ' ' . $row['address_2']);
                            $dob = (!empty($row['dob']) && $row['dob'] != '0000-00-00') ? date('d/m/Y', strtotime($row['dob'])) : '';
                            
                            // JSON string for modal
                            $jsonData = htmlspecialchars(json_encode([
                                'id' => $row['person_id'],
                                'company_name' => $company,
                                'account_number' => $acc_no,
                                'first_name' => $row['first_name'],
                                'last_name' => $row['last_name'],
                                'phone_number' => $phone,
                                'email' => $email,
                                'address_1' => $row['address_1'],
                                'address_2' => $row['address_2'],
                                'city' => $city,
                                'state' => $state,
                                'zip' => $row['zip'],
                                'country' => $row['country'],
                                'comments' => $row['comments'],
                                'dob' => $dob
                            ]), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr>
                                <td style="text-align: center; color: var(--text-muted); font-weight: 500;">
                                    <?= $sr++ ?>
                                </td>

                                <td>
                                    <div class="company-cell">
                                        <div class="avatar-initial">
                                            <?= $initial ?>
                                        </div>
                                        <div class="company-meta">
                                            <span class="company-name">
                                                <?= htmlspecialchars($display_title) ?>
                                            </span>
                                            <?php if ($acc_no !== ''): ?>
                                                <div>
                                                    <span class="badge badge-code" title="Supplier Account Code">
                                                        <i class="fas fa-tag" style="font-size: 9px;"></i> <?= htmlspecialchars($acc_no) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="contact-name">
                                        <?= htmlspecialchars($contact_name) ?>
                                    </div>
                                    <?php if ($dob !== ''): ?>
                                        <div class="contact-dob" title="Date of Birth">
                                            <i class="fas fa-birthday-cake"></i> <?= $dob ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($phone !== ''): ?>
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <a href="tel:<?= htmlspecialchars($phone) ?>" class="contact-link">
                                                <i class="fas fa-phone-alt" style="font-size: 11px; color: var(--text-muted);"></i>
                                                <?= htmlspecialchars($phone) ?>
                                            </a>
                                            <button type="button" class="copy-btn" onclick="copyText('<?= htmlspecialchars($phone) ?>')" title="Copy Phone">
                                                <i class="far fa-copy"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($email !== ''): ?>
                                        <a href="mailto:<?= htmlspecialchars($email) ?>" class="contact-link" title="<?= htmlspecialchars($email) ?>">
                                            <i class="far fa-envelope" style="font-size: 11px; color: var(--text-muted);"></i>
                                            <?= htmlspecialchars(strlen($email) > 22 ? substr($email, 0, 20) . '...' : $email) ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="location-info">
                                        <?php if ($city !== '' || $state !== ''): ?>
                                            <span class="location-city">
                                                <?= htmlspecialchars(implode(', ', array_filter([$city, $state]))) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($address !== ''): ?>
                                            <span class="location-address" title="<?= htmlspecialchars($address) ?>">
                                                <?= htmlspecialchars($address) ?>
                                            </span>
                                        <?php elseif ($city === '' && $state === ''): ?>
                                            <span style="color: #94a3b8;">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td style="text-align: center;">
                                    <div class="actions-group" style="justify-content: center;">
                                        <!-- View Details Modal Trigger -->
                                        <button type="button" 
                                                class="action-icon-btn" 
                                                title="View Full Profile" 
                                                onclick='openDetailModal(<?= $jsonData ?>)'>
                                            <i class="far fa-eye"></i>
                                        </button>

                                        <!-- Edit Trigger -->
                                        <button type="button" 
                                                class="action-icon-btn" 
                                                title="Quick Edit" 
                                                onclick='openEditModal(<?= $jsonData ?>)'>
                                            <i class="far fa-edit"></i>
                                        </button>

                                        <!-- Bills Link -->
                                        <a href="view_bills.php" 
                                           class="action-icon-btn" 
                                           title="Purchase Bills">
                                            <i class="fas fa-receipt"></i>
                                        </a>

                                        <!-- Delete Trigger -->
                                        <button type="button" 
                                                class="action-icon-btn btn-delete" 
                                                title="Delete Supplier" 
                                                onclick="confirmDelete('<?= $row['person_id'] ?>', '<?= htmlspecialchars(addslashes($display_title)) ?>')">
                                            <i class="far fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <h3>No suppliers found</h3>
                                    <p>Try adjusting your search query or removing active city filters.</p>
                                    <?php if ($search !== '' || $city_filter !== '' || $phone_filter !== 'all'): ?>
                                        <div style="margin-top: 14px;">
                                            <a href="view_supplier.php" class="btn btn-outline btn-sm">
                                                <i class="fas fa-undo"></i> Clear Filters
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-bar">
                <div class="pagination-info">
                    Page <strong><?= $page ?></strong> of <strong><?= $total_pages ?></strong>
                </div>

                <div class="pagination-controls">
                    <?php 
                    $query_params = $_GET;
                    
                    // First & Prev
                    $query_params['page'] = 1;
                    $first_url = '?' . http_build_query($query_params);
                    $query_params['page'] = max(1, $page - 1);
                    $prev_url = '?' . http_build_query($query_params);
                    ?>
                    <a href="<?= $first_url ?>" class="page-link <?= ($page <= 1) ? 'disabled' : '' ?>" title="First Page">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="<?= $prev_url ?>" class="page-link <?= ($page <= 1) ? 'disabled' : '' ?>" title="Previous Page">
                        <i class="fas fa-angle-left"></i>
                    </a>

                    <?php
                    // Windowed page buttons (e.g. current +/- 2)
                    $start_p = max(1, $page - 2);
                    $end_p = min($total_pages, $page + 2);
                    for ($p = $start_p; $p <= $end_p; $p++):
                        $query_params['page'] = $p;
                        $page_url = '?' . http_build_query($query_params);
                    ?>
                        <a href="<?= $page_url ?>" class="page-link <?= ($p === $page) ? 'active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php
                    // Next & Last
                    $query_params['page'] = min($total_pages, $page + 1);
                    $next_url = '?' . http_build_query($query_params);
                    $query_params['page'] = $total_pages;
                    $last_url = '?' . http_build_query($query_params);
                    ?>
                    <a href="<?= $next_url ?>" class="page-link <?= ($page >= $total_pages) ? 'disabled' : '' ?>" title="Next Page">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="<?= $last_url ?>" class="page-link <?= ($page >= $total_pages) ? 'disabled' : '' ?>" title="Last Page">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- =================================================================== -->
<!-- MODAL 1: VIEW SUPPLIER DETAILS                                      -->
<!-- =================================================================== -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-id-card"></i> Supplier Profile
            </h3>
            <button type="button" class="modal-close" onclick="closeModal('detailModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-item full-width" style="border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 4px;">
                    <span class="detail-label">Supplier / Company</span>
                    <span class="detail-val" id="det_company" style="font-size: 16px; font-weight: 700; color: var(--primary);">-</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Account Code</span>
                    <span class="detail-val" id="det_account_code">-</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Contact Person</span>
                    <span class="detail-val" id="det_contact">-</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Phone / Mobile</span>
                    <span class="detail-val" id="det_phone">-</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Email Address</span>
                    <span class="detail-val" id="det_email">-</span>
                </div>

                <div class="detail-item full-width">
                    <span class="detail-label">Complete Address</span>
                    <span class="detail-val" id="det_address">-</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">City</span>
                    <span class="detail-val" id="det_city">-</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">State & Pincode</span>
                    <span class="detail-val" id="det_state_zip">-</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Country</span>
                    <span class="detail-val" id="det_country">-</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Date of Birth</span>
                    <span class="detail-val" id="det_dob">-</span>
                </div>

                <div class="detail-item full-width" id="det_comments_wrapper" style="display: none;">
                    <span class="detail-label">Notes & Comments</span>
                    <span class="detail-val" id="det_comments" style="font-style: italic; color: #475569;">-</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('detailModal')">Close</button>
            <button type="button" class="btn btn-primary" id="det_edit_btn">
                <i class="far fa-edit"></i> Edit Details
            </button>
        </div>
    </div>
</div>

<!-- =================================================================== -->
<!-- MODAL 2: QUICK EDIT SUPPLIER                                        -->
<!-- =================================================================== -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <form method="POST" action="Supp_edit.php">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="far fa-edit"></i> Edit Supplier
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_id" value="">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="edit_company_name">Company Name</label>
                        <input type="text" name="company_name" id="edit_company_name" class="form-control" placeholder="Company Name">
                    </div>

                    <div class="form-group">
                        <label for="edit_account_number">Account Code</label>
                        <input type="text" name="account_number" id="edit_account_number" class="form-control" placeholder="e.g. AJ or FLY">
                    </div>

                    <div class="form-group">
                        <label for="edit_first_name">First Name *</label>
                        <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_last_name">Last Name</label>
                        <input type="text" name="last_name" id="edit_last_name" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="edit_phone">Phone / Mobile *</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_email">Email Address</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="edit_add">Street Address</label>
                        <input type="text" name="add" id="edit_add" class="form-control" placeholder="Address">
                    </div>

                    <div class="form-group">
                        <label for="edit_dob">Date of Birth</label>
                        <input type="text" name="dob" id="edit_dob" class="form-control" placeholder="dd/mm/yyyy">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" name="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =================================================================== -->
<!-- MODAL 3: DELETE CONFIRMATION                                        -->
<!-- =================================================================== -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box" style="max-width: 440px;">
        <div class="modal-header">
            <h3 class="modal-title" style="color: var(--danger-text);">
                <i class="fas fa-exclamation-triangle"></i> Delete Supplier
            </h3>
            <button type="button" class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body" style="font-size: 14px; color: #334155;">
            <p>Are you sure you want to delete supplier <strong id="delete_supplier_name" style="color: var(--primary);"></strong>?</p>
            <p style="margin-top: 8px; font-size: 12px; color: var(--text-muted);">
                This action will remove the supplier link from the POS database. Existing historical bills will remain preserved.
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
            <a href="#" id="delete_confirm_btn" class="btn btn-primary" style="background-color: var(--danger-text); border-color: var(--danger-text);">
                <i class="far fa-trash-alt"></i> Yes, Delete
            </a>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script>
    // Copy helper
    function copyText(text) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(function() {
            showToast("Copied to clipboard: " + text);
        }).catch(function() {
            var tempInput = document.createElement("input");
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand("copy");
            document.body.removeChild(tempInput);
            showToast("Copied: " + text);
        });
    }

    // Toast notification
    function showToast(message) {
        var container = document.getElementById("toastContainer");
        var toast = document.createElement("div");
        toast.className = "toast-msg";
        toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
        container.appendChild(toast);
        setTimeout(function() {
            toast.style.transition = "opacity 0.3s ease, transform 0.3s ease";
            toast.style.opacity = "0";
            toast.style.transform = "translateY(10px)";
            setTimeout(function() { toast.remove(); }, 300);
        }, 3000);
    }

    // Modal helpers
    function openModal(id) {
        var modal = document.getElementById(id);
        if (modal) modal.classList.add("active");
    }

    function closeModal(id) {
        var modal = document.getElementById(id);
        if (modal) modal.classList.remove("active");
    }

    // Close on overlay backdrop click
    document.querySelectorAll(".modal-overlay").forEach(function(overlay) {
        overlay.addEventListener("click", function(e) {
            if (e.target === this) {
                this.classList.remove("active");
            }
        });
    });

    // Close on Escape key
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            document.querySelectorAll(".modal-overlay.active").forEach(function(m) {
                m.classList.remove("active");
            });
        }
    });

    // Detail Modal Population
    function openDetailModal(data) {
        document.getElementById("det_company").textContent = data.company_name || (data.first_name + " " + (data.last_name || ""));
        document.getElementById("det_account_code").textContent = data.account_number || "-";
        document.getElementById("det_contact").textContent = (data.first_name + " " + (data.last_name || "")).trim() || "-";
        document.getElementById("det_phone").textContent = data.phone_number || "-";
        document.getElementById("det_email").textContent = data.email || "-";
        
        var fullAddr = [data.address_1, data.address_2].filter(Boolean).join(", ");
        document.getElementById("det_address").textContent = fullAddr || "-";
        document.getElementById("det_city").textContent = data.city || "-";
        
        var stateZip = [data.state, data.zip].filter(Boolean).join(" - ");
        document.getElementById("det_state_zip").textContent = stateZip || "-";
        document.getElementById("det_country").textContent = data.country || "-";
        document.getElementById("det_dob").textContent = data.dob || "-";

        var commentsWrap = document.getElementById("det_comments_wrapper");
        if (data.comments && data.comments.trim() !== "") {
            document.getElementById("det_comments").textContent = data.comments;
            commentsWrap.style.display = "flex";
        } else {
            commentsWrap.style.display = "none";
        }

        document.getElementById("det_edit_btn").onclick = function() {
            closeModal("detailModal");
            openEditModal(data);
        };

        openModal("detailModal");
    }

    // Edit Modal Population
    function openEditModal(data) {
        document.getElementById("edit_id").value = data.id;
        document.getElementById("edit_company_name").value = data.company_name || "";
        document.getElementById("edit_account_number").value = data.account_number || "";
        document.getElementById("edit_first_name").value = data.first_name || "";
        document.getElementById("edit_last_name").value = data.last_name || "";
        document.getElementById("edit_phone").value = data.phone_number || "";
        document.getElementById("edit_email").value = data.email || "";
        document.getElementById("edit_add").value = data.address_1 || "";
        document.getElementById("edit_dob").value = data.dob || "";

        openModal("editModal");
    }

    // Delete Confirmation
    function confirmDelete(id, name) {
        document.getElementById("delete_supplier_name").textContent = name;
        document.getElementById("delete_confirm_btn").href = "delSupplier.php?id=" + encodeURIComponent(id);
        openModal("deleteModal");
    }
</script>

</body>
</html>
<?php CloseCon($con); ?>
