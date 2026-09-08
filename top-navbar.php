<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$navUserName = $_SESSION['username'] ?? 'Admin';
$navUserInitial = strtoupper(substr($navUserName, 0, 1));
?>
<style>
/* =========================================================
   SHADCN UI TOP NAVBAR SPECIFICATION
   ========================================================= */
:root {
    --header-h: 58px;
    --sidebar-w: 240px;
    --header-border: #e2e8f0;
    --header-bg: #ffffff;
    --header-text: #0f172a;
    --header-muted: #64748b;
    --header-hover: #f1f5f9;
}

#load {
    width: 100%;
    height: 100%;
    position: fixed;
    z-index: 9999;
    background: url("/pos/images/logo.svg") no-repeat center center rgba(0, 0, 0, 0.25);
}

/* Master Navbar Container Overrides */
.navbar.default-layout-navbar {
    height: var(--header-h) !important;
    min-height: var(--header-h) !important;
    background: var(--header-bg) !important;
    border-bottom: 1px solid var(--header-border) !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03) !important;
    padding: 0 !important;
    margin: 0 !important;
    z-index: 1030 !important;
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
}

/* Page body wrapper adjustment: ensure content across all pages sits cleanly below fixed header */
.page-body-wrapper {
    padding-top: var(--header-h) !important;
}

/* 1. Brand Logo Container */
.navbar.default-layout-navbar .navbar-brand-wrapper {
    width: var(--sidebar-w) !important;
    min-width: var(--sidebar-w) !important;
    max-width: var(--sidebar-w) !important;
    height: var(--header-h) !important;
    background: var(--header-bg) !important;
    border-right: 1px solid var(--header-border) !important;
    border-bottom: none !important;
    padding: 0 16px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    transition: width 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.shadcn-brand-link {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none !important;
}

.shadcn-brand-logo {
    height: 30px;
    max-width: 175px;
    object-fit: contain;
    transition: opacity 0.15s ease;
}

.navbar.default-layout-navbar .navbar-brand-wrapper.is-collapsed {
    width: 58px !important;
    min-width: 58px !important;
    max-width: 58px !important;
    padding: 0 !important;
    justify-content: center !important;
}

.navbar.default-layout-navbar .navbar-brand-wrapper.is-collapsed .shadcn-brand-link {
    justify-content: center !important;
    width: 100% !important;
}

.navbar.default-layout-navbar .navbar-brand-wrapper.is-collapsed .shadcn-brand-logo {
    display: none !important;
}

.shadcn-brand-mini-logo {
    display: none;
    height: 30px;
    width: 30px;
    object-fit: contain;
    margin: 0 auto;
}

.navbar.default-layout-navbar .navbar-brand-wrapper.is-collapsed .shadcn-brand-mini-logo {
    display: block !important;
}

/* 2. Menu Wrapper & Clear Old Quirks */
.navbar.default-layout-navbar .navbar-menu-wrapper {
    width: calc(100% - var(--sidebar-w)) !important;
    height: var(--header-h) !important;
    background: var(--header-bg) !important;
    padding: 0 20px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    box-shadow: none !important;
    border: none !important;
    transition: width 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.navbar-brand-wrapper.is-collapsed + .navbar-menu-wrapper {
    width: calc(100% - 58px) !important;
}

/* Remove the old dark vertical line divider */
.navbar .navbar-menu-wrapper .navbar-nav.navbar-nav-left {
    width: auto !important;
    border-right: none !important;
    margin: 0 !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
}

.navbar .navbar-menu-wrapper .navbar-nav.navbar-nav-right {
    margin-left: auto !important;
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
}

/* 3. Ghost Button Toggler */
.shadcn-icon-btn {
    width: 34px;
    height: 34px;
    border-radius: 6px;
    background: transparent;
    border: 1px solid var(--header-border);
    color: var(--header-muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
    outline: none !important;
}

.shadcn-icon-btn:hover {
    background: var(--header-hover);
    color: var(--header-text);
    border-color: #cbd5e1;
}

/* 4. Sleek Search Input */
.shadcn-search-box {
    position: relative;
    display: flex;
    align-items: center;
    margin-left: 12px;
}

.shadcn-search-box svg {
    position: absolute;
    left: 11px;
    color: #94a3b8;
    pointer-events: none;
}

.shadcn-search-input {
    height: 34px;
    width: 260px;
    background: #f8fafc;
    border: 1px solid var(--header-border);
    border-radius: 6px;
    padding: 0 34px 0 32px;
    font-size: 12.5px;
    color: var(--header-text);
    transition: all 0.15s ease;
    outline: none;
}

.shadcn-search-input::placeholder {
    color: #94a3b8;
    font-size: 12px;
}

.shadcn-search-input:focus {
    width: 310px;
    background: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.08);
}

.shadcn-search-kbd {
    position: absolute;
    right: 8px;
    font-size: 10px;
    font-family: inherit;
    font-weight: 600;
    color: #94a3b8;
    background: #e2e8f0;
    border-radius: 4px;
    padding: 1px 5px;
    pointer-events: none;
    line-height: 1.4;
}

/* 5. Header Status Badge & Quick Action Buttons */
.shadcn-live-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 500;
    color: #334155;
    background: #f8fafc;
    border: 1px solid var(--header-border);
    border-radius: 9999px;
    padding: 3px 10px;
    white-space: nowrap;
}

.shadcn-live-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
}

