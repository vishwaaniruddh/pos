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
                        								<select class="form-control"  name="alph" id="alph">
                        									<option value=""> Select</option>
                        									<?php
                                                             
                                                            foreach (range('A', 'Z') as $char) {
                                                                echo $char . "\n";
                                                                ?>
                                                            <option value="<?php echo $char; ?>" <?php if($_POST['alph'] == $char) {echo "selected"; }  ?>>
                                                                <?php echo $char; ?></option>
                                                            <?php
                                                            }
                                                           
                                                            ?>
                        								</select>
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
                                    $qry = "SELECT * FROM `phppos_people` WHERE `person_id` not in (SELECT `person_id` FROM `phppos_suppliers`)  ";
                                
                                    if ($alph != "") {
                                        $qry .= " and first_name like '" . $alph . "%'";
                                    }
                                
                                    $qry .= " ORDER BY `phppos_people`.`first_name` ASC";
                                
                                    // echo $qry."   ";
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
            										    
            										    
        										        if(mysqli_num_rows($res)>0) {
        										            
        										        while($row=mysqli_fetch_row($res)){

                                                        $sum = 0;
                                                        $gsttot = 0;
                                                        $pd = 0;
                                                        $s1 = 0;
                                                        $ba = 0;
                                                        $na = 0;
                                                        $ra = 0;
                                                        
                                                        $qry1 = "SELECT * FROM  `approval` where cust_id='$row[11]' ";
                                                        $res1 = mysqli_query($con, $qry1);
                                                        $num1 = mysqli_num_rows($res1);
                                                        $row1 = mysqli_fetch_row($res1);
                                                        
                                                        
                                                        $res2 = mysqli_query($con, "SELECT sum(paid_amount) FROM  `approval` where bill_id ='$row1[0]'");
                                                        $num2 = mysqli_num_rows($res2);
                                                        $row2 = mysqli_fetch_row($res2);
                                                        
                                                        // print_r($row2);
                                                        $qry3 = "SELECT sum(`amount`) FROM `approval_detail` WHERE bill_id ='$row1[0]'";
                                                        $res3 = mysqli_query($con, $qry3);
                                                        $row3 = mysqli_fetch_row($res3);
                                                        $a = 0;
                                                        $a1 = 0;
                                                        // print_r($row3);
                                                        
                                                        $qry4 = "SELECT *  FROM `approval_detail` WHERE bill_id ='$row1[0]'";
                                                        $res4 = mysqli_query($con, $qry4);
                                                        $gsttotiv = 0;
                                                        while ($row4 = mysqli_fetch_row($res4)) {
                                                            
                                                            $a = round(($row4[7] / $row4[2]) * $row4[4]);
                                                            $a1 += $a;
                                                            $ba += $row4[7];
                                                        }
                                                        
                                                        $gsttotqr = mysqli_query($con, "select sum(sgstamt),sum(cgstamt),sum(igstamt) from approval_detail  WHERE bill_id ='$row1[0]'");
                                                        $gsttotrws = mysqli_fetch_array($gsttotqr);
                                                        $gsttot = $gsttot + $gsttotrws[0] + $gsttotrws[1] + $gsttotrws[2];
                                                
                                                        $na = $ba;
                                                        $s1 = ($ba - $a1);
                                                        $s = $row3[0] - $row2[0];
                                                        $sum += $s1;
                                                        
                                                        // var_dump($ba);
                                                        // if($num1>0){
                                                        //     while ($row1 = mysqli_fetch_row($res1)) {
                                                        //         $pd = 0;
                                                        //         $s1 = 0;
                                                        
                                                        //         $ba = 0;
                                                        //         $na = 0;
                                                        //         $ra = 0;
                                                        
                                                        //         $qry2 = "SELECT sum(paid_amount) FROM  `approval` where bill_id ='$row1[0]'";
                                                        //         $res2 = mysqli_query($con, $qry2);
                                                        //         $num2 = mysqli_num_rows($res2);
                                                        //         $row2 = mysqli_fetch_row($res2);
                                                        
                                                        //         $qry3 = "SELECT sum(`amount`) FROM `approval_detail` WHERE bill_id ='$row1[0]'";
                                                        //         $res3 = mysqli_query($con, $qry3);
                                                        //         $row3 = mysqli_fetch_row($res3);
                                                        //         $a = 0;
                                                        //         $a1 = 0;
                                                        //         $qry4 = "SELECT *  FROM `approval_detail` WHERE bill_id ='$row1[0]'";
                                                        //         $res4 = mysqli_query($con, $qry4);
                                                        //         $gsttotiv = 0;
                                                        //         while ($row4 = mysqli_fetch_row($res4)) {
                                                                    
                                                        //             $a = round(($row4[7] / $row4[2]) * $row4[4]);
                                                        //             $a1 += $a;
                                                        //             $ba += $row4[7];
                                                        //         }
                                                        
                                                        //         $gsttotqr = mysqli_query($con, "select sum(sgstamt),sum(cgstamt),sum(igstamt) from approval_detail  WHERE bill_id ='$row1[0]'");
                                                        //         $gsttotrws = mysqli_fetch_array($gsttotqr);
                                                        //         $gsttot = $gsttot + $gsttotrws[0] + $gsttotrws[1] + $gsttotrws[2];
                                                        
                                                        //         $na = $ba;
                                                        //         $s1 = ($ba - $a1);
                                                        //         $s = $row3[0] - $row2[0];
                                                        //         $sum += $s1;
                                                        //     }
                                                        // }
                                                       
                                                        
                                                        $qry41 = "SELECT sum(amount) FROM `paid_amount` WHERE `bill_id`='$row[11]'";
                                                        $res41 = mysqli_query($con, $qry41);
                                                        $num411 = mysqli_num_rows($res41);
                                                        $row41 = mysqli_fetch_row($res41);
                                                        //echo $id."/".$num411."<br/>";
                                                    
                                                        $qry42 = "SELECT SUM( paid_amount ),sum(card_amt) FROM  `approval` WHERE  `cust_id` ='$row[11]'";
                                                        $res42 = mysqli_query($con, $qry42);
                                                        $row42 = mysqli_fetch_row($res42);
                                                    
                                                        if ($num41 == 0 || $num41 == "") {
                                                            //  echo "ok1";
                                                            $pd11 = $row42[0];
                                                    
                                                        } else {
                                                    
                                                            // echo "ok2";
                                                            $pd11 = $row41[0];
                                                    
                                                        }
                                                            //echo "Cust=".$row[11]."/".$sum."-".$pd11."<br/>";
                                                        $sum += $gsttot + $row42[1];
                                                        $ab = round($sum - $pd11, 2);
                                                        if ($ab!= 0) {
                                                            
                                                    
                                                    ?>
                                                            
                                                    <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $row[0] . " " . $row[1] . "(" . $row[11] . ")"; ?></td>
                                                    <td><?php echo $row[2]; ?></td>
                                                    <td><?php echo "Rs. " . round($ab, 2) . "/-"; ?></td>
                                                    <td><a href="Approval_payment.php?cid=<?php echo $row[11]; ?>&amt=<?php echo $ab; ?>" target="_new" ><b>Payment</b></a></td>
                                                    
                                                    </tr>
                                                            
                                                            <?php
                                                                
        										                $i++;
        										                 $total2 += $ab;
        										        } } }
                                                            
                                                            ?>
            									  </tbody>
            									  <tr><td colspan=4 align='right' ><b>TOTAL AMOUNT: </b></td><td><?php echo "Rs. " . round($total2, 2) . "/-"; ?></td></tr>
            									  
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
		"order": [[ 0, "desc" ]],
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
           