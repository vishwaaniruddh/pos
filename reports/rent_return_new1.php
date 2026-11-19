<?php
echo '1';
// $dir = 'https://srishringarr.com/pos/';
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
                                    <h3 class="page-title" >Rent Return Page</h3>
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
                                      <h4 class="card-title"> Alert : Return Date</h4>
                                      <p class="page-description"></p>
                                      <div class="row">
                                        <div class="col-12">
            								  <div class="table-responsive">
            									    <table width="788" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
  
                                                            <tr><td  valign="top" align="center"><font size="+1">
                                                            <?php
                                                            $con=OpenSrishringarrCon();
                                                            
                                                             
                                                            $result5=mysqli_query($con,"select * from   `phppos_app_config`");
                                                            $row5 = mysqli_fetch_array($result5);
                                                            mysqli_data_seek($result5,1);
                                                            $row6=mysqli_fetch_array($result5);
                                                            mysqli_data_seek($result5,10);
                                                            
                                                            $row7=mysqli_fetch_array($result5);
                                                            
                                                            ?>
                                                            <img src="bill.PNG" width="408" height="165"/><br/><br/>
                                                            Rent Return</font><br/><br/><center>
                                                            <form action="approval_detail.php" id="frm1" name="frm1" method="POST">
                                                            
                                                            <div class="col-md-8">
                                    							<div class="form-group row">
                                    								<label class="col-sm-4 col-form-label">Phone Number:<br></label>
                                    									<div class="col-sm-8">
                                    									    <input type="text" name="phoneNo" id="phoneNo" value="" class="form-control" /> <a href="#" onClick="loadPhoneNo();">Find</a> <br /><br />
                                                            
                                    									</div>
                                    							</div>
                                    						</div>    
                                                            <div class="col-md-8">
                                    							<div class="form-group row">
                                    								<label class="col-sm-4 col-form-label">Customer Name:<br></label>
                                    									<div class="col-sm-8">
                                    										<select name="cid" id="cid" class="form-control js-example-basic-single w-100" onchange="MakeRequest();">
                                    											<option value="">Select</option>
                                    											<?php 
                                                                            	   	  $result = mysqli_query($con,"SELECT * FROM phppos_people WHERE first_name!='' order by first_name");
                                                                            	      while($row = mysqli_fetch_row($result)){ 
                                                                            	  ?>
                                                                                        <option value="<?php echo $row[11]; ?>" ><?php echo $row[0] ."  ". $row[1]; ?></option>
                                                                                  <?php } ?>  						
                                    										</select>
                                    									</div>
                                    							</div>
                                    						</div>
                                                            
                                                            
                                                                  <input type="hidden" name="myvar" value="0" id="theValue" /><br/><br/>
                                                                 
                                                                  <table width="778" border="0" cellpadding="4" cellspacing="0">
                                                              <tr><td>
                                                             <div id="detail"></div>
                                                            
                                                                 </td></tr>
                                                                </table>
                                                                  
                                                                  <br/>
                                                            </form></center>
                                                             </td></tr>
                                                             
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
<script src= "/pos/vendors/js/vendor.bundle.base.js"></script>
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
<!--<script src="/pos/js/typeahead.js"></script>-->
<script src="/pos/js/select2.js"></script>
<script type="text/javascript" src="js/rent_return.js"></script>            
</body>
</html>
           