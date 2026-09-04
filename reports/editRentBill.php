<?php
include('../db_connection.php');
$con = OpenSrishringarrCon();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Pagination variables
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;


// Search variables
$searchBillId = isset($_GET['bill_id']) ? $_GET['bill_id'] : '';
$searchStartDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$searchEndDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build the query with search filters
$query = "
    SELECT r.bill_id, r.cust_id, r.bill_date, p.first_name, p.last_name, r.new_bill_number 
    FROM phppos_rent r
    JOIN phppos_people p ON r.cust_id = p.person_id
    WHERE 1=1
";

$paramTypes = "";
$paramValues = [];

// Search by Bill ID or New Bill Number
if ($searchBillId) {
    $query .= " AND (r.bill_id = ? OR r.new_bill_number = ?)";
    $paramTypes .= "ii";
    $paramValues[] = $searchBillId;
    $paramValues[] = $searchBillId;
}

// Search by Date Range
if ($searchStartDate && $searchEndDate) {
    $query .= " AND r.bill_date BETWEEN ? AND ?";
    $paramTypes .= "ss";
    $paramValues[] = $searchStartDate;
    $paramValues[] = $searchEndDate;
}

// Add ordering and pagination
$query .= " ORDER BY r.bill_id DESC LIMIT ? OFFSET ?";
$paramTypes .= "ii";
$paramValues[] = $limit;
$paramValues[] = $offset;

// Prepare the statement
$stmt = $con->prepare($query);

// Bind parameters dynamically
$stmt->bind_param($paramTypes, ...$paramValues);

