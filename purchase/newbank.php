<?php 

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

?>
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
	
}
function showrow()
{
	//alert("hii");
	document.getElementById('image').innerHTML="<img src='loading.gif' width='100px' height='50px'>";
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
		
var numi = document.getElementById('theValue');
var num = parseInt(document.getElementById('theValue').value) +1;
numi.value = num;

		var newdiv=document.createElement("div");
		newdiv.setAttribute('id',num);
		//alert(num);
	newdiv.innerHTML=xmlhttp.responseText+"<input type='button' value='Remove' onClick='removeElement("+num+")'></div>";
	
	document.getElementById('back').appendChild(newdiv);
    document.getElementById('image').innerHTML="";
    }
  }
xmlhttp.open("GET","getnewrow.php",true);
xmlhttp.send();

	
}

function subtotal()
{	//alert("hii");
	var elem = document.getElementsByClassName('qty');
	 var price=document.getElementsByClassName('price');
	 var subto=document.getElementsByClassName('subtotal');
	 var sumamt=0; var sumqty=0;
	 for(i=0;i<elem.length;i++)
	 {
		if(elem[i]!=0 || price[i]!=0)
		{
			var subtotal=parseInt(elem[i].value)*parseInt(price[i].value);
			subto[i].value=subtotal;
			sumamt=sumamt+subtotal;
			sumqty=sumqty+parseInt(elem[i].value);	
		}	 
	}
		document.getElementById('totalamt').value=sumamt;
		document.getElementById('totalqty').value=sumqty;
}
*/
function details()
{	//alert("hii");
	 var bankname = document.getElementById('bank_name').value;
	 var acno=document.getElementById('ac_no').value;
	 var openbal=document.getElementById('openbal').value;
	 var address = document.getElementById('address').value;
	 var actype = document.getElementById('actype').value;
	//alert("hii");
  if(bankname=="")
  {
	 alert("Please Enter Bank Name.");
	 document.getElementById('bank_name').focus(); 
	 return false; 
	}
	if(actype==0)
  {
	 alert("Please Select Bank Account Type");
	 document.getElementById('actype').focus(); 
	 return false; 
	}
	
  if(acno=="" && actype!=1)
  {
	 alert("Please Enter Bank Account Number");
	 document.getElementById('ac_no').focus(); 
	 return false; 
	}
	
 if(address=="" && actype!=1)
  {
	 alert("Please Enter bank Address ");
	 document.getElementById('address').focus(); 
	 return false; 
	}
    if(openbal=="")
  {
	 alert("Please Enter Opening Balance ");
	 document.getElementById('openbal').focus(); 
	 return false; 
	}
  if(actype==4)
  {
		var overlimit=document.getElementById('over').value;
		if(overlimit=="")
		{
		 alert("Please Enter Bank Over Draft Limit");
	 document.getElementById('over').focus(); 
	 return false;
	 } 
	}
}

function showovr()
{	actype=document.getElementById('actype').value;
	if(actype==4)
	{
		document.getElementById('ovr').innerHTML="OverDraft Limit : &nbsp;&nbsp;&nbsp;<input type='text' name='over' id='over' placeholder='Enter OverDraft Limit'>"
	}
	else
	{
		document.getElementById('ovr').innerHTML="";
	}
	if(actype==1)
	{
		document.getElementById('address').disabled=true;
		document.getElementById('ac_no').disabled=true;	
	}
	else
	{
		document.getElementById('address').disabled=false;
		document.getElementById('ac_no').disabled=false;	
	}
	
		
}//end of function show
////remove div
	/* function removeElement(divNum) {
	 
          // alert("hii"+divNum);
		    var d = document.getElementById('back');
            var olddiv = document.getElementById(divNum);
			//var num = parseInt(document.getElementById('theValue').value) ;
//numi.value = num;
               d.removeChild(olddiv);
			   subtotal();
        }*/
</script>
<body onLoad="">
 
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

CloseCon($con);
?>

<img src="../reports/bill.png" width="408" height="165"/><br/><br/>
<b>***Add New Bank ***</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      
      
     <form id="purchse" action="processaddbank.php" method="post" onSubmit="return details();">  
     <hr>
     <div id="back">
     <table><tr><td>Bank Name : </td><td><input type="text" name="bank_name" id="bank_name" placeholder="Enter bank Name"/></td></tr>
        <tr><td> Account Type: </td><td><select id="actype" name="actype" onChange="showovr()"><option value="0">Select Account Type</option>
           <option value="1">Petty Cash</option><option value="2">Saving Account</option><option value="3">Current Account</option><option value="4">OverDraft Account</option>
           </select></td></tr>
        <tr><td> Account Number: </td><td><input type="text" name="ac_no" id="ac_no" placeholder="Enter bank A/C number"/></td></tr> 
              
            <tr><td> Bank Addess : </td><td><textarea id="address" name="address" placeholder="Enter Bank Address"></textarea></td></tr>
            <tr><td>Opening Balance : </td><td><input type="text" name="openbal" id="openbal" placeholder="Enter Opening Balance"/></td></tr> 
             <tr><td colspan="2"><div id="ovr"></div></td></tr>
            </table>
        </div>
    <input type="submit" name="submit"  value="Add Bank">&nbsp; &nbsp;<a href="bank_entry.php"><input type="button" name="cancel"   value="Cancel"></a>
     <br/>
  </form>
      
</center>
</td>
</tr>
</table>
</div>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>