<?php include('top-header.php');?>
     <?php include('top-navbar.php');?>
            <!-- partial -->
            <div class="container-fluid page-body-wrapper">
                <!-- partial:partials/_settings-panel.html -->
                <!-- partial -->
                <!-- partial:partials/_sidebar.html -->
                <?php include('navbar.php');?>
                 
                <?php   $con = OpenSrishringarrCon();
                        $currentdate=Date("Y-m-d");
                        $a=date('Y-m-d', strtotime($currentdate. ' + 1 days'));
                        $b=date('Y-m-d', strtotime($currentdate. ' + 2 days'));
                        $c=date('Y-m-d', strtotime($currentdate. ' + 10 days'));
                        // echo $c;
                        // echo "<script>alert($currentdate);</script>";
                        ////echo "SELECT * FROM `phppos_rent` where  booking_status<>'Delivered' and delivery_date='$a' or delivery_date='$b' or delivery_date='$currentdate'   order by delivery_date ASC";
                        $day=date('d');
                        //echo "SELECT * FROM `phppos_rent` where (booking_status<>'Returned' or booking_status='Picked') order by delivery_date ASC";
                        if(isset($_POST['cmdret'])){
                          $r=mysqli_query($con,"SELECT * FROM `phppos_rent` where (booking_status='NULL' or booking_status='Picked') and delivery='".$_POST['return']."' order by pick_date DESC");
                        }else{
                          $r=mysqli_query($con,"SELECT * FROM `phppos_rent` where (booking_status='NULL' or booking_status='Picked') order by pick_date DESC");
                        }
                        $num=mysqli_num_rows($r);
                        
                        //echo "SELECT * FROM `phppos_rent` where  (delivery_date='$a' and (booking_status='' or booking_status='Picked')) or (delivery_date='$b' and (booking_status='' or booking_status='Picked')) or (delivery_date='$currentdate' and (booking_status='' or booking_status='Picked'))  order by delivery_date ASC";
                        	
                        // $r1=mysqli_query($con,"SELECT * FROM `phppos_rent` where  (pick_date<'$c' and  booking_status='Booked') or (pick_date='$currentdate' and  booking_status='Booked') order by pick_date ASC");
                        $qry = "SELECT * FROM `phppos_rent` where  (booking_status='Booked') order by pick_date ASC";
                        // echo $qry;
                        $r1 = mysqli_query($con,$qry);
                        $num=mysqli_num_rows($r1);	
                 ?>
                
                <!-- partial -->
                  <div class="main-panel">
                        <div class="content-wrapper">
                            <div class="page-header">
                                <div class="center">
                                    <h3 class="page-title" >Dashboard Page</h3>
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
                			    	    <form class="form-sample" id="forms" action="<?php $_SERVER['PHP_SELF'] ?>" method="POST" enctype="multipart/form-data">
                							<div class="row">
                						    	<div class="col-md-6">
                        							<div class="form-group row">
                        							  <label class="col-sm-3 col-form-label">Select Delivery</label>
                        							  <div class="col-sm-9">
                        								<select class="form-control" name="return" id="return">
                        									<option value=""> Select</option>
                        									<?php
                                                             
                                                            $qry=mysqli_query($con,"select distinct(delivery) from phppos_rent order by delivery ASC");
                                                            if(mysqli_num_rows($qry)>0){
                                                            while($qrro=mysqli_fetch_array($qry))
                                                            {
                                                                if($qrro[0]!=""){
                                                            ?>
                                                            <option value="<?php echo $qrro[0]; ?>" <?php if(isset($_POST['return'])){ if($_POST['return']==$qrro[0]){ echo 'selected';} }?>><?php echo $qrro[0]; ?></option>
                                                            <?php
                                                            }} }
                                                           
                                                            ?>
                        								</select>
                        							  </div>
                        							</div>
                						         </div>
                						         <div class="col-md-6">
                						             <button class="btn btn-primary mr-2" type="submit" name="cmdret">Submit</button>
                						         </div>       
                						    </div>
                    						
                					  </form>
                				    </div>							  
                                </div>
                			 </div>
                			 <div class="col-12 grid-margin">
                			    <div class="card">
                                    <div class="card-body">
                                      <h4 class="card-title"><strong> Alert : Return Date</strong></h4>
                                      <p class="page-description"></p>
                                      <div class="row">
                                        <div class="col-12">
            								  <div class="table-responsive">
            									<table id="order-listing" class="table">
            									  <thead>
            									 
            										<tr>
            											<th>Bill No.</th>
            											<th>Return By</th>
            											<th>Return Date</th>
            											<th>Name</th>
            											<th>Item--Qty</th>
            											
            																	  
            										</tr>
            									  </thead>
            									  <tbody>
            									      <?php 
                                                        $qty[]=array();
                                                        if(mysqli_num_rows($r)>0){
                                                        while($r2=mysqli_fetch_row($r)){
                                                        
                                                        $cust=$r2[1];
                                                        //echo $cust."hi";
                                                        $customer=mysqli_query($con,"SELECT * FROM `phppos_people` where person_id='$cust'");
                                                        $cust1=mysqli_fetch_row($customer);
                                                        
                                                        $bill=mysqli_query($con,"SELECT * FROM `order_detail` where  bill_id='$r2[0]'");
                                                        //$bill11=mysql_fetch_row($bill);
                                                        
                                                        $bill1=mysqli_query($con,"SELECT * FROM `order_detail` where  bill_id='$r2[0]' and is_status = 0");
                                                        $bill11=mysqli_fetch_row($bill1);
                                                        if($bill11[0]!='')
                                                        {
                                                        ?>
                                                        
                                                        <tr>
                                                        <td><a href="reports/rent_report_detail.php?id=<?php echo $bill11[0]; ?>" target="_blank"><?php echo $bill11[0]; ?></a></td>
                                                        <td><?php echo $r2[7]; ?></td>
                                                        <td><?php if(isset($r2[12]) and $r2[12]!='0000-00-00') echo date('d/m/Y',strtotime($r2[12]))." ".$r2[22]; ?></td>
                                                        <td><?php echo $cust1[0]." ".$cust1[1]; ?></td>
                                                        <td>
                                                        
                                                        <?php while($bill1 = mysqli_fetch_row($bill)){
                                                        $item=$bill1[1];
                                                        $item1=mysqli_query($con,"SELECT * FROM `phppos_items` where name='$item'");
                                                        $item2=mysqli_fetch_row($item1);?>
                                                        <?php echo $item2[0]."--".$bill1[9]."<br>"; } ?></td>
                                                        
                                                        </tr>
                                                        
                                                        <?php }}} ?>
            										
            									  </tbody>
            									</table>
            								  </div>
            								</div>
            							</div>
            						   </div>
            						</div>  
            					</div>
            					<div class="col-12 grid-margin">
            						<div class="card">
                                        <div class="card-body">
                                          <h4 class="card-title"><strong> Alert : Pick Date</strong></h4>
                                          <p class="page-description"></p>
                                          <div class="row">
                                            <div class="col-12">	  
            								  <div class="table-responsive">
            									<table class="table" id="order-listing2">
            									  <thead>
            									 
            										<tr>
            											<th>Bill No.</th>
            											<th>Pick By</th>
            											<th>Pick Date</th>
            											<th>Name</th>
            											<th>Item--Qty</th>
            											<th>Picked</th>
            																	  
            										</tr>
            									  </thead>
            									  <tbody>
            										    <?php 
            										   if(mysqli_num_rows($r1)>0) {
            										    while($r3=mysqli_fetch_row($r1)){

                                                            $cust=$r3[1];
                                                            //echo $cust."hi";
                                                            $customer=mysqli_query($con,"SELECT * FROM `phppos_people` where person_id='$cust'");
                                                            $cust1=mysqli_fetch_row($customer);
                                                            
                                                            $bill=mysqli_query($con,"SELECT * FROM `order_detail` where  bill_id='$r3[0]'");
                                                            //$bill11=mysql_fetch_row($bill);
                                                            
                                                            $bill1=mysqli_query($con,"SELECT * FROM `order_detail` where  bill_id='$r3[0]'");
                                                            $bill11=mysqli_fetch_row($bill1);
                                                            ?>
                                                            
                                                            <tr>
                                                            <td><a href="reports/rent_report_detail.php?id=<?php echo $bill11[0]; ?>" target="_blank"><?php echo $bill11[0]; ?></a></td>
                                                            <td><?php echo $r3[6]; ?></td>
                                                            <td><?php if(isset($r3[11]) and $r3[11]!='0000-00-00'){ echo date('Y/m/d',strtotime($r3[11]));} ?></td>
                                                            <td><?php echo $cust1[0]." ".$cust1[1]; ?></td>
                                                            <td>
                                                            
                                                            <?php while($bill1 = mysqli_fetch_row($bill)){
                                                            $item=$bill1[1];
                                                            $item1=mysqli_query($con,"SELECT * FROM `phppos_items` where name='$item'");
                                                            $item2=mysqli_fetch_row($item1);?>
                                                            <?php echo $item2[0]."--".$bill1[9]."<br>"; } ?></td>
                                                            <td>
                                                            
                                                            <?php
                                                            $p=0;
                                                            $bill2=mysqli_query($con,"SELECT * FROM `order_detail` where  bill_id='$r2[0]'");
                                                            while($bill12=mysqli_fetch_row($bill2)){
                                                            
                                                            $qt=mysqli_query($con,"select * from phppos_items where name='$bill12[1]'");
                                                            $qt1=mysqli_fetch_row($qt);
                                                            ///echo $qt1[7];
                                                            if($qt1[7]<=0){
                                                            $p=1;
                                                            }
                                                            }
                                                            ////echo "<br/>".$p;
                                                            
                                                            ?><a class="btn btn-info" href="reports/deliver.php?bid=<?php echo $bill11[0]; ?>" <?php if($p==1){ ?>disabled="disabled" <?php } else { } ?>>Picked</a>
                                                            <?php ?>
                                                            </tr>
                                                            
                                                            <?php } }
                                                             Closecon($con);
                                                            ?>
            									  </tbody>
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
<script src="vendors/js/vendor.bundle.base.js">
</script>
<script src="vendors/js/vendor.bundle.addons.js">
</script>
<!-- endinject -->
<!-- Plugin js for this page-->
<!-- End plugin js for this page-->
<!-- inject:js -->
<script src="js/off-canvas.js">
</script>
<script src="js/hoverable-collapse.js">
</script>
<script src="js/misc.js">
</script>
<script src="js/settings.js">
</script>
<script src="js/todolist.js"></script>

<!-- End custom js for this page-->
<!-- video.js -->
<script src="js/data-table.js"></script>
<script src="js/data-table2.js"></script>
<script src="js/select2.js"></script>
            
</body>
</html>
           