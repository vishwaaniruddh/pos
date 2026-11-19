<?php session_start() ; 

include('top-header.php');?>
     <?php include('top-navbar.php');?>
            <div class="container-fluid page-body-wrapper">
                <?php include('navbar2.php');
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



                            
                            
                            <h2>Edit - Permission</h2>
                            
                            <div class="card">
                                <div class="card-body" style="overflow:auto;">
                            
    
<?php 

$id = $_REQUEST['id'];

$sql = mysqli_query($con,"select * from loginusers where id='".$id."'");
if($sql_result = mysqli_fetch_assoc($sql)){
    
    
 
$user_permission = $sql_result['permission'];
$user_permission = explode (",", $user_permission);


if(isset($_POST['submit'])){
    
    $sub_menu = $_REQUEST['sub_menu'];
    
    $sub_menu_str = implode(',',$sub_menu);
    
    echo 
    $update = "update loginusers set permission='".$sub_menu_str."' where id='".$id."'" ; 

if(mysqli_query($con,$update)){
    ?>
    <script>
        alert('Permission Updated Successfully !');
        window.location.href="./viewuser.php";
    </script>
    <?
}else{
    ?>
    <script>
        alert('Permission Updated Error !');
                window.location.href="./viewuser.php";
    </script>
    <?php 
}
    
}



    ?>
    
    <form action="<?php $_SERVER['PHP_SELF']; ?>" method="POST">
     <ul style="">
                <?php
                $statusColumn = 'status';
                $mainsql = mysqli_query($con, "select * from main_menu where $statusColumn=1");
                while ($mainsql_result = mysqli_fetch_assoc($mainsql)) {
                    $main_id = $mainsql_result['id'];
                    ?>
                    <li class="card-block">
                        <input type="checkbox" class="main_menu" value="<?php echo $main_id; ?>">
                        <span class="strong">
                            <?php echo $mainsql_result['name']; ?>
                        </span>
                        <ul class="showsubmenu">
                            <?php
                            $sub_sql = mysqli_query($con, "select * from sub_menu where main_menu='" . $main_id . "' and $statusColumn=1");
                            while ($sub_sql_result = mysqli_fetch_assoc($sub_sql)) {
                                $sub_id = $sub_sql_result['id'];
                                ?>
                                <li>
                                    <input class="submenu" type="checkbox" data-main_id="<?php echo $main_id ?>"
                                        name="sub_menu[]" value="<?php echo $sub_id; ?>"  <? if(in_array($sub_id,$user_permission)){ echo 'checked' ; } ?>>
                                    <?php echo $sub_sql_result['sub_menu']; ?>
                                </li>
                            <?php } ?>
                        </ul>
                        <hr />
                    </li>
                <?php } ?>
            

            </ul>
            <br />
                <input type="submit" name="submit"  />            
            
    </form>
    
            
            
    <?php 
    
    
    
}else{
    echo 'User NOt Found ! ';
}


?>
    
    
    
                                    
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