<?php
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$id=$_GET['id'];


// echo $id; 

$delqry = mysqli_query($con,"delete from `phppos_suppliers` where person_id = '".$id."' ");

// $del=mysqli_fetch_assoc($delqry);

if($delqry)
{
    echo '<script>alert("Data Deleted Successfully")</script>';
    echo '<script>window.location="view_supplier.php"</script>';
}
else{
    echo '<script>alert("Something Went Wrong!!!")</script>';
    echo '<script>window.location="view_supplier.php"</script>';
}


 CloseCon($con);
?>
