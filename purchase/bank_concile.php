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
function showtrans()
{
	//alert("hii");
	
		 var bank_id=document.getElementById('bank_id').value;
		 var frmdate=document.getElementById('frmdate').value;
		 var todate=document.getElementById('todate').value;
		 if(bank_id==0)
		 {
			alert("Please Select Bank.");
		 	document.getElementById('bank_id').focus(); 
	 		return false;  
			}
		document.getElementById('back').innerHTML="<img src='loading.gif' width='150px' height='100px'>";	
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
	document.getElementById('back').innerHTML=xmlhttp.responseText;
    //document.getElementById('image').innerHTML="";
    }
  }
xmlhttp.open("GET","gettrans1.php?bank_id="+bank_id+"&frmdate="+frmdate+"&todate="+todate,true);
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
/*function details()
{	//alert("hii");
	
	 var bank_id=document.getElementById('bank_id').value;
	 var trans_type=document.getElementById('trans_type').value;
	 var amt = document.getElementById('amt').value;
	 var bal = document.getElementById('balance').value;
  if(bank_id==0)
  {
	 alert("Please Select Bank.");
	 document.getElementById('bank_id').focus(); 
	 return false; 
	}
  if(trans_type==0)
  {
	 alert("Please Select Transaction Type");
	 document.getElementById('trans_type').focus(); 
	 return false; 
	}
  if(amt=="")
  {
	 alert("Please Enter Amount");
	 document.getElementById('amt').focus(); 
	 return false; 
	}
	//alert(trans_type+bal+" "+amt);
	if(trans_type=="payment" && (parseInt(amt)>parseInt(bal))){
		alert("Not Sufficient Balance Fullfill this Transaction");
		document.getElementById('amt').focus(); 
		return false;
	}
    
	var pr=confirm("Do You Really Want to Make Transaction ?")
	if(pr==false)
	{return false;}
}//end of function show
//*/
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
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$result5=mysqli_query($con,"select * from `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($result5,10);
$row7=mysqli_fetch_array($result5);

	CloseCon($con);
?>

<img src="../reports/bill.png" width="408" height="165"/><br/><br/>
<b>*** Reconcile Bank Statement ***</b>
</td></tr>

<tr>
<td width="1308"  valign="top"><center>
      
      
     <form id="purchse" action="processConcile.php" method="post" onSubmit="return details();">  
     <hr>
     <?php $qryitem=mysqli_query($con,"select bank_name, bank_id from banks");
	 	$items=array();
		
		
			//print_r($items);
		
	 ?>
     
     <table width="70%"><tr><td width="121">Bank Name : </td><td width="129"><select name="bank_id" id="bank_id" onChange="showbal();"><option value="0">Select Name</option><?php  while($row=mysql_fetch_row($qryitem))
		 {
				echo "<option value='".$row[1]."'>".$row[0]."</option>";
			}?></select></td><td width="280"> Date From : <input type="text" name="frmdate" id="frmdate" onClick="displayDatePicker('frmdate');"></td><td width="203"> To :<input type="text" name="todate" id="todate" onClick="displayDatePicker('todate');"></td><td width="159"><input type="button" name="btn"  value="Show Transactions" onClick="showtrans()"></td></tr>
            </table>
      <div id="back">  </div>
     <br/>
  </form>
      
</center>
</td>
</tr>
</table>
</div>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>