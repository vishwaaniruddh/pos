<?php session_start(); 

include('../top-header.php');
include('../top-navbar.php');
?>
<div class="container-fluid page-body-wrapper">
    <?php include('../navbar.php');
    $con = OpenSrishringarrCon();
    $product_id = htmlspecialchars($_REQUEST['productid']);
    $sku = htmlspecialchars($_REQUEST['sku']);
    $img = htmlspecialchars($_REQUEST['img']);
    ?>

    <!-- partial -->
    <div class="main-panel">
        <div class="content-wrapper">

            <div class="row">
                <div class="col-sm-6">
                    <img style="width:100%;" src="<?php echo htmlspecialchars($_REQUEST['img']); ?>" />
                </div>
                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <h6>Measurements Here</h6>

                            <!-- Measurement Table -->
                            <form id="measurementForm">
                                <table class="table" id="measurementTable">
                                    <thead>
                                        <tr>
                                            <th>Measurement Name</th>
                                            <th>Measurement Value</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Rows will be dynamically added here -->
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-success" onclick="addRow()">+</button>
                                <button type="submit" class="btn btn-primary">Save Measurements</button>
                            </form>

                        </div>
                    </div>
                </div>
            </div>

        </div>

        <?php include('../footer.php'); ?>
    </div>
</div>

<script src="../vendors/js/vendor.bundle.base.js"></script>
<script src="../vendors/js/vendor.bundle.addons.js"></script>
<script src="../js/off-canvas.js"></script>
<script src="../js/hoverable-collapse.js"></script>
<script src="../js/misc.js"></script>
<script src="../js/settings.js"></script>
<script src="../js/todolist.js"></script>

<script>
function addRow() {
    var table = document.getElementById("measurementTable").getElementsByTagName('tbody')[0];
    var newRow = table.insertRow();
    
    newRow.innerHTML = `
        <td>
            <input type="text" name="measure_name[]" class="form-control" list="measureNames" required>
            <datalist id="measureNames">
                <?php
                // Fetch measurement names for the datalist
                $measure_sql = "SELECT measure_name FROM measurements WHERE activityStatus != 'Deleted'";
                $measure_result = $con->query($measure_sql);
                while ($measure_row = $measure_result->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($measure_row['measure_name']) . "'>";
                }
                ?>
            </datalist>
        </td>
        <td><input type="text" name="measure_value[]" class="form-control" required></td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">-</button>
        </td>
    `;
}

function removeRow(button) {
    var row = button.closest('tr');
    row.remove();
}

document.addEventListener('DOMContentLoaded', function() {
    // Fetch measurement details for the product_id
    fetch('fetch_measurements.php?product_id=<?php echo $product_id; ?>')
        .then(response => response.json())
        .then(data => {
            var tableBody = document.getElementById("measurementTable").getElementsByTagName('tbody')[0];

            data.forEach((item) => {
                var newRow = tableBody.insertRow();
                newRow.innerHTML = `
                    <td>
                        <input type="text" name="measure_name[]" class="form-control" value="${item.measure_name}" list="measureNames" >
                        <datalist id="measureNames">
                            <?php
                            // Fetch measurement names for the datalist
                            $measure_sql = "SELECT measure_name FROM measurements WHERE activityStatus != 'Deleted'";
                            $measure_result = $con->query($measure_sql);
                            while ($measure_row = $measure_result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($measure_row['measure_name']) . "'>";
                            }
                            ?>
                        </datalist>
                    </td>
                    <td><input type="text" name="measure_value[]" class="form-control" value="${item.measure_value}" ></td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">-</button>
                    </td>
                `;
            });
        });
});

document.getElementById("measurementForm").addEventListener("submit", function(event) {
    event.preventDefault();
    
    var formData = new FormData(this);
    
    fetch('save_measurements.php?product_id=<?php echo $product_id; ?>', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
      .then(invalidRows => {
          if (Array.isArray(invalidRows) && invalidRows.length > 0) {
              // Remove previous highlights
              document.querySelectorAll('#measurementTable tbody tr').forEach((row, index) => {
                  row.style.backgroundColor = '';
              });

              // Highlight invalid rows
              invalidRows.forEach(rowIndex => {
                  var row = document.querySelectorAll('#measurementTable tbody tr')[rowIndex];
                  if (row) {
                      row.style.backgroundColor = 'red';
                  }
              });
              
              alert('Some measurements were invalid and not saved.');
          } else {
              alert('Measurements saved successfully!');
              window.location.href="./editMeasuments.php?productid=<?php echo $product_id; ?>&sku=<?php echo $sku; ?>&img=<?php echo $img;?>";
              // Optionally, you can refresh or redirect after saving
          }
      }).catch(error => {
          console.error('Error:', error);
      });
});
</script>

</body>
</html>
