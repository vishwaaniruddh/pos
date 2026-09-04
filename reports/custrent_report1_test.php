<script type="text/javascript" src="datepicker/datepick_js.js"></script>
<!--<script type="text/javascript" src="tableExport/jquery.js"></script>-->

<!-- must have for any conversion --->
<!--<script type="text/javascript" src="tableExport/tableExport.js" ></script>-->
<!-- <script type="text/javascript" src="tableExport/jquery.base64.js" ></script>-->
 
<link rel="stylesheet" type="text/css" href="datepicker/date_css.css"  />
<script type="text/javascript" 
   src="/jquery/jquery-1.8.3.js"></script>
<style type="text/css">
@media print{
#print_div {
display:block;
}
#not {
display:none;
}

}
</style>


<!--<script>-->
<!-- $(document).ready(function(e) {-->
     
<!--     $("#xml").click(function(e) {-->
         
<!--        $("#test").tableExport({-->
<!--            type: 'xml',-->
<!--            escape: 'false',-->
<!--        });-->
<!-- });-->

<!-- });-->

<!--</script>-->


<script>
function printdiv()
{
	var page=document.body.innerHTML;
	var tbl=document.getElementById('printdiv').innerHTML;
	document.body.innerHTML=tbl;
	window.print() ;
	document.body.innerHTML=page;
}

/*function sales()
{
	//alert("hii bal");
	//document.getElementById('image').innerHTML="<img src='loading.gif' width='100px' height='50px'>";
		 var cust_id=document.getElementById('cust_id').value;
		 document.getElementById('dtl').innerHTML='';
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
		
	document.getElementById('bal').innerHTML=xmlhttp.responseText;
    }
  }
  //if(str=='')
xmlhttp.open("GET","getsales.php?cust_id="+cust_id,true);
xmlhttp.send();

	
}*/
function show()
{
		var status=document.getElementById('typ').value;
			if(status=='a')
			{document.getElementById('printdiv').setAttribute('style','display:bolck');
			document.getElementById('printdiv1').setAttribute('style','display:none');}
			else
			{document.getElementById('printdiv1').setAttribute('style','display:bolck');
			document.getElementById('printdiv').setAttribute('style','display:none');}
	}
function detail()
{
	//alert("hii bal");
	//document.getElementById('image').innerHTML="<img src='loading.gif' width='100px' height='50px'>";
		 var cust_id=document.getElementById('cust_id').value;
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
		
	document.getElementById('dtl').innerHTML=xmlhttp.responseText;
    }
  }
  //if(str=='')
xmlhttp.open("GET","getdetails.php?cust_id="+cust_id,true);
xmlhttp.send();

	
}


function showsales()
{	//alert("hii");
	
	 var frmdate=document.getElementById('frmdate').value;
	 var todate=document.getElementById('todate').value;
	 
	 var invno=document.getElementById('invno').value;
	 
	  if((frmdate=="" && todate!="")||(frmdate!="" && todate==""))
		 {
			 alert("Please Enter date in both Fields");
			 return ;
		 }
		 document.getElementById('printdiv').innerHTML="<img src='loading.gif' width='250px' height='200px'>";
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
		
	document.getElementById('printdiv').innerHTML=xmlhttp.responseText;
    }
  }
  //if(str=='')
xmlhttp.open("GET","getrent.php?frmdate="+frmdate+"&todate="+todate+"&invno="+invno,true);
xmlhttp.send();

}
// opening child window...
var popupWindow=null;

function popup(cust_id)
{
         var frmdate=document.getElementById('frmdate').value;
	 var todate=document.getElementById('todate').value;
   popupWindow = window.open('getdetails.php?cust_id='+cust_id+'&frmdate='+frmdate+'&todate='+todate,'name','width=325,height=250');
}

function parent_disable() {
if(popupWindow && !popupWindow.closed)
popupWindow.focus();
}
</script>
<body onLoad="" onFocus="parent_disable();" onclick="parent_disable();">prin

<div  style="text-align: center;">
<a href="/pos/home_dashboard.php">Back</a>
<table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center" id="test">
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

<img src="../reports/bill.PNG" width="408" height="165"/><br/><br/>
<b>*** Customer Rent Report***</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      
      
     <form id="purchse" > 
     
     <button id="xml">Export To XML</button>
     <input type="button" value="Print Page" onClick="printdiv()">
     
    <div align="center" style="color:#03F"> Filter By Date : From <input type="text" id='frmdate' name="frmdate" onClick="displayDatePicker('frmdate')"> &nbsp To : <input type="text" id='todate' name="todate" onClick="displayDatePicker('todate')">&nbsp Inovice Number : <input type="text" id='invno' name="invno" ">
    
    
    <input type="button" name="btn" id="btn" style="color:#FFF; background-color:#000" value="< GO >" onClick="showsales();"/></div>
     <hr> 
     <div id="printdiv">
     
     <hr/></div>
     
           
        &nbsp; &nbsp;<a href="/pos/home_dashboard.php"><input type="button" name="cancel"  value="EXIT to Main Menu"></a>
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