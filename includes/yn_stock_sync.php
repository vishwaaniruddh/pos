<?php
// includes/yn_stock_sync.php
// Synchronizes POS inventory changes (bill deletion, edits, sales) back to the YN (web2) database and cache.

if (!function_exists('open_yn_website_connection')) {
    /**
     * Opens a mysqli connection to the Yosshita & Neha (web2) database across local and production environments.
     */
    function open_yn_website_connection() {
        @mysqli_report(MYSQLI_REPORT_OFF);
        $isLocal = function_exists('isLocalPosEnv')
            ? isLocalPosEnv()
            : (php_sapi_name() === 'cli' || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'xampp') !== false);

        $configs = [];
        if ($isLocal) {
            $configs[] = ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => 'yosshitaneha_db'];
            $configs[] = ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => 'u464193275_yosshitanehafs'];
        } else {
            $configs[] = ['host' => 'localhost', 'user' => 'u464193275_yosshitanehafs', 'pass' => 'AVav@@2026', 'db' => 'u464193275_yosshitanehafs'];
        }

        foreach ($configs as $cfg) {
            $conn = @mysqli_connect($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db']);
            if ($conn && !mysqli_connect_errno()) {
                @mysqli_set_charset($conn, 'utf8mb4');
                return $conn;
            }
        }

        return null;
    }
}

if (!function_exists('build_yn_sku_candidates')) {
    /**
     * Generates normalized SKU lookup candidates matching web2's get_pos_stock_for_skus().
     */
    function build_yn_sku_candidates($sku) {
        $clean = trim((string)$sku);
        if ($clean === '') {
            return [];
        }

        $candidates = [$clean];
        $stripped = trim(preg_replace('/\s*\(\d+\)$/', '', $clean));
        if ($stripped !== '' && $stripped !== $clean) {
            $candidates[] = $stripped;
        }

        foreach ([$clean, $stripped] as $variant) {
            if ($variant === '') {
                continue;
            }
            if (preg_match('/^([A-Za-z]+)0+(\d+)$/', $variant, $m)) {
                $candidates[] = $m[1] . $m[2];
            }
        }

        $normalized = [];
        foreach ($candidates as $cand) {
            $lower = strtolower(trim($cand));
            if ($lower !== '') {
                $normalized[] = $lower;
            }
        }

        return array_values(array_unique($normalized));
    }
}

if (!function_exists('purge_yn_website_cache')) {
    /**
     * Purges web2 API cache on disk so restored/updated product quantities appear immediately.
     */
    function purge_yn_website_cache() {
        $purgedFiles = 0;
        $cacheDirFound = false;

        $candidateDirs = [
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'yn' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'cache',
            dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'yn' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'cache',
        ];

        $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        if ($docRoot !== '') {
            $candidateDirs[] = $docRoot . DIRECTORY_SEPARATOR . 'yn' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'cache';
            $candidateDirs[] = dirname($docRoot) . DIRECTORY_SEPARATOR . 'yosshitaneha.com' . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'cache';
        }

        foreach (array_unique($candidateDirs) as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $cacheDirFound = true;
            $files = @glob($dir . DIRECTORY_SEPARATOR . 'cache_*.json');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (@unlink($file)) {
                        $purgedFiles++;
                    }
                }
            }
            @file_put_contents($dir . DIRECTORY_SEPARATOR . 'last_purge.txt', (string)time());
        }

        // Cross-vhost fallback when web2 resides under a separate open_basedir root
        if (!$cacheDirFound && php_sapi_name() !== 'cli' && function_exists('curl_init')) {
            $isLocal = function_exists('isLocalPosEnv') ? isLocalPosEnv() : false;
            $syncUrl = $isLocal
                ? 'http://localhost/yn/admin/api/sync_outfit_pos_stock.php'
                : 'https://yosshitaneha.com/admin/api/sync_outfit_pos_stock.php';

            $ch = @curl_init($syncUrl);
            if ($ch) {
                @curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT_MS => 1000,
                    CURLOPT_TIMEOUT_MS => 2000,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                @curl_exec($ch);
                @curl_close($ch);
            }
        }

        return ['cache_dir_found' => $cacheDirFound, 'purged_files' => $purgedFiles];
    }
}

