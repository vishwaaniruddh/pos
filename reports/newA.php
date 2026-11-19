<?php 
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<html>
    <head>
        

<link href="https://www.cssscript.com/demo/multiselect-dropdown-list-checkboxes-multiselect-js/styles/multiselect.css" rel="stylesheet"/>
<script src="https://www.cssscript.com/demo/multiselect-dropdown-list-checkboxes-multiselect-js/multiselect.min.js"></script>


	<style>
		/* example of setting the width for multiselect */
		#testSelect1_multiSelect {
			width: 200px;
		}
	</style>
</head>
<body>
<select id='testSelect1' multiple>
     <?php $sq=mysqli_query($con,"SELECT category FROM  `phppos_items` GROUP BY category");
	         while($ro=mysqli_fetch_array($sq)){

          ?>
             <option value="<?php echo $ro['category']; ?>"><?php echo $ro['category']; ?></option>
          <?php } ?>
          
</select>


<script>
	document.multiselect('#testSelect1')
		.setCheckBoxClick("checkboxAll", function(target, args) {
			console.log("Checkbox 'Select All' was clicked and got value ", args.checked);
		})
		.setCheckBoxClick("1", function(target, args) {
			console.log("Checkbox for item with value '1' was clicked and got value ", args.checked);
		});

</script>
<?php CloseCon($con);?>
</body>