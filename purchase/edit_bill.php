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


function subtotal(cnt)
{	//alert("hi");
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
		document.getElementById('payamts').value=sumamt;
		
		
	var amt=sumamt;
	var type=document.getElementsByClassName('disa');
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
	document.getElementById('payamts').value=payamt;	
		
}

function calcAmt()
{
    //alert("hi")
	var amt=document.getElementById('totalamt').value;
	//alert(amt);
	var type=document.getElementsByClassName('disa');
   //alert(type[0]);
	var dis=document.getElementById('per').value;
	//alert(dis);
    var payamt=0;

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
//alert("hi");
 //alert(payamt);	
	document.getElementById('payamts').value=payamt;
}
/////////////////////////////////////////////////////////////////////////////////////////////
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
$qryid=mysqli_query($con,"Select * from phppos_purchase where `pur_id`='".$_GET['bill_id']."'");
$row1=mysqli_fetch_row($qryid);
if($row1[9]=='percentage')
{$type="%";
	$disamt=round($row1[5]*$row1[7]/100);
}
else
{
$type="rs";
$disamt=$row1[7];
}

//$pur_id=$row1[0]+1;
?>

<img src="../reports/bill.png" width="408" height="165"/><br/><br/>
<b>Supplier`s Bill</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      
      
    
      <table width="50%" height="59">
      <tr>
      <td width="51%"> <strong>Purchase Id :</strong> <?php echo $row1[0];?></td>
      <td width="49%"><strong>Bill No : </strong> <?php echo $row1[1];?>
        
        &nbsp;&nbsp;&nbsp;&nbsp;</td></tr><tr><td width="51%"><strong> Supllier Name: </strong>
        <?php $qrysupp=mysqli_query($con,"select * from `phppos_suppliers` where `person_id`='$row1[2]' ");
        $ressupp=mysqli_fetch_row($qrysupp)?>
        <?php echo $ressupp[1]?>      </td>
      <td width="49%"><strong>Bill Date :</strong>  <?php echo $row1[3];?> </td>
      </tr>
      </table>
    <hr>
     <?php $qryfetch=mysqli_query($con,"select * from `phppos_purchase_details` where `pur_id`='$row1[0]'");
	 
	  		$i=0;
			$total=0;
			$qty=0;
			
	 $qryitem=mysqli_query($con,"select distinct category from phppos_items");
	 	$items=array();
		$itemid=array();
		$category=array();
		 while($row=mysqli_fetch_row($qryitem))
		 {
				//$items[]=$row[0];
				//$itemid[]=$row[1];
				$category[]=$row[0];
			}
			//print_r($items);
		
	 ?>
     <div id="back">
     <form action="process_edit.php " method="post" id="purchse" onSubmit="return details();" >
     <table width="70%" border='1'>
      <tr>
      <th width="10%">Sr. No</th>
      <th width="30%">Item Name</th>
      <th width="30%">Categery</th>
      <th width="15%">Cost Price</th>
      <th width="15%">Sales Price</th>
      <th width="20%">Quantity</th>
      <th width="25%">Total Amount</th>
      
      </tr>
     <?php while($resfetch=mysqli_fetch_row($qryfetch)){
		 $i=++$i ;
		 ?>
     <tr>
     
	
     <?php $itemqry=mysqli_query($con,"SELECT `name`,`unit_price`,`category`,item_id from `phppos_items` where `item_id`='$resfetch[2]'"); 
     $item=mysqli_fetch_row($itemqry);?>  
     
 <td align="center"> 
       <input type="hidden" name="itmai_id[]" id="itmai_id" value="<?php echo $item[3] ;?>" />
      <input type="hidden" name="purai_id[]" id="purai_id" value="<?php echo $resfetch[0]  ;?>"/>
         <?php echo $i;?></td>   <!--srno-->
 <td align="justify"><input type="text" name="item[]" id="item<?php echo $i; ?>" value="<?php echo $item[0]; ?>" /></td>  <!--Item Name-->
 <td align="justify"><!--Categery-->
        <select  name="item_cat[]" class="item_cat">
       <option value="<?php echo $item[2]; ?>"><?php echo $item[2]; ?></option>
       
      <?php 
	   
	   for($j=0;$j< count($category);$j++){
		   ?><option value="<?php echo $category[$j]; ?>"><?php echo $category[$j]; ?></option><?php
	   
	   }
	   ?>
    
    </select>
 <td align="right"><input type="text" name="cprice[]" class="cprice" id="cprice<?php echo $i; ?>" value="<?php echo $resfetch[4];  ?>" onkeypress="return isNumberKey(event)"  onBlur="subtotal('<?php echo $i; ?>');"  /></td>   <!--Cost Price-->
 
 <td align="right"><input type="text" name="sprice[]" id="sprice<?php echo $i; ?>" class="sprice" value="<?php echo $item[1]; ?>" /></td>     <!--sales price-->
 
 <td align="right"><input type="text" name="qty[]" id="qty<?php echo $i; ?>" class="qty" value="<?php echo $resfetch[3];$qty+=$resfetch[3]; ?>" onkeypress="return isNumberKey(event)"  onBlur="subtotal('<?php echo $i; ?>');" /></td> <!--quantity-->
 
 <td align="right"><input type="text" name="subtotal[]" class="subtotal" id="subtotal<?php echo $i; ?>" value="<?php echo $res=($resfetch[3]*$resfetch[4]); $total+=$res; ?>" /></td><!--total amount-->
     
	 
     </tr>
     <?php }?>
     
	 <tr>
     <td colspan='5' align='right'> Total :</td>
     <td align='right' ><input type="text" name="totalqty" id="totalqty" readonly="readonly" value="<?php echo $qty; ?>" /> </td> <!-- Quantity-->
     <td align='right'><input type="text" name="totalamt" id="totalamt" readonly="readonly" value="<?php echo $total; ?>" /></td>  <!-- total amount-->  
     </tr>
     
	 <tr>
     <td colspan='5' align='right'> 
    <!--  <input type="text" name="disto" id="disto"  class="disto"  value="<?php echo $row1[9]; ?>" />-->
     Discount :
     
     
     
     <input type="radio" name="disa" class="disa" id="disa" value="0" <?php if($row1[9]=="percentage")echo "checked"; ?> onClick="calcAmt()"   /> %
	
     <input type="radio" name="disa" class="disa" id="disa" value="1" <?php if($row1[9]=="Rupees")echo "checked"; ?> onClick="calcAmt()"   />Rs
    
     </td>
     
	<td> 
    <input type="text" onkeypress="return isNumberKey(event)" id="per" name="per"  onKeyUp="calcAmt()" value="<?php echo $row1[7]; ?>" on autocomplete="off"> 
    </td>  <!-- Discount in %--> 	  
	 <td align='right'> </td><!-- Discount-->
     </tr>
      
	 <tr>
     <td colspan='6' align='right'> Payable Total :</td>
     <td align='right'>
     <input type="hidden" name="distype" id="distype"> 
     <input type="text" name="payamts" id="payamts" readonly="readonly" value="<?php echo $row1[8]; ?>" /></td> <!-- Total payable amount-->
     </tr>
	
     
     <tr><td colspan="7" align="center">
     
     <input type="hidden" name="supp_id" id"supp_id" value="<?php echo $row1[0]; ?>" />
     <input type="submit" value="Submit" onClick=""> </td></tr>
     </table>
     
     </form>
    </div>
  
     </center>
</td>
</tr>
</table>
</div>

<div align="center">You are using Point Of Sale Version 10.5 .</div>
<?php CloseCon($con);?>
</body>