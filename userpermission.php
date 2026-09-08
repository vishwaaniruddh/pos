<?php 
session_start(); 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('top-header.php');
include('top-navbar.php');
?>
<div class="container-fluid page-body-wrapper">
    <?php 
    include('navbar.php');
    $con = OpenSrishringarrCon();

    $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

    // Handle Form Submission
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['submit']) || isset($_POST['is_ajax_submit']))) {
        $sub_menu = isset($_POST['sub_menu']) ? $_POST['sub_menu'] : [];
        
        if (is_array($sub_menu)) {
            $sub_menu_str = implode(',', array_filter(array_map('trim', $sub_menu)));
        } else {
            $sub_menu_str = trim($sub_menu);
        }

        $update = "UPDATE loginusers SET permission='" . mysqli_real_escape_string($con, $sub_menu_str) . "' WHERE id='" . $id . "'";
        $isOk = mysqli_query($con, $update);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            if ($isOk) {
                echo json_encode(['status' => 'success', 'message' => 'Permissions updated successfully!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error: ' . mysqli_error($con)]);
            }
            exit;
        }

        if ($isOk) {
            echo "<script>alert('Permissions Updated Successfully!'); window.location.href='./viewuser.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error updating permissions: " . addslashes(mysqli_error($con)) . "');</script>";
        }
    }

    // Fetch User Details
    $userData = null;
    $rawPerm = '';
    $userPermissions = [];
    $isAllPerm = false;

    if ($con && $id > 0) {
        $userQry = mysqli_query($con, "SELECT * FROM loginusers WHERE id='" . $id . "'");
        if ($userQry && $row = mysqli_fetch_assoc($userQry)) {
            $userData = $row;
            $rawPerm = trim($row['permission'] ?? '');
            if (strtoupper($rawPerm) === 'ALL') {
                $isAllPerm = true;
            } else if (!empty($rawPerm)) {
                $parts = explode(',', $rawPerm);
                foreach ($parts as $p) {
                    $cleanP = trim($p);
                    if ($cleanP !== '') {
                        $userPermissions[] = (string)$cleanP;
                    }
                }
            }
        }
    }
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

                .perm-page-container {
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

                /* User Profile Card */
                .pm-profile-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 14px 18px;
                    margin-bottom: 18px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 14px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                }

                .user-avatar-lg {
                    width: 44px;
                    height: 44px;
                    border-radius: 50%;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-800);
                    font-size: 16px;
                    font-weight: 700;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    text-transform: uppercase;
                    flex-shrink: 0;
                    margin-right: 14px !important;
                }

                .user-title-name {
                    font-size: 15px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    line-height: 1.2;
                }

                .user-title-handle {
                    font-size: 12.5px;
                    color: var(--pm-slate-500);
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    margin-top: 2px;
                }

                /* Presets Bar */
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
                    padding: 5px 12px;
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

                /* Main Card */
                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 20px;
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

                /* Permission Module Cards */
                .perm-module-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    overflow: hidden;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
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
                    cursor: pointer;
                }

                .perm-module-body {
                    padding: 12px 14px;
                    flex: 1;
                }

                .perm-check-item {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 5px 0;
                    font-size: 12.5px;
                    color: var(--pm-slate-700);
                    cursor: pointer;
                    transition: color 0.1s ease;
                }

                .perm-check-item:hover {
                    color: var(--pm-slate-900);
                }

                .pm-checkbox {
                    accent-color: var(--pm-slate-900);
                    width: 15px;
                    height: 15px;
                    cursor: pointer;
                    margin-right: 8px !important;
                }

                .pm-btn-primary i,
                .pm-btn-outline i {
                    margin-right: 6px !important;
                }

                .pm-page-title i,
                .pm-card-title i {
                    margin-right: 8px !important;
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
            </style>

            <div class="perm-page-container">

                <?php if (!$userData): ?>
                    <!-- User Not Found State -->
                    <div class="pm-card text-center p-5">
                        <i class="fa fa-exclamation-circle fa-2x text-muted mb-2"></i>
                        <h4 style="font-size: 16px; font-weight: 700; color: var(--pm-slate-900);">User Account Not Found</h4>
                        <p style="font-size: 13px; color: var(--pm-slate-500);">The requested user ID does not exist in the system database.</p>
                        <div class="mt-3">
                            <a href="viewuser.php" class="pm-btn-primary">
                                <i class="fa fa-arrow-left"></i> Return to User List
                            </a>
                        </div>
                    </div>
                <?php else: ?>

                    <?php
                    // Compute initials
                    $userName = trim($userData['name'] ?? 'Staff');
                    $userHandle = trim($userData['uname'] ?? '');
                    $userDesignation = trim($userData['designation'] ?? 'Staff');
                    $userLevel = trim($userData['level'] ?? '');
                    $userStatus = (int)($userData['user_status'] ?? 0);

                    $initials = '';
                    $parts = explode(' ', $userName);
                    foreach ($parts as $p) {
                        if (!empty($p)) $initials .= strtoupper($p[0]);
                        if (strlen($initials) >= 2) break;
                    }
                    if (empty($initials)) $initials = strtoupper(substr($userHandle, 0, 2));
                    ?>

                    <!-- Page Header -->
                    <div class="pm-page-header">
                        <div>
                            <h1 class="pm-page-title">
                                <i class="fa fa-sliders text-muted" style="font-size: 18px;"></i> 
                                Edit User Permissions
                            </h1>
                            <p class="pm-page-subtitle">Configure module visibility and navigation access for this staff account.</p>
                        </div>
                        <div>
                            <a href="viewuser.php" class="pm-btn-outline">
                                <i class="fa fa-arrow-left"></i> Back to User List
                            </a>
                        </div>
                    </div>

                    <!-- User Profile Header Card -->
                    <div class="pm-profile-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="user-avatar-lg">
                                <?php echo htmlspecialchars($initials); ?>
                            </div>
                            <div>
                                <div class="user-title-name">
                                    <?php echo htmlspecialchars($userName); ?>
                                    <span style="font-size: 12px; font-weight: 500; color: var(--pm-slate-400); margin-left: 4px;">(ID: <?php echo $id; ?>)</span>
                                </div>
                                <div class="user-title-handle">
                                    @<?php echo htmlspecialchars($userHandle); ?>
                                    &bull; 
                                    <span style="color: var(--pm-slate-700); font-weight: 500;"><?php echo htmlspecialchars($userDesignation); ?></span>
                                    <?php if (!empty($userLevel) && $userLevel !== 'NA' && $userLevel !== $userDesignation): ?>
                                        <span style="color: var(--pm-slate-500);"> (<?php echo htmlspecialchars($userLevel); ?>)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <span class="pm-badge-counter">
                                <i class="fa fa-circle" style="color: <?php echo ($userStatus === 1) ? '#22c55e' : '#94a3b8'; ?>; font-size: 8px;"></i>
                                <?php echo ($userStatus === 1) ? 'Active Account' : 'Inactive Account'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Main Permissions Card Form -->
                    <form id="permForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $id; ?>" method="POST">
                        <input type="hidden" name="id" value="<?php echo $id; ?>" />

                        <div class="pm-card">
                            <div class="pm-card-header">
                                <div>
                                    <h3 class="pm-card-title">
                                        <i class="fa fa-th-large text-muted"></i> Navigation & Feature Modules
                                    </h3>
                                    <p class="pm-page-subtitle" style="margin-top: 2px;">Check or uncheck the individual features this staff member is allowed to use.</p>
                                </div>

                                <!-- Preset Pills -->
                                <div class="pm-presets-bar">
                                    <span style="font-size: 11.5px; font-weight: 600; color: var(--pm-slate-500); margin-right: 4px;">Presets:</span>
                                    <button type="button" class="pm-preset-btn" onclick="applyPreset(this, 'admin')">Full Access (All)</button>
                                    <button type="button" class="pm-preset-btn" onclick="applyPreset(this, 'billing')">Store & Billing</button>
                                    <button type="button" class="pm-preset-btn" onclick="applyPreset(this, 'reports')">Reports & Inventory</button>
                                    <button type="button" class="pm-preset-btn" onclick="applyPreset(this, 'restore')">Reset to Saved</button>
                                    <button type="button" class="pm-preset-btn" onclick="applyPreset(this, 'clear')">Clear All</button>
                                </div>
                            </div>

                            <div class="pm-card-body">
                                <div class="row g-3">
                                    <?php
                                    $mainsql = mysqli_query($con, "SELECT * FROM main_menu WHERE status=1 ORDER BY id ASC");
                                    if ($mainsql) {
                                        while ($mainRow = mysqli_fetch_assoc($mainsql)) {
                                            $mainId = (int)$mainRow['id'];
                                            $mainName = htmlspecialchars($mainRow['name']);

                                            $subsql = mysqli_query($con, "SELECT * FROM sub_menu WHERE main_menu='$mainId' AND status=1 ORDER BY id ASC");
                                            $subRows = [];
                                            if ($subsql) {
                                                while ($sRow = mysqli_fetch_assoc($subsql)) {
                                                    $subRows[] = $sRow;
                                                }
                                            }

                                            if (empty($subRows)) continue;

                                            // Determine if all sub-items are checked
                                            $totalSubCount = count($subRows);
                                            $checkedSubCount = 0;
                                            foreach ($subRows as $s) {
                                                $sid = (string)$s['id'];
                                                if ($isAllPerm || in_array($sid, $userPermissions, true)) {
                                                    $checkedSubCount++;
                                                }
                                            }
                                            $isMainChecked = ($totalSubCount > 0 && $totalSubCount === $checkedSubCount);
                                            ?>
                                            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                                <div class="perm-module-card">
                                                    <!-- Module Header -->
                                                    <div class="perm-module-header">
                                                        <label class="perm-module-title">
                                                            <input type="checkbox" 
                                                                   class="pm-checkbox main-menu-toggle" 
                                                                   data-main-id="<?php echo $mainId; ?>" 
                                                                   <?php if ($isMainChecked) echo 'checked'; ?> 
                                                                   onchange="toggleMainGroup(this, <?php echo $mainId; ?>)" />
                                                            <span><?php echo $mainName; ?></span>
                                                        </label>
                                                        <span class="text-muted" style="font-size: 11px;">
                                                            <?php echo $totalSubCount; ?> items
                                                        </span>
                                                    </div>

                                                    <!-- Module Items -->
                                                    <div class="perm-module-body">
                                                        <?php foreach ($subRows as $sRow): ?>
                                                            <?php 
                                                            $subId = (string)$sRow['id']; 
                                                            $isChecked = ($isAllPerm || in_array($subId, $userPermissions, true));
                                                            ?>
                                                            <label class="perm-check-item">
                                                                <input type="checkbox" 
                                                                       class="pm-checkbox sub-menu-item sub-group-<?php echo $mainId; ?>" 
                                                                       name="sub_menu[]" 
                                                                       value="<?php echo $subId; ?>" 
                                                                       data-main-id="<?php echo $mainId; ?>" 
                                                                       data-saved-checked="<?php echo $isChecked ? '1' : '0'; ?>"
                                                                       <?php if ($isChecked) echo 'checked'; ?> 
                                                                       onchange="updateMainToggleState(<?php echo $mainId; ?>); updateCountBadge();" />
                                                                <span><?php echo htmlspecialchars($sRow['sub_menu']); ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Sticky Action Bottom Toolbar -->
                        <div class="pm-sticky-actions">
                            <div class="d-flex align-items-center gap-2">
                                <span class="pm-badge-counter" id="permCountBadge">
                                    <i class="fa fa-check-circle text-muted"></i> 0 Permissions Granted
                                </span>
                                <span style="font-size: 12px; color: var(--pm-slate-500);">Changes take effect immediately on staff login.</span>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="viewuser.php" class="pm-btn-outline">
                                    <i class="fa fa-times"></i> Cancel
                                </a>
                                <button type="submit" name="submit" id="saveBtn" class="pm-btn-primary">
                                    <i class="fa fa-save"></i> Save Permissions
                                </button>
                            </div>
                        </div>

                    </form>

                <?php endif; ?>

            </div>

            <!-- Client-side Logic -->
            <script>
                $(document).ready(function() {
                    updateCountBadge();

                    // Wire AJAX form submission
                    $('#permForm').on('submit', function(e) {
                        e.preventDefault();

                        var $btn = $('#saveBtn');
                        var origHtml = $btn.html();
                        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                        var formData = $(this).serialize();
                        formData += '&is_ajax_submit=1';

                        $.ajax({
                            url: $(this).attr('action'),
                            type: 'POST',
                            data: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            success: function(response) {
                                $btn.prop('disabled', false).html(origHtml);
                                var isSuccess = false;

                                try {
                                    var resObj = typeof response === 'object' ? response : JSON.parse(response);
                                    if (resObj.status === 'success') {
                                        isSuccess = true;
                                    }
                                } catch(e) {
                                    if (typeof response === 'string' && response.indexOf('Successfully') !== -1) {
                                        isSuccess = true;
                                    }
                                }

                                if (isSuccess) {
                                    // Update saved checked snapshot
                                    $('.sub-menu-item').each(function() {
                                        $(this).attr('data-saved-checked', $(this).is(':checked') ? '1' : '0');
                                    });

                                    Swal.fire({
                                        toast: true,
                                        position: 'top-end',
                                        icon: 'success',
                                        title: 'Permissions Saved Successfully!',
                                        showConfirmButton: false,
                                        timer: 2000
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Update Failed',
                                        text: 'Could not save permissions. Please verify database connection.',
                                        confirmButtonColor: '#0f172a'
                                    });
                                }
                            },
                            error: function() {
                                $btn.prop('disabled', false).html(origHtml);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Server Error',
                                    text: 'Network error occurred while updating permissions.',
                                    confirmButtonColor: '#0f172a'
                                });
                            }
                        });
                    });
                });

                function toggleMainGroup(masterCheck, mainId) {
                    var isChecked = masterCheck.checked;
                    $('.sub-group-' + mainId).prop('checked', isChecked);
                    updateCountBadge();
                }

                function updateMainToggleState(mainId) {
                    var total = $('.sub-group-' + mainId).length;
                    var checked = $('.sub-group-' + mainId + ':checked').length;
                    $('.main-menu-toggle[data-main-id="' + mainId + '"]').prop('checked', total > 0 && total === checked);
                }

                function updateCountBadge() {
                    var totalChecked = $('.sub-menu-item:checked').length;
                    var totalAll = $('.sub-menu-item').length;

                    if (totalChecked === totalAll && totalAll > 0) {
                        $('#permCountBadge').html('<i class="fa fa-check-circle" style="color:#22c55e;"></i> All (' + totalChecked + ') Permissions Granted');
                    } else {
                        $('#permCountBadge').html('<i class="fa fa-check-circle text-muted"></i> ' + totalChecked + ' of ' + totalAll + ' Permissions Granted');
                    }
                }

                function applyPreset(btnEl, presetType) {
                    $('.pm-preset-btn').removeClass('active');
                    if (btnEl) $(btnEl).addClass('active');

                    if (presetType === 'admin') {
                        $('.sub-menu-item').prop('checked', true);
                        $('.main-menu-toggle').prop('checked', true);
                    } else if (presetType === 'clear') {
                        $('.sub-menu-item').prop('checked', false);
                        $('.main-menu-toggle').prop('checked', false);
                    } else if (presetType === 'restore') {
                        $('.sub-menu-item').each(function() {
                            var wasSaved = $(this).attr('data-saved-checked') === '1';
                            $(this).prop('checked', wasSaved);
                        });
                        $('.main-menu-toggle').each(function() {
                            var mId = $(this).data('main-id');
                            updateMainToggleState(mId);
                        });
                    } else if (presetType === 'billing') {
                        $('.sub-menu-item').prop('checked', false);
                        // Main 1 (Dashboard), 5 (Reports), 8 (GST), 9 (Item Code)
                        $('.sub-group-1, .sub-group-5, .sub-group-8, .sub-group-9').prop('checked', true);
                        $('.main-menu-toggle').each(function() {
                            var mId = $(this).data('main-id');
                            updateMainToggleState(mId);
                        });
                    } else if (presetType === 'reports') {
                        $('.sub-menu-item').prop('checked', false);
                        // Main 1 (Dashboard), 5 (Reports), 7 (Bank), 8 (GST)
                        $('.sub-group-1, .sub-group-5, .sub-group-7, .sub-group-8').prop('checked', true);
                        $('.main-menu-toggle').each(function() {
                            var mId = $(this).data('main-id');
                            updateMainToggleState(mId);
                        });
                    }

                    updateCountBadge();
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