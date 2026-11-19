<script type="text/javascript" src="datepicker/datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="datepicker/date_css.css" />

<body onLoad="">
	<div style="text-align: center;"> <a href="/pos/items/itemcode_search.php">Back</a>
		<table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" align="center">
			<tr>
				<td align="center">
					<?php
                        // include('config.php');
                        include('../db_connection.php') ;
                        $con=OpenSrishringarrCon();
                            
                        if($_POST['submit']){    
                            $itemid = $_POST['itemid'];
                            
                            $itemno = $_POST['itemNo'];
                            
                            $name = $_POST['itemName'];
                            $category = $_POST['category'];
                            $supp_id =$_POST['supp_id'];
                            $cost_price =$_POST['cost_price'];
                            $unit_price = $_POST['unit_price'];
                            $quantity = $_POST['quantity'];
                            $description = $_POST['description'];
                            
                            $updatesql = mysqli_query($con,"update phppos_items set name='".$name."', category='".$category."', supplier_id= '".$supp_id."', cost_price = '".$cost_price."', unit_price = '".$unit_price."', quantity = '".$quantity."', description= '".$description."' where item_id = '".$itemid."' ");
                            // var_dump($updatesql);
                            if($updatesql)
                            { ?>
                                <script>
                                    alert('Details Updated Successfully');
                                    window.location.href="itemcode_search.php";
                                </script>
                        <?    }
                            else { ?>
                                <script>
                                    alert('Something Went Wrong!!');
                                    window.location.href="itemcode_search.php";
                                </script>
                        <?    }
                        }
                        
                    ?> 
                        <img src="../images/logo.png" width="408" height="165"/>
						<br/>
						<br/> <b>***Itemcode Details Edit***</b> </td>
			</tr>
			<tr>
				<td width="1308" valign="top">
					<center>
						<form  action="<?php echo $_SERVER["PHP_SELF"] ?>" method="post" enctype="multipart/form-data">
						   <?php 
						    $id = $_GET['id'];
						  
						    $fetchdata = mysqli_query($con,"select * from phppos_items  where item_id = '".$id."' ");
						    $fetchdata_result = mysqli_fetch_assoc($fetchdata);
						    
						    $supplier_id = $fetchdata_result['supplier_id'];
		                    $itemid  = $fetchdata_result['item_id'];
						   
						    $fetchsupplier = mysqli_query($con,"select * from phppos_suppliers where person_id='".$supplier_id."' ");
                            $fetchsupplier_res = mysqli_fetch_assoc($fetchsupplier);
                            $supp_name = $fetchsupplier_res['company_name'];
                            
                           ?>
							<hr>
							<table width="70%">
							    <input type="hidden" id="itemid" name="itemid" value="<? echo $id;?>" readonly />
							    <tr>
							        <td width="103">Item Number:</td>
									<td colspan="2">
									    
										<input type="text" name="itemNo" id="itemNo" value="<? echo $fetchdata_result['item_number']; ?>" readonly/> 
									</td>	
								</tr>
								<tr>
									<td width="103">Item Name:</td>
									<td colspan="2">
										<input type="text" name="itemName" id="itemName" value="<? echo $fetchdata_result['name']; ?>" /> 
									</td>	
								</tr>
							        
							    </tr>
							    <tr>
							        <td width="103">Category :</td>
									<td colspan="2">
									    
										<input type="text" name="category" id="category" value="<? echo $fetchdata_result['category']; ?>" /> 
									</td>	
								</tr>
								<tr>
									<td width="103">Supplier :</td>
									<td colspan="2">
									    <?php $qryitem=mysqli_query($con,"select * from phppos_suppliers  order by company_name ASC "); ?>
										
										<select name="supp_id" id="supp_id">
											<option value="0">Select Name</option>
											
											<?php  while($row=mysqli_fetch_row($qryitem))
                                        		 {
                                        				echo "<option value='".$row[0]."'>".$row[1]."</option>";
                                        			}?>
										</select>
									</td>	
								</tr>
							        
							    </tr>
							    <tr>
							        <td width="103">Cost Price :</td>
									<td colspan="2">
									    
										<input type="text" name="cost_price" id="cost_price" value="<? echo $fetchdata_result['cost_price']; ?>" /> 
									</td>	
								</tr>
								<tr>
									<td width="103">Unit Price :</td>
									<td colspan="2">
										<input type="text" name="unit_price" id="unit_price" value="<? echo $fetchdata_result['unit_price']; ?>" /> 
									</td>	
								</tr>\
								<tr>
							        <td width="103">Quantity :</td>
									<td colspan="2">
									    
										<input type="text" name="quantity" id="quantity" value="<? echo $fetchdata_result['quantity']; ?>" /> 
									</td>	
								</tr>
								<tr>
									<td width="103">Description :</td>
									<td colspan="2">
										<input type="text" name="description" id="description" value="<? echo $fetchdata_result['description']; ?>" /> 
									</td>	
								</tr>
							        
							    </tr>
								 
							</table>
							<input type="submit" id="submit" name="submit" class="btn btn-success"  value="Update">
							<br/> </form>
							    
					</center>
				</td>
			</tr>
		</table>
		<br/>
    
		
	</div>
	<div align="center">You are using Point Of Sale Version 10.5 .</div>
</body>

<? CloseCon($con); ?>