<?php 
session_start(); 

include('top-header.php');
include('top-navbar.php');
?>
<div class="container-fluid page-body-wrapper">
    <?php 
    include('navbar.php');
    $con = OpenSrishringarrCon();
    ?>
    
    <!-- partial -->
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">

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

                .adduser-container {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                /* Page Header */
                .pm-page-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 22px;
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

                /* Cards */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 18px;
                    overflow: hidden;
                }

                .pm-card-header {
                    padding: 14px 18px;
                    background: #ffffff;
                    border-bottom: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 8px;
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
                    padding: 18px;
                }

                /* Form Controls */
                .pm-form-group {
                    margin-bottom: 16px;
                }

                .pm-label {
                    font-size: 12.5px;
                    font-weight: 600;
                    color: var(--pm-slate-700);
                    margin-bottom: 6px;
                    display: block;
                }

                .pm-label .required-star {
                    color: #ef4444;
                    margin-left: 2px;
                }

                .pm-input-group {
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
                    z-index: 2;
                }

                .pm-form-control {
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

                .pm-form-control.no-icon {
                    padding-left: 12px;
                }

                .pm-form-control:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                    outline: none;
                }

                .pm-password-toggle {
                    position: absolute;
                    right: 12px;
                    color: var(--pm-slate-400);
                    cursor: pointer;
                    font-size: 13px;
                    z-index: 2;
                    transition: color 0.15s;
                }

                .pm-password-toggle:hover {
                    color: var(--pm-slate-800);
                }

                .pm-helper-text {
                    font-size: 11.5px;
                    color: var(--pm-slate-500);
                    margin-top: 4px;
                }

                /* Buttons */
                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border: 1px solid var(--pm-slate-900);
                    font-size: 13px;
                    font-weight: 600;
                    border-radius: 6px;
                    padding: 8px 20px;
                    height: 38px;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
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
                    padding: 6px 14px;
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

                /* Preset Pills */
                .pm-presets-bar {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    flex-wrap: wrap;
                }

                .pm-preset-btn {
                    background: var(--pm-slate-50);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
                    font-size: 12px;
                    font-weight: 500;
                    padding: 4px 10px;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                }

                .pm-preset-btn:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900);
                    border-color: #cbd5e1;
                }

                .pm-preset-btn.active {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border-color: var(--pm-slate-900);
                }

                /* Permission Module Cards */
                .perm-module-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    margin-bottom: 12px;
                    overflow: hidden;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .perm-module-card:hover {
                    border-color: #cbd5e1;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                }

                .perm-module-header {
                    padding: 10px 14px;
                    background: var(--pm-slate-50);
                    border-bottom: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }

                .perm-module-title {
                    font-size: 13px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin: 0;
                }

                .perm-module-body {
                    padding: 12px 14px;
                }

                .perm-check-item {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 5px 0;
                    font-size: 12.5px;
                    color: var(--pm-slate-700);
                    cursor: pointer;
                }

                .perm-check-item:hover {
                    color: var(--pm-slate-900);
                }

                .pm-checkbox {
                    accent-color: var(--pm-slate-900);
                    width: 15px;
                    height: 15px;
                    cursor: pointer;
                }

                /* Sticky Action Footer */
                .pm-sticky-actions {
                    position: sticky;
                    bottom: 0;
                    background: rgba(255, 255, 255, 0.95);
                    backdrop-filter: blur(8px);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 14px 20px;
                    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.04);
                    margin-top: 24px;
                    margin-bottom: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 12px;
                    z-index: 10;
                }

                .pm-badge-counter {
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-800);
                    font-size: 12px;
                    font-weight: 600;
                    padding: 4px 12px;
                    border-radius: 9999px;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                }
            </style>

            <div class="adduser-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa fa-user-plus text-muted" style="font-size: 18px;"></i> 
                            Create New User
                        </h1>
                        <p class="pm-page-subtitle">Configure user credentials, administrative roles, and granular module permissions.</p>
                    </div>
                    <div>
                        <a href="viewuser.php" class="pm-btn-outline">
                            <i class="fa fa-users"></i> View All Users
                        </a>
                    </div>
                </div>

                <form id="addUserForm" action="signup.php" method="POST">

                    <div class="row g-3">
                        <!-- Left Column: Personal Info -->
                        <div class="col-lg-6 col-md-12">
                            <div class="pm-card h-100 mb-0">
                                <div class="pm-card-header">
                                    <h3 class="pm-card-title">
                                        <i class="fa fa-id-card-o text-muted"></i> User Information
                                    </h3>
                                    <span class="text-muted" style="font-size: 11.5px;">Step 1 of 2</span>
                                </div>
                                <div class="pm-card-body">
                                    
                                    <!-- Full Name -->
                                    <div class="pm-form-group">
                                        <label class="pm-label" for="name">
                                            Full Name <span class="required-star">*</span>
                                        </label>
                                        <div class="pm-input-group">
                                            <i class="fa fa-user pm-input-icon"></i>
                                            <input type="text" class="pm-form-control" id="name" name="name" 
                                                   placeholder="e.g. Aniruddh Khandelwal" required />
                                        </div>
                                    </div>

                                    <!-- Username -->
                                    <div class="pm-form-group">
                                        <label class="pm-label" for="uname">
                                            Username / Login ID <span class="required-star">*</span>
                                        </label>
                                        <div class="pm-input-group">
                                            <i class="fa fa-at pm-input-icon"></i>
                                            <input type="text" class="pm-form-control" id="uname" name="uname" 
                                                   placeholder="e.g. aniruddh or store.manager" required />
                                        </div>
                                        <div class="pm-helper-text">Unique handle used to sign in to POS and reports.</div>
                                    </div>

                                    <!-- Email Address -->
                                    <div class="pm-form-group">
                                        <label class="pm-label" for="email">
                                            Email Address <span class="required-star">*</span>
                                        </label>
                                        <div class="pm-input-group">
                                            <i class="fa fa-envelope-o pm-input-icon"></i>
                                            <input type="email" class="pm-form-control" id="email" name="email" 
                                                   placeholder="e.g. staff@srishringarr.com" required />
                                        </div>
                                    </div>

                                    <!-- Contact Phone -->
                                    <div class="pm-form-group mb-0">
                                        <label class="pm-label" for="contact">
                                            Contact Number <span class="required-star">*</span>
                                        </label>
                                        <div class="pm-input-group">
                                            <i class="fa fa-phone pm-input-icon"></i>
                                            <input type="text" class="pm-form-control" id="contact" name="contact" 
                                                   placeholder="e.g. +91 9876543210" required />
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Password & Role -->
                        <div class="col-lg-6 col-md-12">
                            <div class="pm-card h-100 mb-0">
                                <div class="pm-card-header">
                                    <h3 class="pm-card-title">
                                        <i class="fa fa-lock text-muted"></i> Security & Role Assignment
                                    </h3>
                                    <span class="text-muted" style="font-size: 11.5px;">Step 2 of 2</span>
                                </div>
                                <div class="pm-card-body">
                                    
                                    <!-- Password -->
                                    <div class="pm-form-group">
                                        <label class="pm-label" for="pwd">
                                            Account Password <span class="required-star">*</span>
                                        </label>
                                        <div class="pm-input-group">
                                            <i class="fa fa-key pm-input-icon"></i>
                                            <input type="password" class="pm-form-control" id="pwd" name="pwd" 
                                                   placeholder="Enter a secure password" required />
                                            <i class="fa fa-eye pm-password-toggle" id="togglePassword" title="Show/Hide password"></i>
                                        </div>
                                        <div class="pm-helper-text">Minimum 6 characters recommended.</div>
                                    </div>

                                    <!-- Designation -->
                                    <div class="pm-form-group">
                                        <label class="pm-label" for="designation">
                                            Designation <span class="required-star">*</span>
                                        </label>
                                        <div class="pm-input-group">
                                            <i class="fa fa-briefcase pm-input-icon"></i>
                                            <input type="text" class="pm-form-control" id="designation" name="designation" 
                                                   placeholder="e.g. Store Manager, Cashier, Sales Associate" list="designationList" required />
                                            <datalist id="designationList">
                                                <option value="Store Manager">
                                                <option value="Cashier">
                                                <option value="Sales Staff">
                                                <option value="Retail Head">
                                                <option value="Accountant">
                                                <option value="Administrator">
                                            </datalist>
                                        </div>
                                    </div>

                                    <!-- Access Level -->
                                    <div class="pm-form-group mb-0">
                                        <label class="pm-label" for="level">
                                            Access Level <span class="required-star">*</span>
                                        </label>
                                        <div class="pm-input-group">
                                            <i class="fa fa-shield pm-input-icon"></i>
                                            <input type="text" class="pm-form-control" id="level" name="level" 
                                                   placeholder="e.g. Admin, Manager, Staff" list="levelList" required />
                                            <datalist id="levelList">
                                                <option value="Admin">
                                                <option value="Manager">
                                                <option value="Retail Head">
                                                <option value="Staff">
                                                <option value="Cashier">
                                                <option value="Account">
                                            </datalist>
                                        </div>
                                        <div class="pm-helper-text">Defines role hierarchy and reporting authority.</div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Section -->
                    <div class="pm-card mt-3">
                        <div class="pm-card-header">
                            <div>
                                <h3 class="pm-card-title">
                                    <i class="fa fa-sliders text-muted"></i> System Module Access Permissions
                                </h3>
                                <p class="pm-page-subtitle" style="margin-top: 2px;">Select individual modules or choose a quick permission preset.</p>
                            </div>
                            <div class="pm-presets-bar">
                                <span style="font-size: 11.5px; font-weight: 600; color: var(--pm-slate-500); margin-right: 4px;">Presets:</span>
                                <button type="button" class="pm-preset-btn" onclick="applyPreset(this, 'admin')">Full Access (Admin)</button>
                                <button type="button" class="pm-preset-btn" onclick="applyPreset(this, 'billing')">Store & Billing</button>
                                <button type="button" class="pm-preset-btn" onclick="applyPreset(this, 'reports')">Reports & Inventory</button>
                                <button type="button" class="pm-preset-btn" onclick="applyPreset(this, 'clear')">Clear All</button>
                            </div>
                        </div>
                        <div class="pm-card-body">

                            <!-- Hidden Permission String (Submitted to DB) -->
                            <input type="hidden" id="permission" name="permission" value="" required />

                            <div class="row g-3">
                                <?php
                                if ($con) {
                                    $mainsql = mysqli_query($con, "SELECT * FROM main_menu WHERE status=1 ORDER BY id ASC");
                                    if ($mainsql) {
                                        while ($mainRow = mysqli_fetch_assoc($mainsql)) {
                                            $mainId = $mainRow['id'];
                                            $mainName = htmlspecialchars($mainRow['name']);
                                            
                                            $subsql = mysqli_query($con, "SELECT * FROM sub_menu WHERE main_menu='$mainId' AND status=1 ORDER BY id ASC");
                                            $subRows = [];
                                            if ($subsql) {
                                                while ($sRow = mysqli_fetch_assoc($subsql)) {
                                                    $subRows[] = $sRow;
                                                }
                                            }

                                            if (empty($subRows)) continue;
                                            ?>
                                            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                                <div class="perm-module-card h-100">
                                                    <div class="perm-module-header">
                                                        <label class="perm-module-title" style="cursor: pointer;">
                                                            <input type="checkbox" class="pm-checkbox main-menu-toggle" 
                                                                   data-main-id="<?php echo $mainId; ?>" 
                                                                   onchange="toggleMainGroup(this, <?php echo $mainId; ?>)" />
                                                            <span><?php echo $mainName; ?></span>
                                                        </label>
                                                        <span class="text-muted" style="font-size: 11px;">
                                                            <?php echo count($subRows); ?> items
                                                        </span>
                                                    </div>
                                                    <div class="perm-module-body">
                                                        <?php foreach ($subRows as $sRow): ?>
                                                            <?php $subId = $sRow['id']; ?>
                                                            <label class="perm-check-item">
                                                                <input type="checkbox" 
                                                                       class="pm-checkbox sub-menu-item sub-group-<?php echo $mainId; ?>" 
                                                                       value="<?php echo $subId; ?>" 
                                                                       data-main-id="<?php echo $mainId; ?>" 
                                                                       onchange="syncPermissionString()" />
                                                                <span><?php echo htmlspecialchars($sRow['sub_menu']); ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    }
                                }
                                ?>
                            </div>

                        </div>
                    </div>

                    <!-- Sticky Action Toolbar -->
                    <div class="pm-sticky-actions">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pm-badge-counter" id="permCounterBadge">
                                <i class="fa fa-check-circle text-muted"></i> 0 Permissions Selected
                            </span>
                            <span style="font-size: 12px; color: var(--pm-slate-500);">Verify details before generating account.</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="reset" class="pm-btn-outline" onclick="setTimeout(syncPermissionString, 50);">
                                <i class="fa fa-refresh"></i> Reset
                            </button>
                            <button type="submit" id="submitBtn" class="pm-btn-primary">
                                <i class="fa fa-user-plus"></i> Create User Account
                            </button>
                        </div>
                    </div>

                </form>

            </div>

            <!-- Client-side Logic & Validation -->
            <script>
                $(document).ready(function() {
                    // Password visibility toggle
                    $('#togglePassword').on('click', function() {
                        var pwdInput = $('#pwd');
                        var type = pwdInput.attr('type') === 'password' ? 'text' : 'password';
                        pwdInput.attr('type', type);
                        $(this).toggleClass('fa-eye fa-eye-slash');
                    });

                    // Initial sync
                    syncPermissionString();

                    // AJAX Form Submission
                    $('#addUserForm').on('submit', function(e) {
                        e.preventDefault();

                        var permStr = $('#permission').val().trim();
                        if (!permStr) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Permissions Required',
                                text: 'Please select at least one module access permission for this user.',
                                confirmButtonColor: '#0f172a'
                            });
                            return false;
                        }

                        var $btn = $('#submitBtn');
                        var origHtml = $btn.html();
                        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creating Account...');

                        $.ajax({
                            url: 'signup.php',
                            type: 'POST',
                            data: $(this).serialize(),
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            success: function(response) {
                                $btn.prop('disabled', false).html(origHtml);
                                var isSuccess = false;

                                try {
                                    var resObj = typeof response === 'object' ? response : JSON.parse(response);
                                    if (resObj.status === 'success' || (resObj.message && resObj.message.indexOf('successful') !== -1)) {
                                        isSuccess = true;
                                    }
                                } catch(e) {
                                    if (typeof response === 'string' && response.toLowerCase().indexOf('successful') !== -1) {
                                        isSuccess = true;
                                    }
                                }

                                if (isSuccess) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'User Created Successfully!',
                                        text: 'The login account has been configured and is ready for use.',
                                        confirmButtonColor: '#0f172a',
                                        confirmButtonText: 'View All Users'
                                    }).then(function() {
                                        window.location.href = 'viewuser.php';
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Creation Failed',
                                        text: (typeof response === 'string') ? response : 'Unable to create account. Please check user details.',
                                        confirmButtonColor: '#0f172a'
                                    });
                                }
                            },
                            error: function() {
                                $btn.prop('disabled', false).html(origHtml);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Network Error',
                                    text: 'Could not connect to the server. Please verify database connection.',
                                    confirmButtonColor: '#0f172a'
                                });
                            }
                        });
                    });
                });

                function toggleMainGroup(masterCheckbox, mainId) {
                    var isChecked = masterCheckbox.checked;
                    $('.sub-group-' + mainId).prop('checked', isChecked);
                    syncPermissionString();
                }

                function syncPermissionString() {
                    var selectedIds = [];
                    $('.sub-menu-item:checked').each(function() {
                        selectedIds.push($(this).val());
                    });

                    // Update Main group checkbox state (checked if all sub items checked)
                    $('.main-menu-toggle').each(function() {
                        var mainId = $(this).data('main-id');
                        var totalSubs = $('.sub-group-' + mainId).length;
                        var checkedSubs = $('.sub-group-' + mainId + ':checked').length;
                        $(this).prop('checked', totalSubs > 0 && totalSubs === checkedSubs);
                    });

                    var permVal = selectedIds.join(',');
                    $('#permission').val(permVal);

                    var countText = selectedIds.length + ' Permission' + (selectedIds.length === 1 ? '' : 's') + ' Selected';
                    $('#permCounterBadge').html('<i class="fa fa-check-circle text-muted"></i> ' + countText);
                }

                function applyPreset(btnEl, presetType) {
                    $('.pm-preset-btn').removeClass('active');
                    if (btnEl) $(btnEl).addClass('active');

                    if (presetType === 'admin') {
                        // Check all
                        $('.sub-menu-item').prop('checked', true);
                        $('.main-menu-toggle').prop('checked', true);
                        $('#permission').val('ALL');
                    } else if (presetType === 'clear') {
                        // Uncheck all
                        $('.sub-menu-item').prop('checked', false);
                        $('.main-menu-toggle').prop('checked', false);
                        $('#permission').val('');
                    } else if (presetType === 'billing') {
                        // Check Dashboard, Reports, GST, Item Code
                        $('.sub-menu-item').prop('checked', false);
                        // Main menu 1 (Dashboard), 5 (Reports), 8 (GST), 9 (Item Code)
                        $('.sub-group-1, .sub-group-5, .sub-group-8, .sub-group-9').prop('checked', true);
                    } else if (presetType === 'reports') {
                        // Check Dashboard, Reports, Bank, GST
                        $('.sub-menu-item').prop('checked', false);
                        $('.sub-group-1, .sub-group-5, .sub-group-7, .sub-group-8').prop('checked', true);
                    }

                    if (presetType !== 'admin') {
                        syncPermissionString();
                    } else {
                        var totalCount = $('.sub-menu-item').length;
                        $('#permCounterBadge').html('<i class="fa fa-check-circle text-muted"></i> All (' + totalCount + ') Permissions Granted');
                    }
                }
            </script>

        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</div>
</div>
</div>

<script src="vendors/js/vendor.bundle.base.js"></script>
<script src="vendors/js/vendor.bundle.addons.js"></script>
<script src="js/off-canvas.js"></script>
<script src="js/hoverable-collapse.js"></script>
<script src="js/misc.js"></script>
<script src="js/settings.js"></script>
<script src="js/todolist.js"></script>

</body>
</html>