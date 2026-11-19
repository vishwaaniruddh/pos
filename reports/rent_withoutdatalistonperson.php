<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css"  />
<!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>-->

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!--<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />-->
<!--<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>-->

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
 ///////////////////////////////////////////get mobile no. of beauti
  function loadXMLDoc()
{
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
		
		///alert(s1[0]+"/"+s1[1]);
		document.getElementById("tphone").value=s;
	
    //document.getElementById("myDiv").innerHTML=xmlhttp.responseText;

    }
  }
  var str=document.getElementById('name').value;
  //alert(str);
xmlhttp.open("GET","get_mobile.php?cid="+str,true);
xmlhttp.send();
}

//=======================Find booking all items

function findbook(){
	
	var str=document.getElementById('itmval').value;
	//alert(str);
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
				//alert(xmlhttp.responseText);
				//var s=xmlhttp.responseText;
			
			document.getElementById("bookdetail").innerHTML=xmlhttp.responseText;

			}
		  }
		  //alert("get_booking.php?itmname="+str);
		xmlhttp.open("GET","get_booking.php?itmname="+str,true);
		xmlhttp.send();
}
/////////////////
function formSubmit()
{
if(document.getElementById('cid').value=="-1")
 {
alert("Please Select Customer to continue.");
document.getElementById('cid').focus();
return false;

}else if(document.getElementById('bill_date').value=="")
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

   var arr = document.getElementsByClassName('design');
   var barc = document.getElementsByClassName('barc');
   var qty = document.getElementsByClassName('qty');
   var total_qty = document.getElementsByClassName('total_qty');
   document.getElementById('itmval').value=document.getElementById('barcode').value
   var bar = document.getElementById('barcode').value.toUpperCase();
   var bar2 = document.getElementById('barcode2').value.toUpperCase();
   var flag=0;
   for (i=0;i<arr.length;i++) {
        ///alert(arr[i].value.toUpperCase()+"-"+bar+"-"+barc[i].value.toUpperCase());
	    if(arr[i].value.toUpperCase()==bar || barc[i].value.toUpperCase()==bar2)
	    {
	        if(parseInt(qty[i].value)<parseInt(total_qty[i].value)){
        	    qty[i].value=parseInt(qty[i].value)+1;
                checkTotalqty();checkTotal1();checkTotal();checkTotalDeposit();
                document.getElementById('barcode').value='';
                document.getElementById('barcode2').value='';
        
        	    flag=1;
        	    break;
	        }
	        else
    	    {
    	        alert('Insufficient Item Quantity');
    	        flag=1;
    	    break;
    	    }
	    }
	    
     }
     if(flag==0){
        var xmlHttp = getXMLHttp();
    // alert("hi");
        xmlHttp.onreadystatechange = function()
        {
            if(xmlHttp.readyState == 4)
            {
               HandleResponse(xmlHttp.responseText);
            }
        }
        var pdt="";
        var ddt="";
// alert("hi2");
        if(frm1.pick[0].checked==true)
        {
         ///alert("hi");
        var pdt=document.getElementById('cust_pick').value;
        
        
        }
        if(frm1.del[1].checked==true)
        { //alert("hi11");
           var pdt=document.getElementById('compick_date').value
        	
        }
        if(frm1.del[0].checked==true)
        {
            var ddt=document.getElementById('cust_del').value;
        }
        if(frm1.pick[1].checked==true)
        { 
            var ddt=document.getElementById('comdel_date').value;
        }
        ///alert(pdt+"/////////"+ddt);
        if((frm1.pick[0].checked==false && frm1.pick[1].checked==false)&& (pdt=="" && ddt=="")){
        
            alert("Please Select Pick Up And Delivery Type And Date.");
        }else{
        var str = escape(document.getElementById('barcode').value);
        //alert(str);
        var str1=document.getElementById('barcode2').value;
                ///alert(str1);
                var arr = document.getElementsByClassName('amt');
         xmlHttp.open("GET", "getbarcode.php?barcode="+str+"&barcode2="+str1+"&cnt="+arr.length+"&pdt="+pdt+"&ddt="+ddt, false);
        
          xmlHttp.send(null);
        }
    }
}

