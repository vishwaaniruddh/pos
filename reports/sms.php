<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
<script>
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


if(cust.checked == 1 && beu.checked != 1)
{
var str1 = escape(document.getElementById('cust').value);
 xmlHttp.open("GET", "getsms.php?cust="+str1, false);
}
else if(beu.checked == 1 && cust.checked != 1)
{
var str2 = escape(document.getElementById('beu').value);
 xmlHttp.open("GET", "getsms.php?beu="+str2, false);
}
else if(beu.checked == 1 && cust.checked == 1)
{
var str1 = escape(document.getElementById('cust').value);
var str2 = escape(document.getElementById('beu').value);
xmlHttp.open("GET", "getsms.php?beu="+str2+"&cust="+str1, false);	
}
  xmlHttp.send(null);

}

function HandleResponse(response)

{
//alert(response);
document.getElementById('detail').innerHTML=response;

}
</script>
<SCRIPT LANGUAGE="JavaScript">
<!-- 

<!-- Begin
function CheckAll(chk)
{
for (i = 0; i < chk.length; i++)
chk[i].checked = true ;
}

function UnCheckAll(chk)
{
for (i = 0; i < chk.length; i++)
chk[i].checked = false ;
}
// End -->
</script>
</head>

<body>
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
<center>
<img src="bill.PNG" width="408" height="165"/><br><br>
Send SMS<br/>
<br/>

<input type="checkbox" name="all" id="cust" value="Customer" onclick="MakeRequest();"/>Customer  &nbsp;&nbsp;
<input type="checkbox" name="all" id="beu" value="Beautician" onclick="MakeRequest();"/>Beautician <br /><br />
<form name="myform" action="#" method="post">
<input type="button" name="Check_All" value="Check All"
onClick="CheckAll(document.myform.send)">
<input type="button" name="Un_CheckAll" value="Uncheck All"
onClick="UnCheckAll(document.myform.send)">
<div id="detail"></div>
<?php CloseCon($con);?>

<textarea rows="5" cols="32"></textarea>
<input type="submit" name="send" value="send" />
</form></center>
</body>
</html>