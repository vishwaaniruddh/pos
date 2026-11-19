<?php
//ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$cid=$_POST['cid'];
$result = mysqli_query($con,"SELECT * FROM  phppos_people where person_id='$cid'");
	$row = mysqli_fetch_row($result);
$design=$_POST['design'];
$items=$_POST['items'];
$acc=$_POST['acc'];

$pick=$_POST['pick'];
$del=$_POST['del'];
$name=$_POST['name'];
$pamount=$_POST['pamount'];
$paid=$_POST['paid'];
$rentpaid=$_POST['rentpaid'];
$commis=$_POST['commis'];
$comm=$_POST['comm'];
$discount=$_POST['discount'];
$amt=$_POST['amt'];
$note=$_POST['note'];
$qty=$_POST['qty11'];
$qtyn=$_POST['qty'];
$qty1=0;
$d=count($design);
$del_date='';

if($pick=="Customer")
{
	$pick_date=$_POST['cust_pick'];
	
}
else
{
	//$pick_date=$_POST['compick_date'];
	$pick_date=$_POST['comdel_date'];
}

if(isset($_POST['del']) && $_POST['del']=='Customer Return')
{
 $del_date=$_POST['cust_del'];
}
else
{
//$del_date=$_POST['comdel_date'];
$del_date=$_POST['compick_date'];
}

