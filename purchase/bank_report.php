<script type="text/javascript" src="datepicker/datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="datepicker/date_css.css"  />
<script>
/*var isCtrl = false;
document.onkeyup=function(e){
	if(e.which == 17) isCtrl=false;
}
document.onkeydown=function(e){
	if(e.which == 17) isCtrl=true;
	if(e.which == 66 && isCtrl == true) {
		document.getElementById("barcode").focus(); 
		return false;
	}
	
}*/
function showtrans(ld)
{
	//alert("hii");
		
		 var bank_id=document.getElementById('bank_id').value;
		 var frmdate=document.getElementById('frmdate').value;
		 var todate=document.getElementById('todate').value;
		 if(bank_id==0 && ld=='')
		 {
			alert("Please Select Bank.");
		 	document.getElementById('bank_id').focus(); 
	 		return false;  
			}
		document.getElementById('back').innerHTML="<img src='loading.gif' width='150px' height='100px'>";	
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
		//alert(xmlhttp.responseText);
	document.getElementById('back').innerHTML=xmlhttp.responseText;
    //document.getElementById('image').innerHTML="";
    }
  }
xmlhttp.open("GET","gettrans.php?bank_id="+bank_id+"&frmdate="+frmdate+"&todate="+todate+"&ld="+ld,true);
xmlhttp.send();

	
}
function printDiv() {
     var printContents = document.getElementById('back').innerHTML;
     var originalContents = document.body.innerHTML;

     document.body.innerHTML = printContents;

     window.print();

     document.body.innerHTML = originalContents;
}
function edit_memo(cnt,ld)
{
  var rem=document.getElementById('rem'+cnt).value;
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
		//alert(xmlhttp.responseText);
	if(xmlhttp.responseText=="1")
	{
		showrem('showrem'+cnt);
		document.getElementById('showrem'+cnt+'1').innerHTML=rem;
	}
	else
	{
		alert('Error in completing request. Please try again');
	}
    }
  }
xmlhttp.open("GET","edit_bank_report.php?rem="+rem+"&id="+ld,true);
xmlhttp.send();

	
}
function typealert()
{
	if(confirm("Are you sure you want to Delete this transaction."))
	return true;
	else
	return false;
}
function showrem(id)
{
if(document.getElementById(id).style.display=='none')
document.getElementById(id).style.display='block';
else
document.getElementById(id).style.display='none';
if(document.getElementById(id+'1').style.display=='none')
document.getElementById(id+'1').style.display='block';
else
document.getElementById(id+'1').style.display='none';
}
</script>
<style type="text/css">
@media print {
#back {
display:block;
}
#print_not {
display:none;
}

}
</style>
<body onLoad="showtrans('ld')">

<?php
	if(isset($_SESSION['success']))
	{
		if($_SESSION['success']==0)	
		{
			$result="Problem please try again.";
		}
		else if($_SESSION['success']==1)	
		{
			$result="Request completed sucessfully.";
		}
?>
<script>
alert('<?php echo $result; ?>');
</script>
<?php
		unset($_SESSION['success']);
	}
?>
<div style="text-align: center;">
<a href="/pos/home_dashboard.php">Back</a>
<table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
<tr>
<td align="center"> 
<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$result5=mysqli_query($con,"select * from `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($result5,10);
$row7=mysqli_fetch_array($result5);
?>

<img src="../reports/bill.png" width="408" height="165"/><br/><br/>
<b>*** Bank Report ***</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      
      
     <!--<form id="purchse" action="processbanktrans.php" method="post" onSubmit="return details();">-->
     <hr>
     <?php $qryitem=mysqli_query($con,"select bank_name, bank_id from banks");
	 	$items=array();
		
		
			//print_r($items);
		
	 ?>
     
     <table width="70%"><tr><td width="121">Bank Name : </td><td width="129"><select name="bank_id" id="bank_id" onChange="showbal();"><option value="0">Select Name</option><?php  while($row=mysqli_fetch_row($qryitem))
		 {
				echo "<option value='".$row[1]."'>".$row[0]."</option>";
			}?></select></td><td width="280"> Date From : <input type="text" name="frmdate" id="frmdate" onClick="displayDatePicker('frmdate');"></td><td width="203"> To :<input type="text" name="todate" id="todate" onClick="displayDatePicker('todate');"></td><td width="159"><input type="button" name="btn"  value="All Transactions" onClick="showtrans()"><input type="button" value="Print this page" onclick="printDiv()" /></div></td></tr>
            </table>
      <div id="back">  </div>
     <br/>
  <!--</form>-->
      
</center>
</td>
</tr>
</table>
</div>
<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>