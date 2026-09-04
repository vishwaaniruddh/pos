<?php include('../top-header.php');?>
     <?php include('../top-navbar.php');?>
            <!-- partial -->
            <div class="container-fluid page-body-wrapper">
                <?php include('../navbar.php');
                    $con = OpenSrishringarrCon();
                
                ?>
                
                <!-- partial -->
                  <div class="main-panel">
                        <div class="content-wrapper">
                            <div class="page-header">
                                <div class="center">
                                    <h3 class="page-title" >Customer Balance Amount Report(Approval & Sales)</h3>
                                </div>
                            </div>
                            <div class="col-12 grid-margin">
                				<div class="card">
                					<div class="card-body">
                			    	    <form class="form-sample" id="forms" action="<?php $_SERVER['PHP_SELF'] ?>" method="POST" enctype="multipart/form-data">
                							<div class="row">
                						    	<div class="col-md-6">
                        							<div class="form-group row">
                        							  <label class="col-sm-6 col-form-label">Search Name Starting with:</label>
                        							  <div class="col-sm-3">
                        								<input type="text" name="alph" id="alph" />
                        								
                        							  </div>
                        							</div>
                						         </div>
                						         
                						         <div class="col-md-6">
                						             <button class="btn btn-primary mr-2" type="submit" name="submit">Submit</button>
                						         </div>       
                						    </div>
                    						
                					  </form>
                				    </div>							  
                                </div>
                			 </div>
            			    <?php  
            			       
                                $alph = $_POST['alph'];
                                $total2 = 0;
                                if (isset($_POST['submit'])) {
                                    $qry = "SELECT * FROM `phppos_people` WHERE `person_id` not in (SELECT `person_id` FROM `phppos_suppliers`) ";
                                
                                    if ($alph != "") {
                                        $qry .= " and first_name like '" . $alph . "%'";
                                    }
                                
                                    $qry .= " ORDER BY `phppos_people`.`first_name` ASC";
                                

                                    $res = mysqli_query($con, $qry);
                                    $num = mysqli_num_rows($res);
                                    // echo $num; 
                                
                                }	
                             ?>
                             
            					<div class="col-12 grid-margin">
            						<div class="card">
                                        <div class="card-body">
                                            
                                          
                                          <div class="row">
                                            <div class="col-12">	  
            								  <div class="table-responsive">
            									<table class="table" id="tablelist">
                                                <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Customer Name</th>
                                                        <th>Mobile No.</th>
                                                        <th>Net Amount</th>
                                                        <th>Payment</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $i = 1;
                                                    $total2 = 0;
                                                    if (mysqli_num_rows($res) > 0) {
                                                        while ($row = mysqli_fetch_assoc($res)) {
                                                            $sum = 0;
                                            
                                                            $qry1 = "SELECT * FROM `approval` WHERE cust_id = '{$row['person_id']}'";
                                                            $res1 = mysqli_query($con, $qry1);
                                                            $num1 = mysqli_num_rows($res1);
                                                            $gsttot = 0;
                                            
                                                            while ($row1 = mysqli_fetch_assoc($res1)) {
                                                                $pd = 0;
                                                                $s1 = 0;
                                                                $ba = 0;
                                                                $na = 0;
                                                                $ra = 0;
                                            
                                                                $qry2 = "SELECT SUM(paid_amount) FROM `approval` WHERE bill_id = '{$row1['bill_id']}'";
                                                                $res2 = mysqli_query($con, $qry2);
                                                                $num2 = mysqli_num_rows($res2);
                                                                $row2 = mysqli_fetch_row($res2);
                                            
                                                                $a = 0;
                                                                $a1 = 0;
                                            
                                                                $qry4 = "SELECT * FROM `approval_detail` WHERE bill_id = '{$row1['bill_id']}'";
                                                                $res4 = mysqli_query($con, $qry4);
                                            
                                                                if (mysqli_num_rows($res4) > 0) {
                                                                    $gsttotiv = 0;
                                                                    while ($row4 = mysqli_fetch_assoc($res4)) {
                                                                        $a = round(($row4['sgstamt'] + $row4['cgstamt'] + $row4['igstamt']) * $row4['quantity'], 2);
                                                                        $a1 += $a;
                                                                        $ba += $row4['sgstamt'] + $row4['cgstamt'] + $row4['igstamt'];
                                                                    }
                                            
                                                                    $qry3 = "SELECT SUM(amount) FROM `approval_detail` WHERE bill_id = '{$row1['bill_id']}'";
                                                                    $res3 = mysqli_query($con, $qry3);
                                                                    $row3 = mysqli_fetch_row($res3);
                                                                }
                                            
                                                                $gsttotqr = mysqli_query($con, "SELECT SUM(sgstamt), SUM(cgstamt), SUM(igstamt) FROM approval_detail WHERE bill_id = '{$row1['bill_id']}'");
                                                                $gsttotrws = mysqli_fetch_array($gsttotqr);
                                                                $gsttot = $gsttot + $gsttotrws[0] + $gsttotrws[1] + $gsttotrws[2];
                                            
                                                                $na = $ba;
                                                                $s1 = $ba - $a1;
                                            
                                                                if (mysqli_num_rows($res4) > 0) {
                                                                    $s = $row3[0] - $row2[0];
                                                                } else {
                                                                    $s = $row2[0];
                                                                }
                                            
                                                                $sum += $s1;
                                                            }
                                            
                                                            $qry41 = "SELECT SUM(amount) FROM `paid_amount` WHERE `bill_id` = '{$row['person_id']}'";
                                                            $res41 = mysqli_query($con, $qry41);
                                                            $num411 = mysqli_num_rows($res41);
                                                            $row41 = mysqli_fetch_row($res41);
                                            
                                                            $qry42 = "SELECT SUM(paid_amount), SUM(card_amt) FROM `approval` WHERE `cust_id` = '{$row['person_id']}'";
                                                            $res42 = mysqli_query($con, $qry42);
                                                            $row42 = mysqli_fetch_row($res42);
                                            
                                                            if ($num411 == 0 || $num411 == "") {
                                                                $pd11 = $row42[0];
                                                            } else {
                                                                $pd11 = $row41[0];
                                                            }
                                            
                                                            $sum += $gsttot + $row42[1];
                                                            $ab = round($sum - $pd11, 2);
                                            
                                                            if ($ab != 0) {
                                                                ?>
                                                                <tr>
                                                                    <td><?php echo $i; ?></td>
                                                                    <td><?php echo "{$row['first_name']} {$row['last_name']} ({$row['person_id']})"; ?></td>
                                                                    <td><?php echo $row['phone_number']; ?></td>
                                                                    <?php
                                                                    if (is_nan($ab)) {
                                                                        $ab = 0;
                                                                    }
                                                                    if ($ab != 0 && !is_nan($ab)) {
                                                                        ?>
                                                                        <td><?php echo "Rs. " . number_format($ab, 2) . "/-"; ?></td>
                                                                    <?php } else { ?>
                                                                        <td><?php echo "Rs. " . $ab . "/-"; ?></td>
                                                                    <?php } ?>
                                                                    <td><a href="Approval_payment.php?cid=<?php echo $row['person_id']; ?>&amt=<?php echo $ab; ?>" target="_new"><b>Payment</b></a></td>
                                                                </tr>
                                                                <?php
                                                                $i++;
                                                                $total2 += $ab;
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </tbody>
                                                <tr>
                                                    <td colspan="4" align="right"><b>TOTAL AMOUNT:</b></td>
                                                    <td><?php echo "Rs. " . number_format($total2, 2) . "/-"; ?></td>
                                                </tr>
                                            </table>

            								  </div>
            							</div>
                                      </div>
                                    </div>
                                  </div>
                			  
                			  
                			  </div>
                						
                	    </div>
                	
                	 <?php include('footer.php');?>
                  </div>
        <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
</div>
<!-- container-scroller -->
<!-- plugins:js -->
<script src="../vendors/js/vendor.bundle.base.js">
</script>
<script src="../vendors/js/vendor.bundle.addons.js">
</script>
<!-- endinject -->
<!-- Plugin js for this page-->
<!-- End plugin js for this page-->
<!-- inject:js -->
<script src="../js/off-canvas.js">
</script>
<script src="../js/hoverable-collapse.js">
</script>
<script src="../js/misc.js">
</script>
<script src="../js/settings.js">
</script>
<script src="../js/todolist.js"></script>

<!-- End custom js for this page-->
<!-- video.js -->
<!--<script src="../js/data-table.js"></script>-->
<!--<script src="../js/data-table2.js"></script>-->
<script src="../js/select2.js"></script>

<!--<script src="../js/pdfmake.min.js"></script>-->
<!--<script src="../js/jszip.min.js"></script>-->
<script src="../js/buttons.print.min.js"></script>
<!--<script src="https://cdn.datatables.net/buttons/2.3.2/js/dataTables.buttons.min.js"></script>-->
<!--<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>-->
<!--<script src="https://code.jquery.com/jquery-3.5.1.js"></script>-->

<script>
//'excelHtml5',
		//	  'pdfHtml5'
    $('#tablelist').DataTable(
	{
		"order": [[ 0, "asc" ]],
		dom: 'Bfrtip',
		buttons: [
			 'copy', 'csv', 'excel', 'pdf', 'print' 
		]
	}
);
</script>
            
</body>
</html>
<?php  Closecon($con);  ?>
           