$tphone=$_POST['tphone'];
$pname=$_POST['pname'];
$pcontact=$_POST['pcontact'];
$odate=$_POST['bill_date'];
$myflag=true;$dname="";$dqty=0;$bqty=0;
for($i=0;$i<$d;$i++)
{
$a=$qtyn[$i].".00";
$sq="SELECT quantity FROM phppos_items WHERE name='$design[$i]' and is_deleted = 0";
$res2 = mysqli_query($con,$sq);
$det = mysqli_fetch_row($res2);
if($a>$det[0])
{
 $dname=$design[$i];
 $dqty=$a;
 $bqty=$det[0];
 $myflag=false;
 break;
}
}
if(!$myflag){
            echo "<br><br><br><center>You don't have enough quantity for ".$dname.", required  (".$dqty.",) in Stock  (".$bqty;
            echo "). Go back and try again</center>";
            
}
mysqli_query($con,"BEGIN;");
if($myflag){
    
    
      $cardperc="0";
	    if(isset($_POST['pay_By']))
	    {
	        
	        
	        if($_POST['pay_By']=="Card")
	        {
	            
	           $cardperc="2"; 
	        }
	    }
    
    
    
//echo "insert into `phppos_rent`(cust_id,bill_date,status,pick,delivery,throught,pstatus,bal_amount,pick_date,delivery_date,delivery_status,throught_phone,person_Name,person_contact,comm_by,comm_amount,note,booking_status) values('$cid',STR_TO_DATE('".$odate."','%d/%m/%Y'),'A','$pick','$del','$name','$rentpaid','$pamount',STR_TO_DATE('".$pick_date."','%d/%m/%Y'),STR_TO_DATE('".$del_date."','%d/%m/%Y'),'$paid','$tphone','$pname','$pcontact','$commis','$comm','$note','Booked')";
$t1=mysqli_query($con,"insert into `phppos_rent`(cust_id,bill_date,status,pick,delivery,throught,pstatus,bal_amount,pick_date,delivery_date,delivery_status,throught_phone,person_Name,person_contact,comm_by,comm_amount,note,booking_status,card_perc,card_amt) 
values('$cid',STR_TO_DATE('".$odate."','%d/%m/%Y'),'A','$pick','$del','$name','$rentpaid','$pamount',STR_TO_DATE('".$pick_date."','%d/%m/%Y'),STR_TO_DATE('".$del_date."','%d/%m/%Y'),'$paid','$tphone','$pname','$pcontact','$commis','$comm','$note','Booked','".$cardperc."','".$_POST['cardpercamt']."')");
 

 if($_POST['pamount']>0)
 	$paymat=mysqli_query($con,"Insert into rent_amount(`cust_id`,`amount`) Values('".$cid."','".$_POST['pamount']."')");
		if($paymat){
	 //echo "INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','".$acc."','receit','".$pamount."',STR_TO_DATE('".$odate."','%d/%m/%Y'),'rent payment from customer $cid','NO',now())";		
		$t3=mysqli_query($con,"INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','".$acc."','receit','".$pamount."',STR_TO_DATE('".$odate."','%d/%m/%Y'),'rent payment from customer $row[0] $row[1]','NO',now())");	
			}
	$result1 = mysqli_query($con,"SELECT max(bill_id) FROM  `phppos_rent` where cust_id='$cid'");
	$rowordno = mysqli_fetch_row($result1);
	
	
	$people = mysqli_query($con,"SELECT * FROM  phppos_people where person_id='$name'");
	$prow = mysqli_fetch_row($people);
	
	

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SHRINGAAR</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

</head>
<script type="text/javascript" src="paging.js"></script>
<script type="text/javascript">     





        function PrintDiv() {    
           var divToPrint = document.getElementById('bill');
           var popupWin = window.open('', '_blank', 'width=800,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
           popupWin.document.close();
                }
		function rollback(){
			document.getElementById("bdy").innerHTML="Transaction is rolled back. Please refresh this page to complete the transaction!";
			//document.getElementById("pageNavPosition").innerHTML="";
			}
		
</script>
<body id="bdy">

<style>
    .tnc li{
        font-size:12px;
    }
</style>

<div id="bill">

<table width="826" border="0" align="center">
<tr>
<td width="820" height="42">
    
    <table width="100%" >
       <tr>
        <td colspan="3" align="center"><font size="2"><B><U> CONFIRMATION MEMO </U></B></font></td>
       </tr>            
      
       <tr>
       <td width="361" align="left" valign="top"><b><font size="-1" >MANUFACTURERS AND RETAILERS</font> <font size="-1">OF BRIDAL SETS</font>,<br />
        <font size="-1">HAIR ACCESSORIES AND  BROOCHES,</font><br/>
        <font size="-1">BRIDAL DUPATTAS</font>,<br />
        <font size="-1">CHANIYA CHOLI,<br/>
       &amp; ALL KINDS OF ACCESSORIES.</font></b><br />
       </td>
       <td width="448" height="42" colspan="2" align="left" valign="bottom" style="padding-left:10px;"><img src="bill.PNG" width="408" height="165"/></td>
       </tr>
    
       <tr>
       <td colspan="2" ></td>
       </tr>
       <tr>
       <td height="70" colspan="2" >
       <table width="811" height="166"> 
      <tr>
      <td width="320" height="43" ><b> Name :</b>&nbsp;&nbsp;&nbsp;<?PHP echo $row[0] . " ".$row[1]; ?></td>
      <td width="278"><b> Through Name: </b><?php echo $prow[0]." ".$prow[1]; ?></b><br/><b> Through Contact No:</b> <?php echo $tphone; ?></td>
      <td width="197" rowspan="5"><b> Bill. No. </b><?php echo $rowordno[0]; ?><br/><b> Date: </b><?php echo $odate; ?></td>
      </tr>
    
      <tr>
      <td><b>Contact No.: </b>&nbsp;&nbsp;&nbsp; <?php echo $row[2]; ?></td>
      <td width="278"><b> Pick-Up : </b>&nbsp;<?php echo $pick ?></td>
      </tr>
      
      <tr>
      <td height="45"><b> Address : </b>&nbsp;&nbsp;&nbsp; <?php echo $row[4]." ".$row[5]; ?><br/><?php echo $row[6]; ?><?php echo $row[8]; ?> <?php echo $row[9]; ?></td>
      <td><?php if($del=="Customer Return"){
                echo "Return";
                }else{ echo "Delivery";}
           ?> :<b><?php echo $del; ?></b>
      </td></tr>
      
       <tr>
       <td><b> 2nd Person Name : </b>&nbsp;&nbsp;&nbsp; <?php echo $pname; ?></td>
       <td><b> Pick-Up Date : </b>
	      <?php if($pick=="Customer"){ echo $pick_date; }else{
	             echo $pick_date; }
          ?>
       </td></tr>
       
       <tr>
       <td><b> 2nd Contact No.: </b>&nbsp;&nbsp;&nbsp; <?php echo $pcontact; ?></td>
       <td><?php if($del=="Customer Return"){ echo "Return Date";}else{ echo "Delivery Date"; }
	   
            ?>   : &nbsp;<b><?php if($del=="Customer Delivery"){ echo $del_date; }else{
		     echo $del_date;} ?></b>
       </td></tr>
      </table>
    </td>
    </tr>
  </table>
  
  <font size="2" >
  
  <table width="100%" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
    <th width="96"><font size="2">Sr. No.</font></th>
    <th width="96"><font size="2">ITEM CODE</font></th>
    <th width="151">PARTICULARS</th>
    <th width="96"><font size="2">DESCRIPTION</font></th>
    <th width="86"><font size="2">PRICE</font></th>
    <th width="86"><font size="2">QTY</font></th>
    <th width="110"><font size="2">RENT</font></th>
    <th width="119"><font size="2">DEPOSIT</font></th>
    <th width="88"><font size="2">TOTAL RENT</font></th>
  </tr>
  
  <?php
  $a=1;
  $total=0;
  $total1=0;
  $p2=0;
  $tol1=$_POST['total'];
  ///echo $tol1;
  for($i=0;$i<$d;$i++)
{
//echo "SELECT * FROM phppos_items WHERE name=".$design[$i];

$sq="SELECT * FROM phppos_items WHERE name='$design[$i]' and is_deleted = 0";
$res2 = mysqli_query($con,$sq);
$num2=mysqli_num_rows($res2);
$row1=mysqli_fetch_row($res2);


$prz=$_POST['prz'];
$qt=$_POST['qty'];
$q=$qt[$i];

////////update on 08/07/2013///$t2=mysqli_query($con,"update `phppos_items` set quantity=quantity-$q WHERE name='".$design[$i]."'");

$dep=$_POST['dep'];
 if($commis=="%"){
$p2=round($amt[$i]*($comm/100.0));
///$sum=$amt[$i]-$p;

}else{
//$p1=$comm;
$c=$comm/$tol1;
$b=$c*100;
$p2=round($amt[$i]*($b/100.0));

}
$rpt=$prz[$i]-$p2;

/////echo $rowordno[0]."/ ".$design[$i]." / ".$prz[$i]." / ".$amt[$i]."/".$p2."/".$rpt."<br/>";
 $t3=mysqli_query($con,"insert into order_detail(bill_id,item_id,rent,deposit,discount,total_amount,item_detail,commission_amt,qty) values('$rowordno[0]','$design[$i]','$prz[$i]','$dep[$i]','$discount[$i]','$amt[$i]','$items[$i]','$rpt','$qt[$i]')");

  ?>
  <tr>
    <td align="center"><?php echo $a; ?></td>
    <td align="center"><?php echo $row1[0]; ?></td>
    <td align="center"><?php echo $row1[1]; ?></td>
    <td align="center"><?php echo $items[$i]; ?></td>
    <td align="center"><?php echo $row1[6] ?></td>
    <td align="center"><font size="2" ><?php echo $qt[$i]; $qty1+= $qt[$i]; ?></font></td>
    <td align="center"><?php echo $prz[$i] ?></td>
    <td align="center"><?php echo $dep[$i]; ?></td>
    <td align="center"><?php echo $amt[$i]; ?></td>
  </tr>
 
  <?php 
  $total+=$dep[$i]+$prz[$i];
  $total1+=$amt[$i]; 
  $a++;
  } 
 ?>
 
 
 <?php if($_POST['cardpercamt']>0)
 {
     ?>
      <td colspan="6" align="right"></td>
 <td colspan="3" align="right"> <b>Card 2 % &nbsp; :&nbsp;</b><b><?php echo $_POST['cardpercamt']; ?></b></td>
<?php
 }
 
 
 ?>
 
 <tr>
<?php 
    
if($commis=="%")
{
$p=round($total1*($comm/100.0));
$sum=$total1-$p;
}
else
{
$p=$comm;
$sum=$total1-$p;
}
$famt=$total1-$p;
$ap= ($total1+$_POST['cardpercamt'])-$pamount;
///echo $sum."/".$comm."/".$commis;
    $t4=mysqli_query($con,"update `phppos_rent`  set amount='".$famt."',rent_amount='".$total1."',total_comm='".$p."' where bill_id='".$rowordno[0]."'");
?>
 <td colspan="6" align="right"><b> Total Qty : </b><?php echo $qty1; ?></td>
 <td colspan="3" align="right"> <b>Total Rent &nbsp; :&nbsp;</b><b><?php echo $total1+$_POST['cardpercamt']; ?></b></td>
 </tr>
</table>
</font>

</td>
</tr>
     
<td height="31" align="center"><font size="3" >
    
 Balance :<b>&nbsp;&nbsp;<?php echo  "Rs.".$ap;  ?>
   
<b>Deposit&nbsp; :&nbsp;</b><b><?php echo $paid; ?>&nbsp;&nbsp;<br/>Paid Amount <?php echo "Rs.".$pamount;?></b></font>
</td>
</tr>

<tr><td><b>TERMS & CONDITION: <?php echo $note; ?></b></td></tr>
<tr>
    <td>
    <div class="row">
        <div class="col-md-8">
 <ul class="tnc" style="list-style-type:disc;border-right:1px solid;">
            <li>FIXED  PRICE  NO  BARGAINING.</li>
            <li>THE  FULL  AMOUNT  OF  RENT  IS TO BE GIVEN ON THE  DAY OF BOOKING.</li>
            <li>ONCE  AN ORDER IS BOOKED,IT WILL  NOT BE CHANGED, EXCHANGE OR CANCELLED.</li>
            <li>NO  MONEY  WILL  BE REFUNDED.</li>
            <li>RENTAL IS  FOR 3  DAYS ONLY , 10% EXTRA FOR  EACH DAY.</li>
            <li>SECURITY  DEPOSIT  IS  COMPULSORY.</li>
            <li>ANY  DAMAGE  DONE  WILL BE  DEDUCTED FROM THE SECURITY  DEPOSIT.</li>
        </ul>

        </div>
        
        <div class="col-md-4" style="font-size:12px;">
    SRI  SHRINGARR   FASHION   STUDIO <br>
A/C  NO : 50200010838727 <br>
IFSC : HDFC0000227 <br>
HDFC  BANK <br>
CONTACT :  NIPA   AGRAWAL <br>
Tel : 8928272568               
        
        </div>
        
    </div>        
    </td>

</tr>

<!--<tr><td>-->
<!--  <hr/>-->
<!--  <table width="752" border="0">-->
<!--  <tr>-->
<!--    <td width="381"><ul>-->
<!--    <li ><font>Subject to Mumbai jurisdiction</font> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>E. & O . E</b>  </li>-->
<!--    <li><font>Deposit necessary</font></li>-->
<!--    <li><font>Rent basis available for 3 days only</font></li>-->
<!--    <li><font>Any damage done will be deducted from the deposit</font></li>-->
<!--    <li> <font>Time 11 a.m. to 6 p.m.</font></li></ul>-->
<!--    </td>-->
<!--    <td width="138">-->
<!--    <br/>-->
<!--    <br/><br/>-->
<!--    <br/><hr />-->
<!--    <font>Receiver's Signature</font>&nbsp;-->
<!--    </td>-->
<!--    <td width="219" valign="top"align="right">-->
<!--    <img src="shringaar.png" width="163" height="57"/>-->
<!--    <br/><br/><br/>-->
<!--    <font>Auth. Signatory</font>&nbsp;-->
<!--    </td>-->
<!--  </tr>-->
<!--</table>-->

<!--</td></tr>-->
</table>

</div>
<br/><br/>
<div id="pageNavPosition"></div>
<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="/pos/home_dashboard.php">Back</a></center>

</body>
</html><?php }
////if($t1 && $t2 && $t3 && $t4){
if($t1 && $t3 && $t4){
	mysqli_query($con,"COMMIT;");
	}
	else{
		mysqli_query($con,"ROLLBACK;");
		echo "<script> rollback(); </script>";
		}
	 ?>
	 <?php
	 
	 	$title ="Rent bill generated" ;

	 	//NOtification Code Start
	

include "nottest.php";

    //Notification Code End
CloseCon($con);	 
	 ?>
	 
	 