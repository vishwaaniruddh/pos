<?php
if (file_exists(__DIR__ . '/base_url.php')) {
    include_once __DIR__ . '/base_url.php';
} elseif (file_exists(__DIR__ . '/../base_url.php')) {
    include_once __DIR__ . '/../base_url.php';
}
if (!function_exists('get_pos_asset_url')) {
    if (file_exists(__DIR__ . '/db_connection.php')) {
        include_once __DIR__ . '/db_connection.php';
    } elseif (file_exists(__DIR__ . '/../db_connection.php')) {
        include_once __DIR__ . '/../db_connection.php';
    }
}
$pos_asset_url = function_exists('get_pos_asset_url') ? get_pos_asset_url() : '/pos/';
?>

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport">
    <title>
        SriShringarr
    </title>
    <!-- plugins:css -->
    <link href="<?php echo $pos_asset_url; ?>vendors/iconfonts/font-awesome/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $pos_asset_url; ?>vendors/css/vendor.bundle.base.css" rel="stylesheet">
    <link href="<?php echo $pos_asset_url; ?>vendors/css/vendor.bundle.addons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.2/css/buttons.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.2/css/buttons.dataTables.min.css" rel="stylesheet">
    <!-- endinject -->
    <!-- plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="<?php echo $pos_asset_url; ?>vendors/lightgallery/css/lightgallery.css">
    <link href="<?php echo $pos_asset_url; ?>css/style.css" rel="stylesheet">
    <!-- endinject -->
    <!-- <link href="media/comfort.ico" rel="shortcut icon"/>-->
    <link rel="shortcut icon" href="<?php echo $pos_asset_url; ?>images/favicon.png">
    <!--<div id="loader" class="center">-->
    <!--         <img class="lazyload" data-src="assets/loader.gif">     -->
    <!--</div>-->
    </link>
    </link>
    </link>
    </link>
    <style>
        .content-wrapper {
            padding: 5.5rem 1.7rem !important;
        }

        .nav-link {
            white-space: pre-line !important;
        }
    </style>
    <!--<link href="sweetalert/sweetalert.css" rel="stylesheet">-->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <!-- select2 css -->
    <link href="<?php echo $pos_asset_url; ?>select2/dist/css/select2.min.css" rel="stylesheet" type="text/css">



    </meta>
    </meta>
</head>