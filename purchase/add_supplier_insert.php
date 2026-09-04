<?php
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$fname = $_POST['fname'];
$lname = $_POST['lname'];
$email = $_POST['email'];
$phone_no = $_POST['ph_no'];
$add1 = $_POST['add1'];
$add2 = $_POST['add2'];
$city  = $_POST['city'];
$state = $_POST['state'];
$pincode = $_POST['pincode'];
$country = $_POST['country'];
$comment = $_POST['comment'];
$company_name = $_POST['supp_comp_name'];

$company_acc_no = $_POST['supp_acc_no'];


$insert = mysqli_query($con,"insert into `phppos_people` (`first_name`, `last_name`, `phone_number`, `email`, `address_1`, `address_2`, `city`, `state`, `zip`, `country`, `comments`) values ('".$fname."','".$lname."','".$phone_no."','".$email."','".$add1."','".$add2."','".$city."','".$state."','".$pincode."','".$country."','".$comment."'  )");
if($insert)
{ 
    // echo $fname;
    $suppsql = mysqli_query($con,"select person_id,first_name from phppos_people where first_name = '".$fname."'");
    $fetch_suppsql = mysqli_fetch_assoc($suppsql);
    $first_name = $fetch_suppsql['first_name'];
    // echo $first_name."<br>";
    
    $person_id = $fetch_suppsql['person_id'];
    // echo $person_id."<br>";
    // echo $company_name."<br/>".$company_acc_no; 
    
    $insert_supp = mysqli_query($con,"insert into phppos_suppliers (person_id,company_name,account_number) values ('".$person_id."', '".$company_name."', '".$company_acc_no."')  ");

?>


    <script>
        alert("Successfully Inserted");
        window.location.href="add_supplier.php";
    </script>

<?php } else { ?>
    <script>
        alert("Something Wrong!!");
        window.location.href="add_supplier.php";
    </script>
    
<?php }

CloseCon($con);

?>