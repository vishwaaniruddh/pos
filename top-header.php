<?php 
// ini_set('session.save_path', '/home/srishringarr/sessions'); 
            
            // session_start();
            include("db_connection.php");
         
            if($_SESSION['username']){ }else{ ?>
                <script>
                     window.location.href="/pos/login.php";
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