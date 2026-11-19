<?php 

include('../db_connection.php');
$con = OpenSrishringarrCon();
$people = trim($_REQUEST['userid']);

if ($people) {
    $sql = mysqli_query($con, "SELECT * FROM phppos_people WHERE person_id = '$people' order by person_id desc");
    if ($sql_result = mysqli_fetch_assoc($sql)) {
        $person_id = $sql_result['person_id'];
        $personName = $sql_result['first_name'] . ' ' . $sql_result['last_name'];
        $data = ['person_id' => $person_id, 'personName'=>$personName];
        echo json_encode($data);    
    } else {
        echo 0;
    }
} else {
    echo 0;
}

mysqli_close($con);
?>