function HandleResponse(response)

{
//alert(response);
if(response==0){
    alert('Insufficient Item Quantity');
}else{
var ni =document.getElementById('detail');

var numi = document.getElementById('theValue');
var num = parseInt(document.getElementById('theValue').value) +1;
numi.value = num;

var newdiv = document.createElement('div');

var divIdName = num;

newdiv.setAttribute('id',divIdName);

newdiv.innerHTML =response+"<table border='0' height='200' valign='middle'><tr><td valign='middle'><a href=\"javascript:;\" onclick=\"removeElement(\'" + divIdName + "\')\">Delete</a></td></tr></table>";

ni.insertBefore(newdiv,ni.childNodes[0]);

checkTotalqty();checkTotal1();checkTotal();checkTotalDeposit();

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

function ch(){
if(frm1.pick[0].checked==true)
{
 ///alert("hi");

document.getElementById('cust_pick').disabled=false;
document.getElementById('compick_date').disabled=true;

}
 if(frm1.del[1].checked==true)
{ //alert("hi11");

	document.getElementById('compick_date').disabled=false;
	document.getElementById('cust_pick').disabled=true;
}
}
function ch1(){
if(frm1.del[0].checked==true)
{


document.getElementById('cust_del').disabled=false;
document.getElementById('comdel_date').disabled=true;

}
 if(frm1.pick[1].checked==true)
{ 

	document.getElementById('comdel_date').disabled=false;
	document.getElementById('cust_del').disabled=true;
}
}


function checkTotal() {
        document.listForm.total.value = '';
        var sum = 0;
		var arr = document.getElementsByClassName('amt');
     
    //var tot=0;
	///alert(arr.length);
        for (i=0;i<arr.length;i++) {
       
     //alert(sum);
            sum = sum + Number(arr[i].value);
         
        }
        
          var radioValue = $("input[name='pay_By']:checked"). val();
         var cprtmt=0;
        if(radioValue=="Card")
        {
            var cprt=Number(2)/Number(100);
             cprtmt=Number(sum)*Number(cprt);
          // alert(cprt+"---"+cprtmt);
          sum=Number(sum)+Number(cprtmt);
          
         
          
        }
        
           document.getElementById("cardpercamt").value=cprtmt;
        
        document.listForm.total.value = sum;
        checkTotalDeposit();
    }
    
    
    
function checkTotalDeposit() {
        document.listForm.totalDeposit.value = '';
        var sum = 0;
		var arr = document.getElementsByClassName('dep');
     
        for (i=0;i<arr.length;i++) {
       
     //alert(sum);
            sum = sum + Number(arr[i].value);
         
        }
        
       
        document.listForm.totalDeposit.value = sum;
    }




///
function checkTotal1() {
       
   	
     var arr = document.getElementsByClassName('prz');
      var qty= document.getElementsByClassName('qty');
     var discount = document.getElementsByClassName('discount');
     var amt= document.getElementsByClassName('amt');
 var og_dep= document.getElementsByClassName('txt');
  var dep= document.getElementsByClassName('dep');
    
        for (i=0;i<arr.length;i++) {
       //alert(qty[i].value);

          amt[i].value=(Number(arr[i].value)*Number(qty[i].value)) - Number(discount[i].value)
          dep[i].value=Number(og_dep[i].value)*Number(qty[i].value) 
          
          console.log(Number(dep[i].value)*Number(qty[i].value) )
          
        }
        ///document.listForm.total.value = sum;
    }
	
///////total qty
function checkTotalqty() {

        document.listForm.qty11.value = '';
        var sum = 0;
	var arr = document.getElementsByClassName('qty');
     
    //var tot=0;
	///alert(arr.length);
        for (i=0;i<arr.length;i++) {
            sum = sum + Number(arr[i].value);
        }
        document.listForm.qty11.value = sum;
    }

    $('#cid').load('process_people.php');
	
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
		document.getElementById("cid").value=s1[1];
		showbills();
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

function loadPhoneNo2()
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
		document.getElementById("name").value=s1[1];
		showbills();
		}
		//document.getElementById("").value=s1[];
    //document.getElementById("myDiv").innerHTML=xmlhttp.responseText;

    }
  }
   var str=document.getElementById('tphone').value;
   //alert(str);
   xmlhttp.open("GET","getbyphone2.php?cid="+str,true);
   xmlhttp.send();
}
	
	
	
	
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

