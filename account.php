<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include('top-header.php');
include('top-navbar.php');
include('db_connection.php');
$con = OpenSrishringarrCon();

// ── Fetch current user data ──
$userId   = (int)($_SESSION['userid'] ?? 0);
$userData = null;
if ($userId > 0) {
    $uRes = mysqli_query($con, "SELECT * FROM loginusers WHERE id = $userId LIMIT 1");
    $userData = $uRes ? mysqli_fetch_assoc($uRes) : null;
}
if (!$userData) {
    header('Location: /pos/login.php');
    exit;
}

// ── Handle POST actions ──
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update Profile
    if ($action === 'update_profile') {
        $newName    = mysqli_real_escape_string($con, trim($_POST['full_name'] ?? ''));
        $newEmail   = mysqli_real_escape_string($con, trim($_POST['email'] ?? ''));
        $newContact = mysqli_real_escape_string($con, trim($_POST['contact'] ?? ''));

        if ($newName === '') {
            $msg = 'Full name is required.';
            $msgType = 'error';
        } else {
            $upd = mysqli_query($con, "UPDATE loginusers SET name='$newName', email='$newEmail', contact='$newContact' WHERE id=$userId");
            if ($upd) {
                $_SESSION['username'] = $newName; // sync session
                $msg = 'Profile updated successfully.';
                $msgType = 'success';
                // Re-fetch
                $uRes = mysqli_query($con, "SELECT * FROM loginusers WHERE id = $userId LIMIT 1");
                $userData = mysqli_fetch_assoc($uRes);
            } else {
                $msg = 'Failed to update profile. Please try again.';
                $msgType = 'error';
            }
        }
    }

    // Change Password
    if ($action === 'change_password') {
        $currentPwd = $_POST['current_password'] ?? '';
        $newPwd     = $_POST['new_password'] ?? '';
        $confirmPwd = $_POST['confirm_password'] ?? '';

        if ($currentPwd === '' || $newPwd === '' || $confirmPwd === '') {
            $msg = 'All password fields are required.';
            $msgType = 'error';
        } elseif ($currentPwd !== $userData['pwd']) {
            $msg = 'Current password is incorrect.';
            $msgType = 'error';
        } elseif (strlen($newPwd) < 4) {
            $msg = 'New password must be at least 4 characters.';
            $msgType = 'error';
        } elseif ($newPwd !== $confirmPwd) {
            $msg = 'New passwords do not match.';
            $msgType = 'error';
        } else {
            $safePwd = mysqli_real_escape_string($con, $newPwd);
            $upd = mysqli_query($con, "UPDATE loginusers SET pwd='$safePwd' WHERE id=$userId");
            if ($upd) {
                $msg = 'Password changed successfully.';
                $msgType = 'success';
                // Re-fetch
                $uRes = mysqli_query($con, "SELECT * FROM loginusers WHERE id = $userId LIMIT 1");
                $userData = mysqli_fetch_assoc($uRes);
            } else {
                $msg = 'Failed to change password. Please try again.';
                $msgType = 'error';
            }
        }
    }
}

$displayName  = htmlspecialchars($userData['name'] ?? '');
$displayUname = htmlspecialchars($userData['uname'] ?? '');
$displayEmail = htmlspecialchars($userData['email'] ?? '');
$displayContact = htmlspecialchars($userData['contact'] ?? '');
$displayDesig = htmlspecialchars($userData['designation'] ?? '');
$displayBranch = htmlspecialchars($userData['branch'] ?? '');
$displayLevel = htmlspecialchars($userData['level'] ?? '');
$userInitial  = strtoupper(substr($userData['name'] ?? 'U', 0, 1));
$memberSince  = $userData['updated_at'] ? date('M Y', strtotime($userData['updated_at'])) : 'N/A';


?>
<!-- partial -->
<div class="container-fluid page-body-wrapper">
    <?php include('navbar.php'); ?>

    <div class="main-panel">
        <div class="content-wrapper">

<style>
/* ─── Account Page — Scoped Shadcn UI ─── */
.acct-page * { box-sizing: border-box; }
.acct-page {
    font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
    color: #0f172a;
    max-width: 880px;
}

/* Header */
.acct-page-header {
    margin-bottom: 28px;
}
.acct-page-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px 0;
}
.acct-page-header p {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}

