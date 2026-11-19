<script type="text/javascript" src="datepicker/datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="datepicker/date_css.css"  />
<script type="text/javascript" src="jquery-1.11.1.min.js"></script>
<script>
function checkUsername(keyEvent1)
{
	//alert("hi");
keyEvent1 = (keyEvent1) ? keyEvent1: window.event;
input = (keyEvent1.target) ? keyEvent1.target :
keyEvent1.srcElement;
if (keyEvent1.type == "keyup") {
var targetDiv = document.getElementById("targetDiv1");
targetDiv.innerHTML = "<div></div>";

var targetDiv1 = document.getElementById("restul");
targetDiv1.innerHTML = "<div></div>";
if (input.value) {
	//alert(input.value);
getData("itemsearch.php?qu=" + input.value),getDataitem("search.php?searchdata=" + input.value);

}
}
}





function getData(dataSource)
{	
var XMLHttpRequestObject = false;
 if (window.XMLHttpRequest) {
 XMLHttpRequestObject = new XMLHttpRequest();
 } else if (window.ActiveXObject) {
 XMLHttpRequestObject = new ActiveXObject("Microsoft.XMLHttp");
 }
if(XMLHttpRequestObject) {
XMLHttpRequestObject.open("GET", dataSource);
XMLHttpRequestObject.onreadystatechange = function()
{
if (XMLHttpRequestObject.readyState == 4 && XMLHttpRequestObject.status == 200) {
	//alert("result= "+XMLHttpRequestObject.responseText);
    
	if(XMLHttpRequestObject.responseText == "taken"){
document.getElementById("targetDiv1").innerHTML = "<div style='color:red; font-weight:bold;'>This Item Name already exists and this will create batch code for it.</div>";
    
    }else{
		document.getElementById("targetDiv1").innerHTML = "<div style='color:red; font-weight:bold'>This Item Name  does not exists.</div>";
		}
		
}
}
XMLHttpRequestObject.send(null);
}
}

//////////////////////////////////////////////

function getDataitem(dataSource)
{	
var XMLHttpRequestObject = false;
 if (window.XMLHttpRequest) {
 XMLHttpRequestObject = new XMLHttpRequest();
 } else if (window.ActiveXObject) {
 XMLHttpRequestObject = new ActiveXObject("Microsoft.XMLHttp");
 }
//alert("hia");
if(XMLHttpRequestObject) {
XMLHttpRequestObject.open("GET", dataSource);
XMLHttpRequestObject.onreadystatechange = function()
{
if (XMLHttpRequestObject.readyState == 4 && XMLHttpRequestObject.status == 200) {
	//alert("result1= "+XMLHttpRequestObject.responseText);
    
	
	document.getElementById("restul").innerHTML = XMLHttpRequestObject.responseText;
    
    
}
}
XMLHttpRequestObject.send(null);
}
}
</script>


<script type="text/javascript">

/*function checkUsername2(keyEvent)
{
//alert("hi");
keyEvent = (keyEvent) ? keyEvent: window.event;
input = (keyEvent.target) ? keyEvent.target :
keyEvent.srcElement;
if (keyEvent.type == "keyup") {
  //var targetDiv = document.getElementById("result");
  //targetDiv.innerHTML = "<div></div>";
if (input.value) {
	//alert(input.value);
getDataitem("search.php?searchdata=" + input.value);
}
}
}

////-------------

var XMLHttpRequestObject = false;
 if (window.XMLHttpRequest) {
 XMLHttpRequestObject = new XMLHttpRequest();
 } else if (window.ActiveXObject) {
 XMLHttpRequestObject = new ActiveXObject("Microsoft.XMLHttp");
 }

function getDataitem(dataSource)
{	
//alert("hia");
if(XMLHttpRequestObject) {
XMLHttpRequestObject.open("GET", dataSource);
XMLHttpRequestObject.onreadystatechange = function()
{
if (XMLHttpRequestObject.readyState == 4 && XMLHttpRequestObject.status == 200) {
	//alert("result1= "+XMLHttpRequestObject.responseText);
    
	
	document.getElementById("restul").innerHTML = XMLHttpRequestObject.responseText;
    
    
}
}
XMLHttpRequestObject.send(null);
}
}*/

