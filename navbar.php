<?php 
if (isset($_SESSION['userid']) && $_SESSION['userid']) {

    $base_url = '/pos/';
    $id = $_SESSION['userid'];

    $user = "select * from loginusers where id=" . $id;
    $usersql = mysqli_query($con, $user);
    $usersql_result = mysqli_fetch_assoc($usersql);
    $userName = $usersql_result['name'] ?? ($_SESSION['username'] ?? 'Admin');
    $userInitial = strtoupper(substr($userName, 0, 1));

    $permission = $usersql_result['permission'] ?? '';
    $permission = explode(',', $permission);
    sort($permission);

    $cpermission = json_encode($permission);
    $cpermission = str_replace(array('[', ']', '"'), '', $cpermission);
    $cpermission = explode(',', $cpermission);
    $cpermission = "'" . implode("', '", $cpermission) . "'";
    $mainmenu = [];
    foreach ($permission as $key => $val) {
        $sub_menu_sql = mysqli_query($con, "select * from sub_menu where id='" . $val . "' and status=1");

        if ($sub_menu_sql && mysqli_num_rows($sub_menu_sql) > 0) {
            $sub_menu_sql_result = mysqli_fetch_assoc($sub_menu_sql);
            $mainmenu[] = $sub_menu_sql_result['main_menu'];
        }
    }
    $mainmenu = array_unique($mainmenu);
    sort($mainmenu);

    $currentPage = basename($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
?>

<aside class="shadcn-sidebar-wrapper" id="sidebar">
    <!-- Navigation Menu -->
    <div class="shadcn-sidebar-content">
        <div class="shadcn-group-label">Platform Menu</div>

        <nav class="shadcn-nav-list">
            <?php 
            foreach ($mainmenu as $menu => $menu_id) {
                $menu_sql = mysqli_query($con, "select * from main_menu where id='" . $menu_id . "' and status=1");
                if (!$menu_sql || mysqli_num_rows($menu_sql) === 0) continue;
                
                $menu_sql_result = mysqli_fetch_assoc($menu_sql);
                $main_name = $menu_sql_result['name'];
                $targetId = 'menu-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $main_name);

                // Modern SVG / FontAwesome Icons
                $iconSvg = '<i class="fas fa-circle"></i>';
                switch (trim($main_name)) {
                    case 'Dashboard':
                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>';
                        break;
                    case 'Users':
                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';
                        break;
                    case 'Reports':
                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>';
                        break;
                    case 'Purchase':
                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>';
                        break;
                    case 'Bank':
                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="20" y2="7"></line><line x1="12" y1="2" x2="4" y2="7"></line><line x1="2" y1="22" x2="22" y2="22"></line><line x1="4" y1="7" x2="20" y2="7"></line><line x1="6" y1="7" x2="6" y2="22"></line><line x1="18" y1="7" x2="18" y2="22"></line></svg>';
                        break;
                    case 'Masteradmin':
                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>';
                        break;
                    case 'GST Reports':
                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
                        break;
                    case 'Item Code':
                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="5" x2="3" y2="19"></line><line x1="8" y1="5" x2="8" y2="19"></line><line x1="12" y1="5" x2="12" y2="19"></line><line x1="17" y1="5" x2="17" y2="19"></line><line x1="21" y1="5" x2="21" y2="19"></line></svg>';
                        break;
                    case 'Leads':
                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>';
                        break;
                    case 'Bulk Process':
                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>';
                        break;
                    default:
                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';
                        break;
                }

                // Submenu items
                $submenus = [];
                $hasActiveChild = false;
                $submenu_sql = mysqli_query($con, "select * from sub_menu where main_menu = '" . $menu_id . "' and id in ($cpermission) and status=1 order by sub_menu asc");
                if ($submenu_sql) {
                    while ($subRow = mysqli_fetch_assoc($submenu_sql)) {
                        if ($currentPage === $subRow['page']) {
                            $hasActiveChild = true;
                        }
                        $submenus[] = $subRow;
                    }
                }

                $hasChildren = !empty($submenus);
            ?>
                <div class="shadcn-menu-item <?php echo $hasActiveChild ? 'is-active-group' : ''; ?>">
                    <a href="javascript:void(0);" 
                       class="shadcn-menu-btn <?php echo $hasActiveChild ? 'is-open' : ''; ?>" 
                       data-shadcn-target="#<?php echo $targetId; ?>">
                        <span class="shadcn-icon-box"><?php echo $iconSvg; ?></span>
                        <span class="shadcn-menu-label"><?php echo htmlspecialchars($main_name); ?></span>
                        <?php if ($hasChildren): ?>
                            <svg class="shadcn-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        <?php endif; ?>
                    </a>

                    <?php if ($hasChildren): ?>
                        <div class="shadcn-submenu-box" id="<?php echo $targetId; ?>" style="<?php echo $hasActiveChild ? 'display:block;' : 'display:none;'; ?>">
                            <div class="shadcn-submenu-tree">
                                <?php 
                                foreach ($submenus as $subItem) {
                                    $page = $subItem['page'];
                                    $submenu_name = $subItem['sub_menu'];
                                    $folder = $subItem['folder'];
                                    $title = $subItem['title'];

                                    $isActive = ($currentPage === $page);
                                    if ($isActive && !empty($title)) {
                                        echo '<title>' . htmlspecialchars($title) . '</title>';
                                    }
                                ?>
                                    <a class="shadcn-sub-link <?php echo $isActive ? 'is-active' : ''; ?>" 
                                       href="<?php echo $base_url . $folder . '/' . $page; ?>">
                                        <span class="shadcn-sub-bullet"></span>
                                        <span><?php echo htmlspecialchars($submenu_name); ?></span>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php } ?>
        </nav>
    </div>

    <!-- Shadcn Sidebar Footer with User Profile -->
    <div class="shadcn-sidebar-footer">
        <div class="shadcn-user-badge">
            <div class="shadcn-user-avatar"><?php echo htmlspecialchars($userInitial); ?></div>
            <div class="shadcn-user-meta">
                <span class="shadcn-user-name"><?php echo htmlspecialchars($userName); ?></span>
                <span class="shadcn-user-role">Store Manager</span>
            </div>
            <a href="/pos/logout.php" class="shadcn-logout-btn" title="Logout">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </div>
</aside>

<script>
    // Pure, standalone Shadcn Accordion Toggle (Guaranteed to open & close smoothly on every page)
    (function() {
        function initShadcnSidebar() {
            $(document).off('click.shadcnMenu', '.shadcn-menu-btn').on('click.shadcnMenu', '.shadcn-menu-btn', function(e) {
                e.preventDefault();
                var targetSelector = $(this).data('shadcn-target');
                if (!targetSelector) return;

                var $target = $(targetSelector);
                var $btn = $(this);
                var isOpen = $btn.hasClass('is-open');

                if (isOpen) {
                    $btn.removeClass('is-open');
                    $target.stop(true, true).slideUp(160);
                } else {
                    $btn.addClass('is-open');
                    $target.stop(true, true).slideDown(160);
                }
            });

            // Mobile sidebar toggle with hamburger
            $(document).off('click.shadcnToggle', '[data-toggle="minimize"], [data-toggle="offcanvas"]').on('click.shadcnToggle', '[data-toggle="minimize"], [data-toggle="offcanvas"]', function(e) {
                e.preventDefault();
                $('#sidebar').toggleClass('is-collapsed');
            });
        }

        if (window.jQuery) {
            $(document).ready(initShadcnSidebar);
        } else {
            document.addEventListener('DOMContentLoaded', initShadcnSidebar);
        }
    })();
</script>

<?php } ?>

<style>
/* =========================================================
   SHADCN UI SIDEBAR SPECIFICATION
   ========================================================= */
:root {
    --shadcn-bg: #ffffff;
    --shadcn-sidebar-w: 240px;
    --shadcn-border: #e2e8f0;
    --shadcn-text: #334155;
    --shadcn-text-dark: #0f172a;
    --shadcn-text-muted: #64748b;
    --shadcn-hover-bg: #f1f5f9;
    --shadcn-active-bg: #f1f5f9;
    --shadcn-accent: #4f46e5;
}

#sidebar.shadcn-sidebar-wrapper {
    width: var(--shadcn-sidebar-w) !important;
    min-width: var(--shadcn-sidebar-w) !important;
    max-width: var(--shadcn-sidebar-w) !important;
    background: var(--shadcn-bg) !important;
    border-right: 1px solid var(--shadcn-border) !important;
    border-top: none !important;
    border-bottom: none !important;
    border-left: none !important;
    box-shadow: none !important;
    display: flex !important;
    flex-direction: column !important;
    height: calc(100vh - 58px) !important;
    position: fixed !important;
    top: 58px !important;
    left: 0 !important;
    bottom: 0 !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    z-index: 1020 !important;
    padding: 0 !important;
    transition: width 0.2s cubic-bezier(0.4, 0, 0.2, 1), left 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    overflow: hidden !important;
}

/* Offset Main Content Area to prevent overlap with fixed sidebar */
.main-panel {
    margin-left: var(--shadcn-sidebar-w) !important;
    width: calc(100% - var(--shadcn-sidebar-w)) !important;
    min-height: calc(100vh - 58px) !important;
    transition: margin-left 0.2s cubic-bezier(0.4, 0, 0.2, 1), width 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

/* 1. Workspace Header */
.shadcn-workspace-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px 12px;
    border-bottom: 1px solid var(--shadcn-border);
    background: #ffffff;
}

.shadcn-workspace-avatar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #0f172a;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: -0.02em;
    flex-shrink: 0;
}

