<script type='text/javascript' src='http://code.jquery.com/jquery-1.4.4.min.js'></script>
  
  
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.7/jquery-ui.js"></script>
  
  
  <link rel="stylesheet" type="text/css" href="/css/result-light.css">
  
    
      <link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/themes/smoothness/jquery-ui.css">
    
    
  
  <style type='text/css'>
    body
{
    font-size: 10px;
}
  </style>
  
<script type='text/javascript'>//<![CDATA[ 

$(function() {
    $(".firstcal").datepicker({
        dateFormat: "dd/mm/yy",
        onSelect: function(dateText, instance) {
            date = $.datepicker.parseDate(instance.settings.dateFormat, dateText, instance.settings);
            date.setMonth(date.getMonth() + 6);
            $(".secondcal").datepicker("setDate", date);
        }
    });
    $(".secondcal").datepicker({
        dateFormat: "dd/mm/yy"
    });
});
//]]>  

</script>


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
/////////////////
function formSubmit()
{
if(document.getElementById('bill_date').value=="")
 {
alert("Please Select Bill Date to continue.");
document.getElementById('bill_date').focus();
return false;
}
else{

document.getElementById("frm1").submit();
 return true;
 }

}

var searchReq = getXMLHttp();
function getXMLHttp()
{

  var xmlHttp

// alert("hi1");

  try

  {

    //Firefox, Opera 8.0+, Safari

    xmlHttp = new XMLHttpRequest();

  }

  catch(e)

  {

    //Internet Explorer

    try

    {

      xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");

    }

    catch(e)

    {

      try

      {

        xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");

      }

      catch(e)

      {

        alert("Your browser does not support AJAX!")

        return false;

      }

    }

  }

  return xmlHttp;

}


function MakeRequest()

{

  var xmlHttp = getXMLHttp();

// alert("hi");

  xmlHttp.onreadystatechange = function()

  {

    if(xmlHttp.readyState == 4)

    {

      HandleResponse(xmlHttp.responseText);

    }

  }

// alert("hi2");



var str = escape(document.getElementById('barcode').value);
///alert(str);
var str1=document.getElementById('barcode2').value;
        ///alert(str1);
 xmlHttp.open("GET", "getbarcode5.php?barcode="+str+"&barcode2="+str1, false);

  xmlHttp.send(null);

}
function HandleResponse(response)

{
///alert(response);
var ni =document.getElementById('detail');

var numi = document.getElementById('theValue');
var num = parseInt(document.getElementById('theValue').value) +1;
numi.value = num;

var newdiv = document.createElement('div');

var divIdName = num;

newdiv.setAttribute('id',divIdName);

newdiv.innerHTML =response+"<table border='0' height='50' valign='middle'><tr><td valign='middle'><a href=\"javascript:;\" onclick=\"removeElement(\'" + divIdName + "\')\">Delete</a></td></tr></table>";

ni.insertBefore(newdiv,ni.childNodes[0]);
document.getElementById('barcode').value='';
document.getElementById('barcode2').value='';
checkTotal1();checkTotal();
}

	
////remove div
	 function removeElement(divNum) {
	 
            var d = document.getElementById('detail');
            var olddiv = document.getElementById(divNum);
            d.removeChild(olddiv);
        }

function checkTotal() {
        document.listForm.total.value = '';
        var sum = 0;
		 var arr = document.getElementsByClassName('amt');
     
    //var tot=0;
	///alert(arr.length);
        for (i=0;i<arr.length;i++) {
       
      
            sum = sum + Number(arr[i].value);
         
        }
        document.listForm.total.value = sum;
    }
    
///
function checkTotal1() {
       
   	
     var arr = document.getElementsByClassName('prz');
     var discount = document.getElementsByClassName('discount');
     var amt= document.getElementsByClassName('amt');
 
    
        for (i=0;i<arr.length;i++) {
       

          amt[i].value=Number(arr[i].value) - Number(discount[i].value)
       
        }
        ///document.listForm.total.value = sum;
    }
	////total amount
	
function Totalamount() {
       
        var sum = 0;
		var sum1=0;
		
		
		
	//var arr = document.getElementsByClassName('qty');
     var prz=document.getElementsByClassName('amount');
      var dis=document.getElementsByClassName('ds');
        var dis1=document.getElementsByClassName('ds1');
	var dsamt=document.getElementsByClassName('disamt');
    //var tot=0;
	///alert(dsamt.length);
        for (i=0;i<prz.length;i++) {
       //// alert(dsamt[i].value);
	     sum1=Number(prz[i].value);
        
       if(dis1[i].checked==true){
      
		  v=Math.round(sum1*(dsamt[i].value/100.0));
		 /// alert(sum1+","+v);
		  a=sum1-v;
		//  alert(a);
	   }else if(dis[i].checked==true){
	   ////alert("else");
		    a=sum1-Number(dsamt[i].value);
		    ////alert(a);
		   
	   } sum = sum + a;
			
        document.listForm.amountTotal.value = sum;
		
    }
    }
    
    $('#cid').load('processpeople.php');
	
	
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
	    alert(s);
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
   alert(str);
   xmlhttp.open("GET","getbyphone.php?cid="+str,true);
   xmlhttp.send();
}

	
	
	
</script>
<body >
<div id="page_title" style="margin-bottom:8px;"><?php echo $title ?></div>