if (!function_exists('sync_pos_items_to_yn')) {
    /**
     * Reverts or updates matching YN products.stock_qty, Client Diary lookbook status,
     * and deleted bill links after POS bill deletion, item edit, or sale.
     *
     * @param mysqli $posCon Open POS mysqli connection
     * @param array $itemAdjustments List of ['sku' => string, 'delta_qty' => int]
     * @param string $deletedBillNumber Deleted POS invoice number (if bill deleted)
     * @param int $numericBillId Deleted numeric POS bill_id (if bill deleted)
     * @param string $contextNote Optional reason/note for audit logging
     * @return array Summary of synced products and errors (never throws/blocks POS flow)
     */
    function sync_pos_items_to_yn($posCon, array $itemAdjustments, $deletedBillNumber = '', $numericBillId = 0, $contextNote = '') {
        $result = [
            'connected' => false,
            'updated_products' => [],
            'orders_unlinked' => 0,
            'cache_purged' => false,
        ];

        $ynCon = open_yn_website_connection();
        if (!$ynCon) {
            return $result;
        }
        $result['connected'] = true;

        try {
            $aggregated = [];
            foreach ($itemAdjustments as $entry) {
                $sku = trim((string)($entry['sku'] ?? $entry['item_id'] ?? ''));
                if ($sku === '') {
                    continue;
                }
                $key = strtolower($sku);
                if (!isset($aggregated[$key])) {
                    $aggregated[$key] = ['sku' => $sku, 'delta_qty' => 0];
                }
                $aggregated[$key]['delta_qty'] += (int)($entry['delta_qty'] ?? $entry['qty'] ?? 0);
            }

            foreach ($aggregated as $item) {
                $sku = $item['sku'];
                $deltaQty = (int)$item['delta_qty'];
                $candidates = build_yn_sku_candidates($sku);
                if (empty($candidates)) {
                    continue;
                }

                // Read authoritative live POS quantity for this item after the POS update
                $hasPosQty = false;
                $livePosQty = 0;
                if ($posCon) {
                    $posPlaceholders = implode(',', array_fill(0, count($candidates), '?'));
                    $posSql = "SELECT quantity FROM phppos_items WHERE LOWER(TRIM(name)) IN ($posPlaceholders) OR LOWER(TRIM(item_number)) IN ($posPlaceholders) ORDER BY is_deleted ASC, item_id ASC LIMIT 1";
                    $posStmt = @$posCon->prepare($posSql);
                    if ($posStmt) {
                        $types = str_repeat('s', count($candidates) * 2);
                        $params = array_merge($candidates, $candidates);
                        $posStmt->bind_param($types, ...$params);
                        if ($posStmt->execute()) {
                            $posRes = $posStmt->get_result();
                            if ($posRow = $posRes->fetch_assoc()) {
                                $hasPosQty = true;
                                $livePosQty = max(0, (int)round((float)$posRow['quantity']));
                            }
                        }
                        $posStmt->close();
                    }
                }

                // Locate matching product(s) in YN products table
                $ynPlaceholders = implode(',', array_fill(0, count($candidates), '?'));
                $findSql = "SELECT id, sku, stock_qty FROM products WHERE deleted_at IS NULL AND (LOWER(TRIM(sku)) IN ($ynPlaceholders) OR LOWER(TRIM(name)) IN ($ynPlaceholders))";
                $findStmt = @$ynCon->prepare($findSql);
                if (!$findStmt) {
                    continue;
                }

                $types = str_repeat('s', count($candidates) * 2);
                $params = array_merge($candidates, $candidates);
                $findStmt->bind_param($types, ...$params);
                if (!$findStmt->execute()) {
                    $findStmt->close();
                    continue;
                }

                $prodRes = $findStmt->get_result();
                $matchedProducts = [];
                while ($prodRow = $prodRes->fetch_assoc()) {
                    $matchedProducts[] = $prodRow;
                }
                $findStmt->close();

                foreach ($matchedProducts as $prod) {
                    $prodId = (int)$prod['id'];
                    $oldStock = (int)$prod['stock_qty'];
                    $newStock = $hasPosQty ? $livePosQty : max(0, $oldStock + $deltaQty);

                    if ($newStock !== $oldStock) {
                        $updStmt = @$ynCon->prepare("UPDATE products SET stock_qty = ? WHERE id = ?");
                        if ($updStmt) {
                            $updStmt->bind_param("ii", $newStock, $prodId);
                            $updStmt->execute();
                            $updStmt->close();
                        }
                    }

                    // If restocked above 0, draft any auto-bridged 'Client Diaries' sold-out lookbook entry
                    if ($newStock > 0) {
                        $collStmt = @$ynCon->prepare("UPDATE collections SET status = 'draft' WHERE product_id = ? AND is_sold = 1 AND status = 'published'");
                        if ($collStmt) {
                            $collStmt->bind_param("i", $prodId);
                            $collStmt->execute();
                            $collStmt->close();
                        }
                    }

                    $result['updated_products'][] = [
                        'product_id' => $prodId,
                        'sku' => $prod['sku'],
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                    ];
                }
            }

            // If a bill was deleted in POS, unlink any matching YN order's pos_bill_number and log activity
            $deletedBillNumber = trim((string)$deletedBillNumber);
            $numericBillId = (int)$numericBillId;
            $billRefs = array_values(array_unique(array_filter([
                $deletedBillNumber,
                $numericBillId > 0 ? (string)$numericBillId : '',
            ])));

            if (!empty($billRefs)) {
                $refPlaceholders = implode(',', array_fill(0, count($billRefs), '?'));
                $findOrdersStmt = @$ynCon->prepare("SELECT id, pos_bill_number FROM orders WHERE pos_bill_number IN ($refPlaceholders)");
                if ($findOrdersStmt) {
                    $refTypes = str_repeat('s', count($billRefs));
                    $findOrdersStmt->bind_param($refTypes, ...$billRefs);
                    if ($findOrdersStmt->execute()) {
                        $ordRes = $findOrdersStmt->get_result();
                        $affectedOrderIds = [];
                        while ($ordRow = $ordRes->fetch_assoc()) {
                            $affectedOrderIds[] = (int)$ordRow['id'];
                        }
                        $findOrdersStmt->close();

                        if (!empty($affectedOrderIds)) {
                            $clearStmt = @$ynCon->prepare("UPDATE orders SET pos_bill_number = NULL, pos_synced_at = NULL WHERE pos_bill_number IN ($refPlaceholders)");
                            if ($clearStmt) {
                                $clearStmt->bind_param($refTypes, ...$billRefs);
                                $clearStmt->execute();
                                $result['orders_unlinked'] = $clearStmt->affected_rows;
                                $clearStmt->close();
                            }

                            $billLabel = $deletedBillNumber ?: ('#' . $numericBillId);
                            $summarySkus = array_map(function ($p) {
                                return "{$p['sku']} ({$p['old_stock']} -> {$p['new_stock']})";
                            }, $result['updated_products']);
                            $details = "POS Bill {$billLabel} deleted" . ($contextNote ? " ({$contextNote})" : '') . (!empty($summarySkus) ? ' | Stock reverted: ' . implode(', ', $summarySkus) : '');
                            foreach ($affectedOrderIds as $ordId) {
                                $logStmt = @$ynCon->prepare("INSERT INTO activity_logs (user_type, action, entity_type, entity_id, details) VALUES ('admin', 'pos_bill_deleted', 'orders', ?, ?)");
                                if ($logStmt) {
                                    $logStmt->bind_param("is", $ordId, $details);
                                    $logStmt->execute();
                                    $logStmt->close();
                                }
                            }
                        }
                    } else {
                        $findOrdersStmt->close();
                    }
                }
            }

            $cacheRes = purge_yn_website_cache();
            $result['cache_purged'] = ($cacheRes['cache_dir_found'] || !empty($result['updated_products']));
        } catch (Throwable $e) {
            error_log("YN stock sync warning: " . $e->getMessage());
        } finally {
            @mysqli_close($ynCon);
        }

        return $result;
    }
}

