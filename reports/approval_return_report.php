<script type="text/javascript" src="datepicker/datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="datepicker/date_css.css"  />
<script>
var isCtrl = false;
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
function showreturn()
{
	//alert("hii");
	var cust_id=document.getElementById('cust_id').value;
	var frmdate=document.getElementById('frmdate').value;
	var todate=document.getElementById('todate').value;
	if(cust_id==0)
	{
		alert("Please select Customer");
		return;	
		}
	if(todate=="" || frmdate=="")
	{
			alert("Date Can Not Be Left as Blank");	
			return;
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
		
	document.getElementById('back').innerHTML=xmlhttp.responseText;
   // document.getElementById('image').innerHTML="";
    }
  }
xmlhttp.open("GET","getreturn.php?cust_id="+cust_id+"&frmdate="+frmdate+"&todate="+todate,true);
xmlhttp.send();

	
}


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
		document.getElementById("cust_id").value=s1[1];
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





/*
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

function details()
{	//alert("hii");
	 var elem = document.getElementsByClassName('qty');
	 var bill=document.getElementById('bill_id').value;
	 var supp=document.getElementById('supp_id').value;
	 var bill_date = document.getElementById('bill_date').value;
	 var item_id=document.getElementsByClassName('item_id');
	 var price=document.getElementsByClassName('price');
	 
  //var names = [];
 // alert(bill+supp+bill_date);
  if(bill=="")
  {
	 alert("Please Enter Bill Number");
	 document.getElementById('bill_id').focus(); 
	 return false; 
	}
  if(supp==0)
  {
	 alert("Please Select Supplier");
	 document.getElementById('supp_id').focus(); 
	 return false; 
	}
  if(bill_date=="")
  {
	 alert("Please Enter Bill Date");
	 document.getElementById('bill_date').focus(); 
	 return false; 
	}
 
  for ( i = 0; i < elem.length;i++) {
      if(item_id[i].value==0)
  {
	 alert("Please Select Item in Row Number "+(i+1));
	 item_id[i].focus(); 
	 return false; 
	}
   if(price[i].value==""||price[i].value==0)
  {
	 alert("Please enter Price in Row Number "+(i+1));
	 price[i].focus(); 
	 return false; 
	}
  
	  if(elem[i].value==""||elem[i].value==0)
  {
	 alert("Please Enter Qty in Row Number "+(i+1));
	 qty[i].focus(); 
	 return false; 
	}
   
 
  }//for loop
	
    
}//end of function show
////remove div
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
//$qryid=mysqli_query($con,"select `cust_id` from `approval` where `bill_id` in (SELECT `bill_id` FROM `return_qty` group by `bill_id`) group by `cust_id`");
$qryid=mysqli_query($con,"select * from phppos_people where first_name!='' and person_id in (select distinct(`cust_id`) from `approval`) order by first_name");
?>

<img src="../reports/bill.PNG" width="408" height="165"/><br/><br/>
<b>Approval Return Report</b></td></tr>

<tr>
<td width="1308"  valign="top"><center>

  Phone Number:<input type="text" name="phoneNo" id="phoneNo" value=""  /> <a href="#" onClick="loadPhoneNo();">Find</a>
      <?php // echo "select * from phppos_people where person_id='$row1[0]'"; ?>
     <select id="cust_id" ><option value="0">Select Customer</option><?php while($row1=mysqli_fetch_row($qryid)){ 
     
	 	//$qrycust=mysqli_fetch_row(mysqli_query($con,"select * from phppos_people where person_id='$row1[0]'"))
	 ?><option value="<?php echo $row1[11];?>"><?php echo $row1[0]; ?></option><?php } ?></select> </br>
    &nbsp; Date From :<input type="text" name="frmdate" id="frmdate" onClick="displayDatePicker('frmdate')">    &nbsp; Date From :<input type="text" name="todate" id="todate" onClick="displayDatePicker('todate')">&nbsp;<input type="button" value="< GET DETAILS >" style="background-color:#000;color:#fff;" onClick="showreturn()" >
          <hr>
     <div id="back">
         </div>
  
     </center>
</td>
</tr>
</table>
</div>
<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>