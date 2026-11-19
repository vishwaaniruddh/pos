<?php

//if(isset($_SESSION['username'])){ 
 //   $id = $_SESSION['userid'];
    
  /*  $user = "select * from loginusers where id=".$id;
    $usersql = mysqli_query($con,$user);
    $usersql_result = mysqli_fetch_assoc($usersql);
    
    $permission = $usersql_result['permission'];
    $permission = explode(',',$permission);
    sort($permission);

$cpermission=json_encode($permission);
$cpermission=str_replace( array('[',']','"') , ''  , $cpermission);
$cpermission=explode(',',$cpermission);
$cpermission = "'" . implode ( "', '", $cpermission )."'";


    $mainmenu = [];
    foreach($permission as $key=>$val){
        
        
        $sub_menu_sql = mysqli_query($con,"select * from sub_menu where id='".$val."' and status=1");
        $sub_menu_sql_result = mysqli_fetch_assoc($sub_menu_sql);
        
         $mainmenu[] = $sub_menu_sql_result['main_menu'];
        
    }
    
$mainmenu    = array_unique($mainmenu);*/

?>

<nav class="sidebar sidebar-offcanvas bt" id="sidebar">
                    <ul class="nav">
                        <li class="nav-item">
							<a class="nav-link" data-toggle="collapse" href="/pos/home_dashboard.php" aria-expanded="false" aria-controls="dashboard">
							  <i class="fas fa-tachometer-alt menu-icon"></i>
							  <span class="menu-title">Dashboard</span>
							  
							</a>
						</li>	
						<li class="nav-item icons-list">
                            <a class="nav-link" href="/pos/reports/custLst.php">
                                <i class="fas fa-user menu-icon">
                                </i>
                                <span class="menu-title">
                                    Customer List
                                </span>
                            </a>
                        </li>
                        
						<li class="nav-item icons-list">
                            <a class="nav-link" href="/pos/sku-price.php">
                                <i class="fas fa-user menu-icon">
                                </i>
                                <span class="menu-title">
                                    Sku Price
                                </span>
                            </a>
                        </li>
						
						<li class="nav-item">
							<a class="nav-link" data-toggle="collapse" href="#usermodule" aria-expanded="false" aria-controls="usermodule">
							  <i class="fas fa-archive menu-icon"></i>
							  <span class="menu-title">Users</span>
							  <i class="menu-arrow"></i>
							</a>
							<div class="collapse" id="usermodule">
							  <ul class="nav flex-column sub-menu">
							    <li class="nav-item"> <a class="nav-link" href="/pos/adduser.php">Add New User</a></li>
							    <li class="nav-item"> <a class="nav-link" href="/pos/viewuser.php">view User</a></li>
						    </ul>
						    </div>
						    </li>
						
						
                        <li class="nav-item">
							<a class="nav-link" data-toggle="collapse" href="#graphical_reports" aria-expanded="false" aria-controls="graphical_reports">
							  <i class="fas fa-archive menu-icon"></i>
							  <span class="menu-title">Reports</span>
							  <i class="menu-arrow"></i>
							</a>
							<div class="collapse" id="graphical_reports">
							  <ul class="nav flex-column sub-menu">
							    <li class="nav-item"> <a class="nav-link" href="/pos/reports/approval.php">Approval</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/reports/app_return.php">Approval Return</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/reports/approval_return_report.php">Approval Return Report</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/reports/custsales_report.php">Consolidate Sales Report (Customer Wise)</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/reports/item_sales_report.php">Consolidate Sales Report (Item Wise)</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/reports/catagorysales_report.php">Consolidate Sales Report (Category Wise)</a></li>
							    <li class="nav-item"> <a class="nav-link" href="/pos/reports/custrent_report1.php">Consolidate Rent Report (Customer Wise)</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/reports/item_rent_report.php">Consolidate Rent Report (Item Wise)</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/reports/catagoryrent_report.php">Consolidate Rent Report (Category Wise)</a></li>
							    <li class="nav-item"> <a class="nav-link" href="/pos/reports/rent.php">Rent</a></li>
						        <li class="nav-item"> <a class="nav-link" href="/pos/reports/rent_return_new.php">Rent Return</a></li>
						        <li class="nav-item"> <a class="nav-link" href="/pos/reports/bookingreport.php">Booking Status</a></li>
						        <li class="nav-item"> <a class="nav-link" href="/pos/reports/rent1.php">Scheme</a></li>
						        <li class="nav-item"> <a class="nav-link" href="/pos/reports/rent_return1.php">Scheme Return</a></li>
						        <li class="nav-item"> <a class="nav-link" href="/pos/reports/appReport.php">Approval Reports</a></li>
						        <li class="nav-item"> <a class="nav-link" href="/pos/reports/saleReport.php">Sales Reports</a></li>
						        <li class="nav-item"> <a class="nav-link" href="/pos/reports/rentReport.php">Rent Reports</a></li>
						        <li class="nav-item"> <a class="nav-link" href="/pos/reports/rentReport1.php">Scheme Report</a></li>
						        <li class="nav-item"> <a class="nav-link" href="/pos/reports/stock.php">Stock Report</a></li> 
						        <li class="nav-item"> <a class="nav-link" href="/pos/reports/commReport.php">Commission Reports</a></li>
						        <li class="nav-item"> <a class="nav-link" href="/pos/reports/balance_amount.php">Customer Balance Amount Reports</a></li>
						        <li class="nav-item"> <a class="nav-link" href="/pos/reports/rent_amount.php">Customer Rent Balance Amount Reports</a></li>
							  </ul>
							</div>
						</li>
						<li class="nav-item">
							<a class="nav-link" data-toggle="collapse" href="#purchase" aria-expanded="false" aria-controls="purchase">
							  <i class="fas fa-shopping-basket menu-icon"></i>
							  <span class="menu-title">Purchase</span>
							  <i class="menu-arrow"></i>
							</a>
							<div class="collapse" id="purchase">
							  <ul class="nav flex-column sub-menu">
							    <li class="nav-item"> <a class="nav-link" href="/pos/purchase/purchase_entry.php">Supplier's Bill Entry</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/purchase/view_bills.php">View Supplier's Bill</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/purchase/view_payments.php">View Supplier's Payments</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/purchase/add_supplier.php">Add Supplier</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/purchase/view_supplier.php">View Supplier's </a></li>
								
							  </ul>
							</div>
						  </li>
						  <li class="nav-item">
							<a class="nav-link" data-toggle="collapse" href="#bank" aria-expanded="false" aria-controls="bank">
							  <i class="fas fa-piggy-bank menu-icon"></i>
							  <span class="menu-title">Bank</span>
							  <i class="menu-arrow"></i>
							</a>
							<div class="collapse" id="bank">
							  <ul class="nav flex-column sub-menu">
							    
								<li class="nav-item"> <a class="nav-link" href="/pos/purchase/bank_entry.php">Bank Transactions</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/purchase/bank_report.php">Bank Report</a></li>
								<li class="nav-item"> <a class="nav-link" href="purchase/bank_concile.php">Reconcile Bank Transactions</a></li>
							  </ul>
							</div>
						  </li>
						 <li class="nav-item">
							<a class="nav-link" data-toggle="collapse" href="#gst" aria-expanded="false" aria-controls="gst">
							  <i class="fas fa-file menu-icon"></i>
							  <span class="menu-title">GST Reports</span>
							  <i class="menu-arrow"></i>
							</a>
							<div class="collapse" id="gst">
							  <ul class="nav flex-column sub-menu">
							    
								<li class="nav-item"> <a class="nav-link" href="/pos/reports/approvalnew.php">Sales</a></li>
								<li class="nav-item"> <a class="nav-link" href="/pos/reports/rentnew.php">Rent</a></li>
							
							  </ul>
							</div>
						  </li>
						  <li class="nav-item">
							<a class="nav-link" data-toggle="collapse" href="#itemcode" aria-expanded="false" aria-controls="itemcode">
							  <i class="fa fa-list-alt menu-icon"></i>
							  <span class="menu-title">Itemcode</span>
							  <i class="menu-arrow"></i>
							</a>
							<div class="collapse" id="itemcode">
							  <ul class="nav flex-column sub-menu">
							    
								<li class="nav-item"> <a class="nav-link" href="/pos/itemcode_details.php">Itemcode Details</a></li>
							
							  </ul>
							</div>
						  </li>
						<li class="nav-item icons-list">
                            <a class="nav-link" href="/pos/logout.php">
                                <i class="fa fa-sign-out-alt menu-icon">
                                </i>
                                <span class="menu-title">
                                    Logout
                                </span>
                            </a>
                        </li>
                    </ul>
                   <!-- <ul class="nav bt">
                         <li class="nav-item nav-settings d-none d-lg-block" style="margin: 0 auto;margin-top: 3%;">
                            <button class="btn btn-primary btn-rounded btn-icon" type="button">
                                <i class="fa fa-bell">
                                </i>
                            </button>
                        </li> 
                        
                    </ul> -->
                   <!-- <ul class="nav bt">
                        <li class="nav-item nav-settings d-none d-lg-block" style="margin: 0 auto;margin-top: 3%;">
                            <h5 class="text-white">
                                Wednesday 5:40 pm
                            </h5>
                            <h5 class="text-white">
                                25 August, 2021
                            </h5>
                        </li>
                    </ul> -->
                </nav>
			