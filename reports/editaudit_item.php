<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css"  />
<?php
ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();



$cid=$_GET['id'];

$result = mysqli_query($con,"SELECT * FROM  `phppos_people` where person_id='$cid'");
$row = mysqli_fetch_row($result);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SHRINGAAR</title>
</head>
<script type="text/javascript" src="paging.js"></script>
<script type="text/javascript">     
        function PrintDiv() {    
           var divToPrint = document.getElementById('bill');
           var popupWin = window.open('', '_blank', 'width=800,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
            popupWin.document.close();
                }

  
$(document).ready(function(){
alert("h");
$('input[name="qty[]"]').each(function(){
$(this).keyup(function(){
calculate();
});
});
});
function calculate() { 


$('input[name="qty[]"]').each (function() {
	var $a = $('input[name="qty[]"]').val();
	var $b = $('input[name="qt[]"]').val();
if($b.val() > $a.val())
{


}
});
 
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
 var arr = document.getElementsByClassName('design');
 var barc = document.getElementsByClassName('barc');
 var qty = document.getElementsByClassName('qty');
 var bar = document.getElementById('barcode').value.toUpperCase();
  var bar2 = document.getElementById('barcode2').value.toUpperCase();
 var flag=0;
 for (i=0;i<arr.length;i++) {
        ///alert(arr[i].value.toUpperCase()+"-"+bar+"-"+barc[i].value.toUpperCase());
	    if(arr[i].value.toUpperCase()==bar || barc[i].value.toUpperCase()==bar2)
	    {
			if(qty[i].value==""){
				qty[i].value=qty[i].value+1; 
			}else{
	    qty[i].value=parseInt(qty[i].value)+1;
				}

document.getElementById('barcode').value='';
document.getElementById('barcode2').value='';
checkTotal();
	    flag=1;
	    break;
	    }
 }
 if(flag==0){
 alert("This Itme is Not In the list,You Can Not Add This Item.");
document.getElementById('barcode').value='';
document.getElementById('barcode2').value='';
checkTotal();
/*
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
//alert(str);

var str1=document.getElementById('barcode2').value;
     var arr = document.getElementsByClassName('qty');
	 
 xmlHttp.open("GET", "getaudit.php?barcode="+str+"&barcode2="+str1+"&cnt="+arr.length, false);

  xmlHttp.send(null);*/
}
}
function HandleResponse(response)

{
////alert(response);
var s=response;
//alert(s);
if(s==0){

alert("There is no such Item.Please create new Item.");
document.getElementById('barcode').value='';
document.getElementById('barcode2').value='';

}else{
var ni =document.getElementById('detail');

var numi = document.getElementById('theValue');
var num = parseInt(document.getElementById('theValue').value) +1;
numi.value = num;

var newdiv = document.createElement('div');

var divIdName = num;

newdiv.setAttribute('id',divIdName);

newdiv.innerHTML =response;
//ni.appendChild(newdiv);

ni.insertBefore(newdiv,ni.childNodes[0]);


document.getElementById('barcode').value='';
document.getElementById('barcode2').value='';
checkTotal();
orgamount();
orgqty();
}
}

/////get Category

function MakeRequest1()
{ var arr = document.getElementsByClassName('design');
var str1=document.getElementById('cat').value;
var str = escape(document.getElementById('barcode').value);

 var catg = document.getElementsByClassName('catg');
///alert(catg.length);
 
 var flag=0;
 for (i=0;i<catg.length;i++) {
 
        ///alert(catg[i].value+"=="+str1);
	   if(catg[i].value==str1 )
	   {
			
				

	   flag=1;
	   break;
	    }
	    }
 
 if(flag==0){
 

  var xmlHttp = getXMLHttp();

// alert("hi");

  xmlHttp.onreadystatechange = function()

  {

    if(xmlHttp.readyState == 4)

    {

      HandleResponse1(xmlHttp.responseText);

    }

  }

// alert("hi2");



  
	 
 xmlHttp.open("GET", "getaudit1.php?barcode="+str1+"&cnt="+arr.length, false);

  xmlHttp.send(null);
}
}

function HandleResponse1(response)

{
////alert(response);
var s=response;
//alert(s);
if(s==0){

alert("There is no such Item.Please create new Item.");
document.getElementById('barcode').value='';
document.getElementById('barcode2').value='';

}else{
//alert(response);
document.getElementById('detail').innerHTML=response
/*var ni =document.getElementById('detail');

var numi = document.getElementById('theValue');
var num = parseInt(document.getElementById('theValue').value) +1;
numi.value = num;

var newdiv = document.createElement('div');

var divIdName = num;

newdiv.setAttribute('id',divIdName);

newdiv.innerHTML =response;
//ni.appendChild(newdiv);
///vnewdiv.innerHTML =response+"<table border='0' height='80' valign='top'><tr><td valign='top'><a href=\"javascript:;\" onclick=\"removeElement(\'" + divIdName + "\')\">Delete</a></td></tr></table>";
//ni.appendChild(newdiv);


ni.insertBefore(newdiv,ni.childNodes[0]);*/


document.getElementById('barcode').value='';
document.getElementById('barcode2').value='';
checkTotal();
orgamount();
orgqty();
}
}

////remove div
	 function removeElement(divNum) {
	 
            var d = document.getElementById('detail');
            var olddiv = document.getElementById(divNum);
           
            d.removeChild(olddiv);
			checkTotal()
        }
        
/////count total amount
function orgamount() {
        var sum=0;
     var arr = document.getElementsByClassName('toamt');
      //var tot=0;
	///alert(arr.length);
    for(var i=0;i<arr.length;i++){
   /////alert(arr[i].value+" <= "+arr1[i].value);
    sum = sum + Number(arr[i].value);
     
     
         
        }
		///alert(sum);
        document.listForm.qty12.value = sum;

      
    }
	///////////////////////total original qty
	function orgqty() {
        var sum=0;
     var arr = document.getElementsByClassName('org_qty');

    //var tot=0;
	//alert(arr.length);
    for(var i=0;i<arr.length;i++){
   /////alert(arr[i].value+" <= "+arr1[i].value);
    sum = sum + Number(arr[i].value);
     
     
         
        }
		///alert(sum);
        document.listForm.qty13.value = sum;

      
    }

        
        
///cmpr
function checkTotal() {
        var sum=0;
     var arr = document.getElementsByClassName('qty');
     var arr1 = document.getElementsByClassName('qt');
    //var tot=0;
	//alert(arr.length);
    for(var i=0;i<arr.length;i++){
   /////alert(arr[i].value+" <= "+arr1[i].value);
    sum = sum + Number(arr[i].value);
     
     
         
        }
		//alert(sum);
        document.listForm.qty11.value = sum;

      
    }
	 
function formSubmit()
{
//alert("hii");
/*if(document.getElementById('bill_date').value=="")
 {
alert("Please Select Bill Date to continue.");
document.getElementById('bill_date').focus();
return false;
}
else{*/
/////alert(v.value);
//document.listForm.command.value =v.value;
//document.listForm.submit( );

document.getElementById("frm1").submit();
 return true;
// }

}

     </script>
<body>

<div id="bill">
 <form name="listForm" action="updateedt_audit.php" method="post" id="frm1">
<table width="798" border="0" align="center">
<tr>
    <td width="792" height="42">
    
    <table width="793" >
       <tr>
        <td colspan="2" align="center" style="padding-left:100px;">
          <font size="+1">
          <?php
          $result5=mysqli_query($con,"select * from   `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($result5,10);

$row7=mysqli_fetch_array($result5);

?>
<img src="bill.png" width="408" height="165"/><br/><br/>

            <B><U>EDIT AUDIT ENTRY</U></B></font></td>
         </tr>            
 
  
    <tr>
     

 <td width="409">Item Code : <input type="text" name="barcode" onFocus="this.value=''" onClick="this.value=''"  id="barcode" onChange="MakeRequest();"/>
      <input type="hidden" name="myvar" value="0" id="theValue" /> <br/><br/> Barcode : 
      &nbsp;<input type="text" name="barcode2" onFocus="this.value=''" onClick="this.value=''"  id="barcode2" onChange=" MakeRequest();"/></td>
 
 
      <td width="372">Audit Date :<?php echo date('d/m/Y',strtotime($_GET['dt'])); ?>
        <br/><br/>
        Category: 
        <?php $sq=mysqli_query($con,"SELECT category FROM  `phppos_items` where name in(select item_id from audit where audit_date='".$_GET['dt']."') GROUP BY category");
		$ro=mysqli_fetch_row($sq);

echo $ro[0]; ?>
      </tr>
  </table><font size="2" >
 
  
    <table width="795" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
    <th width="42"><font size="2">SR NO.</font></th>
    <th width="99"><font size="2">BARCODE</font></th>
    <th width="99"><font size="2">ITEM NAME</font></th>
    <th width="103"><font size="2">CATEGORY</font></th>
    <th width="88"><font size="2"> PRICE</font></th>
    <th width="130"><font size="2">ORIGINAL QTY</font></th>
    <th width="162"><font size="2">AUDIT QTY</font></th>
   <!--<th width="57"><font size="2">DELETE</font></th>-->
   </tr>
  <?php
  $sr=0;
  $auqt=0;
  $ori=0;
  $tp=0;
  //echo "select * from audit where audit_date='".$_GET['dt']."'";
  $aud=mysqli_query($con,"select * from audit where audit_date='".$_GET['dt']."'");
  while($auro=mysqli_fetch_array($aud))
  {
  $sr=$sr=$sr+1;
   $qry=mysqli_query($con,"select item_number,name,category,unit_price,quantity from  phppos_items where name='$auro[1]' order by name");
   $suggest=mysqli_fetch_row($qry);
   $item_number =$suggest[0]; 
$name=$suggest[1]; 
$category=$suggest[2]; 
$unit_price=$suggest[3]; 
$tp=$tp+$suggest[3];
$quan=$suggest[4]; 
$ori=$ori+$suggest[4];
$auqt=$auqt+$auro[3];
  ?>
  <tr>
  <td><?php echo $sr; ?></td>
  <td width="95" align="center"><?php echo $item_number; ?><input name="barc[]" type="hidden" value="<?php echo $item_number; ?>" class="barc"/></td>
<td width="97" align="center"><?php echo $name; ?><input name="design[]" type="hidden" value="<?php echo $name; ?>" class="design"/>
</td>
<td width="114" align="center"><?php echo $ro[0]; ?></td>
<td width="86" align="center"> <?php echo $unit_price;  ?>
 <input name="auid[]" type="hidden" value="<?php echo $auro[0]; ?>" class="toamt"/>
 <input name="toamt[]" type="hidden" value="<?php echo $unit_price; ?>" class="toamt"/>
<input name="org_qty[]" type="hidden"  class="org_qty" value="<?php echo $quan; ?>"/></td>
<td  align="center" width="104"> <?php echo $quan; ?></td>

<td  align="center" width="155"> <input name="qty[]" type="text" class="qty" onkeyup="checkTotal();Totalamount();" value="<?php echo $auro[3]; ?>"/></td>
  </tr>
  <?php
  }
  ?>
<tr>
  
    <td align="right" colspan="7">
    Total Price : <input type="text" name="qty12" id="qty12"  value="<?php echo $tp; ?>"  class="qty12" readonly/>&nbsp;&nbsp;
    Total Original Qty : <input type="text" name="qty13" id="qty13"  value="<?php echo $ori; ?>"  class="qty13" readonly/>&nbsp;&nbsp;
    Total Audit Qty : <input type="text" name="qty11" id="qty11"  value="<?php echo $auqt; ?>"  class="qty11" readonly/>
    </td>
    
  </tr>

  <input name="id" type="hidden" value="<?php echo $cid ?>" />

<tr>
  <td colspan="8" align="center">
 
    <input type="hidden" name="command" value="">
<input type="button" onClick="formSubmit()" name="Submit" value="Audit" >
</td></tr>
</table></font></td>
    </tr>
     <tr><td>
</td></tr>
</table>
</form>
</div><br/><br/><div id="pageNavPosition"></div>
<?php CloseCon($con);?>
<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="/pos/home_dashboard.php">Back</a></center>

</body>
</html>