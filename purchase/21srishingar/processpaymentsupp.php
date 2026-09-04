
<script type="text/javascript">

          /* function PrintDiv() {    
           var divToPrint = document.getElementById('');
           divToPrint.style.fontSize = "20px";
           var popupWin = window.open('', '_blank', 'width=500,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()" >' + divToPrint.innerHTML + '</html>');
           popupWin.document.close();
             }*/


function printDiv(divName) {
	alert(divName);
	//document.getElementsByClassName("pagination").style.display='none';
     var printContents = document.getElementById(divName).innerHTML;
     var originalContents = document.body.innerHTML;

     document.body.innerHTML = printContents;

     window.print();

     document.body.innerHTML = originalContents;
}


 </script>

<meta name="keywords" content="#" />
<meta name="description" content="#" />
<style type="text/css">
/*@media print {
#print_div {
display:block;
}
#not {
display:none;
}
#nottoprint {
display:none;
}

}*/
</style>

<script>
function printpage()
{
window.print();
}
</script>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">
/*@media print {
#print_div {
display:block;
}
#not {
display:none;
}
#nottoprint {
display:none;
}

}
td
{
text-align:left;
}*/
</style>

<?php
// include('config.php');
include('../../db_connection.php') ;
$con=OpenSrishringarrCon();


$supp=$_POST['supp'];
$bill=$_POST['bill'];
$amt=$_POST['amt'];
$amt1111=$amt;
$mode=$_POST['mode'];

//$billno=array();
$billno=explode(',',$bill);
//print_r($billno);
mysqli_query($con,"SET AUTOCOMMIT=0");
mysqli_query($con,"START TRANSACTION");

for($i=0;$i<count($billno);$i++)
{
	$qrybill=mysqli_query($con,"Select * from `phppos_purchase` where pur_id='$billno[$i]'");
	$resbill=mysqli_fetch_row($qrybill);
	//echo $resbill[6]." and amt ".$amt;
	if($resbill[6]<=$amt)
	{
		//echo "INSERT INTO `phppos_purchase_payments`(`trans_id`, `bill_no`, `mode`, `amt`, `paid_date`) VALUES ('','$resbill[0]','$mode','$resbill[6]',now())<br>";
		$insqry[$i]=mysqli_query($con,"INSERT INTO `phppos_purchase_payments`(`trans_id`, `bill_no`, `mode`, `amt`, `paid_date`) VALUES ('','$resbill[0]','$mode','$resbill[6]',now())");
		echo $tr=mysqli_insert_id();
		if($insqry)
		{
			$amt-=$resbill[6];
			//echo "UPDATE `phppos_purchase` SET `outstanding`='0' WHERE `pur_id`='$billno[$i]'<br/>";
			$qry[$i]=mysqli_query($con,"UPDATE `phppos_purchase` SET `outstanding`='0' WHERE `pur_id`='$billno[$i]'");	
		}	
	}
	
	else if($resbill[6]>$amt)
	{
		//echo "INSERT INTO `phppos_purchase_payments`(`trans_id`, `bill_no`, `mode`, `amt`, `paid_date`) VALUES ('','$resbill[0]','$mode','$amt',now())<br/>";
		$insqry[$i]=mysqli_query($con,"INSERT INTO `phppos_purchase_payments`(`trans_id`, `bill_no`, `mode`, `amt`, `paid_date`) VALUES ('','$resbill[0]','$mode','$amt',now())");
	    $tr=mysqli_insert_id();
		if($insqry)
		{
			$resbill[6]-=$amt;
			//echo "UPDATE `phppos_purchase` SET `outstanding`='$resbill[6]' WHERE `pur_id`='$billno[$i]' <br>";
			$qry[$i]=mysqli_query($con,"UPDATE `phppos_purchase` SET `outstanding`='$resbill[6]' WHERE `pur_id`='$billno[$i]'");	
		}	
	}
	
}
for($j=0;$j<count($billno);$j++)
{	//echo $j;
	if(!$qry[$j] and !$qrybill[$j])
	{
			 mysqli_query($con,"ROLLBACK");
			?>
            <div align='center' style='background-color:#987564'> <br/><br/><br/><br/>Transaction aborted. Please Try Again<br/><br/> <a href='view_bills.php'><input type='button' value='BACK'></a>&nbsp<a href='/pos/home_dashboard.php'> <input type='button' value='EXIT'></a><br><br></div>
            <?php
			 $flag=1;
			 break;	
	}	
}
if($flag!=1)
{
 mysqli_query($con,"COMMIT");
 ?>
<div align='center' style='background-color:#987564'> <br/><br/><br/><br/>Transaction Completed.<br/><br/> <a href='view_bills.php'><input type='button' value='BACK'></a>&nbsp<a href='/pos/home_dashboard.php'><input type='button' value='EXIT'></a> &nbsp <button type='button' onclick="printDiv('bill');">Print</button><br/></div>
 
 <?php
}

CloseCon($con);
?>


<style>
/*h1,h2,h3,h6,h5{text-align:center; vertical-align:top;}
p {text-align:; font-size:14px;}
div p{text-align:center;}

table{border:px solid #F00; width:70%;; margin-left:auto; margin-right:auto; border-collapse:collapse;}
table tr td{border:1px solid #333; padding:5px; }

.td_bg_col{background-color:#CCC;}
img{}
p span{font-size:12px;}*/
/*th{background:url(red-cross14.png) left no-repeat scroll; height:10px; width:10px;}*/
</style>


 


   <!-- <h2><strong>Bill / Receipt</strong></h2>-->
<!--<div id="not" align="center">-->
<div id="bill">
<table  align="center"  bgcolor="#CCCCCC"  border="1">
   
           
           <tr><td colspan="2" align="center"><img src="../reports/bill.png"  width="408" height="165"/></td></tr>
                   
           <tr> <td height="26" colspan="2"></td></tr>
           
           <tr> <td height="26" colspan="2"> 
                                    <p>
                                        <strong>Supplier Name:</strong> <?php echo $supp; ?>                                         
                                    </p>
                                </td></tr>
           <tr> <td height="26" colspan="2"></td></tr>
                  
           <tr>          
            <td colspan="" width="100" height="33">
                
                     <p><strong>Transaction ID:</strong> <?php echo $tr; ?></p>    
                               
                     </td>               
               <td colspan="" width="100" height="33">                         
                                        
                              <p>  <strong>Date:</strong>    <?php echo date('d/m/Y');?> </p></td>  
                            
                              </tr>
    <!--   <tr><td colspan="2"  height="33"></td></tr>-->
            <tr>           
            <td colspan="" width="100" height="33">
                
                         
                               
                                    <p>
                                        <strong>Amount:</strong>
                                        <?php echo $amt1111; ?>
                                    </p>
                                
                            </td>
                            
                            <td colspan=""  height="33" width="100">
                                    <p><strong>Payment Mode:</strong> <?php echo $mode; ?></p>
                                        </td> </tr> 
                       
                        <tr><td colspan="" height="50">
                                    <p><strong>Received By:</strong> </p></td>
                                       <td colspan="2"> </td></tr>  
                                <tr> <td height="26" colspan="2"></td></tr>
     
   
</table></div>
<!--</div> -->