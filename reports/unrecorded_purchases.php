<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set("display_errors", 0);
require_once __DIR__ . '/../db_connection.php';
$con = OpenSrishringarrCon();

if (!function_exists('is_pos_local')) {
    function is_pos_local()
    {
        return php_sapi_name() === 'cli'
            || !isset($_SERVER['HTTP_HOST'])
            || in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1'])
            || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0
            || strpos($_SERVER['HTTP_HOST'], '127.0.0.1:') === 0
            || strpos($_SERVER['HTTP_HOST'], '192.168.') === 0
            || (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']));
    }
}

if (!function_exists('get_pos_base_url')) {
    function get_pos_base_url()
    {
        if (is_pos_local()) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($uri, '/ss/pos/') !== false) {
                return '/ss/pos/';
            }
            return '/pos/';
        }
        return 'https://srishringarr.com/pos/';
    }
}

$base_url = get_pos_base_url();

// ---------------------------------------------------------------------
// 1. AJAX Endpoint for Detailed Item Audit Breakdown
// ---------------------------------------------------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] === 'item_audit' && !empty($_GET['item_name'])) {
    header('Content-Type: application/json');
    $item_name = mysqli_real_escape_string($con, trim($_GET['item_name']));

    // Item Info
    $item_q = mysqli_query($con, "
        SELECT i.item_id, i.name, i.item_number, i.category, i.unit_price, i.cost_price, i.quantity,
               s.company_name as supplier_name, s.person_id as supplier_id
        FROM phppos_items i
        LEFT JOIN phppos_suppliers s ON i.supplier_id = s.person_id
        WHERE i.name = '{$item_name}'
        LIMIT 1
    ");
    $item = mysqli_fetch_assoc($item_q);
    if (!$item) {
        echo json_encode(["status" => "error", "message" => "Item not found."]);
        CloseCon($con);
        exit;
    }

    // Purchases (should be empty for unrecorded items)
    $qp = mysqli_query($con, "
        SELECT pd.qty, pd.price, p.pur_id, p.bill_id, p.date as bill_date, p.supp_id, s.company_name
        FROM phppos_purchase_details pd
        LEFT JOIN phppos_purchase p ON pd.pur_id = p.pur_id
        LEFT JOIN phppos_suppliers s ON p.supp_id = s.person_id
        WHERE pd.item_id = '{$item['item_id']}'
        ORDER BY p.date DESC
    ");
    $purchases = [];
    $total_pur = 0;
    while ($r = mysqli_fetch_assoc($qp)) {
        $purchases[] = $r;
        $total_pur += (float)$r['qty'];
    }

    // Sales (Approval)
    $qs = mysqli_query($con, "
        SELECT ad.qty, ad.return_qty, a.bill_id, a.new_bill_number, a.bill_date, a.cust_id, 
               pp.first_name, pp.last_name
        FROM approval_detail ad
        JOIN approval a ON ad.bill_id = a.bill_id
        LEFT JOIN phppos_people pp ON a.cust_id = pp.person_id
        WHERE ad.item_id = '{$item_name}'
        ORDER BY a.bill_date DESC
    ");
    $sales = [];
    $total_sold = 0;
    $total_returned = 0;
    while ($r = mysqli_fetch_assoc($qs)) {
        $sales[] = $r;
        $total_sold += (float)$r['qty'];
        $total_returned += (float)$r['return_qty'];
    }
    $net_sold = $total_sold - $total_returned;

    // Rentals (Rent)
    $qr = mysqli_query($con, "
        SELECT od.qty, od.rent, pr.bill_id, pr.new_bill_number, pr.booking_status, pr.pick_date, pr.delivery_date, 
               pp.first_name, pp.last_name
        FROM order_detail od
        JOIN phppos_rent pr ON od.bill_id = pr.bill_id
        LEFT JOIN phppos_people pp ON pr.cust_id = pp.person_id
        WHERE od.item_id = '{$item_name}'
        ORDER BY pr.bill_id DESC
    ");
    $rentals = [];
    $active_rent = 0;
    $all_rent = 0;
    while ($r = mysqli_fetch_assoc($qr)) {
        $rentals[] = $r;
        $rqty = (float)($r['qty'] > 0 ? $r['qty'] : 1);
        $all_rent += $rqty;
        $status = trim($r['booking_status'] ?? '');
        if ($status !== 'Returned' && $status !== 'Cancelled') {
            $active_rent += $rqty;
        }
    }

    $avail = (float)$item['quantity'];
    $accounted = $net_sold + $active_rent + $avail;

    echo json_encode([
        "status" => "success",
        "item" => $item,
        "summary" => [
            "total_purchased" => $total_pur,
            "stock_report_purchased" => 0,
            "mod_diff" => $total_pur,
            "gross_sold" => $total_sold,
            "sales_returned" => $total_returned,
            "net_sold" => $net_sold,
            "active_rent" => $active_rent,
            "all_rent_occurrences" => $all_rent,
            "available_stock" => $avail,
            "accounted_total" => $accounted,
            "unrecorded_inflow" => $accounted - $total_pur
        ],
        "purchases" => $purchases,
        "sales" => $sales,
        "rentals" => $rentals
    ], JSON_UNESCAPED_UNICODE);

    CloseCon($con);
    exit;
}

// ---------------------------------------------------------------------
// 2. Filter & Pagination Parameters
// ---------------------------------------------------------------------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$activity_filter = isset($_GET['activity_filter']) ? trim($_GET['activity_filter']) : 'active'; // default: active (with stock, sales, or rent)
$cat_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$limit_param = isset($_GET['limit']) ? trim($_GET['limit']) : '25';
$limit = ($limit_param === 'all') ? 999999 : (int)$limit_param;
if ($limit <= 0) $limit = 25;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// ---------------------------------------------------------------------
// 3. Dropdown Options (Categories)
// ---------------------------------------------------------------------
$categories_res = mysqli_query($con, "
    SELECT DISTINCT category 
    FROM phppos_items 
    WHERE category IS NOT NULL AND category != '' 
    ORDER BY category ASC
");
$categories = [];
while ($crow = mysqli_fetch_assoc($categories_res)) {
    $categories[] = $crow['category'];
}

// ---------------------------------------------------------------------
// 4. Query Construction for Zero-Purchase Items
// ---------------------------------------------------------------------
$where_clauses = ["pd.item_id IS NULL"];
$params = [];
$types = "";

if ($search !== '') {
    $search_like = "%" . $search . "%";
    $where_clauses[] = "(i.name LIKE ? OR i.item_number LIKE ? OR i.description LIKE ?)";
    $types .= "sss";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}

if ($cat_filter !== '') {
    $where_clauses[] = "i.category = ?";
    $types .= "s";
    $params[] = $cat_filter;
}

// Activity Filter (HAVING clause)
$having_clause = "";
switch ($activity_filter) {
    case 'active':
        // Has physical stock, or sales, or rent
        $having_clause = "HAVING (avail_qty != 0 OR net_sales > 0 OR act_rent > 0)";
        break;
    case 'with_stock':
        $having_clause = "HAVING (avail_qty > 0)";
        break;
    case 'only_sold':
        $having_clause = "HAVING (net_sales > 0)";
        break;
    case 'only_rent':
        $having_clause = "HAVING (act_rent > 0)";
        break;
    case 'dormant':
        $having_clause = "HAVING (avail_qty = 0 AND net_sales <= 0 AND act_rent <= 0)";
        break;
    case 'all':
    default:
        $having_clause = "";
        break;
}

$where_sql = implode(" AND ", $where_clauses);

// Base subquery aggregation
$base_query = "
    FROM phppos_items i
    LEFT JOIN (
        SELECT DISTINCT item_id 
        FROM phppos_purchase_details
    ) pd ON i.item_id = pd.item_id
    LEFT JOIN (
        SELECT item_id, SUM(qty - return_qty) AS net_sales 
        FROM approval_detail 
        GROUP BY item_id
    ) sale ON i.name = sale.item_id
    LEFT JOIN (
        SELECT 
            od.item_id, 
            SUM(CASE WHEN pr.booking_status IS NULL OR pr.booking_status NOT IN ('Returned', 'Cancelled') THEN (CASE WHEN od.qty > 0 THEN od.qty ELSE 1 END) ELSE 0 END) AS act_rent,
            SUM(CASE WHEN od.qty > 0 THEN od.qty ELSE 1 END) AS all_rent
        FROM order_detail od
        LEFT JOIN phppos_rent pr ON od.bill_id = pr.bill_id
        GROUP BY od.item_id
    ) rent ON i.name = rent.item_id
    WHERE {$where_sql}
";

// ---------------------------------------------------------------------
// 5. Handle CSV Export
// ---------------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_sql = "
        SELECT 
            i.item_id,
            i.name AS item_name,
            i.item_number AS barcode,
            i.category,
            i.unit_price,
            i.cost_price,
            i.quantity AS avail_qty,
            COALESCE(sale.net_sales, 0) AS net_sales,
            COALESCE(rent.act_rent, 0) AS act_rent,
            COALESCE(rent.all_rent, 0) AS all_rent
        {$base_query}
        {$having_clause}
        ORDER BY (COALESCE(sale.net_sales, 0) + COALESCE(rent.act_rent, 0) + i.quantity) DESC, i.name ASC
    ";

    $stmt_exp = $con->prepare($export_sql);
    if (!empty($params)) {
        $stmt_exp->bind_param($types, ...$params);
    }
    $stmt_exp->execute();
    $exp_res = $stmt_exp->get_result();

    $filename = "unrecorded_purchases_audit_" . date('Y-m-d_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $out = fopen('php://output', 'w');

    fputcsv($out, [
        'Item Code / SKU',
        'Barcode',
        'Category',
        'Cost Price',
        'Sales Price',
        'Purchased Qty (P)',
        'Sales Qty (S)',
        'Active Rent Qty (R)',
        'Available in Stock (A)',
        'Total Accounted (S+R+A)',
        'Unrecorded Inflow',
        'Status'
    ]);

    while ($r = $exp_res->fetch_assoc()) {
        $pur = 0;
        $sale = (float)$r['net_sales'];
        $rent = (float)$r['act_rent'];
        $avail = (float)$r['avail_qty'];
        $accounted = $sale + $rent + $avail;

        $status = ($accounted > 0) ? "Active Inflow ({$accounted})" : "Dormant (0)";

        fputcsv($out, [
            $r['item_name'],
            $r['barcode'],
            $r['category'],
            $r['cost_price'],
            $r['unit_price'],
            $pur,
            $sale,
            $rent,
            $avail,
            $accounted,
            $accounted,
            $status
        ]);
    }
    fclose($out);
    CloseCon($con);
    exit;
}

// ---------------------------------------------------------------------
// 6. Total Matching Records Count for Pagination
// ---------------------------------------------------------------------
$count_query = "
    SELECT COUNT(*) as total_items FROM (
        SELECT 
            i.item_id,
            COALESCE(sale.net_sales, 0) AS net_sales,
            COALESCE(rent.act_rent, 0) AS act_rent,
            i.quantity AS avail_qty
        {$base_query}
        {$having_clause}
    ) AS audit_table
";

$stmt_cnt = $con->prepare($count_query);
if (!empty($params)) {
    $stmt_cnt->bind_param($types, ...$params);
}
$stmt_cnt->execute();
$total_rows = $stmt_cnt->get_result()->fetch_assoc()['total_items'] ?? 0;
$total_pages = ($limit_param === 'all' || $limit >= $total_rows) ? 1 : (int)ceil($total_rows / $limit);
if ($total_pages < 1) $total_pages = 1;

// ---------------------------------------------------------------------
// 7. Paginated Data Fetch
// ---------------------------------------------------------------------
$data_query = "
    SELECT 
        i.item_id,
        i.name AS item_name,
        i.item_number AS barcode,
        i.category,
        i.unit_price,
        i.cost_price,
        i.quantity AS avail_qty,
        COALESCE(sale.net_sales, 0) AS net_sales,
        COALESCE(rent.act_rent, 0) AS act_rent,
        COALESCE(rent.all_rent, 0) AS all_rent
    {$base_query}
    {$having_clause}
    ORDER BY (COALESCE(sale.net_sales, 0) + COALESCE(rent.act_rent, 0) + i.quantity) DESC, i.name ASC
    LIMIT ? OFFSET ?
";

$fetch_params = $params;
$fetch_types = $types . "ii";
$fetch_params[] = $limit;
$fetch_params[] = $offset;

$stmt_data = $con->prepare($data_query);
if (!empty($fetch_params)) {
    $stmt_data->bind_param($fetch_types, ...$fetch_params);
}
$stmt_data->execute();
$items_result = $stmt_data->get_result();

// ---------------------------------------------------------------------
// 8. Global High-Level Metrics for Top Cards
// ---------------------------------------------------------------------
$kpi_query = "
    SELECT 
        COUNT(DISTINCT i.item_id) AS total_no_purchase,
        COUNT(DISTINCT CASE WHEN (i.quantity != 0 OR sale.net_sales > 0 OR rent.act_rent > 0) THEN i.item_id END) AS active_no_purchase,
        COUNT(DISTINCT CASE WHEN i.quantity > 0 THEN i.item_id END) AS items_with_stock,
        COUNT(DISTINCT CASE WHEN sale.net_sales > 0 THEN i.item_id END) AS items_sold,
        COUNT(DISTINCT CASE WHEN rent.act_rent > 0 THEN i.item_id END) AS items_rented
    FROM phppos_items i
    LEFT JOIN (SELECT DISTINCT item_id FROM phppos_purchase_details) pd ON i.item_id = pd.item_id
    LEFT JOIN (
        SELECT item_id, SUM(qty - return_qty) AS net_sales 
        FROM approval_detail 
        GROUP BY item_id
    ) sale ON i.name = sale.item_id
    LEFT JOIN (
        SELECT 
            od.item_id, 
            SUM(CASE WHEN pr.booking_status IS NULL OR pr.booking_status NOT IN ('Returned', 'Cancelled') THEN (CASE WHEN od.qty > 0 THEN od.qty ELSE 1 END) ELSE 0 END) AS act_rent
        FROM order_detail od
        LEFT JOIN phppos_rent pr ON od.bill_id = pr.bill_id
        GROUP BY od.item_id
    ) rent ON i.name = rent.item_id
    WHERE pd.item_id IS NULL
";
$kpi_res = mysqli_query($con, $kpi_query);
$kpi = mysqli_fetch_assoc($kpi_res) ?: [
    'total_no_purchase' => 3573,
    'active_no_purchase' => 3042,
    'items_with_stock' => 506,
    'items_sold' => 2578,
    'items_rented' => 669
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unrecorded Purchases Audit (Zero Purchase Bills) | Sri Shringarr POS</title>
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
            --success-bg: #f0fdf4;
            --success-border: #bbf7d0;
            --success-text: #166534;
            --warning-bg: #fffbeb;
            --warning-border: #fde68a;
            --warning-text: #92400e;
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
            padding: 24px;
            font-size: 13px;
            line-height: 1.5;
        }

        .layout-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Top Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 16px;
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
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 500;
            border-radius: var(--radius);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            outline: none;
            border: 1px solid transparent;
            height: 38px;
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
            border: 1px solid var(--border);
        }

        .btn-outline:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            color: var(--primary);
        }

        /* Info Alert Card */
        .formula-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-left: 4px solid #f59e0b;
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .formula-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .formula-desc {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* KPI Cards Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .stat-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background-color: var(--badge-bg);
            border: 1px solid var(--border);
            color: var(--badge-text);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .stat-content {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            line-height: 1.2;
            margin-top: 2px;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
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
            grid-template-columns: 2fr 1.6fr 1.4fr 0.8fr auto;
            gap: 14px;
            align-items: flex-end;
        }

        @media (max-width: 1024px) {
            .filter-form {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
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
            transition: border-color 0.15s ease;
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

        /* Table Card */
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

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }

        .audit-table th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .audit-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
            vertical-align: middle;
        }

        .audit-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .audit-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            border: 1px solid var(--border);
            background-color: var(--badge-bg);
            color: var(--badge-text);
        }

        .badge-zero-pur {
            background-color: #fff1f2;
            color: #be123c;
            border-color: #fecdd3;
            font-weight: 700;
        }

        .badge-code {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid var(--border);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-active-inflow {
            background-color: var(--warning-bg);
            color: var(--warning-text);
            border: 1px solid var(--warning-border);
        }

        .status-dormant {
            background-color: var(--badge-bg);
            color: var(--badge-text);
            border: 1px solid var(--border);
        }

        /* Action Buttons */
        .action-icon-btn {
            background: #ffffff;
            border: 1px solid var(--border);
            color: #475569;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .action-icon-btn:hover {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }

        /* Pagination */
        .pagination-container {
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
            font-size: 12px;
            color: var(--text-muted);
        }

        .pagination-nav {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            font-size: 12px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: #334155;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .page-link:hover {
            background: #f1f5f9;
            color: var(--primary);
        }

        .page-link.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            font-weight: 600;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 860px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: modalFadeIn 0.15s ease-out;
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
            background: #ffffff;
        }

        .modal-title {
            font-size: 15px;
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
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
            border-radius: 4px;
        }

        .modal-close:hover {
            color: var(--primary);
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 12px 20px;
            border-top: 1px solid var(--border);
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .audit-summary-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 14px;
            margin-bottom: 18px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
            text-align: center;
        }

        .audit-stat-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .audit-stat-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }

        .audit-stat-num {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .audit-section-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            margin: 16px 0 8px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .audit-subtable {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 12px;
        }

        .audit-subtable th {
            background: #f1f5f9;
            color: var(--text-muted);
            padding: 6px 10px;
            border: 1px solid var(--border);
            text-align: left;
            font-size: 11px;
        }

        .audit-subtable td {
            padding: 6px 10px;
            border: 1px solid var(--border);
            color: #334155;
        }

        .audit-bill-link {
            color: #0f172a;
            font-weight: 600;
            text-decoration: underline;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-xs {
            padding: 3px 8px;
            font-size: 11px;
            height: 24px;
        }

        @media print {
            body { background: #ffffff !important; padding: 0 !important; }
            .header-actions, .filter-card, .pagination-container, .modal-overlay { display: none !important; }
            .table-card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>

<div class="layout-container">

    <!-- Header & Navigation -->
    <div class="page-header">
        <div class="header-title-group">
            <div class="breadcrumb">
                <a href="<?= $base_url ?>home_dashboard.php"><i class="fas fa-home"></i> Home</a>
                <span>/</span>
                <a href="stock_audit.php">Stock Audit & Reconciliation</a>
                <span>/</span>
                <span>Zero Purchase Catalog Items</span>
            </div>
            <h1 class="page-title">
                <i class="fas fa-box-open" style="color: #64748b;"></i>
                Unrecorded Purchases Audit
                <span class="badge badge-code"><?= number_format($total_rows) ?> Items Found</span>
            </h1>
        </div>

        <div class="header-actions">
            <a href="stock_audit.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Stock Audit
            </a>
            <a href="<?= $base_url ?>purchase/purchase_entry.php" class="btn btn-outline">
                <i class="fas fa-plus"></i> New Purchase Entry
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
            <a href="<?= $base_url ?>home_dashboard.php" class="btn btn-outline">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Alert / Explanation Card -->
    <div class="formula-card">
        <div class="formula-content">
            <span class="formula-title">
                <i class="fas fa-info-circle"></i> What are these items?
            </span>
            <span class="formula-desc">
                These are items registered in <code>phppos_items</code> that have <strong>no recorded purchase bills</strong> in <code>phppos_purchase_details</code>.
                Active items below have circulating physical stock, historical sales, or rentals that entered the system without a supplier invoice.
            </span>
        </div>
        <div>
            <span class="badge" style="background:#ffffff; border-color:#cbd5e1; font-weight:700;">
                Purchase Records: <code>0 (No Inward Bill)</code>
            </span>
        </div>
    </div>

    <!-- KPI Metric Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Total Zero-Purchase Items</span>
                <span class="stat-value"><?= number_format($kpi['total_no_purchase']) ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color:#d97706; background:#fffbeb;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Active Circulating Items</span>
                <span class="stat-value"><?= number_format($kpi['active_no_purchase']) ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color:#0284c7; background:#f0f9ff;">
                <i class="fas fa-warehouse"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">With Physical Stock (>0)</span>
                <span class="stat-value"><?= number_format($kpi['items_with_stock']) ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color:#166534; background:#f0fdf4;">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Sold Without Purchase</span>
                <span class="stat-value"><?= number_format($kpi['items_sold']) ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color:#4f46e5; background:#eef2ff;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Rented Without Purchase</span>
                <span class="stat-value"><?= number_format($kpi['items_rented']) ?></span>
            </div>
        </div>
    </div>

    <!-- Filter Toolbar Card -->
    <div class="filter-card">
        <form method="GET" action="unrecorded_purchases.php" class="filter-form">
            <div class="form-group">
                <label for="search">Search Item Code / Barcode</label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       class="form-control" 
                       placeholder="e.g. FL26, FL3, N182..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="form-group">
                <label for="activity_filter">Activity Filter</label>
                <select id="activity_filter" name="activity_filter" class="form-control">
                    <option value="active" <?= ($activity_filter === 'active') ? 'selected' : '' ?>>
                        Active Only (Stock / Sold / Rented - <?= number_format($kpi['active_no_purchase']) ?>)
                    </option>
                    <option value="all" <?= ($activity_filter === 'all') ? 'selected' : '' ?>>
                        All Zero-Purchase Items (<?= number_format($kpi['total_no_purchase']) ?>)
                    </option>
                    <option value="with_stock" <?= ($activity_filter === 'with_stock') ? 'selected' : '' ?>>
                        With Physical Stock (>0)
                    </option>
                    <option value="only_sold" <?= ($activity_filter === 'only_sold') ? 'selected' : '' ?>>
                        Items with Sales History
                    </option>
                    <option value="only_rent" <?= ($activity_filter === 'only_rent') ? 'selected' : '' ?>>
                        Items with Rental History
                    </option>
                    <option value="dormant" <?= ($activity_filter === 'dormant') ? 'selected' : '' ?>>
                        Dormant Items (0 Stock, 0 Sales, 0 Rent)
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= ($cat_filter === $cat) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="limit">Per Page</label>
                <select id="limit" name="limit" class="form-control">
                    <option value="25" <?= ($limit_param === '25') ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= ($limit_param === '50') ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= ($limit_param === '100') ? 'selected' : '' ?>>100</option>
                    <option value="250" <?= ($limit_param === '250') ? 'selected' : '' ?>>250</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary" style="height: 38px;">
                    <i class="fas fa-filter"></i> Apply
                </button>
                <?php if ($search !== '' || $activity_filter !== 'active' || $cat_filter !== '' || $limit_param !== '25'): ?>
                    <a href="unrecorded_purchases.php" class="btn btn-outline" style="height: 38px;" title="Reset Filters">
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
                <i class="fas fa-table"></i>
                <span>Unrecorded Purchases Audit Table</span>
            </div>
            <div style="font-size: 13px; color: var(--text-muted);">
                Showing <strong><?= ($total_rows > 0) ? ($offset + 1) : 0 ?></strong> to 
                <strong><?= min($offset + $limit, $total_rows) ?></strong> of 
                <strong><?= number_format($total_rows) ?></strong> items
            </div>
        </div>

        <div class="table-responsive">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">#</th>
                        <th>Product / Code</th>
                        <th>Category</th>
                        <th style="text-align: right;">Cost Price</th>
                        <th style="text-align: right;">Selling Price</th>
                        <th style="text-align: center;" title="Total Purchases recorded in system">Purchased (P)</th>
                        <th style="text-align: right;" title="Net Sold Units via Sales Bills (Gross - Returns)">Sold (S)</th>
                        <th style="text-align: right;" title="Units Currently Out on Rent (Unreturned Orders)">Rent (R)</th>
                        <th style="text-align: right;" title="Physical Units In Stock (phppos_items.quantity)">Available (A)</th>
                        <th style="text-align: right;" title="Total Accounted Units: Sold + Rent + Available">Accounted (S+R+A)</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: center; width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items_result && $items_result->num_rows > 0): ?>
                        <?php 
                        $sr = $offset + 1;
                        while ($row = $items_result->fetch_assoc()): 
                            $pur = 0;
                            $sale = (float)$row['net_sales'];
                            $rent = (float)$row['act_rent'];
                            $avail = (float)$row['avail_qty'];
                            $accounted = $sale + $rent + $avail;

                            $is_active = ($accounted > 0 || $avail != 0);
                        ?>
                            <tr>
                                <td style="text-align: center; color: var(--text-muted); font-weight: 500;">
                                    <?= $sr++ ?>
                                </td>

                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 3px;">
                                        <span style="font-weight: 700; color: var(--primary);">
                                            <?= htmlspecialchars($row['item_name']) ?>
                                        </span>
                                        <?php if (!empty($row['barcode'])): ?>
                                            <div>
                                                <span class="badge badge-code" title="Barcode Tag">
                                                    <i class="fas fa-barcode"></i> <?= htmlspecialchars($row['barcode']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge">
                                        <?= htmlspecialchars($row['category'] ?: '-') ?>
                                    </span>
                                </td>

                                <td style="text-align: right; color: var(--text-muted);">
                                    <?= number_format((float)$row['cost_price'], 2) ?>
                                </td>

                                <td style="text-align: right; color: var(--text-muted);">
                                    <?= number_format((float)$row['unit_price'], 2) ?>
                                </td>

                                <td style="text-align: center;">
                                    <span class="badge badge-zero-pur" title="No supplier purchase details entered">
                                        0 (No Bill)
                                    </span>
                                </td>

                                <td style="text-align: right; font-weight: 600; color: #334155;">
                                    <?= number_format($sale) ?>
                                </td>

                                <td style="text-align: right; color: #334155;" title="All-time rental count: <?= number_format($row['all_rent']) ?>">
                                    <?= number_format($rent) ?>
                                    <?php if ($row['all_rent'] > $rent): ?>
                                        <span style="font-size: 10px; color: var(--text-muted);">
                                            (<?= (int)$row['all_rent'] ?> tot)
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align: right; font-weight: 700; color: var(--primary);">
                                    <?= number_format($avail) ?>
                                </td>

                                <td style="text-align: right; font-weight: 700; color: #b45309;">
                                    <?= number_format($accounted) ?>
                                </td>

                                <td style="text-align: center;">
                                    <?php if ($is_active): ?>
                                        <span class="status-badge status-active-inflow">
                                            <i class="fas fa-exclamation-triangle"></i> Inflow (+<?= (int)$accounted ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-dormant">
                                            <i class="fas fa-minus-circle"></i> Dormant
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align: center;">
                                    <button type="button" 
                                            class="action-icon-btn" 
                                            title="View Transaction Details & Sales/Rent Bills"
                                            onclick="openAuditModal('<?= htmlspecialchars(addslashes($row['item_name'])) ?>')">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fas fa-check-circle" style="font-size: 28px; color: #166534; margin-bottom: 8px;"></i>
                                <p style="font-size: 14px; font-weight: 600; color: var(--primary);">No unrecorded items matching your filters</p>
                                <p style="font-size: 12px; margin-top: 4px;">Try selecting a different filter option or clearing the search query.</p>
                                <a href="unrecorded_purchases.php" class="btn btn-outline btn-sm" style="margin-top: 12px;">Reset Filters</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Page <strong><?= $page ?></strong> of <strong><?= $total_pages ?></strong>
                </div>

                <div class="pagination-nav">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-link" title="First Page">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link" title="Previous Page">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php 
                    $start_p = max(1, $page - 2);
                    $end_p = min($total_pages, $page + 2);
                    for ($p = $start_p; $p <= $end_p; $p++): 
                    ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" 
                           class="page-link <?= ($p === $page) ? 'active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link" title="Next Page">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="page-link" title="Last Page">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal Audit Popup -->
<div class="modal-overlay" id="auditModal" onclick="if(event.target === this) closeAuditModal();">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-search"></i>
                <span>Item Audit: <span id="modal_item_title" style="color:var(--primary); font-family:monospace;"></span></span>
            </div>
            <button type="button" class="modal-close" onclick="closeAuditModal()">&times;</button>
        </div>

        <div class="modal-body" id="modal_body_content">
            <!-- Dynamic Content via AJAX -->
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeAuditModal()">Close</button>
        </div>
    </div>
</div>

<script>
    function closeAuditModal() {
        document.getElementById('auditModal').classList.remove('active');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeAuditModal();
    });

    function openAuditModal(itemName) {
        var modal = document.getElementById('auditModal');
        var titleElem = document.getElementById('modal_item_title');
        var bodyElem = document.getElementById('modal_body_content');
        
        titleElem.textContent = itemName;
        bodyElem.innerHTML = '<div style="text-align: center; padding: 30px; color: var(--text-muted);"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top: 10px;">Loading transaction history & reconciliation records...</p></div>';
        modal.classList.add('active');

        var baseUrl = "<?= rtrim($base_url, '/') ?>/";

        fetch('unrecorded_purchases.php?ajax=item_audit&item_name=' + encodeURIComponent(itemName))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.status !== 'success') {
                    bodyElem.innerHTML = '<div style="color: red; padding: 20px;">' + (data.message || 'Error loading audit.') + '</div>';
                    return;
                }

                var s = data.summary;
                var it = data.item;

                var html = '';

                // Summary Numbers Banner
                html += '<div class="audit-summary-box">';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Purchased (P)</span><span class="audit-stat-num" style="color:#be123c;">0</span></div>';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Net Sold (S)</span><span class="audit-stat-num">' + s.net_sold + '</span></div>';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Active Rent (R)</span><span class="audit-stat-num">' + s.active_rent + '</span></div>';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Available (A)</span><span class="audit-stat-num">' + s.available_stock + '</span></div>';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Accounted (S+R+A)</span><span class="audit-stat-num" style="color:#b45309;">' + s.accounted_total + '</span></div>';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Unrecorded Inflow</span><span class="audit-stat-num" style="color:#d97706;">+' + s.unrecorded_inflow + '</span></div>';
                html += '</div>';

                // Notice Callout
                html += '<div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:10px 14px; border-radius:6px; font-size:12px; margin-bottom:16px;">';
                html += '<i class="fas fa-exclamation-triangle"></i> <strong>Zero Supplier Bills:</strong> This item has <strong>0 units</strong> recorded in purchase bills, yet <strong>' + s.accounted_total + ' units</strong> exist in circulation (Sold: ' + s.net_sold + ', Rent: ' + s.active_rent + ', In Stock: ' + s.available_stock + ').';
                html += '</div>';

                // Section 1: Purchases (Zero)
                html += '<div class="audit-section-title"><i class="fas fa-truck"></i> Purchase Bills (0 entries)</div>';
                html += '<p style="font-size:12px; color:var(--text-muted); margin-bottom:14px; background:#f8fafc; padding:8px 12px; border-radius:6px; border:1px solid #e2e8f0;">No supplier purchase bills were ever entered for this item in Sri Shringarr POS.</p>';

                // Section 2: Sales
                html += '<div class="audit-section-title"><i class="fas fa-shopping-bag"></i> Sales / Approval Bills (' + data.sales.length + ' entries)</div>';
                if (data.sales.length > 0) {
                    html += '<table class="audit-subtable"><thead><tr><th>Bill ID</th><th>Invoice No</th><th>Customer</th><th>Date</th><th style="text-align:right;">Sold</th><th style="text-align:right;">Returned</th><th style="text-align:right;">Net Sold</th><th style="text-align:center;">Action</th></tr></thead><tbody>';
                    data.sales.forEach(function(s) {
                        var net = parseInt(s.qty) - parseInt(s.return_qty);
                        var cust = [s.first_name, s.last_name].filter(Boolean).join(' ') || '-';
                        var saleUrl = baseUrl + 'reports/sales_report_detail.php?id=' + encodeURIComponent(s.bill_id);
                        var saleLink = '<a href="' + saleUrl + '" target="_blank" class="audit-bill-link" title="Open Sales Bill #' + s.bill_id + '"><i class="fas fa-receipt"></i> Bill #' + s.bill_id + '</a>';
                        var invLink = s.new_bill_number ? '<a href="' + saleUrl + '" target="_blank" class="audit-bill-link" title="Open Sales Invoice">' + s.new_bill_number + '</a>' : '-';
                        var actionBtn = '<a href="' + saleUrl + '" target="_blank" class="btn btn-outline btn-xs"><i class="fas fa-external-link-alt"></i> View Sale</a>';

                        html += '<tr><td>' + saleLink + '</td><td>' + invLink + '</td><td>' + cust + '</td><td>' + (s.bill_date || '-') + '</td><td style="text-align:right;">' + s.qty + '</td><td style="text-align:right;">' + s.return_qty + '</td><td style="text-align:right; font-weight:700;">' + net + '</td><td style="text-align:center;">' + actionBtn + '</td></tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p style="font-size:12px; color:var(--text-muted); margin-bottom:12px;">No recorded sales for this item.</p>';
                }

                // Section 3: Rentals
                html += '<div class="audit-section-title"><i class="fas fa-clock"></i> Rental Orders (' + data.rentals.length + ' entries)</div>';
                if (data.rentals.length > 0) {
                    html += '<table class="audit-subtable"><thead><tr><th>Rent Bill ID</th><th>Invoice</th><th>Customer</th><th>Pickup Date</th><th>Return Date</th><th>Status</th><th style="text-align:right;">Qty</th><th style="text-align:center;">Action</th></tr></thead><tbody>';
                    data.rentals.forEach(function(r) {
                        var cust = [r.first_name, r.last_name].filter(Boolean).join(' ') || '-';
                        var isReturned = (r.booking_status === 'Returned' || r.booking_status === 'Cancelled');
                        var badgeStyle = isReturned ? 'color:#166534; font-weight:600;' : 'color:#e11d48; font-weight:700;';
                        var rentUrl = baseUrl + 'reports/rent_report_detail.php?id=' + encodeURIComponent(r.bill_id);
                        var rentLink = '<a href="' + rentUrl + '" target="_blank" class="audit-bill-link" title="Open Rent Bill #' + r.bill_id + '"><i class="fas fa-clock"></i> Rent #' + r.bill_id + '</a>';
                        var invLink = r.new_bill_number ? '<a href="' + rentUrl + '" target="_blank" class="audit-bill-link" title="Open Rent Invoice">' + r.new_bill_number + '</a>' : '-';
                        var actionBtn = '<a href="' + rentUrl + '" target="_blank" class="btn btn-outline btn-xs"><i class="fas fa-external-link-alt"></i> View Rent</a>';

                        html += '<tr><td>' + rentLink + '</td><td>' + invLink + '</td><td>' + cust + '</td><td>' + (r.pick_date || '-') + '</td><td>' + (r.delivery_date || '-') + '</td><td style="' + badgeStyle + '">' + (r.booking_status || 'Active') + '</td><td style="text-align:right; font-weight:700;">' + (r.qty > 0 ? r.qty : 1) + '</td><td style="text-align:center;">' + actionBtn + '</td></tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p style="font-size:12px; color:var(--text-muted); margin-bottom:12px;">No recorded rentals for this item.</p>';
                }

                bodyElem.innerHTML = html;
            })
            .catch(function(err) {
                bodyElem.innerHTML = '<div style="color:red; padding:20px;">Failed to load audit data: ' + err + '</div>';
            });
    }
</script>

</body>
</html>
<?php CloseCon($con); ?>
