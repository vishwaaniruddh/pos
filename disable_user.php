<? include('./db_connection.php');

$id = $_POST['id'];

if($id>0){
    
    $user_sql = mysqli_query($con,"select user_status from loginusers where id ='".$id."'"); 
    $user_data = mysqli_fetch_assoc($user_sql);
    $current_user_status = $user_data['user_status'];
    if($current_user_status==0){
        $status = 1;
    }else{
       $status = 0; 
    }
    
    
    $sql = "update loginusers set user_status='".$status."' where id ='".$id."'";
    if(mysqli_query($con,$sql)){
        echo 1;
    }else{
        echo 0;
    }
}else{
    echo 0;
}

?>