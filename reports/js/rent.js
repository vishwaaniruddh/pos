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
{  debugger;

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
                checkTotalqty();checkTotal1();checkTotal();
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

checkTotalqty();checkTotal1();checkTotal();

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
    }
    
///
function checkTotal1() {
       
   	
     var arr = document.getElementsByClassName('prz');
      var qty= document.getElementsByClassName('qty');
     var discount = document.getElementsByClassName('discount');
     var amt= document.getElementsByClassName('amt');
 
    
        for (i=0;i<arr.length;i++) {
       //alert(qty[i].value);

          amt[i].value=(Number(arr[i].value)*Number(qty[i].value)) - Number(discount[i].value)
       
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
	
	
	