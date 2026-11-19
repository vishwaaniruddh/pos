<script type="text/javascript" src="datepicker/datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="datepicker/date_css.css"  />
<script>
/*var isCtrl = false;
document.onkeyup=function(e){
	if(e.which == 17) isCtrl=false;
}
document.onkeydown=function(e){
	if(e.which == 17) isCtrl=true;
	if(e.which == 66 && isCtrl == true) {
		document.getElementById("barcode").focus(); 
		return false;
	}
	
}*/
function showbal()
{
	//alert("hii bal");
	//document.getElementById('image').innerHTML="<img src='loading.gif' width='100px' height='50px'>";
		 var bank_id=document.getElementById('bank_id').value;
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
		
	document.getElementById('bal').innerHTML=" Balance Amount : "+xmlhttp.responseText;
    document.getElementById('balance').value=xmlhttp.responseText;
	showod();
    }
  }
xmlhttp.open("GET","getbalance.php?bank_id="+bank_id,true);
xmlhttp.send();

	
}
function showod()
{
	//alert("hii");
	//document.getElementById('image').innerHTML="<img src='loading.gif' width='100px' height='50px'>";
		 var bank_id=document.getElementById('bank_id').value;
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
		if(xmlhttp.responseText>0)
		document.getElementById('bal').innerHTML+=" <br/>OverDraft Limit : "+xmlhttp.responseText;
    document.getElementById('od').value=xmlhttp.responseText;
    }
  }
xmlhttp.open("GET","getod.php?bank_id="+bank_id,true);
xmlhttp.send();

	
}

/*
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
			var subtotal=parseInt(elem[i].value)*parseInt(price[i].value);
			subto[i].value=subtotal;
			sumamt=sumamt+subtotal;
			sumqty=sumqty+parseInt(elem[i].value);	
		}	 
	}
		document.getElementById('totalamt').value=sumamt;
		document.getElementById('totalqty').value=sumqty;
}
*/
function details()
{	//alert("hii");
	
	 var bank_id=document.getElementById('bank_id').value;
	 var trans_type=document.getElementById('trans_type').value;
	 
	 var amt =0;
	 var cnt=document.getElementById('count').value;
	// alert(cnt);
	 for(var i=0;i<=cnt;i++)
	 {
	 //alert(document.getElementById('amt'+i).value);
	 amt = (Number(amt)+Number(document.getElementById('amt'+i).value));
	 //alert(amt);
	 }
	 //alert(cnt);
	 var bal = document.getElementById('balance').value;
	  var od = document.getElementById('od').value;
	 var bank_id1=document.getElementById('bank_id1').value;
  if(trans_type==0)
  {
	 alert("Please Select Transaction Type");
	 document.getElementById('trans_type').focus(); 
	 return false; 
	}
  if(bank_id==0)
  {
	 alert("Please Select Bank.");
	 document.getElementById('bank_id').focus(); 
	 return false; 
	}
	if(trans_type=="banktrans" && bank_id1==0)
  {
	  alert("Please Select Bank for Transfer.");
	 document.getElementById('bank_id1').focus(); 
	 return false;
	 
	}
  if(amt=="" || amt=='0')
  {
	 alert("Please Enter Amount");
	 //document.getElementById('amt').focus(); 
	 return false; 
	}
	//alert(trans_type+bal+" "+amt);
	if((trans_type=="payment" || trans_type=="banktrans") && (parseInt(amt)>(parseInt(bal)+parseInt(od)))){
		alert("Not Sufficient Balance Fullfill this Transaction");
		//document.getElementById('amt').focus(); 
		return false;
	}
    
	var pr=confirm("Do You Really Want to Make Transaction ?")
	if(pr==false)
	{return false;}
}//end of function show
//
function showbank()
{
	var trans_type=document.getElementById('trans_type').value;	
	//alert("hii");
	if(trans_type=="banktrans")
	{
		document.getElementById('tbank').style.display="block";	
		document.getElementById('tbank1').style.display="block";	
	}
	else
	{
		document.getElementById('tbank').style.display="none";		
		document.getElementById('tbank1').style.display="none";	
	}
}

