<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

$posBaseUrl = function_exists('get_pos_base_url') ? rtrim(get_pos_base_url(), '/') : '/pos';

// Read & sanitize filter parameters
$q        = trim((string)($_GET['q'] ?? ''));
$letter   = strtoupper(trim((string)($_GET['letter'] ?? $_GET['alphabets'] ?? '')));
if (!preg_match('/^[A-Z]$/', $letter)) {
    $letter = '';
}
$phone    = trim((string)($_GET['phone'] ?? ''));
$city     = trim((string)($_GET['city'] ?? ''));
$type     = trim((string)($_GET['type'] ?? 'all'));
$allowedTypes = ['all', 'customers_only', 'with_bills', 'website'];
if (!in_array($type, $allowedTypes, true)) {
    $type = 'all';
}
$contact  = trim((string)($_GET['contact'] ?? ''));
$allowedContacts = ['', 'has_phone', 'missing_phone', 'has_email', 'has_dob'];
if (!in_array($contact, $allowedContacts, true)) {
    $contact = '';
}
$fromDate = trim((string)($_GET['from_date'] ?? ''));
$toDate   = trim((string)($_GET['to_date'] ?? ''));
if ($fromDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
    $fromDate = '';
}
if ($toDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
    $toDate = '';
}

$sort = trim((string)($_GET['sort'] ?? 'newest'));
$sortMap = [
    'newest'    => 'p.person_id DESC',
    'oldest'    => 'p.person_id ASC',
    'name_asc'  => "NULLIF(TRIM(p.first_name), '') IS NULL ASC, p.first_name ASC, p.last_name ASC, p.person_id DESC",
    'name_desc' => "p.first_name DESC, p.last_name DESC, p.person_id DESC",
];
if (!isset($sortMap[$sort])) {
    $sort = 'newest';
}
$orderBySql = $sortMap[$sort];

// Strictly enforce max 50 records per page
$allowedLimits = [10, 25, 50];
$limitRaw = (int)($_GET['limit'] ?? 50);
$limit = min(50, max(10, $limitRaw));
if (!in_array($limit, $allowedLimits, true)) {
    $limit = 50;
}
$page = max(1, (int)($_GET['page'] ?? 1));

// Build parameterized WHERE clauses
$where = [
    "NOT EXISTS (SELECT 1 FROM phppos_suppliers s WHERE s.person_id = p.person_id)"
];
$paramTypes = '';
$paramValues = [];

if ($q !== '') {
    $wild = '%' . $q . '%';
    if (ctype_digit($q)) {
        $where[] = "(p.person_id = ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR CONCAT_WS(' ', p.first_name, p.last_name) LIKE ? OR p.phone_number LIKE ? OR p.email LIKE ? OR p.city LIKE ? OR p.address_1 LIKE ? OR p.address_2 LIKE ?)";
        $paramTypes .= 'issssssss';
        $paramValues[] = (int)$q;
        array_push($paramValues, $wild, $wild, $wild, $wild, $wild, $wild, $wild, $wild);
    } else {
        $where[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR CONCAT_WS(' ', p.first_name, p.last_name) LIKE ? OR p.phone_number LIKE ? OR p.email LIKE ? OR p.city LIKE ? OR p.address_1 LIKE ? OR p.address_2 LIKE ?)";
        $paramTypes .= 'ssssssss';
        array_push($paramValues, $wild, $wild, $wild, $wild, $wild, $wild, $wild, $wild);
    }
}

if ($letter !== '') {
    $where[] = "p.first_name LIKE ?";
    $paramTypes .= 's';
    $paramValues[] = $letter . '%';
}

if ($phone !== '') {
    $phoneDigits = preg_replace('/[^0-9]/', '', $phone);
    if ($phoneDigits !== '') {
        $where[] = "(p.phone_number LIKE ? OR REPLACE(REPLACE(REPLACE(REPLACE(p.phone_number, ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?)";
        $paramTypes .= 'ss';
        $paramValues[] = '%' . $phone . '%';
        $paramValues[] = '%' . $phoneDigits . '%';
    } else {
        $where[] = "p.phone_number LIKE ?";
        $paramTypes .= 's';
        $paramValues[] = '%' . $phone . '%';
    }
}

if ($city !== '') {
    $where[] = "(p.city LIKE ? OR p.state LIKE ? OR p.address_1 LIKE ?)";
    $paramTypes .= 'sss';
    $cityWild = '%' . $city . '%';
    array_push($paramValues, $cityWild, $cityWild, $cityWild);
}

