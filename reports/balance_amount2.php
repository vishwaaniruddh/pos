<?php include('../top-header.php');?>
     <?php include('../top-navbar.php');?>
            <!-- partial -->
            <div class="container-fluid page-body-wrapper">
                <?php include('../navbar.php');
                    $con = OpenSrishringarrCon();
                
                
        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);



                ?>
                
                <!-- partial -->
                  <div class="main-panel">
                        <div class="content-wrapper">
                            <div class="page-header">
                                <div class="center">
                                    <h3 class="page-title" >New Version - Bill Wise - Customer Balance Amount Report(Approval & Sales)</h3>
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
                        								<input type="text" name="alph" id="alph" required/>
                        								
                        							  </div>
                        							</div>
                						         </div>
                						         
                						         <div class="col-md-6">
                						             <button class="btn btn-primary mr-2" type="submit" name="submit">Submit</button>
                						         </div>       
                						    </div>
                    						
                					  </form>
                					  
                					  <br>
                					  <a href="./balance_amount.php">Goto Old Version </a>
                					  
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
                                

                                    // echo ' res = ' . $qry ;
                                    $res = mysqli_query($con, $qry);
                                    $num = mysqli_num_rows($res);
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

            																	  
            										</tr>
            									  </thead>
            									  <tbody>
        										    <?php 
            										    $i = 1;
        										        if(isset($res) && mysqli_num_rows($res)>0) {
            										        while($row=mysqli_fetch_array($res)){
                                                            $sum=0;

                                                            $cust_id= $row['person_id'];
                                                            $qry1="SELECT * FROM  `approval` where cust_id='".$cust_id."' ";
                                                            $res1=mysqli_query($con,$qry1);                
                                                            $num1=mysqli_num_rows($res1);
                                                            
                                                            $gsttot=0;
                                                            while($row1=mysqli_fetch_array($res1))
                                                            {
                                                                $pd=0;
                                                                $s1=0;	
                                                                $ba=0;
                                                                $na=0;	
                                                                $ra=0;
                                                                
                                                                $qry2="SELECT sum(paid_amount) FROM  `approval` where bill_id ='$row1[0]'";
                                                                $res2=mysqli_query($con,$qry2);                
                                                                $num2=mysqli_num_rows($res2);
                                                                $row2=mysqli_fetch_row($res2);


                                                                

                                                                $a=0;
                                                                $a1=0;
                                                                
                                                               $qry4="SELECT *  FROM `approval_detail` WHERE bill_id ='$row1[0]'";
                                                                $res4=mysqli_query($con,$qry4);
                                                                if(mysqli_num_rows($res4)>0) {
                                                                    $gsttotiv=0;	
                                                                    while($row4=mysqli_fetch_array($res4)){
                                                                        // $a=round(((float)$row4['amount']/((float)$row4['qty'])*(float)$row4['return_qty']));
                                                                        
                                                                        $amount = (float) $row4['amount'];
                                                                        $qty = (float) $row4['qty'];
                                                                        $return_qty = (float) $row4['return_qty'];
                                                                        
                                                                        if ($qty != 0) {
                                                                            $a = round(($amount / $qty) * $return_qty);
                                                                        } else {
                                                                            $a = 0; // or handle the case appropriately
                                                                        }

                                                                        $a1 = (float)$a1 + (float)$a;
                                                                        $ba = (float)$ba + (float)$row4['amount'];
                                                                    }   
                                                                    $qry3="SELECT sum(amount) FROM `approval_detail` WHERE bill_id ='$row1[0]'";
                                                                    $res3=mysqli_query($con,$qry3);
                                                                    $row3=mysqli_fetch_row($res3);
                                                                }
                                                                
                                                                $gsttotqr=mysqli_query($con,"select sum(sgstamt),sum(cgstamt),sum(igstamt) from approval_detail  WHERE bill_id ='$row1[0]'");
                                                                $gsttotrws=mysqli_fetch_array($gsttotqr);
                                                                $gsttot=(float)$gsttot+(float)$gsttotrws[0]+(float)$gsttotrws[1]+(float)$gsttotrws[2];
                                                                
                                                                $na=$ba;
                                                                $s1=((float)$ba-(float)$a1);
                                                                if(mysqli_num_rows($res4)>0) {
                                                                    $s=(float)$row3[0]-(float)$row2[0];
                                                                } else {
                                                                    $s=$row2[0];
                                                                }
                                                                
                                                                $sum = (float)$sum + (float)$s1;  
                                                                
                                                                // echo $sum."<br>";
                                                            }
                                                            
                                                            // echo '<br>';
                                                            $qry41="SELECT sum(amount) FROM `paid_amount` WHERE `bill_id`='$row[11]'";
                                                            $res41=mysqli_query($con,$qry41);
                                                            $num41=mysqli_num_rows($res41);
                                                            $row41=mysqli_fetch_row($res41);
                                                        
                                                        
                                                        
                                                         $qry42="SELECT SUM( paid_amount ),sum(card_amt) FROM  `approval` WHERE  `cust_id` ='$row[11]'";
                                                        
                                                        
                                                            $res42=mysqli_query($con,$qry42);
                                                            $row42=mysqli_fetch_row($res42);
                                                            
                                                            if($num41==0 || $num41=="") 
                                                            { 
                                                                $pd11 = $row42[0]; 
                                                            }else{ 
                                                                $pd11 = $row41[0]; 
                                                            }
                                                            

                                                            $sum = (float)$sum + (float)$gsttot+(float)$row42[1];
                                                            $ab=round((int)$sum-(int)$pd11,2);
                                                            
                                                            
                                                            
                                                            if($ab!=0){
                                                                
                                                        
                                                        ?>
                                                                
                                                        <tr>
                                                        <td><?php echo $i; ?></td>
                                                        <td><?php echo $row[0] . " " . $row[1] . "(" . $row[11] . ")"; ?></td>
                                                        <td><?php echo $row[2]; ?></td>
                                                        
                                                        
                                                        
                                                        
                                                       <td>
                                                           <table border="1" cellpadding="0" cellspacing='0'>
                                                               
                                                           <tr>
                                                               <th>Bill ID</th>
                                                               <th>Paid amount</th>
                                                               <th>Balance Amount</th>
                                                               <th>Payment</th>
                                                           </tr>
                                                           <?php 
                                                           $totalbalanceAmount = $totalamountTotal = $totalthisPaidAmount = 0 ; 
                                                           
                                                        //   echo "select * from approval where  paid_amount<>amountTotal and cust_id='".$row[11]."'" ;
                                                           $balancesql = mysqli_query($con,"select * from approval where  paid_amount<>amountTotal and cust_id='".$row[11]."'");
                                                           while($balancesqlResult = mysqli_fetch_assoc($balancesql)){
                                                               
                                                            //   var_dump($balancesqlResult);
                                                               $thisBillID =  $balancesqlResult['bill_id'];
                                                               $new_bill_number = $balancesqlResult['new_bill_number'];
                                                                $thisPaidAmount = $balancesqlResult['paid_amount'];
                                                            //   echo '<br />';
                                                               $amountTotal = $balancesqlResult['amountTotal'];
                                                               
                                                               $thisBalanceAmount = (float)$amountTotal - (float)$thisPaidAmount ; 
                                                               if($thisBalanceAmount!=0){ ?>
                                                               
                                                               
                                                               <tr>
                                                                   <td class="thisone"><a href="./sales_report_detail.php?id=<?php echo $thisBillID ; ?>" target="_blank"><?php echo $new_bill_number ? $new_bill_number :  $thisBillID   ; ?></a> <?php echo $new_bill_number ; ?></td>
                                                                   <td><?php echo $thisPaidAmount  ;  ?></td>
                                                                   <td><?php echo $thisBalanceAmount ; ?></td>
                                                                   <td>
                                                                       <a href="Approval_payment_new.php?cid=<?php echo $row[11]; ?>&amt=<?php echo $thisBalanceAmount; ?>&bill_id=<?php echo $thisBillID; ?>" target="_new" ><b>Payment</b></a>
                                                                   </td>
                                                               </tr>
                                                               
                                                                   
                                                               <?php 
                                                               
                                                               $totalthisPaidAmount = $totalthisPaidAmount + $thisPaidAmount ; 
                                                               $totalamountTotal = (float)$totalamountTotal + (float)$amountTotal ; 
                                                                $totalbalanceAmount = $totalbalanceAmount + $thisBalanceAmount ;
                                                                
                                                               } ?>
                                                               
                                                               
                                                               <?php 
                                                                
                                                           }
                                                           
                                                           
                                                           
                                                           
                                                           ?>

                                                           
                                                           </table>
                                                       </td>
                                                        </tr>
                                                                <?php
            										                $i++;
            										                 $total2= (float)$total2 + (float)$ab;
            										      //  } else { 
            										      //  }       
            										        } 
        										        }
        										        }
                                                            ?>
            									  </tbody>
            									  <tr><td colspan=4 align='right' ><b>TOTAL AMOUNT: </b></td><td><?php echo "Rs. " . round($totalbalanceAmount, 2) . "/-"; ?></td></tr>
            									  
            									</table>
            								  </div>
            							</div>
                                      </div>
                                    </div>
                                  </div>
                			  
                			  
                			  </div>
                						
                	    </div>
                	
                	 
                	 <?php include('../footer.php');?>
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

           