.shadcn-header-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 32px;
    padding: 0 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none !important;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.shadcn-header-action-btn.btn-outline {
    color: var(--header-text);
    background: #ffffff;
    border: 1px solid var(--header-border);
}

.shadcn-header-action-btn.btn-outline:hover {
    background: var(--header-hover);
    color: var(--header-text);
    border-color: #cbd5e1;
}

.shadcn-header-action-btn.btn-solid {
    color: #ffffff;
    background: #0f172a;
    border: 1px solid #0f172a;
}

.shadcn-header-action-btn.btn-solid:hover {
    background: #1e293b;
    border-color: #1e293b;
    color: #ffffff;
}

/* 6. Profile Dropdown */
.shadcn-profile-wrapper {
    position: relative;
}

.shadcn-profile-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 6px;
    padding: 3px 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    outline: none !important;
}

.shadcn-profile-btn:hover,
.shadcn-profile-btn.active {
    background: var(--header-hover);
    border-color: var(--header-border);
}

.shadcn-user-avatar-head {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #0f172a;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    flex-shrink: 0;
}

.shadcn-user-head-text {
    display: flex;
    flex-direction: column;
    text-align: left;
    line-height: 1.2;
}

.shadcn-user-head-name {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--header-text);
}

.shadcn-user-head-sub {
    font-size: 10.5px;
    color: var(--header-muted);
}

.shadcn-dropdown-panel {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 210px;
    background: #ffffff;
    border: 1px solid var(--header-border);
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    padding: 6px;
    display: none;
    z-index: 1050;
    animation: shadcnDropdownIn 0.15s cubic-bezier(0.16, 1, 0.3, 1);
}

.shadcn-dropdown-panel.show {
    display: block;
}