.shadcn-workspace-info {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.shadcn-workspace-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--shadcn-text-dark);
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.shadcn-workspace-subtitle {
    font-size: 11px;
    color: var(--shadcn-text-muted);
    line-height: 1.2;
}

/* 2. Content & Group Label */
.shadcn-sidebar-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 10px 8px;
}

.shadcn-sidebar-content::-webkit-scrollbar {
    width: 4px;
}

.shadcn-sidebar-content::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.shadcn-group-label {
    font-size: 10.5px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 6px 10px 6px;
}

/* 3. Navigation Buttons */
.shadcn-nav-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.shadcn-menu-item {
    display: flex;
    flex-direction: column;
}

.shadcn-menu-btn {
    display: flex !important;
    align-items: center !important;
    width: 100% !important;
    min-height: 34px !important;
    height: auto !important;
    padding: 6px 10px !important;
    border-radius: 6px !important;
    color: var(--shadcn-text) !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    text-decoration: none !important;
    background: transparent !important;
    border: none !important;
    transition: all 0.12s ease-in-out !important;
    cursor: pointer !important;
    user-select: none !important;
}

.shadcn-menu-btn:hover {
    background-color: var(--shadcn-hover-bg) !important;
    color: var(--shadcn-text-dark) !important;
}

.shadcn-menu-btn.is-open {
    color: var(--shadcn-text-dark) !important;
    font-weight: 600 !important;
}