if (!function_exists('delete_legacy_pos_approval_bill')) {
    /**
     * Deletes a bill in legacy GET delete endpoints, restores POS stock, and reverts YN stock.
     */
    function delete_legacy_pos_approval_bill($con, $billId, $reason = 'Deleted via POS') {
        $billId = (int)$billId;
        if ($billId <= 0) {
            return;
        }

        $billRes = mysqli_query($con, "SELECT bill_id, new_bill_number FROM `approval` WHERE `bill_id` = {$billId} LIMIT 1");
        $billRow = $billRes ? mysqli_fetch_assoc($billRes) : null;
        $newBillNumber = $billRow['new_bill_number'] ?? '';

        $ynSyncItems = [];
        $sql = mysqli_query($con, "SELECT item_id, qty, return_qty FROM `approval_detail` WHERE `bill_id` = {$billId}");
        while ($sql && ($row = mysqli_fetch_assoc($sql))) {
            $sku = trim((string)($row['item_id'] ?? ''));
            $restoreQty = max(0, (int)($row['qty'] ?? 0) - (int)($row['return_qty'] ?? 0));
            if ($sku !== '' && $restoreQty > 0) {
                $escSku = mysqli_real_escape_string($con, $sku);
                mysqli_query($con, "UPDATE `phppos_items` SET quantity = quantity + {$restoreQty} WHERE name = '{$escSku}' OR item_number = '{$escSku}'");
                $ynSyncItems[] = ['sku' => $sku, 'delta_qty' => $restoreQty];
            }
        }

        mysqli_query($con, "DELETE FROM `approval_detail` WHERE `bill_id` = {$billId}");
        mysqli_query($con, "DELETE FROM `paid_amount` WHERE `bid` = {$billId}");
        mysqli_query($con, "DELETE FROM `approval` WHERE `bill_id` = {$billId}");

        sync_pos_items_to_yn($con, $ynSyncItems, $newBillNumber ?: (string)$billId, $billId, $reason);
    }
}
