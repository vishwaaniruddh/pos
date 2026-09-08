<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

include(__DIR__ . '/../db_connection.php');
$con = OpenSrishringarrCon();

// Handle AJAX Request (JSON)
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_POST['is_ajax']) || isset($_GET['is_ajax'])) {
    header('Content-Type: application/json');
    
    if (!$con) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit;
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    // Single transaction toggle
    if ($action === 'toggle') {
        $trans_id  = intval($_POST['trans_id'] ?? 0);
        $new_state = ($_POST['reconcile'] === 'Yes') ? 'Yes' : 'NO';
        
        if ($trans_id > 0) {
            if ($new_state === 'Yes') {
                $sql = "UPDATE `bank_transaction` SET `reconcile`='Yes', `reconcile_date`=CURDATE() WHERE `trans_id`=$trans_id";
            } else {
                $sql = "UPDATE `bank_transaction` SET `reconcile`='NO', `reconcile_date`=NULL WHERE `trans_id`=$trans_id";
            }
            $upd = mysqli_query($con, $sql);
            if ($upd) {
                $recon_date = ($new_state === 'Yes') ? date('d/m/Y') : '—';
                echo json_encode([
                    'success'        => true,
                    'trans_id'       => $trans_id,
                    'reconcile'      => $new_state,
                    'reconcile_date' => $recon_date
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($con)]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid transaction ID.']);
        }
        CloseCon($con);
        exit;
    }

    // Bulk reconcile / unreconcile
    if ($action === 'bulk') {
        $trans_ids  = isset($_POST['trans_ids']) ? (array)$_POST['trans_ids'] : [];
        $new_state  = ($_POST['reconcile'] === 'Yes') ? 'Yes' : 'NO';
        
        $sanitized_ids = array_filter(array_map('intval', $trans_ids));
        if (!empty($sanitized_ids)) {
            $id_list = implode(',', $sanitized_ids);
            if ($new_state === 'Yes') {
                $sql = "UPDATE `bank_transaction` SET `reconcile`='Yes', `reconcile_date`=CURDATE() WHERE `trans_id` IN ($id_list)";
            } else {
                $sql = "UPDATE `bank_transaction` SET `reconcile`='NO', `reconcile_date`=NULL WHERE `trans_id` IN ($id_list)";
            }
            $upd = mysqli_query($con, $sql);
            if ($upd) {
                echo json_encode([
                    'success'   => true,
                    'count'     => count($sanitized_ids),
                    'reconcile' => $new_state,
                    'date'      => ($new_state === 'Yes') ? date('d/m/Y') : '—'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($con)]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No transactions selected.']);
        }
        CloseCon($con);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unrecognized action.']);
    CloseCon($con);
    exit;
}

// Standard Form POST Submission
$bank_id   = intval($_POST['bank_id'] ?? 0);
$frmdate   = trim($_POST['frmdate'] ?? '');
$todate    = trim($_POST['todate'] ?? '');
$trans_ids = isset($_POST['trans_id']) ? (array)$_POST['trans_id'] : [];
$concil    = isset($_POST['concil']) ? (array)$_POST['concil'] : [];

// IDs that were checked
$checked_ids = [];
foreach ($concil as $cid) {
    $cid_int = intval($cid);
    if ($cid_int > 0) {
        $checked_ids[] = $cid_int;
    }
}

// IDs that were in the scope
$scope_ids = [];
foreach ($trans_ids as $tid) {
    $tid_int = intval($tid);
    if ($tid_int > 0) {
        $scope_ids[] = $tid_int;
    }
}

if ($con && !empty($scope_ids)) {
    mysqli_query($con, "SET AUTOCOMMIT=0");
    mysqli_query($con, "START TRANSACTION");

    // Reconcile checked IDs
    if (!empty($checked_ids)) {
        $checked_list = implode(',', $checked_ids);
        mysqli_query($con, "UPDATE `bank_transaction` SET `reconcile`='Yes', `reconcile_date`=CURDATE() WHERE `trans_id` IN ($checked_list)");
    }

    // Un-reconcile unchecked IDs that were submitted in this view
    $unchecked_ids = array_diff($scope_ids, $checked_ids);
    if (!empty($unchecked_ids)) {
        $unchecked_list = implode(',', $unchecked_ids);
        mysqli_query($con, "UPDATE `bank_transaction` SET `reconcile`='NO', `reconcile_date`=NULL WHERE `trans_id` IN ($unchecked_list)");
    }

    mysqli_query($con, "COMMIT");
}

if ($con) {
    CloseCon($con);
}

// Redirect back to bank_concile.php with state preserved
$redirect_url = "bank_concile.php?bank_id=$bank_id";
if ($frmdate) $redirect_url .= "&frmdate=" . urlencode($frmdate);
if ($todate)  $redirect_url .= "&todate=" . urlencode($todate);
$redirect_url .= "&status_msg=saved";

header("Location: $redirect_url");
exit;
