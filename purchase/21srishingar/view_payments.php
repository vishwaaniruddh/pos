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
function showbills()
{
	//alert("hii");
	
		 var supp_id=document.getElementById('supp_id').value;
		 var frmdate=document.getElementById('frmdate').value;
		 var todate=document.getElementById('todate').value;
		 
		 if(supp_id==0)
		 {
			alert("Please Select Supplier.");
		 	document.getElementById('supp_id').focus(); 
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
	if(supp_id=='-1')
	{
	document.getElementById('hide').style.display='none';
	document.getElementById('disname').style.display='none';
	}
    //document.getElementById('image').innerHTML="";
    }
  }
xmlhttp.open("GET","getpayments.php?supp_id="+supp_id+"&frmdate="+frmdate+"&todate="+todate,true);
xmlhttp.send();

	
}



function total()
{
	
	var payment = document.getElementsByClassName('payment');
	 var ttl=document.getElementsByClassName('ttl');
	
	 var sumamt=0;
	 for(i=0;i<ttl.length;i++)
	 {//alert("hii1");
		if(payment[i].checked)
		{	sumamt+=parseInt(ttl[i].value);
			//alert("hii"+sumamt);	
		}	 
	}
		document.getElementById('payamt').value=sumamt;
		if(sumamt>0)
		document.getElementById('paybtn').disabled=false;
		else
		document.getElementById('paybtn').disabled=true;
		//document.getElementById('totalqty').value=sumqty;*/
}

//////////////////Phone Number

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
		document.getElementById("supp_id").value=s1[1];
		showbills()
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
<body onLoad="">

<div style="text-align: center;">
<a href="/pos/home_dashboard.php">Back</a>
<table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
<tr>
<td align="center"> 
<?php
// include('config.php');
include('../../db_connection.php') ;
$con=OpenSrishringarrCon();

$result5=mysqli_query($con,"select * from `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($result5,10);
$row7=mysqli_fetch_array($result5);
?>

<img src="../reports/bill.png" width="408" height="165"/><br/><br/>
<b>***View Supplier`s Payments***</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      
      
     <form id="purchse" action="payment_supp.php" method="post" onSubmit="return details();">  
     <hr>
     <?php $qryitem=mysqli_query($con,"select *  from `phppos_suppliers`  order by company_name ASC ");
	 	//$items=array();
		
		
			//print_r($items);
		
	 ?>
     
     <table width="70%">
     <tr><td width="103">Phone Number:</td> <td colspan="2"> <input type="text" name="phoneNo" id="phoneNo" value=""  /> <a href="#" onClick="loadPhoneNo();">Find</a></td></tr>
         
         <tr><td width="300">Supplier Name : <select name="supp_id" id="supp_id" >
          <option value="0">Select Name</option>
		  <option value="-1">All</option>
		 <?php  while($row=mysqli_fetch_row($qryitem))
		 {
				echo "<option value='".$row[0]."'>".$row[1]."</option>";
			}?></select></td>
            
            <td width="217"> Date From : <input type="text" name="frmdate" id="frmdate" onClick="displayDatePicker('frmdate');"></td>
            
            <td width="166"> To :<input type="text" name="todate" id="todate" onClick="displayDatePicker('todate');"></td>
            
            
            
            
            
            <td width="213"><input type="button" name="btn"  value="Show Payments" onClick="showbills()"> </td>
            </tr>
           
            </table>
            
      <div id="back">  </div>
     <br/>
  </form>
      
</center>
</td>
</tr>
</table>
</div>
<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>