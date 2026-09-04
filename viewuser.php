<?php session_start() ; 

include('top-header.php');?>
     <?php include('top-navbar.php');?>
            <div class="container-fluid page-body-wrapper">
                <?php include('navbar.php');
                $con = OpenSrishringarrCon();
                ?>
                
                <!-- partial -->
                  <div class="main-panel">
                        <div class="content-wrapper">


<script src="//cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script>
    function disable(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Think twice to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Proceed it!'
        }).then((result) => {
            if (result.isConfirmed) {

                jQuery.ajax({
                    type: "POST",
                    url: 'disable_user.php',
                    data: 'id=' + id,
                    success: function (msg) {

                        if (msg == 1) {
                            Swal.fire(
                                'Updated!',
                                'Status has been changed.',
                                'success'
                            );

                            setTimeout(function () {
                                window.location.reload();
                            }, 2000);

                        } else if (msg == 0 || msg == 2) {

                            Swal.fire(
                                'Cancelled',
                                'Your imaginary file is safe :)',
                                'error'
                            );



                        }

                    }
                });


            }
        })

    }
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>



                            
                            
                            <h2>All Users</h2>
                            
                            <div class="card">
                                <div class="card-body" style="overflow:auto;">
                            
    <table id="example" class="table table-hover dataTable js-exportable no-footer" style="width:100%">
        <tr class='table-primary'>
            <th>Sr No</th>
            <th>USERID</th>
            <th>Name</th>
            <th>Username</th>

            <th>Email</th>
            <th>Contact</th>
            <th>Action</th>
            <th>Permission</th>
        </tr>
        <?php
        $sql = "SELECT * FROM loginusers";
$result = $con->query($sql);
$srno= 1; 
        if ($result->num_rows > 0) {
            // Output data of each row
            while($row = $result->fetch_assoc()) {
                                                $makeuser_status = ($row['user_status'] == 0) ? 'Make Active' : 'Make Inactive';


                echo "<tr>";
                echo "<td>" . $srno . "</td>";
                echo "<td>" . $row["id"] . "</td>";
                echo "<td>" . $row["name"] . "</td>";
                echo "<td>" . $row["uname"] . "</td>";

                echo "<td>" . $row["email"] . "</td>";
                echo "<td>" . $row["contact"] . "</td>";
                ?>
                
                <td>
                    <a href="#" class="btn btn-danger" onclick="disable(<?= $row['id']; ?>)"><?= $makeuser_status; ?></a>
                </td>
                
                <td>
                    <a href="./userpermission.php?id=<?php echo $row['id'];  ?>" class="btn btn-primary" style="color:white;">Permission</a>
                </td>
                
                <?php
                echo "</tr>";
            $srno++ ; 
                
            }
            
        } else {
            echo "<tr><td colspan='9'>No users found</td></tr>";
        }

        ?>
    </table>
    
                                    
                                </div>
                            </div>
    
    
    
                	    </div>
                	
                	 <?php include('footer.php');?>
                  </div>

    </div>

</div>

<script src="vendors/js/vendor.bundle.base.js">
</script>
<script src="vendors/js/vendor.bundle.addons.js">
</script>

<script src="js/off-canvas.js">
</script>
<script src="js/hoverable-collapse.js">
</script>
<script src="js/misc.js">
</script>
<script src="js/settings.js">
</script>
<script src="js/todolist.js"></script>

<script src="js/data-table.js"></script>
<script src="js/data-table2.js"></script>
<script src="js/select2.js"></script>
            
</body>
</html>