/* Toast */
.acct-toast {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 500;
    margin-bottom: 24px;
    border: 1px solid;
    animation: acctSlideIn 0.3s ease;
}
@keyframes acctSlideIn {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.acct-toast.success {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #166534;
}
.acct-toast.error {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}
.acct-toast svg { flex-shrink: 0; }

/* Tabs */
.acct-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0;
}
.acct-tab {
    padding: 10px 20px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.15s;
    user-select: none;
    background: none;
    border-top: none;
    border-left: none;
    border-right: none;
    margin-bottom: -1px;
}
.acct-tab:hover { color: #0f172a; }
.acct-tab.active {
    color: #0f172a;
    border-bottom-color: #0f172a;
    font-weight: 600;
}

/* Section Card */
.acct-section {
    display: none;
}
.acct-section.active { display: block; }

.acct-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 0;
    margin-bottom: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.acct-card-header {
    padding: 20px 24px 16px;
    border-bottom: 1px solid #f1f5f9;
}
.acct-card-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #0f172a;
    margin: 0 0 4px 0;
}
.acct-card-header p {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0;
}
.acct-card-body {
    padding: 20px 24px 24px;
}

/* Profile Banner */
.acct-profile-banner {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0;
}
.acct-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #0f172a;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    flex-shrink: 0;
}
.acct-profile-meta h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px 0;
}
.acct-profile-meta .role-badge {
    display: inline-block;
    font-size: 0.6875rem;
    font-weight: 600;
    color: #475569;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 3px 12px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.acct-profile-meta .meta-row {
    display: flex;
    gap: 16px;
    margin-top: 8px;
    flex-wrap: wrap;
}
.acct-profile-meta .meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.8125rem;
    color: #64748b;
}

