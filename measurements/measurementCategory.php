<?php session_start() ; 


include('../top-header.php');?>

     <?php include('../top-navbar.php');?>
     
     <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
            <div class="container-fluid page-body-wrapper">
                <?php include('../navbar.php');
                $con = OpenSrishringarrCon();
                ?>
                
                <!-- partial -->
                  <div class="main-panel">
                        <div class="content-wrapper">
     
     
     <style>
         .measurment_button{
             width: 100%;
    font-size: 12px;
         }
         .grid_img{
    height: 300px;
    object-fit: cover;
         }
     </style>               
<?php

$sku = trim($_REQUEST['query']);


?>


        <div class="row">
            
            <div class="col-sm-6">
                
                <div class="card">
    <div class="card-body">
        <h5>Option 1 (Category)</h5>
                    <select id="garmentid" name="garmentid" class="form-control">
                        <option> Select </option>
                        <?
                        $garsql = mysqli_query($web_con,"SELECT * FROM `garments` where `Main_id`=1 or `Main_id`=3");
                        while($garsql_result = mysqli_fetch_assoc($garsql)){
                            $name = $garsql_result['name'];
                            $garment_id = $garsql_result['garment_id'];    
                            echo '<option value="'.$garment_id.'">'.ucwords(strtolower($name)).'</option>' ;
                        }
                        ?>
                    </select>

        
    </div>
</div>
                
            </div>
            
            <div class="col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h5>Option 2 (Search-sku)</h5>
                        <form action="./measurementCategory.php" method="POST" class="search-box" id="search_form" style="display:flex;">
                            <input type="text" name="query" class="form-control search-box-input" placeholder="What are you looking for ?" id="query" value="<?php echo $sku ; ?>" >
                            <button class="search-box-btn" class="btn btn-primary" id="search" style="position:relative;">Search</button>
                        </form>
                    </div>
                </div>
                
            </div>
            
        </div>



         <br>         <br>           
                    
    
<div class="card">
    <div class="card-body row" id="productList">                
                    
          
  
    </div>
</div>



<script>
        $(document).on('change','#garmentid',function(){
       $("#productList").html('');
       var garmentid = $(this).val();
        $.ajax({
            url: "./garmentProduct.php",
            data: 'garmentid='+garmentid,
            success: function(response) {
               $("#productList").html(response);
            }
        });
    });
    
    
$(document).ready(function() {
  var currentPage = 1;
  var itemsPerPage = 20; // Number of items to display per page


var sku = '<?php echo $sku; ?>';

if(sku){

$('#seachBlock').css('display','none');
$.ajax({
    url: "./search1.php?query="+sku,
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
          
          
          
    html += `<div class="col-sm-3 product_grid" style="margin: 10px auto;" data-selling_price ="${product.lastSellingPrice}" data-rent_price ="${product.addedRentPrice}">
        <img class="grid_img" src="${product.img}" style="width:100%;" />
        <hr />
        SKU: <strong>${product.sku}</strong>
        <p class="rent_price">Rent Price : ${product.addedRentPrice}</p>
        <p class="selling_price">Selling Price :${product.lastSellingPrice}</p>
        
<a class="btn btn-primary measurment_button" href="./editMeasuments.php?productid=${product.product_id}&sku=${product.sku}&img=${product.img}">Add / Edit Measuments</a>        

    </div>`;      
        }

        $('#productList').html(html);
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
}else{
    $('#loadingOverlay').css('display','none');
}

});

</script>
                    
                    
                    
                	    </div>
                	
                	 <?php include('../footer.php');?>
                  </div>

    </div>

</div>

<script src="../vendors/js/vendor.bundle.base.js">
</script>
<script src="../vendors/js/vendor.bundle.addons.js">
</script>

<script src="../js/off-canvas.js">
</script>
<script src="../js/hoverable-collapse.js">
</script>
<script src="../js/misc.js">
</script>
<script src="../js/settings.js">
</script>
<script src="../js/todolist.js"></script>

<script src="../js/data-table.js"></script>
<script src="../js/data-table2.js"></script>
<script src="../js/select2.js"></script>
            
</body>
</html>