<?php
/*
$qry="select item_number,name,category,unit_price,quantity from  phppos_items where name='$barcode' and CAST(abs(quantity-0.00) AS DECIMAL) > 0";
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
if($num>0){	
    while($suggest = mysqli_fetch_array($res, MYSQLI_ASSOC)) 
 {

$item_number =$suggest['item_number']; echo $item_number;die;
}}
die; */
?>

<img src="bill.PNG" width="408" height="165"/><br/><br/>
<b>CONFIRMATION MEMO</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      
      <form name="listForm" action="order_detail.php"  method="POST" id="frm1">
       <br/>
       
       <table width="1079" height="83">
       <tr>
       <td width="145" height="36"><strong>Customer Pick-Up :</strong></td>
       <td width="48"> <input name="pick" id="pick" type="radio" value="Customer"></td>
       <td width="350"><strong>Customer Pick-Up Date :</strong><input type="text" name="cust_pick" id="cust_pick" onClick="displayDatePicker('cust_pick');" autocomplete="nope"/></td>  
       <td width="162"> <strong>Company Delivery :</strong></td>
       <td width="23"><input name="pick" id="del2" type="radio" value="Company Delivery" ></td>
       <td width="330" ><strong>Company Delivery Date: </strong><input type="text" name="comdel_date" id="comdel_date" onClick="displayDatePicker('comdel_date');" autocomplete="nope"/></td>
       </tr>
      
      <tr>
      <td height="39"><strong>Customer Return :</strong></td>
      <td><input name="del" id="del" type="radio" value="Customer Return" ></td>
      <td><strong>Customer Return Date :</strong><input type="text" name="cust_del" id="cust_del"   onClick="displayDatePicker('cust_del');" autocomplete="nope"/></td>
      <td width="162"><strong>Company Pick-Up :</strong></td>
      <td width="23"><input name="del" id="pick2" type="radio" value="Company Pickup"></td>
      <td><strong>Company Pick-Up Date :</strong><input type="text" name="compick_date" id="compick_date"  onClick="displayDatePicker('compick_date');" autocomplete="nope"/></td>
      </tr>
      </table>
      
      
      <center>
      <hr>
      <table width="100%" height="110">
      <tr><td><a href="people.php?mode=rent" target="_new">New Customer</a></td></tr>
      <tr><td width="103">Phone Number:</td> <td colspan="2"> <input type="text" name="phoneNo" id="phoneNo" value=""  /> <a href="#" onClick="loadPhoneNo();">Find</a></td></tr>
      
      <tr>
          <td width="103">Bill Made By</td>
          <td colspan="2"> 
          <select name="bill_by" required>
              <option>Select</option>
              <option value="Rajni Podar">Rajni Podar</option>
              <option value="Akruti Manjrekar">Akruti Manjrekar</option>
              <option value="Nipa Agrawal">Nipa Agrawal</option>
              
          </select>
          </td>
          <td width="103">Company Name</td>
          <td colspan="2"> 
          <select name="company_name" required>
              <option>Select</option>
              <option value="Sri Shringarr">Sri Shringarr</option>
              <option value="SAKAR TRADE LINK" selected>SAKAR TRADE LINK</option>
          </select>
          </td>
      </tr>
      <tr>
      <td width="143" height="34"><strong>Customer Name:&nbsp;</strong></td>
      <td width="154">
      <select name="cid" id="cid" >
         <option value="-1" >select</option>
     <?php 
