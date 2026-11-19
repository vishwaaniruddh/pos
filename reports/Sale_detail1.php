<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css"  />
<?php
ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

$cid=$_GET['id'];


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


	
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
       /// alert(arr[i].value.toUpperCase()+"-"+bar+"-"+barc[i].value.toUpperCase()+"-"+bar2);
	    if(arr[i].value.toUpperCase()==bar || barc[i].value.toUpperCase()==bar2)
	    {
	    qty[i].value=parseInt(qty[i].value)+1;
checkTotal();

document.getElementById('barcode').value='';
document.getElementById('barcode2').value='';
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
//alert("h");
      HandleResponse(xmlHttp.responseText);

    }

  }

 //alert("hi2");



var str = escape(document.getElementById('barcode').value);
//alert(str);
var str1=document.getElementById('barcode2').value;

 xmlHttp.open("GET", "getbarcode3.php?barcode="+str+"&id="+<?php echo $cid; ?>+"&barcode2="+str1, false);

  xmlHttp.send(null);

}
}
function HandleResponse(response)
{

var s=response;
///alert(s);
if(s==0){
document.getElementById('barcode').value='';
document.getElementById('barcode2').value='';
///alert("This Item is already returned.");

}else{
///alert("ddd");
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
checkTotal();

document.getElementById('barcode').value='';
document.getElementById('barcode2').value='';

}
}

////remove div
	 function removeElement(divNum) {
	 
            var d = document.getElementById('detail');
            var olddiv = document.getElementById(divNum);
            d.removeChild(olddiv);
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
      if(arr[i].value <= arr1[i].value  ){
     
         
         }else{ alert("Quantity can't be more than Original Quantity");
         sum-=arr[i].value;
         arr[i].value="";
         
          }
        }
		
        document.listForm.qty11.value = sum;

      
    }
    
function formSubmit()
{
if(document.getElementById('bill_date').value=="")
 {
alert("Please Select Bill Date to continue.");
document.getElementById('bill_date').focus();
return false;
}
else{
/////alert(v.value);
//document.listForm.command.value =v.value;
//document.listForm.submit( );

document.getElementById("frm1").submit();
 return true;
 }

}

     </script>
<body>

<div id="bill">
 <form name="listForm" action="updatereturn2.php" method="post" id="frm1">
<table width="803" border="0" align="center">
<tr>
    <td width="797" height="42">
    
    <table width="800" >
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
<img src="bill.PNG" width="408" height="165"/><br/><br/>

            <B><U> SALE RETURN</U></B></font></td>
         </tr>            
  <tr>
  <td height="42" colspan="2" align="left"><br /></td>
    </tr>
    
  <tr>
    <td width="441" ></td>
    </tr>
    
  <tr>
    <td height="21"><font size="+1" >M/s.&nbsp;:&nbsp;&nbsp;<?PHP echo $row[0] . " ".$row[1]; ?></font></td>
    <td width="347">Salel Return Date:
      <input type="text" name="bill_date" id="bill_date" value="<?php echo date('d/m/Y'); ?>" onClick="displayDatePicker('bill_date');"/></td>
    </tr>
    
  <tr>
    <td height="23">Contact No.: &nbsp;&nbsp;&nbsp;
      <?php echo $row[2]; ?></td>
    <td>&nbsp;</td></tr>
     <tr>
     <?php  $pd=0;
 $s1=0;			

$ba=0;
$na=0;	
$ra=0;
/////count total retun
$qry1="SELECT * FROM  `approval` where cust_id='$cid' and status='S' ";
$res1=mysqli_query($con,$qry1);                
$num1=mysqli_num_rows($res1);
while($row1=mysqli_fetch_row($res1)){


$qry2="SELECT sum(paid_amount) FROM  `approval` where bill_id ='$row1[0]'";
$res2=mysqli_query($con,$qry2);                
$num2=mysqli_num_rows($res2);
$row2=mysqli_fetch_row($res2);
			
$qry3="SELECT sum(`amount`) FROM `approval_detail` WHERE bill_id ='$row1[0]'";
$res3=mysqli_query($con,$qry3);
$row3=mysqli_fetch_row($res3);
$a=0;	
$a1=0;
$qry4="SELECT *  FROM `approval_detail` WHERE bill_id ='$row1[0]'";
$res4=mysqli_query($con,$qry4);

while($row4=mysqli_fetch_row($res4)){

// $a=round(($row4[7]/$row4[2])*$row4[4]);

if ($row4[2] != 0) {
    $a = round(($row4[7] / $row4[2]) * $row4[4]);
} else {
    // Handle the division by zero case, for example, set $a to 0 or some other default value
    $a = 0; // or any other appropriate value or action
}

$a1+=$a;
//echo $row4[7]."/".$row4[2].")*".$row4[4]."=".$a1."<br/>";

//echo "c-".$row[11]."pd-".$row2[0]."bll-".$row1[0]."/".$a1."k<br/>";
$ba+=$row4[7];
}

$pd+=$row1[4];
$na=$ba-$pd;
$s1=$ba-$a1-$pd; 
  

}
?>
    <td height="23">Paid Amount.: &nbsp;&nbsp;&nbsp;
      <?php echo $pd; ?></td>
    <td>Balance Amount:<?php echo $s1; ?></td>
    
    </tr>
  
    <tr>
     

 <td>Item Code : <input type="text" name="barcode" onFocus="this.value=''" onClick="this.value=''"  id="barcode" onChange="MakeRequest();"/>
      <input type="hidden" name="myvar" value="0" id="theValue" /> <br/><br/> Barcode : 
      &nbsp;<input type="text" name="barcode2" onFocus="this.value=''" onClick="this.value=''"  id="barcode2" onChange=" MakeRequest();"/></td></tr>
  </table><font size="2" >
 
  
    <table width="770" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
    <th width="86"><font size="2">ITEM CODE</font></th>
    <th width="135"><font size="2">PARTICULARS</font></th>
   <th width="90"><font size="2"> Price</font></th>
    <th width="101"><font size="2">ORIGINAL QTY</font></th>
     <th width="113"><font size="2"> RETURN QTY</font></th>
     
       
      <th width="183"><font size="2">QTY</font></th>
      
    
  </tr>
  <tr>
  
    <td align="left" colspan="6">
    
     <div id="detail"></div>
    </td>
    
  </tr>
<tr>
  
    <td align="right" colspan="6">
    
    Total Qty : <input type="text" name="qty11" id="qty11"  value=""  readonly/>
    </td>
    
  </tr>

  <input name="id" type="hidden" value="<?php echo $cid ?>" />

<tr>
  <td colspan="8" align="center">
  Payment By :
    <input type="radio" name="radio2" id="radio2" value="Cash" />
    <label for="radio">Cash</label>
    <input type="radio" name="radio2" id="radio2" value="Cheque" />
    <label for="radio2">Cheque</label>
    &nbsp;&nbsp;&nbsp;&nbsp;
    Paid Amount : &nbsp; <input type="text" name="amt" id="amt" /><br/><br/>     &nbsp;&nbsp;
    <input type="hidden" name="command" value="">
<input type="button" onClick="formSubmit()" name="Submit" value="Approval Return" >
&nbsp;&nbsp;&nbsp;

<!--<input type="button" onclick="formSubmit(this)" name="Final Submit" value="Final Approval Return" >--></td></tr>
</table></font></td>
    </tr>
     <tr><td>
</td></tr>
</table>
</form>
</div><br/><br/><div id="pageNavPosition"></div>

<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="/app_return.php">Back</a></center>

</body>
</html>