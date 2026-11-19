<?php include('top-header.php');?>
<script
  src="https://code.jquery.com/jquery-3.7.1.min.js"
  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
  crossorigin="anonymous"></script>
  
  
  
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
                        //echo $c;
                        ////echo "SELECT * FROM `phppos_rent` where  booking_status<>'Delivered' and delivery_date='$a' or delivery_date='$b' or delivery_date='$currentdate'   order by delivery_date ASC";
                        $day=date('d');
                        //echo "SELECT * FROM `phppos_rent` where (booking_status<>'Returned' or booking_status='Picked') order by delivery_date ASC";
                        if(isset($_POST['cmdret'])){
                          $r=mysqli_query($con,"SELECT * FROM `phppos_rent` where (booking_status='NULL' or booking_status='Picked') and delivery='".$_POST['return']."' order by bill_id DESC");
                        }else{
                          $r=mysqli_query($con,"SELECT * FROM `phppos_rent` where (booking_status='NULL' or booking_status='Picked') order by bill_id DESC");
                        }
                        $num=mysqli_num_rows($r);
                        
                        //echo "SELECT * FROM `phppos_rent` where  (delivery_date='$a' and (booking_status='' or booking_status='Picked')) or (delivery_date='$b' and (booking_status='' or booking_status='Picked')) or (delivery_date='$currentdate' and (booking_status='' or booking_status='Picked'))  order by delivery_date ASC";
                        	
                        // $r1=mysqli_query($con,"SELECT * FROM `phppos_rent` where  (pick_date<'$c' and  booking_status='Booked') or (pick_date='$currentdate' and  booking_status='Booked') order by pick_date ASC");
                        $r1=mysqli_query($con,"SELECT * FROM `phppos_rent` where  (booking_status='Booked') order by pick_date ASC");
                        
                        $num=mysqli_num_rows($r1);	
                 ?>
                
                <!-- partial -->
                  <div class="main-panel">
                        <div class="content-wrapper">
                            <h3>Search Price By SKU</h3>
                            
                              <div class="container_s">
                            		<form action="./sku-price.php?sku=<? echo  $_REQUEST['query'];?>" method="POST" class="search-box" id="search_form" >
                            			<input type="text" name="query" class="form-control" placeholder="Enter SKU" id="query">
                            			<input type="submit" class="search-box-btn" id="search" style="position:relative;" />
                            		</form>
                              </div>
                            

<div id="searchResult">
    
    <? $sku = $_REQUEST['query']; ?>
    <br />
    
    <br />
    <br />
    
    
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>

     <div id="dataContainer" class="row rowFlexMargin wrapper"></div>
     
     
     <script>
$(document).ready(function() {
  var currentPage = 1;
  var itemsPerPage = 20; // Number of items to display per page

  // Show loading overlay
  $('#loadingOverlay').show();

  $.ajax({
    url: "search1.php?query=<? echo $sku; ?>",
    dataType: 'json',
    success: function(response) {
      var data = response;
      console.log(response);

      var totalItems = data.length;
      var totalPages = Math.ceil(totalItems / itemsPerPage);

      function displayItems(page) {
        var startIndex = (page - 1) * itemsPerPage;
        var endIndex = startIndex + itemsPerPage;
        var html = '';

        for (var i = startIndex; i < endIndex && i < totalItems; i++) {
          var product = data[i];
          // Create the HTML for each product
          html += '<div class="col-xs-6 col-md-4 col-lg-3 ftco-animate fadeInUp ftco-animated product_col">';
          html += '<div class="product">';
        

          html += '<h2 style="display:block;">' + product.type + '</h2>';
          html += '<hr style="margin-top: 0rem; margin-bottom: 0.3rem;">';
          html += '<div class="subpart">';
          html += '<p style="display:block;">MRP ₹ ' + product.mrp + '</p>';
          html += '<h6 style="color:red; font-size: 13px; text-decoration: underline; font-weight: 700;">SKU : <a href="' + product.link + '">' + product.sku + '</a></h6>';
          html += '<div class="d-flex">';

          html += '<div class="pricing">';
          html += '<p class="price">';
          html += '<span class="mr-2 price-dc">Rent ₹ <strong>' + product.addedRentPrice + '</strong> for 3 Days</span>';
          html += '<br>';
          html += '<span class="price-sale">Refundable Deposit ₹ <strong>' + product.deposit + '</strong></span>';
          html += '<br>';
          html += '<span class="price-sale">Selling Price ₹ <strong>' + product.lastSellingPrice + '</strong></span>';
          html += '</p>';
          html += '</div>';


          html += '<div class="rating">';
          html += '<p class="text-right">';
          html += '<a href="#"><span class="ion-ios-star-outline"></span></a>';
          html += '<a href="#"><span class="ion-ios-star-outline"></span></a>';
          html += '<a href="#"><span class="ion-ios-star-outline"></span></a>';
          html += '<a href="#"><span class="ion-ios-star-outline"></span></a>';
          html += '<a href="#"><span class="ion-ios-star-outline"></span></a>';
          html += '</p>';
          html += '</div>';
          

          html += '</div>';
          html += '</div>';

          html += '</div>';
          html += '</div>';
          html += '</div>';
        }

        // Insert the HTML into the dataContainer div
        $('#dataContainer').html(html);
        $('html, body').animate({
          scrollTop: 0
        }, 'slow');

        // Hide loading overlay
        $('#loadingOverlay').hide();
      }

      function displayPagination() {
        var paginationHtml = '';

        // Generate the pagination links
        for (var i = 1; i <= totalPages; i++) {
          if (i === currentPage) {
            paginationHtml += '<a href="#" class="page-link active">' + i + '</a>';
          } else {
            paginationHtml += '<a href="#" class="page-link">' + i + '</a>';
          }
        }

        // Insert the pagination links into the paginationContainer div
        $('#paginationContainer').html(paginationHtml);

        // Add event listener to handle page changes
        $('#paginationContainer .page-link').on('click', function(e) {
          e.preventDefault();
          var page = parseInt($(this).text());
          currentPage = page;
          displayItems(page);
          updateActivePageLink(page);
        });
      }

      function updateActivePageLink(page) {
        $('#paginationContainer .page-link').removeClass('active');
        $('#paginationContainer .page-link:contains(' + page + ')').addClass('active');
      }

      displayItems(currentPage);
      displayPagination();
      updateActivePageLink(currentPage);
    }
  });
});

</script>
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
           