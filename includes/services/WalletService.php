<?php
declare(strict_types=1);

/**
 * Seller Wallet, Immutable Financial Ledger & Manual Withdrawal Service (bKash / Nagad / Rocket)
 * Uses exact fixed-point string math inside SQLite transactions — prevents negative balances or double payouts.
 */
final class WalletService
{
    public static function requestWithdrawal(int $sellerId, string $method, string $accountNumber, string $rawAmount): array
    {
        $method = strtolower(trim($method));
        if (!in_array($method, ['bkash', 'nagad', 'rocket'], true)) {
            return ['success' => false, 'message' => 'Please choose bKash, Nagad, or Rocket.'];
        }

        $cleanAcct = preg_replace('/[^0-9+-]/', '', trim($accountNumber)) ?? '';
        if (strlen($cleanAcct) < 10 || strlen($cleanAcct) > 16) {
            return ['success' => false, 'message' => 'Please enter a valid mobile wallet number.'];
        }

        if (!is_numeric($rawAmount)) {
            return ['success' => false, 'message' => 'Please enter a valid BDT withdrawal amount.'];
        }
        $amount = number_format((float)$rawAmount, 2, '.', '');
        $minWd = number_format((float)site_setting('min_withdrawal_amount', '500.00'), 2, '.', '');
        $maxWd = number_format((float)site_setting('max_withdrawal_amount', '100000.00'), 2, '.', '');

        if (bccomp($amount, $minWd, 2) < 0) {
            return ['success' => false, 'message' => 'Minimum withdrawal amount is ' . format_price($minWd) . '.'];
        }
        if (bccomp($amount, $maxWd, 2) > 0) {
            return ['success' => false, 'message' => 'Maximum single withdrawal amount is ' . format_price($maxWd) . '.'];
        }

        $pdo = db();
        $now = date('Y-m-d H:i:s');
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT OR IGNORE INTO `seller_balances` (`seller_id`) VALUES (?)")->execute([$sellerId]);
            $bStmt = $pdo->prepare("SELECT * FROM `seller_balances` WHERE `seller_id` = ? LIMIT 1");
            $bStmt->execute([$sellerId]);
            $bal = $bStmt->fetch();

            if (!$bal || bccomp((string)$bal['available_balance'], $amount, 2) < 0) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Insufficient available balance (' . format_price((string)($bal['available_balance'] ?? '0.00')) . ').'];
            }

            $newAvail = bcsub((string)$bal['available_balance'], $amount, 2);
            $newPending = bcadd((string)$bal['pending_withdrawal'], $amount, 2);
            $reqNo = generate_unique_code('WD');

