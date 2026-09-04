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
<!--<div align='center' style='background-color:#987564'><br /><br /> <a href='view_payments.php'><input type='button' value='BACK'></a> &nbsp <a href='/pos/home_dashboard.php'> <input type='button' value='HOME'></a><br /></div>-->
 

<?php  
// include('config.php'); 
include('../db_connection.php') ;
$con=OpenSrishringarrCon();



$pur_id=$_GET['pur_id'];
$sup_name=$_GET['sup_name'];

$qrysuppayment=mysqli_query($con,"select * from `phppos_purchase_payments` where `bill_no`='$pur_id'");
$paymentsupp=mysqli_fetch_row($qrysuppayment);


?>
<div id="res">

<table  align="center"  bgcolor="#CCCCCC"   border="1">
   
           
           <tr><td colspan="2"><img src="../reports/bill.png"  align="middle" width="408" height="165"/></td></tr>
                   
           <tr> <td height="26" colspan="2"></td></tr>
           
           <tr> <td height="26" colspan="2"> 
                                    <p>
                                        <strong>Supplier Name:</strong> <?php echo $sup_name; ?>                                         
                                    </p>
                                </td></tr>
           <tr> <td height="26" colspan="2"></td></tr>
                  
           <tr>          
            <td colspan="" width="100" height="33">
                
                     <p><strong>Transaction ID:</strong> <?php echo $paymentsupp[0]; ?></p>    
                               
                     </td>               
               <td colspan="" width="100" height="33">                         
                                        
                              <p>  <strong>Date:</strong>    <?php echo date("d/m/Y",strtotime($paymentsupp[4]));?> </p></td>  
                            
                              </tr>
    <!--   <tr><td colspan="2"  height="33"></td></tr>-->
            <tr>           
            <td colspan="" width="100" height="33">
                
                         
                               
                                    <p>
                                        <strong>Amount:</strong>
                                        <?php echo $paymentsupp[3]; ?>
                                    </p>
                                
                            </td>
                            
                            <td colspan=""  height="33" width="100">
                                    <p><strong>Payment Mode:</strong> <?php echo $paymentsupp[2]; ?></p>
                                        </td> </tr> 
                       
                        <tr><td colspan="" height="50">
                                    <p><strong>Received By:</strong> </p></td>
                                       <td colspan="2"> </td></tr>  
                                <tr> <td height="26" colspan="2"></td></tr>
     
   
</table>
</div>

<?php CloseCon($con);?>
 <div align="center"><br/><button type='button'  onclick="printDiv('res');">Print</button></div>
 