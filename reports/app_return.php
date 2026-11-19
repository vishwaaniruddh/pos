<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

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
		////////////////
	function formSubmit() {
		if (document.getElementById('cid').value == -1) {
			alert("Please enter Customer Id to continue.");
			document.getElementById('cid').focus();
			return false;
		} else {
			document.getElementById("frm1").submit();
			return true;
		}
	}
	var searchReq = getXMLHttp();

	function getXMLHttp() {
		var xmlHttp
			// alert("hi1");
		try {
			//Firefox, Opera 8.0+, Safari
			xmlHttp = new XMLHttpRequest();
		} catch (e) {
			//Internet Explorer
			try {
				xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try {
					xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e) {
					alert("Your browser does not support AJAX!")
					return false;
				}
			}
		}
		return xmlHttp;
	}

function MakeRequest() {
    var xmlHttp = getXMLHttp();
    var loader = document.getElementById("loader"); // Loader element

    function checkAndRequest() {
        var str = document.getElementById('cid').value.trim(); // Trim spaces to avoid empty values

        if (str) {
            loader.style.display = "block"; // Show loader

            xmlHttp.onreadystatechange = function() {
                if (xmlHttp.readyState == 4) {
                    loader.style.display = "none"; // Hide loader when response is received
                    
                    if (xmlHttp.status == 200) {
                        HandleResponse(xmlHttp.responseText);
                    } else {
                        alert("Error fetching data!");
                    }
                }
            };

            xmlHttp.open("GET", "getDetail.php?barcode=" + encodeURIComponent(str), true);
            xmlHttp.send(null);
        } else {
            setTimeout(checkAndRequest, 500); // Check again in 500ms if input is empty
        }
    }

    checkAndRequest();
}



	function HandleResponse(response) {
		//alert(response);
		document.getElementById('detail').innerHTML = response;
	}
	////remove div
	function removeElement(divNum) {
		var d = document.getElementById('detail');
		var olddiv = document.getElementById(divNum);
		d.removeChild(olddiv);
	}
	//////////////////Phone Number
	function loadPhoneNo() {
		//alert("hi");
		var xmlhttp;
		if (window.XMLHttpRequest) { // code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		} else { // code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				///alert(xmlhttp.responseText);
				var s = xmlhttp.responseText;
				//alert(s);
				var s1 = s.split('&&');
				//alert(s1[0]+"/"+s1[1]);
				if (s1[0] == "0") alert("No such Phone Number");
				else {
					document.getElementById("cid").value = s1[1];
					MakeRequest();
				}
				//document.getElementById("").value=s1[];
				//document.getElementById("myDiv").innerHTML=xmlhttp.responseText;
			}
		}
		var str = document.getElementById('phoneNo').value;
		//alert(str);
		xmlhttp.open("GET", "getbyphone.php?cid=" + str, true);
		xmlhttp.send();
	}
</script>
<script type="text/javascript">
	function confirm_delete(id) {
		if (confirm("Are you sure you want to delete this entry?")) {
			document.location = "delete_app.php?id=" + id;
		}
	}
</script>
<div style="text-align: center;"> <font size="+1">
<a href="/pos/home_dashboard.php">Back</a></font>
	<table width="788" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" align="center">
		<tr>
			<td valign="top" align="center">
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

?> <img src="bill.PNG" width="408" height="165" />
					<br/>
					<br/> Approval Return
					<br/>
					<br/>
					<center>
						<form action="approval_detail.php" id="frm1" name="frm1" method="POST"> Phone Number:&nbsp;&nbsp;&nbsp;
							<input type="text" name="phoneNo" id="phoneNo" value="" /> <a href="#" onClick="loadPhoneNo();">Find</a>
							<br />
							<br /> Customer Name:&nbsp;&nbsp;&nbsp;
   
                            <input type="hidden" name="cid" id="cid" />
                            <input type="text" id="people" class="form-control" list="peopleOptions" name="person" placeholder="Enter people ..." value="<?php if(isset($_REQUEST['person'])){ echo $_REQUEST['person'] ; } ?>" />
                            <img id="loader2" src="loader.gif" style="display:none; width: 20px; height: 20px; margin-left: 5px;" />
                            <datalist id="peopleOptions"></datalist>

        
        
							<input type="hidden" name="myvar" value="0" id="theValue" />
							<br/>
							<br/>
							
							
							<style>
							    #loader {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 10px;
    border-radius: 5px;
    display: none;
}

#loader2 {
    vertical-align: middle;
}

							</style>
							<div id="loader" style="display:none;">Loading...</div>

                            

							<table width="778" border="0" cellpadding="4" cellspacing="0">
								<tr>
									<td>
										<div id="detail"></div>
									</td>
								</tr>
							</table>
							<br/> </form>
					</center>
			</td>
		</tr>
	</table>
</div>

<script>
    
    $(document).ready(function () {
    let typingTimer; 
    let doneTypingInterval = 2000; 

    $("#people").on('input', function () {
        clearTimeout(typingTimer); 
        typingTimer = setTimeout(function () {
            var input = $("#people").val().trim(); 

            if (input) {
                $("#loader").show(); // Show loader when request starts
                
                $.ajax({
                    type: "POST",
                    url: './get_suggestions_people.php',
                    data: { input: input },
                    success: function (response) {
                        $("#loader").hide(); // Hide loader on success
                        var datalist = $("#peopleOptions");
                        datalist.empty();

                        var suggestions = JSON.parse(response);
                        suggestions.forEach(function (suggestion) {
                            datalist.append($("<option>").attr('value', suggestion));
                        });
                    },
                    error: function () {
                        $("#loader").hide(); // Hide loader on error
                    }
                });
            }
        }, doneTypingInterval);
    });

    function populatePeopleData(people) {
        $("#loader").show(); // Show loader when fetching people data
        
        $.ajax({
            type: "POST",
            url: 'get_people_data.php',
            data: { people: people },
            success: function (msg) {
                $("#loader").hide(); // Hide loader on success

                if (msg != 0) {
                    var obj = JSON.parse(msg);
                    $('#cid').val(obj.person_id);
                } else {
                    alert('No Info With This Name');
                    $('#people').val('');
                }
            },
            error: function () {
                $("#loader").hide(); // Hide loader on error
                alert('An error occurred while fetching data.');
                $('#people').val('');
            }
        });
    }

    $("#people").on('change', function () {
        var people = $(this).val().trim();
        if (people) {
            populatePeopleData(people);
            setTimeout(function () {
                MakeRequest();
            }, 4000);
        }
    });
});


</script>

<?php CloseCon($con);?>
	<div align="center">You are using Point Of Sale Version 10.5 .</div>