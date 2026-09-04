<?php
$data = array();

$name = "Ashwin";
$date = "20/3/2018";
$amount = "20000";

for($i = 0; $i<=3; $i++){
    $data[] = ['name' => $name, 'date' => $date,'amount' => $amount];
}


echo json_encode($data);
?>