// Execute the statement
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .search-form {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .search-form input[type="text"],
        .search-form input[type="date"],
        .search-form input[type="submit"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
            flex: 1;
        }
        .search-form input[type="submit"] {
            background-color: #4a90e2;
            color: white;
            border: none;
            cursor: pointer;
        }
        .search-form input[type="submit"]:hover {
            background-color: #357ab8;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #4a90e2;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .pagination {
            text-align: center;
            margin-top: 20px;
        }
        .pagination a {
            padding: 8px 12px;
            margin: 0 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #4a90e2;
        }
        .pagination a.active {
            background-color: #4a90e2;
            color: white;
        }
        .pagination a:hover {
            background-color: #357ab8;
            color: white;
        }
        .delete_bill{
            background-color: #e70505;
    color: white;
    border: none;
    cursor: pointer;
    padding:5px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Rent Bill Records</h2>

    <form class="search-form" method="GET" action="">
        <input type="text" name="bill_id" placeholder="Search by Bill ID" value="<?php echo $searchBillId; ?>">
        <input type="date" name="start_date" value="<?php echo $searchStartDate; ?>">
        <input type="date" name="end_date" value="<?php echo $searchEndDate; ?>">
        <input type="submit" value="Search">
    </form>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Sr No</th>
                <th>Bill ID</th>
                <th>Bill Date</th>
                <th>Customer Name</th>
            </tr>
            <?php
            $srno = ($page*$limit - $limit) +1  ; 
            while ($row = $result->fetch_assoc()): 
            
            
            $new_bill_number = $row['new_bill_number'];
            ?>
                <tr>
                    <td><?php echo $srno; ?></td>
                    <td>
                        <a href="./editrentbillDetails.php?bill_id=<?php echo $row['bill_id']; ?>"><?php echo ($new_bill_number ?  $new_bill_number : $row['bill_id'] ) ;   ?> </a>
                        |
                        <a href="./rent_report_detail.php?id=<?php echo $row['bill_id']; ?>" target="_blank">View Bill</a>
                        |
                        <button class="btn btn-danger delete_bill" data-bill-id="<?php echo $row['bill_id']; ?>" >Delete</button>
                        </td>
                    <td><?php echo $row['bill_date']; ?></td>
                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                </tr>
            <?php $srno++;  endwhile; ?>
        </table>

        <?php
        // Pagination Logic
        $totalQuery = "SELECT COUNT(*) as total FROM phppos_rent";
        if ($searchBillId) {
            $totalQuery .= " WHERE bill_id = ?";
            $totalStmt = $con->prepare($totalQuery);
            $totalStmt->bind_param('i', $searchBillId);
        } elseif ($searchStartDate && $searchEndDate) {
            $totalQuery .= " WHERE bill_date BETWEEN ? AND ?";
            $totalStmt = $con->prepare($totalQuery);
            $totalStmt->bind_param('ss', $searchStartDate, $searchEndDate);
        } else {
            $totalStmt = $con->prepare($totalQuery);
        }
        
        $totalStmt->execute();
        $totalResult = $totalStmt->get_result();
        $total = $totalResult->fetch_assoc()['total'];
        $totalPages = ceil($total / $limit);
        ?>

        <div class="pagination">
    <?php if ($totalPages > 1): ?>
        <a href="?page=1&bill_id=<?php echo $searchBillId; ?>&start_date=<?php echo $searchStartDate; ?>&end_date=<?php echo $searchEndDate; ?>" class="<?php echo $page == 1 ? 'disabled' : ''; ?>">First</a>
        <a href="?page=<?php echo max(1, $page - 1); ?>&bill_id=<?php echo $searchBillId; ?>&start_date=<?php echo $searchStartDate; ?>&end_date=<?php echo $searchEndDate; ?>" class="<?php echo $page == 1 ? 'disabled' : ''; ?>">Previous</a>

        <?php 
        // Set the range of pages to display
        $range = 2; // Show 2 pages before and after the current page
        $start = max(1, $page - $range);
        $end = min($totalPages, $page + $range);
        
        // Show first page link if not visible
        if ($start > 1) {
            echo '<a href="?page=1&bill_id=' . $searchBillId . '&start_date=' . $searchStartDate . '&end_date=' . $searchEndDate . '">1</a>';
            if ($start > 2) echo '<span>...</span>'; // Ellipsis
        }
        
        // Display the range of pages
        for ($i = $start; $i <= $end; $i++): ?>
            <a href="?page=<?php echo $i; ?>&bill_id=<?php echo $searchBillId; ?>&start_date=<?php echo $searchStartDate; ?>&end_date=<?php echo $searchEndDate; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; 

        if ($end < $totalPages) {
            if ($end < $totalPages - 1) echo '<span>...</span>'; // Ellipsis
            echo '<a href="?page=' . $totalPages . '&bill_id=' . $searchBillId . '&start_date=' . $searchStartDate . '&end_date=' . $searchEndDate . '">' . $totalPages . '</a>';
        }
        ?>

        <a href="?page=<?php echo min($totalPages, $page + 1); ?>&bill_id=<?php echo $searchBillId; ?>&start_date=<?php echo $searchStartDate; ?>&end_date=<?php echo $searchEndDate; ?>" class="<?php echo $page == $totalPages ? 'disabled' : ''; ?>">Next</a>
        <a href="?page=<?php echo $totalPages; ?>&bill_id=<?php echo $searchBillId; ?>&start_date=<?php echo $searchStartDate; ?>&end_date=<?php echo $searchEndDate; ?>" class="<?php echo $page == $totalPages ? 'disabled' : ''; ?>">Last</a>
    <?php endif; ?>
</div>

    <?php else: ?>
        <p>No records found.</p>
    <?php endif; ?>
</div>

<?php
// Close the connection
$con->close();
?>

<script>
document.querySelectorAll(".delete_bill").forEach(button => {
    button.addEventListener("click", function () {
        const billId = this.getAttribute("data-bill-id");

        if (confirm(`Are you sure you want to delete this bill (ID: ${billId})?`)) {
            fetch("./rent_report_delete.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `bill_id=${encodeURIComponent(billId)}`,
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === "success") {
                        alert(data.message);
                        button.closest("tr").remove();
                    } else {
                        alert(`Error: ${data.message}`);
                    }
                })
                .catch(error => {
                    alert(`Request failed: ${error.message}`);
                });
        }
    });
});


</script>
</body>
</html>