//jquery method 

/*$(function(){
$(".item_id").keyup(function() 
{ 
//alert("hi");
var searchid = $(this).val();
var dataString = 'search='+ searchid;
if(searchid!='')
{
    $.ajax({
    type: "POST",
    url: "search.php", //page
    data: dataString,  //var send to page
    cache: false,
    success: function(html)
    {
    $("#result").html(html).show();
    }
    });
}else{ 
  var targetDiv = document.getElementById("result");
  targetDiv.innerHTML = "<div></div>";

}
 //return false; 
   
});

jQuery("#result").live("click",function(e){ 
    var $clicked = $(e.target);
    var $name = $clicked.find('.name').html();
    var decoded = $("<div/>").html($name).text();
    $('#textField').val(decoded);
});
jQuery(document).live("click", function(e) { 
    var $clicked = $(e.target);
    if (! $clicked.hasClass("search")){
    jQuery("#result").fadeOut(); 
    }
});
$('#textField').click(function(){
    jQuery("#result").fadeIn();
});
});*/

</script>
<!-- -------------------------------------------------------------------------------------------------------------------------->
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
function showrow()
{
	//alert("hii");
	document.getElementById('image').innerHTML="<img src='loading.gif' width='100px' height='50px'>";
var numi = document.getElementById('theValue');
var num = parseInt(document.getElementById('theValue').value) +1;
numi.value = num;
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
    {	var newdiv=document.createElement("div");
		newdiv.setAttribute('id',num);
		//alert(num);
newdiv.innerHTML=xmlhttp.responseText;	
	document.getElementById('back').appendChild(newdiv);
    document.getElementById('image').innerHTML="";
    }
  }
xmlhttp.open("GET","getnewrow.php",true);
xmlhttp.send();

	
}

function subtotal()
{	//alert("hii");
	var elem = document.getElementsByClassName('qty');
	 var price=document.getElementsByClassName('cprice');
	 //alert(price);
	 var subto=document.getElementsByClassName('subtotal');
	 var sumamt=0; var sumqty=0;
	 for(i=0;i<elem.length;i++)
	 {
		if(elem[i]!=0 || price[i]!=0)
		{
			if(price[i].value=="")
			price[i].value=0;
			
			if(elem[i].value=="")
			elem[i].value=0;
			
			var subtotal=parseInt(elem[i].value)*parseInt(price[i].value);
			subto[i].value=subtotal;
			sumamt=sumamt+subtotal;
			sumqty=sumqty+parseInt(elem[i].value);	
		}	 
	}
	
	    
		document.getElementById('totalamt').value=sumamt;
		document.getElementById('totalqty').value=sumqty;
		document.getElementById('payamt').value=sumamt;
		
		
	var amt=sumamt;
	var type=document.getElementsByClassName('dis');
	var dis=document.getElementById('per').value;
	
	if(type[0].checked)
	{
			disamt=amt*dis/100;
			payamt=Math.round(amt-disamt);
			document.getElementById('distype').value="percentage";
			
	}
	else
	{
		payamt=Math.round(amt-dis);
		document.getElementById('distype').value="Rupees";
			
	}	
	document.getElementById('payamt').value=payamt;
		
}

