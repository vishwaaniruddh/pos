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
                            	
                            	
                            	<link rel="stylesheet" type="text/css" href="../datatable/dataTables.bootstrap.css">
     
                                <div class="card grid-margin">
                                    <div class="card-block">
                                        
                                        <!--Menu-->
                                        <?
                                        if(isset($_POST['menu_submit'])){
                                            $menu = $_POST['menu'];
                                            
                                            $sql = "insert into main_menu(name,status) values('".$menu."','1')";
                                            
                                            if(mysqli_query($con,$sql)){ ?>
                                                <script>
                                                    alert('Menu Added');
                                                </script>
                                            <? }else{
                                              ?>
                                                <script>
                                                    alert('Menu Added Error !');
                                                </script>
                                            <?  
                                            } }
                                        
                                        
                                        ?>
                                        <!--End Menu-->
                                        
                                        
                                        
                                        
                                        <!--Sub Menu-->
                                        
                                        <?
                                        if(isset($_POST['submenu_submit'])){
                                            $menu = $_POST['menu'];
                                            $submenu = $_POST['submenu'];
                                            $page = $_POST['page'];
                                            $sql = "insert into sub_menu(main_menu,sub_menu,page,status) values('".$menu."','".$submenu."','".$page."','1')";
                                            
                                            if(mysqli_query($con,$sql)){ ?>
                                                <script>
                                                    alert('SubMenu Added');
                                                </script>
                                            <? }else{
                                                echo mysqli_error($con);
                                              ?>
                                                <script>
                                                    alert('Sub Menu Added Error !');
                                                </script>
                                            <?  
                                            } }
                                        
                                        
                                        ?>
                                        
                                        
                                        <!--End Sub Menu-->                                        
                                        
                                        
                                        
                                        
                                        <form action="<? echo $_SERVER['PHP_SELF'];?>" method="POST">
                                            <div class="row">
                                               <div class="col-sm-12 grid-margin">
                                                   <label>Create Menu</label>
                                                   <input type="text" name="menu" class="form-control">
                                               </div>
                                                 <div class="col-sm-12 grid-margin">
                                                   <input type="submit" name="menu_submit" class="btn btn-primary">
                                               </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                
                                
                                <div class="card grid-margin">
                                    <div class="card-block">
                                        

                                        
                                <form action="<? echo $_SERVER['PHP_SELF'];?>" method="POST">
                                            <div class="row">
                                                <div class="col-sm-12 grid-margin">
                                                    <label>Select Under</label>
                                                    <select name="menu" class="form-control" id="main_menu" required>
                                                        <option value="">Select</option>
                                                        <?
                                                        $main_sql = mysqli_query($con,"select * from main_menu where status=1");
                                                        while($main_sql_result = mysqli_fetch_assoc($main_sql)){ ?>
                                                            <option value="<? echo $main_sql_result['id'];?>"><? echo $main_sql_result['name'];?></option>
                                                        <? } ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-sm-12 grid-margin">
                                                    <label>Submenu</label>
                                                    <input type="text" name="submenu" class="form-control" required>
                                                </div>
                                                <div class="col-sm-12 grid-margin">
                                                    <label>Page</label>
                                                    <input type="text" name="page" class="form-control" placeholder="like index.php" required>
                                                </div>
                                                
                                                <div class="col-sm-12 grid-margin">
                                                    <input type="submit" name="submenu_submit" class="btn btn-primary">
                                                </div>
                                                
                                                
                                            </div>    
                                    </form>
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