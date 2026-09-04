<?php
$dir = 'https://srishringarr.com/pos/';
include('../top-header.php');?>

  <?php include('../top-navbar.php');?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_settings-panel.html -->
      <!-- partial -->
      <!-- partial:partials/_sidebar.html -->
      <?php include('../navbar.php');?>

        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="page-header">
              <div class="center">
                <h3 class="page-title">Rent Page</h3>
              </div>
              <!-- <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="#">Forms</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Form elements</li>
                                    </ol>
                                </nav> -->
            </div>
            <div class="col-12 grid-margin">

              <div class="card">
                <div class="card-body">
                  <h4 class="card-title"> Rent</h4>
                  <p class="page-description"></p>
                  <div class="row">
                    <div class="col-12">
                      <div class="table-responsive">
                        <table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" align="center">
                          <tr>
                            <td align="center">
                              <?php
                                                         $con = OpenSrishringarrCon();
                                                        
                                                        $result5=mysqli_query($con,"select * from `phppos_app_config`");
                                                        $row5 = mysqli_fetch_array($result5);
                                                        mysqli_data_seek($con,$result5,1);
                                                        $row6=mysqli_fetch_array($result5);
                                                        mysqli_data_seek($con,$result5,10);
                                                        $row7=mysqli_fetch_array($con,$result5);
                                                        
                                                        ?>

                                <img src="bill.PNG" width="408" height="165" />
                                <br/>
                                <br/>
                                <b>CONFIRMATION MEMO</b>
                            </td>
                          </tr>

                          <tr>
                            <td width="1308" valign="top">
                                <form name="listForm" action="order_detail.php" method="POST" id="frm1">
                                  <br/> 
                                  <div class="row">
                                      <div class="col-md-12 grid-margin stretch-card">
                                          <div class="card">
                                              <div class="card-body">
                                                  <div class="row left-text">
                                                        <div class="col-md-6">
                                                            <div class="form-group row">
                                                                <div class="col-md-4">
                                                                    <div class="form-check">
                                                                        <label class="form-check-label">
                                                                          <input type="radio" class="form-check-input" name="pick" id="pick" value="Customer">
                                                                          Customer Pick-Up 
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="col-md-4 left-text" >
                												    <label>Customer Pick-Up Date</label>
                													<input type="date" name="cust_pick" id="cust_pick" class="form-control"  />
                												</div>
        												    </div>
											            </div>
											            <div class="col-md-6">
                                                            <div class="form-group row">
                                                                <div class="col-md-4">
                                                                    <div class="form-check">
                                                                        <label class="form-check-label">
                                                                          <input type="radio" class="form-check-input" name="pick" id="del2" value="Company Delivery">
                                                                          Company Delivery 
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="col-md-4" >
                												    <label>Company Delivery Date</label>
                													<input type="date" name="comdel_date" id="comdel_date" class="form-control"  />
                												</div>
        												    </div>
											            </div>
                                                  </div>
                                                  <div class="row left-text">
                                                        <div class="col-md-6">
                                                            <div class="form-group row">
                                                                <div class="col-md-4">
                                                                    <div class="form-check">
                                                                        <label class="form-check-label">
                                                                          <input type="radio" class="form-check-input" name="del" id="del" value="Customer Return">
                                                                          Customer Return 
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="col-md-4" >
                												    <label>Customer Return Date</label>
                													<input type="date" name="cust_del" id="cust_del" class="form-control"  />
                												</div>
        												    </div>
											            </div>
											            <div class="col-md-6">
                                                            <div class="form-group row">
                                                                <div class="col-md-4">
                                                                    <div class="form-check">
                                                                        <label class="form-check-label">
                                                                          <input type="radio" class="form-check-input" name="del" id="pick2" value="Company Pick-Up">
                                                                          Company Pick-Up 
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="col-md-4" >
                												    <label>Company Pick-Up Date</label>
                													<input type="date" name="compick_datecompick_date" id="compick_date" class="form-control"  />
                												</div>
        												    </div>
											            </div>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                  </div>

                                  <!--<center>-->
                                  <!--  <hr>-->
                                  <!--  <table width="100%" height="110">-->
                                  <!--    <tr>-->
                                  <!--      <td><a href="people.php?mode=rent" target="_new">New Customer</a></td>-->
                                  <!--    </tr>-->
                                  <!--    <br>-->
                                  <!--  </table>-->

                                  <!--</center>-->
                                  <!--<button type="button" class="btn btn-light btn-fw"><a href="people.php?mode=rent">New Customer</button>-->
                                 <div class="row">
                                    <div class="col-md-12 grid-margin stretch-card">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row text-left">
                                                    
                                                    <!--<div class="col-md-4">-->
                                                    <!--    <div class="form-group row">-->
                                                    <!--        <div class="col-md-9">-->
                                                    <!--            <label>Phone Number</label>-->
                                                    <!--            <input type="text" class="form-control" name="phoneNo" id="phoneNo" value="" />-->
                                                    <!--        </div>-->
                                                    <!--        <div class="col-md-3">	-->
                                                    <!--            <a href="#" onClick="loadPhoneNo();">Find</a> -->
                                                    <!--        </div>-->
                                                    <!--    </div>-->
                                                    <!-- </div>-->
                                                    <div class="col-md-4">
                                                            <div class="form-group row">
                                                                <div class="col-md-9">
                                                                    <label>Phone Number</label>
                                                                    <input type="text" class="form-control" name="phoneNo" id="phoneNo" value="" />
        													    </div>
            												    <div class="col-md-3 " style="margin-top: auto;">
            												        <a href="#" onClick="loadPhoneNo();">Find</a>
            												    </div> 
        												    </div>
											        </div>
											     <div class="col-md-4">
    												<div class="form-group row">
    												  <div class="col-sm-9">
    												       <label >Bill Made By</label>
        													   <select name="bill_by" class="form-control">
                                                                    <option>Select</option>
                                                                    <option value="Rajni Podar">Rajni Podar</option>
                                                                    <option value="Akruti Manjrekar">Akruti Manjrekar</option>
                                                                    <option value="Nipa Agrawal">Nipa Agrawal</option>
                                                                </select>
    												  </div>
    											    </div>
											     </div>
                                                </div>
                                                <div class="row text-left">
                                                    <div class="col-md-4">
                                                        <div class="form-group row">
        												  
        												  <div class="col-md-9">
        												      <label>Customer Name</label>
        													<select name="cid" id="cid" class="form-control">
                                                                <option value="-1">select</option>
                                                                <?php
                                                            	  $result = mysqli_query($con,"SELECT * FROM  phppos_people order by first_name");
                                                            	  while($row = mysqli_fetch_row($result)){ 
                                                            	      
                                                            	      if($row[11]!=""){
                                                            	  ?>
                                                                  <option value="<?php echo $row[11]; ?>">
                                                                    <?php echo $row[0] ."  ". $row[1]; ?>
                                                                  </option>
                                                                <?php }} ?>
                                                            </select>
        												  </div>
        											    </div>
                                                    </div>
                                                    <div class="col-md-4">
        												<div class="form-group row">
        												  <div class="col-md-9">
        												      <label>2nd Person's Name</label>
        													<input type="text" class="form-control" name="pname" id="pname" />
                                                            <input type="hidden" name="myvar" value="0" id="theValue" />
        												  </div>
        											    </div>
											        </div>
											        <div class="col-md-4">
                                                        <div class="form-group row">
        												 
        												  <div class="col-md-9" >
        												       <label>Through Name:</label>
        													<select name="name" class="form-control" id="name" onChange="loadXMLDoc();" >
                                                                <option value="">select</option>
                                                                <?php                                                        	  
                                                                    $bresult = mysqli_query($con,"SELECT * FROM  phppos_people  WHERE first_name LIKE 'B %' order by first_name");
                                                                    while($brow = mysqli_fetch_row($bresult)){ 
                                                                ?>
                                                                <option value="<?php echo $brow[11]; ?>">
                                                                    <?php echo $brow[0] ."  ". $brow[1]; ?>
                                                                </option>
                                                              <?php } ?>
                                                            </select>
        												  </div>
        											    </div>
                                                    </div>
                                                </div>
                                                <div class="row text-left">
                                                    <div class="col-md-3">
        												<div class="form-group row">
        												  <div class="col-md-9">
        												      <label>Bill  Date</label>
        													<input type="date" name="bill_date" id="bill_date" class="form-control"  />
        												  </div>
        											    </div>
											        </div>
											        <div class="col-md-3">
        												<div class="form-group row">
        												  <div class="col-md-9">
        												       <label>2nd Contact No.</label>
        													<input type="text" name="pcontact" id="pcontact" class="form-control" />
        												  </div>
        											    </div>
											        </div>
											        <div class="col-md-3">
        												<div class="form-group row">
        												  <div class="col-md-9">
        												      <label>Through Phone No</label>
        													<input type="text" name="tphone" id="tphone" class="form-control" />
        												  </div>
        											    </div>
											        </div>
											        <div class="col-md-3">
        												<div class="form-group row">
        												  <div class="col-md-9">
        												      <label>Through Area</label>
        													<input type="text" name="area" id="area" class="form-control" />
        												  </div>
        											    </div>
											        </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        
                                    </div>
                                </div>      
                                  
                                  <div id="bookdetail"></div>
                                  <div class="row">
                                      <div class="col-md-12 grid-margin stretch-card">
                                          <div class="card">
                                              <div class="card-body">
                                                    <div class="row left-text">
                                                        <div class="col-md-4">
                                                            <label>Commission</label>
                                                            <div class="form-group row">
                                                                <div class="col-md-3">
                                                                   <div class="form-check">
                                                                        <label class="form-check-label">
                                                                          <input type="radio" class="form-check-input" name="commis" id="comm_rs" value="">
                                                                          Rs.
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <label class="form-check-label">
                                                                          <input type="radio" class="form-check-input" name="commis" id="com_per" value="">
                                                                          %
                                                                        </label>
                                                                    </div>
                                                                </div><br/>
                                                                <div class="row">
                                                                    <div class="col-md-9">
                													<input type="text" name="comm" id="comm" class="form-control"  />
                												</div>
                                                                </div>
                                                                
                                                            </div>
        												</div>
        												        
                												<div class="col-md-4" >
                												    <label>Item Code</label>
                													<input type="text" name="barcode" id="barcode" onFocus="this.value=''" class="form-control" onChange="MakeRequest();findbook();" />
                												</div>
                												<input type="hidden" name="itmval" id="itmval" />
                												<div class="col-md-4" >
                												    <label>Barcode</label>
                													<input type="text" name="barcode2" id="barcode2" onFocus="this.value=''" class="form-control" onChange=" MakeRequest();" />
                												</div>
											        </div>
											            
                                                  </div>
                                                  
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                                  
                                  <hr>


                                  <table width="1301" border="1" cellpadding="4" cellspacing="0">
                                    <tr>
                                      <td width='62'>
                                        <U><strong>Sr.No.</strong></U>
                                      </td>
                                      <td width='59'>
                                        <U><strong>Item Code</strong></U>
                                      </td>
                                      <td width='135'>
                                        <U><strong>Category</strong></U>
                                      </td>
                                      <td width='120'>
                                        <U><strong>Price</strong></U>
                                      </td>
                                      <td width='104'>
                                        <U><strong>Qty</strong></U>
                                      </td>
                                      <td width='136'>
                                        <U><strong>Rent</strong></U>
                                      </td>
                                      <td width='137'>
                                        <U><strong>Discount</strong></U>
                                      </td>
                                      <td width='107'>
                                        <U><strong>Amount</strong></U>
                                      </td>
                                      <td width='139'>
                                        <U><strong>Deposit</strong></U>
                                      </td>
                                      <td width='120'>
                                        <U><strong>Booking Status</strong></U>
                                      </td>
                                      <td width='70'>
                                        <U><strong>Delete</strong></U>
                                      </td>
                                    </tr>

                                    <tr>
                                      <td colspan="11">
                                        <div id="detail"></div>
                                      </td>
                                    </tr>
                                  </table>
                                  <br/>
                                  
                                  <div class="row">
                                      <div class="col-md-12 grid-margin stretch-card">
                                            <div class="card">
                                                <div class="card-body">
                                                    <!--<label> Rent </label>-->
                                                    <div class="row left-text">
                                                        <div class="col-md-4"><label> Rent </label>
                                                            <div class="form-group row">
                                                                        <div class="col-md-3">
                                                                           <div class="form-check">
                                                                                <label class="form-check-label">
                                                                                  <input type="radio" class="form-check-input" name=rentpaid id="rentpaid" value="Paid">
                                                                                    Paid Amount
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div class="col-md-3">
                                                                            <div class="form-check">
                                                                                <label class="form-check-label">
                                                                                  <input type="radio" class="form-check-input" name="rentpaid" id="rentunpaid" value="Unpaid">
                                                                                  UnPaid Amount
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <div class="form-check">
                                                                                <label class="form-check-label">
                                                                                  <input type="radio" class="form-check-input" name="rentpaid" id="rentbalanced" value="Balance">
                                                                                  Balance Amount
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                </div>
                                                            </div>
                                                        
                                                            <div class="col-md-4"><label> Deposit </label>
                                                                <div class="form-group row">
                                                                    <div class="col-md-3">
                                                                       <div class="form-check">
                                                                            <label class="form-check-label">
                                                                              <input type="radio" class="form-check-input" name=rentpaid id="paid" value="Paid">
                                                                                Paid
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="col-md-3">
                                                                        <div class="form-check">
                                                                            <label class="form-check-label">
                                                                              <input type="radio" class="form-check-input" name="rentpaid" id="paid2" value="Unpaid">
                                                                              UnPaid
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        
                                                            <div class="col-md-2">
                                                                <div class="form-group row">
                												  <div class="col-md-9">
                												     <label>Total Quantity</label>
                													<input type="text" name="qty11" id="qty11" class="form-control"  />
                												  </div>
                											    </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group row">
                												  <div class="col-md-9">
                												     <label>Amount Paid</label>
                													<input type="text" name="pamount" id="pamount" class="form-control"  />
                												  </div>
                											    </div>
                                                            </div>
                                                        </div>
                                                        </div>
                                                </div>
                                            </div>
                                      </div>
                                  </div>
                                  
                                  

                                  <table width="1240" height="29">
                                    <tr>
                                      <td width="44"><strong>Rent:-</strong></td>
                                      <td width="173">
                                        <input type="radio" name="rentpaid" id="rentpaid" value="Paid">
                                        <strong>        Paid Amount</strong>
                                        <br/>
                                        <input type="radio" name="rentpaid" id="rentpaid" value="Unpaid">
                                        <strong>       Unpaid Amount      
                                                                </strong>
                                        <br/>
                                        <input type="radio" name="rentpaid" id="rentpaid" value="Balance">
                                        <strong>Balance Amount
                                                                   </strong>
                                      </td>
                                      <td width="90"><strong>Deposit :-</strong></td>
                                      <td width="171"><strong>
                                                                  <input type="radio" name="paid" id="paid" value="Paid">
                                                                  Paid <br/>
                                                                   <input type="radio" name="paid" id="paid2" value="Unpaid">
                                                                  Unpaid </strong>
                                      </td>
                                      <td width='186'><strong>Total Qty :</strong>
                                        <input type="text" name="qty11" id="qty11" value="" style="width:100px;" readonly/>
                                      </td>

                                      <td width="192"><strong>Amount Paid : </strong>&nbsp;
                                        <input type="text" name="pamount" id="pamount" value="" size="11">
                                      </td>
                                      <td width="206"><strong>Total Amount : </strong>
                                        <input type="text" size="10" name="total" id="total" value="0" />

                                        <input type="hidden" name="cardpercamt" id="cardpercamt" value="" style="width:100px;" readonly/>
                                      </td>

                                    </tr>

                                    <tr>

                                      <td>In Account :</td>
                                      <td>
                                        <select id="acc" name='acc'>
                                          <option value="0">Select </option>
                                          <?php 
                                                              
                                                              $qryitem=mysqli_query($con,"select bank_name, bank_id from banks");
                                                               while($row=mysqli_fetch_row($qryitem))
                                                        		 {
                                                        				echo "<option value='".$row[1]."'>".$row[0]."</option>";
                                                        			}?>
                                        </select>
                                      </td>

                                      <td width="90">Payment By : </td>
                                      <td width="173">
                                        <input name="pay_By" type="radio" value="Cheque" onchange="checkTotal()">
                                        <strong>Cheque</strong>
                                        <br>
                                        <input name="pay_By" type="radio" value="Cash" onchange="checkTotal()">
                                        <strong>Cash</strong>
                                        <br>
                                        <input name="pay_By" type="radio" value="Card" onchange="checkTotal()">
                                        <strong>Card</strong>

                                        <td><strong>Note:</strong></td>
                                        <td>
                                          <textarea name="note" id="note" rows="3" cols="55"></textarea>
                                        </td>

                                      </td>

                                    </tr>
                                    <tr>
                                      <td></td>
                                      <td><strong>Trail Date :</strong>
                                        <input type="text" name="trail_date" id="trail_date" onClick="displayDatePicker('trail_date');" autocomplete="nope" />
                                      </td>
                                      <td width="90"><strong>Measurement :-</strong></td>
                                      <td width="171">
                                        <select id="measurement" name='measurement'>
                                          <option value="">Select </option>
                                          <option value="yes">yes</option>
                                          <option value="no">no</option>
                                      </td>
                                      <td><strong>Measurement Note:</strong></td>
                                      <td>
                                        <textarea name="measurement_note" id="note" rows="3" cols="55"></textarea>
                                      </td>

                                      <td width="90"><strong>Delivery :-</strong></td>
                                      <td width="171">
                                        <select id="delivery" name='delivery'>
                                          <option value="">Select </option>
                                          <option value="yes">yes</option>
                                          <option value="no">no</option>
                                      </td>
                                    </tr>
                                  </table>
                                  <br/>

                                  <input type="button" onClick="formSubmit()" value="Rent Bill" />
                                </form>
                              </center>
                            </td>
                          </tr>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>

          </div>

          <?php Closecon($con); include('../footer.php');?>
        </div>
        <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="/pos/vendors/js/vendor.bundle.base.js"></script>
    <script src="/pos/vendors/js/vendor.bundle.addons.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page-->
    <!-- End plugin js for this page-->
    <!-- inject:js -->
    <script src="/pos/js/off-canvas.js">
    </script>
    <script src="/pos/js/hoverable-collapse.js">
    </script>
    <script src="/pos/js/misc.js">
    </script>
    <script src="/pos/js/settings.js">
    </script>
    <script src="/pos/js/todolist.js"></script>

    <!-- End custom js for this page-->
    <!-- video.js -->
    <!--<script src="/pos/js/data-table.js"></script>
<script src="/pos/js/data-table2.js"></script>-->
    <script src="/pos/js/select2.js"></script>
    <script type="text/javascript" src="datepick_js.js"></script>
    <script type="text/javascript" src="js/rent.js"></script>
    </body>

    </html>