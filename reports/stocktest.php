<script
  src="https://code.jquery.com/jquery-3.6.0.min.js"
  integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
  crossorigin="anonymous"></script>
<script>
/////////////////////////item coce


// function ab(){
// var isCtrl = false;
// document.onkeyup=function(e){
// 	if(e.which == 13){ 

// 		dis1();MakeRequest();
// 		return false;
// 	}else if(document.getElementById('icode').value==""){
// 	dis1();
// 	}else{
// 	}
// }
// }
////////////////item category





function ab1(){
var isCtrl = false;
document.onkeyup=function(e){
	if(e.which == 13){ 
	dis2();
	MakeRequest();
		return false;
	}
	else if(document.getElementById('cid').value==""){
	dis2();
	}
}
}






///barcode id
function ab2(){
var isCtrl = false;
document.onkeyup=function(e){
	if(e.which == 13){ 

	dis3();MakeRequest();
		return false;
	}else if(document.getElementById('barid').value==""){
	dis3();
	}
}
}
///end of function 

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





function ab(){
    
    $("#detail").html('');
var cid = escape(document.getElementById('icode').value);


    $.ajax({
                type: "POST",
                url: "getstocktest.php",
                data: 'itemcode='+cid,
                success:function(msg) {
                    $("#detail").html(msg);
                }
            });
}




function MakeRequest(){
    
    $("#detail").html('');
var cid = escape(document.getElementById('cid').value);


    $.ajax({
                type: "POST",
                url: "getstocktest.php",
                data: 'categ='+cid,
                success:function(msg) {
                    $("#detail").html(msg);
                }
            });
}



///////////////////
function MakeRequest1()

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

var str4 = escape(document.getElementById('agname').value);
//alert(str4);
 xmlHttp.open("GET", "getstock1.php?agent="+str4, false);

  xmlHttp.send(null);

}


function HandleResponse(response)

{
//alert(response);
document.getElementById('detail').innerHTML=response;

}
	
////remove div
	 function removeElement(divNum) {
	 
            var d = document.getElementById('detail');
            var olddiv = document.getElementById(divNum);
            d.removeChild(olddiv);
        }

//
function dis1(){
	var a1=document.getElementById('icode');
	var a2=document.getElementById('cid');
	var a3=document.getElementById('barid');
	var a4=document.getElementById('agname');
	
	if(a1.value==""){
		a2.disabled=false;
		a3.disabled=false;
		a4.disabled=false;
	}
	else{
		a2.disabled=true;
		a3.disabled=true;
		a4.disabled=true;
	}
}

function dis2(){
	var a1=document.getElementById('icode');
	var a2=document.getElementById('cid');
	var a3=document.getElementById('barid');
	var a4=document.getElementById('agname');
	
	if(a2.value==""){
		a1.disabled=false;
		a3.disabled=false;
		a4.disabled=false;
	}
	else{
		a1.disabled=true;
		a3.disabled=true;
		a4.disabled=true;
	}
}






function dis3(){
	var a1=document.getElementById('icode');
	var a2=document.getElementById('cid');
	var a3=document.getElementById('barid');
	var a4=document.getElementById('agname');
	
	if(a3.value==""){
		a2.disabled=false;
		a1.disabled=false;
		a4.disabled=false;
	}
	else{
		a2.disabled=true;
		a1.disabled=true;
		a4.disabled=true;
	}
}

function dis4(){
	var a1=document.getElementById('icode');
	var a2=document.getElementById('cid');
	var a3=document.getElementById('barid');
	var a4=document.getElementById('agname');
	
	if(a4.value=="-1"){
		a2.disabled=false;
		a1.disabled=false;
		a3.disabled=false;
	}
	else{
		a2.disabled=true;
		a1.disabled=true;
		a3.disabled=true;
	}
}
</script>
<script type="text/javascript">     
        function PrintDiv() {    
           var divToPrint = document.getElementById('bill');
           divToPrint.style.fontSize = "10px";
           var popupWin = window.open('', '_blank', 'width=800,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
            popupWin.document.close();
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
		document.getElementById("agname").value=s1[1];
		MakeRequest1();
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
<body >
<div style="text-align: center;">
<font size="+1">
<a href="/pos/home_dashboard.php">Back</a></font>
<table width="1203" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
  
<tr><td width="1191" align="center"  valign="top">
<?php
//  include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

 
$result5=mysqli_query($con,"select * from   `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($result5,10);

$row7=mysqli_fetch_array($result5);

?>
<img src="bill.PNG" width="408" height="165"/><br><br>
Stock Return<br/>
<br/><center>
<table width="1175">
<tr>

<td width="149"><strong>Item Code:</strong></td>

<td width="274"><input type="text" name="icode" id="icode" placeholder="Search By Item Code" style="width:150px;"/> <a href="#" onClick="ab();">Get Result</a> &nbsp;&nbsp;&nbsp;<b>OR</b></td>
<!--<td width="37" align="center">OR</td>-->
<td width="128"><strong>Item Category:</strong></td>

<td width="227"><input type="text" name="cid" id="cid"  placeholder="Search By Item Category" style="width:150px;"/> <a href="#" onClick="MakeRequest();">Get Result</a> &nbsp;&nbsp;&nbsp;<b>OR</b></td>
<!--<td width="37" align="center">OR</td>-->
<td width="123"><strong>Barcode ID:</strong></td>
<td width="246"><input type="text" name="barid" id="barid" onKeyUp="ab2();" placeholder="Search By Barcode ID" style="width:150px;"/>&nbsp;&nbsp;&nbsp;<b>OR</b></td>
</tr>
<tr>
<!--<td width="37" align="center">OR</td>-->
<td width="149"><strong>Phone Number:</strong></td>
<td> <input type="text" name="phoneNo" id="phoneNo" value=""  /> <a href="#" onClick="loadPhoneNo();">Find</a>&nbsp;&nbsp;&nbsp;<b>OR</b></td>
<!--<td width="37" align="center">OR</td>-->


</tr>

      <input type="hidden" name="myvar" value="0" id="theValue" /><br/><br/>
      </table>
     
      <table width="100%" border="0" cellpadding="4" cellspacing="0" id="bill">
      <tr><td>
      <div id="detail"></div>

     </td></tr>
    </table>
      
      <br/>
</form>
 </td></tr>
 
 </table>
	

	
</div>
<?php CloseCon($con);?>
<center><a href="#" style="font-size:18px;font-weight:bold;" onclick='PrintDiv();'>Print</a></center><br>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
	
</body>