<!DOCTYPE html>
<html lang="en">
    <?php include('head.php');?>
	
	<style>
    .table thead th, .jsgrid .jsgrid-table thead th {
    border-top: 0;
    border-bottom-width: 1px;
    font-weight: bold;
    font-size: .9rem;
    padding: 0.4375rem;
}
		.bt{
				border-top: 1px solid #1e1f33;
		  }
		  .br
		  {
				border-right: 1px solid #282844;
		  }
		  #accordion div.card-body {
		/*	margin:4px, 4px;
			padding:4px;
			background-color: green;
			width: 500px;  */
			height: 210px;
			overflow-x: hidden;
			overflow-y: scroll;
			text-align:justify;
		}
	</style>
	<style>
		.menu-icon
		{
			width: 33px;
			margin-right: 7%;
		}
	</style>
     <?php include('top-navbar.php');
     echo $_SESSION['username'];?>
            <!-- partial -->
            <div class="container-fluid page-body-wrapper">
                <!-- partial:partials/_settings-panel.html -->
                <!-- partial -->
                <!-- partial:partials/_sidebar.html -->
                <?php include('navbar.php');?>
                <!-- partial -->
  <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
          <div class="center">
              
               <h3 class="page-title" >
                Booking Status 
            </h3>
          </div>



            <!-- <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Forms</a></li>
                <li class="breadcrumb-item active" aria-current="page">Form elements</li>
                </ol>
            </nav> -->
          </div>
        
          <div class="row">
		    <div class="col-12 grid-margin">
                        
			</div>   
            <div class="col-md-12 grid-margin stretch-card">
              <div class="card">
                                   
              <div class="card-body">
                  <h4 class="card-title">Booking Status Request </h4>
				 
                    
                    <div style="text-align: center;">
                    <!--<a href="../../../index.php/reports">Back</a>-->
                    <table width="980" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center">
                    <tr>
                    <td align="center"> 
                    <?php
                    include('../db_connection.php') ;
                    $con=OpenSrishringarrCon();
                    
                    $result5=mysqli_query($con,"select * from `phppos_app_config`");
                    $row5 = mysqli_fetch_array($con,$result5);
                    mysqli_data_seek($con,$result5,1);
                    $row6=mysqli_fetch_array($con,$result5);
                    mysqli_data_seek($con,$result5,10);
                    $row7=mysqli_fetch_array($con,$result5);
                    	CloseCon($con);
                    ?>
                    
                    <!--<img src="bill.PNG" width="408" height="165"/><br/><br/>-->
                    <b>Booking Status</b>
                    </td></tr>
                    
                    <tr>
                    <td width="1308"  valign="top"><center>
                          <table width="100%" align='center'>
                          <tr align="center">
                              
                          <td width="493">
                              <strong>Select From Pick Date :</strong><input type="text" name="from_date" id="from_date" onClick="displayDatePicker('from_date');" autocomplete="nope" /><br>
                              <strong>Select To Pick Date :</strong><input type="text" name="to_date" id="to_date" onClick="displayDatePicker('to_date');" autocomplete="nope" /><br>
                              <strong>Item code: </strong>
                           <input type="text" name="barcode" onFocus="this.value=''" onClick="this.value=''"  id="barcode" onChange="showdetails();"/> &nbsp;&nbsp;&nbsp;&nbsp; Barcode : 
                           <input type="text" name="barcode2" onFocus="this.value=''" onClick="this.value=''"  id="barcode2" onChange=" showdetails();"/>
                           <button type="button" onClick=" showdetails();">Show Details</button>
                          </td>
                          </tr>
                          </table>
                        <hr>
                        
                         <div id="back"></div>
                         <br/>
                      
                          
                    </center>
                    </td>
                    </tr>
                    </table>
                    </div>
                    <div align="center">You are using Point Of Sale Version 10.5 .</div>
                </div>
              </div>
            </div>

          </div>
        </div>



        <!-- content-wrapper ends -->
       <!-- partial:partials/_footer.html -->
                    <?php include('footer.php');?>
                    <!-- partial -->
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
        <!--<script src="js/chart.js"></script>-->
        <!-- endinject -->
        <!-- Custom js for this page-->
        <script src="js/dashboard.js"></script>
        
        <!-- End custom js for this page-->
        <!-- video.js -->
       <!-- <script src="js/dvrdashboard.js"></script>-->
		<script src="js/select2.js"></script>
        <!-- video.js -->
    <script>
        
        function showdetails()
        { debugger;
        var from_date = document.getElementById('from_date').value;
        var to_date = document.getElementById('to_date').value;
        var date = 0;
        if(from_date==""){
            date = date + 1;
        }
        if(to_date==""){
            date = date + 1;
        }
        if(date==2 || date==0){
        var bar = document.getElementById('barcode').value;
         var bar2 = document.getElementById('barcode2').value;
        if (window.XMLHttpRequest)
          {// code for IE7+, Firefox, Chrome, Opera, Safari
          xmlhttp=new XMLHttpRequest();
          }
        else
          {// code for IE6, IE5
          xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
          }
        xmlhttp.onreadystatechange=function()
          { //alert("hii");
          if (xmlhttp.readyState==4 && xmlhttp.status==200)
            {
        		document.getElementById('barcode').value='';
        	document.getElementById('barcode2').value='';
            document.getElementById("back").innerHTML=xmlhttp.responseText;
        	
            }
          }
        xmlhttp.open("GET",'getbookdetail_new.php?barcode='+bar+'&barcode2='+bar2+'&fromdate='+from_date+'&todate='+to_date,true);
        xmlhttp.send();
        }else{
            alert("Both From pick date and to pick date required");
        }
        }
    </script>
    
    
    </body>
</html>