// 	  include('config.php');
	  $result_name = mysqli_query($con,"SELECT * FROM  phppos_people  order by first_name");
	  while($row = mysqli_fetch_row($result_name)){ 
	      
	      if($row[11]!=""){
	  ?>
     <option value="<?php echo $row[11]; ?>" ><?php echo $row[0] ."  ". $row[1]; ?></option>
     
     <?php }} ?>
     </select>
     </td>
	 
	 <td width="195"><strong>2nd Person's Name:</strong></td><td width="200"><input type="text" name="pname" id="pname" />  <input type="hidden" name="myvar" value="0" id="theValue" /></td>
     <td width="157"><strong>Through Name:</strong></td>
     <td width="208">
      <select name="name" id="name" onChange="loadXMLDoc();">
         <option value="" >select</option>
     <?php
	  $bresult = mysqli_query($con,"SELECT * FROM  phppos_people  WHERE first_name LIKE 'B %' order by first_name");
	  while($brow = mysqli_fetch_row($bresult)){ 
	  ?>
     <option value="<?php echo $brow[11]; ?>" ><?php echo $brow[0] ."  ". $brow[1]; ?></option>
     <?php } ?>
     </select>
      </td>
    </tr>
    
    <tr>
    <td width="143" height="35" ><strong>Bill  Date :</strong></td><td><input type="text" name="bill_date" id="bill_date" onClick="displayDatePicker('bill_date');"/></td>
    <td><strong>2nd Contact No. :</strong> </td><td>         <input type="text" name="pcontact" id="pcontact" /></td>
    <td><strong>Through Phone No:</strong> </td><td>       <input type="text" name="tphone" id="tphone" />  <a href="#" onClick="loadPhoneNo2();">Find</a></td>
    </tr>
    
    <tr>
    <td width="143" height="31" >&nbsp;</td>
    <td>&nbsp;</td>
    <td><strong>Through Area:</strong></td><td><input type="text" name="area" id="area" /></td>
    </tr>
    </table>
    
    </center><hr>
    
      <table width="100%">
      <tr><td colspan="3"><div id="bookdetail"></div></td></tr>
      <tr>
      <td width="96"> <strong> Beautician Discount : </strong></td>
      <td width="280"><input type="radio" name="commis" id="commis" value="Rs."  checked="checked"/><label for="radio">Rs.<input type="radio" name="commis" id="commis" value="%" />%<br/>
       <input type="text"  name="comm" id="comm"  value="0"/>
       </label>
      </td>
      <td width="493"><strong>Item code: </strong>
      <input type="text" name="barcode" onFocus="this.value=''" onClick="this.value=''"  id="barcode" onChange="MakeRequest();findbook();"/> 
	   <input type="hidden" name="itmval"   id="itmval"/>
       &nbsp;&nbsp;&nbsp;&nbsp; Barcode : 
       <input type="text" name="barcode2" onFocus="this.value=''" onClick="this.value=''"  id="barcode2" onChange=" MakeRequest();"/>
      </td>
      </tr>
      </table>
    <hr>
     
       <table width="1301" border="1" cellpadding="4" cellspacing="0">
         <tr>
    <td width='62'><U><strong>Sr.No.</strong></U></td>  
    <td width='59'><U><strong>Item Code</strong></U></td>
    <td width='135'><U><strong>Category</strong></U></td>
    <td width='120'><U><strong>Price</strong></U></td>
    <td width='104'><U><strong>Qty</strong></U></td>
    <td width='136'><U><strong>Rent</strong></U></td>
    <td width='137'><U><strong>Discount</strong></U></td>
    <td width='107'><U><strong>Amount</strong></U></td>
    <td width='139'><U><strong>Deposit</strong></U></td>
	 <td width='120'><U><strong>Booking Status</strong></U></td>
    <td width='70'><U><strong>Delete</strong></U></td>
  </tr>
  
  <tr>
  <td colspan="11"><div id="detail"></div></td>
  </tr>
  </table><br/>
  
      <table width="1240" height="29">
      <tr>
      <td width="44"><strong>Rent:-</strong></td>
      <td width="173">
        <input type="radio" name="rentpaid" id="rentpaid" value="Paid">
        <strong>        Paid Amount</strong><br/>
        <input type="radio" name="rentpaid" id="rentpaid" value="Unpaid">
        <strong>       Unpaid Amount      
        </strong><br/>
           <input type="radio" name="rentpaid" id="rentpaid" value="Balance">
           <strong>Balance Amount
           </strong>
       </td>
        <td width="90"><strong>Deposit :-</strong></td>
        <td width="171"><strong>
          <input type="radio" name="paid" id="paid" value="Paid">
          Paid <br/>
           <input type="radio" name="paid" id="paid2" value="Unpaid">
          Unpaid </strong>
        </td>
         <td width='186'><strong>Total Qty :</strong> <input type="text" name="qty11" id="qty11"  value="" style="width:100px;" readonly/></td>
        
         <td width="192"><strong>Amount Paid : </strong>&nbsp;<input type="text" name="pamount" id="pamount" value="" size="11"></td>
         
		 <td width="206"><strong>Total Amount : </strong><input type="text" size="10" name="total" id="total" value="0" />
		 <td width="206"><strong>Total Deposit : </strong><input type="text" size="10" name="totalDeposit" id="totalDeposit" value="0" />
		 	<input type="hidden" name="cardpercamt" id="cardpercamt"  value="" style="width:100px;" readonly/>
		 </td>
        
        </tr>
        
		<tr>
        
        
        
        <td>In Account :</td>
	 <td><select id="acc" name='acc'><option value="0">Select </option>
      <?php 
      
      $qryitem=mysqli_query($con,"select bank_name, bank_id from banks");
       while($row=mysqli_fetch_row($qryitem))
		 {
				echo "<option value='".$row[1]."'>".$row[0]."</option>";
			}?></select></td>
			
			
			<td width="90">Payment By : </td>
			<td width="173">
        <input name="pay_By" type="radio" value="Cheque" onchange="checkTotal()">
        <strong>Cheque</strong><br>
        <input name="pay_By" type="radio" value="Cash" onchange="checkTotal()">
        <strong>Cash</strong><br> 
        <input name="pay_By" type="radio" value="Card" onchange="checkTotal()">
        <strong>Card</strong>
        
        <td><strong>Note:</strong></td>
		<td><textarea name="note" id="note" rows="3" cols="55"></textarea></td>
        
        </td>
			
        </tr>
        <tr><td></td>
          <td><strong>Trail Date :</strong><input type="text" name="trail_date" id="trail_date"   onClick="displayDatePicker('trail_date');" autocomplete="nope"/></td> 
          <td width="90"><strong>Measurement :-</strong></td>
        <td width="171"><select id="measurement" name='measurement'><option value="">Select </option><option value="yes">yes</option><option value="no">no</option>
        </td>
        <td><strong>Measurement Note:</strong></td>
		<td><textarea name="measurement_note" id="note" rows="3" cols="55"></textarea></td>
		
        <td width="90"><strong>Delivery :-</strong></td>
        <td width="171"><select id="delivery" name='delivery'><option value="">Select </option><option value="yes">yes</option><option value="no">no</option>
        </td>
        </tr>
        </table><br/>
        
<input type="button" onClick="formSubmit()" value="Rent Bill" />
</form>
</center>
</td>
</tr>
</table>
</div>
<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
<script>
//     $('#cid').select2({
//   selectOnClose: true
// });
</script>
</body>