.shadcn-menu-btn.is-open:hover {
    background-color: var(--shadcn-hover-bg) !important;
}

.shadcn-icon-box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    margin-right: 9px;
    color: var(--shadcn-text-muted);
    flex-shrink: 0;
    transition: color 0.15s ease;
    align-self: flex-start;
    margin-top: 1px;
}

.shadcn-menu-btn:hover .shadcn-icon-box,
.shadcn-menu-btn.is-open .shadcn-icon-box,
.is-active-group > .shadcn-menu-btn .shadcn-icon-box {
    color: var(--shadcn-text-dark);
}

.shadcn-menu-label {
    flex: 1;
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    line-height: 1.35 !important;
}

.shadcn-chevron {
    color: #94a3b8;
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    margin-left: auto;
    flex-shrink: 0;
    align-self: flex-start;
    margin-top: 3px;
}

.shadcn-menu-btn.is-open .shadcn-chevron {
    transform: rotate(90deg);
    color: var(--shadcn-text-dark);
}

/* 4. Submenu (Shadcn Left Guide Tree) */
.shadcn-submenu-box {
    padding-left: 10px;
    margin-left: 18px;
    border-left: 1px solid var(--shadcn-border);
    margin-top: 2px;
    margin-bottom: 2px;
}

.shadcn-submenu-tree {
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.shadcn-sub-link {
    display: flex !important;
    align-items: flex-start !important;
    min-height: 28px !important;
    height: auto !important;
    padding: 5px 8px !important;
    border-radius: 5px !important;
    font-size: 12px !important;
    font-weight: 450 !important;
    color: var(--shadcn-text-muted) !important;
    text-decoration: none !important;
    transition: all 0.12s ease-in-out !important;
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    line-height: 1.35 !important;
}

.shadcn-sub-link span:last-child {
    flex: 1;
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    line-height: 1.35 !important;
}

.shadcn-sub-link:hover {
    color: var(--shadcn-text-dark) !important;
    background-color: var(--shadcn-hover-bg) !important;
}

.shadcn-sub-link.is-active {
    color: var(--shadcn-text-dark) !important;
    font-weight: 600 !important;
    background-color: var(--shadcn-active-bg) !important;
}

.shadcn-sub-bullet {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #cbd5e1;
    margin-right: 8px;
    margin-top: 6px;
    flex-shrink: 0;
}

.shadcn-sub-link.is-active .shadcn-sub-bullet {
    background: var(--shadcn-text-dark);
}

/* 5. Footer Profile */
.shadcn-sidebar-footer {
    border-top: 1px solid var(--shadcn-border);
    padding: 10px 12px;
    background: #ffffff;
}

.shadcn-user-badge {
    display: flex;
    align-items: center;
    gap: 10px;
}

.shadcn-user-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #334155;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.shadcn-user-meta {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    flex: 1;
}

