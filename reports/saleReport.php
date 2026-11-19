<?php
ini_set( "display_errors", 0);
$s=$_GET['submit'];

$id=$_GET['cid'];
$from=$_GET['from'];
$to=$_GET['to_date'];
$pd=0;
$sum=0;
$totalSalesAmount = 0 ; 
$totalbalanceAmount = 0 ;


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

?>
<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css"  />
<script>
/////////////////

// function validate1(form1){
//  with(form1)
//  {
  
  
//   if(invno.value=="")
//   {
// if(cid.value== -1)
//  {
// alert("Please Select Customer Name to continue.");
// cid.focus();
// return false;
// }

// }
// return true;
// }
//  return true;
//  }

function validate1(form1) {
        if (form1.invno === "") {
            if (form1.cid.value === -1) {
                alert("Please Select Customer Name to continue.");
                form1.cid.focus();
                return false;
            }
        }
        return true;
    }

////////////// phone no	
function loadPhoneNo()
{
	//alert("hi");
var xmlhttp;
if (window.XMLHttpRequest)
  {// code for IE7+, Firefox, Chrome, Opera, Safari
  xmlhttp=new XMLHttpRequest();
  }
else
  {// code for IE6, IE5
  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
xmlhttp.onreadystatechange=function()
  {
  if (xmlhttp.readyState==4 && xmlhttp.status==200)
    {
		///alert(xmlhttp.responseText);
		var s=xmlhttp.responseText;
	    //alert(s);
		var s1=s.split('&&');
		//alert(s1[0]+"/"+s1[1]);
		if(s1[0]=="0")
		alert("No such Phone Number"); 
		else{
		document.getElementById("cid").value=s1[1];
		MakeRequest();
		}
		//document.getElementById("").value=s1[];
    //document.getElementById("myDiv").innerHTML=xmlhttp.responseText;

    }
  }
   var str=document.getElementById('phoneNo').value;
   //alert(str);
   xmlhttp.open("GET","getbyphone.php?cid="+str,true);
   xmlhttp.send();
}
</script>
 
<div style="text-align: center;">
  <a href="/pos/home_dashboard.php">Back</a>
  <table width="1096"  border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
  
<tr>
<td align="center">

<img src="bill.PNG" width="408" height="165"/><br><br>
<b>Sales Report</b>

</td></tr>
<tr>
<td width="1084"  valign="top">
      
     <center>
       <form  action="saleReport.php" onSubmit="return validate1(this)">
       <br/>
       
       <table width="1073" height="42"><tr>
       <td width="293" height="36"><strong>From Date :</strong>
         <input type="text" name="from" value="<?php echo $_REQUEST['from']; ?>" id="from" onClick="displayDatePicker('from');" /></td>  
       <td width="269" ><strong>To Date: </strong>
         <input type="text" name="to_date"  value="<?php echo $_REQUEST['to_date']; ?>" id="to_date" onClick="displayDatePicker('to_date');"/></td><td width="324" height="34">
         
         <strong> Phone Number:</strong> <input type="text" name="phoneNo" id="phoneNo" value=""  /> <a href="#" onClick="loadPhoneNo();">Find</a>
         
         <strong>Invoice Number:</strong> <input type="text" name="invno" id="invno" value=""  /> 
         <br />
         <strong>Customer Name:&nbsp;</strong>&nbsp;&nbsp;
         <select name="cid" id="cid">
           <option value="-1" >select</option>
           <?php 
// 	  include('config.php');
	  include('../db_connection.php') ;
$con=OpenSrishringarrCon();

	  
	  $result = mysqli_query($con,"SELECT * FROM  phppos_people order by first_name");
	  while($row = mysqli_fetch_row($result)){ 
	      $full_name = $row[0] ."  ". $row[1];
	      if($row[0] || $row[1]){ ?>
	       <option value="<?php echo $row[11]; ?>" ><?php echo $full_name; ?></option>   
	      <?php } 
           
            } ?>
         </select>
        </td><td width="167"> <input type="submit" value="Search" name="submit" /></td></tr></table>
       </form></center>
 </td></tr>
 
<tr><td height="103">
  <?php

if(isset($s)){
       
 // echo $from;
   if($from=="" && $to==""){
	  $qry="SELECT * FROM  `approval` where status='S'";
		//echo "all";
		
		if($id!="-1")
		{
		    
		 $qry.=" and cust_id='$id'";
	   
		}
   }else if($from==""){
	    $qry="SELECT * FROM  `approval` where bill_date <=STR_TO_DATE('".$to."','%d/%m/%Y') and status='S'";
	
	if($id!="-1")
		{
		    
		 $qry.=" and cust_id='$id'";
	   
		}
	
	 //  echo "to";
   }else if($to==""){
	// echo "SELECT * FROM  `approval` where cust_id='$id' and bill_date=STR_TO_DATE('".$from."','%d/%m/%Y') and status='A'";
	      $qry="SELECT * FROM  `approval` where  bill_date >=STR_TO_DATE('".$from."','%d/%m/%Y') and status='S'";
	
	if($id!="-1")
		{
		    
		 $qry.=" and cust_id='$id'";
	   
		}
	   
   }else{
   $qry="SELECT * FROM  `approval` where bill_date BETWEEN STR_TO_DATE('".$from."','%d/%m/%Y') and STR_TO_DATE('".$to."','%d/%m/%Y') and status='S'";
   	if($id!="-1")
		{
		    
		 $qry.=" and cust_id='$id'";
	   
		}
    // echo "between";
   }
   
   
   
   if($_GET["invno"]!="")
   {
       
     $qry.=" and bill_id='".$_GET["invno"]."'";  
   }
//   echo  $qry;
   
   
   
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
 // echo $from;
   if($from=="" && $to==""){
// 	  $qry_new="SELECT * FROM  `approval` where cust_id='$id' and status='A'";
	  
	  $qry_new="SELECT * FROM  `approval` where cust_id='$id' and status='S'";
		//echo "all";
   }else if($from==""){
	   // $qry_new="SELECT * FROM  `approval` where cust_id='$id' and bill_date <=STR_TO_DATE('".$to."','%d/%m/%Y') and status='A'";
	$qry_new="SELECT * FROM  `approval` where cust_id='$id' and bill_date <=STR_TO_DATE('".$to."','%d/%m/%Y') and status='S'";
	 //  echo "to";
   }else if($to==""){
	// echo "SELECT * FROM  `approval` where cust_id='$id' and bill_date=STR_TO_DATE('".$from."','%d/%m/%Y') and status='A'";
	   //   $qry_new="SELECT * FROM  `approval` where cust_id='$id' and bill_date >=STR_TO_DATE('".$from."','%d/%m/%Y') and status='A'";
	   $qry_new="SELECT * FROM  `approval` where cust_id='$id' and bill_date >=STR_TO_DATE('".$from."','%d/%m/%Y') and status='S'";
	   
   }else{
//   $qry_new="SELECT * FROM  `approval` where cust_id='$id' and bill_date BETWEEN STR_TO_DATE('".$from."','%d/%m/%Y') and STR_TO_DATE('".$to."','%d/%m/%Y') and status='A'";
  $qry_new="SELECT * FROM  `approval` where cust_id='$id' and bill_date BETWEEN STR_TO_DATE('".$from."','%d/%m/%Y') and STR_TO_DATE('".$to."','%d/%m/%Y') and status='S'";
    // echo "between";
   }
   
 
//  echo $qry_new ; 
$res_new=mysqli_query($con,$qry_new);                
//$num=mysqli_num_rows($res_new);
       

// var_dump($_REQUEST);

$fromDate=$_REQUEST['from']; 
$fromDate = date('Y-m-d',strtotime($fromDate));

$toDate=$_REQUEST['to_date']; 
$toDate = date('Y-m-d',strtotime($toDate));



			
				 				 
?>
<table  border="1" cellpadding="4" cellspacing="0" width="1085" align="left">
 <tr>
    <th width='115' height="34"><U>Sr.No.</U></th>
    <th width='115' height="34"><U>Bill No.</U></th>
    <th width='183'><u>Customer Name</u></th>
    <th width='127'><U>Bill Date</U></th>
    <th width='251'><U>Balance Amount</U></th>
    <th width='165'><u>Bill Detail</u></th>
  </tr>
<?php
$i=1;
while($row = mysqli_fetch_array($res)) 
 {
     
     $new_bill_number = $row['new_bill_number'];
    // echo '<pre>';
    // print_r($row);
    // echo '</pre>';
          
     $balanceAmount = 0;
     
 $s1=0;			
$pd=0;
$ba=0;
$na=0;	
$ra=0;


$thisToDate = $_REQUEST['to_date'];

// Display for debugging
// echo '$thisToDate: ' . $thisToDate ; 
// echo '<br />';

// Convert the date format from DD/MM/YYYY to YYYY-MM-DD
if ($thisToDate) {
    $dateParts = explode('/', $thisToDate);
    if (count($dateParts) == 3) {
        $thisToDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0]; // Reformat to YYYY-MM-DD
    } else {
        // echo "Invalid date format.";
    }
}

// Display for debugging
// echo '$thisToDate (reformatted): ' . $thisToDate ; 
// echo '<br />';

$balancesql =  "select sum(amountTotal) as amountTotal , sum(paid_amount) as paid_amount from approval where status='S' and cust_id='".$row[1]."'" ; 

if ($toDate && $fromDate) {
    $thisToDate = date('Y-m-d', strtotime($thisToDate));
    $balancesql .= " AND bill_date between '".$fromDate."' AND '".$thisToDate."'" ; 
}

$balancesql .= " AND bill_id='".$row[0]."'" ; 

// Display final query for debugging
// echo $balancesql ; 



// echo '<br />';
$balanceAmmountSql = mysqli_query($con,$balancesql);








$balanceAmmountSqlResult = mysqli_fetch_assoc($balanceAmmountSql);
$paid_amount = $balanceAmmountSqlResult['paid_amount'];
$amountTotal = $balanceAmmountSqlResult['amountTotal'];

$balanceAmount = $amountTotal - $paid_amount ; 
// echo '<br />';



$sql1=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$row[1]'");
$row1=mysqli_fetch_row($sql1);


$qry42="SELECT SUM( paid_amount ) FROM  `approval` WHERE  `cust_id` ='$id'";
$res42=mysqli_query($con,$qry42);
$row42=mysqli_fetch_row($res42);

// echo "SELECT sum(amount) as total_amount_paid FROM `paid_amount` WHERE `bill_id` = '".$row[0]."'" ; 
$total_paid_sql = mysqli_query($con,"SELECT sum(amount) as total_amount_paid FROM `paid_amount` WHERE `bill_id` = '".$row[0]."'");
$total_paid_sql_result = mysqli_fetch_assoc($total_paid_sql);
$total_paid_amount = $total_paid_sql_result['total_amount_paid'];

// echo "select sum(amount) as total_amount from approval_detail where bill_id in(select bill_id from approval where status='S' and cust_id='".$row[1]."')" ; 
$detail_sql = mysqli_query($con,"select sum(amount) as total_amount from approval_detail where bill_id in(select bill_id from approval where status='S' and cust_id='".$row[1]."')");
$detail_sql_result = mysqli_fetch_assoc($detail_sql);
$total_amount = $detail_sql_result['total_amount'];





$s = (float)$row3[0]-(float)$row2[0];
$a=0;
$a1=0;

$gstott=0;
$totalAmount = 0 ; 
// echo '<br />';
 $qry4="SELECT *  FROM `approval_detail` WHERE bill_id ='$row[0]'";

$res4=mysqli_query($con,$qry4);

while($row4=mysqli_fetch_array($res4)){

// $a=round(((float)$row4[7]/(float)$row4[2])*(float)$row4[4]);

if ((float)$row4[2] != 0) {
    $a = round(((float)$row4[7] / (float)$row4[2]) * (float)$row4[4]);
} else {
    $a = 0; // Or another fallback value like null or '-'
}


$a1+=$a;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba+=$row4[7];


$gstott=(float)$gstott+(float)$row4[11]+(float)$row4[13]+(float)$row4[15];

$totalAmount  = $totalAmount + $row4['amount'];


}

// echo '$totalAmount = ' . $totalAmount ; 
// echo $ba;
$pd=$row[4];
$na=$ba;
$s1=$ba-$a1;

// echo $s1;

$s1+=$gstott;//add gst amounts

$s1+=$row[15];//add card amount
//echo "cust=".$row[11]."paidamt=".$pd."bal amt=".$na."return".$a1."net amt=".$s1."<br/>";
while($row_new = mysqli_fetch_row($res_new)) 
{
$qry10="SELECT *  FROM `approval_detail` WHERE bill_id ='$row_new[0]'";
$res10=mysqli_query($con,$qry10);

while($row10=mysqli_fetch_row($res10)){

if ((float)$row10[2] != 0) {
    $a10 = round(((float)$row10[7] / (float)$row10[2]) * (float)$row10[4]);
} else {
    $a10 = 0; // or any default value you'd prefer
}


// $a10=round(((float)$row10[7]/(float)$row10[2])*(float)$row10[4]);
$a11+=$a10;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba10+=$row10[7];
}

$s10=(float)$ba10-(float)$a11;
$app_amount=null;
$app_amount+=$s10;
}
?>				   
				   
<tr>
<td width="115"><?php echo $i; ?></td>
<td width="115"><?php echo $new_bill_number ? $new_bill_number : $row[0]; ?></td>
<td width="183" align="center"><?php echo $row1[0]." " .$row1[1]; ?></td>
<td width="127"> <?php if(isset($row[2]) and $row[2]!='0000-00-00') echo date('d/m/Y',strtotime($row[2])); ?></td>

<td width="251"><?php 

echo $balanceAmount ; 
// echo $totalAmount - $row[4];

// $sum+=$row[4];
$sum+=$balanceAmount ; 
$totalbalanceAmount = $totalbalanceAmount + $balanceAmount ; 

$totalSalesAmount = $totalSalesAmount+$amountTotal ; 


?></td>



<td  align="center" width="165"><a href="sales_report_detail.php?id=<?php echo $row[0]; ?>" target="_new">Bill Detail</a>
 
 <?php 
 if($row[12]=="1")
 {
 ?>
 <a href="sales_report_detailgstnew.php?id=<?php echo $row[0]; ?>" target="_new">Gst Bill</a>
 <?php
 
 }
 ?>
 
 </td>

     </tr>
				
			<?php $i++; } ?>
            <tr>
            <td colspan="4" align="right"><b>Total Sales Amount :</b></td>
          
            <td colspan="2"><?php echo $totalSalesAmount  ; ?></td>
			</tr>
             <tr>
            <td colspan="4" align="right"><b>Total Approval and Sales Amount :</b></td>
          
            <td colspan="2"><?php /* echo $sum." ".$app_amount;*/$net=$sum+$app_amount;
            // echo $net;
            echo $total_amount ; 
            ?></td>
			</tr>
			<tr>
			<td colspan="4" align="right"><b>Total Paid Amount :</b></td>
			 <td width="136" colspan="2">
			 <?php echo 
			 $totalSalesAmount - $totalbalanceAmount ; 
			 //$total_paid_amount ;
			 
			 ?>
			     <?php /*if($num411==0 || $num411=="" || $row41[0]=="") {  $pd11=$row42[0]; }else{  $pd11=$row41[0];  } echo $pd11;*/ 
			     //echo $row42[0]; 
			     ?>
			     
			 </td>
			 </tr>
			  <tr>
    <td colspan="4" align="right"><b>Total Balance Amount :</b></td>
    <!--<td width="103"><?php ///echo $pd; ?></td>-->
    <td width="136" colspan="2"><?php echo $totalbalanceAmount ; 
    // $total_amount-$total_paid_amount ." /-"; 
    
    ?></td>
	</tr>
            </table>
            <?php } else { }?>
	</td></tr> </table>
</div>

<div align="center">You are using Point Of Sale Version 10.5 .</div>