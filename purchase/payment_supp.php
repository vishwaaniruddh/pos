<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (file_exists(__DIR__ . '/../top-header.php')) include_once(__DIR__ . '/../top-header.php');
else if (file_exists('../top-header.php')) include_once('../top-header.php');

if (file_exists(__DIR__ . '/../top-navbar.php')) include_once(__DIR__ . '/../top-navbar.php');
else if (file_exists('../top-navbar.php')) include_once('../top-navbar.php');
?>
<div class="container-fluid page-body-wrapper">
    <?php
    if (file_exists(__DIR__ . '/../navbar.php')) include_once(__DIR__ . '/../navbar.php');
    else if (file_exists('../navbar.php')) include_once('../navbar.php');

    if (file_exists(__DIR__ . '/../db_connection.php')) include_once(__DIR__ . '/../db_connection.php');
    else if (file_exists('../db_connection.php')) include_once('../db_connection.php');

    $con = OpenSrishringarrCon();

    function safe_str($v) {
        return trim((string)($v ?? ''));
    }

    $bills_arr = isset($_POST['payment']) && is_array($_POST['payment']) ? $_POST['payment'] : [];
    $payamt    = isset($_POST['payamt']) ? floatval($_POST['payamt']) : 0;
    $supp_id   = isset($_POST['supp_id']) ? safe_str($_POST['supp_id']) : '0';
    $bill_csv  = implode(',', array_map('intval', $bills_arr));

    // Lookup supplier company name
    $supp_name = 'Unknown Supplier';
    if ($supp_id !== '0' && $supp_id !== '') {
        $supp_safe = mysqli_real_escape_string($con, $supp_id);
        $qrysupp = mysqli_query($con, "SELECT company_name FROM `phppos_suppliers` WHERE `person_id`='$supp_safe' LIMIT 1");
        if ($qrysupp && $row_supp = mysqli_fetch_row($qrysupp)) {
            $supp_name = $row_supp[0];
        }
    }

    // Query active bank accounts
    $qrybanks = mysqli_query($con, "SELECT bank_id, bank_name FROM banks ORDER BY bank_name ASC");
    $banks = [];
    if ($qrybanks) {
        while ($rb = mysqli_fetch_assoc($qrybanks)) {
            $banks[] = $rb;
        }
    }
    ?>

    <!-- Main Panel -->
    <div class="main-panel">
        <div class="content-wrapper" style="padding: 1.5rem 1.75rem !important; background: #f8fafc; min-height: calc(100vh - 58px);">

            <!-- Load External Assets -->
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
                    --pm-slate-300: #cbd5e1;
                    --pm-slate-200: #e2e8f0;
                    --pm-slate-100: #f1f5f9;
                    --pm-slate-50:  #f8fafc;
                }

                body {
                    background-color: var(--pm-slate-50);
                    color: var(--pm-slate-900);
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                .voucher-container {
                    max-width: 800px;
                    margin: 0 auto;
                }

                .pm-page-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    flex-wrap: wrap;
                    gap: 16px;
                    margin-bottom: 24px;
                    padding-bottom: 16px;
                    border-bottom: 1px solid var(--pm-slate-200);
                }

                .pm-page-title {
                    font-size: 20px;
                    font-weight: 700;
                    color: var(--pm-slate-900);
                    margin: 0 0 4px 0;
                    letter-spacing: -0.02em;
                    display: flex;
                    align-items: center;
                }

                .pm-page-subtitle {
                    font-size: 13px;
                    color: var(--pm-slate-500);
                    margin: 0;
                }

                .pm-card {
                    background: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 8px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    margin-bottom: 20px;
                    overflow: hidden;
                }

                .pm-card-header {
                    padding: 16px 20px;
                    border-bottom: 1px solid var(--pm-slate-200);
                    background: #ffffff;
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
                }

                .pm-card-body {
                    padding: 22px 24px;
                }

                .pm-form-group {
                    margin-bottom: 18px;
                }

                .pm-form-label {
                    display: block;
                    font-size: 12.5px;
                    font-weight: 600;
                    color: var(--pm-slate-700);
                    margin-bottom: 6px;
                }

                .pm-form-label .req {
                    color: #ef4444;
                    margin-left: 2px;
                }

                .pm-control {
                    width: 100%;
                    height: 38px;
                    padding: 6px 12px;
                    font-size: 13px;
                    color: var(--pm-slate-900);
                    background-color: #ffffff;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    transition: border-color 0.15s ease, box-shadow 0.15s ease;
                }

                .pm-control:focus {
                    outline: none;
                    border-color: var(--pm-slate-900);
                    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.08);
                }

                .pm-btn-primary {
                    background: var(--pm-slate-900);
                    color: #ffffff !important;
                    border: 1px solid var(--pm-slate-900);
                    border-radius: 6px;
                    padding: 8px 20px;
                    font-size: 13.5px;
                    font-weight: 600;
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
                }

                .pm-btn-outline {
                    background: #ffffff;
                    color: var(--pm-slate-700) !important;
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 6px;
                    padding: 8px 16px;
                    font-size: 13px;
                    font-weight: 500;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    text-decoration: none;
                }

                .pm-btn-outline:hover {
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-900) !important;
                    border-color: var(--pm-slate-300);
                }

                .pm-bill-pill {
                    display: inline-block;
                    padding: 3px 8px;
                    font-size: 11.5px;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-weight: 600;
                    background: var(--pm-slate-100);
                    color: var(--pm-slate-700);
                    border: 1px solid var(--pm-slate-200);
                    border-radius: 4px;
                    margin-right: 4px;
                    margin-bottom: 4px;
                }
            </style>

            <div class="voucher-container">

                <!-- Page Header -->
                <div class="pm-page-header">
                    <div>
                        <h1 class="pm-page-title">
                            <i class="fa fa-credit-card text-muted" style="font-size: 18px; margin-right: 8px;"></i>
                            Vendor Payment Voucher
                        </h1>
                        <p class="pm-page-subtitle">Disburse payments against selected supplier bills and settle outstanding balances.</p>
                    </div>
                    <div>
                        <a href="view_bills.php" class="pm-btn-outline">
                            <i class="fa fa-arrow-left"></i> Return to Bills
                        </a>
                    </div>
                </div>

                <?php if (empty($bills_arr)) { ?>
                    <!-- Empty selection notice -->
                    <div class="pm-card text-center py-5">
                        <div class="mb-3 d-inline-flex align-items-center justify-content-center" 
                             style="width: 52px; height: 52px; border-radius: 50%; background: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b;">
                            <i class="fa fa-info-circle fa-lg"></i>
                        </div>
                        <h4 style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">No Bills Selected</h4>
                        <p style="font-size: 13px; color: #64748b; max-width: 440px; margin: 0 auto 16px;">
                            Please navigate to View Purchase Bills, select one or more unpaid bills for a supplier, and click "Proceed to Pay Supplier".
                        </p>
                        <a href="view_bills.php" class="pm-btn-primary">
                            Browse Purchase Bills
                        </a>
                    </div>
                <?php } else { ?>

                    <!-- Payment Voucher Card -->
                    <div class="pm-card">
                        <div class="pm-card-header">
                            <h3 class="pm-card-title">
                                <i class="fa fa-file-text-o text-muted" style="margin-right: 6px;"></i>
                                Settlement Particulars
                            </h3>
                            <span style="font-size: 12.5px; color: var(--pm-slate-500);">
                                Paying <strong><?php echo count($bills_arr); ?></strong> bill(s)
                            </span>
                        </div>
                        <div class="pm-card-body">

                            <!-- Summary Info Box -->
                            <div class="p-3 mb-4" style="background: var(--pm-slate-50); border: 1px solid var(--pm-slate-200); border-radius: 8px;">
                                <div class="row g-2 align-items-center">
                                    <div class="col-sm-6">
                                        <div style="font-size: 11.5px; color: var(--pm-slate-500); text-transform: uppercase; font-weight: 600;">Supplier / Vendor</div>
                                        <div style="font-size: 15px; font-weight: 700; color: var(--pm-slate-900); margin-top: 2px;">
                                            <?php echo htmlspecialchars($supp_name); ?>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 text-sm-end">
                                        <div style="font-size: 11.5px; color: var(--pm-slate-500); text-transform: uppercase; font-weight: 600;">Selected Outstanding</div>
                                        <div style="font-size: 18px; font-weight: 800; color: var(--pm-slate-900); margin-top: 2px;">
                                            ₹ <?php echo number_format($payamt, 2); ?>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-2 pt-2 border-top">
                                        <div style="font-size: 11.5px; color: var(--pm-slate-500); margin-bottom: 4px;">Associated Bill IDs:</div>
                                        <div>
                                            <?php foreach ($bills_arr as $b_id) { ?>
                                                <span class="pm-bill-pill">#<?php echo intval($b_id); ?></span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Form -->
                            <form id="payVoucherForm" action="processpaymentsupp.php" method="POST" onsubmit="return validateVoucher();">
                                <input type="hidden" name="supp" value="<?php echo htmlspecialchars($supp_name); ?>" />
                                <input type="hidden" name="bill" value="<?php echo htmlspecialchars($bill_csv); ?>" />

                                <div class="row g-3">
                                    <!-- Payment Mode -->
                                    <div class="col-md-6">
                                        <div class="pm-form-group">
                                            <label class="pm-form-label" for="mode">
                                                Payment Mode <span class="req">*</span>
                                            </label>
                                            <select name="mode" id="mode" class="pm-control" required>
                                                <option value="0">Select Payment Mode</option>
                                                <option value="cash" selected>Cash Payment</option>
                                                <option value="cheque">Cheque</option>
                                                <option value="neft">NEFT / RTGS</option>
                                                <option value="upi">UPI / Online Transfer</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Bank Account -->
                                    <div class="col-md-6">
                                        <div class="pm-form-group">
                                            <label class="pm-form-label" for="acc">
                                                From Bank Account <span class="req">*</span>
                                            </label>
                                            <select name="acc" id="acc" class="pm-control" required>
                                                <option value="0">Select Disbursing Account</option>
                                                <?php foreach ($banks as $bk) { ?>
                                                    <option value="<?php echo $bk['bank_id']; ?>">
                                                        <?php echo htmlspecialchars($bk['bank_name']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Payment Date -->
                                    <div class="col-md-6">
                                        <div class="pm-form-group">
                                            <label class="pm-form-label" for="paydate">
                                                Disbursement Date <span class="req">*</span>
                                            </label>
                                            <input type="date" name="paydate" id="paydate" class="pm-control" 
                                                   value="<?php echo date('Y-m-d'); ?>" required />
                                        </div>
                                    </div>

                                    <!-- Amount Paid -->
                                    <div class="col-md-6">
                                        <div class="pm-form-group">
                                            <label class="pm-form-label" for="amt">
                                                Payment Amount (₹) <span class="req">*</span>
                                            </label>
                                            <input type="number" step="0.01" min="0.01" name="amt" id="amt" class="pm-control" 
                                                   value="<?php echo $payamt; ?>" required style="font-weight: 700; font-size: 14px;" />
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-between">
                                    <a href="view_bills.php" class="pm-btn-outline">
                                        Cancel & Return
                                    </a>
                                    <button type="submit" name="submit" class="pm-btn-primary">
                                        <i class="fa fa-check-circle"></i> Confirm & Record Payment
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                <?php } ?>

            </div><!-- .voucher-container -->

            <script>
                function validateVoucher() {
                    var mode = document.getElementById('mode').value;
                    var acc  = document.getElementById('acc').value;
                    var amt  = parseFloat(document.getElementById('amt').value) || 0;

                    if (mode === "0" || mode === "") {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Payment Mode Required',
                            text: 'Please select a payment mode.',
                            confirmButtonColor: '#0f172a'
                        });
                        document.getElementById('mode').focus();
                        return false;
                    }

                    if (acc === "0" || acc === "") {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Bank Account Required',
                            text: 'Please select the disbursing bank account.',
                            confirmButtonColor: '#0f172a'
                        });
                        document.getElementById('acc').focus();
                        return false;
                    }

                    if (amt <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Valid Amount Required',
                            text: 'Please enter a valid disbursement amount greater than 0.',
                            confirmButtonColor: '#0f172a'
                        });
                        document.getElementById('amt').focus();
                        return false;
                    }

                    return true;
                }
            </script>

        </div><!-- .content-wrapper -->
    </div><!-- .main-panel -->
</div><!-- .page-body-wrapper -->

<?php
CloseCon($con);
if (file_exists(__DIR__ . '/../footer.php')) include_once(__DIR__ . '/../footer.php');
else if (file_exists('../footer.php')) include_once('../footer.php');
?>