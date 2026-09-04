<?php

  include('../db_connection.php') ;
        $con=OpenSrishringarrCon();

	  
	  
ini_set( "display_errors", 0);
$s=$_GET['submit'];

$id=$_GET['cid'];
$from=$_GET['from'];
$to=$_GET['to_date'];
$pd=0;
$sum=0;
?>
<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css"  />
<script>
/////////////////

function validate1(form1){
 with(form1)
 {
  
if(cid.value== -1)
 {
alert("Please Select Customer Name to continue.");
cid.focus();
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
<b>Approval Report</b>

</td></tr>
<tr>
<td width="1084"  valign="top">
      
     <center>
       <form  action="appReport.php" onSubmit="return validate1(this)">
       <br/>
       
       <table width="1073" height="42"><tr>
       <td width="293" height="36"><strong>From Date :</strong>
         <input type="text" name="from" id="from" onClick="displayDatePicker('from');" /></td>  
       <td width="269" ><strong>To Date: </strong>
         <input type="text" name="to_date" id="to_date" onClick="displayDatePicker('to_date');"/></td><td width="324" height="34">
      
      <?
      

      ?>   
         <strong> Phone Number:</strong> <input type="text" name="phoneNo" id="phoneNo" value=""  /> <a href="#" onClick="loadPhoneNo();">Find</a> <br />
         <strong>Customer Name:&nbsp;</strong>&nbsp;&nbsp;
         <select name="cid" id="cid">
           <option value="-1" >select</option>
           <?php 
            // include('config.php');
	  
	  
	  $result = mysqli_query($con,"SELECT * FROM phppos_people where first_name<>'' order by first_name asc");
	  while($row = mysqli_fetch_row($result)){ 
	  ?>
           <option value="<?php echo $row[11]; ?>" ><?php echo $row[0] ."  ". $row[1]; ?></option>
           <?php } ?>
         </select>
        </td><td width="167"> <input type="submit" value="Search" name="submit" /></td></tr></table>
       </form></center>
 </td></tr>
 
<tr><td height="103">
  <?php

if(isset($s)){
       
 // echo $from;
   if($from=="" && $to==""){
	  $qry="SELECT * FROM  `approval` where cust_id='$id' and status='A'";
	  $qry42="SELECT SUM( paid_amount ) FROM  `approval` WHERE  `cust_id` ='$id'";
		//echo "all";
   }else if($from==""){
	    $qry="SELECT * FROM  `approval` where cust_id='$id' and bill_date <=STR_TO_DATE('".$to."','%d/%m/%Y') and status='A'";
	$qry42="SELECT SUM(amount) FROM  `paid_amount` WHERE  `bill_id` ='$id' and return_date <=STR_TO_DATE('".$to."','%d/%m/%Y')";
	 //  echo "to";
   }else if($to==""){
	// echo "SELECT * FROM  `approval` where cust_id='$id' and bill_date=STR_TO_DATE('".$from."','%d/%m/%Y') and status='A'";
	      $qry="SELECT * FROM  `approval` where cust_id='$id' and bill_date >=STR_TO_DATE('".$from."','%d/%m/%Y') and status='A'";
	$qry42="SELECT SUM(amount) FROM  `paid_amount` WHERE  `bill_id` ='$id' and return_date >=STR_TO_DATE('".$from."','%d/%m/%Y')";
	   
   }else{
   $qry="SELECT * FROM  `approval` where cust_id='$id' and bill_date BETWEEN STR_TO_DATE('".$from."','%d/%m/%Y') and STR_TO_DATE('".$to."','%d/%m/%Y') and status='A'";
    // echo "between";
    $qry42="SELECT SUM(amount) FROM  `paid_amount` WHERE  `bill_id` ='$id' and return_date BETWEEN STR_TO_DATE('".$from."','%d/%m/%Y') and STR_TO_DATE('".$to."','%d/%m/%Y')";
   }
   
   echo $qry;
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
 // echo $from;
   if($from=="" && $to==""){
	  $qry_new="SELECT * FROM  `approval` where cust_id='$id' and status='S'";
		//echo "all";
   }else if($from==""){
	    $qry_new="SELECT * FROM  `approval` where cust_id='$id' and bill_date <=STR_TO_DATE('".$to."','%d/%m/%Y') and status='S'";
	
	 //  echo "to";
   }else if($to==""){
	// echo "SELECT * FROM  `approval` where cust_id='$id' and bill_date=STR_TO_DATE('".$from."','%d/%m/%Y') and status='A'";
	      $qry_new="SELECT * FROM  `approval` where cust_id='$id' and bill_date >=STR_TO_DATE('".$from."','%d/%m/%Y') and status='S'";
	
	   
   }else{
   $qry_new="SELECT * FROM  `approval` where cust_id='$id' and bill_date BETWEEN STR_TO_DATE('".$from."','%d/%m/%Y') and STR_TO_DATE('".$to."','%d/%m/%Y') and status='S'";
    // echo "between";
   }
$res_new=mysqli_query($con,$qry_new);                
//$num=mysqli_num_rows($res_new);
$res42=mysqli_query($con,$qry42);
$row42=mysqli_fetch_row($res42);
       			 				 
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
while($row = mysqli_fetch_row($res)) 
 {
$s1=0;			
$pd=0;
$ba=0;
$na=0;	
$ra=0;
$sql1=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$row[1]'");
$row1=mysqli_fetch_row($sql1);
 
 /* $qry41="SELECT sum(amount) FROM `paid_amount` WHERE `bill_id`='$id' and amt_of='A'";
$res41=mysqli_query($con,$qry41);
$num411=mysqli_num_rows($res41);
$row41=mysqli_fetch_row($res41);*/
///echo $id."/".$num411."<br/>";
 

$s=$row3[0]-$row2[0];
$a=0;
$a1=0;

$qry4="SELECT *  FROM `approval_detail` WHERE bill_id ='$row[0]'";
$res4=mysqli_query($con,$qry4);

while($row4=mysqli_fetch_row($res4)){

$a=round(($row4[7]/$row4[2])*$row4[4]);
$a1+=$a;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba+=$row4[7];
}
$pd=$row[4];
$na=$ba;
$s1=$ba-$a1;
//echo "cust=".$row[11]."paidamt=".$pd."bal amt=".$na."return".$a1."net amt=".$s1."<br/>";

?>				   
				   
<tr>
<td width="115"><?php echo $i; ?></td>
<td width="115"><?php echo $row[0]; ?></td>
<td width="183" align="center"><?php echo $row1[0]." " .$row1[1]; ?></td>
<td width="127"> <?php if(isset($row[2]) and $row[2]!='0000-00-00') echo date('d/m/Y',strtotime($row[2])); ?></td>

<td width="251"><?php echo $s1; $sum+=$s1; 
while($row_new = mysqli_fetch_row($res_new)) 
{
$qry10="SELECT *  FROM `approval_detail` WHERE bill_id ='$row_new[0]'";
$res10=mysqli_query($con,$qry10);

while($row10=mysqli_fetch_row($res10)){

$a10=round(($row10[7]/$row10[2])*$row10[4]);
$a11+=$a10;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba10+=$row10[7];
}

$s10=$ba10-$a11;
$salesamount=null;
$salesamount+=$s10;
}?></td>
 <td  align="center" width="165"><a href="app_report_detail.php?id=<?php echo $row[0]; ?>" target="_new">Bill Detail</a></td>

     </tr>
				
			<?php $i++; } ?>
            <tr>
              <td colspan="4" align="right"><b>Total Approval Amount :</b></td>
           
          <td colspan="2"><?php echo $sum; ?></td></tr>
           <tr>
              <td colspan="4" align="right"><b>Total Approval and Sales Amount :</b></td>
           
          <td colspan="2"><?php /*echo $sum." ".$salesamount; */$net= $sum+$salesamount; echo $net;?></td></tr>
            <tr>
              <td colspan="4" align="right"><b>Total Paid Amount :</b></td>
           
            <td colspan="2"><?php /*if($num411==0 || $num411=="" || $row41[0]=="") {  $pd11=$row42[0]; }else{  $pd11=$row41[0];  } echo $pd11;*/ echo $row42[0];?></td></tr>
            <tr>
              <td colspan="4" align="right"><b>Total Balance Amount :</b></td>
           
            <td colspan="2"><?php echo $net-$row42[0]." /-"; ?></td></tr>
            </table>
            <?php } else { }?>
	</td></tr> </table>
</div>
<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>