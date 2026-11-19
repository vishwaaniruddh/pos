<?php
// include('config.php');
include('../../db_connection.php') ;
$con=OpenSrishringarrCon();


$bank_id=$_POST['bank_id'];
$bank_id1=$_POST['bank_id1'];
$trans_type=$_POST['trans_type'];
$cntr=$_POST['count'];
$c=0;
$amt=array();
$memo=array();
$flag=array();
for($i=0;$i<=$cntr;$i++)
{
if($_POST['amt'][$i]!='' && $_POST['amt'][$i]!='0')
{

$amt[$c]=$_POST['amt'][$i];
$memo[$c]=$_POST['memo'][$i];
$flag[$c]='1';
$c=$c+1;
}
}
$trans_date=$_POST['transdate'];
mysqli_query($con,"SET AUTOCOMMIT=0");
mysqli_query($con,"START TRANSACTION");
//echo $trans_date;
?>

<table vspace="100px" hspace="250px" width="500px" height="200px" border="1" bgcolor="#FFFFCC"><tr><td>
<?php 
$transqry=array();
$transqry1=array();
$transac=array();
		//echo "INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','$bank_id','$trans_type','$amt',STR_TO_DATE('".$trans_date."','%d/%m/%Y'),'$memo','NO',now())";/*
		for($i=0;$i<$c;$i++)
		{
		
		//echo "<br>INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','$bank_id','$trans_type','".$amt[$i]."',STR_TO_DATE('".$trans_date."','%d/%m/%Y'),'".$memo[$i]."','NO',now())<br>";
		$transqry[$i]=mysqli_query($con,"INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','$bank_id','$trans_type','".$amt[$i]."',STR_TO_DATE('".$trans_date."','%d/%m/%Y'),'".$memo[$i]."','NO',now())");
		//echo "select max(trans_id) from bank_transaction";
		$trans=mysqli_query($con,"select max(trans_id) from bank_transaction");
		
		$transid=mysqli_fetch_row($trans);
		 $transac[$i]=$transid[0];
		//$flag=1;
		if($transqry[$i])
		{		if($trans_type=="banktrans")
				{
				//echo "INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','$bank_id1','receit',''".$amt[$i]."'',STR_TO_DATE('".$trans_date."','%d/%m/%Y'),'".$memo[$i]."','NO',now())";
				$transqry1=mysqli_query($con,"INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','$bank_id1','receit','".$amt[$i]."',STR_TO_DATE('".$trans_date."','%d/%m/%Y'),'".$memo[$i]."','NO',now())");
				if(!$transqry1)
				{	$flag[$i]=0;
				}
				}
			}
				
		}
		$success=1;
			for($i=0;$i<$c;$i++)
			{
		if($transqry[$i] && $flag[$i]==1)
					{
					
					/*	mysqli_query($con,"COMMIT");
				echo "Transaction Done Successfully. Transaction ID : ".$trans[$i]."<br> <a href='bank_entry.php'><input type='button' value=Another Transation'/></a> &nbsp <a href='/pos/home_dashboard.php'><input type='button' value='Exit'></a> "	;
*/
						}
		else
				{
				$success=0;
					/*mysqli_query($con,"ROLLBACK");
					echo "Bank Transaction of amount '".$amt[$i]."' Failed to Transfer.".mysqli_error()." <br> <a href='bank_entry.php'><input type='button' value='Back'/></a> &nbsp <a href='/pos/home_dashboard.php'><input type='button' value='Exit'></a> ";	*/	
					}
			}
			if($success=1)
			{
					
						mysqli_query($con,"COMMIT");
						for($i=0;$i<$c;$i++)
						{
				echo "Transaction Done Successfully. Transaction ID : ".$transac[$i]."<br>";
						}
						echo "<br> <a href='bank_entry.php'><input type='button' value=Another Transation'/></a> &nbsp <a href='/pos/home_dashboard.php'><input type='button' value='Exit'></a> "	;

			}
			else
			{
			
					mysqli_query($con,"ROLLBACK");
					echo "Bank Transaction of amount '".$amt[$i]."' Failed to Transfer.".mysqli_error()." <br> <a href='bank_entry.php'><input type='button' value='Back'/></a> &nbsp <a href='../../../index.php/purchase'><input type='button' value='Exit'></a> ";		
			}
	
//*/

CloseCon($con);

?>
</td></tr></table>