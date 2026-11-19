<script type="text/javascript" src="datepicker/datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="datepicker/date_css.css"  />
<script type="text/javascript" 
   src="/jquery/jquery-1.8.3.js"></script>
<style type="text/css">
@media print {
#print_div {
display:block;
}
#not {
display:none;
}

}
</style>

<script>
function printdiv()
{
	var page=document.body.innerHTML;
	var tbl=document.getElementById('printdiv').innerHTML;
	document.body.innerHTML=tbl;
	window.print() ;
	document.body.innerHTML=page;
}

function showitems()
{
	//alert("hii bal");
	
		 var cat=document.getElementById('cat').value;
		 var frmdate=document.getElementById('frmdate').value;
		 var todate=document.getElementById('todate').value;
		//alert(frmdate);
		 if(cat==0)
		 {
			alert('Plaese Select Category');
			document.getElementById('cat').focus();
			return false; 
			}
			if((frmdate==""&&todate!="")||(frmdate!=""&&todate==""))
			{
				alert("Please Select Dates");
				if(frmdate=="")
				document.getElementById('frmdate').focus();
				if(todate=="")
				document.getElementById('frmdate').focus();
			return ;
			}
	document.getElementById('back').innerHTML="<img src='loading.gif' width='200px' height='150px'>";
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
    }
  }
  //if(str=='')
 // alert(cat);
xmlhttp.open("GET","getitems.php?cate="+cat+"&frmdate="+frmdate+"&todate="+todate,true);
xmlhttp.send();

	
}
// opening child window...
var popupWindow=null;

function popup(item_id,frmdate,todate)
{
    popupWindow = window.open('item_pur_dtl.php?item_id='+item_id+"&frmdate="+frmdate+"&todate="+todate,'name','width=325,height=250');
}

function parent_disable() {
if(popupWindow && !popupWindow.closed)
popupWindow.focus();
}
//end of function show
//
/*//remove div
	 function removeElement(divNum) {
	 
          // alert("hii"+divNum);
		    var d = document.getElementById('back');
            var olddiv = document.getElementById(divNum);
			//var num = parseInt(document.getElementById('theValue').value) ;
//numi.value = num;
               d.removeChild(olddiv);
			   subtotal();
        }*/
</script>
<body onLoad="" onFocus="parent_disable();" onclick="parent_disable();">

<div  style="text-align: center;">
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

<img src="../reports/bill.PNG" width="408" height="165"/><br/><br/>
<b>*** Catagory Wise Rent Report***</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      
      
     <form id="purchse" > 
     <div align="center" style="color:#03F"> Filter By Date : From <input type="text" id='frmdate' name="frmdate" onclick="displayDatePicker('frmdate')" placeholder="DD/MM/YYY" > &nbsp To : <input type="text" placeholder="DD/MM/YYY" id='todate' name="todate" onclick="displayDatePicker('todate')" > &nbsp;<input type="button" name="btn" id="btn" style="color:#FFF; background-color:#000" value="< GO >" onClick="showitems();"/></div>
     <!--<input type="button" value="Print Page" onClick="printdiv()"> -->
     <hr>
     <?php $qryapp=mysqli_query($con,"select distinct(`category`)  from `phppos_items` ACS");
	 		 ?>
     <div id="printdiv">
     <table border="1" id="tbl">
     <tr><td> Category</td><td><select id="cat" onChange="showitems()"><option value="0">SELECT CATEGORY</option>
     <?php 
	 	while($app=mysqli_fetch_row($qryapp)){
			echo "<option value='$app[0]'>$app[0]</option>";
		}?></select></td></tr>
          </table>
          <hr/>
        <div id="back"></div>   
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