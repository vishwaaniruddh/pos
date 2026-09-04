<?php 
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$idd=$_POST["id"];
// echo $idd; die;

$fname=$_POST['first_name'];
$lname= $_POST['last_name'];
$mobile=$_POST['phone'];
$email=$_POST['email'];
$address=$_POST['add'];
$dob= $_POST['dob'];

$dob1=date("Y-m-d",strtotime($dob));

// echo "$fname"."$lname";
// echo $dob1;

$update ="update `phppos_people` set `first_name`='".$fname."',`last_name`='".$lname."',`phone_number`='".$mobile."',`email`='".$email."',`address_1` = '".$address."',  `dob`= '".$dob1."' where person_id='".$idd."'";
$updqry=mysqli_query($con,$update);
$res=mysqli_fetch_assoc($updqry);

// $res=1;

if($updqry)
{
     echo '<script>alert("Data Updated Successfully")</script>';
            echo '<script>window.location="view_supplier.php"</script>';
}
else
{
     echo '<script>alert("Something Wrong!! Try Again!!")</script>';
            echo '<script>window.location="view_supplier.php"</script>';
}
?>

<?php CloseCon($con);?> 