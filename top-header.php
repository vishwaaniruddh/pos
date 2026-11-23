<?php 
// ini_set('session.save_path', '/home/srishringarr/sessions'); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('OpenSrishringarrCon')) {
    if (file_exists(__DIR__ . '/db_connection.php')) {
        include_once(__DIR__ . '/db_connection.php');
    } elseif (file_exists(__DIR__ . '/../db_connection.php')) {
        include_once(__DIR__ . '/../db_connection.php');
    }
}

if (!isset($con) || !$con || !($con instanceof mysqli)) {
    if (function_exists('OpenSrishringarrCon')) {
        $con = OpenSrishringarrCon();
    }
}

if (file_exists(__DIR__ . '/base_url.php')) {
    include_once __DIR__ . '/base_url.php';
} elseif (file_exists(__DIR__ . '/../base_url.php')) {
    include_once __DIR__ . '/../base_url.php';
}

$pos_base_url = function_exists('get_pos_base_url') ? get_pos_base_url() : '/pos/';
if (!empty($_SESSION['username'])) { } else { ?>
    <script>
         window.location.href="<?php echo $pos_base_url; ?>login.php";
    </script>
<?php } ?>

<!DOCTYPE html>
<html lang="en">
    <?php include('head.php'); ?>

	<style>
    .table thead th, .jsgrid .jsgrid-table thead th {
    border-top: 0;
    border-bottom-width: 1px;
    font-weight: bold;
    font-size: .9rem;
    padding: 0.4375rem;
}
		.bt{
				border-top: 1px solid #1e1f33;
		  }
		  .br
		  {
				border-right: 1px solid #282844;
		  }
		  #accordion div.card-body {
		/*	margin:4px, 4px;
			padding:4px;
			background-color: green;
			width: 500px;  */
			height: 210px;
			overflow-x: hidden;
			overflow-y: scroll;
			text-align:justify;
		}
	</style>
	<style>
		.menu-icon
		{
			width: 33px;
			margin-right: 7%;
		}
	</style>