function showrow()
{
	//alert("hii");
var num=Number(document.getElementById('count').value)+1;
//alert(num);
//	document.getElementById('image').innerHTML="<img src='loading.gif' width='100px' height='50px'>";
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
	document.getElementById('ro').innerHTML=document.getElementById('ro').innerHTML+' '+xmlhttp.responseText;
	document.getElementById('count').value=num;
	
    }
  }
  //alert("getamtrow.php?cnt="+num);
xmlhttp.open("GET","getamtrow.php?cnt="+num,true);
xmlhttp.send();

	
}
/*//remove div
	 function removeElement(divNum) {
	 
          // alert("hii"+divNum);
		    var d = document.getElementById('back');
            var olddiv = document.getElementById(divNum);
			//var num = parseInt(document.getElementById('theValue').value) ;
//numi.value = num;
               d.removeChild(olddiv);
			   subtotal();
        }*/
</script>
<body onLoad="">

<div style="text-align: center;">
<a href="/pos/home_dashboard.php">Back</a>
<table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
<tr>
<td align="center"> 
<?php
// include('config.php');
include('../../db_connection.php') ;
$con=OpenSrishringarrCon();


$result5=mysqli_query($con,"select * from `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($con,$result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($con,$result5,10);
$row7=mysqli_fetch_array($result5);
?>

<img src="../reports/bill.png" width="408" height="165"/><br/><br/>
<b>*** Bank Entry ***</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      
      
     <form id="purchse" action="processbanktrans.php" method="post" onSubmit="return details();">  
     <hr>
     <?php $qryitem=mysqli_query($con,"select bank_name, bank_id from banks");
	 	//$items=array();
		
		
			//print_r($items);
		
	 ?>
     <div id="back">
     <table>
     <tr><td> Transaction Type : </td><td><select name="trans_type" id="trans_type" onChange="showbank()"><option value="0">Select type</option>
        <option value='payment'>payment</option>
        <option value='receit'>Receit </option>
        <option value='banktrans'>Bank Transfer</option>
			</select></td></tr>    
       <tr><td> Transaction Date</td><td><input type="text" name="transdate" id="transdate" onClick="displayDatePicker('transdate')" value="<?php echo date('d/m/Y',strtotime('today')); ?>"></td></tr> 
     <tr><td valign="top">Bank Name : </td><td><select name="bank_id" id="bank_id" onChange="showbal(); "><option value="0">Select Name</option><?php  while($row=mysqli_fetch_row($qryitem))
		 {
				echo "<option value='".$row[1]."'>".$row[0]."</option>";
			}?></select><br><a href="newbank.php"> Add New Bank Account</a> <div id="bal"></div>
            <input type="hidden" name="balance" id="balance"/> <input type="hidden" name="od" id="od"/>
            </td></tr>
           <?php $qryitem1=mysqli_query($con,"select bank_name, bank_id from banks");?>
           
           <tr ><td style="display:none" id="tbank">Transfer To Bank </td><td style="display:none" id="tbank1"><select name="bank_id1" id="bank_id1"> <option value="0">Select Name</option><?php  while($row1=mysqli_fetch_row($qryitem1))
		 {
				echo "<option value='".$row1[1]."'>".$row1[0]."</option>";
			}?></select></td></tr>
            
            <?php
			$cnt=0;
			for($i=0;$i<3;$i++)
			{
			if($i>0)
			$cnt=$cnt+1;
			?>
            <tr><td>Amount : </td><td><input type="text" name="amt[<?php echo $i; ?>]" id="amt<?php echo $i; ?>" placeholder="Enter Amount" /></td><td> Memo </td><td><textarea id="memo<?php echo $i; ?>" name="memo[<?php echo $i; ?>]" placeholder="Enter Transaction Memo"></textarea></td></tr>
            <?php
			}
			?>
            <tr><td colspan="4" align="center" id="ro">
            
          
            </td></tr>
            <tr><td colspan="4" align="center" id="ro">
            
            <input type="button" name="btn" onClick="showrow()" value="Add New Row"><input type="hidden" name="count" id="count" value="<?php echo $cnt; ?>">
            </td></tr>
            </table>
        </div>
    <input type="submit" name="submit"  value="Make Transaction">&nbsp; &nbsp;<a href="../../../index.php/purchase"><input type="button" name="cancel"  value="Cancel Transaction"></a>
     <br/>
  </form>
      
</center>
</td>
</tr>
</table>
</div>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>