<div id="page_subtitle" style="margin-bottom:8px;"><?php echo $subtitle ?></div>
<div style="text-align: center;">
<a href="/pos/home_dashboard.php" style="font-size:16px;">Back</a>
<table width="1096" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
  
<tr>
<td align="center">  <?php
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
<img src="bill.PNG" width="408" height="165"/><br/><br/>
<b>CONFIRMATION SCHEME</b>

</td></tr>
<tr>
<td width="1084"  valign="top">
      
     <center>
      <form name="listForm" action="order_detail1.php"  method="POST" id="frm1">
       
       <center>
       <hr>
       <table width="100%" height="77">
       <tr><td width="447" height="34">
       
       
      <strong> Phone Number:</strong> <input type="text" name="phoneNo" id="phoneNo" value=""  /> <a href="#" onClick="loadPhoneNo();">Find</a> <br>
         <strong>Customer Name:&nbsp;</strong>&nbsp;&nbsp;
  <select name="cid" id="cid">
    <option value="-1" >select</option>
    <?php 
	  include('config.php');
	  $result = mysqli_query($con,"SELECT * FROM  phppos_people order by first_name");
	  while($row = mysqli_fetch_row($result)){ 
	  ?>
    <option value="<?php echo $row[11]; ?>" ><?php echo $row[0] ."  ". $row[1]; ?></option>
    <?php } ?>
  </select></td>
  
  <td width="184"><a href="people.php?mode=rent1" target="_new">New Customer</a></td>
 
  <td width="438"><strong>Through Name:</strong>
        <select name="name" id="name" >
         <option value="-1" >select</option>
     <?php 
	  include('config.php');
	  $bresult = mysqli_query($con,"SELECT * FROM  phppos_people  WHERE first_name LIKE 'B %' order by first_name");
	  while($brow = mysqli_fetch_row($bresult)){ 
	  ?>
     <option value="<?php echo $brow[11]; ?>" ><?php echo $brow[0] ."  ". $brow[1]; ?></option>
     <?php } ?>
     </select>
  </tr>
  <tr>
    <td width="447" height="35" ><strong>Bill  Date :</strong><input type="text"  name="bill_date" id="bill_date"class="firstcal">

</td>
      <td><strong>Through Phone No:</strong>        <input type="text" name="tphone" id="tphone" /></td>
  </table>
       </center>
      <hr>
      <table width="957">
        <tr>
          <td width="153"><strong>Date of Maturety</strong> </td>
          <td width="254"><label for="radio">
          <input type="text" class="secondcal" name="m_date" id="m_date" readonly="readonly"> <br/>
        </label></td><td width="534"><strong>Item code: </strong>
         <input type="text" name="barcode" onFocus="this.value=''" onClick="this.value=''"  id="barcode" onChange="MakeRequest();"/> &nbsp;&nbsp;&nbsp;&nbsp; <input type="hidden" name="myvar" value="0" id="theValue" /><strong>Barcode :</strong> 
      <input type="text" name="barcode2" onFocus="this.value=''" onClick="this.value=''"  id="barcode2" onChange=" MakeRequest();"/>
  </td></tr></table>
    <hr>
     
       <table width="1085" border="1" cellpadding="4" cellspacing="0">
         <tr>
    <td width='151'><U><strong>Item Code</strong></U></td>
    <td width='189'><U><strong>Category</strong></U></td>
    <td width='179'><U><strong>Price</strong></U></td>
    
    <td width='207'><U><strong>Scheme Amount</strong></U></td>
    <td width='189'><U><strong>Discount</strong></U></td>
     
    <td width='108'><U><strong>Delete</strong></U></td>
  </tr>
  <tr><td colspan="6">
 <div id="detail"></div>

     </td></tr>
  </table>
       <br/>
      <table width="966" height="29"><tr>
      <td width="46"><strong>Scheme:-</strong></td>
      <td width="153"><p>
        <input type="radio" name="paid" id="paid" value="Paid">
        <strong>        Paid Amount</strong><br/>
        <input type="radio" name="paid" id="paid" value="Unpaid">
        <strong>       Unpaid Amount      
        </strong><br/>
           <input type="radio" name="paid" id="paid" value="Balance">
           <strong>Balance Amount
           </strong></td>
        <td width="78">&nbsp;</td>
        <td width="94">&nbsp;</td>
        
        
        <td width="269"><strong>Amount Paid : </strong>&nbsp;
          <input type="text" name="pamount" id="pamount" value="">
		<td width="298"><strong>Total Amount : </strong><input type="text" size="20" name="amountTotal" value="0" /></td>
        </tr>
		<tr><td><strong>Note:</strong></td>
		<td colspan="4"><textarea name="note" id="note" rows="3" cols="55"></textarea></td></tr></table>
      <br/>
 <input type="button" onClick="formSubmit()" value="Scheme Bill" />
</form></center>
 </td></tr>
 
 </table>
	

	
</div>
<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>