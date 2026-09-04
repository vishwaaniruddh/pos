<?php
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$id=$_GET['id'];


// echo $id; 
$delete = "delete from phppos_people where person_id='".$id."' ";
$delqry = mysqli_query($con,$delete);

$del=mysqli_fetch_assoc($delqry);

if($del)
{
    echo '<script>alert("Data Deleted Successfully")</script>';
            echo '<script>window.location="custLst_copy.php"</script>';
}
else{
    echo '<script>alert("Something Went Wrong!!!")</script>';
            echo '<script>window.location="custLst_copy.php"</script>';
}

?>
 <?php CloseCon($con);?> 