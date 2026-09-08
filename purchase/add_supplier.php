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

    <!-- Main Panel -->
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

                .supp-container {
                    max-width: 980px;
                    margin: 0 auto;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                /* Page Header */
                .pm-page-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 24px;
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
                    margin: 3px 0 0 0;
                }

                /* Form Cards */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 20px;
                    overflow: hidden;
                }

                .pm-card-header {
                    padding: 14px 20px;
                    background: #ffffff;
                    border-bottom: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }

                .pm-card-title {
                    font-size: 14px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .pm-card-body {
                    padding: 20px;
                }

                /* Form Controls */
                .pm-form-group {
                    margin-bottom: 16px;
                }

                .pm-form-label {
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--pm-slate-700);
                    margin-bottom: 6px;
                    display: block;
                }

                .pm-form-label .req {
                    color: #ef4444;
                    margin-left: 2px;
                }

                .pm-input-wrapper {
                    position: relative;
                    display: flex;
                    align-items: center;
                }

                .pm-input-icon {
                    position: absolute;
                    left: 12px;
                    color: var(--pm-slate-400);
                    font-size: 13px;
                    pointer-events: none;
                }

                .pm-control {
                    height: 38px;
                    font-size: 13px;
                    color: var(--pm-slate-900);
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    padding: 6px 12px 6px 36px;
                    width: 100%;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-control.no-icon {
                    padding-left: 12px;
                }

                .pm-control:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                    outline: none;
                }

                .pm-textarea {
                    font-size: 13px;
                    color: var(--pm-slate-900);
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    padding: 8px 12px;
                    width: 100%;
                    min-height: 72px;
                    resize: vertical;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-textarea:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                    outline: none;
                }

                /* Buttons */
                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border: 1px solid var(--pm-slate-900);
                    font-size: 13px;
                    font-weight: 600;
                    border-radius: 6px;
                    padding: 8px 18px;
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
                    font-size: 13px;
                    font-weight: 500;
                    border-radius: 6px;
                    padding: 7px 16px;
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
                    margin-right: 6px;
                }

                /* Helper Pill Badge */
                .pm-badge-neutral {
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-600);
                    font-size: 11.5px;
                    font-weight: 500;
                    padding: 2px 8px;
                    border-radius: 12px;
                }
            </style>

            <div class="supp-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa fa-truck text-muted" style="font-size: 18px; margin-right: 8px;"></i>
                            Add New Supplier
                        </h1>
                        <p class="pm-page-subtitle">Register vendor business details, primary contact representative, and billing address.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="view_supplier.php" class="pm-btn-outline" title="View all registered suppliers">
                            <i class="fa fa-list"></i> View Suppliers List
                        </a>
                        <a href="purchase_entry.php" class="pm-btn-outline" title="Go to purchase entry">
                            <i class="fa fa-shopping-cart"></i> Purchase Entry
                        </a>
                    </div>
                </div>

                <!-- Main Form -->
                <form id="addSupplierForm" action="add_supplier_insert.php" method="POST">

                    <!-- Card 1: Business & Company Information -->
                    <div class="pm-card">
                        <div class="pm-card-header">
                            <h3 class="pm-card-title">
                                <i class="fa fa-briefcase text-muted" style="margin-right: 6px;"></i>
                                Company & Business Profile
                            </h3>
                            <span class="pm-badge-neutral">Vendor Credentials</span>
                        </div>
                        <div class="pm-card-body">
                            <div class="row g-3">
                                
                                <div class="col-md-7 col-sm-12">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="supp_comp_name">
                                            Supplier Company Name <span class="req">*</span>
                                        </label>
                                        <div class="pm-input-wrapper">
                                            <i class="fa fa-building-o pm-input-icon"></i>
                                            <input type="text" name="supp_comp_name" id="supp_comp_name" 
                                                   class="pm-control" placeholder="e.g. Sakar Trade Link Pvt Ltd" required autofocus />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-5 col-sm-12">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="supp_acc_no">
                                            Account Number / Vendor Code <span class="req">*</span>
                                        </label>
                                        <div class="pm-input-wrapper">
                                            <i class="fa fa-hashtag pm-input-icon"></i>
                                            <input type="text" name="supp_acc_no" id="supp_acc_no" 
                                                   class="pm-control" placeholder="e.g. SUPP-1049 or Bank A/C" required />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="pm-form-group mb-0">
                                        <label class="pm-form-label" for="comments">
                                            Comments & Trade Notes
                                        </label>
                                        <textarea name="comments" id="comments" class="pm-textarea" 
                                                  placeholder="Add any payment terms, supplier specialties, or notes (optional)..."></textarea>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Contact Person Details -->
                    <div class="pm-card">
                        <div class="pm-card-header">
                            <h3 class="pm-card-title">
                                <i class="fa fa-user-o text-muted" style="margin-right: 6px;"></i>
                                Primary Contact Person
                            </h3>
                            <span class="pm-badge-neutral">Contact Representative</span>
                        </div>
                        <div class="pm-card-body">
                            <div class="row g-3">

                                <div class="col-md-6 col-sm-12">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="fname">
                                            First Name <span class="req">*</span>
                                        </label>
                                        <div class="pm-input-wrapper">
                                            <i class="fa fa-user pm-input-icon"></i>
                                            <input type="text" name="fname" id="fname" 
                                                   class="pm-control" placeholder="First Name" required />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-12">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="lname">
                                            Last Name <span class="req">*</span>
                                        </label>
                                        <div class="pm-input-wrapper">
                                            <i class="fa fa-user pm-input-icon"></i>
                                            <input type="text" name="lname" id="lname" 
                                                   class="pm-control" placeholder="Last Name" required />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-12">
                                    <div class="pm-form-group mb-md-0">
                                        <label class="pm-form-label" for="ph_no">
                                            Mobile / Contact Phone <span class="req">*</span>
                                        </label>
                                        <div class="pm-input-wrapper">
                                            <i class="fa fa-phone pm-input-icon"></i>
                                            <input type="tel" name="ph_no" id="ph_no" 
                                                   class="pm-control" placeholder="e.g. 9820012345" required />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-12">
                                    <div class="pm-form-group mb-0">
                                        <label class="pm-form-label" for="email">
                                            Email Address <span class="req">*</span>
                                        </label>
                                        <div class="pm-input-wrapper">
                                            <i class="fa fa-envelope-o pm-input-icon"></i>
                                            <input type="email" name="email" id="email" 
                                                   class="pm-control" placeholder="supplier@example.com" required />
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Address & Location -->
                    <div class="pm-card">
                        <div class="pm-card-header">
                            <h3 class="pm-card-title">
                                <i class="fa fa-map-marker text-muted" style="margin-right: 6px;"></i>
                                Business Address & Location
                            </h3>
                            <span class="pm-badge-neutral">Billing Address</span>
                        </div>
                        <div class="pm-card-body">
                            <div class="row g-3">

                                <div class="col-md-6 col-sm-12">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="add1">
                                            Address Line 1 <span class="req">*</span>
                                        </label>
                                        <div class="pm-input-wrapper">
                                            <i class="fa fa-home pm-input-icon"></i>
                                            <input type="text" name="add1" id="add1" 
                                                   class="pm-control" placeholder="Shop / Office / Building No." required />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-12">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="add2">
                                            Address Line 2 <span class="req">*</span>
                                        </label>
                                        <div class="pm-input-wrapper">
                                            <i class="fa fa-map-signs pm-input-icon"></i>
                                            <input type="text" name="add2" id="add2" 
                                                   class="pm-control" placeholder="Street, Area, Landmark" required />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="city">
                                            City <span class="req">*</span>
                                        </label>
                                        <div class="pm-input-wrapper">
                                            <i class="fa fa-building pm-input-icon"></i>
                                            <input type="text" name="city" id="city" 
                                                   class="pm-control" value="Mumbai" required />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="state">
                                            State <span class="req">*</span>
                                        </label>
                                        <div class="pm-input-wrapper">
                                            <i class="fa fa-map pm-input-icon"></i>
                                            <input type="text" name="state" id="state" 
                                                   class="pm-control" value="Maharashtra" required />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="pincode">
                                            Pincode / Zip <span class="req">*</span>
                                        </label>
                                        <div class="pm-input-wrapper">
                                            <i class="fa fa-map-pin pm-input-icon"></i>
                                            <input type="text" name="pincode" id="pincode" 
                                                   class="pm-control" placeholder="e.g. 400054" required />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="pm-form-group">
                                        <label class="pm-form-label" for="country">
                                            Country <span class="req">*</span>
                                        </label>
                                        <div class="pm-input-wrapper">
                                            <i class="fa fa-globe pm-input-icon"></i>
                                            <input type="text" name="country" id="country" 
                                                   class="pm-control" value="India" required />
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Action Bar Card -->
                    <div class="pm-card" style="margin-bottom: 0;">
                        <div class="pm-card-body d-flex align-items-center justify-content-between flex-wrap gap-2 py-3">
                            <div style="font-size: 12.5px; color: var(--pm-slate-500);">
                                <i class="fa fa-info-circle text-muted" style="margin-right: 4px;"></i>
                                All fields marked with <span style="color:#ef4444;">*</span> are mandatory.
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="pm-btn-outline" onclick="resetSupplierForm()">
                                    <i class="fa fa-refresh"></i> Reset Fields
                                </button>
                                <button type="submit" id="btnSubmitSupplier" class="pm-btn-primary">
                                    <i class="fa fa-check"></i> Register Supplier
                                </button>
                            </div>
                        </div>
                    </div>

                </form>

            </div>

            <!-- Scripts -->
            <script>
                function resetSupplierForm() {
                    document.getElementById('addSupplierForm').reset();
                    $('#city').val('Mumbai');
                    $('#state').val('Maharashtra');
                    $('#country').val('India');
                    $('#supp_comp_name').focus();
                }

                $(document).ready(function() {
                    $('#addSupplierForm').on('submit', function(e) {
                        e.preventDefault();

                        var $btn = $('#btnSubmitSupplier');
                        var origHtml = $btn.html();
                        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Registering...');

                        var formData = $(this).serialize();

                        $.ajax({
                            url: 'add_supplier_insert.php',
                            type: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            data: formData,
                            dataType: 'json',
                            success: function(response) {
                                $btn.prop('disabled', false).html(origHtml);

                                if (response && response.status === 'success') {
                                    Swal.fire({
                                        title: 'Supplier Registered!',
                                        text: 'Supplier "' + (response.company_name || 'Vendor') + '" has been registered successfully.',
                                        icon: 'success',
                                        showCancelButton: true,
                                        confirmButtonColor: '#0f172a',
                                        cancelButtonColor: '#64748b',
                                        confirmButtonText: '<i class="fa fa-list"></i> View Suppliers List',
                                        cancelButtonText: '<i class="fa fa-plus"></i> Add Another Supplier'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = 'view_supplier.php';
                                        } else {
                                            resetSupplierForm();
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Registration Error',
                                        text: (response && response.message) ? response.message : 'Could not save supplier details. Please try again.',
                                        confirmButtonColor: '#0f172a'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                $btn.prop('disabled', false).html(origHtml);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Server Error',
                                    text: 'An error occurred while communicating with the server. Please try again.',
                                    confirmButtonColor: '#0f172a'
                                });
                            }
                        });
                    });
                });
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
