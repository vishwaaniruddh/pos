<?php session_start();


session_destroy();


?>

<html>
    <head>
        <script src="./sweetalert2.all.min.js"></script>          
    </head>
    <body>

<script>
    
    
           Swal.fire({
                title: "Logout Successfully !",
                text: "Redirecting...",
                icon: "success",
                showConfirmButton: false,
                timer: 1500,
                didClose: () => {
                  window.location.href = './login.php';
                },
              });
              
              
    //   swal("", "Logout Successfully !", "success");
    //               setTimeout(function(){ 
    //           window.location.href="login.php";
    //       }, 3000);
</script>        
    </body>
</html>
        