if ($type === 'customers_only') {
    $where[] = "EXISTS (SELECT 1 FROM phppos_customers c WHERE c.person_id = p.person_id)";
} elseif ($type === 'with_bills') {
    $where[] = "(EXISTS (SELECT 1 FROM approval ab WHERE ab.cust_id = p.person_id) OR EXISTS (SELECT 1 FROM phppos_rent rb WHERE rb.cust_id = p.person_id))";
} elseif ($type === 'website') {
    $where[] = "(p.comments LIKE '%Website%' OR EXISTS (SELECT 1 FROM approval aw WHERE aw.cust_id = p.person_id AND aw.bill_prepare_by LIKE '%Website%'))";
}

if ($contact === 'has_phone') {
    $where[] = "(p.phone_number IS NOT NULL AND TRIM(p.phone_number) != '')";
} elseif ($contact === 'missing_phone') {
    $where[] = "(p.phone_number IS NULL OR TRIM(p.phone_number) = '')";
} elseif ($contact === 'has_email') {
    $where[] = "(p.email IS NOT NULL AND TRIM(p.email) != '')";
} elseif ($contact === 'has_dob') {
    $where[] = "(p.dob IS NOT NULL AND p.dob != '0000-00-00' AND p.dob != '1970-01-01')";
}

if ($fromDate !== '' || $toDate !== '') {
    $appCond = ["ab.cust_id = p.person_id"];
    $rentCond = ["rb.cust_id = p.person_id"];
    $dateTypes = '';
    $dateVals = [];

    if ($fromDate !== '') {
        $appCond[] = "ab.bill_date >= ?";
        $dateTypes .= 's';
        $dateVals[] = $fromDate;
    }
    if ($toDate !== '') {
        $appCond[] = "ab.bill_date <= ?";
        $dateTypes .= 's';
        $dateVals[] = $toDate;
    }
    if ($fromDate !== '') {
        $rentCond[] = "rb.bill_date >= ?";
        $dateTypes .= 's';
        $dateVals[] = $fromDate;
    }
    if ($toDate !== '') {
        $rentCond[] = "rb.bill_date <= ?";
        $dateTypes .= 's';
        $dateVals[] = $toDate;
    }

    $where[] = "(EXISTS (SELECT 1 FROM approval ab WHERE " . implode(' AND ', $appCond) . ") OR EXISTS (SELECT 1 FROM phppos_rent rb WHERE " . implode(' AND ', $rentCond) . "))";
    $paramTypes .= $dateTypes;
    $paramValues = array_merge($paramValues, $dateVals);
}

$whereSql = implode(' AND ', $where);

if (!function_exists('bind_dynamic_params')) {
    /**
     * Binds dynamic typed parameters to a prepared statement safely.
     */
    function bind_dynamic_params($stmt, $types, array $values) {
        if ($types !== '' && !empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
    }
}

// Optional CSV Export of matching filtered rows (up to 5,000 rows)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pos_customers_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'First Name', 'Last Name', 'Mobile No', 'Email', 'Address 1', 'Address 2', 'City', 'State', 'Zip', 'DOB', 'Comments']);

    $csvSql = "SELECT p.person_id, p.first_name, p.last_name, p.phone_number, p.email, p.address_1, p.address_2, p.city, p.state, p.zip, p.dob, p.comments
               FROM phppos_people p
               WHERE {$whereSql}
               ORDER BY {$orderBySql}
               LIMIT 5000";
    $csvStmt = $con->prepare($csvSql);
    bind_dynamic_params($csvStmt, $paramTypes, $paramValues);
    $csvStmt->execute();
    $csvRes = $csvStmt->get_result();
    while ($r = $csvRes->fetch_assoc()) {
        fputcsv($out, [
            $r['person_id'],
            $r['first_name'],
            $r['last_name'],
            $r['phone_number'],
            $r['email'],
            $r['address_1'],
            $r['address_2'],
            $r['city'],
            $r['state'],
            $r['zip'],
            ($r['dob'] && $r['dob'] !== '0000-00-00') ? $r['dob'] : '',
            $r['comments'],
        ]);
    }
    fclose($out);
    CloseCon($con);
    exit;
}

