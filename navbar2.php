<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SESSION['userid']) {


$base_url = 'https://srishringarr.com/pos/';

    $id = $_SESSION['userid'];


    $user = "select * from loginusers where id=" . $id;
    $usersql = mysqli_query($con, $user);
    $usersql_result = mysqli_fetch_assoc($usersql);




    $permission = $usersql_result['permission'];
    $permission = explode(',', $permission);
    sort($permission);

    $cpermission = json_encode($permission);
    $cpermission = str_replace(array('[', ']', '"'), '', $cpermission);
    $cpermission = explode(',', $cpermission);
    $cpermission = "'" . implode("', '", $cpermission) . "'";
    $mainmenu = [];
    foreach ($permission as $key => $val) {
        $sub_menu_sql = mysqli_query($con, "select * from sub_menu where id='" . $val . "' and status=1");

        if (mysqli_num_rows($sub_menu_sql) > 0) {
            $sub_menu_sql_result = mysqli_fetch_assoc($sub_menu_sql);
            $mainmenu[] = $sub_menu_sql_result['main_menu'];
        }
    }
    $mainmenu = array_unique($mainmenu);
    sort($mainmenu);






    ?>



<nav class="sidebar sidebar-offcanvas bt" id="sidebar">
                    <ul class="nav">
                      
                      
                      
                      
                      
                      
                      
                         <?php 
            foreach ($mainmenu as $menu => $menu_id) {
                
                $menu_sql = mysqli_query($con, "select * from main_menu where id='" . $menu_id . "' and status=1");
                $menu_sql_result = mysqli_fetch_assoc($menu_sql);
                $main_name = $menu_sql_result['name'];
                $targetDiv = str_replace(' ', '', $main_name);
             
                ?>

                <li class="nav-item ">
                    <a class="nav-link" data-toggle="collapse" href="#<?= $targetDiv; ?>" aria-expanded="false"
                        aria-controls="<?= $targetDiv; ?>">
                        
                        
                            <!-- <i class="mdi mdi-security"></i> -->
                            <?
                            
                            if ($main_name == 'Dashboard') {
                                echo '<i class="fas fa-tachometer-alt menu-icon" ></i>';
                                // echo '<i class="fa fa-american-sign-language-interpreting"></i>';                                                
                            } else if ($main_name == 'Users') {
                                echo '<i class="fas fa-archive menu-icon"></i></span>';
                            } else if ($main_name == 'Reports') {
                                echo '<i class="fas fa-archive menu-icon"></i></span>';
                            } else if ($main_name == 'Purchase') {
                                echo '<i class="fas fa-shopping-basket menu-icon" style="color: #FFB64D;"></i>';
                            } else if ($main_name == 'Bank') {
                                echo '<i class="fas fa-piggy-bank menu-icon" style="color: #8df3ff;"></i>';
                            } else if ($main_name == 'GST Reports') {
                                echo '<i class="fas fa-file menu-icon"></i>';
                            } else if ($main_name == 'Item Code') {
                                echo '<i class="fa fa-list-alt menu-icon" style="color: #23ff23;"></i>';
                            }else if ($main_name == 'Leads') {
                                echo '<i class="mdi mdi-format-align-center" style="color: #23ff23;"></i>';
                            }else if ($main_name == 'Reports') {
                                echo '<i class="mdi mdi-file-chart align-center" style="color: #23ff23;"></i>';
                            }else if ($main_name == 'Bulk Process') {
                                echo '<i class="mdi mdi-forum align-center"></i>';
                            }
                            
                            
                            
                            
                            
                            ?>

                        <span class="menu-title">
                            <? echo $main_name; ?>
                        </span>
                        <i class="menu-arrow"></i>
                    </a>

                    <div class="collapse" id="<?= $targetDiv; ?>">
                        <ul class="nav flex-column sub-menu">
                            <?
                            $submenu_sql = mysqli_query($con, "select * from sub_menu where main_menu = '" . $menu_id . "' and id in ($cpermission) and status=1 order by sub_menu asc");
                            while ($submenu_sql_result = mysqli_fetch_assoc($submenu_sql)) {
                                
                                $page = $submenu_sql_result['page'] ;
                                $submenu_name = $submenu_sql_result['sub_menu'];
                                $folder = $submenu_sql_result['folder'];

                                // echo $page ; 
                                // if($page == 'sitestest.php'){
                                //     $page = 'sites.php'
                                // }

                                if (basename($_SERVER['PHP_SELF'], PATHINFO_BASENAME) == $page) {
                                    $className = 'active';
                                } else {
                                    $className = '';
                                }
                                ?>
                                <li class="nav-item <? echo $className; ?>">
                                    <a class="nav-link" href="<?= $base_url . $folder . '/' . $page; ?>">
                                        <? echo $submenu_name; ?>
                                    </a>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                </li>

            <? } ?>



                       
					
						
						
                 
						<li class="nav-item icons-list">
                            <a class="nav-link" href="/pos/logout.php">
                                <i class="fa fa-sign-out-alt menu-icon">
                                </i>
                                <span class="menu-title">
                                    Logout
                                </span>
                            </a>
                        </li>
                    </ul>
                
                </nav>
		<?php } ?>	