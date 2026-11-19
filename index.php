<?php session_start();
          include("db_connection.php");
         
            if($_SESSION['username']){ 
            ?>
            
            
            <script>
                     window.location.href="/pos/home_dashboard.php";
                </script>
            
            <?php
            }else{ ?>
                <script>
                     window.location.href="/pos/login.php";
                </script>
            <?php }
          
            
            
            
            
            ?>