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
			var subtotal=parseInt(elem[i].value)*parseInt(price[i].value);
			subto[i].value=subtotal;
			sumamt=sumamt+subtotal;
			sumqty=sumqty+parseInt(elem[i].value);	
		}	 
	}
		document.getElementById('totalamt').value=sumamt;
		document.getElementById('totalqty').value=sumqty;
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
	 
	  		$i=1;
			$total=0;
			$qty=0;
			
	 ?>
     <div id="back">
     <table width="70%" border='1'>
      <tr>
      <th width="10%">Sr. No</th>
      <th width="30%">Item Name</th>
      <th width="15%">Price</th>
      <th width="15%">Sales Price</th>
      <th width="20%">Quantity</th>
      <th width="25%">Total Amount</th>
      </tr>
     <?php while($resfetch=mysqli_fetch_row($qryfetch)){?>
     <tr>
     <td align="center"><?php echo $i++;?></td><td align="justify">
     <?php $itemqry=mysqli_query($con,"SELECT `name`,`unit_price` from `phppos_items` where `item_id`='$resfetch[2]'"); 
     $item=mysqli_fetch_row($itemqry);
     
     
	 echo $item[0];//Item Name?></td><td align="right"><?php echo $resfetch[4];//Price ?></td><td align="right"><?php echo $item[1];;//sales price?></td><td align="right"><?php echo $resfetch[3];$qty+=$resfetch[3]//quantity?></td><td align="right"><?php echo $res=($resfetch[3]*$resfetch[4]); $total+=$res;?>
	 </td>
     </tr>
     <?php }
	 echo "<tr><td colspan='3' align='right'> Total :</td><td align='right'>".$qty."</td><td align='right'>".$total."</td></tr>";
	 echo "<tr><td colspan='4' align='right'> Discount ";
	 if($type=="%")
	 echo " ".$row1[7]."%";
	 echo "</td><td align='right'>".$disamt."</td></tr>";
	 echo "<tr><td colspan='4' align='right'> Payable Total :</td><td align='right'>".$row1[8]."</td></tr>";
	 ?></table>
    </div>
  
     </center>
</td>
</tr>
</table>
</div>
<?php CloseCon($con);?>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>