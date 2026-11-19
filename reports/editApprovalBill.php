<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../db_connection.php');
$con = OpenSrishringarrCon();

// Pagination variables
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Search variables
$searchBillId = isset($_GET['bill_id']) ? trim($_GET['bill_id']) : '';
$searchStartDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$searchEndDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$searchCustomer = isset($_GET['customer']) ? trim($_GET['customer']) : '';

// Build the query with search filters
$query = "
    SELECT a.bill_id, a.cust_id, a.bill_date, a.paid_amount, a.amountTotal, a.new_bill_number, 
           a.company_name, a.bill_prepare_by, a.pay_by,
           p.first_name, p.last_name, p.phone_number
    FROM approval a
    LEFT JOIN phppos_people p ON a.cust_id = p.person_id
    WHERE 1=1
";

$paramTypes = "";
$paramValues = [];

// Search by Bill ID or New Bill Number
if ($searchBillId !== '') {
    $query .= " AND (a.bill_id = ? OR a.new_bill_number LIKE ?)";
    $paramTypes .= "ss";
    $paramValues[] = $searchBillId;
    $paramValues[] = "%" . $searchBillId . "%";
}

// Search by Date Range
if ($searchStartDate !== '' && $searchEndDate !== '') {
    $query .= " AND a.bill_date BETWEEN ? AND ?";
    $paramTypes .= "ss";
    $paramValues[] = $searchStartDate;
    $paramValues[] = $searchEndDate;
} elseif ($searchStartDate !== '') {
    $query .= " AND a.bill_date >= ?";
    $paramTypes .= "ss";
    $paramValues[] = $searchStartDate;
} elseif ($searchEndDate !== '') {
    $query .= " AND a.bill_date <= ?";
    $paramTypes .= "ss";
    $paramValues[] = $searchEndDate;
}

// Search by Customer Name or Phone
if ($searchCustomer !== '') {
    $query .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.phone_number LIKE ?)";
    $paramTypes .= "sss";
    $searchWildcard = "%" . $searchCustomer . "%";
    $paramValues[] = $searchWildcard;
    $paramValues[] = $searchWildcard;
    $paramValues[] = $searchWildcard;
}

// Add ordering and pagination
$query .= " ORDER BY a.bill_id DESC LIMIT ? OFFSET ?";
$paramTypes .= "ii";
$paramValues[] = $limit;
$paramValues[] = $offset;

$stmt = $con->prepare($query);
if (!empty($paramValues)) {
    $stmt->bind_param($paramTypes, ...$paramValues);
}
$stmt->execute();
$result = $stmt->get_result();

// Count total rows for pagination
$totalQuery = "
    SELECT COUNT(*) as total 
    FROM approval a 
    LEFT JOIN phppos_people p ON a.cust_id = p.person_id 
    WHERE 1=1
";
$totalParamTypes = "";
$totalParamValues = [];

if ($searchBillId !== '') {
    $totalQuery .= " AND (a.bill_id = ? OR a.new_bill_number LIKE ?)";
    $totalParamTypes .= "ss";
    $totalParamValues[] = $searchBillId;
    $totalParamValues[] = "%" . $searchBillId . "%";
}
if ($searchStartDate !== '' && $searchEndDate !== '') {
    $totalQuery .= " AND a.bill_date BETWEEN ? AND ?";
    $totalParamTypes .= "ss";
    $totalParamValues[] = $searchStartDate;
    $totalParamValues[] = $searchEndDate;
}
if ($searchCustomer !== '') {
    $totalQuery .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.phone_number LIKE ?)";
    $totalParamTypes .= "sss";
    $totalParamValues[] = $searchWildcard;
    $totalParamValues[] = $searchWildcard;
    $totalParamValues[] = $searchWildcard;
}

