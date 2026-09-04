<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css"  />
<script>
var isCtrl = false;
document.onkeyup=function(e){
	if(e.which == 17) isCtrl=false;
}
document.onkeydown=function(e){
	if(e.which == 17) isCtrl=true;
	if(e.which == 66 && isCtrl == true) 
	{
		document.getElementById("barcode").focus(); 
		return false;
	}
	
}
function showdetails()
{ debugger;
var bar = document.getElementById('barcode').value;
 var bar2 = document.getElementById('barcode2').value;
if (window.XMLHttpRequest)
  {// code for IE7+, Firefox, Chrome, Opera, Safari
  xmlhttp=new XMLHttpRequest();
  }
else
  {// code for IE6, IE5
  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
xmlhttp.onreadystatechange=function()
  { //alert("hii");
  if (xmlhttp.readyState==4 && xmlhttp.status==200)
    {
		document.getElementById('barcode').value='';
	document.getElementById('barcode2').value='';
    document.getElementById("back").innerHTML=xmlhttp.responseText;
	
    }
  }
xmlhttp.open("GET",'getbookdetail.php?barcode='+bar+'&barcode2='+bar2,true);
xmlhttp.send();
}

/*function MakeRequest()
{ 
alert("hii");
 var bar = document.getElementById('barcode').value;
 var bar2 = document.getElementById('barcode2').value;
 //window.location='getbookdetail.php?barcode='+bar+'&barcode2='+bar2;
 if (window.XMLHttpRequest)
  {// code for IE7+, Firefox, Chrome, Opera, Safari
  var xmlhttp=new XMLHttpRequest();
  }
else
  {// code for IE6, IE5
  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
xmlhttp.onreadystatechange=function()
  { alert(hii);
  if (xmlhttp.readyState==4 && xmlhttp.status==200)
    { alert(xmlhttp.responseText);
    document.getElementById("back").innerHTML=xmlhttp.responseText;
    }
  }
 alert(bar +bar2);
 xmlHttp.open('GET','getbookdetail.php?barcode='+bar+'&barcode2='+bar2, false);
 xmlHttp.send();
}*/

</script>
<body onLoad="">
<div id="page_title" style="margin-bottom:8px;"><?php echo $title ?></div>

<div id="page_subtitle" style="margin-bottom:8px;"><?php echo $subtitle ?></div>

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

<!--<img src="bill.PNG" width="408" height="165"/><br/><br/>-->
<b>Booking Status</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      <table width="100%" align='center'>
      <tr align="center">
      <td width="493"><strong>Item code: </strong>
       <input type="text" name="barcode" onFocus="this.value=''" onClick="this.value=''"  id="barcode" onChange="showdetails();"/> &nbsp;&nbsp;&nbsp;&nbsp; Barcode : 
       <input type="text" name="barcode2" onFocus="this.value=''" onClick="this.value=''"  id="barcode2" onChange=" showdetails();"/>
      </td>
      </tr>
      </table>
    <hr>
    
     <div id="back"></div>
     <br/>
  
      
</center>
</td>
</tr>
</table>
</div>
<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>