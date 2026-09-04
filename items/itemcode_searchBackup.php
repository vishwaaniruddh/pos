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
                        <img src="../images/logo.png" width="408" height="165" />
						<br/>
						<br/> <b>***Itemcode ***</b> </td>
			</tr>
			<tr>
				<td width="1308" valign="top">
					<center>
						<form  action="<? echo $_SERVER["PHP_SELF"] ?>" method="post" enctype="multipart/form-data">
						   
							<hr>
							<table width="70%">
								<tr>
								    
									<td width="103">Item Code:</td>
									<td colspan="5">
									    
										<input type="text" name="itemcode" id="itemcode" /> 
										<input type="submit" name="submit"  id="submit" value="Search"> 
									</td>
								</tr>
							</table>
							<br/> </form>
							    
					</center>
				</td>
			</tr>
		</table>
		<br/>
    <table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" align="center">
	<td width="1308" valign="top">
		<center>
		    <div class="card">
		        <div class="card-body">
		            <table width="1300">
		                <tr>
		                    <thead>
		                        <td>Srno</td>
		                        <td>Item Code</td>
		                        <td>Item ID</td>
		                        <td>Category</td>
		                        <td>Supplier</td>
		                        <td>Cost Price</td>
		                        <td>Unit Price</td>
		                        <td>Quantity</td>
		                        <td>Description</td>
		                        <td>Action</td>
		                    </thead>
		                    <tbody>
		                       
		                        <?php
		                        if($_POST['submit']){
		                        
		                            $i=1;
		                            $fetchdata = mysqli_query($con,"select * from phppos_items_test where name='".$_POST['itemcode']."' ");
		                            $fetchdata_res = mysqli_fetch_assoc($fetchdata);
		                            $supplier_id = $fetchdata_res['supplier_id'];
		                            $itemid  = $fetchdata_res['item_id'];
		                         
		                            $fetchsupplier = mysqli_query($con,"select * from phppos_suppliers_test where person_id='".$supplier_id."' ");
		                            $fetchsupplier_res = mysqli_fetch_assoc($fetchsupplier);
		                         
		                        
		                            $fetchtaxes = mysqli_query($con,"select * from phppos_items_taxes_test where item_id='".$itemid."' ");
		                            $fetchtaxes_res = mysqli_fetch_assoc($fetchsupplier);
		                            
		                        }
		                        ?>
		                        <td><? echo $i;?></td>
		                        <td><?=$fetchdata_res['name'];?></td>
		                        <td><?=$fetchdata_res['item_id'];?></td>
		                        <td><?=$fetchdata_res['category'];?></td>
		                        <td><?=$fetchsupplier_res['company_name'];?></td>
		                        <td><?=$fetchdata_res['cost_price'];?></td>
		                        <td><?=$fetchdata_res['unit_price'];?></td>
		                        <td><?=$fetchdata_res['quantity'];?></td>
		                        <td><?=$fetchdata_res['description'];?></td>
		                        
		                        <td>
		                          <a href="edititemcode.php?id=<?php echo $fetchdata_res['item_id'];?>">  <button type="button" id="edit" name="edit" class="btn btn-primary">Edit</button></a>
		                          <br/>
		                          <a href="deleteitemcode.php?id=<?php echo $fetchdata_res['item_id'];?>"> <button type="button" id="delete" name="delete" class="btn btn-warning" onclick="return confirm('Are you sure you want to Remove?');">Delete</button></a>
		                        </td>
		                    <? $i++;?>
		                    </tbody>
		                </tr>
		            </table>
		            
		        </div>
		    </div>
		</center>
		</td>
	</table>
		
	</div>
	<div align="center">You are using Point Of Sale Version 10.5 .</div>

</body>

<? CloseCon($con); ?>