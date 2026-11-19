<script type="text/javascript" src="datepicker/datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="datepicker/date_css.css" />


<body onLoad="">
	<div style="text-align: center;"> <a href="/pos/home_dashboard.php">Back</a>
		<table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" align="center">
			<tr>
				<td align="center">
					<?php
                        // include('config.php');
                        include('../db_connection.php') ;
                        $con=OpenSrishringarrCon();
                    ?> 
                        <img src="../reports/bill.PNG" width="408" height="165" /> 
						<br/>
						<br/> 
						<b>***Add Supplier ***</b> 
				</td>
			</tr>
			<tr>
				<td width="1308" valign="top">
					<center>
						<form id="purchse" action="add_supplier_insert_test.php" method="post" >
								<table width="70%">
									<tr>
										<td width="103">First Name:</td>
										<td colspan="3">
											<input type="text" name="fname" id="fname"  required>
										</td>
										
									    <br/>
										<td width="103"> Last Name :</td>
										<td colspan="3">
											<input type="text" name="lname" id="lname" required >
										</td>
										<td width="103">Email :</td>
										<td colspan="3">
											<input type="text" name="email" id="email"  required>
										</td>
									</tr>
									<tr>
									   
										<td width="103"> Phone Number :</td>
										<td colspan="3">
											<input type="text" name="ph_no" id="ph_no" required >
										</td>
										
										<td width="103">Address 1 :</td>
										<td colspan="3">
											<input type="text" name="add1" id="add1"  required>
										</td>
									    
										<td width="103"> Address 2 :</td>
										<td colspan="3">
											<input type="text" name="add2" id="add2" required >
										</td>
									</tr>
									<tr>	
										<td width="103">City :</td>
										<td colspan="3">
											<input type="text" name="city" id="city"  required>
										</td>
									    
										<td width="103"> State :</td>
										<td colspan="3">
											<input type="text" name="state" id="state" required >
										</td>
										<td width="103">Pincode :</td>
										<td colspan="3">
											<input type="text" name="pincode" id="pincode"  required>
										</td>
									    
									</tr>
									<br/>
									<tr> 
										<td width="103"> Country :</td>
										<td colspan="3">
											<input type="text" name="country" id="country" required >
										</td>
										<td width="103">Comments :</td>
										<td colspan="3">
											<input type="text" name="comments" id="comments"  required>
										</td>
									</tr>
									<br/>
									<tr>
									    <td width="103"> Supplier Company Name:</td>
										<td colspan="3">
											<input type="text" name="supp_comp_name" id="supp_comp_name" required >
										</td>
										<td width="103">Supplier Account Number :</td>
										<td colspan="3">
											<input type="text" name="supp_acc_no" id="supp_acc_no"  required>
										</td>
									    
									    <td width="213">
										   <input type="submit" name="submit" id="submit" value="Submit" > 
									    </td>
									</tr>
									
								</table>
								<!--<div id="back"> </div>-->
								<br/>
						</form>
					</center>
				</td>
			</tr>
		</table>
	</div>
	<div align="center">You are using Point Of Sale Version 10.5 .</div>
	<?php CloseCon($con);?>
</body>