function calcAmt()
{
	var amt=document.getElementById('totalamt').value;
	var type=document.getElementsByClassName('dis');
	var dis=document.getElementById('per').value;
	
	if(type[0].checked)
	{
			disamt=amt*dis/100;
			payamt=Math.round(amt-disamt);
			document.getElementById('distype').value="percentage";
			
	}
	else
	{
		payamt=Math.round(amt-dis);
		document.getElementById('distype').value="Rupees";
			
	}	
	document.getElementById('payamt').value=payamt;
}
function details()
{	//alert("hii");
	 var elem = document.getElementsByClassName('qty');
	 var cat =  document.getElementsByClassName('item_cat');
	 var bill=document.getElementById('bill_id').value;
	 var supp=document.getElementById('supp_id').value;
	 var bill_date = document.getElementById('bill_date').value;
	 var item_id=document.getElementsByClassName('item_id');
	 var price=document.getElementsByClassName('cprice');
	 var uprice=document.getElementsByClassName('uprice');
	 var item_no=document.getElementsByClassName('item_no');
	 
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
	   
	   if(item_no[i].value==0)
     {
	 alert("Please Click on Item Number Row Number "+(i+1));
	 item_no[i].focus(); 
	 return false; 
	}
	   
      if(item_id[i].value==0)
     {
	 alert("Please Fill Item in Row Number "+(i+1));
	 item_id[i].focus(); 
	 return false; 
	}
   
  
     if(cat[i].value==0)
     {
	 alert("Please select cat in Row Number "+(i+1));
	 cat[i].focus(); 
	 return false; 
	 }
	
  

   if(price[i].value==""||price[i].value==0)
  {
	 alert("Please Enter Cost Price in Row Number "+(i+1));
	 price[i].focus(); 
	 return false; 
	}
	
   if(uprice[i].value==""||uprice[i].value==0)
  {
	 alert("Please Enter Unit Price in Row Number "+(i+1));
	 uprice[i].focus(); 
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
	 
           //alert("hii"+divNum);
           var d = document.getElementById('back');
           //alert(d);

            var olddiv = document.getElementById(divNum);

	      //var num = parseInt(document.getElementById('theValue').value) ;
          //numi.value = num;

            d.removeChild(olddiv);
			
	        subtotal();
        }
		
		
		 function isNumberKey(evt)
      {
         var charCode = (evt.which) ? evt.which : event.keyCode
         if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57))
            return false;

         return true;
      }
</script>

<script>
//1st item ---------------------------
function item_num1(id)
{
     if(id.value==""){
	//alert(id);
	if(document.getElementById('myval').value==""){
	itm = document.getElementById('myval').value=document.getElementById('item_no_1').value
	//alert(itm);	
	        
	       // if(itm=='1A0')item_inff="AAA";
         		//else{
         			        f=itm.substring(0,[1]);
	                                s=itm.substring(1,[2]);
	                                t=itm.substring(2,[3]);
		 			//alert("a="+f);
					//alert("b="+s);
					//alert("c="+t);
		 
         			     if(t=='Z')
		    			{ 
			  			t='A'; 
			  			if(s=='Z')
			  				{ s='A'; fc = f.charCodeAt(0); fc=fc+1; f=String.fromCharCode(fc); } 
			  				else {   sc = s.charCodeAt(0); sc=sc+1; s=String.fromCharCode(sc); }
		    			}
              						
							else {	tc=t.charCodeAt(0); tc=tc+1; t=String.fromCharCode(tc); }
												 
         						item_inff=""+f+s+t;
//alert("a="+item_inff);
				//	}
												
	        		n=id.value=item_inff;
        			id.readOnly = true;
        			id.onClick=false;
	    			k=document.getElementById('myval').value=n;
        //alert("last"+k);
	    }else{
	
	itm1 =  document.getElementById('myval').value
	f=itm1.substring(0,[1]);
	s=itm1.substring(1,[2]);
	t=itm1.substring(2,[3]);	 
         			 if(t=='Z')
		    			{ 
			  			t='A'; 
			  			if(s=='Z')
			  				{ s='A'; fc = f.charCodeAt(0); fc=fc+1; f=String.fromCharCode(fc); } 
			  				else {   sc = s.charCodeAt(0); sc=sc+1; s=String.fromCharCode(sc); }
		    			}
              						
							else { 	tc=t.charCodeAt(0); tc=tc+1; t=String.fromCharCode(tc); }
												 
         						item_inff=""+f+s+t;
	 //alert(item_inff);
	
         	
        n=id.value=item_inff;
        id.readOnly = true;
        id.onClick=false;
	k=document.getElementById('myval').value=n;
        //alert("last"+k);
	//alert(n);
	}
}
	
}	
</script>

