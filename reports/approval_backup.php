<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css"  />
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
	if(document.getElementById('cid').value== -1)
 {
alert("Please enter Customer Id to continue.");
document.getElementById('cid').focus();
return false;
} else if(document.getElementById('by').value=="")
 {
alert("Please Select Bill By to continue.");
document.getElementById('by').focus();
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
/////

/*
function ct(){

var str =document.getElementById('barcode').value;
var arr = document.getElementsByClassName('design');
var qty2 = document.getElementsByClassName('qty');

     ///alert(arr.length);
     if(arr.length==0){
     
      MakeRequest();
    
     }else{
      alert("else");
   for (i=0;i<arr.length;i++) {
   
       alert(arr[i].value+"/"+str);
       
	if(arr[i].value!=str){
	
	  alert("else2");
	   MakeRequest();
	  break;   
		
	   }else{
	   alert("h");
	   /// qty2[i].value=Number(qty2[i].value)+1;
       }
        
       }
   }
}

*/
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
	    qty[i].value=parseInt(qty[i].value)+1;
checkTotal();
Totalamount();
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

      HandleResponse(xmlHttp.responseText);

    }

  }

// alert("hi2");



var str = escape(document.getElementById('barcode').value);
//alert(str);

var str1=document.getElementById('barcode2').value;
        ///alert(str1);
 xmlHttp.open("GET", "getbarcode1.php?barcode="+str+"&barcode2="+str1, false);

  xmlHttp.send(null);
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

newdiv.innerHTML =response+"<table border='0' height='80' valign='top'><tr><td valign='top'><a href=\"javascript:;\" onclick=\"removeElement(\'" + divIdName + "\')\">Delete</a></td></tr></table>";
//ni.appendChild(newdiv);

ni.insertBefore(newdiv,ni.childNodes[0]);
checkTotal();
Totalamount();
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
        
/////count Item code

</script>
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script>
///////////Assets
    $(document).ready(function(){
 
        //iterate through each textboxes and add keyup
        //handler to trigger sum event
        $(".txt").each(function() {

            $(this).keyup(function(){
                calculateSum();
            });
        });
 
    });
 
    function calculateSum() {
 
        var sum = 0;
        //iterate through each textboxes and add the values
        $(".txt").each(function() {
 
            //add only if the value is number
            if(!isNaN(this.value) && this.value.length!=0) {
                sum += parseFloat(this.value);
               
            }
 
        });
        //.toFixed() method will roundoff the final sum to 2 decimal places
     $("#total").val(sum.toFixed(2));
    }
    
  ////////end
  ///////////////////////////////////////////get paid or balance amount
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
		var s1=s.split('&&');
		///alert(s1[0]+"/"+s1[1]);
		document.getElementById("tp").value=s1[0];
		document.getElementById("ba").value=s1[1];
    //document.getElementById("myDiv").innerHTML=xmlhttp.responseText;

    }
  }
  var str=document.getElementById('cid').value;
  //alert(str);
xmlhttp.open("GET","amount.php?cid="+str,true);
xmlhttp.send();
}