$totalStmt = $con->prepare($totalQuery);
if (!empty($totalParamValues)) {
    $totalStmt->bind_param($totalParamTypes, ...$totalParamValues);
}
$totalStmt->execute();
$totalRows = $totalStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit / Delete Approval Bills - POS</title>
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
            --danger: #ef4444;
            --danger-hover: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--background);
            color: var(--text-main);
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 1300px;
            margin: 0 auto;
        }
        .top-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        h2 {
            margin: 0;
            color: var(--primary);
            font-size: 22px;
            font-weight: 700;
        }
        .search-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .search-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 120px;
            gap: 12px;
            align-items: flex-end;
        }
        .form-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .form-field label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .form-field input {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 13px;
            outline: none;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-outline {
            background: #ffffff;
            color: #334155;
            border-color: var(--border);
        }
        .btn-outline:hover { background: #f8fafc; }
        .table-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th {
            background: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            text-align: left;
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tr:hover td { background-color: #fbfcfe; }
        .action-links {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        .action-link {
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: #334155;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .action-link:hover { background: #f1f5f9; }
        .action-link.edit-link { color: #0284c7; border-color: #bae6fd; }
        .action-link.edit-link:hover { background: #f0f9ff; }
        .action-link.view-link { color: #0f172a; }
        .btn-delete-bill {
            background: #fff1f2;
            color: #e11d48;
            border: 1px solid #fecdd3;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-delete-bill:hover {
            background: #e11d48;
            color: #ffffff;
        }
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 6px 12px;
            border: 1px solid var(--border);
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            color: #334155;
            background: #ffffff;
        }
        .pagination a.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
        .pagination a:hover:not(.active) { background: #f1f5f9; }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid var(--border);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-header">
        <div>
            <h2>Approval Bill Records</h2>
            <span style="font-size:13px; color:var(--text-muted);">Manage, edit, view, and safely delete approval sales bills</span>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="editRentBill.php" class="btn btn-outline"><i class="fas fa-file-invoice"></i> Rent Bills</a>
            <a href="../ssfs_format_sales_view.php" class="btn btn-outline"><i class="fas fa-table"></i> Sales View</a>
            <a href="/pos/home_dashboard.php" class="btn btn-outline"><i class="fas fa-home"></i> Dashboard</a>
        </div>
    </div>

    <!-- Search / Filter Box -->
    <div class="search-card">
        <form method="GET" action="">
            <div class="search-grid">
                <div class="form-field">
                    <label>Bill ID / Invoice No</label>
                    <input type="text" name="bill_id" placeholder="e.g. APP-26/A-00042 or 123" value="<?= htmlspecialchars($searchBillId) ?>">
                </div>
                <div class="form-field">
                    <label>From Date</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($searchStartDate) ?>">
                </div>
                <div class="form-field">
                    <label>To Date</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($searchEndDate) ?>">
                </div>
                <div class="form-field">
                    <label>Customer Name / Phone</label>
                    <input type="text" name="customer" placeholder="Search customer..." value="<?= htmlspecialchars($searchCustomer) ?>">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="width:100%; height:37px;">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">Sr No</th>
                    <th>Invoice / Bill No</th>
                    <th>Bill Date</th>
                    <th>Customer Name</th>
                    <th>Total Amount</th>
                    <th>Paid Amount</th>
                    <th>Prepared By</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:32px; color:var(--text-muted);">
                            No approval bill records found matching your filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $srno = $offset + 1;
                    while ($row = $result->fetch_assoc()):
                        $bid = $row['bill_id'];
                        $bnum = $row['new_bill_number'] ? $row['new_bill_number'] : $bid;
                        $cname = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                        if ($cname === '') $cname = 'Customer #' . ($row['cust_id'] ?? '-');
                    ?>
                    <tr id="row-bill-<?= $bid ?>">
                        <td><?= $srno ?></td>
                        <td>
                            <strong><a href="editApprovalBillDetails.php?bill_id=<?= $bid ?>" style="color:var(--primary); text-decoration:none;"><?= htmlspecialchars($bnum) ?></a></strong>
                            <div style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($row['company_name'] ?: 'Sri Shringarr') ?></div>
                        </td>
                        <td><?= htmlspecialchars($row['bill_date'] ?? '-') ?></td>
                        <td>
                            <?= htmlspecialchars($cname) ?>
                            <?php if (!empty($row['phone_number'])): ?>
                                <div style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($row['phone_number']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><strong>₹ <?= htmlspecialchars($row['amountTotal'] ?? '0') ?></strong></td>
                        <td>₹ <?= htmlspecialchars($row['paid_amount'] ?? '0') ?> <span class="badge"><?= htmlspecialchars($row['pay_by'] ?? 'Cash') ?></span></td>
                        <td><?= htmlspecialchars($row['bill_prepare_by'] ?? '-') ?></td>
                        <td style="text-align: center;">
                            <div class="action-links" style="justify-content:center;">
                                <a href="sales_report_detail.php?id=<?= $bid ?>" target="_blank" class="action-link view-link" title="View Full Invoice">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="editApprovalBillDetails.php?bill_id=<?= $bid ?>" class="action-link edit-link" title="Edit Bill Details">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button type="button" class="btn-delete-bill" data-bill-id="<?= $bid ?>" data-bill-no="<?= htmlspecialchars($bnum) ?>" title="Delete with Audit Archive">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php $srno++; endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php 
            $queryParams = http_build_query([
                'bill_id' => $searchBillId,
                'start_date' => $searchStartDate,
                'end_date' => $searchEndDate,
                'customer' => $searchCustomer
            ]);
            ?>
            <a href="?page=1&<?= $queryParams ?>" class="<?= ($page <= 1) ? 'disabled' : '' ?>">First</a>
            <a href="?page=<?= max(1, $page - 1) ?>&<?= $queryParams ?>">Prev</a>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?>&<?= $queryParams ?>" class="<?= ($page == $i) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <a href="?page=<?= min($totalPages, $page + 1) ?>&<?= $queryParams ?>">Next</a>
            <a href="?page=<?= $totalPages ?>&<?= $queryParams ?>">Last (<?= $totalPages ?>)</a>
        </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.btn-delete-bill').forEach(btn => {
    btn.addEventListener('click', function() {
        const billId = this.getAttribute('data-bill-id');
        const billNo = this.getAttribute('data-bill-no');

        if (confirm(`Are you sure you want to delete Approval Bill (${billNo})?\n\nThis will safely ARCHIVE the full bill into the audit log, restore product stock, and remove the bill.`)) {
            fetch('approval_report_delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'bill_id=' + encodeURIComponent(billId) + '&reason=' + encodeURIComponent('Deleted from approval bills list')
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    const row = document.getElementById('row-bill-' + billId);
                    if (row) {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                alert('Request failed: ' + err.message);
            });
        }
    });
});
</script>
</body>
</html>
