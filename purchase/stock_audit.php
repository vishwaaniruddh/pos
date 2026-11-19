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

    // Purchases
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

    // Stock Report Purchase (from pos/reports/stock.php -> gettotalPurchase)
    $stk_pur_q = mysqli_query($con, "SELECT SUM(qty) as totalQty FROM phppos_purchase_details WHERE item_id = '{$item['item_id']}'");
    $stk_pur_row = mysqli_fetch_assoc($stk_pur_q);
    $stock_report_pur = (float)($stk_pur_row['totalQty'] ?? 0);
    $mod_diff = abs($total_pur - $stock_report_pur);

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
    $discrepancy = $total_pur - $accounted;

    echo json_encode([
        "status" => "success",
        "item" => $item,
        "summary" => [
            "total_purchased" => $total_pur,
            "stock_report_purchased" => $stock_report_pur,
            "mod_diff" => $mod_diff,
            "gross_sold" => $total_sold,
            "sales_returned" => $total_returned,
            "net_sold" => $net_sold,
            "active_rent" => $active_rent,
            "all_rent_occurrences" => $all_rent,
            "available_stock" => $avail,
            "accounted_total" => $accounted,
            "discrepancy" => $discrepancy,
            "is_matched" => ($discrepancy == 0)
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
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : 'mismatch'; // default: purchase != sales+rent+avail
$cat_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$limit_param = isset($_GET['limit']) ? trim($_GET['limit']) : '25';
$limit = ($limit_param === 'all') ? 999999 : (int)$limit_param;
if ($limit <= 0) $limit = 25;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// ---------------------------------------------------------------------
// 3. Dropdown Options (Categories & Suppliers)
// ---------------------------------------------------------------------
$categories_res = mysqli_query($con, "SELECT DISTINCT category FROM categories WHERE category != '' ORDER BY category ASC");
$category_list = [];
while ($crow = mysqli_fetch_assoc($categories_res)) {
    $category_list[] = $crow['category'];
}

// ---------------------------------------------------------------------
// 4. Construct SQL Query for Reconciliation
// ---------------------------------------------------------------------
$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $search_like = "%" . $search . "%";
    $where_clauses[] = "(i.name LIKE ? OR i.item_number LIKE ?)";
    $types .= "ss";
    $params[] = $search_like;
    $params[] = $search_like;
}

if ($cat_filter !== '') {
    $where_clauses[] = "i.category = ?";
    $types .= "s";
    $params[] = $cat_filter;
}

// Discrepancy Status Having Clause:
// Purchase = COALESCE(pur.total_pur, 0)
// Accounted = COALESCE(sale.net_sales, 0) + COALESCE(rent.act_rent, 0) + i.quantity
// Diff = Purchase - Accounted
$having_clause = "";
if ($status_filter === 'mismatch') {
    $having_clause = "HAVING (COALESCE(total_pur, 0) != (COALESCE(net_sales, 0) + COALESCE(act_rent, 0) + avail_qty))";
} elseif ($status_filter === 'shortage') {
    $having_clause = "HAVING (COALESCE(total_pur, 0) > (COALESCE(net_sales, 0) + COALESCE(act_rent, 0) + avail_qty))";
} elseif ($status_filter === 'surplus') {
    $having_clause = "HAVING (COALESCE(total_pur, 0) < (COALESCE(net_sales, 0) + COALESCE(act_rent, 0) + avail_qty))";
} elseif ($status_filter === 'balanced') {
    $having_clause = "HAVING (COALESCE(total_pur, 0) = (COALESCE(net_sales, 0) + COALESCE(act_rent, 0) + avail_qty))";
}

$where_sql = implode(" AND ", $where_clauses);

// Base subquery aggregation
$base_query = "
    FROM phppos_items i
    INNER JOIN (
        SELECT item_id, SUM(qty) AS total_pur 
        FROM phppos_purchase_details 
        GROUP BY item_id
    ) pur ON i.item_id = pur.item_id
    LEFT JOIN (
        SELECT item_id, SUM(qty) AS total_stock_pur 
        FROM phppos_purchase_details 
        GROUP BY item_id
    ) stk_pur ON i.item_id = stk_pur.item_id
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
            COALESCE(pur.total_pur, 0) AS total_pur,
            COALESCE(stk_pur.total_stock_pur, 0) AS stock_report_pur,
            COALESCE(sale.net_sales, 0) AS net_sales,
            COALESCE(rent.act_rent, 0) AS act_rent,
            COALESCE(rent.all_rent, 0) AS all_rent
        {$base_query}
        {$having_clause}
        ORDER BY ABS(COALESCE(pur.total_pur, 0) - (COALESCE(sale.net_sales, 0) + COALESCE(rent.act_rent, 0) + i.quantity)) DESC
    ";

    $stmt_exp = $con->prepare($export_sql);
    if (!empty($params)) {
        $stmt_exp->bind_param($types, ...$params);
    }
    $stmt_exp->execute();
    $exp_res = $stmt_exp->get_result();

    $filename = "stock_reconciliation_audit_" . date('Y-m-d_His') . ".csv";
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
        'Stock Report Purchase',
        'Difference in Mod (|P - Stock Report|)',
        'Sales Qty (S)',
        'Active Rent Qty (R)',
        'Available in Stock (A)',
        'Accounted Qty (S+R+A)',
        'Discrepancy (P - (S+R+A))',
        'Status'
    ]);

    while ($r = $exp_res->fetch_assoc()) {
        $pur = (float)$r['total_pur'];
        $stock_report_pur = (float)$r['stock_report_pur'];
        $mod_diff = abs($pur - $stock_report_pur);
        $sale = (float)$r['net_sales'];
        $rent = (float)$r['act_rent'];
        $avail = (float)$r['avail_qty'];
        $accounted = $sale + $rent + $avail;
        $diff = $pur - $accounted;

        $status = ($diff == 0) ? 'Balanced' : (($diff > 0) ? "Shortage ({$diff})" : "Surplus (" . abs($diff) . ")");

        fputcsv($out, [
            $r['item_name'],
            $r['barcode'],
            $r['category'],
            $r['cost_price'],
            $r['unit_price'],
            $pur,
            $stock_report_pur,
            $mod_diff,
            $sale,
            $rent,
            $avail,
            $accounted,
            $diff,
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
            COALESCE(pur.total_pur, 0) AS total_pur,
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
        COALESCE(pur.total_pur, 0) AS total_pur,
        COALESCE(stk_pur.total_stock_pur, 0) AS stock_report_pur,
        COALESCE(sale.net_sales, 0) AS net_sales,
        COALESCE(rent.act_rent, 0) AS act_rent,
        COALESCE(rent.all_rent, 0) AS all_rent
    {$base_query}
    {$having_clause}
    ORDER BY ABS(COALESCE(pur.total_pur, 0) - (COALESCE(sale.net_sales, 0) + COALESCE(rent.act_rent, 0) + i.quantity)) DESC, i.name ASC
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
// 8. Global Reconciliation KPI Summary
// ---------------------------------------------------------------------
$kpi_query = "
    SELECT 
        COUNT(CASE WHEN (COALESCE(pur.total_pur, 0) != (COALESCE(sale.net_sales, 0) + COALESCE(rent.act_rent, 0) + i.quantity)) THEN 1 END) AS total_mismatches,
        COUNT(CASE WHEN (COALESCE(pur.total_pur, 0) > (COALESCE(sale.net_sales, 0) + COALESCE(rent.act_rent, 0) + i.quantity)) THEN 1 END) AS total_shortages,
        COUNT(CASE WHEN (COALESCE(pur.total_pur, 0) < (COALESCE(sale.net_sales, 0) + COALESCE(rent.act_rent, 0) + i.quantity)) THEN 1 END) AS total_surpluses,
        COUNT(CASE WHEN (COALESCE(pur.total_pur, 0) = (COALESCE(sale.net_sales, 0) + COALESCE(rent.act_rent, 0) + i.quantity)) THEN 1 END) AS total_balanced
    FROM phppos_items i
    INNER JOIN (
        SELECT item_id, SUM(qty) AS total_pur 
        FROM phppos_purchase_details 
        GROUP BY item_id
    ) pur ON i.item_id = pur.item_id
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
";
$kpi_res = mysqli_query($con, $kpi_query);
$kpi = mysqli_fetch_assoc($kpi_res) ?: [
    'total_mismatches' => 0,
    'total_shortages' => 0,
    'total_surpluses' => 0,
    'total_balanced' => 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Audit & Reconciliation | Sri Shringarr POS</title>
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
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .layout-container {
            max-width: 1420px;
            margin: 0 auto;
            padding: 24px 28px;
        }

        /* Page Header */
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

        /* Shadcn UI Buttons */
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

        /* Formula Banner */
        .formula-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-left: 4px solid var(--primary);
            border-radius: var(--radius);
            padding: 14px 18px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .formula-content {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .formula-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .formula-equation {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .formula-desc {
            font-size: 12px;
            color: var(--text-muted);
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

        /* Filters Card */
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
            grid-template-columns: 2fr 1.5fr 1.2fr 100px auto;
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

        table.audit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }

        table.audit-table th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.audit-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            color: #334155;
        }

        table.audit-table tbody tr:hover {
            background-color: #fbfcfe;
        }

        .item-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .item-sku {
            font-weight: 700;
            color: var(--primary);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .item-meta {
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px;
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
        }

        /* Status Pills (Subtle, Muted) */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-badge.status-shortage {
            background-color: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger-text);
        }

        .status-badge.status-surplus {
            background-color: var(--warning-bg);
            border: 1px solid var(--warning-border);
            color: var(--warning-text);
        }

        .status-badge.status-balanced {
            background-color: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
        }

        .diff-tag {
            font-weight: 700;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
        }

        .diff-negative {
            color: #dc2626;
        }

        .diff-positive {
            color: #d97706;
        }

        .diff-zero {
            color: #166534;
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
            cursor: pointer;
            font-size: 12px;
            transition: all 0.15s ease;
        }

        .action-icon-btn:hover {
            background-color: #f8fafc;
            color: #0f172a;
            border-color: #cbd5e1;
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

        /* Modal Styles */
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
            max-width: 840px;
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

        /* Modal Audit Tabs & Sections */
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

        .btn-xs {
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            gap: 4px;
        }

        .audit-bill-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px;
            border-radius: 4px;
            background: #f1f5f9;
            border: 1px solid var(--border);
            transition: all 0.15s ease;
            white-space: nowrap;
            font-size: 11px;
        }

        .audit-bill-link:hover {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
            text-decoration: none;
        }

        @media print {
            .header-actions, .filter-card, .pagination-bar, .action-icon-btn, .formula-card, .modal-overlay {
                display: none !important;
            }
            body { background: #ffffff !important; }
            .table-card { border: none !important; box-shadow: none !important; }
            table.audit-table { border: 1px solid #000000 !important; font-size: 9pt !important; }
            table.audit-table th, table.audit-table td { border: 1px solid #cccccc !important; }
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
                <span>Reports & Audit</span>
                <span>/</span>
                <span>Stock Audit & Discrepancies</span>
            </div>
            <h1 class="page-title">
                Stock Audit & Reconciliation
                <span class="badge badge-code"><?= number_format($total_rows) ?> Items Found</span>
            </h1>
        </div>

        <div class="header-actions">
            <a href="unrecorded_purchases.php" class="btn btn-outline" style="border-color: #fecdd3; background: #fff1f2; color: #be123c; font-weight: 600;" title="View 3,573 items in catalog with no supplier purchase bills (3,042 active in stock/sales/rent)">
                <i class="fas fa-exclamation-triangle"></i> Zero Purchase Items (3,573)
            </a>
            <a href="<?= $base_url ?>purchase/purchase_entry.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Purchase Entry
            </a>
            <a href="<?= $base_url ?>purchase/view_bills.php" class="btn btn-outline">
                <i class="fas fa-file-invoice"></i> View Bills
            </a>
            <a href="<?= $base_url ?>purchase/view_supplier.php" class="btn btn-outline">
                <i class="fas fa-truck"></i> Suppliers
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
            <a href="<?= $base_url ?>home_dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Reconciliation Formula Explanation Card -->
    <div class="formula-card">
        <div class="formula-content">
            <span class="formula-title">
                <i class="fas fa-balance-scale"></i> Inventory Reconciliation Rule
            </span>
            <div style="margin: 4px 0;">
                <span class="formula-equation">Purchase Qty (P) = Net Sales (S) + Active On-Rent (R) + Available in Stock (A)</span>
            </div>
            <span class="formula-desc">
                Flags any item where <strong>Purchase &ne; Sales + Rent + Available</strong>. Shortage indicates missing units, while Surplus indicates accounted units exceeding recorded purchases.
            </span>
        </div>
        <div>
            <span class="badge" style="background:#ffffff; border-color:#cbd5e1; font-weight:700;">
                Physical Stock Source: <code>phppos_items.quantity</code>
            </span>
            <a href="unrecorded_purchases.php" class="badge" style="background:#fff1f2; border-color:#fecdd3; color:#be123c; font-weight:700; text-decoration:none; margin-left:6px;" title="View items with zero purchase bills">
                <i class="fas fa-boxes"></i> 3,573 Zero-Purchase Items &rarr;
            </a>
        </div>
    </div>

    <!-- KPI Metric Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Total Mismatched Items</span>
                <span class="stat-value"><?= number_format($kpi['total_mismatches']) ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-arrow-circle-down"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Shortage Items (Missing)</span>
                <span class="stat-value" style="color: #e11d48;"><?= number_format($kpi['total_shortages']) ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-arrow-circle-up"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Surplus Items (Excess)</span>
                <span class="stat-value" style="color: #d97706;"><?= number_format($kpi['total_surpluses']) ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Balanced Items (100% Match)</span>
                <span class="stat-value" style="color: #166534;"><?= number_format($kpi['total_balanced']) ?></span>
            </div>
        </div>
    </div>

    <!-- Filter Toolbar Card -->
    <div class="filter-card">
        <form method="GET" action="" class="filter-form">
            <div class="form-group">
                <label for="search">Search Item Code / Barcode</label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       class="form-control" 
                       placeholder="e.g. N182, 7W5, FM4766-1..." 
                       value="<?= htmlspecialchars($search) ?>" 
                       autocomplete="off">
            </div>

            <div class="form-group">
                <label for="status_filter">Reconciliation Status</label>
                <select id="status_filter" name="status_filter" class="form-control">
                    <option value="mismatch" <?= ($status_filter === 'mismatch') ? 'selected' : '' ?>>
                        All Mismatches (Purchase != S + R + A)
                    </option>
                    <option value="shortage" <?= ($status_filter === 'shortage') ? 'selected' : '' ?>>
                        Shortages Only (Purchased > S + R + A)
                    </option>
                    <option value="surplus" <?= ($status_filter === 'surplus') ? 'selected' : '' ?>>
                        Surplus Only (Purchased < S + R + A)
                    </option>
                    <option value="balanced" <?= ($status_filter === 'balanced') ? 'selected' : '' ?>>
                        Balanced Only (Purchase == S + R + A)
                    </option>
                    <option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>
                        All Inventory Items
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($category_list as $cat): ?>
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
                <?php if ($search !== '' || $status_filter !== 'mismatch' || $cat_filter !== '' || $limit_param !== '25'): ?>
                    <a href="stock_audit.php" class="btn btn-outline" style="height: 38px;" title="Reset Filters">
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
                <span>Reconciliation Audit Table</span>
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
                        <th style="text-align: right;" title="Total Units Purchased via Supplier Bills">Purchased (P)</th>
                        <th style="text-align: right;" title="Total Purchase reported in Stock Report (stock.php)">Stock Report Purchase</th>
                        <th style="text-align: right;" title="Absolute Difference: |Purchased (P) - Stock Report Purchase|">Difference in Mod</th>
                        <th style="text-align: right;" title="Net Sold Units via Sales Bills (Gross - Returns)">Sold (S)</th>
                        <th style="text-align: right;" title="Units Currently Out on Rent (Unreturned Orders)">Rent (R)</th>
                        <th style="text-align: right;" title="Physical Units In Stock (phppos_items.quantity)">Available (A)</th>
                        <th style="text-align: right;" title="Total Accounted Units: Sold + Rent + Available">Accounted (S+R+A)</th>
                        <th style="text-align: right;" title="Discrepancy: Purchased - Accounted">Difference</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: center; width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items_result && $items_result->num_rows > 0): ?>
                        <?php 
                        $sr = $offset + 1;
                        while ($row = $items_result->fetch_assoc()): 
                            $pur = (float)$row['total_pur'];
                            $stock_report_pur = (float)$row['stock_report_pur'];
                            $mod_diff = abs($pur - $stock_report_pur);
                            $sale = (float)$row['net_sales'];
                            $rent = (float)$row['act_rent'];
                            $avail = (float)$row['avail_qty'];
                            $accounted = $sale + $rent + $avail;
                            $diff = $pur - $accounted;

                            if ($diff == 0) {
                                $status_class = 'status-balanced';
                                $status_text = 'Balanced';
                                $diff_class = 'diff-zero';
                                $status_icon = 'fa-check';
                            } elseif ($diff > 0) {
                                $status_class = 'status-shortage';
                                $status_text = 'Shortage (' . $diff . ')';
                                $diff_class = 'diff-negative';
                                $status_icon = 'fa-exclamation-circle';
                            } else {
                                $status_class = 'status-surplus';
                                $status_text = 'Surplus (' . abs($diff) . ')';
                                $diff_class = 'diff-positive';
                                $status_icon = 'fa-plus-circle';
                            }
                        ?>
                            <tr>
                                <td style="text-align: center; color: var(--text-muted); font-weight: 500;">
                                    <?= $sr++ ?>
                                </td>

                                <td>
                                    <div class="item-cell">
                                        <span class="item-sku">
                                            <?= htmlspecialchars($row['item_name']) ?>
                                        </span>
                                        <div class="item-meta">
                                            <?php if (!empty($row['barcode'])): ?>
                                                <span class="badge badge-code" title="Barcode Tag">
                                                    <i class="fas fa-barcode"></i> <?= htmlspecialchars($row['barcode']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge">
                                        <?= htmlspecialchars($row['category'] ?: '-') ?>
                                    </span>
                                </td>

                                <td style="text-align: right; font-weight: 600;">
                                    <?= number_format($pur) ?>
                                </td>

                                <td style="text-align: right; font-weight: 600; color: #334155;" title="From stock.php: Total Purchase">
                                    <?= number_format($stock_report_pur) ?>
                                </td>

                                <td style="text-align: right;">
                                    <?php if ($mod_diff == 0): ?>
                                        <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 600; border: 1px solid #e2e8f0;" title="|<?= $pur ?> - <?= $stock_report_pur ?>| = 0">0</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; font-weight: 700;" title="|<?= $pur ?> - <?= $stock_report_pur ?>| = <?= $mod_diff ?>">
                                            ±<?= number_format($mod_diff) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align: right; color: #334155;">
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

                                <td style="text-align: right; font-weight: 600; color: #475569;">
                                    <?= number_format($accounted) ?>
                                </td>

                                <td style="text-align: right;">
                                    <span class="diff-tag <?= $diff_class ?>">
                                        <?= ($diff > 0) ? '+' . $diff : $diff ?>
                                    </span>
                                </td>

                                <td style="text-align: center;">
                                    <span class="status-badge <?= $status_class ?>">
                                        <i class="fas <?= $status_icon ?>"></i> <?= $status_text ?>
                                    </span>
                                </td>

                                <td style="text-align: center;">
                                    <button type="button" 
                                            class="action-icon-btn" 
                                            title="View Complete Item Audit & Transaction Bills"
                                            onclick="openAuditModal('<?= htmlspecialchars(addslashes($row['item_name'])) ?>')">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11">
                                <div class="empty-state">
                                    <i class="fas fa-check-circle" style="color: #166534;"></i>
                                    <h3 style="color: var(--primary); font-size: 16px; margin-bottom: 6px;">No Discrepancies Found</h3>
                                    <p>All items matching your active filter criteria are perfectly balanced (Purchase == Sales + Rent + Available).</p>
                                    <div style="margin-top: 14px;">
                                        <a href="stock_audit.php?status_filter=all" class="btn btn-outline btn-sm">
                                            View All Items
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-bar">
                <div class="pagination-info">
                    Page <strong><?= $page ?></strong> of <strong><?= $total_pages ?></strong>
                </div>

                <div class="pagination-controls">
                    <?php 
                    $qp = $_GET;
                    $qp['page'] = 1;
                    $first_url = '?' . http_build_query($qp);
                    $qp['page'] = max(1, $page - 1);
                    $prev_url = '?' . http_build_query($qp);
                    ?>
                    <a href="<?= $first_url ?>" class="page-link <?= ($page <= 1) ? 'disabled' : '' ?>" title="First Page">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="<?= $prev_url ?>" class="page-link <?= ($page <= 1) ? 'disabled' : '' ?>" title="Previous Page">
                        <i class="fas fa-angle-left"></i>
                    </a>

                    <?php
                    $start_p = max(1, $page - 2);
                    $end_p = min($total_pages, $page + 2);
                    for ($p = $start_p; $p <= $end_p; $p++):
                        $qp['page'] = $p;
                        $page_url = '?' . http_build_query($qp);
                    ?>
                        <a href="<?= $page_url ?>" class="page-link <?= ($p === $page) ? 'active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php
                    $qp['page'] = min($total_pages, $page + 1);
                    $next_url = '?' . http_build_query($qp);
                    $qp['page'] = $total_pages;
                    $last_url = '?' . http_build_query($qp);
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
<!-- MODAL: ITEM RECONCILIATION AUDIT BREAKDOWN                          -->
<!-- =================================================================== -->
<div class="modal-overlay" id="auditModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-search-plus"></i> Item Audit: <span id="modal_item_title" style="color: var(--primary); font-family: monospace;">-</span>
            </h3>
            <button type="button" class="modal-close" onclick="closeModal('auditModal')">&times;</button>
        </div>
        <div class="modal-body" id="modal_body_content">
            <div style="text-align: center; padding: 30px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top: 10px;">Loading transaction history & reconciliation records...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('auditModal')">Close</button>
        </div>
    </div>
</div>

<script>
    function closeModal(id) {
        var modal = document.getElementById(id);
        if (modal) modal.classList.remove('active');
    }

    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
                m.classList.remove('active');
            });
        }
    });

    function openAuditModal(itemName) {
        var modal = document.getElementById('auditModal');
        var titleElem = document.getElementById('modal_item_title');
        var bodyElem = document.getElementById('modal_body_content');
        
        titleElem.textContent = itemName;
        bodyElem.innerHTML = '<div style="text-align: center; padding: 30px; color: var(--text-muted);"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top: 10px;">Loading transaction history & reconciliation records...</p></div>';
        modal.classList.add('active');

        var baseUrl = "<?= rtrim($base_url, '/') ?>/";

        fetch('stock_audit.php?ajax=item_audit&item_name=' + encodeURIComponent(itemName))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.status !== 'success') {
                    bodyElem.innerHTML = '<div style="color: red; padding: 20px;">' + (data.message || 'Error loading audit.') + '</div>';
                    return;
                }

                var s = data.summary;
                var it = data.item;

                var diffText = s.discrepancy === 0 ? 'Balanced (0)' : (s.discrepancy > 0 ? 'Shortage (-' + s.discrepancy + ')' : 'Surplus (+' + Math.abs(s.discrepancy) + ')');
                var diffColor = s.discrepancy === 0 ? '#166534' : (s.discrepancy > 0 ? '#e11d48' : '#d97706');

                var html = '';

                // Summary Numbers Banner
                html += '<div class="audit-summary-box">';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Purchased (P)</span><span class="audit-stat-num">' + s.total_purchased + '</span></div>';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Stock Report (P)</span><span class="audit-stat-num">' + (s.stock_report_purchased !== undefined ? s.stock_report_purchased : s.total_purchased) + '</span></div>';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Mod Diff</span><span class="audit-stat-num" style="color:' + (s.mod_diff == 0 ? '#166534' : '#e11d48') + '">' + (s.mod_diff !== undefined ? s.mod_diff : 0) + '</span></div>';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Net Sold (S)</span><span class="audit-stat-num">' + s.net_sold + '</span></div>';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Active Rent (R)</span><span class="audit-stat-num">' + s.active_rent + '</span></div>';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Available (A)</span><span class="audit-stat-num">' + s.available_stock + '</span></div>';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Accounted (S+R+A)</span><span class="audit-stat-num">' + s.accounted_total + '</span></div>';
                html += '<div class="audit-stat-item"><span class="audit-stat-label">Discrepancy</span><span class="audit-stat-num" style="color:' + diffColor + '">' + (s.discrepancy > 0 ? '+' : '') + s.discrepancy + '</span></div>';
                html += '</div>';

                // Equation Callout
                html += '<div style="background:#f1f5f9; padding:8px 12px; border-radius:6px; font-size:12px; margin-bottom:16px;">';
                html += '<strong>Reconciliation:</strong> ' + s.total_purchased + ' (Purchased) vs [' + s.net_sold + ' (Sold) + ' + s.active_rent + ' (Rent) + ' + s.available_stock + ' (In Stock)] = <strong>' + diffText + '</strong>';
                html += '</div>';

                // Section 1: Purchases
                html += '<div class="audit-section-title"><i class="fas fa-truck"></i> Purchase Bills (' + data.purchases.length + ' entries)</div>';
                if (data.purchases.length > 0) {
                    html += '<table class="audit-subtable"><thead><tr><th>Pur ID</th><th>Bill No</th><th>Supplier</th><th>Date</th><th style="text-align:right;">Cost Price</th><th style="text-align:right;">Qty</th><th style="text-align:center;">Action</th></tr></thead><tbody>';
                    data.purchases.forEach(function(p) {
                        var purUrl = baseUrl + 'purchase/view_bill.php?bill_id=' + encodeURIComponent(p.pur_id);
                        var purLink = '<a href="' + purUrl + '" target="_blank" class="audit-bill-link" title="Open Purchase Bill #' + p.pur_id + '"><i class="fas fa-file-invoice"></i> Pur #' + p.pur_id + '</a>';
                        var timelineBtn = p.supp_id ? ' <a href="' + baseUrl + 'purchase/Timeline/bill_his.php?sup_id=' + encodeURIComponent(p.supp_id) + '&bill_id=' + encodeURIComponent(p.pur_id) + '&link=detail" target="_blank" class="audit-bill-link" title="Supplier Timeline Bill History"><i class="fas fa-history"></i> Timeline</a>' : '';
                        var billNoDisplay = (p.bill_id && p.bill_id !== '-') ? '<a href="' + purUrl + '" target="_blank" class="audit-bill-link" title="Open Purchase Bill">' + p.bill_id + '</a>' : '-';
                        var actionBtn = '<a href="' + purUrl + '" target="_blank" class="btn btn-outline btn-xs"><i class="fas fa-external-link-alt"></i> View Bill</a>';

                        html += '<tr><td>' + purLink + timelineBtn + '</td><td>' + billNoDisplay + '</td><td>' + (p.company_name || '-') + '</td><td>' + (p.bill_date || '-') + '</td><td style="text-align:right;">' + p.price + '</td><td style="text-align:right; font-weight:700;">' + p.qty + '</td><td style="text-align:center;">' + actionBtn + '</td></tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p style="font-size:12px; color:var(--text-muted); margin-bottom:12px;">No recorded purchase bills for this item.</p>';
                }

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
                        var invLink = r.new_bill_number ? '<a href="../reports/rent_report_detail.php?id=' + encodeURIComponent(r.bill_id) + '" target="_blank" class="audit-bill-link" title="Open Rent Invoice">' + r.new_bill_number + '</a>' : '-';
                        var actionBtn = '<a href="../reports/rent_report_detail.php?id=' + encodeURIComponent(r.bill_id) + '" target="_blank" class="btn btn-outline btn-xs"><i class="fas fa-external-link-alt"></i> View Rent</a>';

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

