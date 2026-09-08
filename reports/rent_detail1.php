<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

$cid = isset($_GET['id']) ? mysqli_real_escape_string($con, trim($_GET['id'])) : '';

$result1 = mysqli_query($con, "SELECT s.*, p.first_name, p.last_name, p.phone_number FROM scheme s LEFT JOIN phppos_people p ON s.cust_id = p.person_id WHERE s.bill_id='$cid'");
$scheme = $result1 ? mysqli_fetch_assoc($result1) : null;

$bill_no = !empty($scheme['new_bill_number']) ? $scheme['new_bill_number'] : ($scheme['bill_id'] ?? $cid);
$cust_name = trim(($scheme['first_name'] ?? '') . ' ' . ($scheme['last_name'] ?? ''));
if (!$cust_name) $cust_name = 'Walk-in / General';
$phone = $scheme['phone_number'] ?? '';
$bill_date = (!empty($scheme['bill_date']) && $scheme['bill_date'] !== '0000-00-00') ? date('d/m/Y', strtotime($scheme['bill_date'])) : '—';
$m_date = (!empty($scheme['m_date']) && $scheme['m_date'] !== '0000-00-00') ? date('d/m/Y', strtotime($scheme['m_date'])) : '—';

$tot_amount = floatval($scheme['amount'] ?? 0);
$paid_amount = floatval($scheme['paid_amount'] ?? 0);
$bal_amount = $tot_amount - $paid_amount;
$ret_65 = round($tot_amount * 0.65);
$net_due = $tot_amount - $ret_65;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scheme Return - Invoice #<?= htmlspecialchars($bill_no) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --pm-slate-50: #f8fafc;
            --pm-slate-100: #f1f5f9;
            --pm-slate-200: #e2e8f0;
            --pm-slate-300: #cbd5e1;
            --pm-slate-400: #94a3b8;
            --pm-slate-500: #64748b;
            --pm-slate-600: #475569;
            --pm-slate-700: #334155;
            --pm-slate-800: #1e293b;
            --pm-slate-900: #0f172a;
        }

        body {
            background-color: var(--pm-slate-50);
            color: var(--pm-slate-900);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 30px 15px;
        }

        .receipt-card {
            max-width: 860px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid var(--pm-slate-200);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 28px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid var(--pm-slate-200);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .brand-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--pm-slate-900);
            margin: 0;
        }

        .brand-sub {
            font-size: 12px;
            color: var(--pm-slate-500);
            margin-top: 4px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
            padding: 16px;
            background: var(--pm-slate-50);
            border-radius: 6px;
            border: 1px solid var(--pm-slate-200);
        }

        .info-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--pm-slate-500);
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--pm-slate-900);
            margin-top: 2px;
        }

        .pm-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            text-align: left;
            margin-bottom: 24px;
        }

        .pm-table th {
            background: var(--pm-slate-100);
            color: var(--pm-slate-700);
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid var(--pm-slate-200);
            font-size: 11px;
            text-transform: uppercase;
        }

        .pm-table td {
            padding: 11px 12px;
            border-bottom: 1px solid var(--pm-slate-200);
            color: var(--pm-slate-700);
        }

        .pm-btn {
            height: 36px;
            padding: 0 16px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .pm-btn-primary {
            background: var(--pm-slate-900);
            color: #ffffff;
            border-color: var(--pm-slate-900);
        }

        .pm-btn-primary:hover {
            background: var(--pm-slate-800);
        }

        .pm-btn-secondary {
            background: #ffffff;
            color: var(--pm-slate-700);
            border-color: var(--pm-slate-200);
        }

        .pm-btn-secondary:hover {
            background: var(--pm-slate-50);
            color: var(--pm-slate-900);
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--pm-slate-200);
        }

        .return-box {
            background: #ffffff;
            border: 1px solid var(--pm-slate-300);
            border-radius: 6px;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 16px;
        }

        .pm-input {
            height: 36px;
            padding: 0 12px;
            font-size: 13px;
            border: 1px solid var(--pm-slate-300);
            border-radius: 6px;
            outline: none;
            width: 140px;
        }

        .pm-input:focus {
            border-color: var(--pm-slate-900);
            box-shadow: 0 0 0 1px var(--pm-slate-900);
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .receipt-card {
                border: none;
                box-shadow: none;
                padding: 0;
            }
            .action-bar, .return-box {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="receipt-card">
        <div class="header-top">
            <div>
                <h1 class="brand-title">Sri Shringarr Fashion Studio</h1>
                <div class="brand-sub">Scheme Return & Settlement Docket</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--pm-slate-400);">Bill Number</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--pm-slate-900); font-family: monospace;">#<?= htmlspecialchars($bill_no) ?></div>
                <div style="font-size: 12px; color: var(--pm-slate-500); margin-top: 2px;">Date: <?= $bill_date ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div>
                <div class="info-label">Customer Name</div>
                <div class="info-value"><?= htmlspecialchars($cust_name) ?></div>
                <?php if ($phone): ?>
                    <div style="font-size: 12px; color: var(--pm-slate-500); margin-top: 2px;"><i class="fa fa-phone" style="font-size: 10px;"></i> <?= htmlspecialchars($phone) ?></div>
                <?php endif; ?>
            </div>

            <div>
                <div class="info-label">Referred By / Agent</div>
                <div class="info-value"><?= htmlspecialchars($scheme['throught'] ?: '—') ?></div>
                <?php if (!empty($scheme['throught_phone'])): ?>
                    <div style="font-size: 12px; color: var(--pm-slate-500); margin-top: 2px;"><i class="fa fa-phone" style="font-size: 10px;"></i> <?= htmlspecialchars($scheme['throught_phone']) ?></div>
                <?php endif; ?>
            </div>

            <div>
                <div class="info-label">Maturity Date</div>
                <div class="info-value" style="font-family: monospace;"><?= $m_date ?></div>
            </div>

            <div>
                <div class="info-label">Financial Status</div>
                <div class="info-value">
                    Total: ₹ <?= number_format($tot_amount) ?> &bull; Paid: ₹ <?= number_format($paid_amount) ?> &bull; Bal: ₹ <?= number_format($bal_amount) ?>
                </div>
            </div>
        </div>

        <table class="pm-table">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Particulars</th>
                    <th style="text-align: right;">Price (₹)</th>
                    <th style="text-align: right;">Scheme Amt (₹)</th>
                    <th style="text-align: center;">Discount</th>
                    <th style="text-align: right;">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res_items = mysqli_query($con, "SELECT sd.*, pi.name as item_name FROM scheme_detail sd LEFT JOIN phppos_items pi ON sd.item_id = pi.name WHERE sd.bill_id = '$cid'");
                $has_items = false;
                if ($res_items && mysqli_num_rows($res_items) > 0) {
                    while ($it = mysqli_fetch_assoc($res_items)) {
                        $has_items = true;
                        $disc = ($it['discount_type'] === '%') ? ($it['discount'] . '%') : ('Rs.' . $it['discount']);
                ?>
                <tr>
                    <td style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($it['item_id'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($it['item_name'] ?: ($it['item_id'] ?? 'Item')) ?></td>
                    <td style="text-align: right; font-family: monospace;">₹ <?= number_format(floatval($it['price'] ?? 0)) ?></td>
                    <td style="text-align: right; font-family: monospace;">₹ <?= number_format(floatval($it['amount'] ?? 0)) ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($disc) ?></td>
                    <td style="text-align: right; font-family: monospace; font-weight: 600;">₹ <?= number_format(floatval($it['total_amount'] ?? 0)) ?></td>
                </tr>
                <?php
                    }
                }
                if (!$has_items):
                ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--pm-slate-400); padding: 20px;">
                        No line items recorded for this scheme docket.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Settlement Calculations Summary -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
            <div style="width: 280px; background: var(--pm-slate-50); border: 1px solid var(--pm-slate-200); border-radius: 6px; padding: 12px 16px;">
                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px;">
                    <span style="color: var(--pm-slate-500);">Scheme Total:</span>
                    <span style="font-weight: 600; font-family: monospace;">₹ <?= number_format($tot_amount) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px;">
                    <span style="color: var(--pm-slate-500);">Return Value (65%):</span>
                    <span style="font-weight: 600; font-family: monospace;">₹ <?= number_format($ret_65) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; border-top: 1px solid var(--pm-slate-200); padding-top: 6px; color: var(--pm-slate-900);">
                    <span>Net Refund / Due:</span>
                    <span style="font-family: monospace;">₹ <?= number_format($net_due) ?></span>
                </div>
            </div>
        </div>

        <?php if (($scheme['status'] ?? '') === 'A'): ?>
        <!-- Return Settlement Form -->
        <form action="updateRent1.php" method="POST">
            <input type="hidden" name="id" value="<?= htmlspecialchars($cid) ?>">
            <div class="return-box">
                <span style="font-size: 13px; font-weight: 600; color: var(--pm-slate-700);">Settlement Paid Amount (₹):</span>
                <input type="number" step="any" name="amt" class="pm-input" placeholder="0.00" value="<?= $net_due ?>" required>
                <button type="submit" name="Submit" value="Scheme Return" class="pm-btn pm-btn-primary">
                    <i class="fa fa-check"></i> Complete Return
                </button>
            </div>
        </form>
        <?php else: ?>
        <div style="padding: 12px 16px; background: var(--pm-slate-100); border-radius: 6px; color: var(--pm-slate-700); font-size: 13px; font-weight: 600; text-align: center;">
            <i class="fa fa-circle-check" style="color: #10b981; margin-right: 6px;"></i> This scheme return has already been settled and closed (Status: <?= htmlspecialchars($scheme['status'] ?? '') ?>).
        </div>
        <?php endif; ?>

        <div class="action-bar">
            <a href="rent_return1.php" class="pm-btn pm-btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Scheme Returns
            </a>
            <button type="button" class="pm-btn pm-btn-secondary" onclick="window.print();">
                <i class="fa fa-print"></i> Print Docket
            </button>
        </div>
    </div>

<?php CloseCon($con); ?>
</body>
</html>