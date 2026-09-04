<script type="text/javascript" src="datepicker/datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="datepicker/date_css.css"  />
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
		
var numi = document.getElementById('theValue');
var num = parseInt(document.getElementById('theValue').value) +1;
numi.value = num;

		var newdiv=document.createElement("div");
		newdiv.setAttribute('id',num);
		//alert(num);
	newdiv.innerHTML=xmlhttp.responseText+"<input type='button' value='Remove' onClick='removeElement("+num+")'></div>";
	
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
	 var price=document.getElementsByClassName('price');
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
	 var bill=document.getElementById('bill_id').value;
	 var supp=document.getElementById('supp_id').value;
	 var bill_date = document.getElementById('bill_date').value;
	 var item_id=document.getElementsByClassName('item_id');
	 var price=document.getElementsByClassName('price');
	 
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
      if(item_id[i].value==0)
  {
	 alert("Please Select Item in Row Number "+(i+1));
	 item_id[i].focus(); 
	 return false; 
	}
   if(price[i].value==""||price[i].value==0)
  {
	 alert("Please enter Price in Row Number "+(i+1));
	 price[i].focus(); 
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
	 
          // alert("hii"+divNum);
		    var d = document.getElementById('back');
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
<body onLoad="">

 <div style="text-align: center;">
<a href="../../../index.php/purchase">Back</a>
<table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
<tr>
<td align="center"> 
<?php
// include('config.php');
include('../../db_connection.php') ;
$con=OpenSrishringarrCon();


$result5=mysqli_query($con,"select * from `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($result5,10);
$row7=mysqli_fetch_array($result5);
$qryid=mysqli_query($con,"Select max(pur_id) from phppos_purchase");
$row1=mysqli_fetch_row($qryid);

$pur_id=$row1[0]+1;
?>

<img src="../reports/bill.png" width="408" height="165"/><br/><br/>
<b>Supplier`s Bill  Entry</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      
      
     <form id="purchse" action="processPurchase.php" method="post" onSubmit="return details();">  
      <table width="100%">
      <tr>
      <td width="25%"> <strong>Purchase Id :</strong>        <input type="text" name="pur_id"   id="pur_id" value="<?php echo $pur_id;?>"readonly />  </td>
      <td width="25%"><strong>Bill No: </strong>
        <input type="text" name="bill_id"   id="bill_id" autocomplete="off" /> 
        &nbsp;&nbsp;&nbsp;&nbsp;</td><td width="25%"><strong> Supllier Name: </strong>
        <?php $qrysupp=mysqli_query($con,"select * from phppos_suppliers order by company_name");?>
        <select id="supp_id" name="supp_id"><option  value="0">Select Supplier</option>
        <?php while($ressupp=mysqli_fetch_row($qrysupp)){?>
        <option value="<?php echo $ressupp[0]?>"><?php echo $ressupp[1]?></option>
        <?php } ?>
        </select>
      </td>
      <td width="25%"><strong>Bill Date :</strong>        <input type="text" name="bill_date"   id="bill_date" onClick="displayDatePicker('bill_date');" /> </td>
      </tr>
      </table>
    <hr>
     <?php $qryitem=mysqli_query($con,"select name, item_id from phppos_items");
	 	$items=array();
		$itemid=array();
		
		 while($row=mysqli_fetch_row($qryitem))
		 {
				$items[]=$row[0];
				$itemid[]=$row[1];
			}
			//print_r($items);
		
	 ?>
     <div id="back">
     <input type="hidden" name="theValue" id="theValue" value="5"/>
     
     <div id='1'>Item Name : <select name="item_id[]" class="item_id"><option value="0">Select Item</option><?php for($i=0;$i<count($items);$i++){ echo "<option value='".$itemid[$i]."'>".$items[$i]."</option>";}?></select> &nbsp;  Price : <input type="text" name="price[]" class="price" onChange="subtotal()" value="0" align="right" onkeypress="return isNumberKey(event)" autocomplete="off">&nbsp; Quantity : <input type="text" name="qty[]" id="qty" class="qty" onChange="subtotal()"  onkeypress="return isNumberKey(event)" value="0" align="right" autocomplete="off"> Amount : <input type="text" name="subtotal[]" class="subtotal" align="right" readonly><input type="button" value="Remove" onClick="removeElement('1')"></div>
     
   <div id='2'>Item Name : <select name="item_id[]" class="item_id"><option value="0">Select Item</option><?php for($i=0;$i<count($items);$i++){ echo "<option value='".$itemid[$i]."'>".$items[$i]."</option>";}?></select> &nbsp; Price : <input type="text" name="price[]" align="right"  onkeypress="return isNumberKey(event)" onChange="subtotal()" value="0" class="price" autocomplete="off">&nbsp; Quantity : <input type="text" align="right" name="qty[]" id="qty" class="qty" value="0"  onkeypress="return isNumberKey(event)" onChange="subtotal()" autocomplete="off"> Amount : <input type="text" name="subtotal[]" align="right" class="subtotal" readonly><input type="button" value="Remove" onClick="removeElement('2')"></div>
   
   <div id='3'>Item Name : <select name="item_id[]" class="item_id"><option value="0">Select Item</option><?php for($i=0;$i<count($items);$i++){ echo "<option value='".$itemid[$i]."'>".$items[$i]."</option>";}?></select> &nbsp; Price : <input type="text" align="right" value="0"  onkeypress="return isNumberKey(event)" name="price[]" onChange="subtotal()" class="price" autocomplete="off">&nbsp; Quantity : <input type="text" align="right" name="qty[]" id="qty" class="qty" value="0" onkeypress="return isNumberKey(event)" onChange="subtotal()" autocomplete="off"> Amount : <input type="text" name="subtotal[]" align="right" class="subtotal" readonly><input type="button" value="Remove" onClick="removeElement('3')"></div>
  
   <div id='4'>Item Name : <select name="item_id[]" class="item_id"><option value="0">Select Item</option><?php for($i=0;$i<count($items);$i++){ echo "<option value='".$itemid[$i]."'>".$items[$i]."</option>";}?></select> &nbsp; Price : <input type="text" align="right" onkeypress="return isNumberKey(event)" name="price[]" value="0" onChange="subtotal()" class="price" autocomplete="off">&nbsp; Quantity : <input type="text" name="qty[]" id="qty" align="right" class="qty" value="0" onkeypress="return isNumberKey(event)" onChange="subtotal()" autocomplete="off"> Amount : <input type="text" name="subtotal[]" class="subtotal" align="right" readonly><input type="button" value="Remove" onClick="removeElement('4')"></div>
  
   <div id='5'>Item Name : <select name="item_id[]" class="item_id"><option value="0">Select Item</option><?php for($i=0;$i<count($items);$i++){ echo "<option value='".$itemid[$i]."'>".$items[$i]."</option>";}?></select> &nbsp; Price : <input type="text" align="right"  onkeypress="return isNumberKey(event)" name="price[]" onChange="subtotal()" value="0" class="price" autocomplete="off">&nbsp; Quantity : <input type="text" name="qty[]" align="right" id="qty" class="qty" value="0" onkeypress="return isNumberKey(event)" onChange="subtotal()" autocomplete="off"> Amount : <input type="text" name="subtotal[]" class="subtotal" align="right" readonly><input type="button" value="Remove" onClick="removeElement('5')"></div>
  
  </div>
  <div id="image" align="center" ></div>
  <div align="center"> <br/>Total Quantity : <input type="text" name="totalqty" id="totalqty" value="0" readonly align="right"> &nbsp; Total Amount :  <input type="text" name="totalamt" id="totalamt" value="0" readonly align="right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br/>
  <div align="center">Discount : <input type="radio" name="dis" class="dis" value="1" checked onClick="calcAmt()"> % &nbsp;<input type="radio" name="dis" class="dis" value="0" onClick="calcAmt()">Rs&nbsp; <input type="text" onkeypress="return isNumberKey(event)" id="per" name="per"  onKeyUp="calcAmt()" value="0" on autocomplete="off">&nbsp; Payable Amount <input type="text" name="payamt" id="payamt" value="0" readonly align="right"></div>
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
<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>