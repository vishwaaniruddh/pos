<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (file_exists('../top-header.php')) include_once('../top-header.php');
if (file_exists('../top-navbar.php')) include_once('../top-navbar.php');
?>
<div class="container-fluid page-body-wrapper">
    <?php
    if (file_exists('../navbar.php')) include_once('../navbar.php');
    if (file_exists('../db_connection.php')) include_once('../db_connection.php');
    $con = OpenSrishringarrCon();
    ?>

    <!-- partial -->
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">

            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"
                integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            <style>
                :root {
                    --pm-slate-900: #0f172a;
                    --pm-slate-800: #1e293b;
                    --pm-slate-700: #334155;
                    --pm-slate-600: #475569;
                    --pm-slate-500: #64748b;
                    --pm-slate-400: #94a3b8;
                    --pm-slate-200: #e2e8f0;
                    --pm-slate-100: #f1f5f9;
                    --pm-slate-50: #f8fafc;
                }

                .bank-report-container {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                /* Page Header */
                .pm-page-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 20px;
                    flex-wrap: wrap;
                    gap: 12px;
                }

                .pm-page-title {
                    font-size: 20px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    letter-spacing: -0.02em;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .pm-page-subtitle {
                    font-size: 13px;
                    color: var(--pm-slate-500);
                    margin: 2px 0 0 0;
                }

                /* Filter Toolbar Card */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 18px;
                    overflow: hidden;
                }

                .pm-card-body {
                    padding: 16px 18px;
                }

                .pm-form-label {
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--pm-slate-700);
                    margin-bottom: 5px;
                    display: block;
                }

                .pm-control {
                    height: 38px;
                    font-size: 13px;
                    color: var(--pm-slate-900);
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    padding: 6px 12px;
                    width: 100%;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-control:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                    outline: none;
                }

                /* Buttons */
                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border: 1px solid var(--pm-slate-900);
                    font-size: 12.5px;
                    font-weight: 600;
                    border-radius: 6px;
                    padding: 8px 16px;
                    height: 38px;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                }

                .pm-btn-primary:hover {
                    background: var(--pm-slate-800);
                    border-color: var(--pm-slate-800);
                    color: #ffffff;
                }

                .pm-btn-outline {
                    background: #ffffff;
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
                    font-size: 12.5px;
                    font-weight: 500;
                    border-radius: 6px;
                    padding: 7px 14px;
                    height: 38px;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                }

                .pm-btn-outline:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900);
                    border-color: #cbd5e1;
                }

                .pm-btn-primary i, .pm-btn-outline i {
                    margin-right: 4px;
                }

                /* Date Presets */
                .pm-preset-pill {
                    background: var(--pm-slate-50);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-600);
                    font-size: 11.5px;
                    font-weight: 500;
                    padding: 3px 9px;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                }

                .pm-preset-pill:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900);
                }

                /* Print Styles */
                @media print {
                    .navbar, #sidebar, .pm-page-header, .pm-filter-card, .footer, .d-print-none {
                        display: none !important;
                    }
                    .page-body-wrapper {
                        padding-top: 0 !important;
                    }
                    .main-panel {
                        margin-left: 0 !important;
                        width: 100% !important;
                    }
                    .content-wrapper {
                        padding: 0 !important;
                        background: #ffffff !important;
                    }
                    #back {
                        display: block !important;
                    }
                }
            </style>

            <div class="bank-report-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa fa-university text-muted" style="font-size: 18px; margin-right: 8px;"></i> 
                            Bank Ledger & Transaction Report
                        </h1>
                        <p class="pm-page-subtitle">Track bank statement transactions, opening balances, debits, credits, and editable remarks.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="bank_entry.php" class="pm-btn-outline" title="Record a new transaction">
                            <i class="fa fa-plus"></i> Bank Entry
                        </a>
                        <button type="button" class="pm-btn-outline" onclick="printReport()" title="Print ledger">
                            <i class="fa fa-print"></i> Print Report
                        </button>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="pm-card pm-filter-card">
                    <div class="pm-card-body">
                        <form id="bankFilterForm" onsubmit="event.preventDefault(); showtrans('');">
                            <div class="row g-3 align-items-end">
                                
                                <!-- Bank Dropdown -->
                                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12">
                                    <label class="pm-form-label" for="bank_id">
                                        <i class="fa fa-building-o text-muted me-1" style="margin-right: 6px;"></i> Select Bank Account
                                    </label>
                                    <select name="bank_id" id="bank_id" class="pm-control" onchange="showtrans('');">
                                        <option value="0">All Banks (Today's Transactions)</option>
                                        <?php
                                        if ($con) {
                                            $qryitem = mysqli_query($con, "SELECT bank_name, bank_id FROM banks ORDER BY bank_name ASC");
                                            if ($qryitem) {
                                                while ($bRow = mysqli_fetch_row($qryitem)) {
                                                    echo "<option value='" . htmlspecialchars($bRow[1]) . "'>" . htmlspecialchars($bRow[0]) . "</option>";
                                                }
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Date From -->
                                <div class="col-xl-2 col-lg-3 col-md-6 col-sm-6">
                                    <label class="pm-form-label" for="frmdate">
                                        <i class="fa fa-calendar-o text-muted me-1" style="margin-right: 6px;"></i> From Date
                                    </label>
                                    <input type="date" name="frmdate" id="frmdate" class="pm-control" 
                                           value="<?php echo date('Y-m-d'); ?>" />
                                </div>

                                <!-- Date To -->
                                <div class="col-xl-2 col-lg-3 col-md-6 col-sm-6">
                                    <label class="pm-form-label" for="todate">
                                        <i class="fa fa-calendar-o text-muted me-1" style="margin-right: 6px;"></i> To Date
                                    </label>
                                    <input type="date" name="todate" id="todate" class="pm-control" 
                                           value="<?php echo date('Y-m-d'); ?>" />
                                </div>

                                <!-- Action Buttons -->
                                <div class="col-xl-4 col-lg-2 col-md-12 col-sm-12 d-flex gap-2">
                                    <button type="button" class="pm-btn-primary flex-fill justify-content-center" onclick="showtrans('');">
                                        <i class="fa fa-filter"></i> View Ledger
                                    </button>
                                    <button type="button" class="pm-btn-outline" onclick="resetFilters();" title="Reset date filters">
                                        <i class="fa fa-refresh"></i>
                                    </button>
                                </div>

                            </div>

                            <!-- Quick Preset Date Ranges -->
                            <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                                <span style="font-size: 11.5px; font-weight: 600; color: var(--pm-slate-500); margin-right: 4px;">Quick Ranges:</span>
                                <button type="button" class="pm-preset-pill" onclick="setDateRange('today')">Today</button>
                                <button type="button" class="pm-preset-pill" onclick="setDateRange('yesterday')">Yesterday</button>
                                <button type="button" class="pm-preset-pill" onclick="setDateRange('this_week')">Last 7 Days</button>
                                <button type="button" class="pm-preset-pill" onclick="setDateRange('this_month')">This Month</button>
                                <button type="button" class="pm-preset-pill" onclick="setDateRange('last_month')">Last Month</button>
                                <button type="button" class="pm-preset-pill" onclick="setDateRange('financial_year')">FY 2026-27</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Live Report Container (Loaded via gettrans.php) -->
                <div id="back">
                    <div class="text-center py-5">
                        <i class="fa fa-spinner fa-spin fa-2x text-muted mb-2"></i>
                        <div style="font-size: 13.5px; font-weight: 600; color: var(--pm-slate-800);">Loading Bank Ledger...</div>
                        <div style="font-size: 12px; color: var(--pm-slate-500);">Fetching transactions and opening balances...</div>
                    </div>
                </div>

            </div>

            <!-- Scripts -->
            <script>
                $(document).ready(function() {
                    // Initial load for today's transactions
                    showtrans('ld');
                });

                function showtrans(ld) {
                    var bank_id = document.getElementById('bank_id').value;
                    var frmdate = document.getElementById('frmdate').value;
                    var todate = document.getElementById('todate').value;

                    if (bank_id == "0" && ld != 'ld') {
                        // If all banks is selected with custom dates, allow it or notify
                    }

                    document.getElementById('back').innerHTML = 
                        '<div class="pm-card text-center py-5">' +
                        '  <i class="fa fa-spinner fa-spin fa-2x text-muted mb-2"></i>' +
                        '  <div style="font-size:13.5px; font-weight:600; color:var(--pm-slate-800);">Loading Ledger Report...</div>' +
                        '  <div style="font-size:12px; color:var(--pm-slate-500);">Calculating opening balance & running totals...</div>' +
                        '</div>';

                    $.ajax({
                        url: 'gettrans.php',
                        type: 'GET',
                        data: {
                            bank_id: bank_id,
                            frmdate: frmdate,
                            todate: todate,
                            ld: ld
                        },
                        success: function(response) {
                            document.getElementById('back').innerHTML = response;
                        },
                        error: function() {
                            document.getElementById('back').innerHTML = 
                                '<div class="alert alert-danger my-3 text-center" style="font-size:13px;">' +
                                '  <i class="fa fa-exclamation-triangle me-1"></i> Failed to retrieve bank transactions. Please check database connection.' +
                                '</div>';
                        }
                    });
                }

                function edit_memo(cnt, transId) {
                    var currentMemo = document.getElementById('rem' + cnt) ? document.getElementById('rem' + cnt).value : '';

                    Swal.fire({
                        title: 'Edit Transaction Memo',
                        text: 'Update memo for Transaction #' + transId,
                        input: 'textarea',
                        inputValue: currentMemo,
                        inputPlaceholder: 'Enter transaction particulars / memo...',
                        showCancelButton: true,
                        confirmButtonColor: '#0f172a',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Save Memo',
                        inputValidator: (value) => {
                            if (!value || !value.trim()) {
                                return 'Memo cannot be empty';
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            var newMemo = result.value.trim();
                            $.ajax({
                                url: 'edit_bank_report.php',
                                type: 'GET',
                                data: {
                                    rem: newMemo,
                                    id: transId
                                },
                                success: function(res) {
                                    if (res.trim() == "1") {
                                        var memoEl = document.getElementById('showrem' + cnt + '1');
                                        if (memoEl) memoEl.innerText = newMemo;
                                        if (document.getElementById('rem' + cnt)) {
                                            document.getElementById('rem' + cnt).value = newMemo;
                                        }
                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end',
                                            icon: 'success',
                                            title: 'Memo updated successfully!',
                                            showConfirmButton: false,
                                            timer: 1500
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Update Error',
                                            text: 'Could not update memo. Please try again.',
                                            confirmButtonColor: '#0f172a'
                                        });
                                    }
                                },
                                error: function() {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Server Error',
                                        text: 'Network error occurred. Please try again.',
                                        confirmButtonColor: '#0f172a'
                                    });
                                }
                            });
                        }
                    });
                }

                function confirmDeleteTransaction(transId) {
                    Swal.fire({
                        title: 'Delete Transaction #' + transId + '?',
                        text: 'This will move the entry to history and remove it from active records.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Yes, Delete Entry'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: 'delete_transac.php',
                                type: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                data: { trans_id: transId },
                                dataType: 'json',
                                success: function(res) {
                                    if (res && res.status === 'success') {
                                        var row = document.getElementById('txn-row-' + transId);
                                        if (row) {
                                            $(row).fadeOut(250, function() {
                                                $(this).remove();
                                            });
                                        }
                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end',
                                            icon: 'success',
                                            title: 'Transaction #' + transId + ' deleted',
                                            showConfirmButton: false,
                                            timer: 1500
                                        });
                                        setTimeout(function() { showtrans(''); }, 500);
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Delete Failed',
                                            text: 'Could not delete transaction. Please try again.',
                                            confirmButtonColor: '#0f172a'
                                        });
                                    }
                                },
                                error: function() {
                                    var form = document.createElement('form');
                                    form.method = 'POST';
                                    form.action = 'delete_transac.php';
                                    var hiddenField = document.createElement('input');
                                    hiddenField.type = 'hidden';
                                    hiddenField.name = 'trans_id';
                                    hiddenField.value = transId;
                                    form.appendChild(hiddenField);
                                    document.body.appendChild(form);
                                    form.submit();
                                }
                            });
                        }
                    });
                }

                function resetFilters() {
                    var today = new Date().toISOString().split('T')[0];
                    $('#bank_id').val('0');
                    $('#frmdate').val(today);
                    $('#todate').val(today);
                    showtrans('ld');
                }

                function setDateRange(range) {
                    var now = new Date();
                    var from = new Date();
                    var to = new Date();

                    if (range === 'today') {
                        // same day
                    } else if (range === 'yesterday') {
                        from.setDate(now.getDate() - 1);
                        to.setDate(now.getDate() - 1);
                    } else if (range === 'this_week') {
                        from.setDate(now.getDate() - 7);
                    } else if (range === 'this_month') {
                        from = new Date(now.getFullYear(), now.getMonth(), 1);
                    } else if (range === 'last_month') {
                        from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                        to = new Date(now.getFullYear(), now.getMonth(), 0);
                    } else if (range === 'financial_year') {
                        var year = (now.getMonth() >= 3) ? now.getFullYear() : (now.getFullYear() - 1);
                        from = new Date(year, 3, 1);
                        to = new Date(year + 1, 2, 31);
                    }

                    var fmt = (d) => {
                        var month = '' + (d.getMonth() + 1);
                        var day = '' + d.getDate();
                        var year = d.getFullYear();
                        if (month.length < 2) month = '0' + month;
                        if (day.length < 2) day = '0' + day;
                        return [year, month, day].join('-');
                    };

                    $('#frmdate').val(fmt(from));
                    $('#todate').val(fmt(to));
                    showtrans('');
                }

                function printReport() {
                    window.print();
                }
            </script>

        </div>
    </div>
</div>

<?php 
if (file_exists('../footer.php')) include_once('../footer.php'); 
CloseCon($con);
?>
</div>
</div>
</div>

<script src="../vendors/js/vendor.bundle.base.js"></script>
<script src="../vendors/js/vendor.bundle.addons.js"></script>
<script src="../js/off-canvas.js"></script>
<script src="../js/hoverable-collapse.js"></script>
<script src="../js/misc.js"></script>
<script src="../js/settings.js"></script>
<script src="../js/todolist.js"></script>

</body>
</html>