//////////////////Phone Number

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
	//	alert(s);
		var s1=s.split('&&');
		//alert(s1[0]+"/"+s1[1]);
		if(s1[0]=="0")
		alert("No such Phone Number"); 
		else{
		document.getElementById("cid").value=s1[1];
		
			$.ajax({
        type: "POST",
        url: 'get_people_data_by_phone.php',
        data: { userid: s1[1] }, // Properly formatted data object
        success: function (msg) {
            if (msg != 0) {
                var obj = JSON.parse(msg);
                var fields = ['person_id','personName'];



                // Populate the fields (if needed, more logic can be added here)
                    $('#cid').val(obj.person_id);
                    $('#people').val(obj.personName);
                
            } else {
                alert('No Info With This Name');
                $('#people').val('');
            }
        },
        error: function () {
            alert('An error occurred while fetching data.');
            $('#people').val('');
        }
    });
    
    
    
    
    
		loadXMLDoc();
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




/////////////discount
function yes(){
if(document.getElementById('dyes').checked==true){

document.getElementById('dyes').checked=true	
document.getElementById('dno').checked=false;
document.getElementById('itemwise').disabled=false;
document.getElementById('totalwise').disabled=false;
document.getElementById('totalwise').disabled=false;
for (i=0; i<document.listForm.disper.length; i++)
         {
        // alert(document.forms.completed.length);
         document.listForm.disper[i].disabled=false;
         }
         document.getElementById('dis').disabled=false;
}
}
function no(){
	///alert("f");
 if(document.getElementById('dno').checked==true){

document.getElementById('dno').checked=true;
document.getElementById('dyes').checked=false;
document.getElementById('itemwise').disabled=true;
document.getElementById('totalwise').disabled=true;

        /// alert(document.listForm.disper.length);
		 for (i=0; i<document.listForm.disper.length; i++)
         {
         document.listForm.disper[i].disabled=true;
         }
		 //alert("f1");
}
document.getElementById('dis').disabled=true;
}

function yes1(){
	//alert("hi");
if(document.getElementById('itemwise').checked==true){

document.getElementById('itemwise').checked=true	
document.getElementById('totalwise').checked=false;
for (i=0; i<document.listForm.disper.length; i++)
         {
         //alert(document.forms.completed.length);
         document.listForm.disper[i].disabled=true;
         }
         document.getElementById('dis').disabled=true;
}
}
function no1(){
 if(document.getElementById('totalwise').checked==true){

document.getElementById('totalwise').checked=true;
document.getElementById('itemwise').checked=false;

for (i=0; i<document.listForm.disper.length; i++)
         {
        // alert(document.forms.completed.length);
         document.listForm.disper[i].disabled=false;
         }
         document.getElementById('disper').checked=true;
         document.getElementById('dis').disabled=false;
}
}
///////total qty
function checkTotal() {

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

/////total amount
function Totalamount() {
       
        var sum = 0;
		var sum1=0;
		
		
	var design=document.getElementsByClassName('design');	
	var arr = document.getElementsByClassName('qty');
     var prz=document.getElementsByClassName('prz');
      var dis=document.getElementsByClassName('ds');
        var dis1=document.getElementsByClassName('ds1');
	var dsamt=document.getElementsByClassName('disamt');
	var t_qty=document.getElementsByClassName('total_qty'); 
	   //var tot=0;
	///alert(dsamt.length);
        for (i=0;i<prz.length;i++) {
       //// alert(dsamt[i].value);
	     if(Number(arr[i].value)>t_qty[i].value)
	     {alert('Insufficient Item Quantity');

	     var num = parseInt(document.getElementById('theValue').value);
document.getElementById('qty11').value-=1;
arr[i].value=Number(arr[i].value)-1;
	    // document.getElementById('qty11').value-=Number(arr[i].value);
	   	//removeElement(num);
	   	Totalamount();
	   	return;
	   	
	     	     }
	     sum1=Number(prz[i].value)*Number(arr[i].value);
	    
	     if(t_qty[i].value==0)
	    { alert("No Org. Quantity left for product "+design[i].value);
	   	var num = parseInt(document.getElementById('theValue').value);
	   	removeElement(num);
	   	//document.getElementById('qty11').value-=1;
	   	Totalamount();
	   	return;
	      }
        if(document.getElementById('itemwise').checked==true){
        
      
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
			
         }else if(document.getElementById('totalwise').checked==true){
       
			 
			 var c=Number(document.getElementById('dis').value)/sum1;
                              var b=c*100;
				
				var dd=document.getElementsByClassName('dis2');
	for (j=0; j<dd.length; j++)
         {
         //alert(dd.length);
         if(dd[j].checked==true){
			 /// alert("true");
			 v=Math.round(sum1*(document.getElementById('dis').value/100.0));
			 /// alert(sum1+","+v);
			 a=sum1-v;
			 
		 }else {
			// alert("else");
			v=sum1-document.getElementById('dis').value;
			a=v;
		 }
         }
         sum = sum +a ;
         
         }
        }
        
        
        var radioValue = $("input[name='pay_By']:checked"). val();
         var cprtmt=0;
        if(radioValue=="Card")
        {
            var cprt=Number(2)/Number(100);
             cprtmt=Number(sum)*Number(cprt);
           // alert(cprt+"---"+cprtmt);
          sum=Number(sum)+cprtmt;
          
         
          
        }
        document.getElementById("cardpercamt").value=cprtmt;
        
        document.listForm.amountTotal.value = sum;
		
    }
    
     $('#cid').load('processpeople.php');
</script>
<body onLoad="yes1();">
<div style="text-align: center;">
<font size="+1">
<a href="/pos/home_dashboard.php">Back</a></font>
<table width="894" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
  
<tr><td width="882" align="center"  valign="top">
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
<!--<img src="bill.PNG" width="408" height="165"/><br/><br/>-->
<table width="100%">
       <tr>
          
          <td style="padding:0px; margin:0px;">
            <div style="text-align:left;font-size:14px;"> <b>SRI SHRINGARR FASHION STUDIO</b> </div><br/>  
            <div style="text-align:left;font-size:14px;"> <b>UPI ID: srishringarrfashionstudio@icici</b> </div><br/>
            <div style="text-align:left;padding-left:50px" > <img src="img/sri_qr_icici.jpg" width="80px"> </div>
              
              
          </td>
          
          <td style=" padding: 0px; margin:0px; padding-left: 50px; ">
              <img src="bill.PNG" width="250px" style="padding-right:80px">
          </td>
          
          <td style="padding:0px; margin:0px; " style="font-size:11px;">
              <div><b><u style="font-size:11px;">Bank Account Details</u></b></div>
              <div style="font-size:10px;">SRI SHRINGARR FASHION STUDIO</div>
              <div style="font-size:11px;">Account number: 756305000529</div>
              <div style="font-size:11px;">IFSC: ICICI0007563</div>
              <div style="font-size:11px;">Bank name: ICICI BANK</div>
              <div style="font-size:11px;">Branch: VILE PARLE EAST BRANCH</div>
          </td>
       
       </tr>
       </table>
       <hr style="margin:3px;border-top: 1px solid #000;">
Approval Sale<br/><br/><center>
<form  action="approval_detail.php" id="frm1" name="listForm" method="POST">
<table width="863">
<tr>
    <td width="103">Phone Number:</td> <td> <input type="text" name="phoneNo" id="phoneNo" value=""  /> <a href="#" onClick="loadPhoneNo();">Find</a></td>
    <td width="">Item code: </td><td><input type="text" name="barcode" onFocus="this.value=''" onClick="this.value=''"  id="barcode" onChange=" MakeRequest();"/></td>
</tr>


<tr><td width="103" height="35">
Customer Name:</td>



<td width="225">
    <input type="hidden" name="cid" id="cid" />
    <input type="text" id="people" class="form-control" list="peopleOptions" name="person" placeholder="Enter people ..." />
    <datalist id="peopleOptions"></datalist>
      </td>
      
      <a href="people.php?mode=app" target="_new">New Customer</a>
     
      <td width="">Barcode :</td><td> <input type="text" name="barcode2" onFocus="this.value=''" onClick="this.value=''"  id="barcode2" onChange=" MakeRequest();"/> 
           <input type="hidden" name="myvar" value="0" id="theValue" /></td>
           
    </tr>
      <tr>
        <td width="103" height="36"> Paid Amount:</td><td><input type="text" name="tp" id="tp" readonly/> </td>
      <td>Balance Amount:</td><td><input type="text" name="ba" id="ba" readonly /></td></tr>
   <tr>
        <td width="103" height="36"> Discount:</td><td><input type="checkbox" name="dyes" id="dyes" value="yes" onClick="yes();" checked>
          <label for="checkbox">Yes</label><input type="checkbox" name="dno" id="dno" value="no" onClick="no();">
          <label for="checkbox">No</label></td>
      <td>Bill By:</td><td>
        <select name="by" id="by">
        <option value="">Select</option>
        <option value="APPROVAL RECEIPT">APPROVAL RECEIPT</option>
        <option value="QUOTATION">QUOTATION</option>
        </select></td></tr>
       <tr>
        <td width="103" height="36">&nbsp;</td>
        <td>Item Wise: 
          <input type="checkbox" name="itemwise" id="itemwise" value="Item Wise" onClick="yes1();" checked>&nbsp;&nbsp;&nbsp;
          Total Wise:
          <input type="checkbox" name="totalwise" id="totalwise" value="Total Wise" onClick="no1();"></td>
      <td>Bill Date:</td>
      <td><input type="text" name="bill_date" id="bill_date" onClick="displayDatePicker('bill_date');"/></td></tr>
      </table>
     
      <table width="818" border="1" cellpadding="4" cellspacing="0">
  <tr>
  
    <td width='93'><U>Item Code</U></td>
    <td width='112'><u>Item Name</u></td>
    <td width='92'><U>Price</U></td>
    <td width='203'><u>Qty</u></td>
     <td width='184'><u>Discount</u></td>
     <td width='72'><U>Org.Qty</U></td>
    <td width='72'><U>Delete</U></td>
  </tr><tr><td colspan="7">
 <div id="detail"></div>

     </td></tr>
       <tr>
    <td colspan="3">&nbsp;</td>
    <td width='203'>Total Qty : <input type="text" name="qty11" id="qty11"  value="" style="width:100px;" readonly/></td>
     <td colspan="3">Amount Total <input type="text" name="amountTotal" id="amountTotal"  value="" style="width:100px;" />
     
     <input type="hidden" name="cardpercamt" id="cardpercamt"  value="" style="width:100px;" readonly/>
     </td>
    </tr>
      </table>
      
      <br/>   <table width="821"><tr><td width="56">Discount:</td><td width="192" >Rs<input name="disper" id="disper" type="radio" value="Rs" class="dis2" onClick="Totalamount();" >&nbsp;&nbsp;%<input name="disper" type="radio" value="%" id="disper" class="dis2" onClick="Totalamount();" ><br/>
      
      <input name="dis" id="dis" type="text" value="" onKeyUp="Totalamount();"  /></td>
      <td width="263">Paid Amount:&nbsp;<input type="text"  name="amt" id="amt" /></td>
      <td width="340">Payment By : Cheque
      
       <input name="pay_By" type="radio" value="Cheque" onchange="Totalamount()">
        &nbsp;Cash
        <input name="pay_By" type="radio" value="Cash" onchange="Totalamount()">
        &nbsp;Card 
        <input name="pay_By" type="radio" value="Card" onchange="Totalamount()">
       
      
      </td>
      </tr>
	  <tr>
	  <td>Note : </td>
	  <td colspan="2"><textarea name="note" id="note" rows="3" cols="55"></textarea></td>
      
      <td>In Account :
	 <select id="acc" name='acc'><option value="0">Select </option>
      <?php 
      
      $qryitem=mysqli_query($con,"select bank_name, bank_id from banks");
       while($row=mysqli_fetch_row($qryitem))
		 {
				echo "<option value='".$row[1]."'>".$row[0]."</option>";
			}?></select></td>
	  </tr>
      
      </table>
 <input type="button" onClick="formSubmit()" value="Approval Bill" />  </form></center>
 </td></tr>
 
 </table>
</div>

<script>
    $(document).ready(function() {
        $("#people").on('input', function () {
            var input = $(this).val();

            $.ajax({
                type: "POST",
                url: './get_suggestions_people.php',
                data: { input: input },
                success: function (response) {
                    var datalist = $("#peopleOptions");
                    datalist.empty();

                    var suggestions = JSON.parse(response);

                    suggestions.forEach(function (suggestion) {
                        datalist.append($("<option>").attr('value', suggestion));
                    });
                }
            });
        });
    });
        $(document).ready(function () {
       function populatePeopleData(people) {
    $.ajax({
        type: "POST",
        url: 'get_people_data.php',
        data: { people: people }, // Properly formatted data object
        success: function (msg) {
            if (msg != 0) {
                var obj = JSON.parse(msg);
                var fields = ['person_id'];

                // Populate the fields (if needed, more logic can be added here)
                    $('#cid').val(obj.person_id);
                
            } else {
                alert('No Info With This Name');
                $('#people').val('');
            }
        },
        error: function () {
            alert('An error occurred while fetching data.');
            $('#people').val('');
        }
    });
}

     






      

        $("#people").on('change', function () {
            var people = $(this).val();
            populatePeopleData(people);

        });
    });

</script>


<?php CloseCon($con);?>

<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>