<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);



//ini_set( "display_errors", 0);
// include('config.php');


//  var_dump($_POST); die;

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


 include('../db_connection.php') ;
$con=OpenSrishringarrCon();
 
//  var_dump($_POST); die;

$cid=$_POST['cid'];
$result = mysqli_query($con,"SELECT * FROM  phppos_people where person_id='".$cid."' ");
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
$company_name = $_POST['company_name'];

$bill_madeby = $_POST['bill_by'];

if($pick=="Customer")
{
	$pick_date=$_POST['cust_pick'];
// 	$pick_date = strtotime("Y-m-d",$pick_date);
	
}
else
{
	//$pick_date=$_POST['compick_date'];
	$pick_date=$_POST['comdel_date'];
    // $pick_date = strtotime("Y-m-d",$pick_date);
}

if(isset($_POST['del']) && $_POST['del']=='Customer Return')
{
 $del_date=$_POST['cust_del'];
//  $del_date = strtotime("Y-m-d",$del_date);
}
else
{
//$del_date=$_POST['comdel_date'];
$del_date=$_POST['compick_date'];
// $del_date = strtotime("Y-m-d",$del_date);
}

$trail_date=$_POST['trail_date'];
// $trail_date = strtotime("Y-m-d",$trail_date);

$measurement=$_POST['measurement'];
$measurement_note = $_POST['measurement_note'];
$is_delivery=$_POST['delivery'];
$tphone=$_POST['tphone'];
$pname=$_POST['pname'];
$pcontact=$_POST['pcontact'];
$odate=$_POST['bill_date'];
// $odate = strtotime("Y-m-d",$odate);