            $pdo->prepare(
                "INSERT INTO `withdrawals` (`request_number`, `seller_id`, `method`, `account_number`, `amount`, `status`, `created_at`, `updated_at`)
                 VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)"
            )->execute([$reqNo, $sellerId, $method, $cleanAcct, $amount, $now, $now]);
            $wdId = (int)$pdo->lastInsertId();

            $pdo->prepare(
                "UPDATE `seller_balances` SET `available_balance` = ?, `pending_withdrawal` = ?, `updated_at` = ? WHERE `seller_id` = ?"
            )->execute([$newAvail, $newPending, $now, $sellerId]);

            $pdo->prepare(
                "INSERT INTO `financial_ledger`
                 (`reference_code`, `user_id`, `withdrawal_id`, `entry_type`, `direction`, `amount`, `balance_after`, `description`, `created_by`, `created_at`)
                 VALUES (?, ?, ?, 'withdrawal_hold', 'debit', ?, ?, ?, ?, ?)"
            )->execute([
                generate_unique_code('WHD'),
                $sellerId,
                $wdId,
                $amount,
                $newAvail,
                strtoupper($method) . " withdrawal requested ({$reqNo}) to {$cleanAcct}",
                $sellerId,
                $now,
            ]);

            $pdo->prepare(
                "INSERT INTO `withdrawal_history` (`withdrawal_id`, `old_status`, `new_status`, `note`, `changed_by`, `created_at`)
                 VALUES (?, NULL, 'pending', ?, ?, ?)"
            )->execute([$wdId, "Requested " . format_price($amount) . " to " . strtoupper($method) . " {$cleanAcct}", $sellerId, $now]);

            NotificationService::notifyAdmins(
                'withdrawal_request',
                "New withdrawal request {$reqNo}",
                format_price($amount) . " requested via " . strtoupper($method) . " ({$cleanAcct}).",
                'admin/withdrawals.php',
                'wallet'
            );

            $pdo->commit();
            return ['success' => true, 'request_number' => $reqNo, 'message' => "Withdrawal request {$reqNo} for " . format_price($amount) . " submitted successfully."];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Withdrawal request error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not submit withdrawal request. Please retry.'];
        }
    }

    public static function approveOrPay(int $withdrawalId, int $adminId, string $newStatus, string $paymentRef = '', string $adminNote = ''): array
    {
        if (!in_array($newStatus, ['approved', 'paid'], true)) {
            return ['success' => false, 'message' => 'Invalid withdrawal action.'];
        }

        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM `withdrawals` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$withdrawalId]);
        $wd = $stmt->fetch();
        if (!$wd) {
            return ['success' => false, 'message' => 'Withdrawal record not found.'];
        }

        $curStatus = (string)$wd['status'];
        if ($curStatus === 'paid' || $curStatus === 'rejected') {
            return ['success' => false, 'message' => 'This withdrawal has already been finalized.'];
        }

        if ($newStatus === 'paid' && trim($paymentRef) === '') {
            return ['success' => false, 'message' => 'Please enter the payout TrxID / payment reference before marking as Paid.'];
        }

        $now = date('Y-m-d H:i:s');
        $sellerId = (int)$wd['seller_id'];
        $amount = (string)$wd['amount'];

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE `withdrawals`
                 SET `status` = ?, `payment_reference` = ?, `admin_note` = ?, `processed_by` = ?, `processed_at` = ?, `updated_at` = ?
                 WHERE `id` = ?"
            )->execute([
                $newStatus,
                trim($paymentRef) !== '' ? mb_substr(trim($paymentRef), 0, 80) : $wd['payment_reference'],
                trim($adminNote) !== '' ? mb_substr(trim($adminNote), 0, 300) : $wd['admin_note'],
                $adminId,
                $now,
                $now,
                $withdrawalId,
            ]);

            if ($newStatus === 'paid') {
                $bStmt = $pdo->prepare("SELECT * FROM `seller_balances` WHERE `seller_id` = ? LIMIT 1");
                $bStmt->execute([$sellerId]);
                $bal = $bStmt->fetch();

                $newPending = bcsub((string)$bal['pending_withdrawal'], $amount, 2);
                if (bccomp($newPending, '0.00', 2) < 0) {
                    $newPending = '0.00';
                }
                $newPaidTotal = bcadd((string)$bal['withdrawn_total'], $amount, 2);

                $pdo->prepare(
                    "UPDATE `seller_balances` SET `pending_withdrawal` = ?, `withdrawn_total` = ?, `updated_at` = ? WHERE `seller_id` = ?"
                )->execute([$newPending, $newPaidTotal, $now, $sellerId]);

                $pdo->prepare(
                    "INSERT INTO `financial_ledger`
                     (`reference_code`, `user_id`, `withdrawal_id`, `entry_type`, `direction`, `amount`, `balance_after`, `description`, `created_by`, `created_at`)
                     VALUES (?, ?, ?, 'withdrawal_paid', 'debit', ?, ?, ?, ?, ?)"
                )->execute([
                    generate_unique_code('WPD'),
                    $sellerId,
                    $withdrawalId,
                    $amount,
                    (string)$bal['available_balance'],
                    strtoupper((string)$wd['method']) . " payout sent ({$wd['request_number']}) · Ref: " . trim($paymentRef),
                    $adminId,
                    $now,
                ]);
            }

            $pdo->prepare(
                "INSERT INTO `withdrawal_history` (`withdrawal_id`, `old_status`, `new_status`, `note`, `changed_by`, `created_at`)
                 VALUES (?, ?, ?, ?, ?, ?)"
            )->execute([
                $withdrawalId,
                $curStatus,
                $newStatus,
                $newStatus === 'paid' ? ("Disbursed · Ref: " . trim($paymentRef)) : 'Approved for disbursement',
                $adminId,
                $now,
            ]);

            NotificationService::send(
                $sellerId,
                'withdrawal_' . $newStatus,
                $newStatus === 'paid' ? "Withdrawal {$wd['request_number']} Paid!" : "Withdrawal {$wd['request_number']} Approved",
                $newStatus === 'paid'
                    ? format_price($amount) . " sent to your " . strtoupper((string)$wd['method']) . " ({$wd['account_number']}). Ref: " . trim($paymentRef)
                    : "Your withdrawal of " . format_price($amount) . " is approved and queued for transfer.",
                'seller/withdrawals.php',
                'check-circle'
            );

            log_admin_action($adminId, 'withdrawal_' . $newStatus, 'withdrawal', $withdrawalId, "{$wd['request_number']} (" . format_price($amount) . ") " . trim($paymentRef));

            $pdo->commit();
            return ['success' => true, 'message' => "Withdrawal {$wd['request_number']} marked " . ucfirst($newStatus) . "."];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Failed to update withdrawal: ' . $e->getMessage()];
        }
    }

    public static function reject(int $withdrawalId, int $adminId, string $reason): array
    {
        $reason = trim($reason);
        if (mb_strlen($reason, 'UTF-8') < 5) {
            return ['success' => false, 'message' => 'Please enter a rejection reason (min 5 characters).'];
        }

        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM `withdrawals` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$withdrawalId]);
        $wd = $stmt->fetch();
        if (!$wd || in_array((string)$wd['status'], ['paid', 'rejected'], true)) {
            return ['success' => false, 'message' => 'Withdrawal cannot be rejected in its current state.'];
        }

        $now = date('Y-m-d H:i:s');
        $sellerId = (int)$wd['seller_id'];
        $amount = (string)$wd['amount'];

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE `withdrawals` SET `status` = 'rejected', `admin_note` = ?, `processed_by` = ?, `processed_at` = ?, `updated_at` = ? WHERE `id` = ?"
            )->execute([mb_substr($reason, 0, 300), $adminId, $now, $now, $withdrawalId]);

            $bStmt = $pdo->prepare("SELECT * FROM `seller_balances` WHERE `seller_id` = ? LIMIT 1");
            $bStmt->execute([$sellerId]);
            $bal = $bStmt->fetch();

            $newPending = bcsub((string)$bal['pending_withdrawal'], $amount, 2);
            if (bccomp($newPending, '0.00', 2) < 0) {
                $newPending = '0.00';
            }
            $newAvail = bcadd((string)$bal['available_balance'], $amount, 2);

            $pdo->prepare(
                "UPDATE `seller_balances` SET `available_balance` = ?, `pending_withdrawal` = ?, `updated_at` = ? WHERE `seller_id` = ?"
            )->execute([$newAvail, $newPending, $now, $sellerId]);

            $pdo->prepare(
                "INSERT INTO `financial_ledger`
                 (`reference_code`, `user_id`, `withdrawal_id`, `entry_type`, `direction`, `amount`, `balance_after`, `description`, `created_by`, `created_at`)
                 VALUES (?, ?, ?, 'withdrawal_release', 'credit', ?, ?, ?, ?, ?)"
            )->execute([
                generate_unique_code('WRL'),
                $sellerId,
                $withdrawalId,
                $amount,
                $newAvail,
                "Returned held withdrawal {$wd['request_number']}: {$reason}",
                $adminId,
                $now,
            ]);

            $pdo->prepare(
                "INSERT INTO `withdrawal_history` (`withdrawal_id`, `old_status`, `new_status`, `note`, `changed_by`, `created_at`)
                 VALUES (?, ?, 'rejected', ?, ?, ?)"
            )->execute([$withdrawalId, (string)$wd['status'], $reason, $adminId, $now]);

            NotificationService::send(
                $sellerId,
                'withdrawal_rejected',
                "Withdrawal {$wd['request_number']} returned to balance",
                "Reason: {$reason}. " . format_price($amount) . " has been restored to your available balance.",
                'seller/withdrawals.php',
                'info'
            );

            log_admin_action($adminId, 'withdrawal_rejected', 'withdrawal', $withdrawalId, "{$wd['request_number']}: {$reason}");

            $pdo->commit();
            return ['success' => true, 'message' => "Withdrawal {$wd['request_number']} rejected and funds returned to seller balance."];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Could not reject withdrawal.'];
        }
    }
}
