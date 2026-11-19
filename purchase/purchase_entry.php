<script type="text/javascript" src="datepicker/datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="datepicker/date_css.css" />
<script type="text/javascript" src="jquery-1.11.1.min.js"></script>
<script>
	function checkUsername(keyEvent1) {
		//alert("hi");
		keyEvent1 = (keyEvent1) ? keyEvent1 : window.event;
		input = (keyEvent1.target) ? keyEvent1.target : keyEvent1.srcElement;
		if (keyEvent1.type == "keyup") {
			var targetDiv = document.getElementById("targetDiv1");
			targetDiv.innerHTML = "<div></div>";
			var targetDiv1 = document.getElementById("restul");
			targetDiv1.innerHTML = "<div></div>";
			if (input.value) {
				//alert(input.value);
				getData("itemsearch.php?qu=" + input.value), getDataitem("search.php?searchdata=" + input.value);
			}
		}
	}

	function getData(dataSource) {
		var XMLHttpRequestObject = false;
		if (window.XMLHttpRequest) {
			XMLHttpRequestObject = new XMLHttpRequest();
		} else if (window.ActiveXObject) {
			XMLHttpRequestObject = new ActiveXObject("Microsoft.XMLHttp");
		}
		if (XMLHttpRequestObject) {
			XMLHttpRequestObject.open("GET", dataSource);
			XMLHttpRequestObject.onreadystatechange = function() {
				if (XMLHttpRequestObject.readyState == 4 && XMLHttpRequestObject.status == 200) {
					//alert("result= "+XMLHttpRequestObject.responseText);
					if (XMLHttpRequestObject.responseText == "taken") {
						document.getElementById("targetDiv1").innerHTML = "<div style='color:red; font-weight:bold;'>This Item Name already exists and this will create batch code for it.</div>";
					} else {
						document.getElementById("targetDiv1").innerHTML = "<div style='color:red; font-weight:bold'>This Item Name  does not exists.</div>";
					}
				}
			}
			XMLHttpRequestObject.send(null);
		}
	}
	//////////////////////////////////////////////
	function getDataitem(dataSource) {
		var XMLHttpRequestObject = false;
		if (window.XMLHttpRequest) {
			XMLHttpRequestObject = new XMLHttpRequest();
		} else if (window.ActiveXObject) {
			XMLHttpRequestObject = new ActiveXObject("Microsoft.XMLHttp");
		}
		//alert("hia");
		if (XMLHttpRequestObject) {
			XMLHttpRequestObject.open("GET", dataSource);
			XMLHttpRequestObject.onreadystatechange = function() {
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
	document.onkeyup = function(e) {
		if (e.which == 17) isCtrl = false;
	}
	document.onkeydown = function(e) {
		if (e.which == 17) isCtrl = true;
		if (e.which == 66 && isCtrl == true) {
			document.getElementById("barcode").focus();
			return false;
		}
	}

	function showrow() {
		var cnt = document.getElementById("rowcnt").value;
		document.getElementById('image').innerHTML = "<img src='loading.gif' width='100px' height='50px'>";
		// alert(cnt);
		document.getElementById("rowcnt").value = parseInt(cnt) + 1;
		$.ajax({
			type: "POST",
			url: "getnewrow.php", //page
			data: "cnt=" + cnt, //var send to page
			cache: false,
			success: function(msg) {
				var numi = document.getElementById('theValue');
				var num = parseInt(document.getElementById('theValue').value) + 1;
				numi.value = num;
				var newdiv = document.createElement("tr");
				newdiv.setAttribute('id', num);
				//alert(num);
				newdiv.innerHTML = msg + '<td style="width:12%"><input type="button" value="Remove" onClick="removeElement(' + num + ')"><td></tr>';
				document.getElementById('back').appendChild(newdiv);
				document.getElementById('image').innerHTML = "";
			}
		});
	}

	function showrowxx() {
		//alert("hii");
		var cnt = document.getElementById("rowcnt").value;
		document.getElementById('image').innerHTML = "<img src='loading.gif' width='100px' height='50px'>";
		if (window.XMLHttpRequest) { // code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		} else { // code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				var numi = document.getElementById('theValue');
				var num = parseInt(document.getElementById('theValue').value) + 1;
				numi.value = num;
				var newdiv = document.createElement("tr");
				newdiv.setAttribute('id', num);
				//alert(num);
				newdiv.innerHTML = xmlhttp.responseText + '<td style="width:12%"><input type="button" value="Remove" onClick="removeElement(' + num + ')"><td></tr>';
				document.getElementById('back').appendChild(newdiv);
				document.getElementById('image').innerHTML = "";
				document.getElementById("rowcnt").value = parseInt(cnt) + 1;
			}
		}
		xmlhttp.open("GET", "getnewrow.php?cnt=" + cnt, true);
		xmlhttp.send();
	}

	function subtotal() { //alert("hii");
		var elem = document.getElementsByClassName('qty');
		var price = document.getElementsByClassName('cprice');
		//alert(price);
		var subto = document.getElementsByClassName('subtotal');
		var sumamt = 0;
		var sumqty = 0;
		for (i = 0; i < elem.length; i++) {
			if (elem[i] != 0 || price[i] != 0) {
				if (price[i].value == "") price[i].value = 0;
				if (elem[i].value == "") elem[i].value = 0;
				var subtotal = parseInt(elem[i].value) * parseInt(price[i].value);
				subto[i].value = subtotal;
				sumamt = sumamt + subtotal;
				sumqty = sumqty + parseInt(elem[i].value);
			}
		}
		document.getElementById('totalamt').value = sumamt;
		document.getElementById('totalqty').value = sumqty;
		document.getElementById('payamt').value = sumamt;
		var amt = sumamt;
		var type = document.getElementsByClassName('dis');
		var dis = document.getElementById('per').value;
		if (type[0].checked) {
			disamt = amt * dis / 100;
			payamt = Math.round(amt - disamt);
			document.getElementById('distype').value = "percentage";
		} else {
			payamt = Math.round(amt - dis);
			document.getElementById('distype').value = "Rupees";
		}
		document.getElementById('payamt').value = payamt;
	}

	function calcAmt() {
		var amt = document.getElementById('totalamt').value;
		var type = document.getElementsByClassName('dis');
		var dis = document.getElementById('per').value;
		if (type[0].checked) {
			disamt = amt * dis / 100;
			payamt = Math.round(amt - disamt);
			document.getElementById('distype').value = "percentage";
		} else {
			payamt = Math.round(amt - dis);
			document.getElementById('distype').value = "Rupees";
		}
		document.getElementById('payamt').value = payamt;
	}

	function details() { //alert("hii");
		var elem = document.getElementsByClassName('qty');
		var cat = document.getElementsByClassName('item_cat');
		var bill = document.getElementById('bill_id').value;
		var supp = document.getElementById('supp_id').value;
		var bill_date = document.getElementById('bill_date').value;
		var item_id = document.getElementsByClassName('item_id');
		var price = document.getElementsByClassName('cprice');
		var uprice = document.getElementsByClassName('uprice');
		var item_no = document.getElementsByClassName('item_no');
		//var names = [];
		// alert(bill+supp+bill_date);
		if (bill == "") {
			alert("Please Enter Bill Number");
			document.getElementById('bill_id').focus();
			return false;
		}
		if (supp == 0) {
			alert("Please Select Supplier");
			document.getElementById('supp_id').focus();
			return false;
		}
		if (bill_date == "") {
			alert("Please Enter Bill Date");
			document.getElementById('bill_date').focus();
			return false;
		}
		for (i = 0; i < elem.length; i++) {
			if (item_no[i].value == 0) {
				alert("Please Click on Item Number Row Number " + (i + 1));
				item_no[i].focus();
				return false;
			}
			if (item_id[i].value == 0) {
				alert("Please Fill Item in Row Number " + (i + 1));
				item_id[i].focus();
				return false;
			}
			if (cat[i].value == 0) {
				alert("Please select cat in Row Number " + (i + 1));
				cat[i].focus();
				return false;
			}
			if (price[i].value == "" || price[i].value == 0) {
				alert("Please Enter Cost Price in Row Number " + (i + 1));
				price[i].focus();
				return false;
			}
			if (uprice[i].value == "" || uprice[i].value == 0) {
				alert("Please Enter Unit Price in Row Number " + (i + 1));
				uprice[i].focus();
				return false;
			}
			if (elem[i].value == "" || elem[i].value == 0) {
				alert("Please Enter Qty in Row Number " + (i + 1));
				qty[i].focus();
				return false;
			}
		} //for loop
	} //end of function show
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

	function isNumberKey(evt) {
		var charCode = (evt.which) ? evt.which : event.keyCode
		if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) return false;
		return true;
	}
</script>
<script>
	//1st item ---------------------------
	function item_num1(id) {
		try {
			// alert(id);  
			var thenum = id.replace(/^\D+/g, '');
			// alert(thenum);
			var elmnt = document.getElementById("item_no" + thenum);
			var val = document.getElementById("textField" + thenum).value;
			if (val != "") {
				//alert(val);
				$.ajax({
					type: "POST",
					url: "getmaxitemnumberdb.php", //page
					data: "nm=" + val, //var send to page
					cache: false,
					success: function(msg) {
						//alert(msg);
						var jsdr = JSON.parse(msg);
						//      alert(jsdr["numrows"]);
						if (jsdr["numrows"] != "0") {
							//alert(jsdr["item_num"]);
							elmnt.value = jsdr["item_num"];
							document.getElementById("item_cat" + thenum).value = jsdr["category"];
							document.getElementById("cprice" + thenum).value = jsdr["costprice"];
							document.getElementById("uprice" + thenum).value = jsdr["unitprice"];
							document.getElementById("qty" + thenum).value = jsdr["qty"];
							subtotal();
						} else {
							elmnt.value = jsdr["item_num"];
							item_num1xx(elmnt, jsdr["item_num"]);
						}
					}
				});
			}
		} catch (ex) {
			alert(ex);
		}
	}

function item_num1xx(id, itmn) {
	// Initialize the value if empty
	if (document.getElementById('myval').value == "") {
		itm = document.getElementById('myval').value = itmn;
	} else {
		itm = document.getElementById('myval').value;
	}

	// Extract characters: allow 3 or 4 characters based on length
	f = itm.substring(0, 1);
	s = itm.substring(1, 2);
	t = itm.substring(2, 3);
	u = itm.length > 3 ? itm.substring(3, 4) : ''; // Fourth character if it exists

	// Logic to increment from SHF to SHFA, SHFB, etc.
	if (u === '') { // Case for 3 characters (like SHF)
		if (t == 'Z') {
			t = 'A';
			if (s == 'Z') {
				s = 'A';
				fc = f.charCodeAt(0);
				if (fc < 90) { // 'Z' is ASCII 90
					fc = fc + 1;
					f = String.fromCharCode(fc);
				} else {
					f = 'A'; // Reset or handle if needed
				}
			} else {
				sc = s.charCodeAt(0);
				sc = sc + 1;
				s = String.fromCharCode(sc);
			}
		} else {
			tc = t.charCodeAt(0);
			tc = tc + 1;
			t = String.fromCharCode(tc);
		}
		u = 'A'; // Start the fourth character after SHF
	} else { // Case when fourth character exists
		uc = u.charCodeAt(0);
		if (uc < 90) { // Increment within 'A' to 'Z'
			uc = uc + 1;
			u = String.fromCharCode(uc);
		} else {
			u = 'A'; // If 'Z', reset to 'A' and increment previous characters
			if (t == 'Z') {
				t = 'A';
				if (s == 'Z') {
					s = 'A';
					fc = f.charCodeAt(0);
					fc = fc + 1;
					f = String.fromCharCode(fc);
				} else {
					sc = s.charCodeAt(0);
					sc = sc + 1;
					s = String.fromCharCode(sc);
				}
			} else {
				tc = t.charCodeAt(0);
				tc = tc + 1;
				t = String.fromCharCode(tc);
			}
		}
	}

	// Construct the new 4-character sequence
	item_inff = "" + f + s + t + u;

	// Set the new value to the input field and make it read-only
	id.value = item_inff;
	id.readOnly = true;
	id.onClick = false;

	// Store the updated value in 'myval'
	document.getElementById('myval').value = item_inff;
	
}


function item_num1xxhj(id) {
    if (id.value == "") {
        // First time value generation
        if (document.getElementById('myval').value == "") {
            itm = document.getElementById('myval').value = document.getElementById('item_no').value;
            f = itm.substring(0, 1); // 1st character
            s = itm.substring(1, 2); // 2nd character
            t = itm.substring(2, 3); // 3rd character
            u = itm.substring(3, 4); // 4th character (newly added)

            // Incrementing logic for 4th character
            if (u == 'Z') {
                u = 'A';
                if (t == 'Z') {
                    t = 'A';
                    if (s == 'Z') {
                        s = 'A';
                        fc = f.charCodeAt(0);
                        fc = fc + 1;
                        f = String.fromCharCode(fc);
                    } else {
                        sc = s.charCodeAt(0);
                        sc = sc + 1;
                        s = String.fromCharCode(sc);
                    }
                } else {
                    tc = t.charCodeAt(0);
                    tc = tc + 1;
                    t = String.fromCharCode(tc);
                }
            } else {
                uc = u.charCodeAt(0);
                uc = uc + 1;
                u = String.fromCharCode(uc);
            }

            item_inff = "" + f + s + t + u;
            n = id.value = item_inff;
            id.readOnly = true;
            id.onClick = false;
            k = document.getElementById('myval').value = n;
        } else {
            // Subsequent increments
            itm1 = document.getElementById('myval').value;
            f = itm1.substring(0, 1);
            s = itm1.substring(1, 2);
            t = itm1.substring(2, 3);
            u = itm1.substring(3, 4); // 4th character (newly added)

            // Incrementing logic for 4th character
            if (u == 'Z') {
                u = 'A';
                if (t == 'Z') {
                    t = 'A';
                    if (s == 'Z') {
                        s = 'A';
                        fc = f.charCodeAt(0);
                        fc = fc + 1;
                        f = String.fromCharCode(fc);
                    } else {
                        sc = s.charCodeAt(0);
                        sc = sc + 1;
                        s = String.fromCharCode(sc);
                    }
                } else {
                    tc = t.charCodeAt(0);
                    tc = tc + 1;
                    t = String.fromCharCode(tc);
                }
            } else {
                uc = u.charCodeAt(0);
                uc = uc + 1;
                u = String.fromCharCode(uc);
            }

            item_inff = "" + f + s + t + u;
            n = id.value = item_inff;
            id.readOnly = true;
            id.onClick = false;
            k = document.getElementById('myval').value = n;
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
	<div style="text-align: center;"> <a href="/pos/home_dashboard.php">Back</a>
		<table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" align="center">
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
$qryid=mysqli_query($con,"Select max(pur_id) from phppos_purchase");
$row1=mysqli_fetch_row($qryid);

$pur_id=$row1[0]+1;

//generate item numbers

//echo "select item_number from phppos_items where item_id=(select max(item_id) from phppos_items)";
         $res=mysqli_query($con,"select item_number from phppos_items where item_id=(select max(item_id) from phppos_items)");
         $row = mysqli_fetch_row($res);
         $itm = $row[0];

//echo "--item".$itm;
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
	


?> <img src="bill.png" width="408" height="165" />
						<br/>
						<br/> <b>Supplier`s Bill  Entry</b> </td>
			</tr>
			<tr>
				<td width="1308" valign="top">
					<center>
						<?php 
	     //echo $sqlm="select MAX(`bill_id`) from phppos_purchase";
		 $maxbillno=mysqli_query($con,"select MAX(`bill_id`) from phppos_purchase");
		 $maxbill=mysqli_fetch_row($maxbillno);
		 $maxbillno=array($maxbill);
		 //print_r($maxbill);
		 //echo $maxbill[0];
		?>
							<form id="purchse" action="processPurchasetest.php" method="post" onSubmit="return details();">
								<input type="hidden" id="rowcnt" value="1" readonly>
								<table width="100%">
									<tr>
										<td width="25%"> <strong>Purchase Id :</strong>
											<input type="text" name="pur_id" id="pur_id" value="<?php echo $pur_id;?>" readonly /> </td>
										<td width="25%"><strong>Bill No: </strong>
											<input type="text" name="bill_id" id="bill_id" autocomplete="off" value="<?php echo $maxbill[0];?>" /> &nbsp;&nbsp;&nbsp;&nbsp;</td>
										<td width="25%"><strong> Supllier Name: </strong>
											<?php
		//echo "select * from phppos_suppliers order by company_name"; 
		$qrysupp=mysqli_query($con,"select * from phppos_suppliers order by company_name");?>
												<select id="supp_id" name="supp_id">
													<option value="0">Select Supplier</option>
													<?php while($ressupp=mysqli_fetch_row($qrysupp)){?>
														<option value="<?php echo $ressupp[0]; ?>">
															<?php echo $ressupp[1]; ?>
														</option>
														<?php } ?>
												</select>
										</td>
										<td width="25%"><strong>Bill Date :</strong>
											<input type="text" name="bill_date" id="bill_date" onClick="displayDatePicker('bill_date');" /> </td>
									</tr>
								</table>
								<hr>
								<?php $qryitem=mysqli_query($con,"select category from categories");
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
	CloseCon($con);	
	 ?>
									<div id="targetDiv1">
										<div></div>
									</div>
									<div class="content">
										<!--<input type="text" class="search" id="searchid" placeholder="Search for people" /> <br /> -->
										<div id="restul"></div>
									</div>
									<div id="back">
										<input type="hidden" name="theValue" id="theValue" value="5" />
										<input type="hidden" name="myval" id="myval" value="" />
										<table width="100%" border="1">
											<thead>
												<tr>
													<td style="width:12%">Item Number</td>
													<td style="width:12%">Item Name</td>
													<td style="width:12%">Category</td>
													<td style="width:12%">Cost Price</td>
													<td style="width:12%">Sales Price</td>
													<td style="width:12%">Quantity</td>
													<td style="width:12%">Amount</td>
													<td style="width:12%">Remove</td>
												</tr>
											</thead>
											<tbody>
												<!--<div id='1'>
	<tr>
    
	<td><input type="text" name="item_no[]" class="item_no" id="item_no1" autocomplete="off" value="" readonly="readonly"></td>    
    
    <td><input type="text" name="myitemid[]" class="item_id" id="textField1" value="" onKeyUp="checkUsername(event);" onblur="item_num1(this.id);"/> </td>&nbsp;
    
    <td><select  name="item_cat[]" class="item_cat" id="itemcat1">
       <option value="0">Select</option>
       
      <?php 
	   
	   for($i=0;$i< count($category);$i++){
		   ?><option value="<?php echo $category[$i]; ?>"><?php echo $category[$i]; ?></option><?php
	   
	   }
	   ?>
    
    </select>
  </td>
    
  <td><input type="text" name="cprice[]" class="cprice" onChange="subtotal()" value=""  onkeypress="return isNumberKey(event)" autocomplete="off" id="subtotal1"/> </td> 
  <td><input type="text" name="uprice[]" class="uprice"  value=""   autocomplete="off"   id="uprice1">&nbsp; </td> 
  <td> <input type="text" name="qty[]" id="qty1" class="qty" onChange="subtotal()" onKeyPress="return isNumberKey(event)" value=""  autocomplete="off"></td> 
  
  <td> <input type="text" name="subtotal[]" class="subtotal" id="subtotal1" align="right" readonly></td>
 <td><input type="button" value="Remove" onClick="deleteRow(this)"/></td>
  
  
  </tr>
    </div>

   <div id='2'><tr>
   <td><input type="text" name="item_no[]" class="item_no" id="item_no" value="" onClick="item_num1(this)" autocomplete="off" /></td>
    <td><input type="text" name="myitemid[]" class="item_id" id="textField" value="" onKeyUp="checkUsername(event);" /> </td>&nbsp;
    <td><select name="item_cat[]" class="item_cat">
       <option value="0">Select</option>
      <?php 
	   
	   for($i=0;$i< count($category);$i++){
		   ?><option value="<?php echo $category[$i]; ?>"><?php echo $category[$i]; ?></option><?php
	   
	   }
	   ?>
    </select></td>  
    <td><input type="text" name="cprice[]" class="cprice" onChange="subtotal()" value=""  onkeypress="return isNumberKey(event)" autocomplete="off" /> </td> 
  <td><input type="text" name="uprice[]" class="uprice"  value=""   autocomplete="off">&nbsp; </td> 
    <td> <input type="text" name="qty[]" id="qty" class="qty" onChange="subtotal()"  onkeypress="return isNumberKey(event)" value="" align="right" autocomplete="off">  </td> 
    
    <td> <input type="text" name="subtotal[]" class="subtotal" align="right" readonly></td>
	<td><input type="button" value="Remove" onClick="deleteRow(this)"/></td>
    
    </tr>
    </div>
 
   <div id='3'><tr>
   <td><input type="text" name="item_no[]" class="item_no" id="item_no" value="" onClick="item_num1(this)" autocomplete="off" /></td>
   
    <td><input type="text" name="myitemid[]" class="item_id" id="textField" value="" onKeyUp="checkUsername(event);" /> </td>&nbsp;
    <td><select name="item_cat[]" class="item_cat">
       <option value="0">Select</option>
       <?php 
	   
	   for($i=0;$i< count($category);$i++){
		   ?><option value="<?php echo $category[$i]; ?>"><?php echo $category[$i]; ?></option><?php
	   
	   }
	   ?>
    </select></td>  
    <td><input type="text" name="cprice[]" class="cprice" onChange="subtotal()" value=""  onkeypress="return isNumberKey(event)" autocomplete="off" /> </td> 
  <td><input type="text" name="uprice[]" class="uprice"  value=""   autocomplete="off">&nbsp; </td> 
    <td> <input type="text" name="qty[]" id="qty" class="qty" onChange="subtotal()"  onkeypress="return isNumberKey(event)" value="" align="right" autocomplete="off">  </td> 
     
    <td> <input type="text" name="subtotal[]" class="subtotal" align="right" readonly></td>
	<td><input type="button" value="Remove" onClick="deleteRow(this)"/></td>
   
    </tr>
    </div>
  
   <div id='4'><tr>
  <td><input type="text" name="item_no[]" class="item_no" id="item_no" value="" onClick="item_num1(this)" autocomplete="off" / ></td>
    <td><input type="text" name="myitemid[]" class="item_id" id="textField" value="" onKeyUp="checkUsername(event);" /> </td>&nbsp;
    <td><select name="item_cat[]" class="item_cat">
       <option value="0">Select</option>
       <?php 
	   
	   for($i=0;$i< count($category);$i++){
		   ?><option value="<?php echo $category[$i]; ?>"><?php echo $category[$i]; ?></option><?php
	   
	   }
	   ?>
       
    </select></td>  
    <td><input type="text" name="cprice[]" class="cprice" onChange="subtotal()" value=""  onkeypress="return isNumberKey(event)" autocomplete="off" /> </td> 
  <td><input type="text" name="uprice[]" class="uprice"  value=""   autocomplete="off">&nbsp; </td> 
    <td> <input type="text" name="qty[]" id="qty" class="qty" onChange="subtotal()"  onkeypress="return isNumberKey(event)" value="" align="right" autocomplete="off">  </td> 
    
    <td> <input type="text" name="subtotal[]" class="subtotal" align="right" readonly></td>
	<td><input type="button" value="Remove" onClick="deleteRow(this)"/></td>
   
    </tr>
    </div>
 
  
 
  <div id='5'>
  <tr>
  <td><input type="text" name="item_no[]" class="item_no" id="item_no" value="" onClick="item_num1(this)" autocomplete="off" / ></td>
    <td><input type="text" name="myitemid[]" class="item_id" id="textField" value="" onKeyUp="checkUsername(event);" /> </td>&nbsp;
    <td><select name="item_cat[]" class="item_cat">
       <option value="0">Select</option>
       <?php 
	   
	   for($i=0;$i< count($category);$i++){
		   ?><option value="<?php echo $category[$i]; ?>"><?php echo $category[$i]; ?></option><?php
	   
	   }
	   ?>
    </select></td>  
   <td><input type="text" name="cprice[]" class="cprice" onChange="subtotal()" value=""  onkeypress="return isNumberKey(event)" autocomplete="off" /> </td> 
  <td><input type="text" name="uprice[]" class="uprice"  value=""   autocomplete="off">&nbsp; </td> 
  <td> <input type="text" name="qty[]" id="qty" class="qty" onChange="subtotal()"  onkeypress="return isNumberKey(event)" value="" align="right" autocomplete="off">  </td> 
    
    <td> <input type="text" name="subtotal[]" class="subtotal" align="right" readonly></td>
	<td><input type="button" value="Remove" onClick="deleteRow(this)"/></td>
    
      </tr>
      
    </div>-->
											</tbody>
										</table>
									</div>
									<!-- back div close-->
									<div id="image" align="center"></div>
									<div align="center">
										<br/>Total Quantity :
										<input type="text" name="totalqty" id="totalqty" value="0" readonly align="right"> &nbsp; Total Amount :
										<input type="text" name="totalamt" id="totalamt" value="0" readonly align="right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
										<br/>
										<div align="center">Discount :
											<input type="radio" name="dis" class="dis" value="1" checked onClick="calcAmt()"> % &nbsp;
											<input type="radio" name="dis" class="dis" value="0" onClick="calcAmt()">Rs&nbsp;
											<input type="text" onKeyPress="return isNumberKey(event)" id="per" name="per" onKeyUp="calcAmt()" value="0" on autocomplete="off">&nbsp; Payable Amount
											<input type="text" name="payamt" id="payamt" value="0" readonly align="right">
										</div>
									</div>
									<input type="hidden" name="distype" id="distype">
									<input type="button" name="btn" onClick="showrow()" value="Add New Row">&nbsp; &nbsp;
									<input type="submit" name="submit" value="Submit">
									<br/> </form>
					</center>
				</td>
			</tr>
		</table>
	</div>
	<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>
<script>
	for (var a = 0; a < 4; a++) {
		showrow();
	}
</script>