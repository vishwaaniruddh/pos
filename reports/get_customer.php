<?php include('../db_connection.php');
 $con = OpenSrishringarrCon();

$alph = $_POST['alph'];

$sql = mysqli_query($con,"SELECT person_id,first_name,last_name FROM `phppos_people` where first_name like '".$alph."%' ORDER BY `phppos_people`.`first_name` ASC");

$option = "<option value=''>".'Select'."</option>";

while($sql_result = mysqli_fetch_assoc($sql)){
    
    $fullname = $sql_result['first_name'].' '.$sql_result['last_name'];
    $person_id = $sql_result['person_id'];

    $option=$option."<option value='".$person_id."'>".$fullname."</option>";

    
}
echo $option;
CloseCon($con);
?>