<script>
function deleteRow(btn) {
  var row = btn.parentNode.parentNode;
  row.parentNode.removeChild(row);
  subtotal();
}
</script>
<body onLoad="">

 <div style="text-align: center;">
<a href="../../../index.php/purchase">Back</a>
<table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
<tr>
<td align="center"> 
<?php
include('config.php');
$result5=mysql_query("select * from `phppos_app_config`");
$row5 = mysql_fetch_array($result5);
mysql_data_seek($result5,1);
$row6=mysql_fetch_array($result5);
mysql_data_seek($result5,10);
$row7=mysql_fetch_array($result5);
$qryid=mysql_query("Select max(pur_id) from phppos_purchase");
$row1=mysql_fetch_row($qryid);

$pur_id=$row1[0]+1;

//generate item numbers


         $res=mysql_query("select item_number from phppos_items where item_id=(select max(item_id) from phppos_items)");
         $row = mysql_fetch_row($res);
         $itm = $row[0];
         //echo $itm;
         if($itm=='1A0')$item_info="AAA";
         else{
         $f = substr($itm,0,1); 
		 $s = substr($itm,1,1); 
		 $t = substr($itm,2,1);
		 
         if($t=='Z')
		    { 
			  $t='A'; 
			  if($s=='Z')
			  { $s='A'; $fc = ord($f); $fc=$fc+1; $f=chr($fc); } 
			  else { $sc = ord($s); $sc=$sc+1; $s=chr($sc); }
		    }
              else { $tc = ord($t); $tc=$tc+1; $t=chr($tc); }
         $item_info="".$f.$s.$t;
		} 
	


?>