$myflag=true;$dname="";$dqty=0;$bqty=0;
for($i=0;$i<$d;$i++)
{
$a=$qtyn[$i].".00";
$sq="SELECT quantity FROM phppos_items WHERE name='".$design[$i]."' and is_deleted = 0";
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
// mysqli_query($con,"BEGIN;");
mysqli_autocommit($con,FALSE);
if($myflag){
    
    $mode= $_POST['pay_By'];
      $cardperc="0";
	    if(isset($_POST['pay_By']))
	    {
	        
	        
	        if($_POST['pay_By']=="Card")
	        {
	            
	           $cardperc="2"; 
	        }
	         if($_POST['pay_By']=="Cheque")
	        {
	            
	           $cardperc="1"; 
	        }
	    }
    

    
$t1=mysqli_query($con,"insert into `phppos_rent`(cust_id,bill_date,status,pick,delivery,throught,pstatus,bal_amount,pick_date,delivery_date,delivery_status,trail_date,measurement,measurement_note,is_delivery,throught_phone,person_Name,person_contact,comm_by,comm_amount,note,booking_status,card_perc,card_amt,bill_madeby,company_name) 
values('".$cid."', STR_TO_DATE('".$odate."','%d/%m/%Y'),'A','".$pick."','".$del."','".$name."','".$rentpaid."','".$pamount."',STR_TO_DATE('".$pick_date."','%d/%m/%Y'),STR_TO_DATE('".$del_date."','%d/%m/%Y'),'".$paid."',STR_TO_DATE('".$trail_date."','%d/%m/%Y'),'".$measurement."','".$measurement_note."','".$is_delivery."','".$tphone."','".$pname."','".$pcontact."','".$commis."','".$comm."','".$note."','Booked','".$cardperc."',
'".$_POST['cardpercamt']."', '".$bill_madeby."','".$company_name."' )  ");


 if($_POST['pamount']>0)
 	$paymat=mysqli_query($con,"Insert into rent_amount(`cust_id`,`amount`) Values('".$cid."','".$_POST['pamount']."')");
		if($paymat){



echo "INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`,company_name) VALUES ('','".$acc."','receit','".$pamount."',STR_TO_DATE('".$odate."','%d/%m/%Y'),'rent payment from customer $row[0] $row[1]','NO',now(),'".$company_name."') " ; 


$t3=mysqli_query($con,"INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`,company_name) VALUES ('','".$acc."','receit','".$pamount."',STR_TO_DATE('".$odate."','%d/%m/%Y'),'rent payment from customer $row[0] $row[1]','NO',now(),'".$company_name."') ");	
			}
			
			
	$result1 = mysqli_query($con,"SELECT max(bill_id) FROM  `phppos_rent` where cust_id='".$cid."'");
	$rowordno = mysqli_fetch_row($result1);
	
	
	$people = mysqli_query($con,"SELECT * FROM  phppos_people where person_id='$name'");
	$prow = mysqli_fetch_row($people);
	
	
	
	
	

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="https://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SHRINGAAR</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

</head>
<!--<script type="text/javascript" src="paging.js"></script>-->
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
body{
    font-size:10px;
}
td{
    padding:3px;
}
    .tnc li{
        font-size:12px;
    }
    p{
        margin:0;
        padding:0;
    }
    pre{
        display:none;
    }
</style>

<div id="bill">

        <p style="text-align:center;"><B><U> CONFIRMATION MEMO </U></B></font></p>
        
        
        
<table width="826" border="0" align="center">
<tr>
<td width="820" height="42">
    
    <table width="100%">
       <tr>
          
          <td style="padding:0px; margin:0px;">
              <div><u><b style="font-size:11px;">We Rent, Sell And Customise </b></u></div>
              <ul style="margin:0;font-size:10px;">
                  <li>Bridal Jewellery & Accessories</li>
                  <li>Lehenga, Evening Gowns, Blouse</li>
                  <li>All Kinds Of Jewellery & Outfits</li>
              </ul>
              <br>
              
              <div><b><u style="font-size:11px;">Bank Account Details</u></b></div>

<?php if($company_name == 'SAKAR TRADE LINK'){ ?>

        <div style="font-size:10px;">SAKAR TRADE LINK</div>
              <div style="font-size:11px;">Account number: 50231010008836</div>
              <div style="font-size:11px;">IFSC: CNRB0015023</div>
              <div style="font-size:11px;">Bank name: CANARA BANK</div>
              <div style="font-size:11px;">Branch: VILE PARLE EAST BRANCH</div>
    <?php }else{ ?>
        <div style="font-size:10px;">SRI SHRINGARR FASHION STUDIO</div>
              <div style="font-size:11px;">Account number: 756305000529</div>
              <div style="font-size:11px;">IFSC: ICICI0007563</div>
              <div style="font-size:11px;">Bank name:  ICICI BANK</div>
              <div style="font-size:11px;">Branch: VILE PARLE EAST BRANCH</div>
    <?php } ?>


          
              
          </td>
          
          <td style=" padding: 0px; margin:0px; padding-left: 145px; ">
              
              <?php if($company_name == 'SAKAR TRADE LINK'){

?>

                                <img src="./img/ss_fly.png" width="250px" style="padding-right:70px">

<?php
              }else{
                  ?>
              <img src="sri_logo.jpg" width="250px"/ style="padding-right:70px">                  
                  <?php 
                  
              }
              
              ?>

          </td>
          
          image.png
          
          <td style="padding:0px; margin:0px;" style="font-size:11px;">
              <div>Shyamkamal Building B,</div>
              <div>Wing B/1, Flat No.104,1st Floor,</div>
              <div>Agarwal Market, Vile Parle (East),</div>
              <div>Mumbai-400057, India.</div>
              <div>Phone - +91-9324243011/ +91-7400413163</div>
              <div>Email - rajanipodar@gmail.com</div>
          </td>
       
       </tr>
       </table>
       
       <hr style="margin:3px;border-top: 1px solid #000;">

    <table width="100%">
        <tr>
            <div class="col-md-3">
                <td>
                              <?php if($company_name == 'SAKAR TRADE LINK'){ 
                     }
                     else{
                         ?>
                    
                    
                    <div style="text-align:left;font-size:14px;"> <b>UPI ID: srishringarrfashionstudio@icici</b> </div><br/><br/>
                    <div style="text-align:left"> <img src="img/sri_qr_icici.jpg" width="80px"> </div>
                    <br/>
                    
                         <?php
                     }
                     
              ?>
              
              
                    <div style="width: 300px; height:30px;"><b> Name :</b><?php echo $row[0] . " ".$row[1]; ?></div>
                    <div style="height:30px;"><b>Contact No: </b><?php echo $row[2]; ?></div>
                    <div><b> Address : </b><?php echo $row[4]." ".$row[5]; ?><br/><?php echo $row[6]; ?><?php echo $row[8]; ?> <?php echo $row[9]; ?></div>
                    <div style="height:30px;"><b> 2nd Person Name : </b>&nbsp;&nbsp;&nbsp; <?php echo $pname; ?></div>
                    <div style="height:30px;"><b> 2nd Contact No.: </b>&nbsp;&nbsp;&nbsp; <?php echo $pcontact; ?></div>
               </td>
            </div>
            <div class="col-md-9">
                <td><br/><br/><br/><br/><br/>
               <div style="width: 220px;height:30px;"><b> Through Name: </b><?php echo $prow[0]." ".$prow[1]; ?></b></div><br/>
               <div><b> Through Contact No:</b> <?php echo $tphone; ?></div><br/>
               <div style="height:30px;"><b> Pick-Up By: </b>&nbsp;</div>
               <div style="height:30px;"><?php if($del=="Customer Return"){
                echo "<b>Return By</b>";
                }else{ echo "<b>Delivery By</b>";}
           ?> :<b></b></div>
               
               <div style="height:30px;"> <b> Pick-Up Date : </b>
            	      <?php if($pick=="Customer"){ echo $pick_date; }else{
        	             echo $pick_date; } ?> </div>
        	             
                <div style="height:30px;"><?php if($del=="Customer Return"){ echo "<b>Return Date</b>";}else{ echo "<b>Delivery Date</b>"; }
	   
            ?>   : &nbsp;<b><?php if($del=="<b>Customer Delivery</b>"){ echo $del_date; }else{
		     echo $del_date;} ?></b></div>     
           </td>
           <td>
               <div style="width: 320px;"><b> Bill. No. </b><?php echo $rowordno[0]; ?><br/><b> Date: </b><?php echo $odate; ?></div>
               <div style="width: 320px;">
                   <br>
                   <b><u>TERMS & CONDITION:</u></b>
            <ul style="padding: 0;">
              
              <li><b>Once an order Is Booked, it will not be changed, exchange or cancelled.</b></li>
              <li>Rental is for 3 days only, 5% extra for each day.</li>
              <li>Security deposit is compulsory.</li>
              <li>Any damage done will be deducted from the security deposit.</li>
              <li><b>No money will be refunded under any circumstances.</b></li>
              <li>Subject to Mumbai Jurisdiction.</li>
            </ul>
               </div>
           </td>
            </div>
        </tr>
        
       
</table>
  
  <font size="2" >
  
  <table width="100%" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
    <th style="padding:3px;" width="96"><font size="2"><u><center>SR NO.</center></u></font></th>
    <th style="padding:3px;" width="96"><font size="2"><u><center>ITEM CODE</center></u></font></th>
    <th style="padding:3px;" width="130"><u>PARTICULARS</u></th>
    <th style="padding:3px;" width="96"><font size="2"><u><center>DESCRIPTION</center></u></font></th>
    <th style="padding:3px;" width="86"><font size="2"><u><center>MRP</center></u></font></th>
    <th style="padding:3px;" width="86"><font size="2"><u><center>QTY</center></u></font></th>
    <th style="padding:3px;" width="110"><font size="2"><u><center>RENT</center></u></font></th>
    <th style="padding:3px;" width="119"><font size="2"><u><center>DEPOSIT</center></u></font></th>
    <th style="padding:3px;" width="88"><font size="2"><u><center>TAXABLE RENT</center></u></font></th>
  </tr>
  
  <?php
  $a=1;
  $total=0;
  $total1=0;
  $p2=0;
  $tol1=$_POST['total'];
  ///echo $tol1;
  
  
  $totalCGST = 0 ; 
$totalGST = 0 ; 
$totalTaxableAmount = 0 ; 




$totalBeautician_discountAmount = 0 ; 
$beautician_discountAmount = 0 ; 

  for($i=0;$i<$d;$i++)
{
//echo "SELECT * FROM phppos_items WHERE name=".$design[$i];

$sq="SELECT * FROM phppos_items WHERE name='".$design[$i]."' and is_deleted = 0";
$res2 = mysqli_query($con,$sq);
$num2=mysqli_num_rows($res2);
$row1=mysqli_fetch_row($res2);


$prz=$_POST['prz'];
$qt=$_POST['qty'];
$q=$qt[$i];

////////update on 08/07/2013///$t2=mysqli_query($con,"update `phppos_items` set quantity=quantity-$q WHERE name='".$design[$i]."'");

$dep=$_POST['dep'];
 if($commis=="%"){
$p2=round((float)$amt[$i]*((float)$comm/100.0));
///$sum=$amt[$i]-$p;

}else{
//$p1=$comm;
$c=(float)$comm/(float)$tol1;
$b=$c*100;
$p2=round((float)$amt[$i]*((float)$b/100.0));

}
$rpt=(float)$prz[$i] -(float)$p2;

/////echo $rowordno[0]."/ ".$design[$i]." / ".$prz[$i]." / ".$amt[$i]."/".$p2."/".$rpt."<br/>";
 $t3=mysqli_query($con,"insert into order_detail(bill_id,item_id,rent,deposit,discount,total_amount,item_detail,commission_amt,qty)
 values('".$rowordno[0]."','".$design[$i]."','".$prz[$i]."','".$dep[$i]."','".$discount[$i]."','".$amt[$i]."','".$items[$i]."','".$rpt."','".$qt[$i]."')");





	
	
	
	
	
	



// Get product type 
// echo "Select * from phppos_items where name='".$row2[1]."'" ; 
$productsql = mysqli_query($con,"Select * from phppos_items where name='".$design[$i]."'");
$productsqlResult = mysqli_fetch_assoc($productsql);

$categoryName = $productsqlResult['category'];
$categorysql = mysqli_query($con,"select * from categories where category='".$categoryName."'");
$categorysqlResult = mysqli_fetch_assoc($categorysql);
$productType = $categorysqlResult['typ'];

$totalProductAmount = $amt[$i] ; 
// echo '$productType = ' . $productType  ; 
if($productType==1){
    // Jewellery 
    // calculate 3% total GST
    
    $thisProductTotalTaxable = $totalProductAmount/1.03 ; 
    $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.03 ; 
    $cgst = $sgst = $thisProductTotalGst / 2 ;  
    
    
}else if($productType==2){
    // Apparel 
    // calculate 12% total GST
    
    $thisProductTotalTaxable = $totalProductAmount/1.12 ;
    $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.12 ; 
    $cgst = $sgst = $thisProductTotalGst / 2 ;  

}
    $roundCGST = round($cgst,0) ; 
    
$totalTaxableAmount = $totalTaxableAmount + $thisProductTotalTaxable ; 
$totalCGST = $totalCGST + $cgst ; 
$totalGST = $totalGST + $thisProductTotalGst  ; 







// 	FLYROBE COMMISSION PART 


 if($company_name == 'SAKAR TRADE LINK'){
     
    //  netAmount calculate
    
    $beautician_discountType = $_REQUEST['commis'];
    $beautician_discount = $_REQUEST['comm'];
    
    
    
    
    
    $netAmount = $thisProductTotalTaxable ;
    $isBeauticianDiscountGiven = 0 ;
    if($beautician_discount > 0 ){
       if($beautician_discountType=='%'){
           $beautician_discountAmount = $thisProductTotalTaxable * ( $beautician_discount / 100 ) ;
           $netAmount = $thisProductTotalTaxable - $beautician_discountAmount ;
       }else{
            $beautician_discountAmount = $beautician_discount ;
            $netAmount = $thisProductTotalTaxable - $beautician_discountAmount ;
       }
       $isBeauticianDiscountGiven = 1; 
    }
    
    
    // echo '$beautician_discountAmount = ' .  $beautician_discountAmount ; 
    // echo '<br />';
    
 $totalBeautician_discountAmount = $totalBeautician_discountAmount + $beautician_discountAmount ;
    // isFlyrobeProduct 
    
    
    $phpitemssql = mysqli_query($con,"select supplier_id from phppos_items where name='".$design[$i]."'");
    $phpitemssql_result = mysqli_fetch_assoc($phpitemssql);
    $supplier_id = $phpitemssql_result['supplier_id'];
    

    $isFlyrobeProduct = ($supplier_id == '242875') ? '1' : '0';
    // End is flyrobe
    
    // Calcuate Commissions

    $commission = ($isFlyrobeProduct == 1) ? '35%' : '8%';
    $commision_amount = $netAmount * ($commission/100);
    
    // 
    
    $purchase_date = date('Y-m-d');
    
     
    $comsqlquery = "INSERT INTO flyrob_commission(productType,sku,purchase_id,totalProductAmount,isBeauticianDiscountGiven,beautician_discountType,beautician_discountRate,beautician_discountAmount, netAmount,isFlyrobeProduct,commision,commision_amount,purchase_date,status) 
     values('".$productType."','".$design[$i]."','".$rowordno[0]."','".$totalProductAmount."','".$isBeauticianDiscountGiven."','".$beautician_discountType."','".$beautician_discount."','".$beautician_discountAmount."','".$netAmount."','".$isFlyrobeProduct."','".$commission."','".$commision_amount."','".$purchase_date."','Visible')" ; 

    mysqli_query($con,$comsqlquery);


$overallCommisionAmount = $netAmount - $commision_amount ; 

$overallCommisionAmount = round($overallCommisionAmount);

mysqli_query($con,"update order_detail set commission_amt='".$overallCommisionAmount."' where bill_id='".$rowordno[0]."' and item_id='".$design[$i]."'");



    

 } 
 


// END FLYRBE COMMISSION PART 
	
	
	
	
	
	
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
    
						<td align="center">
							<?php echo $amt[$i] - ($roundCGST*2); ?>
						</td>
  </tr>
 
  <?php 
  $total+=(float)$dep[$i]+(float)$prz[$i];
  $total1+=$amt[$i]; 
  $a++;
  } 
  
  
  
  

    $updateFinalRent  = "update phppos_rent set total_comm='".round($totalBeautician_discountAmount,0)."' where bill_id='".$rowordno[0]."'"; 
    if(mysqli_query($con,$updateFinalRent)){
        // echo 'commission_updated';
    }else{
        // echo 'Not Updated' ;
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
$p=round((float)$total1*((float)$comm/100.0));
$sum=(float)$total1-(float)$p;
}
else
{
$p=$comm;
$sum=(float)$total1-(float)$p;
}
$famt=(float)$total1-(float)$p;
$ap= ((float)$total1+(float)$_POST['cardpercamt'])-(float)$pamount;
///echo $sum."/".$comm."/".$commis;
    $t4=mysqli_query($con,"update `phppos_rent` set amount='".$famt."',rent_amount='".$total1."',total_comm='".$p."' where bill_id='".$rowordno[0]."'");
    
    
     $updateFinalRent  = "update phppos_rent set total_comm='".round($totalBeautician_discountAmount,0)."' where bill_id='".$rowordno[0]."'"; 
    if(mysqli_query($con,$updateFinalRent)){
        // echo 'commission_updated';
    }else{
        // echo 'Not Updated' ;
    }
    
    
    
?>

 <td colspan="3" align="left"><b> Bill Made by : </b><?php echo $_POST['bill_by']; ?></td>
 <td colspan="3" align="right"><b> Total Qty : </b><?php echo $qty1; ?></td>
 <td colspan="3" align="center"> <b>Total Rent (Including GST) &nbsp; :&nbsp;</b><b><?php echo $total1+$_POST['cardpercamt']; ?></b></td>
 </tr>
 
 
 
                      <?php if($company_name == 'SAKAR TRADE LINK'){  ?>
              
              
                    <tr>
                        <td colspan="3"><b>CGST : </b> <?php echo round($totalCGST,0) ;  ?> </td>
                        <td colspan="3"><b>SGST : </b> <?php echo round($totalCGST,0) ;  ?> </td>
                        <td colspan="3"><b>Total GST : </b> <?php echo round($totalCGST,0) *2 ;  ?> </td>
                    </tr>
              <?php } ?>

                                
                                
 <tr>
    <td colspan="2.8" align="left"><b> Mode of Payment :  </b><?php echo $mode; ?></td>
    <td colspan="2.5" align="center"><b> Deposit : </b><?php echo $paid; ?></td>
    <td colspan="3" align="center"> <b>Paid Amount :</b><b><?php echo $pamount; ?></b></td>
    <td colspan="3" align="center"> <b>Balance : </b><b><?php echo $ap; ?></b></td>
 </tr>
 
  <tr>
    <td colspan="9"><b>Trial Date : </b> <?php echo $trail_date;?></td>     
 </tr>
 
  <tr>
    <td colspan="3" align="left"><b> Measurement :  </b><?php echo $measurement; ?></td>
    <td colspan="8" align="left"><b> Measurement Note : </b><?php echo $measurement_note; ?></td>

 </tr>
 
  <tr>
    <td colspan="9"><b>Delivery : </b> <?php echo $is_delivery;?></td>     
 </tr>
 
 <tr>
    <td colspan="9"><b>Note : </b> <?php echo $_POST['note'];?></td>     
 </tr>
 
 <tr>
    <td colspan="6" align="left"><b> Given By :  </b><?php ?></td>
    <td colspan="6" align="left"><b> Taken By : </b><?php  ?></td>

 </tr>
 
</table>
<!--<h6 style="font-size: 10px;">On Cancellation of Rent Bill, 25% of Rent will be deducted and for 75% of Rent Credit Note will be given for one month for purchase and Rent. </h6>-->
</font>

</td>
</tr>
     
</tr>



</table>

<div style="width:826px; margin:auto; padding-top: 25px; text-align: right; padding-bottom: 11px;">
    <p><b>SRI SHRINGARR FASHION STUDIO</b></p>
</div>


<div style="width:826px; margin:auto; text-align: right; padding: 40px;"> 
    <p><b>AUTH. SIGNATORY</b></p>
</div>



</div>
<br/><br/>
<div id="pageNavPosition"></div>
<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="rent.php">Back</a></center>
<br><br>
<br><br>
</body>
</html><?php }
////if($t1 && $t2 && $t3 && $t4){
if($t1 && $t3 && $t4){
// 	mysqli_query($con,"COMMIT;");
mysqli_commit($con);
	}
	else{
// 		mysqli_query($con,"ROLLBACK;");
mysqli_rollback($con);
		echo "<script> rollback(); </script>";
		}
	 ?>