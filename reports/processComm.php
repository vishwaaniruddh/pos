<?php
ini_set( "display_errors", 0);

// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);





$mobile=$_POST['mobile'];
$name=$_POST['name'];
$amt=$_POST['amount'];
$paid_date=$_POST['paid_date'];
$acc=$_POST['acc'];
$cname=$_POST['cname'];

 // echo $sql="insert into `commission_paid`(name,mobile,amount,paid_date) values('$name','$mobile','$amt',STR_TO_DATE('".$paid_date."','%d/%m/%Y'))" ;
 $sql=mysqli_query($con,"insert into `commission_paid`(name,mobile,amount,paid_date) values('$name','$mobile','$amt',STR_TO_DATE('".$paid_date."','%d/%m/%Y'))");
 
 if($sql){
	 //echo "INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','".$acc."','payment','".$amt."',STR_TO_DATE('".$paid_date."','%d/%m/%Y'),'commission  payment to customer $cname','NO',now())";
		

$insert = "INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','".$acc."','payment','".$amt."',STR_TO_DATE('".$paid_date."','%d/%m/%Y'),'commission  payment to customer $cname','NO',now())" ;

  
if(mysqli_query($con,$insert)){
    ?>
    <script>
        alert('Success');
    </script>
    <?php 
}else{
    ?>
    <script>
        alert('Error');
    </script>
    <?php
}

	
			}
?>

<a href="/pos/home_dashboard.php">Back</a>