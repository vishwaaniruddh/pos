<?php session_start();
include("db_connection.php");
$pos_base_url = function_exists('get_pos_base_url') ? get_pos_base_url() : '/ss/pos/';

if (!empty($_SESSION['username'])) {
    ?>
        <script>
        window.location.href = "<?php echo $pos_base_url; ?>home_dashboard.php";
        </script>
    <?php
} else { ?>
        <script>
            window.location.href = "<?php echo $pos_base_url; ?>login.php";
        </script>
<?php }
?>
