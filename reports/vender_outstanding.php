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
xmlhttp.open("GET","getsales.php?frmdate="+frmdate+"&todate="+todate,true);
xmlhttp.send();

}
// opening child window...
var popupWindow=null;

function popup(supp_id)
{
    popupWindow = window.open('add_vender_outstanding.php?supp_id='+supp_id,'name','width=400,height=250');
	document.body.disabled=true;
}

function parent_disable() {
if(popupWindow && !popupWindow.closed)
{popupWindow.focus();
document.body.disabled=true;
}
else
document.body.disabled=false;
}//end of function show
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
<b>*** Vendor Outstanding Report***</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      
      
     <form id="purchse" >
       <div id="printdiv">
         <hr/></div>
     		<table width="50%" border="1"><tr><th colspan="5"> Vendor Outstanding Report</th></tr>
                       <tr><th>Sr No</th><th>Vendor Name</th><th>Out Standing</th><th>Options</th></tr>
            <?php 
			$i=0;
			$qryven=mysqli_query($con,"select * from `phppos_suppliers` order by company_name");
			while($resven=mysqli_fetch_row($qryven)){
				//echo "hii";
			$qry=mysqli_query($con,"SELECT * FROM `vendor_openbal` WHERE `v_id`='$resven[0]'");
			$num=mysqli_num_rows($qry);
			$resoutstand=mysqli_fetch_row($qry);
			
			$qryout=mysqli_query($con,"select sum(`outstanding`) from `phppos_purchase` where supp_id='$resven[0]'");
			$resout=mysqli_fetch_row($qryout);
			//echo $resout[0];
			if($resout[0]==""&& $resoutstand[2]=="")
			continue;
			else
			{	if($num==0){
				echo "<tr><td align='center'>".++$i."</td><td  align='center'>".$resven[1]."</td><td  align='right'>".$resout[0]."</td><td>";
				 echo "<a href='#' onclick='popup(".$resven[0].")'> Add Outstanding </a>" ;
				 echo "&nbsp; <a href='#' > Pay </a></td></tr>"; 
				}
				else
				{
						echo "<tr><td align='center'>".++$i."</td><td  align='center'>".$resven[1]."</td><td  align='right'>".($resout[0]+$resoutstand[2])."</td><td>";
				
				 echo "&nbsp; <a href='#' > Pay </a></td></tr>"; 
					
				}// inner else
			}//external else
		}
			?>
		     </table>      
        &nbsp; &nbsp;<a href="../../../index.php/reports"><input type="button" name="cancel"  value="EXIT to Main Menu"></a>
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