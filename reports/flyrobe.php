<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flyrobe Commission Records</title>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f9f9f9;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
        }

        /* Form Styling */
        form {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f2f2f2;
            border-radius: 6px;
        }

        label {
            font-weight: bold;
        }

        select, button {
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            cursor: pointer;
            background-color: #007bff;
            color: white;
            border: none;
            transition: 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        #exportCsv {
            background-color: #28a745;
        }

        #exportCsv:hover {
            background-color: #218838;
        }

        hr {
            margin: 20px 0;
            border: none;
            height: 1px;
            background-color: #ddd;
        }

        /* Table Styling */
        .table-container {
            overflow-x: auto;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            background-color: black;
            color: #fff;
            text-align: center;
            padding: 8px;
            border-radius: 6px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            form {
                flex-direction: column;
                align-items: flex-start;
            }

            select, button {
                width: 100%;
            }
        }
    </style>

</head>
<body>

<div class="container-fluid">
    <h1>Flyrobe Commission Records</h1>

    <!-- Filter Form -->
    <?php
    // Get the current month and year
    $current_month = date('M'); // Current month in three-letter format (e.g., "Jan")
    $current_year = date('Y');  // Current year

    // Set selected values based on previous form submission
    $selected_month = $_POST['month'] ?? $current_month;
    $selected_year = $_POST['year'] ?? $current_year;
    ?>

    <form id="filterForm" method="POST">
        <label for="month">Month:</label>
        <select id="month" name="month">
            <option value="">Select Month</option>
            <?php
            $months = ["Jan" => "January", "Feb" => "February", "Mar" => "March", "Apr" => "April", "May" => "May", "Jun" => "June",
                       "Jul" => "July", "Aug" => "August", "Sep" => "September", "Oct" => "October", "Nov" => "November", "Dec" => "December"];
            foreach ($months as $key => $value) {
                echo "<option value='$key' " . ($selected_month == $key ? 'selected' : '') . ">$value</option>";
            }
            ?>
        </select>

        <label for="year">Year:</label>
        <select id="year" name="year">
            <option value="">Select Year</option>
            <?php for ($i = 2018; $i <= date('Y') + 10; $i++) {
                echo "<option value=\"$i\"" . ($selected_year == $i ? ' selected' : '') . ">$i</option>";
            } ?>
        </select>

        <label for="selectFrachise">Franchise (Product Wise):</label>
        <select id="selectFrachise" name="selectFrachise">
            <option value="2">All</option>
            <option value="1">Flyrobe</option>
            <option value="0">Srishringarr</option>
        </select>

        <button type="submit">Filter</button>
        <button type="button" id="exportCsv">Export to CSV</button>
    </form>

    <hr />

    <!-- Results Table -->
    <div class="table-container">
        <table id="resultsTable">
            <!-- The table content will be dynamically loaded here -->
        </table>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#filterForm').on('submit', function(e) {
            e.preventDefault(); 
            
            $.ajax({
                url: 'fetchFlyrobeData.php',
                type: 'POST',
                data: $(this).serialize(), 
                success: function(response) {
                    $('#resultsTable').html(response);
                },
                error: function() {
                    alert('An error occurred while processing the request.');
                }
            });
        });

        $('#exportCsv').on('click', function() {
            window.location.href = 'fetchFlyrobeData.php?export=1&' + $('#filterForm').serialize();
        });
    });
</script>

</body>
</html>
