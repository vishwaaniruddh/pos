<style>

.tooltip-item:hover + .tooltip-content {
    display: block !important;
}


.mytooltip {
    display: inline;
    position: relative;
    z-index: 999;
}

.mytooltip .tooltip-item {
cursor: pointer;
    display: inline-block;
    font-weight: 500;
    padding: 0px 5px;
    background: gray;
    color: white;
    border-radius: 50%;
    line-height: 1.8;
    font-size: 7px;
}
.mytooltip .tooltip-item::after {
    content: '';
    position: absolute;
    width: 360px;
    height: 20px;
    bottom: 45%;
    left: 50%;
    pointer-events: none;
    -webkit-transform: translateX(-50%);
    transform: translateX(-50%);
}

.tooltip-effect-1 .tooltip-content {
    -webkit-transform: translate3d(0,-10px,0);
    transform: translate3d(0,-10px,0);
    -webkit-transition: opacity .3s,-webkit-transform .3s;
    transition: opacity .3s,-webkit-transform .3s;
    transition: opacity .3s,transform .3s;
    transition: opacity .3s,transform .3s,-webkit-transform .3s;
    color: #fff;
}

.mytooltip .tooltip-content {
  position: absolute;
    z-index: 9999;
    width: 360px;
    bottom: 15%;
    text-align: left;
    font-size: 20px;
    line-height: 30px;
    -webkit-box-shadow: -5px -5px 15px rgba(48,54,61,.2);
    box-shadow: -5px -5px 15px rgba(48,54,61,.2);
    background: #06d482;
    display: none;
    cursor: default;
    padding: 14px;
    pointer-events: none;
    font-weight: 600;
     /*font-size: 32px;*/
}




.mytooltip .tooltip-content img {
    position: relative;
    height: 140px;
    display: block;
    float: left;
    margin-right: 1em;
}
img {
    vertical-align: middle;
    border-style: none;
}

.mytooltip .tooltip-text {
    font-size: 14px;
    line-height: 24px;
    display: block;
    padding: 1.31em 1.21em 1.21em 0;
    color: #fff;
}
.mytooltip .tooltip-content::after {
    content: '';
    top: 100%;
    left: 50%;
    border: solid transparent;
    height: 0;
    width: 0;
    position: absolute;
    pointer-events: none;
    border-color: #2a3035 transparent transparent;
    border-width: 10px;
    margin-left: -10px;
}

.clearfix::after {
    display: block;
    clear: both;
    content: "";
}

    .page-link {
        position: relative;
        display: block;
        padding: 0.5rem 0.75rem;
        margin-left: -1px;
        line-height: 1.25;
        color: black;
        background-color: #fff;
        border: 1px solid #dee2e6;
    }

    .page-link.active {
        color: #ffa45c;
        color: white;
        background: darkgray;
    }
</style>

<?php
include('../db_connection.php');
$product_id = $_REQUEST['id'];
if (isset($_REQUEST['pricefilter'])) {
    $pricefilter = $_REQUEST['pricefilter'];
}

$sku = $_REQUEST['query'];

?>
    

<div class="container">
    

    <br />
    <div style="float:right;">
        <select class="form-control" onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);" style="width: auto;">
            <option>Select</option>
            <option value="apparel.php?id=<?php echo $product_id; ?>&type=2&pricefilter=1"<?php if (isset($_GET['pricefilter']) && $_GET['pricefilter'] == 1) {
                                                                                            echo 'selected';
                                                                                        } ?>>Higher To Lower</option>
            <option value="apparel.php?id=<?php echo $product_id; ?>&type=2&pricefilter=2"<?php if (isset($_GET['pricefilter']) && $_GET['pricefilter'] == 2) {
                                                                                            echo 'selected';
                                                                                        } ?>> Lower To Higher </option>
        </select>
    </div>
    <br /><br />
    
    
    <div id="seachBlock" style="color:black;">
        <h1 style="color:black;">Enter Terms In Search Box To Explore...</h1>
    </div>
    
    <form action="/searchresult.php" method="GET" class="search-box" id="search_form">
                <input type="text" name="query" class="search-box-input" placeholder="What are you looking for ?" id="query">
                <button class="search-box-btn" id="search" style="position:relative;">
                    <img class=" ls-is-cached lazyloaded" data-src="/assets/search.png" alt="Sri Shringarr" loading="lazy" src="/assets/search.png">
                </button>
            </form>
    
    <hr />        
    <div id="loadingOverlay" class="center" style="visibility: visible;text-align: center;">
        <img class=" ls-is-cached lazyloaded" data-src="assets/loader.gif" loading="lazy" src="assets/loader.gif" style="width:200px;">     
    </div>
    
    



    <div id="dataContainer" class="row rowFlexMargin wrapper"></div>
    <div id="paginationContainer" class="row mt-3 text-center"></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>

<script>
    

$(document).ready(function() {
  var currentPage = 1;
  var itemsPerPage = 20; // Number of items to display per page

  // Show loading overlay
  $('#loadingOverlay').show();

var sku = '<?php echo  $sku; ?>';

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
          html += '<div class="col-xs-6 col-md-4 col-lg-3 ftco-animate fadeInUp ftco-animated product_col">';
          html += '<div class="product">';
          html += '<a href="' + product.link + '" class="img-prod">';
          html += product.imgframe;
          html += '</a>';

          html += '<div class="text py-3 px-3">';
          html += '<h3>';
          html += '<a href="' + product.link + '">' + product.product_name + '</a>';
          html += '</h3>';
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
                     html +=`<span class="mytooltip tooltip-effect-1">
                        &nbsp;&nbsp;&nbsp;<span class="tooltip-item"><i>i</i></span>

                        <span class="tooltip-content clearfix" style="justify-content: space-between;align-items: center;">

                        <span class="tooltip-text">
                            You can now buy Pre Loved Pieces at exciting prices. They are very well maintained and look as good as new❤️
                        </span>
                        </span>
                        
                        </span>`;
          
            html += '<br /><div id="bookingStatus">'+product.booking+'</div>'
                        
                        
                        
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
}else{
    $('#loadingOverlay').css('display','none');
}

});

</script>

