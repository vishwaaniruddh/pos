<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flyrobe Commission Records</title>
    <link rel="stylesheet" href="styles.css"> <!-- Optional: Add your CSS file for styling -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

 <style>
    .tooltip {
        position: relative;
        /*display: inline-block;*/
        border-bottom: 1px dotted black;
    }

    .tooltip .tooltiptext {
        visibility: hidden;
        background-color: black;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px 0;
        position: absolute;
        z-index: 1;
        padding: 10px;
    }

    .tooltip:hover .tooltiptext {
        visibility: visible;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }
    table, th, td {
        border: 1px solid black;
    }
    th, td {
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
    </style>
    
<div class="container">
    <h1>Flyrobe Commission Records</h1>

    <!-- Filter Form -->
   <?php
// Get the current month and year
$current_month = date('M'); // Current month in three-letter format (e.g., "Jan")
$current_year = date('Y');  // Current year

// You can also set the selected values based on previous form submission
$selected_month = isset($_POST['month']) ? $_POST['month'] : $current_month;
$selected_year = isset($_POST['year']) ? $_POST['year'] : $current_year;
?>

<form id="filterForm" method="POST">
    <label for="month">Month:</label>
    <select id="month" name="month">
        <option value="">Select Month</option>
        <option value="Jan" <?= $selected_month == 'Jan' ? 'selected' : '' ?>>January</option>
        <option value="Feb" <?= $selected_month == 'Feb' ? 'selected' : '' ?>>February</option>
        <option value="Mar" <?= $selected_month == 'Mar' ? 'selected' : '' ?>>March</option>
        <option value="Apr" <?= $selected_month == 'Apr' ? 'selected' : '' ?>>April</option>
        <option value="May" <?= $selected_month == 'May' ? 'selected' : '' ?>>May</option>
        <option value="Jun" <?= $selected_month == 'Jun' ? 'selected' : '' ?>>June</option>
        <option value="Jul" <?= $selected_month == 'Jul' ? 'selected' : '' ?>>July</option>
        <option value="Aug" <?= $selected_month == 'Aug' ? 'selected' : '' ?>>August</option>
        <option value="Sep" <?= $selected_month == 'Sep' ? 'selected' : '' ?>>September</option>
        <option value="Oct" <?= $selected_month == 'Oct' ? 'selected' : '' ?>>October</option>
        <option value="Nov" <?= $selected_month == 'Nov' ? 'selected' : '' ?>>November</option>
        <option value="Dec" <?= $selected_month == 'Dec' ? 'selected' : '' ?>>December</option>
    </select>

    <label for="year">Year:</label>
    <select id="year" name="year">
        <option value="">Select Year</option>
        <?php
        for ($i = 2018; $i <= date('Y')+10; $i++) {
            echo "<option value=\"$i\"" . ($selected_year == $i ? ' selected' : '') . ">$i</option>";
        }
        ?>
    </select>
    
    
    <label for="selectFrachise">Franchise:</label>
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
        e.preventDefault(); // Prevent the form from submitting the traditional way

        $.ajax({
            url: 'fetchFlyrobeData_new.php',
            type: 'POST',
            data: $(this).serialize(), // Serialize the form data
            success: function(response) {
                // Update the table with the new data
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
