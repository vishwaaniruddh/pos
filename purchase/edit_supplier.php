<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../db_connection.php');
$con = OpenSrishringarrCon();

$idd = isset($_GET['id']) ? mysqli_real_escape_string($con, $_GET['id']) : '';

$res_query = mysqli_query($con, "
    SELECT p.*, s.company_name, s.account_number 
    FROM phppos_people p 
    LEFT JOIN phppos_suppliers s ON p.person_id = s.person_id 
    WHERE p.person_id = '{$idd}'
    LIMIT 1
");

$supplier = mysqli_fetch_assoc($res_query);

if (!$supplier) {
    echo '<script>alert("Supplier not found!"); window.location="view_supplier.php";</script>';
    exit;
}

$dob_formatted = (!empty($supplier['dob']) && $supplier['dob'] != '0000-00-00') ? date('d/m/Y', strtotime($supplier['dob'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier | Sri Shringarr POS</title>
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
            padding: 30px 20px;
            display: flex;
            justify-content: center;
        }

        .edit-container {
            width: 100%;
            max-width: 680px;
        }

        .header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
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
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
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
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            padding: 24px 28px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        @media (max-width: 600px) {
            .form-group.full-width {
                grid-column: span 1;
            }
        }

        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            height: 40px;
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

        .form-actions {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }
    </style>
</head>
<body>

<div class="edit-container">
    <div class="header-bar">
        <h1 class="page-title">
            <i class="far fa-edit"></i> Edit Supplier Profile
        </h1>
        <a href="view_supplier.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Directory
        </a>
    </div>

    <div class="card">
        <form method="POST" action="Supp_edit.php">
            <input type="hidden" name="id" value="<?= htmlspecialchars($idd) ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" name="company_name" id="company_name" class="form-control" value="<?= htmlspecialchars($supplier['company_name'] ?? '') ?>" placeholder="Supplier Company">
                </div>

                <div class="form-group">
                    <label for="account_number">Account Code</label>
                    <input type="text" name="account_number" id="account_number" class="form-control" value="<?= htmlspecialchars($supplier['account_number'] ?? '') ?>" placeholder="e.g. AJ">
                </div>

                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" name="first_name" id="first_name" class="form-control" value="<?= htmlspecialchars($supplier['first_name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" name="last_name" id="last_name" class="form-control" value="<?= htmlspecialchars($supplier['last_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone / Mobile *</label>
                    <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($supplier['phone_number'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($supplier['email'] ?? '') ?>">
                </div>

                <div class="form-group full-width">
                    <label for="add">Street Address</label>
                    <input type="text" name="add" id="add" class="form-control" value="<?= htmlspecialchars($supplier['address_1'] ?? '') ?>" placeholder="Street Address">
                </div>

                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="text" name="dob" id="dob" class="form-control" value="<?= htmlspecialchars($dob_formatted) ?>" placeholder="dd/mm/yyyy" autocomplete="off">
                </div>
            </div>

            <div class="form-actions">
                <a href="view_supplier.php" class="btn btn-outline">Cancel</a>
                <button type="submit" name="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Update Supplier
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
<?php CloseCon($con); ?>