<img src="../reports/bill.png" width="408" height="165"/><br/><br/>
<b>Supplier`s Bill  Entry</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      <?php 
	     //echo $sqlm="select MAX(`bill_id`) from phppos_purchase";
		 $maxbillno=mysql_query("select MAX(`bill_id`) from phppos_purchase");
		 $maxbill=mysql_fetch_row($maxbillno);
		 $maxbillno=array($maxbill);
		 //print_r($maxbill);
		 //echo $maxbill[0];
		?>
      
     <form id="purchse" action="processPurchase.php" method="post" onSubmit="return details();">  
      <table width="100%">
      <tr>
      <td width="25%"> <strong>Purchase Id :</strong> 
        <input type="text" name="pur_id"   id="pur_id" value="<?php echo $pur_id;?>"readonly />  </td>
        
        <td width="25%"><strong>Bill No: </strong>
        <input type="text" name="bill_id"   id="bill_id" autocomplete="off" value="<?php echo $maxbill[0];?>" /> 
        &nbsp;&nbsp;&nbsp;&nbsp;</td><td width="25%"><strong> Supllier Name: </strong>
        <?php
		//echo "select * from phppos_suppliers order by company_name"; 
		$qrysupp=mysql_query("select * from phppos_suppliers order by company_name");?>
        <select id="supp_id" name="supp_id">
        <option  value="0">Select Supplier</option>
        <?php while($ressupp=mysql_fetch_row($qrysupp)){?>
        <option value="<?php echo $ressupp[0]; ?>"><?php echo $ressupp[1]; ?></option>
        <?php } ?>
        </select>
      </td>
      <td width="25%"><strong>Bill Date :</strong>  <input type="text" name="bill_date"   id="bill_date" onClick="displayDatePicker('bill_date');" /> </td>
      </tr>
      </table>
    <hr>
     <?php $qryitem=mysql_query("select distinct category from phppos_items");
	 	$items=array();
		$itemid=array();
		$category=array();
		 while($row=mysql_fetch_row($qryitem))
		 {
				//$items[]=$row[0];
				//$itemid[]=$row[1];
				$category[]=$row[0];
			}
			//print_r($items);
		
	 ?>
     <div id ="targetDiv1"><div></div></div>
     
    <div class="content">
<!--<input type="text" class="search" id="searchid" placeholder="Search for people" /> <br /> -->

   <div id="restul"></div>

</div>
     
   <div id="back">  
     
   
     <input type="hidden" name="theValue" id="theValue" value="5"/>
      <input type="hidden" name="myval" id="myval" value=""/>
   <table> 
   <thead>
   <tr><th>Item Number</th> <th>Item Name</th>  <th>Category</th>  <th>Cost Price</th> <th>Sales Price</th>  <th>Quantity</th>  <th>Amount</th> </tr>
   </thead>
      
    <tbody>
<?php
	for($i=1;$i<6;$i++)
	{
?>
<div id='<?php echo $i; ?>'>
<tr>
	<td><input type="text" name="item_no[]" class="item_no" id="item_no_<?php echo $i; ?>" autocomplete="off" <?php if($i==1){ ?> value="<?php echo $item_info;?>" readonly="readonly" <?php }else{ ?> onClick="item_num1(this)"<?php } ?>></td>    
	<td><input type="text" name="myitemid[]" class="item_id" id="textField_<?php echo $i; ?>" value="" onKeyUp="checkUsername(event);" /> </td>&nbsp;
	<td>
		<select  name="item_cat[]" class="item_cat" id="itemcat_<?php echo $i; ?>">
			<option value="0">Select</option>
			<?php 
			for($j=0;$j< count($category);$j++)
			{
			?>
				<option value="<?php echo $category[$j]; ?>"><?php echo $category[$j]; ?></option>
			<?php
			}
			?>
		</select>
	</td>
	<td><input type="text" name="cprice[]" class="cprice" id="cprice_<?php echo $i; ?>" onChange="subtotal()" value=""  onkeypress="return isNumberKey(event)" autocomplete="off" /> </td> 
	<td><input type="text" name="uprice[]" class="uprice" id="uprice_<?php echo $i; ?>"  value=""   autocomplete="off">&nbsp; </td> 
	<td> <input type="text" name="qty[]" class="qty" id="qty_<?php echo $i; ?>" onChange="subtotal()" onKeyPress="return isNumberKey(event)" value=""  autocomplete="off"></td> 
	<td> <input type="text" name="subtotal[]" class="subtotal" align="right" readonly></td>
	<td><input type="button" value="Remove" onClick="deleteRow(this)"/></td>
</tr>
</div>
<?php
	}
?>
</tbody>
</table>
  
 </div><!-- back div close-->
 

  
  <div id="image" align="center" ></div>
  
  <div align="center"> <br/>Total Quantity : <input type="text" name="totalqty" id="totalqty" value="0" readonly align="right"> &nbsp; Total Amount :  <input type="text" name="totalamt" id="totalamt" value="0" readonly align="right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br/>
  <div align="center">Discount : <input type="radio" name="dis" class="dis" value="1" checked onClick="calcAmt()"> % &nbsp;<input type="radio" name="dis" class="dis" value="0" onClick="calcAmt()">Rs&nbsp; <input type="text" onKeyPress="return isNumberKey(event)" id="per" name="per"  onKeyUp="calcAmt()" value="0" on autocomplete="off">&nbsp; Payable Amount <input type="text" name="payamt" id="payamt" value="0" readonly align="right"></div>
  </div>
	<input type="hidden" name="distype" id="distype">  
     <input type="button" name="btn" onClick="showrow()" value="Add New Row">&nbsp; &nbsp;<input type="submit" name="submit"  value="Submit">
     <br/>
  </form>
      
</center>
</td>
</tr>
</table>
</div>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>