@keyframes shadcnDropdownIn {
    from {
        opacity: 0;
        transform: translateY(-4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.shadcn-dropdown-meta {
    padding: 8px 10px 10px;
}

.shadcn-dropdown-meta-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--header-text);
    line-height: 1.2;
}

.shadcn-dropdown-meta-role {
    font-size: 11px;
    color: var(--header-muted);
    margin-top: 2px;
}

.shadcn-dropdown-hr {
    height: 1px;
    background: var(--header-border);
    margin: 4px -6px;
}

.shadcn-dropdown-link {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 7px 10px;
    font-size: 12px;
    font-weight: 500;
    color: var(--header-text);
    text-decoration: none !important;
    border-radius: 5px;
    transition: all 0.12s ease;
}

.shadcn-dropdown-link:hover {
    background: var(--header-hover);
    color: var(--header-text);
}

.shadcn-dropdown-link.logout-link {
    color: #ef4444;
}

.shadcn-dropdown-link.logout-link:hover {
    background: #fef2f2;
    color: #dc2626;
}

/* Mobile adjustments */
@media (max-width: 991px) {
    .navbar.default-layout-navbar .navbar-brand-wrapper {
        width: 58px !important;
        min-width: 58px !important;
        padding: 0 8px !important;
        justify-content: center !important;
    }
    .navbar.default-layout-navbar .navbar-brand-wrapper .shadcn-brand-logo {
        display: none !important;
    }
    .navbar.default-layout-navbar .navbar-brand-wrapper .shadcn-brand-mini-logo {
        display: block !important;
    }
    .navbar.default-layout-navbar .navbar-menu-wrapper {
        width: calc(100% - 58px) !important;
        padding: 0 12px !important;
    }
}
</style>

<body class="sidebar-light">
    <div class="container-scroller">
        <!-- Top Navbar -->
        <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row default-layout-navbar">
            <!-- Brand Logo -->
            <div class="navbar-brand-wrapper d-flex align-items-center" id="navbarBrandWrapper">
                <a class="shadcn-brand-link" href="/pos/home_dashboard.php">
                    <img class="shadcn-brand-logo" alt="Sri Shringarr" src="/pos/images/logo.png" />
                    <img class="shadcn-brand-mini-logo" alt="Sri Shringarr" src="/pos/images/favicon.png" />
                </a>
            </div>

            <!-- Header Menu Wrapper -->
            <div class="navbar-menu-wrapper d-flex align-items-center">
                <!-- Left: Sidebar Toggle + Search -->
                <div class="d-flex align-items-center">
                    <button class="shadcn-icon-btn" data-toggle="minimize" type="button" title="Toggle Sidebar" id="headerSidebarToggle">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>

                    <div class="shadcn-search-box d-none d-md-flex">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" id="headerGlobalSearch" class="shadcn-search-input" placeholder="Search orders, bills, products..." autocomplete="off">
                        <span class="shadcn-search-kbd">⌘K</span>
                    </div>
                </div>

                <!-- Right: Status + Quick Actions + User Profile -->
                <div class="navbar-nav navbar-nav-right">


                    <!-- New Rental Quick Action -->
                    <a href="/pos/reports/rent.php" class="shadcn-header-action-btn btn-solid d-none d-md-inline-flex" title="Create New Rental Booking">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>New Rental</span>
                    </a>

                    <!-- Profile Dropdown -->
                    <div class="shadcn-profile-wrapper">
                        <button class="shadcn-profile-btn" id="headerProfileTrigger" type="button" aria-expanded="false">
                            <div class="shadcn-user-avatar-head">
                                <?php echo $navUserInitial; ?>
                            </div>
                            <div class="shadcn-user-head-text d-none d-md-flex">
                                <span class="shadcn-user-head-name"><?php echo htmlspecialchars($navUserName); ?></span>
                                <span class="shadcn-user-head-sub">Store Manager</span>
                            </div>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>

                        <div class="shadcn-dropdown-panel" id="headerProfileMenu">
                            <div class="shadcn-dropdown-meta">
                                <div class="shadcn-dropdown-meta-name"><?php echo htmlspecialchars($navUserName); ?></div>
                                <div class="shadcn-dropdown-meta-role">Sri Shringarr POS Terminal</div>
                            </div>
                            <div class="shadcn-dropdown-hr"></div>
                            <a class="shadcn-dropdown-link" href="/pos/home_dashboard.php">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                            <a class="shadcn-dropdown-link" href="/pos/viewuser.php">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <span>User Management</span>
                            </a>
                            <a class="shadcn-dropdown-link" href="/pos/reports/pdfmaker.php">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="20" x2="18" y2="10"></line>
                                    <line x1="12" y1="20" x2="12" y2="4"></line>
                                    <line x1="6" y1="20" x2="6" y2="14"></line>
                                </svg>
                                <span>Catalog PDF Maker</span>
                            </a>
                            <a class="shadcn-dropdown-link" href="/pos/account.php">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                </svg>
                                <span>Account Settings</span>
                            </a>
                            <div class="shadcn-dropdown-hr"></div>
                            <a class="shadcn-dropdown-link logout-link" href="/pos/logout.php">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                <span>Sign Out</span>
                            </a>
                        </div>
                    </div>

                    <!-- Mobile Sidebar Toggle -->
                    <button class="shadcn-icon-btn d-lg-none" data-toggle="offcanvas" type="button" title="Toggle Navigation">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
        </nav>
        <!-- partial -->
        <div id="load" style="display:none;"></div>

<script>
(function() {
    function initShadcnHeader() {
        var $brand = $('#navbarBrandWrapper');
        var $trigger = $('#headerProfileTrigger');
        var $menu = $('#headerProfileMenu');
        var $search = $('#headerGlobalSearch');

        // Toggle dropdown
        $trigger.on('click', function(e) {
            e.stopPropagation();
            var isOpen = $menu.hasClass('show');
            $('.shadcn-dropdown-panel').removeClass('show');
            if (!isOpen) {
                $menu.addClass('show');
                $trigger.addClass('active');
            } else {
                $trigger.removeClass('active');
            }
        });

        // Close on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.shadcn-profile-wrapper').length) {
                $menu.removeClass('show');
                $trigger.removeClass('active');
            }
        });

        // Sync brand wrapper collapsed state on toggle button click
        $(document).on('click', '[data-toggle="minimize"], [data-toggle="offcanvas"]', function() {
            setTimeout(function() {
                var isCol = $('#sidebar').hasClass('is-collapsed');
                if (isCol) {
                    $brand.addClass('is-collapsed');
                } else {
                    $brand.removeClass('is-collapsed');
                }
            }, 10);
        });

        // Global shortcut Ctrl/Cmd + K
        $(document).on('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                if ($search.length) {
                    $search.focus();
                }
            }
        });

        // Quick search enter
        $search.on('keypress', function(e) {
            if (e.which === 13) {
                var val = $(this).val().trim();
                if (val.length > 0) {
                    // Check if current page has a DataTable search input
                    var $dtSearch = $('.dataTables_filter input');
                    if ($dtSearch.length) {
                        $dtSearch.val(val).trigger('keyup');
                    } else {
                        // Redirect to dashboard or rent search
                        window.location.href = '/pos/reports/rent_1_new.php';
                    }
                }
            }
        });
    }

    if (window.jQuery) {
        $(document).ready(initShadcnHeader);
    } else {
        document.addEventListener('DOMContentLoaded', initShadcnHeader);
    }
})();
</script>