// Total matching rows count
$countSql = "SELECT COUNT(*) AS total FROM phppos_people p WHERE {$whereSql}";
$countStmt = $con->prepare($countSql);
bind_dynamic_params($countStmt, $paramTypes, $paramValues);
$countStmt->execute();
$totalRows = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$totalPages = max(1, (int)ceil($totalRows / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $limit;

// Fetch current page rows (max 50)
$dataSql = "SELECT p.person_id, p.first_name, p.last_name, p.phone_number, p.email,
                   p.address_1, p.address_2, p.city, p.state, p.zip, p.country, p.dob, p.comments,
                   EXISTS(SELECT 1 FROM phppos_customers c WHERE c.person_id = p.person_id) AS is_customer
            FROM phppos_people p
            WHERE {$whereSql}
            ORDER BY {$orderBySql}
            LIMIT ? OFFSET ?";
$dataStmt = $con->prepare($dataSql);
$dataTypes = $paramTypes . 'ii';
$dataParams = array_merge($paramValues, [$limit, $offset]);
bind_dynamic_params($dataStmt, $dataTypes, $dataParams);
$dataStmt->execute();
$customers = $dataStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$dataStmt->close();

// Batch load sales/quotation & rent counts for ONLY the <= 50 rows currently displayed
$billStats = [];
if (!empty($customers)) {
    $ids = array_values(array_unique(array_map(function ($row) {
        return (int)$row['person_id'];
    }, $customers)));
    $idList = implode(',', $ids);

    $appRes = mysqli_query($con, "SELECT cust_id, COUNT(*) AS cnt, MAX(bill_date) AS last_dt FROM approval WHERE cust_id IN ({$idList}) GROUP BY cust_id");
    while ($appRes && ($ar = mysqli_fetch_assoc($appRes))) {
        $cidKey = (int)$ar['cust_id'];
        $billStats[$cidKey]['sales'] = (int)$ar['cnt'];
        $billStats[$cidKey]['last_dt'] = $ar['last_dt'];
    }

    $rentRes = mysqli_query($con, "SELECT cust_id, COUNT(*) AS cnt, MAX(bill_date) AS last_dt FROM phppos_rent WHERE cust_id IN ({$idList}) GROUP BY cust_id");
    while ($rentRes && ($rr = mysqli_fetch_assoc($rentRes))) {
        $cidKey = (int)$rr['cust_id'];
        $billStats[$cidKey]['rent'] = (int)$rr['cnt'];
        $existingDt = $billStats[$cidKey]['last_dt'] ?? '';
        if (!empty($rr['last_dt']) && $rr['last_dt'] > $existingDt) {
            $billStats[$cidKey]['last_dt'] = $rr['last_dt'];
        }
    }
}

// Top cities for filter suggestions
$topCities = [];
$cityRes = mysqli_query($con, "SELECT TRIM(city) AS c_name FROM phppos_people WHERE city IS NOT NULL AND TRIM(city) != '' AND TRIM(city) NOT IN ('0','1','-') GROUP BY TRIM(city) ORDER BY COUNT(*) DESC LIMIT 40");
while ($cityRes && ($cr = mysqli_fetch_assoc($cityRes))) {
    $topCities[] = $cr['c_name'];
}

$baseQueryArr = [
    'q'         => $q,
    'letter'    => $letter,
    'phone'     => $phone,
    'city'      => $city,
    'type'      => $type,
    'contact'   => $contact,
    'from_date' => $fromDate,
    'to_date'   => $toDate,
    'sort'      => $sort,
    'limit'     => $limit,
];
$queryWithoutEmpty = array_filter($baseQueryArr, function ($v, $k) {
    if ($k === 'limit' && (int)$v === 50) return false;
    if ($k === 'type' && $v === 'all') return false;
    if ($k === 'sort' && $v === 'newest') return false;
    return $v !== '' && $v !== null;
}, ARRAY_FILTER_USE_BOTH);
$baseQueryString = http_build_query($queryWithoutEmpty);

$activeFiltersCount = 0;
foreach ([$q, $letter, $phone, $city, $contact, $fromDate, $toDate] as $fVal) {
    if ($fVal !== '') {
        $activeFiltersCount++;
    }
}
if ($type !== 'all') $activeFiltersCount++;

$startRec = $totalRows > 0 ? ($offset + 1) : 0;
$endRec   = min($totalRows, $offset + count($customers));

$flashMsg = trim((string)($_GET['msg'] ?? ''));
$flashType = trim((string)($_GET['status'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Directory | POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --primary-hover: #1e293b;
            --accent: #0284c7;
            --background: #f8fafc;
            --card: #ffffff;
            --border: #cbd5e1;
            --border-light: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #475569;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --success: #047857;
            --success-bg: #ecfdf5;
        }
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--background);
            color: var(--text-main);
            margin: 0;
            padding: 20px;
            line-height: 1.45;
        }
        .visually-hidden:where(:not(:focus-within, :active)) {
            position: absolute !important;
            clip-path: inset(50%) !important;
            overflow: hidden !important;
            width: 1px !important;
            height: 1px !important;
            margin: -1px !important;
            padding: 0 !important;
            border: 0 !important;
            white-space: nowrap !important;
        }
        :where(a, button, input, select, .table-wrapper):focus-visible {
            outline: 3px solid var(--accent);
            outline-offset: 2px;
        }
        .container {
            max-width: 1380px;
            margin: 0 auto;
        }
        .top-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }
        .top-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }
        .subtitle {
            margin: 3px 0 0 0;
            font-size: 13px;
            color: var(--text-muted);
        }
        .header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 40px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 7px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }
        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-outline {
            background: #ffffff;
            color: var(--primary);
            border-color: var(--border);
        }
        .btn-outline:hover { background: #f1f5f9; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-card {
            background: var(--card);
            border: 1px solid var(--border-light);
            border-radius: 10px;
            padding: 14px 16px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .stat-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
        }
        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            margin-top: 4px;
        }
        .stat-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .alert-banner {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-success {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid #fecaca;
        }
        .filter-card {
            background: var(--card);
            border: 1px solid var(--border-light);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
            gap: 12px;
            align-items: end;
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .field label {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .field input,
        .field select {
            font: inherit;
            font-size: max(0.875rem, 14px);
            min-height: 40px;
            padding: 7px 10px;
            border: 1px solid #94a3b8;
            border-radius: 6px;
            background: #ffffff;
            color: var(--text-main);
            width: 100%;
        }
        .filter-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid var(--border-light);
        }
        .filter-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
            font-size: 12px;
            color: var(--text-muted);
        }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
            font-weight: 600;
        }
        .table-card {
            background: var(--card);
            border: 1px solid var(--border-light);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .table-wrapper {
            overflow: auto;
            max-inline-size: 100%;
            max-block-size: min(680px, 75vh);
            container-type: inline-size;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }
        thead {
            position: sticky;
            inset-block-start: 0;
            z-index: 2;
        }
        th, td {
            padding: 11px 12px;
            border-bottom: 1px solid var(--border-light);
            text-align: left;
            vertical-align: top;
        }
        thead th {
            background: #f1f5f9;
            color: var(--primary);
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
            border-bottom: 1px solid var(--border);
        }
        tbody th {
            position: sticky;
            inset-inline-start: 0;
            z-index: 1;
            background: #ffffff;
            font-weight: 700;
            color: var(--primary);
            min-width: 170px;
            border-right: 1px solid #f1f5f9;
        }
        tbody tr:hover td,
        tbody tr:hover th {
            background-color: #f8fafc;
        }
        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
        }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 5px;
            border: 1px solid var(--border-light);
            background: #f8fafc;
            color: var(--text-muted);
            white-space: nowrap;
        }
        .badge-web {
            background: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        .badge-sales {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }
        .badge-rent {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }
        .action-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            min-block-size: 32px;
            min-inline-size: 32px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--primary);
        }
        .action-btn:hover { background: #f1f5f9; }
        .action-edit {
            color: #0369a1;
            border-color: #bae6fd;
            background: #f0f9ff;
        }
        .action-edit:hover { background: #e0f2fe; }
        .action-delete {
            color: var(--danger);
            border-color: #fecaca;
            background: var(--danger-bg);
        }
        .action-delete:hover {
            background: var(--danger);
            color: #ffffff;
            border-color: var(--danger);
        }
        .pagination-bar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: #f8fafc;
            border-top: 1px solid var(--border-light);
        }
        .pagination-links {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            align-items: center;
        }
        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-inline-size: 34px;
            min-block-size: 34px;
            padding: 5px 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: #ffffff;
            color: var(--primary);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
        .page-link:hover:not(.is-disabled):not(.is-active) { background: #e2e8f0; }
        .page-link.is-active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
        .page-link.is-disabled {
            opacity: 0.45;
            pointer-events: none;
        }

        /* Responsive stacked card layout on narrow viewports/containers */
        @container (width < 720px) {
            .table-wrapper {
                max-block-size: none;
            }
            table, thead, tbody, tr, th, td {
                display: block;
            }
            thead {
                display: none;
            }
            tbody tr {
                margin: 10px 10px 14px 10px;
                border: 1px solid var(--border);
                border-radius: 8px;
                background: #ffffff;
                overflow: hidden;
            }
            tbody th {
                position: sticky;
                inset-block-start: 0;
                z-index: 2;
                background: #f1f5f9;
                border-right: none;
                border-bottom: 1px solid var(--border);
            }
            tbody td {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 12px;
                text-align: right;
                padding: 8px 12px;
            }
            table {
                --label-2: "Sr. No.";
                --label-3: "ID";
                --label-4: "Mobile";
                --label-5: "Email";
                --label-6: "Address / Location";
                --label-7: "DOB";
                --label-8: "POS Activity";
                --label-9: "Actions";
            }
            tbody td:nth-child(2)::before { content: var(--label-2) ": "; font-weight: 700; color: var(--text-muted); text-align: left; }
            tbody td:nth-child(3)::before { content: var(--label-3) ": "; font-weight: 700; color: var(--text-muted); text-align: left; }
            tbody td:nth-child(4)::before { content: var(--label-4) ": "; font-weight: 700; color: var(--text-muted); text-align: left; }
            tbody td:nth-child(5)::before { content: var(--label-5) ": "; font-weight: 700; color: var(--text-muted); text-align: left; }
            tbody td:nth-child(6)::before { content: var(--label-6) ": "; font-weight: 700; color: var(--text-muted); text-align: left; }
            tbody td:nth-child(7)::before { content: var(--label-7) ": "; font-weight: 700; color: var(--text-muted); text-align: left; }
            tbody td:nth-child(8)::before { content: var(--label-8) ": "; font-weight: 700; color: var(--text-muted); text-align: left; }
            tbody td:nth-child(9)::before { content: var(--label-9) ": "; font-weight: 700; color: var(--text-muted); text-align: left; }
        }

        @media print {
            body { background: #ffffff; padding: 0; }
            .no-print { display: none !important; }
            .table-card { border: none; box-shadow: none; }
            .table-wrapper { max-block-size: none; overflow: visible; }
            tbody th { position: static; }
        }
    </style>
</head>
<body>

<main class="container">
    <div class="top-header">
        <div>
            <h1>Customer Directory</h1>
            <p class="subtitle">Search, filter, and manage POS customers (maximum 50 records per page)</p>
        </div>
        <div class="header-actions no-print">
            <a href="people.php?mode=customerList" class="btn btn-primary">
                <i class="fas fa-user-plus" aria-hidden="true"></i> New Customer
            </a>
            <a href="?<?= htmlspecialchars($baseQueryString ? $baseQueryString . '&export=csv' : 'export=csv') ?>" class="btn btn-outline">
                <i class="fas fa-file-csv" aria-hidden="true"></i> Export CSV
            </a>
            <button type="button" id="btnPrintDirectory" class="btn btn-outline">
                <i class="fas fa-print" aria-hidden="true"></i> Print
            </button>
            <a href="<?= htmlspecialchars($posBaseUrl) ?>/home_dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($flashMsg !== ''): ?>
        <div role="status" class="alert-banner <?= $flashType === 'error' ? 'alert-error' : 'alert-success' ?>">
            <i class="fas <?= $flashType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' ?>" aria-hidden="true"></i>
            <span><?= htmlspecialchars($flashMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- KPI Overview -->
    <div class="stats-grid no-print">
        <div class="stat-card">
            <div class="stat-label">Matching Customers</div>
            <div class="stat-value"><?= number_format($totalRows) ?></div>
            <div class="stat-sub">Non-supplier records matching filters</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Showing Records</div>
            <div class="stat-value"><?= $startRec ?>&ndash;<?= $endRec ?></div>
            <div class="stat-sub">Page <?= $page ?> of <?= number_format($totalPages) ?> (max <?= $limit ?>/page)</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Record Scope</div>
            <div class="stat-value">
                <?php
                $scopeLabels = [
                    'all'            => 'All Directory',
                    'customers_only' => 'Registered Only',
                    'with_bills'     => 'With Bills',
                    'website'        => 'Website Online',
                ];
                echo htmlspecialchars($scopeLabels[$type] ?? 'All Directory');
                ?>
            </div>
            <div class="stat-sub">Active filter conditions: <?= $activeFiltersCount ?></div>
        </div>
    </div>

    <!-- Multi-Filter Form -->
    <div class="filter-card no-print">
        <search>
            <form method="GET" action="custLst.php" aria-labelledby="customer-filter-heading">
                <h2 id="customer-filter-heading" class="visually-hidden">Filter Customers</h2>

                <div class="filter-grid">
                    <div class="field">
                        <label for="filter-q">Search Name / ID / Email / Address</label>
                        <input type="search" id="filter-q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="e.g. Sobha, 243953, Mumbai" autocorrect="off" autocapitalize="none" spellcheck="false">
                    </div>

                    <div class="field">
                        <label for="filter-phone">Mobile Number</label>
                        <input type="text" id="filter-phone" name="phone" value="<?= htmlspecialchars($phone) ?>" inputmode="tel" placeholder="e.g. 9495611164" autocorrect="off" autocapitalize="none" spellcheck="false">
                    </div>

                    <div class="field">
                        <label for="filter-city">City / State / Area</label>
                        <input type="text" id="filter-city" name="city" list="city-suggestions" value="<?= htmlspecialchars($city) ?>" placeholder="e.g. Mumbai, Delhi" autocorrect="off" autocapitalize="none" spellcheck="false">
                        <datalist id="city-suggestions">
                            <?php foreach ($topCities as $cityOpt): ?>
                                <option value="<?= htmlspecialchars($cityOpt) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="field">
                        <label for="filter-letter">Name Starts With (A&ndash;Z)</label>
                        <select id="filter-letter" name="letter">
                            <option value="">All Letters</option>
                            <?php foreach (range('A', 'Z') as $ch): ?>
                                <option value="<?= $ch ?>" <?= ($letter === $ch) ? 'selected' : '' ?>><?= $ch ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="filter-type">Customer Scope</label>
                        <select id="filter-type" name="type">
                            <option value="all" <?= ($type === 'all') ? 'selected' : '' ?>>All Non-Suppliers</option>
                            <option value="customers_only" <?= ($type === 'customers_only') ? 'selected' : '' ?>>Registered Customers Only</option>
                            <option value="with_bills" <?= ($type === 'with_bills') ? 'selected' : '' ?>>With Sales / Rent Bills</option>
                            <option value="website" <?= ($type === 'website') ? 'selected' : '' ?>>Website (YN) Customers</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="filter-contact">Contact Filter</label>
                        <select id="filter-contact" name="contact">
                            <option value="" <?= ($contact === '') ? 'selected' : '' ?>>Any Contact Info</option>
                            <option value="has_phone" <?= ($contact === 'has_phone') ? 'selected' : '' ?>>Has Mobile Number</option>
                            <option value="missing_phone" <?= ($contact === 'missing_phone') ? 'selected' : '' ?>>Missing Mobile Number</option>
                            <option value="has_email" <?= ($contact === 'has_email') ? 'selected' : '' ?>>Has Email Address</option>
                            <option value="has_dob" <?= ($contact === 'has_dob') ? 'selected' : '' ?>>Has Birth Date (DOB)</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="filter-from-date">Billed From Date</label>
                        <input type="date" id="filter-from-date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>">
                    </div>

                    <div class="field">
                        <label for="filter-to-date">Billed To Date</label>
                        <input type="date" id="filter-to-date" name="to_date" value="<?= htmlspecialchars($toDate) ?>">
                    </div>

                    <div class="field">
                        <label for="filter-sort">Sort Order</label>
                        <select id="filter-sort" name="sort">
                            <option value="newest" <?= ($sort === 'newest') ? 'selected' : '' ?>>Newest ID First</option>
                            <option value="oldest" <?= ($sort === 'oldest') ? 'selected' : '' ?>>Oldest ID First</option>
                            <option value="name_asc" <?= ($sort === 'name_asc') ? 'selected' : '' ?>>Name (A &rarr; Z)</option>
                            <option value="name_desc" <?= ($sort === 'name_desc') ? 'selected' : '' ?>>Name (Z &rarr; A)</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="filter-limit">Records Per Page (Max 50)</label>
                        <select id="filter-limit" name="limit">
                            <option value="50" <?= ($limit === 50) ? 'selected' : '' ?>>50 per page (Max)</option>
                            <option value="25" <?= ($limit === 25) ? 'selected' : '' ?>>25 per page</option>
                            <option value="10" <?= ($limit === 10) ? 'selected' : '' ?>>10 per page</option>
                        </select>
                    </div>
                </div>

                <div class="filter-footer">
                    <div class="filter-chips">
                        <?php if ($activeFiltersCount === 0): ?>
                            <span>Showing full directory sorted by <?= $sort === 'newest' ? 'newest first' : htmlspecialchars($sort) ?>.</span>
                        <?php else: ?>
                            <span>Active filters:</span>
                            <?php if ($q !== ''): ?><span class="chip">Search: <?= htmlspecialchars($q) ?></span><?php endif; ?>
                            <?php if ($phone !== ''): ?><span class="chip">Phone: <?= htmlspecialchars($phone) ?></span><?php endif; ?>
                            <?php if ($city !== ''): ?><span class="chip">City: <?= htmlspecialchars($city) ?></span><?php endif; ?>
                            <?php if ($letter !== ''): ?><span class="chip">Letter: <?= htmlspecialchars($letter) ?></span><?php endif; ?>
                            <?php if ($type !== 'all'): ?><span class="chip">Scope: <?= htmlspecialchars($scopeLabels[$type] ?? $type) ?></span><?php endif; ?>
                            <?php if ($contact !== ''): ?><span class="chip">Contact: <?= htmlspecialchars($contact) ?></span><?php endif; ?>
                            <?php if ($fromDate !== '' || $toDate !== ''): ?><span class="chip">Bills: <?= htmlspecialchars($fromDate ?: '...') ?> to <?= htmlspecialchars($toDate ?: '...') ?></span><?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <a href="custLst.php" class="btn btn-outline">
                            <i class="fas fa-undo" aria-hidden="true"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter" aria-hidden="true"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </search>
    </div>

    <!-- Results Table Card -->
    <div class="table-card" id="bill">
        <div tabindex="0" role="region" aria-labelledby="customer-table-caption" class="table-wrapper">
            <table>
                <caption id="customer-table-caption" class="visually-hidden">
                    Customer directory showing <?= $startRec ?> to <?= $endRec ?> of <?= number_format($totalRows) ?> matching customers
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Customer Name</th>
                        <th scope="col">Sr. No.</th>
                        <th scope="col">ID</th>
                        <th scope="col">Mobile No.</th>
                        <th scope="col">Email</th>
                        <th scope="col">Address &amp; Location</th>
                        <th scope="col">DOB</th>
                        <th scope="col">POS Activity</th>
                        <th scope="col" class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:36px; color:var(--text-muted);">
                                No customers found matching your filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $idx => $row):
                            $pid = (int)$row['person_id'];
                            $srNo = $offset + $idx + 1;
                            $firstName = trim((string)($row['first_name'] ?? ''));
                            $lastName  = trim((string)($row['last_name'] ?? ''));
                            $fullName  = trim($firstName . ' ' . $lastName);
                            if ($fullName === '') {
                                $fullName = 'Unnamed Customer #' . $pid;
                            }

                            $addrParts = array_filter([
                                trim((string)($row['address_1'] ?? '')),
                                trim((string)($row['address_2'] ?? '')),
                                trim((string)($row['city'] ?? '')),
                                trim((string)($row['state'] ?? '')),
                                trim((string)($row['zip'] ?? '')),
                            ]);
                            $addrDisplay = !empty($addrParts) ? implode(', ', $addrParts) : ($lastName !== '' ? $lastName : '—');

                            $dobRaw = (string)($row['dob'] ?? '');
                            $dobDisplay = ($dobRaw !== '' && $dobRaw !== '0000-00-00' && $dobRaw !== '1970-01-01')
                                ? date('d/m/Y', strtotime($dobRaw))
                                : '—';

                            $salesCnt = $billStats[$pid]['sales'] ?? 0;
                            $rentCnt  = $billStats[$pid]['rent'] ?? 0;
                            $lastBill = $billStats[$pid]['last_dt'] ?? '';
                            $isWebCustomer = stripos((string)($row['comments'] ?? ''), 'Website') !== false;
                        ?>
                        <tr>
                            <th scope="row">
                                <div><?= htmlspecialchars($fullName) ?></div>
                                <?php if ($isWebCustomer): ?>
                                    <span class="badge badge-web" style="margin-top:3px;">Website YN</span>
                                <?php elseif (!empty($row['is_customer'])): ?>
                                    <span class="badge" style="margin-top:3px;">Customer</span>
                                <?php endif; ?>
                            </th>
                            <td><?= $srNo ?></td>
                            <td class="mono">#<?= $pid ?></td>
                            <td class="mono"><?= htmlspecialchars(trim((string)($row['phone_number'] ?? '')) ?: '—') ?></td>
                            <td><?= htmlspecialchars(trim((string)($row['email'] ?? '')) ?: '—') ?></td>
                            <td style="max-width: 280px;"><?= htmlspecialchars($addrDisplay) ?></td>
                            <td style="white-space:nowrap;"><?= htmlspecialchars($dobDisplay) ?></td>
                            <td>
                                <div style="display:flex; flex-direction:column; gap:3px; align-items:flex-start;">
                                    <?php if ($salesCnt > 0): ?>
                                        <a href="saleReport.php?cid=<?= $pid ?>&from=&to_date=&invno=&submit=Search" class="badge badge-sales" style="text-decoration:none;">
                                            Sales/App: <?= $salesCnt ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($rentCnt > 0): ?>
                                        <span class="badge badge-rent">Rent: <?= $rentCnt ?></span>
                                    <?php endif; ?>
                                    <?php if ($salesCnt === 0 && $rentCnt === 0): ?>
                                        <span style="color:var(--text-muted); font-size:12px;">No bills yet</span>
                                    <?php elseif ($lastBill !== '' && $lastBill !== '0000-00-00'): ?>
                                        <span style="font-size:11px; color:var(--text-muted);">Last: <?= htmlspecialchars(date('d/m/Y', strtotime($lastBill))) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="no-print">
                                <div class="action-group">
                                    <a href="custEdit.php?id=<?= $pid ?>" class="action-btn action-edit" aria-label="Edit <?= htmlspecialchars($fullName) ?>">
                                        <i class="fas fa-edit" aria-hidden="true"></i> Edit
                                    </a>
                                    <form method="POST" action="custDel.php" class="js-delete-customer-form" data-customer-name="<?= htmlspecialchars($fullName) ?>" style="margin:0;">
                                        <input type="hidden" name="id" value="<?= $pid ?>">
                                        <button type="submit" class="action-btn action-delete" aria-label="Delete <?= htmlspecialchars($fullName) ?>">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <?php if ($totalRows > 0): ?>
            <div class="pagination-bar no-print">
                <div style="font-size:13px; color:var(--text-muted); font-weight:500;">
                    Showing <strong><?= $startRec ?>&ndash;<?= $endRec ?></strong> of <strong><?= number_format($totalRows) ?></strong> records
                    (Page <strong><?= $page ?></strong> of <strong><?= number_format($totalPages) ?></strong>)
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination-links" aria-label="Customer directory pages">
                        <?php
                        $buildPageUrl = function ($targetPage) use ($baseQueryString) {
                            return '?' . ($baseQueryString ? $baseQueryString . '&' : '') . 'page=' . (int)$targetPage;
                        };
                        ?>
                        <a href="<?= htmlspecialchars($buildPageUrl(1)) ?>" class="page-link <?= ($page <= 1) ? 'is-disabled' : '' ?>" <?= ($page <= 1) ? 'aria-disabled="true" tabindex="-1"' : '' ?>>First</a>
                        <a href="<?= htmlspecialchars($buildPageUrl(max(1, $page - 1))) ?>" class="page-link <?= ($page <= 1) ? 'is-disabled' : '' ?>" <?= ($page <= 1) ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Prev</a>

                        <?php
                        $windowStart = max(1, $page - 2);
                        $windowEnd   = min($totalPages, $page + 2);
                        if ($windowStart > 1) {
                            echo '<a href="' . htmlspecialchars($buildPageUrl(1)) . '" class="page-link">1</a>';
                            if ($windowStart > 2) echo '<span style="padding:0 4px; color:var(--text-muted);">&hellip;</span>';
                        }
                        for ($pNum = $windowStart; $pNum <= $windowEnd; $pNum++):
                        ?>
                            <a href="<?= htmlspecialchars($buildPageUrl($pNum)) ?>" class="page-link <?= ($pNum === $page) ? 'is-active' : '' ?>" <?= ($pNum === $page) ? 'aria-current="page"' : '' ?>>
                                <?= $pNum ?>
                            </a>
                        <?php
                        endfor;
                        if ($windowEnd < $totalPages) {
                            if ($windowEnd < $totalPages - 1) echo '<span style="padding:0 4px; color:var(--text-muted);">&hellip;</span>';
                            echo '<a href="' . htmlspecialchars($buildPageUrl($totalPages)) . '" class="page-link">' . number_format($totalPages) . '</a>';
                        }
                        ?>

                        <a href="<?= htmlspecialchars($buildPageUrl(min($totalPages, $page + 1))) ?>" class="page-link <?= ($page >= $totalPages) ? 'is-disabled' : '' ?>" <?= ($page >= $totalPages) ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Next</a>
                        <a href="<?= htmlspecialchars($buildPageUrl($totalPages)) ?>" class="page-link <?= ($page >= $totalPages) ? 'is-disabled' : '' ?>" <?= ($page >= $totalPages) ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Last (<?= number_format($totalPages) ?>)</a>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const printBtn = document.getElementById('btnPrintDirectory');
    if (printBtn) {
        printBtn.addEventListener('click', () => window.print());
    }

    document.querySelectorAll('.js-delete-customer-form').forEach((form) => {
        form.addEventListener('submit', (e) => {
            const custName = form.getAttribute('data-customer-name') || 'this customer';
            if (!window.confirm(`Are you sure you want to delete "${custName}"?`)) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php CloseCon($con); ?>
</body>
</html>
