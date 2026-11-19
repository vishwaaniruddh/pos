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
                            
                            
                            
                             <h2>Signup Page</h2>
    <form action="signup.php" method="POST">
        <label for="name">Name:</label>
        <input type="text" class="form-control" id="name" name="name" required>

        <label for="uname">Username:</label>
        <input type="text" class="form-control" id="uname" name="uname" required>

        <label for="pwd">Password:</label>
        <input type="password" class="form-control" id="pwd" name="pwd" required>

        <label for="permission">Permission:</label>
        <input type="text" class="form-control" id="permission" name="permission" required>

        <label for="designation">Designation:</label>
        <input type="text" class="form-control" id="designation" name="designation" required>

        <label for="level">Level:</label>
        <input type="text" class="form-control" id="level" name="level" required>

        <label for="email">Email:</label>
        <input type="email" class="form-control" id="email" name="email" required>

        <label for="contact">Contact:</label>
        <input type="text" class="form-control" id="contact" name="contact" required>

        <input type="submit" value="Signup">
    </form>
                            
                            
                            
                            	
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