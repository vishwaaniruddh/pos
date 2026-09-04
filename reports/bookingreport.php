<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script type="module" src="datepick_js.js"></script>
    <link rel="stylesheet" type="text/css" href="date_css.css" />
    <style>
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
    </style>
    
    <style>
@media print {
  body {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  table, th, td {
    border: 1px solid #000 !important;
    border-collapse: collapse !important;
  }

  th {
    background: #f2f2f2 !important;
    -webkit-print-color-adjust: exact;
  }

  table {
    width: 100% !important;
    page-break-inside: auto;
  }

  tr {
    page-break-inside: avoid;
    page-break-after: auto;
  }

  @page {
    size: landscape;
    margin: 10mm;
  }
}
</style>


    <script>
        var isCtrl = false;
        document.addEventListener('keyup', function(e) {
            if (e.which == 17) isCtrl = false;
        });
        document.addEventListener('keydown', function(e) {
            if (e.which == 17) isCtrl = true;
            if (e.which == 66 && isCtrl == true) {
                document.getElementById("barcode").focus();
                return false;
            }
        });

        function showdetails() {
            var from_date = document.getElementById('from_date').value;
            var to_date = document.getElementById('to_date').value;
            var barcode = document.getElementById('barcode').value;
            
            var date = 0;
            if (from_date == "") date++;
            if (to_date == "") date++;
            
            if (date == 2 || date == 0) {
                var bar = document.getElementById('barcode').value;
                var bar2 = document.getElementById('barcode2').value;
                var xmlhttp;
                
                if (window.XMLHttpRequest) {
                    xmlhttp = new XMLHttpRequest();
                } else {
                    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                }
                
                xmlhttp.onreadystatechange = function() {
                    if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                        document.getElementById('barcode2').value = '';
                        document.getElementById("back").innerHTML = xmlhttp.responseText;
                    }
                }

                xmlhttp.open("GET", 'booking_ajax.php?barcode=' + bar + '&barcode2=' + bar2 + '&fromdate=' + from_date + '&todate=' + to_date, true);
                xmlhttp.send();
            } else {
                alert("Both From pick date and To pick date are required");
            }
        }
    </script>
    
    <!-- Add buttons to print -->
<script>
function printFuture() {
    var printContent = document.getElementById("futureBookings").innerHTML;
    var originalContent = document.body.innerHTML;
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
}

function printPast() {
    var printContent = document.getElementById("pastBookings").innerHTML;
    var originalContent = document.body.innerHTML;
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
}
</script>



</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="text-center mb-4">
            <a href="/pos/home_dashboard.php" class="btn btn-secondary">Back</a>
            <h2 class="my-3">Booking Status</h2>
        </div>

        <div class="card p-4">
            <form class="row g-3">
                <div class="col-md-3">
                    <label for="from_date" class="form-label">Select From Pick Date:</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" />
                </div>

                <div class="col-md-3">
                    <label for="to_date" class="form-label">Select To Pick Date:</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" />
                </div>

                <div class="col-md-3">
                    <label for="barcode" class="form-label">Item Code:</label>
                    <input type="text" name="barcode" id="barcode" class="form-control" onFocus="this.value=''" onClick="this.value=''" />
                </div>

                <div class="col-md-3">
                    <label for="barcode2" class="form-label">Barcode:</label>
                    <input type="text" name="barcode2" id="barcode2" class="form-control" onFocus="this.value=''" onClick="this.value=''" />
                </div>

                <div class="col-12 text-center">
                    <button type="button" class="btn btn-primary" onClick="showdetails();">Show Details</button>
                </div>
            </form>
        </div>

        <hr>

        <div id="back"></div>

        <div class="text-center mt-5">
            <p class="text-muted">You are using Point Of Sale Version 10.5</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>




<?php return; ?>
<script type="module" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css" />
<script>
var isCtrl = false;
document.addEventListener('keyup', function(e) {
    if (e.which == 17) isCtrl = false;
});
document.addEventListener('keydown', function(e) {
    if (e.which == 17) isCtrl = true;
    if (e.which == 66 && isCtrl == true) {
        document.getElementById("barcode").focus();
        return false;
    }
});

function showdetails() {
    var from_date = document.getElementById('from_date').value;
    var to_date = document.getElementById('to_date').value;
    var barcode = document.getElementById('barcode').value;
    
    var date = 0;
    if (from_date == "") {
        date = date + 1;
    }
    if (to_date == "") {
        date = date + 1;
    }
    if (date == 2 || date == 0) {
        var bar = document.getElementById('barcode').value;
        var bar2 = document.getElementById('barcode2').value;
        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                // document.getElementById('barcode').value = '';
                document.getElementById('barcode2').value = '';
                document.getElementById("back").innerHTML = xmlhttp.responseText;
            }
        }
            var barcode = document.getElementById('barcode').value;

        xmlhttp.open("GET", 'getbookdetail_new.php?barcode=' + bar + '&barcode2=' + bar2 + '&fromdate=' + from_date + '&todate=' + to_date, true);
        xmlhttp.send();
    } else {
        alert("Both From pick date and to pick date required");
    }
}
</script>

<body>

<div style="text-align: center;">
    <a href="/pos/home_dashboard.php">Back</a>
    <table width="1320" height="300" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" align="center">
        <tr>
            <td align="center"> 
                <!-- PHP code here -->
                <b>Booking Status</b>
            </td>
        </tr>
        <tr>
            <td width="1308" valign="top">
                <center>
                    <table width="100%" align='center'>
                        <tr align="center">
                            <td width="493">
                                <strong>Select From Pick Date :</strong>
                                <input type="date" name="from_date" id="from_date" />
                                <strong>Select To Pick Date :</strong>
                                <input type="date" name="to_date" id="to_date" />
                                <strong>Item code: </strong>
                                <input type="text" name="barcode" id="barcode" onFocus="this.value=''" onClick="this.value=''" onChange="showdetails();" />
                                &nbsp;&nbsp;&nbsp;&nbsp; Barcode :
                                <input type="text" name="barcode2" id="barcode2" onFocus="this.value=''" onClick="this.value=''" onChange="showdetails();" />
                                <button type="button" onClick="showdetails();">Show Details</button>
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
</body>