/* Form */
.acct-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 20px;
}
.acct-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.acct-form-group.full-width {
    grid-column: 1 / -1;
}
.acct-form-group label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #334155;
}
.acct-form-group label .required {
    color: #ef4444;
    margin-left: 2px;
}
.acct-form-input {
    height: 38px;
    padding: 0 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    font-family: inherit;
}
.acct-form-input:focus {
    border-color: #94a3b8;
    box-shadow: 0 0 0 3px rgba(148,163,184,0.12);
}
.acct-form-input:disabled {
    background: #f8fafc;
    color: #94a3b8;
    cursor: not-allowed;
}
.acct-form-input::placeholder { color: #94a3b8; }
.acct-form-hint {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Password Strength */
.pwd-strength {
    display: flex;
    gap: 4px;
    margin-top: 4px;
}
.pwd-strength-bar {
    height: 3px;
    flex: 1;
    border-radius: 2px;
    background: #e2e8f0;
    transition: background 0.3s;
}
.pwd-strength-bar.weak   { background: #ef4444; }
.pwd-strength-bar.medium { background: #f59e0b; }
.pwd-strength-bar.strong { background: #10b981; }
.pwd-strength-text {
    font-size: 0.6875rem;
    color: #94a3b8;
    margin-top: 4px;
}

/* Buttons */
.acct-card-footer {
    padding: 16px 24px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.acct-btn {
    padding: 8px 20px;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    border: 1px solid;
    font-family: inherit;
    outline: none;
}
.acct-btn-primary {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}
.acct-btn-primary:hover {
    background: #1e293b;
    border-color: #1e293b;
}
.acct-btn-outline {
    background: #ffffff;
    color: #334155;
    border-color: #e2e8f0;
}
.acct-btn-outline:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

/* Info Grid */
.acct-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.acct-info-item label {
    font-size: 0.75rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
    margin-bottom: 4px;
    display: block;
}
.acct-info-item .info-val {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0f172a;
}

/* Sessions */
.acct-session-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid #f1f5f9;
}
.acct-session-item:last-child { border-bottom: none; }
.acct-session-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #475569;
    flex-shrink: 0;
}
.acct-session-info { flex: 1; }
.acct-session-info .sess-title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #0f172a;
}
.acct-session-info .sess-meta {
    font-size: 0.75rem;
    color: #64748b;
}
.acct-session-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}

/* Responsive */
@media (max-width: 640px) {
    .acct-form-grid,
    .acct-info-grid { grid-template-columns: 1fr; }
    .acct-profile-banner { flex-direction: column; text-align: center; }
    .acct-profile-meta .meta-row { justify-content: center; }
}
</style>

<div class="acct-page">

    <!-- Page Header -->
    <div class="acct-page-header">
        <h1>⚙️ Account Settings</h1>
        <p>Manage your profile information and security preferences</p>
    </div>

    <!-- Toast Message -->
    <?php if ($msg): ?>
    <div class="acct-toast <?= $msgType ?>">
        <?php if ($msgType === 'success'): ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <?php else: ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <?php endif; ?>
        <span><?= htmlspecialchars($msg) ?></span>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="acct-tabs">
        <button class="acct-tab active" data-tab="profile">Profile</button>
        <button class="acct-tab" data-tab="security">Security</button>
        <button class="acct-tab" data-tab="sessions">Sessions</button>
    </div>

    <!-- ═══ PROFILE TAB ═══ -->
    <div class="acct-section active" id="tab-profile">

        <!-- Profile Banner -->
        <div class="acct-card">
            <div class="acct-profile-banner">
                <div class="acct-avatar"><?= $userInitial ?></div>
                <div class="acct-profile-meta">
                    <h2><?= $displayName ?></h2>
                    <span class="role-badge"><?= $displayDesig ?: $displayLevel ?: 'Staff' ?></span>
                    <div class="meta-row">
                        <?php if ($displayEmail): ?>
                        <span class="meta-item">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <?= $displayEmail ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($displayBranch): ?>
                        <span class="meta-item">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?= $displayBranch ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                <div class="acct-card-body">
                    <div class="acct-form-grid">
                        <div class="acct-form-group">
                            <label>Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" class="acct-form-input" value="<?= $displayName ?>" required>
                        </div>
                        <div class="acct-form-group">
                            <label>Username</label>
                            <input type="text" class="acct-form-input" value="<?= $displayUname ?>" disabled>
                            <span class="acct-form-hint">Username cannot be changed</span>
                        </div>
                        <div class="acct-form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="acct-form-input" value="<?= $displayEmail ?>" placeholder="you@example.com">
                        </div>
                        <div class="acct-form-group">
                            <label>Phone Number</label>
                            <input type="text" name="contact" class="acct-form-input" value="<?= $displayContact ?>" placeholder="+91 XXXXX XXXXX">
                        </div>
                    </div>
                </div>
                <div class="acct-card-footer">
                    <button type="reset" class="acct-btn acct-btn-outline">Cancel</button>
                    <button type="submit" class="acct-btn acct-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>

        <!-- Account Info (Read-only) -->
        <div class="acct-card">
            <div class="acct-card-header">
                <h3>Account Information</h3>
                <p>Read-only details managed by your administrator</p>
            </div>
            <div class="acct-card-body">
                <div class="acct-info-grid">
                    <div class="acct-info-item">
                        <label>User ID</label>
                        <div class="info-val">#<?= $userId ?></div>
                    </div>
                    <div class="acct-info-item">
                        <label>Designation</label>
                        <div class="info-val"><?= $displayDesig ?: '—' ?></div>
                    </div>
                    <div class="acct-info-item">
                        <label>Branch</label>
                        <div class="info-val"><?= $displayBranch ?: '—' ?></div>
                    </div>
                    <div class="acct-info-item">
                        <label>Access Level</label>
                        <div class="info-val"><?= $displayLevel ?: '—' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ SECURITY TAB ═══ -->
    <div class="acct-section" id="tab-security">
        <div class="acct-card">
            <div class="acct-card-header">
                <h3>Change Password</h3>
                <p>Update your password to keep your account secure</p>
            </div>
            <form method="POST" action="" id="pwdForm">
                <input type="hidden" name="action" value="change_password">
                <div class="acct-card-body">
                    <div class="acct-form-grid">
                        <div class="acct-form-group full-width">
                            <label>Current Password <span class="required">*</span></label>
                            <input type="password" name="current_password" class="acct-form-input" required placeholder="Enter your current password" style="max-width:400px">
                        </div>
                        <div class="acct-form-group">
                            <label>New Password <span class="required">*</span></label>
                            <input type="password" name="new_password" id="newPwd" class="acct-form-input" required placeholder="Enter new password" minlength="4">
                            <div class="pwd-strength" id="pwdStrength">
                                <div class="pwd-strength-bar" id="bar1"></div>
                                <div class="pwd-strength-bar" id="bar2"></div>
                                <div class="pwd-strength-bar" id="bar3"></div>
                                <div class="pwd-strength-bar" id="bar4"></div>
                            </div>
                            <div class="pwd-strength-text" id="pwdText"></div>
                        </div>
                        <div class="acct-form-group">
                            <label>Confirm New Password <span class="required">*</span></label>
                            <input type="password" name="confirm_password" id="confirmPwd" class="acct-form-input" required placeholder="Re-enter new password">
                            <span class="acct-form-hint" id="matchHint"></span>
                        </div>
                    </div>
                </div>
                <div class="acct-card-footer">
                    <button type="reset" class="acct-btn acct-btn-outline">Cancel</button>
                    <button type="submit" class="acct-btn acct-btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══ SESSIONS TAB ═══ -->
    <div class="acct-section" id="tab-sessions">
        <div class="acct-card">
            <div class="acct-card-header">
                <h3>Active Sessions</h3>
                <p>Devices where your account is currently signed in</p>
            </div>
            <div class="acct-card-body">
                <div class="acct-session-item">
                    <div class="acct-session-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    </div>
                    <div class="acct-session-info">
                        <div class="sess-title"><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser') ?></div>
                        <div class="sess-meta"><?= $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1' ?> · Last active just now</div>
                    </div>
                    <span class="acct-session-badge">Current</span>
                </div>
            </div>
        </div>

        <div class="acct-card">
            <div class="acct-card-header">
                <h3>Sign Out</h3>
                <p>End your current session and return to the login page</p>
            </div>
            <div class="acct-card-footer" style="border-top: none;">
                <a href="/pos/logout.php" class="acct-btn acct-btn-outline" style="color:#ef4444; border-color:#fecaca;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">Sign Out</a>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Tab Switching ──
    var tabs = document.querySelectorAll('.acct-tab');
    var sections = document.querySelectorAll('.acct-section');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            tabs.forEach(function(t) { t.classList.remove('active'); });
            sections.forEach(function(s) { s.classList.remove('active'); });
            tab.classList.add('active');
            document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
        });
    });

    // Auto-switch to security tab if password error/success
    <?php if ($msg && $action === 'change_password'): ?>
    tabs.forEach(function(t) { t.classList.remove('active'); });
    sections.forEach(function(s) { s.classList.remove('active'); });
    document.querySelector('[data-tab="security"]').classList.add('active');
    document.getElementById('tab-security').classList.add('active');
    <?php endif; ?>

    // ── Password Strength Meter ──
    var newPwd = document.getElementById('newPwd');
    var confirmPwd = document.getElementById('confirmPwd');
    var bars = [document.getElementById('bar1'), document.getElementById('bar2'), document.getElementById('bar3'), document.getElementById('bar4')];
    var pwdText = document.getElementById('pwdText');
    var matchHint = document.getElementById('matchHint');

    if (newPwd) {
        newPwd.addEventListener('input', function() {
            var val = this.value;
            var score = 0;
            if (val.length >= 4) score++;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            var labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            var classes = ['', 'weak', 'medium', 'medium', 'strong'];
            bars.forEach(function(bar, i) {
                bar.className = 'pwd-strength-bar' + (i < score ? ' ' + classes[score] : '');
            });
            pwdText.textContent = val.length > 0 ? labels[score] || '' : '';
            checkMatch();
        });
    }

    if (confirmPwd) {
        confirmPwd.addEventListener('input', checkMatch);
    }

    function checkMatch() {
        if (!confirmPwd.value) { matchHint.textContent = ''; return; }
        if (newPwd.value === confirmPwd.value) {
            matchHint.textContent = '✓ Passwords match';
            matchHint.style.color = '#10b981';
        } else {
            matchHint.textContent = '✗ Passwords do not match';
            matchHint.style.color = '#ef4444';
        }
    }

    // ── Auto-dismiss toast ──
    var toast = document.querySelector('.acct-toast');
    if (toast) {
        setTimeout(function() {
            toast.style.transition = 'opacity 0.4s';
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 400);
        }, 5000);
    }
});
</script>

        </div>
    </div>
</div>
