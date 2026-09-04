<?php session_start();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


include('db_connection.php') ; 
 $con = OpenSrishringarrCon();

require "./vendor/autoload.php";
use \Firebase\JWT\JWT;


 
?>
<html>
    <head>
        <script src="./sweetalert2.all.min.js"></script>        
    </head>
    <body>
        


<?php

$uname = $con->real_escape_string($_REQUEST['username']);
$password = $con->real_escape_string($_REQUEST['password']);


// var_dump($_POST);

if($uname!='' && $password!=''){
   
//   var_dump($con);
  //echo "select * from loginusers where uname = '".$uname."' and pwd='".$password."'" ; 
    // echo "select * from login2 where username = '".$uname."' and password='".$password."'" ; 
    // echo "select * from loginusers where uname = '".$uname."' and pwd='".$password."' and user_status=1" ; 
    $sql = mysqli_query($con,"select * from loginusers where uname = '".$uname."' and pwd='".$password."' and user_status=1");
    $result = mysqli_num_rows($sql);
    if($result>0){
        $sql_result = mysqli_fetch_assoc($sql);

        $_SESSION['auth']=1;
        $_SESSION['username']=$sql_result['name'];
       
        $_SESSION['userid'] = $sql_result['id'];
        $userid = $sql_result['id'];
		
	
        $_SESSION['access']=0 ;
        if($uname == 'admin@gmail.com'){
            $_SESSION['access']=1 ;
        }
        
        ?>
       <script>
           
           Swal.fire({
                title: "Login Successful",
                text: "Redirecting...",
                icon: "success",
                showConfirmButton: false,
                timer: 1500,
                didClose: () => {
                  window.location.href = 'home_dashboard.php';
                },
              });
    
       </script> 

    <?php
    
    
      $secret_key = "SRISHRINGARR";
        		$issuedat_claim = time(); // issued at
        		$notbefore_claim = $issuedat_claim + 10; //not before in seconds
        		$expire_claim = $issuedat_claim + 60; // expire time in seconds
        		
                $token = array(
                    "nbf" => $notbefore_claim,
                    "exp" => $expire_claim,
                    "data" => array(
                        "id" => $userid,
                        "fullname" => $sql_result['name'],
                        "email" => $sql_result['name'],
                ));
                $jwt = JWT::encode($token, $secret_key,"HS256");
                $token_sql = "update loginusers set token='".$jwt."' , updated_at = '".$datetime."' where id='".$userid."'";
                    mysqli_query($con,$token_sql) ;                
                    
                    
                    
                    
                    
    }else{ CloseCon($con); ?>
       <script>
           
    
     Swal.fire({
                title: "Login Failed",
                text: 'Login Error !',
                icon: "error",
                showConfirmButton: true, 
                timer: 1500,
                didClose: () => {
                        window.history.back();
                },
              });


       </script>
<?php }

    
    
}
else{ CloseCon($con); ?>
       <script>
     Swal.fire({
                title: "Login Failed",
                text: "Please Put Username and Password  !",
                icon: "error",
                showConfirmButton: true, 
                timer: 1500,
                didClose: () => {
                        window.history.back();
                },
              });



       </script>
<?php }
      
    

?>
    </body>
</html>