.shadcn-user-name {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--shadcn-text-dark);
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.shadcn-user-role {
    font-size: 10.5px;
    color: var(--shadcn-text-muted);
    line-height: 1.2;
}

.shadcn-logout-btn {
    color: #94a3b8;
    padding: 5px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    text-decoration: none !important;
}

.shadcn-logout-btn:hover {
    color: #ef4444;
    background: #fef2f2;
}

/* Collapsed state when toggled */
#sidebar.shadcn-sidebar-wrapper.is-collapsed {
    width: 58px !important;
    min-width: 58px !important;
    max-width: 58px !important;
}

#sidebar.shadcn-sidebar-wrapper.is-collapsed ~ .main-panel {
    margin-left: 58px !important;
    width: calc(100% - 58px) !important;
}

#sidebar.shadcn-sidebar-wrapper.is-collapsed .shadcn-workspace-info,
#sidebar.shadcn-sidebar-wrapper.is-collapsed .shadcn-group-label,
#sidebar.shadcn-sidebar-wrapper.is-collapsed .shadcn-menu-label,
#sidebar.shadcn-sidebar-wrapper.is-collapsed .shadcn-chevron,
#sidebar.shadcn-sidebar-wrapper.is-collapsed .shadcn-submenu-box,
#sidebar.shadcn-sidebar-wrapper.is-collapsed .shadcn-user-meta {
    display: none !important;
}

@media (max-width: 991px) {
    #sidebar.shadcn-sidebar-wrapper {
        left: -240px !important;
        width: 240px !important;
    }
    #sidebar.shadcn-sidebar-wrapper.is-collapsed {
        left: 0 !important;
        width: 240px !important;
        min-width: 240px !important;
        max-width: 240px !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
    }
    #sidebar.shadcn-sidebar-wrapper.is-collapsed .shadcn-workspace-info,
    #sidebar.shadcn-sidebar-wrapper.is-collapsed .shadcn-group-label,
    #sidebar.shadcn-sidebar-wrapper.is-collapsed .shadcn-menu-label,
    #sidebar.shadcn-sidebar-wrapper.is-collapsed .shadcn-chevron,
    #sidebar.shadcn-sidebar-wrapper.is-collapsed .shadcn-submenu-box,
    #sidebar.shadcn-sidebar-wrapper.is-collapsed .shadcn-user-meta {
        display: block !important;
    }
    .main-panel,
    #sidebar.shadcn-sidebar-wrapper.is-collapsed ~ .main-panel {
        margin-left: 0 !important;
        width: 100% !important;
    }
}
</style>