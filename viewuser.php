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

                .viewuser-container {
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

                /* Stats Summary Cards */
                .pm-stat-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    padding: 14px 16px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    height: 100%;
                }

                .pm-stat-title {
                    font-size: 11.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    color: var(--pm-slate-500);
                    margin-bottom: 4px;
                }

                .pm-stat-val {
                    font-size: 22px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    line-height: 1;
                }

                .pm-stat-icon-wrap {
                    width: 38px;
                    height: 38px;
                    border-radius: 8px;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--pm-slate-700);
                    font-size: 14px;
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

                /* Search & Filter Bar */
                .pm-toolbar {
                    padding: 12px 16px;
                    background: #ffffff;
                    border-bottom: 1px solid var(--pm-slate-200);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 12px;
                }

                .pm-search-input {
                    height: 36px;
                    font-size: 13px;
                    border-radius: 6px;
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-900);
                    padding: 6px 12px 6px 34px;
                    width: 260px;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-search-input:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                    outline: none;
                }

                .pm-select-sm {
                    height: 36px;
                    font-size: 12.5px;
                    border-radius: 6px;
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-800);
                    background: #ffffff;
                    padding: 4px 10px;
                }

                .pm-select-sm:focus {
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 1px var(--pm-slate-900);
                    outline: none;
                }

                /* Table Styling */
                .pm-table {
                    width: 100%;
                    border-collapse: separate;
                    border-spacing: 0;
                    margin: 0;
                }

                .pm-table th {
                    background: var(--pm-slate-50);
                    color: var(--pm-slate-500);
                    font-size: 11.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    padding: 10px 16px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    white-space: nowrap;
                }

                .pm-table td {
                    padding: 12px 16px;
                    vertical-align: middle;
                    border-bottom: 1px solid var(--pm-slate-100);
                    font-size: 13px;
                    color: var(--pm-slate-800);
                    background: #ffffff;
                    transition: background 0.1s ease;
                }

                .pm-table tr:last-child td {
                    border-bottom: none;
                }

                .pm-table tr:hover td {
                    background: var(--pm-slate-50);
                }

                /* User Meta Column */
                .user-avatar-pill {
                    width: 36px;
                    height: 36px;
                    border-radius: 50%;
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
                    font-size: 12.5px;
                    font-weight: 700;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    text-transform: uppercase;
                    flex-shrink: 0;
                    margin-right: 14px !important;
                }

                .user-name-text {
                    font-weight: 600;
                    color: var(--pm-slate-900);
                    font-size: 13.5px;
                    line-height: 1.2;
                }

                .user-handle-text {
                    font-size: 12px;
                    color: var(--pm-slate-500);
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    margin-top: 2px;
                }

                /* Badges */
                .pm-badge {
                    display: inline-flex;
                    align-items: center;
                    font-size: 11.5px;
                    font-weight: 600;
                    padding: 4px 10px;
                    border-radius: 9999px;
                }

                .pm-badge-active {
                    background: #f0fdf4;
                    color: #166534;
                    border: 1px solid #bbf7d0;
                }

                .pm-badge-inactive {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-600);
                    border: 1px solid var(--pm-slate-200);
                }

                .status-dot {
                    width: 6px;
                    height: 6px;
                    border-radius: 50%;
                    margin-right: 6px !important;
                    display: inline-block;
                }

                .status-dot.active {
                    background: #22c55e;
                }

                .status-dot.inactive {
                    background: #94a3b8;
                }

                .pm-role-pill {
                    background: var(--pm-slate-100);
                    border: 1px solid var(--pm-slate-200);
                    color: var(--pm-slate-700);
                    font-size: 11.5px;
                    font-weight: 600;
                    padding: 2px 8px;
                    border-radius: 4px;
                }

                /* Buttons */
                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff;
                    border: 1px solid var(--pm-slate-900);
                    font-size: 12.5px;
                    font-weight: 600;
                    border-radius: 6px;
                    padding: 7px 16px;
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

                .pm-btn-outline-sm {
                    background: #ffffff;
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
                    font-size: 12px;
                    font-weight: 500;
                    border-radius: 6px;
                    padding: 4px 10px;
                    height: 28px;
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                }

                .pm-btn-outline-sm:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900);
                    border-color: #cbd5e1;
                }

                .pm-btn-status-toggle {
                    background: #ffffff;
                    color: var(--pm-slate-600);
                    border: 1px solid var(--pm-slate-200);
                    font-size: 11.5px;
                    font-weight: 500;
                    border-radius: 6px;
                    padding: 4px 9px;
                    height: 28px;
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                }

                .pm-btn-status-toggle:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900);
                    border-color: #cbd5e1;
                }

                .pm-btn-outline-sm i,
                .pm-btn-status-toggle i,
                .pm-btn-primary i {
                    margin-right: 6px !important;
                }
            </style>

            <div class="viewuser-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa fa-users text-muted" style="font-size: 18px;"></i> 
                            User Accounts & Staff
                        </h1>
                        <p class="pm-page-subtitle">Manage POS staff credentials, system roles, account status, and module permissions.</p>
                    </div>
                    <div>
                        <a href="adduser.php" class="pm-btn-primary">
                            <i class="fa fa-plus"></i> Add New User
                        </a>
                    </div>
                </div>

                <?php
                // Pre-compute user counts
                $totalUsers = 0;
                $activeUsers = 0;
                $inactiveUsers = 0;

                $countSql = "SELECT user_status, COUNT(*) as cnt FROM loginusers GROUP BY user_status";
                $countRes = $con ? $con->query($countSql) : false;
                if ($countRes) {
                    while ($cRow = $countRes->fetch_assoc()) {
                        $cnt = (int)$cRow['cnt'];
                        $totalUsers += $cnt;
                        if ((int)$cRow['user_status'] === 1) {
                            $activeUsers += $cnt;
                        } else {
                            $inactiveUsers += $cnt;
                        }
                    }
                }
                ?>

                <!-- Stats Summary Row -->
                <div class="row g-3 mb-3">
                    <div class="col-lg-4 col-md-4 col-sm-12">
                        <div class="pm-stat-card">
                            <div>
                                <div class="pm-stat-title">Total Accounts</div>
                                <div class="pm-stat-val"><?php echo $totalUsers; ?></div>
                            </div>
                            <div class="pm-stat-icon-wrap">
                                <i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6">
                        <div class="pm-stat-card">
                            <div>
                                <div class="pm-stat-title">Active Accounts</div>
                                <div class="pm-stat-val" style="color: #166534;"><?php echo $activeUsers; ?></div>
                            </div>
                            <div class="pm-stat-icon-wrap">
                                <i class="fa fa-check-circle-o" style="color: #22c55e;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6">
                        <div class="pm-stat-card">
                            <div>
                                <div class="pm-stat-title">Inactive Accounts</div>
                                <div class="pm-stat-val" style="color: #64748b;"><?php echo $inactiveUsers; ?></div>
                            </div>
                            <div class="pm-stat-icon-wrap">
                                <i class="fa fa-ban" style="color: #94a3b8;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Data Card -->
                <div class="pm-card">
                    <!-- Toolbar -->
                    <div class="pm-toolbar">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <!-- Search -->
                            <div class="position-relative">
                                <i class="fa fa-search position-absolute text-muted" style="left: 12px; top: 11px; font-size: 13px;"></i>
                                <input type="text" id="userSearchInput" class="pm-search-input" placeholder="Search by name, user, email..." />
                            </div>

                            <!-- Status Filter -->
                            <select id="statusFilter" class="pm-select-sm">
                                <option value="all">All Statuses (<?php echo $totalUsers; ?>)</option>
                                <option value="active">Active Only (<?php echo $activeUsers; ?>)</option>
                                <option value="inactive">Inactive Only (<?php echo $inactiveUsers; ?>)</option>
                            </select>
                        </div>

                        <div class="text-muted" style="font-size: 12px;" id="userCountSummary">
                            Showing <span id="visibleUserCount"><?php echo $totalUsers; ?></span> user accounts
                        </div>
                    </div>

                    <!-- Table -->
                    <div style="overflow-x: auto;">
                        <table class="pm-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">#</th>
                                    <th>Staff Member</th>
                                    <th>Role / Designation</th>
                                    <th>Contact Details</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="text-align: right; width: 220px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM loginusers ORDER BY id ASC";
                                $result = $con ? $con->query($sql) : false;
                                $srno = 1;

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $id = (int)$row['id'];
                                        $name = trim($row['name'] ?? '');
                                        $uname = trim($row['uname'] ?? '');
                                        $email = trim($row['email'] ?? '');
                                        $contact = trim($row['contact'] ?? '');
                                        $designation = trim($row['designation'] ?? 'Staff');
                                        $level = trim($row['level'] ?? '');
                                        $user_status = (int)($row['user_status'] ?? 0);
                                        $isActive = ($user_status === 1);

                                        // Initials for avatar
                                        $initials = '';
                                        $parts = explode(' ', $name);
                                        foreach ($parts as $part) {
                                            if (!empty($part)) $initials .= strtoupper($part[0]);
                                            if (strlen($initials) >= 2) break;
                                        }
                                        if (empty($initials)) $initials = strtoupper(substr($uname, 0, 2));

                                        // Search index text
                                        $searchTokens = strtolower($name . ' ' . $uname . ' ' . $email . ' ' . $contact . ' ' . $designation . ' ' . $level);
                                        ?>
                                        <tr class="user-row" 
                                            id="user-row-<?php echo $id; ?>"
                                            data-status="<?php echo $isActive ? 'active' : 'inactive'; ?>"
                                            data-search="<?php echo htmlspecialchars($searchTokens); ?>">
                                            
                                            <!-- Index -->
                                            <td style="text-align: center; color: var(--pm-slate-400); font-weight: 500; font-size: 12px;">
                                                <?php echo $srno; ?>
                                            </td>

                                            <!-- Staff Member -->
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar-pill" style="margin-right: 14px !important;">
                                                        <?php echo htmlspecialchars($initials); ?>
                                                    </div>
                                                    <div>
                                                        <div class="user-name-text">
                                                            <?php echo htmlspecialchars($name); ?>
                                                        </div>
                                                        <div class="user-handle-text">
                                                            @<?php echo htmlspecialchars($uname); ?> 
                                                            <span style="color: var(--pm-slate-400); font-size: 11px;">(ID: <?php echo $id; ?>)</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Role & Level -->
                                            <td>
                                                <div class="d-flex align-items-center flex-wrap">
                                                    <span class="pm-role-pill" style="margin-right: 6px !important; margin-bottom: 3px;">
                                                        <?php echo htmlspecialchars($designation ?: 'Staff'); ?>
                                                    </span>
                                                    <?php if (!empty($level) && $level !== 'NA' && $level !== $designation): ?>
                                                        <span class="pm-role-pill" style="background: var(--pm-slate-50); color: var(--pm-slate-500); margin-bottom: 3px;">
                                                            <?php echo htmlspecialchars($level); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <!-- Contact Details -->
                                            <td>
                                                <div style="font-size: 12.5px; line-height: 1.5;">
                                                    <?php if (!empty($email)): ?>
                                                        <div style="margin-bottom: 3px;">
                                                            <i class="fa fa-envelope-o text-muted" style="font-size: 11px; width: 14px; margin-right: 8px !important;"></i>
                                                            <span><?php echo htmlspecialchars($email); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($contact)): ?>
                                                        <div style="color: var(--pm-slate-500);">
                                                            <i class="fa fa-phone text-muted" style="font-size: 11px; width: 14px; margin-right: 8px !important;"></i>
                                                            <span><?php echo htmlspecialchars($contact); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (empty($email) && empty($contact)): ?>
                                                        <span class="text-muted" style="font-size: 11.5px;">No contact details</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <!-- Status -->
                                            <td style="text-align: center;">
                                                <span class="pm-badge <?php echo $isActive ? 'pm-badge-active' : 'pm-badge-inactive'; ?>" id="badge-status-<?php echo $id; ?>">
                                                    <span class="status-dot <?php echo $isActive ? 'active' : 'inactive'; ?>" style="margin-right: 6px !important;"></span>
                                                    <span><?php echo $isActive ? 'Active' : 'Inactive'; ?></span>
                                                </span>
                                            </td>

                                            <!-- Actions -->
                                            <td style="text-align: right;">
                                                <div class="d-flex align-items-center justify-content-end">
                                                    <!-- Permission -->
                                                    <a href="./userpermission.php?id=<?php echo $id; ?>" 
                                                       class="pm-btn-outline-sm" 
                                                       style="margin-right: 8px !important;"
                                                       title="Edit user module permissions">
                                                        <i class="fa fa-sliders" style="margin-right: 6px !important;"></i> Permissions
                                                    </a>

                                                    <!-- Toggle Status -->
                                                    <button type="button" 
                                                            class="pm-btn-status-toggle" 
                                                            id="toggle-btn-<?php echo $id; ?>"
                                                            data-user-id="<?php echo $id; ?>"
                                                            data-user-name="<?php echo htmlspecialchars($name); ?>"
                                                            data-current-status="<?php echo $user_status; ?>"
                                                            onclick="toggleUserStatus(<?php echo $id; ?>, '<?php echo htmlspecialchars(addslashes($name)); ?>', <?php echo $user_status; ?>)">
                                                        <i class="fa <?php echo $isActive ? 'fa-ban text-muted' : 'fa-check text-muted'; ?>" style="margin-right: 6px !important;"></i>
                                                        <span class="btn-text"><?php echo $isActive ? 'Deactivate' : 'Activate'; ?></span>
                                                    </button>
                                                </div>
                                            </td>

                                        </tr>
                                        <?php
                                        $srno++;
                                    }
                                } else {
                                    ?>
                                    <tr id="emptyRow">
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fa fa-users fa-2x text-muted mb-2" style="opacity: 0.4;"></i>
                                            <div style="font-size: 14px; font-weight: 600; color: var(--pm-slate-800);">No user accounts found</div>
                                            <div style="font-size: 12px; color: var(--pm-slate-500); margin-top: 4px;">Click "Add New User" to configure the first staff account.</div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                <tr id="noFilterMatchRow" style="display: none;">
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fa fa-search fa-2x text-muted mb-2" style="opacity: 0.4;"></i>
                                        <div style="font-size: 14px; font-weight: 600; color: var(--pm-slate-800);">No matching users found</div>
                                        <div style="font-size: 12px; color: var(--pm-slate-500); margin-top: 4px;">Try refining your search keyword or changing status filter.</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Client-side Search & Interactive Logic -->
            <script>
                $(document).ready(function() {
                    $('#userSearchInput').on('input', function() {
                        filterUserTable();
                    });

                    $('#statusFilter').on('change', function() {
                        filterUserTable();
                    });
                });

                function filterUserTable() {
                    var query = $('#userSearchInput').val().trim().toLowerCase();
                    var statusFilter = $('#statusFilter').val();
                    var visibleCount = 0;

                    $('.user-row').each(function() {
                        var $row = $(this);
                        var searchData = $row.data('search') || '';
                        var rowStatus = $row.data('status');

                        var matchesQuery = (query.length === 0 || searchData.indexOf(query) !== -1);
                        var matchesStatus = (statusFilter === 'all' || rowStatus === statusFilter);

                        if (matchesQuery && matchesStatus) {
                            $row.show();
                            visibleCount++;
                        } else {
                            $row.hide();
                        }
                    });

                    $('#visibleUserCount').text(visibleCount);

                    if (visibleCount === 0 && $('.user-row').length > 0) {
                        $('#noFilterMatchRow').show();
                    } else {
                        $('#noFilterMatchRow').hide();
                    }
                }

                function toggleUserStatus(userId, userName, currentStatus) {
                    var isCurrentlyActive = (parseInt(currentStatus) === 1);
                    var actionVerb = isCurrentlyActive ? 'Deactivate' : 'Activate';
                    var actionTitle = actionVerb + ' User Account?';
                    var actionText = isCurrentlyActive 
                        ? 'User "' + userName + '" will be prevented from signing into the POS system.' 
                        : 'User "' + userName + '" will regain active login access to the POS system.';

                    Swal.fire({
                        title: actionTitle,
                        text: actionText,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#0f172a',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Yes, ' + actionVerb + ' User'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            $.ajax({
                                type: "POST",
                                url: 'disable_user.php',
                                data: { id: userId },
                                success: function(response) {
                                    if (response == 1) {
                                        var newStatus = isCurrentlyActive ? 0 : 1;
                                        var newIsActive = (newStatus === 1);

                                        // Update Row Data
                                        var $row = $('#user-row-' + userId);
                                        $row.data('status', newIsActive ? 'active' : 'inactive');

                                        // Update Badge
                                        var $badge = $('#badge-status-' + userId);
                                        $badge.removeClass('pm-badge-active pm-badge-inactive')
                                              .addClass(newIsActive ? 'pm-badge-active' : 'pm-badge-inactive');
                                        $badge.html(
                                            '<span class="status-dot ' + (newIsActive ? 'active' : 'inactive') + '"></span>' +
                                            '<span>' + (newIsActive ? 'Active' : 'Inactive') + '</span>'
                                        );

                                        // Update Button
                                        var $btn = $('#toggle-btn-' + userId);
                                        $btn.attr('data-current-status', newStatus);
                                        $btn.find('i').removeClass('fa-ban fa-check')
                                                      .addClass(newIsActive ? 'fa-ban text-muted' : 'fa-check text-muted');
                                        $btn.find('.btn-text').text(newIsActive ? 'Deactivate' : 'Activate');
                                        $btn.attr('onclick', "toggleUserStatus(" + userId + ", '" + escapeJs(userName) + "', " + newStatus + ")");

                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end',
                                            icon: 'success',
                                            title: 'User status updated to ' + (newIsActive ? 'Active' : 'Inactive'),
                                            showConfirmButton: false,
                                            timer: 1500
                                        });

                                        filterUserTable();
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Status Update Failed',
                                            text: 'Unable to update user status in the database.',
                                            confirmButtonColor: '#0f172a'
                                        });
                                    }
                                },
                                error: function() {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Server Error',
                                        text: 'Network error occurred. Please check connection.',
                                        confirmButtonColor: '#0f172a'
                                    });
                                }
                            });
                        }
                    });
                }

                function escapeJs(str) {
                    return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
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