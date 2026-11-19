<?php
ini_set( "display_errors", 0);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// 	include('config.php');
	include('../db_connection.php') ;
$con=OpenSrishringarrCon();

	
	$s=$_GET['search'];
	
	$thr=$_GET['through'];
	$throgh=explode("/",$thr);
$from=$_GET['from'];
$to=$_GET['to'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Commission</title>
<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css"  />
<script type="text/javascript">
function validate(form){
	with(form){
		if(through.value=="0"){
			alert("Please select Through Name");
			through.focus();
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
	   // alert(s);
		var s1=s.split('&&');
		
		//alert(s1[0]+"/"+s1[1]);
		if(s1[0]=="0")
		alert("No such Phone Number"); 
		else{
		document.getElementById("through").value=s1[1];
// 		MakeRequest1();
		}
		
		
		//document.getElementById("").value=s1[];
       //document.getElementById("myDiv").innerHTML=xmlhttp.responseText;

    }
  }
   var str=document.getElementById('phoneNo').value;
//   alert(str);
   xmlhttp.open("GET","getbyphone.php?cid="+str,true);
   xmlhttp.send();
}



</script>
</head>

<body>
<form action="commReport.php" onsubmit="return validate(this)" name="form">
<table width="88%" align="center">
<tr><td height="247" align="center">
<a href="/pos/home_dashboard.php">Back</a><br><br>
<img src="bill.PNG" width="408" height="165"/>
</td></tr>
	<tr>
    	<td width="281"><strong>From Date : </strong><input type="text" name="from" id="from" onClick="displayDatePicker('from');" />
        <strong>To Date : </strong><input type="text" name="to" id="to" onClick="displayDatePicker('to');" />
        
        <strong> Phone Number:</strong> <input type="text" name="phoneNo" id="phoneNo" value=""  /> <a href="#" onClick="loadPhoneNo();">Find</a> 
        
        <strong>Through Name : </strong><select name="through" id="through" style="width:200px;"><option value="0">Select Name (Mobile)</option>
        <?php 
    // SQL query to get data from both phppos_rent and phppos_people using a JOIN
    $result = mysqli_query($con, "
        SELECT p.first_name, p.person_id, p.last_name, r.new_bill_number
        FROM phppos_rent r
        INNER JOIN phppos_people p ON r.throught = p.person_id
        WHERE r.throught <> '-1' AND r.throught <> ''
        GROUP BY p.person_id
        ORDER BY p.first_name ASC
    ");

    // Fetching results and displaying options
    while ($row = mysqli_fetch_assoc($result)) { 
        if (!empty($row['last_name'])) { // Replace 'some_column' with the actual column you want to check
?>
           <option value="<?php echo $row['person_id']; ?>">
               <?php echo $row['first_name'] . ' '.   $row['last_name'] ; ?>
           </option>
<?php 
        } 
    } 
?>
</select>

       <input type="submit" name="search" id="search" value="Search"/></td>
	</tr>
    <tr><td>
    <?php

if(isset($s)){
       
  
   if($from=="" && $to==""){
	  $qry="SELECT * FROM  `phppos_rent` where throught='$throgh[0]' ";
	  
		//echo "all".$throgh[0];
   }else if($from==""){
	    $qry="SELECT * FROM  `phppos_rent` where throught='$throgh[0]' and bill_date=STR_TO_DATE('".$to."','%d/%m/%Y')";
	
	 //  echo "to";
   }else if($to==""){
	 
	      $qry="SELECT * FROM  `phppos_rent` where throught='$throgh[0]'  and bill_date=STR_TO_DATE('".$from."','%d/%m/%Y')";
	
	   
   }else{
   $qry="SELECT * FROM  `phppos_rent` where throught='$throgh[0]'  and bill_date BETWEEN STR_TO_DATE('".$from."','%d/%m/%Y') and STR_TO_DATE('".$to."','%d/%m/%Y')";
    // echo "between";
   }
   
//   echo $qry; 
   
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
$sum=0;	
$sumrent=0;				
				 				 
?><br/><a href="comm_paid.php?name=<?php echo $throgh[0]?>  ">Commission Paid</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="commPaid_report.php?name=<?php echo $throgh[0] ?>"  target="_new">Commission Payment Report</a><br/>
    <table  border="1" cellpadding="4" cellspacing="0" width="100%" align="center">
 <tr>
 <th width='43' height="29"><U>Sr.No.</U></th>
    <th width='54' height="29"><U>Bill No.</U></th>
    <th width='132'><u>Customer Name</u></th>
    <th width='82'><U>Bill Date</U></th>
    <th width='99'><U>Rent Amount</U></th>
    <th width='128'><U>Through Name</U></th>
     <th width='144'><U>Through Mobile</U></th>
    <th width='141' colspan="2"><U>Commission</U></th>
    
    <th width='129'><u>Total Commission</u></th>
  </tr>
<?php
$i=1;
while($row = mysqli_fetch_array($res)) 
 {
     $old_bill_id = $row['bill_id'];
     $order_detail_sql = mysqli_query($con,"select * from order_detail where bill_id = '".$old_bill_id."'");
     $order_detail_sql_result = mysqli_fetch_assoc($order_detail_sql);
     $sku = $order_detail_sql_result['item_id'];
     
     $items_sql = mysqli_query($con,"select * from phppos_items where name='".$sku."'");
     $items_sql_result = mysqli_fetch_assoc($items_sql);
     $product_type = $items_sql_result['category_type'];
     
     if($product_type==1){
         $gst = 3 ; 
     } else if($product_type==2){
         $gst = 18 ;
     }
     
     $commission_gst = ($row[23]*$gst)/100;
     $new_commission = $row[23] - $commission_gst ; 

     $new_bill_number = $row['new_bill_number'];
	 $qry1="SELECT * FROM phppos_people where person_id='$row[1]'";
	 $result1=mysqli_query($con,$qry1);
	  $row1=mysqli_fetch_row($result1);
	 	  
	 $qry8="SELECT * FROM phppos_people where person_id='$row[8]'";
	 $result8=mysqli_query($con,$qry8);
	  $row8=mysqli_fetch_row($result8);
//echo $row2[0]."&&".$s;
?>				   
				   
<tr>
<td width="43" align="center"><?php echo $i; ?></td>
<td width="54" align="center" style="white-space:nowrap;"><?php echo $new_bill_number ? $new_bill_number : $row[0]; ?></td>
<td width="132" align="center"><?php echo $row1[0]." " .$row1[1]; ?></td>
<td width="82" align="center"> <?php if(isset($row[2]) and $row[2]!='0000-00-00') echo date('d/m/Y',strtotime($row[2])); ?></td>
<td width="99" align="center"><?php echo $row[3];$sumrent+=$row[3]; ?></td>
<td width="128" align="center"><?php echo $row8[0]; ?></td>
<td width="144" align="center"><?php echo $row8[2]; ?></td>
<td align="center"><?php echo $row[22];


if($row[17]=="%") { echo $row[18]." " .$row[17];} else { echo $row[17]." " .$row[18]; } 
?>
</td>
<td width="144" align="center"><?php echo $gst . '%' ; ?></td>
<td align="center" style="white-space:nowrap;"><?php
// echo $row[23] . '-' . $commission_gst . ' = ' . $new_commission; 
// $sum+=$new_commission;
$sum+=$row[23];
echo $row[23]; 


//  $new_commission  ;
?></td>

     </tr>
				
			<?php $i++; } ?>
            <tr>
<td width="43" align="center">&nbsp;</td>
<td width="54" align="center">&nbsp;</td>
<td width="132" align="center">&nbsp;</td>
<td width="82" align="center">&nbsp;</td>
<td width="99" align="center">Total : <?php echo $sumrent." /-."; ?></td>
<td></td>
<td colspan="3" align="right"><b><u>Total Commission</u></b></td>
<td align="center"><?php echo $sum."/-"; ?></td>

     </tr>
     <?php  $qry1=mysqli_query($con,"SELECT sum(amount) FROM  `commission_paid` where name='$throgh[0]' ");
    $row1=mysqli_fetch_row($qry1);
	?>
                 <tr>
<td width="43" align="center">&nbsp;</td>
<td width="54" align="center">&nbsp;</td>
<td width="132" align="center">&nbsp;</td>
<td width="82" align="center">&nbsp;</td>
<td width="99" align="center">&nbsp;</td>
<td></td>
<td colspan="3" align="right"><b><u>Paid Commission</u></b></td>
<td align="center"><?php echo $row1[0]."/-"; ?></td>

     </tr>
                 <tr>
<td width="43" align="center">&nbsp;</td>
<td width="54" align="center">&nbsp;</td>
<td width="132" align="center">&nbsp;</td>
<td width="82" align="center">&nbsp;</td>
<td width="99" align="center">&nbsp;</td>
<td></td>
<td colspan="3" align="right"><b><u>Balance Commission</u></b></td>
<td align="center"><?php echo $sum-$row1[0]."/-"; ?></td>

     </tr>
        </table>
            <?php } else { }?>
	</td></tr> </table></tr>
</table>
</form>
<?php CloseCon($con);?>
</body>
</html>