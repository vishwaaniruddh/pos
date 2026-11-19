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
						<form  action="items_insert.php" method="post" enctype="multipart/form-data">
						   
						    
						    <?php $qryitem=mysqli_query($con,"select *  from phppos_suppliers_test  order by company_name ASC ");
                            // 	CloseCon($con);	
                            ?>
                            <?php    
                                             $res=mysqli_query($con,"select item_number from phppos_items_test where item_id = (select max(item_id) from phppos_items)");
                                             $row_res = mysqli_fetch_assoc($res);
                                             $itm = $row_res['item_number'];
                                            // $itm = "qyw";
                                            //  var_dump($itm); 
                                             $f = substr($itm,0,1);  
                                             $s = substr($itm,1,1); 
                                             $t = substr($itm,2,1); 
                                             if($t==9){ 
                                                 
                                                 $t=0; 
                                                 if($s=='Z')
                                                 { 
                                                      
                                                     $s='A'; 
                                                     $fc = ord($f); 
                                                      
                                                     $fc=$fc+1; 
                                                      
                                                     $f=chr($fc); 
                                                      
                                                     
                                                 } else { 
                                                     $sc = ord($s);  
                                                     $sc=$sc+1;      
                                                     $s=chr($sc);    
                                                     } 
                                                 
                                             }
                                             else { $t=$t+1;  } 
                                             $itemNo = "".$f.$s.$t;
                                            
                                            $itemid=mysqli_query($con,"select item_id from phppos_items_test where item_number = '".$itm."'");
                                            $itemid_row = mysqli_num_rows($itemid);
                                            $item_id_fetch = mysqli_fetch_assoc($itemid);
                                            // echo $itemid_row;
                                            if($itemid_row==1){ $item_id= $item_id_fetch['item_id']; }
                            ?>
							<hr>
							<table width="70%">
								<tr>
								    <input type="hidden" name ="itemId" id="itemId" value="<?php echo $item_id;?>">
									<td width="103">Item Number:</td>
									<td colspan="2">
									    
										<input type="text" name="itemNo" id="itemNo" value="<? echo $itemNo; ?>" readonly/> 
									</td>	
								</tr>
								<tr>
									<td width="103">Item Name:</td>
									<td colspan="2">
										<input type="text" name="itemName" id="itemName" value="" /> 
									</td>	
								</tr>
								<tr>
									<td width="103">Category:</td>
									<td colspan="2">
									    <?php
									    //$categorylist = mysqli_query($con,"select distinct(category),item_id fromm phppos_items_test ORDER BY category ASC");
									   // $category_list = mysqli_fetch_assoc($categorylist);
									   // $categorylist = $category_list['category'];
									    ?>
										<input type="text" name="category" id="category" value="" /> 
										
									</td>	
								</tr>
								<tr>
									<td width="300">Supplier :</td>
									<td colspan="2">
										<select name="supp_id" id="supp_id">
											<option value="0">Select Name</option>
											
											<?php  while($row=mysqli_fetch_row($qryitem))
                                        		 {
                                        				echo "<option value='".$row[0]."'>".$row[1]."</option>";
                                        			}?>
										</select>
									</td>
								</tr>
								<tr>
									<td width="217"> Cost Price :</td>
									<td colspan="2">
										<input type="text" name="cost_price" id="cost_price" >
									</td>
									<td width="166"> Unit Price :</td>
									<td colspan="2">
										<input type="text" name="unit_price" id="unit_price" >
									</td>
								</tr>
								<tr>
								    <td width="217"> Tax :</td>
								    <td colspan = "2">
								        <input type="text" id="tax1" name="tax" />
								    </td>
								    <td width="166"> Tax 2 :</td>
								    <td colspan = "2">
								        <input type="text" id="tax2" name="tax" />
								    </td>
								</tr>
								<tr>
								    <td width="217"> Quantity :</td>
								    <td colspan="2">
								        <input type="text" id="quantity" name="quantity" />
								    </td>
								</tr>
								<tr>
								    <td width="217"> Reorder Level :</td>
								    <td colspan="2">
								        <input type="text" id="reorder" name="reorder" />
								    </td>
								</tr>
								<tr>
								    <td width="217"> Description :</td>
								    <td colspan="2">
								        <textarea id="description" name="description" rows="5" cols="20"></textarea>
								    </td>
								</tr>
								<tr>
								    <td width="217"> Allow Alternate Description :</td>
								    <td colspan="2">
								        <input type="radio" id="allow_alt_desc1" name="allow_alt_desc" value="1"/>Yes
								        <input type="radio" id="allow_alt_desc2" name="allow_alt_desc" value="0"/>No
								    </td>
								</tr>
								<tr>
								    <td width="217"> Item has Serial Number :</td>
								    <td colspan="2">
								        <input type="radio" id="is_serialized1" name="is_serialized" value="1"/>Yes
								        <input type="radio" id="is_serialized2" name="is_serialized" value="0"/>No
								    </td>
								</tr>
								<tr>
								    <td width="217"> Image :</td>
								    <td colspan="2">
								        <input type="file" id="image" name="image" />
								    </td>
								</tr>
								
								
							</table>
							<input type="submit" name="submit"  id="submit" value="Submit"> 
							<!--<div id="back"> </div>-->
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
		                        <td>Name</td>
		                        <td>Category</td>
		                        <td>Supplier</td>
		                        <td>Cost Price</td>
		                        <td>Unit Price</td>
		                        <td>Tax 1</td>
		                        <td>Tax 2</td>
		                        <td>Quantity</td>
		                        <td>Reorder Level</td>
		                        <td>Description</td>
		                        <td>Allow Alternate Description</td>
		                        <td>Item has Serial Number</td>
		                        <td>Action</td>
		                    </thead>
		                    <tbody>
		                        <?php 
		                            $i = 1;
		                            $datafetch = mysqli_query($con,"select * from phppos_items_test order by item_id desc ");
		                            while($datafetch_res = mysqli_fetch_assoc($datafetch))
		                            { ?>
		                                <td><?echo $i;?></td>
		                                <td></td>
		                           <? 
		                                
		                           $i++; }?>
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