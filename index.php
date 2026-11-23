<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (file_exists(__DIR__ . '/base_url.php')) {
    include_once __DIR__ . '/base_url.php';
}
if (file_exists(__DIR__ . '/db_connection.php')) {
    include_once __DIR__ . '/db_connection.php';
}
$pos_base_url = function_exists('get_pos_base_url') ? get_pos_base_url() : '/pos/';

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
