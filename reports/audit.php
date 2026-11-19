<script type="text/javascript">     
        function PrintDiv() {    
           var divToPrint = document.getElementById('bill');
           divToPrint.style.fontSize = "10px";
           var popupWin = window.open('', '_blank', 'width=800,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
            popupWin.document.close();
                }
////end of print

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
////////////////
function formSubmit()
{
	if(document.getElementById('cid').value== -1)
 {
alert("Please enter Customer Id to continue.");
document.getElementById('cid').focus();
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



var str = escape(document.getElementById('cid').value);
var cat = escape(document.getElementById('cat').value);
//alert(str);
 xmlHttp.open("GET", "get_audit.php?barcode="+str+"&cat="+cat, false);
//alert("get_audit.php?barcode="+str+"&cat="+cat);
  xmlHttp.send(null);

}
function HandleResponse(response)

{
//alert(response);
document.getElementById('detail').innerHTML=response;

}

function GetData()
{

  var xmlHttp = getXMLHttp();

// alert("hi");

  xmlHttp.onreadystatechange = function()

  {

    if(xmlHttp.readyState == 4)

    {
//alert(xmlHttp.responseText);
      document.getElementById('cid').innerHTML=xmlHttp.responseText;

    }

  }

// alert("hi2");



//var str = escape(document.getElementById('cid').value);
var cat = escape(document.getElementById('cat').value);
//alert(str);
 xmlHttp.open("GET", "get_auditdt.php?cat="+cat, false);
//alert("get_audit.php?barcode="+str+"&cat="+cat);
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

  
</script>

<div style="text-align: center;">
<font size="+1">
<a href="/pos/home_dashboard.php">Back</a>&nbsp;&nbsp;&nbsp;<a href="#" style="font-size:18px;font-weight:bold;" onclick='PrintDiv();'>Print</a></font>
<table width="788" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
  
<tr><td  valign="top" align="center">
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
<img src="bill.PNG" width="408" height="165"/><br/><br/> 
<h3> Audit Report </h3>
<center>
<form  action="approval_detail.php" id="frm1" name="frm1" method="POST">
Chooose Category:&nbsp;&nbsp;&nbsp;<select name="cat" id="cat" onchange="GetData();"><option value="-1" >select</option>
      <?php 
	 
	  $cat = mysqli_query($con,"select distinct(category) from phppos_items where name in (SELECT DISTINCT(item_id) FROM  `audit`) order by category ASC");
	  while($catrow = mysqli_fetch_row($cat)){ 
	  ?>
      
      <option value="<?php echo $catrow[0]; ?>" ><?php echo $catrow[0]; ?></option>
      <?php } ?>   </select>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Audit Date:&nbsp;&nbsp;&nbsp;<select name="cid" id="cid" onchange="MakeRequest();"><option value="-1" >select</option>
      <?php 
	 
	  $result = mysqli_query($con,"SELECT DISTINCT(audit_date) FROM  `audit`  order by audit_date");
	  while($row = mysqli_fetch_row($result)){ 
	  ?>
      
      <option value="<?php echo $row[0]; ?>" ><?php  if(isset($row[0]) and $row[0]!='0000-00-00') echo date('d/m/Y',strtotime($row[0])); ?></option>
      <?php } ?>   </select>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      <input type="hidden" name="myvar" value="0" id="theValue" /><br/><br/>
     
      <table width="778" border="0" cellpadding="4" cellspacing="0">
  <tr><td>
 <div id="detail"></div>

     </td></tr>
    </table>
      
      <br/>
</form></center>
 </td></tr>
 
 </table>
</div>
<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>