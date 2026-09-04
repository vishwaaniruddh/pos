<?php session_start() ; 


include('../top-header.php');?>

     <?php include('../top-navbar.php');?>
     
     <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
            <div class="container-fluid page-body-wrapper">
                <?php include('../navbar.php');
                $con = OpenSrishringarrCon();
                ?>
                
                <!-- partial -->
                  <div class="main-panel">
                        <div class="content-wrapper">
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    <?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $measure_name = $_POST['measure_name'];

    // Check if the measurement name already exists
    $check_sql = "SELECT * FROM measurements WHERE measure_name = '$measure_name' AND activityStatus = 'Active'";
    $check_result = $con->query($check_sql);

    if ($check_result->num_rows > 0) {
        echo "<script>alert('Measurement name already exists.');</script>";
    } else {
        // Insert new measurement
        $sql = "INSERT INTO measurements (measure_name, activityStatus) VALUES ('$measure_name', 'Active')";
        if ($con->query($sql) === TRUE) {
            echo "<script>alert('Measurement name added successfully!');</script>";
        } else {
            echo "Error: " . $sql . "<br>" . $con->error;
        }
    }
}

?>

<div class="card">
    <div class="card-body">
<form method="POST">
    <label for="measure_name">Measurement Name:</label>
    <input type="text" class="form-control" name="measure_name" id="measure_name" required>
    <button class="btn btn-primary" type="submit">Add Measurement</button>
</form>
        
    </div>
</div>
         <br>         <br>           
                    
    
<div class="card">
    <div class="card-body">                
                    
                    
                    <?php
// Fetch existing measurements from the database
$sql = "SELECT * FROM measurements WHERE activityStatus != 'Deleted'";
$result = $con->query($sql);
$counter=1 ; 
?>

<table id="measurementsTable" class="display" style="width:100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>Measurement Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $counter; ?></td>
                <td><?php echo htmlspecialchars($row['measure_name']); ?></td>
                <td>
                    <button class="btn btn-warning btn-sm" onclick="openEditModal(<?php echo $row['measure_id']; ?>, '<?php echo addslashes($row['measure_name']); ?>')">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteMeasurement(<?php echo $row['measure_id']; ?>)">Delete</button>
                </td>
            </tr>
        <?php $counter++;  } ?>
    </tbody>
</table>


  
    </div>
</div>





<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Measurement</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="edit_id" name="measure_id">
                    <div class="form-group">
                        <label for="edit_measure_name">Measurement Name:</label>
                        <input type="text" class="form-control" id="edit_measure_name" name="measure_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">

<script>
$(document).ready(function() {
    $('#measurementsTable').DataTable();
});

function openEditModal(id, name) {
    $('#edit_id').val(id);
    $('#edit_measure_name').val(name);
    $('#editModal').modal('show');
}

$('#editForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: 'update_measurement.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            location.reload();
        }
    });
});

function deleteMeasurement(id) {
    if (confirm('Are you sure you want to delete this measurement?')) {
        $.ajax({
            url: 'delete_measurement.php',
            type: 'POST',
            data: { measure_id: id },
            success: function(response) {
                location.reload();
            }
        });
    }
}
</script>
                    
                    
                    
                	    </div>
                	
                	 <?php include('../footer.php');?>
                  </div>

    </div>

</div>

<script src="../vendors/js/vendor.bundle.base.js">
</script>
<script src="../vendors/js/vendor.bundle.addons.js">
</script>

<script src="../js/off-canvas.js">
</script>
<script src="../js/hoverable-collapse.js">
</script>
<script src="../js/misc.js">
</script>
<script src="../js/settings.js">
</script>
<script src="../js/todolist.js"></script>

<script src="../js/data-table.js"></script>
<script src="../js/data-table2.js"></script>
<script src="../js/select2.js"></script>
            
</body>
</html>