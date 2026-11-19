<?php
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$message=" Date-".date("d-m-Y");
// $img_url = "http://sarmicrosystems.in/srishringarr/application/views/reports/img/srishringarr.jpg";
$img_url = "https://srishringarr.com/srishringarr/application/views/reports/img/srishringarr.jpg";
 $path_to_fcm = "https://fcm.googleapis.com/fcm/send";
    $sever_key = "AAAAE53_OSE:APA91bH7dMFJN6dHftn_N5-q2nQHcqh7uhLkixl2IF00sZr5SV-nDTI9I-xHrcnDb5NHQ1VgPZv6QnzX2saBTw1ARV5IzqvPWvBhjOn63iNm_gHk-_bE8cS_sDpeoCiOId0I2MOnE6Mp";
    $sql = "select * from fcm_info"; 
    $result = mysqli_query($con,$sql);
    //$row = mysqli_fetch_row($reult);
   // $key = $row[0];
    
    $headers = array(
        'Authorization:key = ' .$sever_key,
        'Content-Type:application/json'
    );
    
   while($row = mysqli_fetch_array($result)){
  //     echo "Hello";
        $key = $row['fcm_token'];
        
         print '<pre>';
    //print_r($key);
    //print '</pre>';
         $fields = array(
        'to'=>$key,
        'data'=>array('title'=>$title,'message'=>$message,'img_url'=>$img_url
       /* 'click_action'=>'com.example.hp.ipua_TARGET_NOTIFICATION'*/
        ));
        
        
        $payload = json_encode($fields);
    $curl_session = curl_init();
    curl_setopt($curl_session, CURLOPT_URL, $path_to_fcm);
    curl_setopt($curl_session, CURLOPT_POST, true);
    curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_session, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl_session, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($curl_session, CURLOPT_POSTFIELDS, $payload);
    
    
    $test=curl_exec($curl_session);
   // echo $test;
    
    
    if(curl_errno($curl_session)){
    echo 'Request Error:' . curl_error($ch);
}
    if(!$test)
{
    echo 'error:' . curl_error($c);
}
    curl_close($curl_session);
    }

    ?>