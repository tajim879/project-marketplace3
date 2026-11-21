<?php
declare(strict_types=1);

/**
 * Order, Shopping Cart, Server-Side Coupon Validation & Free License Acquisition Service
 * Never trusts client-supplied prices; uses DECIMAL bcmath arithmetic and PDO transactions.
 */
final class OrderService
{
    public static function getOrCreateCart(): array
    {
        $pdo = db();
        $uid = current_user_id();
        $sid = session_id();

        if ($uid) {
            $stmt = $pdo->prepare("SELECT * FROM `carts` WHERE `user_id` = ? AND `status` = 'active' ORDER BY `id` DESC LIMIT 1");
            $stmt->execute([$uid]);
            $cart = $stmt->fetch();
            if ($cart) {
                return $cart;
            }
            $pdo->prepare("INSERT INTO `carts` (`user_id`, `session_id`, `status`) VALUES (?, ?, 'active')")
                ->execute([$uid, $sid]);
            return ['id' => (int)$pdo->lastInsertId(), 'user_id' => $uid, 'session_id' => $sid, 'coupon_id' => null, 'status' => 'active'];
        }

        $stmt = $pdo->prepare("SELECT * FROM `carts` WHERE `user_id` IS NULL AND `session_id` = ? AND `status` = 'active' ORDER BY `id` DESC LIMIT 1");
        $stmt->execute([$sid]);
        $cart = $stmt->fetch();
        if ($cart) {
            return $cart;
        }
        $pdo->prepare("INSERT INTO `carts` (`user_id`, `session_id`, `status`) VALUES (NULL, ?, 'active')")
            ->execute([$sid]);
        return ['id' => (int)$pdo->lastInsertId(), 'user_id' => null, 'session_id' => $sid, 'coupon_id' => null, 'status' => 'active'];
    }

    public static function mergeGuestCart(string $guestSessionId, int $userId): void
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM `carts` WHERE `user_id` IS NULL AND `session_id` = ? AND `status` = 'active' LIMIT 1");
        $stmt->execute([$guestSessionId]);
        $guestCart = $stmt->fetch();
        if (!$guestCart) {
            return;
        }

        $userCartStmt = $pdo->prepare("SELECT * FROM `carts` WHERE `user_id` = ? AND `status` = 'active' LIMIT 1");
        $userCartStmt->execute([$userId]);
        $userCart = $userCartStmt->fetch();

        if (!$userCart) {
            $pdo->prepare("UPDATE `carts` SET `user_id` = ?, `session_id` = ? WHERE `id` = ?")
                ->execute([$userId, session_id(), $guestCart['id']]);
            return;
        }

        $items = $pdo->prepare("SELECT `project_id`, `license_type` FROM `cart_items` WHERE `cart_id` = ?");
        $items->execute([$guestCart['id']]);
        foreach ($items->fetchAll() as $item) {
            // Skip own project
            $own = $pdo->prepare("SELECT `seller_id` FROM `projects` WHERE `id` = ?");
            $own->execute([$item['project_id']]);
            if ((int)$own->fetchColumn() === $userId) {
                continue;
            }
            $pdo->prepare(
                "INSERT INTO `cart_items` (`cart_id`, `project_id`, `license_type`)
                 VALUES (?, ?, ?)
                 ON CONFLICT(`cart_id`, `project_id`) DO UPDATE SET `license_type` = excluded.`license_type`"
            )->execute([$userCart['id'], $item['project_id'], $item['license_type']]);
        }

        $pdo->prepare("UPDATE `carts` SET `status` = 'converted' WHERE `id` = ?")
            ->execute([$guestCart['id']]);
    }

    public static function addItem(int $projectId, string $licenseType = 'regular'): array
    {
        $licenseType = in_array($licenseType, ['regular', 'extended'], true) ? $licenseType : 'regular';
        $pdo = db();

        $stmt = $pdo->prepare("SELECT * FROM `projects` WHERE `id` = ? AND `status` = 'approved' LIMIT 1");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch();

        if (!$project) {
            return ['success' => false, 'message' => 'Project not found or unavailable.'];
        }

        $uid = current_user_id();
        if ($uid && (int)$project['seller_id'] === $uid) {
            return ['success' => false, 'message' => 'You cannot purchase your own project.'];
        }

        if ($uid && user_has_purchased_project($uid, $projectId)) {
            return ['success' => false, 'message' => 'You already own this project. Visit Downloads to access the files.'];
        }

        if ((int)$project['is_free'] === 1) {
            return [
                'success' => false,
                'is_free' => true,
                'message' => 'This is a free project—claim and download it directly from the project page.',
            ];
        }

        $cart = self::getOrCreateCart();
        $pdo->prepare(
            "INSERT INTO `cart_items` (`cart_id`, `project_id`, `license_type`)
             VALUES (?, ?, ?)
             ON CONFLICT(`cart_id`, `project_id`) DO UPDATE SET `license_type` = excluded.`license_type`"
        )->execute([$cart['id'], $projectId, $licenseType]);

        return [
            'success' => true,
            'message' => 'Added "' . $project['title'] . '" to your cart.',
            'cart_count' => active_cart_count(),
        ];
    }

    public static function removeItem(int $projectId): array
    {
        $cart = self::getOrCreateCart();
        db()->prepare("DELETE FROM `cart_items` WHERE `cart_id` = ? AND `project_id` = ?")
            ->execute([$cart['id'], $projectId]);

        return [
            'success' => true,
            'message' => 'Item removed from your cart.',
            'cart_count' => active_cart_count(),
        ];
    }

    public static function getCartSummary(): array
    {
        $cart = self::getOrCreateCart();
        $pdo = db();

        $stmt = $pdo->prepare(
            "SELECT ci.id AS cart_item_id, ci.license_type,
                    p.id AS project_id, p.title, p.slug, p.thumbnail, p.price, p.discount_price,
                    p.extended_price, p.is_free, p.version, p.support_months,
                    c.name AS category_name, c.slug AS category_slug,
                    COALESCE(NULLIF(up.store_name, ''), u.full_name) AS seller_name,
                    u.username AS seller_username, u.is_verified_seller
             FROM `cart_items` ci
             INNER JOIN `projects` p ON p.id = ci.project_id AND p.status = 'approved'
             INNER JOIN `categories` c ON c.id = p.category_id
             INNER JOIN `users` u ON u.id = p.seller_id
             LEFT JOIN `user_profiles` up ON up.user_id = u.id
             WHERE ci.cart_id = ?
             ORDER BY ci.id DESC"
        );
        $stmt->execute([$cart['id']]);
        $rows = $stmt->fetchAll();

        $subtotal = '0.00';
        $items = [];
        foreach ($rows as $row) {
            $unit = effective_project_price($row, (string)$row['license_type']);
            $row['computed_price'] = $unit;
            $subtotal = bcadd($subtotal, $unit, 2);
            $items[] = $row;
        }

        $coupon = null;
        $discount = '0.00';
        if (!empty($cart['coupon_id']) && !empty($items)) {
            $cStmt = $pdo->prepare("SELECT * FROM `coupons` WHERE `id` = ? LIMIT 1");
            $cStmt->execute([$cart['coupon_id']]);
            $candidate = $cStmt->fetch();
            if ($candidate) {
                $eval = self::evaluateCoupon($candidate, $subtotal);
                if ($eval['valid']) {
                    $coupon = $candidate;
                    $discount = $eval['discount'];
                } else {
                    $pdo->prepare("UPDATE `carts` SET `coupon_id` = NULL WHERE `id` = ?")->execute([$cart['id']]);
                }
            }
        }

        $total = bcsub($subtotal, $discount, 2);
        if (bccomp($total, '0.00', 2) < 0) {
            $total = '0.00';
        }

        return [
            'cart' => $cart,
            'items' => $items,
            'items_count' => count($items),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'coupon' => $coupon,
        ];
    }

    public static function evaluateCoupon(array $coupon, string $subtotal): array
    {
        if (($coupon['status'] ?? '') !== 'active') {
            return ['valid' => false, 'message' => 'This coupon is no longer active.'];
        }

        $now = new DateTimeImmutable('now');
        if ($now < new DateTimeImmutable((string)$coupon['starts_at'])) {
            return ['valid' => false, 'message' => 'This coupon is not active yet.'];
        }
        if ($now > new DateTimeImmutable((string)$coupon['expires_at'])) {
            return ['valid' => false, 'message' => 'This coupon has expired.'];
        }
        if ($coupon['usage_limit'] !== null && (int)$coupon['used_count'] >= (int)$coupon['usage_limit']) {
            return ['valid' => false, 'message' => 'This coupon has reached its maximum usage limit.'];
        }
        if (bccomp($subtotal, (string)$coupon['min_order_amount'], 2) < 0) {
            return [
                'valid' => false,
                'message' => 'Minimum cart subtotal of ' . format_price((string)$coupon['min_order_amount']) . ' required for this coupon.',
            ];
        }

        if ($coupon['discount_type'] === 'percentage') {
            $discount = bcdiv(bcmul($subtotal, (string)$coupon['discount_value'], 4), '100', 2);
            if ($coupon['max_discount_amount'] !== null && bccomp($discount, (string)$coupon['max_discount_amount'], 2) > 0) {
                $discount = number_format((float)$coupon['max_discount_amount'], 2, '.', '');
            }
        } else {
            $discount = number_format((float)$coupon['discount_value'], 2, '.', '');
        }

        if (bccomp($discount, $subtotal, 2) > 0) {
            $discount = $subtotal;
        }

        return ['valid' => true, 'discount' => $discount];
    }

    public static function applyCouponCode(string $rawCode): array
    {
        $code = strtoupper(trim($rawCode));
        if ($code === '') {
            return ['success' => false, 'message' => 'Please enter a coupon code.'];
        }

        $summary = self::getCartSummary();
        if (empty($summary['items'])) {
            return ['success' => false, 'message' => 'Add at least one project to your cart before applying a coupon.'];
        }

        $stmt = db()->prepare("SELECT * FROM `coupons` WHERE `code` = ? LIMIT 1");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();
        if (!$coupon) {
            return ['success' => false, 'message' => 'Invalid coupon code.'];
        }

        $eval = self::evaluateCoupon($coupon, $summary['subtotal']);
        if (!$eval['valid']) {
            return ['success' => false, 'message' => $eval['message']];
        }

        db()->prepare("UPDATE `carts` SET `coupon_id` = ? WHERE `id` = ?")
            ->execute([$coupon['id'], $summary['cart']['id']]);

        return [
            'success' => true,
            'message' => 'Coupon ' . $coupon['code'] . ' applied — saved ' . format_price($eval['discount']) . '.',
        ];
    }

    public static function removeCoupon(): void
    {
        $cart = self::getOrCreateCart();
        db()->prepare("UPDATE `carts` SET `coupon_id` = NULL WHERE `id` = ?")
            ->execute([$cart['id']]);
    }

    /**
     * Grant a verified $0 license for approved Free Projects inside a transaction.
     */
    public static function claimFreeProject(int $buyerId, int $projectId): array
    {
        $existing = user_has_purchased_project($buyerId, $projectId);
        if ($existing) {
            return ['success' => true, 'order_item_id' => (int)$existing['id'], 'already_owned' => true];
        }

        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM `projects` WHERE `id` = ? AND `status` = 'approved' AND `is_free` = 1 LIMIT 1");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch();
        if (!$project) {
            return ['success' => false, 'message' => 'Free project not found or requires purchase.'];
        }

        if ((int)$project['seller_id'] === $buyerId) {
            return ['success' => false, 'message' => 'You are the author of this project.'];
        }

        $pdo->beginTransaction();
        try {
            $orderNo = generate_unique_code('FR');
            $pdo->prepare(
                "INSERT INTO `orders` (`order_number`, `buyer_id`, `subtotal_amount`, `discount_amount`, `total_amount`, `currency`, `status`, `paid_at`)
                 VALUES (?, ?, '0.00', '0.00', '0.00', 'BDT', 'paid', ?)"
            )->execute([$orderNo, $buyerId, date('Y-m-d H:i:s')]);
            $orderId = (int)$pdo->lastInsertId();

            $licenseType = in_array($project['license_type'], ['mit', 'free_personal'], true) ? $project['license_type'] : 'free_personal';
            $licenseKey = License::generateKey($licenseType);
            $limit = (int)site_setting('download_limit_default', '25');

            $pdo->prepare(
                "INSERT INTO `order_items`
                 (`order_id`, `project_id`, `seller_id`, `project_title`, `license_type`, `license_key`,
                  `unit_price`, `discount_share`, `final_price`, `commission_rate`, `commission_amount`,
                  `seller_earning`, `download_permitted`, `download_limit`, `status`)
                 VALUES (?, ?, ?, ?, ?, ?, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, ?, 'active')"
            )->execute([
                $orderId,
                $projectId,
                $project['seller_id'],
                $project['title'],
                $licenseType,
                $licenseKey,
                $limit,
            ]);
            $orderItemId = (int)$pdo->lastInsertId();

            $pdo->prepare(
                "INSERT INTO `order_status_history` (`order_id`, `old_status`, `new_status`, `note`, `changed_by`)
                 VALUES (?, NULL, 'paid', 'Free community license activated', ?)"
            )->execute([$orderId, $buyerId]);

            NotificationService::send(
                $buyerId,
                'download_available',
                'Free license ready: ' . $project['title'],
                "Order {$orderNo} is active. Your secure package and license key are ready in Downloads.",
                url('buyer/downloads.php'),
                'download'
            );

            $pdo->commit();
            return ['success' => true, 'order_item_id' => $orderItemId, 'order_number' => $orderNo];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Free claim failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not activate free license. Please retry.'];
        }
    }

    /**
     * Create a real marketplace order from the authenticated buyer's active cart.
     */
    public static function createOrderFromCart(int $buyerId, ?string $notes = null): array
    {
        $summary = self::getCartSummary();
        if (empty($summary['items'])) {
            return ['success' => false, 'message' => 'Your cart is empty. Add at least one project before checking out.'];
        }

        // Guard against self-purchase or duplicate ownership
        foreach ($summary['items'] as $it) {
            $pid = (int)$it['project_id'];
            $sellerStmt = db()->prepare("SELECT `seller_id` FROM `projects` WHERE `id` = ? LIMIT 1");
            $sellerStmt->execute([$pid]);
            if ((int)$sellerStmt->fetchColumn() === $buyerId) {
                return ['success' => false, 'message' => 'Remove your own project "' . $it['title'] . '" from the cart before checkout.'];
            }
            if (user_has_purchased_project($buyerId, $pid)) {
                return ['success' => false, 'message' => 'You already own "' . $it['title'] . '". Remove it from your cart first.'];
            }
        }

        $pdo = db();
        $now = date('Y-m-d H:i:s');
        $subtotal = (string)$summary['subtotal'];
        $discount = (string)$summary['discount'];
        $total = (string)$summary['total'];
        $coupon = $summary['coupon'];
        $isZeroTotal = bccomp($total, '0.00', 2) === 0;
        $dlLimit = max(1, (int)site_setting('default_download_limit', '25'));

        $pdo->beginTransaction();
        try {
            $orderNo = generate_unique_code('ORD');
            $initialStatus = $isZeroTotal ? 'paid' : 'pending_payment';

            $pdo->prepare(
                "INSERT INTO `orders`
                 (`order_number`, `buyer_id`, `coupon_id`, `coupon_code`, `subtotal_amount`, `discount_amount`,
                  `total_amount`, `currency`, `status`, `notes`, `paid_at`, `created_at`, `updated_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'BDT', ?, ?, ?, ?, ?)"
            )->execute([
                $orderNo,
                $buyerId,
                $coupon ? (int)$coupon['id'] : null,
                $coupon ? (string)$coupon['code'] : null,
                $subtotal,
                $discount,
                $total,
                $initialStatus,
                $notes !== null && trim($notes) !== '' ? mb_substr(trim($notes), 0, 500) : null,
                $isZeroTotal ? $now : null,
                $now,
                $now,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            // Distribute coupon discount across items to the exact paisa
            $remainingDiscount = $discount;
            $itemCount = count($summary['items']);
            foreach ($summary['items'] as $idx => $item) {
                $unit = (string)$item['computed_price'];
                if (bccomp($discount, '0.00', 2) > 0 && bccomp($subtotal, '0.00', 2) > 0) {
                    if ($idx === $itemCount - 1) {
                        $share = $remainingDiscount;
                    } else {
                        $ratio = bcdiv($unit, $subtotal, 6);
                        $share = bcmul($discount, $ratio, 2);
                        if (bccomp($share, $remainingDiscount, 2) > 0) {
                            $share = $remainingDiscount;
                        }
                        $remainingDiscount = bcsub($remainingDiscount, $share, 2);
                    }
                } else {
                    $share = '0.00';
                }

                $finalPrice = bcsub($unit, $share, 2);
                if (bccomp($finalPrice, '0.00', 2) < 0) {
                    $finalPrice = '0.00';
                }

                $sellerStmt = $pdo->prepare("SELECT `seller_id` FROM `projects` WHERE `id` = ? LIMIT 1");
                $sellerStmt->execute([(int)$item['project_id']]);
                $sellerId = (int)$sellerStmt->fetchColumn();

                $commRate = seller_commission_rate($sellerId);
                $commAmount = bcmul($finalPrice, bcdiv($commRate, '100', 6), 2);
                $sellerEarning = bcsub($finalPrice, $commAmount, 2);

                $licType = in_array((string)$item['license_type'], ['regular', 'extended'], true) ? (string)$item['license_type'] : 'regular';
                $licKey = $isZeroTotal ? License::generateKey($licType) : null;

                $pdo->prepare(
                    "INSERT INTO `order_items`
                     (`order_id`, `project_id`, `seller_id`, `project_title`, `license_type`, `license_key`,
                      `unit_price`, `discount_share`, `final_price`, `commission_rate`, `commission_amount`,
                      `seller_earning`, `download_permitted`, `download_limit`, `status`, `created_at`, `updated_at`)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                )->execute([
                    $orderId,
                    (int)$item['project_id'],
                    $sellerId,
                    (string)$item['title'],
                    $licType,
                    $licKey,
                    $unit,
                    $share,
                    $finalPrice,
                    $commRate,
                    $commAmount,
                    $sellerEarning,
                    $isZeroTotal ? 1 : 0,
                    $dlLimit,
                    $isZeroTotal ? 'active' : 'pending',
                    $now,
                    $now,
                ]);

                if ($isZeroTotal) {
                    $pdo->prepare("UPDATE `projects` SET `sales_count` = `sales_count` + 1 WHERE `id` = ?")
                        ->execute([(int)$item['project_id']]);
                }
            }

            if ($coupon) {
                $pdo->prepare("UPDATE `coupons` SET `used_count` = `used_count` + 1 WHERE `id` = ?")
                    ->execute([(int)$coupon['id']]);
                $pdo->prepare(
                    "INSERT INTO `coupon_usages` (`coupon_id`, `user_id`, `order_id`, `discount_amount`, `used_at`)
                     VALUES (?, ?, ?, ?, ?)"
                )->execute([(int)$coupon['id'], $buyerId, $orderId, $discount, $now]);
            }

            // Convert active cart
            $pdo->prepare("UPDATE `carts` SET `status` = 'converted', `updated_at` = ? WHERE `id` = ?")
                ->execute([$now, (int)$summary['cart']['id']]);

            $pdo->prepare(
                "INSERT INTO `order_status_history` (`order_id`, `old_status`, `new_status`, `note`, `changed_by`, `created_at`)
                 VALUES (?, NULL, ?, ?, ?, ?)"
            )->execute([
                $orderId,
                $initialStatus,
                $isZeroTotal ? '100% coupon discount applied — license unlocked' : 'Order placed — awaiting manual payment submission',
                $buyerId,
                $now,
            ]);

            NotificationService::send(
                $buyerId,
                'order_created',
                "Order {$orderNo} created",
                $isZeroTotal
                    ? 'Your order is active and ready to download.'
                    : 'Submit your bKash, Nagad, or Rocket transaction details to unlock your downloads.',
                $isZeroTotal ? 'buyer/downloads.php' : ('payment.php?order=' . urlencode($orderNo)),
                'shopping-bag'
            );

            $pdo->commit();
            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNo,
                'status' => $initialStatus,
                'total' => $total,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Create order error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not create order. Please try again.'];
        }
    }

    /**
     * Submit manual bKash / Nagad / Rocket payment details & optional screenshot proof.
     */
    public static function submitManualPayment(
        int $buyerId,
        string $orderNumber,
        int $paymentMethodId,
        string $senderNumber,
        string $transactionId,
        string $paidAmount,
        ?string $buyerNote,
        array $proofFile
    ): array {
        $pdo = db();
        $oStmt = $pdo->prepare("SELECT * FROM `orders` WHERE `order_number` = ? AND `buyer_id` = ? LIMIT 1");
        $oStmt->execute([$orderNumber, $buyerId]);
        $order = $oStmt->fetch();

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found.'];
        }
        if (!in_array((string)$order['status'], ['pending_payment', 'rejected'], true)) {
            return ['success' => false, 'message' => 'This order is not awaiting payment submission.'];
        }

        $mStmt = $pdo->prepare("SELECT * FROM `payment_methods` WHERE `id` = ? AND `is_enabled` = 1 LIMIT 1");
        $mStmt->execute([$paymentMethodId]);
        $method = $mStmt->fetch();
        if (!$method) {
            return ['success' => false, 'message' => 'Selected payment method is unavailable.'];
        }

        $cleanTrx = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', trim($transactionId)) ?? '');
        if (strlen($cleanTrx) < 5 || strlen($cleanTrx) > 40) {
            return ['success' => false, 'message' => 'Please enter a valid Transaction ID (5–40 alphanumeric characters).'];
        }

        $cleanSender = preg_replace('/[^0-9+-]/', '', trim($senderNumber)) ?? '';
        if (strlen($cleanSender) < 10 || strlen($cleanSender) > 16) {
            return ['success' => false, 'message' => 'Please enter a valid bKash/Nagad/Rocket sender mobile number.'];
        }

        if (!is_numeric($paidAmount) || bccomp(number_format((float)$paidAmount, 2, '.', ''), (string)$order['total_amount'], 2) !== 0) {
            return ['success' => false, 'message' => 'Paid amount must match the exact order total (' . format_price((string)$order['total_amount']) . ').'];
        }

        $methodCode = strtolower((string)$method['code']);
        // Ensure unique TrxID
        $dupStmt = $pdo->prepare("SELECT `id` FROM `payments` WHERE `method_code` = ? AND `transaction_id` = ? LIMIT 1");
        $dupStmt->execute([$methodCode, $cleanTrx]);
        if ($dupStmt->fetch()) {
            return ['success' => false, 'message' => 'This Transaction ID has already been submitted. Please verify your SMS receipt.'];
        }

        $proof = UploadService::storePaymentProof($proofFile, (int)$order['id'], $buyerId);
        if (!$proof['success']) {
            return ['success' => false, 'message' => (string)$proof['message']];
        }

        $now = date('Y-m-d H:i:s');
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "INSERT INTO `payments`
                 (`order_id`, `buyer_id`, `payment_method_id`, `method_code`, `amount`, `sender_number`,
                  `transaction_id`, `buyer_note`, `status`, `submitted_at`, `updated_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'submitted', ?, ?)"
            )->execute([
                (int)$order['id'],
                $buyerId,
                (int)$method['id'],
                $methodCode,
                (string)$order['total_amount'],
                $cleanSender,
                $cleanTrx,
                $buyerNote !== null && trim($buyerNote) !== '' ? mb_substr(trim($buyerNote), 0, 400) : null,
                $now,
                $now,
            ]);
            $paymentId = (int)$pdo->lastInsertId();

            if (!empty($proof['uploaded'])) {
                $pdo->prepare(
                    "INSERT INTO `payment_proofs`
                     (`payment_id`, `original_name`, `stored_name`, `private_path`, `mime_type`, `file_size_bytes`, `uploaded_at`)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                )->execute([
                    $paymentId,
                    $proof['original_name'],
                    $proof['stored_name'],
                    $proof['private_path'],
                    $proof['mime_type'],
                    $proof['file_size_bytes'],
                    $now,
                ]);
            }

            $oldStatus = (string)$order['status'];
            $pdo->prepare("UPDATE `orders` SET `status` = 'payment_submitted', `updated_at` = ? WHERE `id` = ?")
                ->execute([$now, (int)$order['id']]);

            $pdo->prepare(
                "INSERT INTO `order_status_history` (`order_id`, `old_status`, `new_status`, `note`, `changed_by`, `created_at`)
                 VALUES (?, ?, 'payment_submitted', ?, ?, ?)"
            )->execute([
                (int)$order['id'],
                $oldStatus,
                "{$method['name']} payment submitted (TrxID: {$cleanTrx})",
                $buyerId,
                $now,
            ]);

            NotificationService::send(
                $buyerId,
                'payment_submitted',
                "Payment submitted for {$order['order_number']}",
                "We received your {$method['name']} TrxID {$cleanTrx} (" . format_price((string)$order['total_amount']) . "). Downloads unlock automatically once verified.",
                'buyer/order-details.php?order=' . urlencode((string)$order['order_number']),
                'clock'
            );

            NotificationService::notifyAdmins(
                'payment_review',
                "Verify {$method['name']} payment ({$order['order_number']})",
                format_price((string)$order['total_amount']) . " from {$cleanSender} · TrxID {$cleanTrx}.",
                'admin/payments.php?status=submitted',
                'banknote'
            );

            $pdo->commit();
            return ['success' => true, 'payment_id' => $paymentId, 'order_number' => (string)$order['order_number']];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Submit payment error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not record payment submission. Please check the Transaction ID and retry.'];
        }
    }

    public static function markPaymentUnderVerification(int $paymentId, int $adminId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT p.*, o.order_number, o.status AS order_status FROM `payments` p INNER JOIN `orders` o ON o.id = p.order_id WHERE p.id = ? LIMIT 1");
        $stmt->execute([$paymentId]);
        $pay = $stmt->fetch();
        if (!$pay || $pay['status'] !== 'submitted') {
            return ['success' => false, 'message' => 'Only newly submitted payments can be marked under verification.'];
        }

        $now = date('Y-m-d H:i:s');
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE `payments` SET `status` = 'under_verification', `verified_by` = ?, `updated_at` = ? WHERE `id` = ?")
                ->execute([$adminId, $now, $paymentId]);
            $pdo->prepare("UPDATE `orders` SET `status` = 'under_verification', `updated_at` = ? WHERE `id` = ?")
                ->execute([$now, (int)$pay['order_id']]);
            $pdo->prepare(
                "INSERT INTO `order_status_history` (`order_id`, `old_status`, `new_status`, `note`, `changed_by`, `created_at`)
                 VALUES (?, ?, 'under_verification', 'Payment is currently being verified by administrator', ?, ?)"
            )->execute([(int)$pay['order_id'], (string)$pay['order_status'], $adminId, $now]);

            log_admin_action($adminId, 'payment_under_verification', 'payment', $paymentId, "Order {$pay['order_number']} TrxID {$pay['transaction_id']}");
            $pdo->commit();
            return ['success' => true, 'message' => "Order {$pay['order_number']} marked Under Verification."];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Could not update payment status.'];
        }
    }

    /**
     * Approve a manual payment atomically:
     * marks payment approved + order paid, generates licenses & enables downloads,
     * calculates & records commission + credits seller balances + immutable financial_ledger.
     */
    public static function approvePayment(int $paymentId, int $adminId, ?string $adminNote = null): array
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            "SELECT p.*, o.order_number, o.status AS order_status, o.buyer_id, pm.name AS method_name
             FROM `payments` p
             INNER JOIN `orders` o ON o.id = p.order_id
             INNER JOIN `payment_methods` pm ON pm.id = p.payment_method_id
             WHERE p.id = ? LIMIT 1"
        );
        $stmt->execute([$paymentId]);
        $pay = $stmt->fetch();

        if (!$pay) {
            return ['success' => false, 'message' => 'Payment record not found.'];
        }
        if ($pay['status'] === 'approved' || $pay['order_status'] === 'paid') {
            return ['success' => false, 'message' => 'This payment has already been approved.'];
        }
        if (!in_array((string)$pay['status'], ['submitted', 'under_verification'], true)) {
            return ['success' => false, 'message' => 'Only pending or under-verification payments can be approved.'];
        }

        $now = date('Y-m-d H:i:s');
        $orderId = (int)$pay['order_id'];

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE `payments`
                 SET `status` = 'approved', `admin_note` = ?, `verified_by` = ?, `verified_at` = ?, `updated_at` = ?
                 WHERE `id` = ?"
            )->execute([
                $adminNote !== null && trim($adminNote) !== '' ? mb_substr(trim($adminNote), 0, 300) : null,
                $adminId,
                $now,
                $now,
                $paymentId,
            ]);

            $pdo->prepare(
                "UPDATE `orders` SET `status` = 'paid', `paid_at` = ?, `updated_at` = ? WHERE `id` = ?"
            )->execute([$now, $now, $orderId]);

            $itemsStmt = $pdo->prepare("SELECT * FROM `order_items` WHERE `order_id` = ? ORDER BY `id` ASC");
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll();

            foreach ($items as $item) {
                $itemId = (int)$item['id'];
                $sellerId = (int)$item['seller_id'];
                $projectId = (int)$item['project_id'];
                $gross = (string)$item['final_price'];
                $rate = (string)$item['commission_rate'];
                $adminComm = (string)$item['commission_amount'];
                $sellerEarn = (string)$item['seller_earning'];
                $licKey = !empty($item['license_key']) ? (string)$item['license_key'] : License::generateKey((string)$item['license_type']);

                $pdo->prepare(
                    "UPDATE `order_items`
                     SET `license_key` = ?, `download_permitted` = 1, `status` = 'active', `updated_at` = ?
                     WHERE `id` = ?"
                )->execute([$licKey, $now, $itemId]);

                $pdo->prepare("UPDATE `projects` SET `sales_count` = `sales_count` + 1 WHERE `id` = ?")
                    ->execute([$projectId]);
                $pdo->prepare("UPDATE `user_profiles` SET `total_sales_count` = `total_sales_count` + 1 WHERE `user_id` = ?")
                    ->execute([$sellerId]);

                // Record commission split (prevent duplicates via uk_commissions_order_item)
                $pdo->prepare(
                    "INSERT INTO `commissions`
                     (`order_item_id`, `seller_id`, `gross_amount`, `commission_rate`, `admin_commission`, `seller_earning`, `status`, `created_at`)
                     VALUES (?, ?, ?, ?, ?, ?, 'credited', ?)"
                )->execute([$itemId, $sellerId, $gross, $rate, $adminComm, $sellerEarn, $now]);

                // Credit seller balance using exact fixed-point BDT math
                $pdo->prepare("INSERT OR IGNORE INTO `seller_balances` (`seller_id`) VALUES (?)")->execute([$sellerId]);
                $bStmt = $pdo->prepare("SELECT * FROM `seller_balances` WHERE `seller_id` = ? LIMIT 1");
                $bStmt->execute([$sellerId]);
                $bal = $bStmt->fetch();

                $newGross = bcadd((string)$bal['gross_sales'], $gross, 2);
                $newComm = bcadd((string)$bal['total_commission'], $adminComm, 2);
                $newEarn = bcadd((string)$bal['total_earnings'], $sellerEarn, 2);
                $newAvail = bcadd((string)$bal['available_balance'], $sellerEarn, 2);

                $pdo->prepare(
                    "UPDATE `seller_balances`
                     SET `gross_sales` = ?, `total_commission` = ?, `total_earnings` = ?, `available_balance` = ?, `updated_at` = ?
                     WHERE `seller_id` = ?"
                )->execute([$newGross, $newComm, $newEarn, $newAvail, $now, $sellerId]);

                // Immutable financial ledger entries: gross sale credit + commission note -> net seller balance
                $pdo->prepare(
                    "INSERT INTO `financial_ledger`
                     (`reference_code`, `user_id`, `order_id`, `entry_type`, `direction`, `amount`, `balance_after`, `description`, `created_by`, `created_at`)
                     VALUES (?, ?, ?, 'sale_credit', 'credit', ?, ?, ?, ?, ?)"
                )->execute([
                    generate_unique_code('LED') . '-' . $itemId,
                    $sellerId,
                    $orderId,
                    $sellerEarn,
                    $newAvail,
                    "Net sale credit for \"{$item['project_title']}\" (Order {$pay['order_number']}, gross " . format_price($gross) . ", fee {$rate}% = " . format_price($adminComm) . ")",
                    $adminId,
                    $now,
                ]);

                NotificationService::send(
                    $sellerId,
                    'new_sale',
                    'New verified sale: ' . format_price($sellerEarn) . ' credited!',
                    "\"{$item['project_title']}\" sold on Order {$pay['order_number']}. Net earning " . format_price($sellerEarn) . " added to your available balance.",
                    'seller/sales.php',
                    'wallet'
                );
            }

            $pdo->prepare(
                "INSERT INTO `order_status_history` (`order_id`, `old_status`, `new_status`, `note`, `changed_by`, `created_at`)
                 VALUES (?, ?, 'paid', ?, ?, ?)"
            )->execute([
                $orderId,
                (string)$pay['order_status'],
                "{$pay['method_name']} payment TrxID {$pay['transaction_id']} verified & approved. Licenses unlocked.",
                $adminId,
                $now,
            ]);

            NotificationService::send(
                (int)$pay['buyer_id'],
                'payment_approved',
                "Payment approved — Order {$pay['order_number']} ready!",
                "Your {$pay['method_name']} payment (" . format_price((string)$pay['amount']) . ") is verified. Download your files & license keys now.",
                'buyer/downloads.php',
                'check-circle'
            );

            log_admin_action($adminId, 'payment_approved', 'payment', $paymentId, "Approved {$pay['method_name']} TrxID {$pay['transaction_id']} on {$pay['order_number']} (" . format_price((string)$pay['amount']) . ")");

            $pdo->commit();
            return ['success' => true, 'message' => "Payment approved! Order {$pay['order_number']} is now Paid, downloads are unlocked, and seller earnings have been credited."];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Approve payment error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve payment: ' . $e->getMessage()];
        }
    }

    public static function rejectPayment(int $paymentId, int $adminId, string $reason): array
    {
        $reason = trim($reason);
        if (mb_strlen($reason, 'UTF-8') < 5) {
            return ['success' => false, 'message' => 'Please provide a clear rejection reason (at least 5 characters).'];
        }

        $pdo = db();
        $stmt = $pdo->prepare(
            "SELECT p.*, o.order_number, o.status AS order_status, o.buyer_id, pm.name AS method_name
             FROM `payments` p
             INNER JOIN `orders` o ON o.id = p.order_id
             INNER JOIN `payment_methods` pm ON pm.id = p.payment_method_id
             WHERE p.id = ? LIMIT 1"
        );
        $stmt->execute([$paymentId]);
        $pay = $stmt->fetch();

        if (!$pay || !in_array((string)$pay['status'], ['submitted', 'under_verification'], true)) {
            return ['success' => false, 'message' => 'Only pending or under-verification payments can be rejected.'];
        }

        $now = date('Y-m-d H:i:s');
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE `payments` SET `status` = 'rejected', `rejection_reason` = ?, `verified_by` = ?, `verified_at` = ?, `updated_at` = ?
                 WHERE `id` = ?"
            )->execute([mb_substr($reason, 0, 350), $adminId, $now, $now, $paymentId]);

            $pdo->prepare("UPDATE `orders` SET `status` = 'rejected', `updated_at` = ? WHERE `id` = ?")
                ->execute([$now, (int)$pay['order_id']]);

            $pdo->prepare(
                "INSERT INTO `order_status_history` (`order_id`, `old_status`, `new_status`, `note`, `changed_by`, `created_at`)
                 VALUES (?, ?, 'rejected', ?, ?, ?)"
            )->execute([
                (int)$pay['order_id'],
                (string)$pay['order_status'],
                "Payment rejected: {$reason}",
                $adminId,
                $now,
            ]);

            NotificationService::send(
                (int)$pay['buyer_id'],
                'payment_rejected',
                "Payment could not be verified ({$pay['order_number']})",
                "Reason: {$reason}. You may resubmit valid transaction details from your order page.",
                'payment.php?order=' . urlencode((string)$pay['order_number']),
                'alert-circle'
            );

            log_admin_action($adminId, 'payment_rejected', 'payment', $paymentId, "Rejected {$pay['order_number']}: {$reason}");

            $pdo->commit();
            return ['success' => true, 'message' => "Payment rejected and buyer notified for order {$pay['order_number']}."];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Could not reject payment.'];
        }
    }

    /**
     * Cancel an unpaid order OR refund a paid order (revoking downloads & reversing seller balance).
     */
    public static function cancelOrRefundOrder(int $orderId, int $adminId, string $reason): array
    {
        $reason = trim($reason) ?: 'Updated by marketplace administrator';
        $pdo = db();
        $oStmt = $pdo->prepare("SELECT * FROM `orders` WHERE `id` = ? LIMIT 1");
        $oStmt->execute([$orderId]);
        $order = $oStmt->fetch();
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found.'];
        }

        $oldStatus = (string)$order['status'];
        if (in_array($oldStatus, ['cancelled', 'refunded'], true)) {
            return ['success' => false, 'message' => 'This order is already ' . $oldStatus . '.'];
        }

        $now = date('Y-m-d H:i:s');
        $isPaid = ($oldStatus === 'paid');
        $newStatus = $isPaid ? 'refunded' : 'cancelled';

        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE `orders` SET `status` = ?, `updated_at` = ? WHERE `id` = ?")
                ->execute([$newStatus, $now, $orderId]);

            $itemsStmt = $pdo->prepare("SELECT * FROM `order_items` WHERE `order_id` = ?");
            $itemsStmt->execute([$orderId]);
            foreach ($itemsStmt->fetchAll() as $item) {
                $itemId = (int)$item['id'];
                $sellerId = (int)$item['seller_id'];
                $sellerEarn = (string)$item['seller_earning'];

                $pdo->prepare(
                    "UPDATE `order_items` SET `download_permitted` = 0, `status` = ?, `updated_at` = ? WHERE `id` = ?"
                )->execute([$isPaid ? 'refunded' : 'revoked', $now, $itemId]);

                if ($isPaid && bccomp($sellerEarn, '0.00', 2) > 0) {
                    $pdo->prepare("UPDATE `commissions` SET `status` = 'reversed' WHERE `order_item_id` = ?")->execute([$itemId]);
                    $pdo->prepare("UPDATE `projects` SET `sales_count` = CASE WHEN `sales_count` > 0 THEN `sales_count` - 1 ELSE 0 END WHERE `id` = ?")
                        ->execute([(int)$item['project_id']]);

                    $bStmt = $pdo->prepare("SELECT * FROM `seller_balances` WHERE `seller_id` = ? LIMIT 1");
                    $bStmt->execute([$sellerId]);
                    if ($bal = $bStmt->fetch()) {
                        $newAvail = bcsub((string)$bal['available_balance'], $sellerEarn, 2);
                        if (bccomp($newAvail, '0.00', 2) < 0) {
                            $newAvail = '0.00';
                        }
                        $newRef = bcadd((string)$bal['refunded_total'], $sellerEarn, 2);
                        $pdo->prepare(
                            "UPDATE `seller_balances` SET `available_balance` = ?, `refunded_total` = ?, `updated_at` = ? WHERE `seller_id` = ?"
                        )->execute([$newAvail, $newRef, $now, $sellerId]);

                        $pdo->prepare(
                            "INSERT INTO `financial_ledger`
                             (`reference_code`, `user_id`, `order_id`, `entry_type`, `direction`, `amount`, `balance_after`, `description`, `created_by`, `created_at`)
                             VALUES (?, ?, ?, 'refund_debit', 'debit', ?, ?, ?, ?, ?)"
                        )->execute([
                            generate_unique_code('REF') . '-' . $itemId,
                            $sellerId,
                            $orderId,
                            $sellerEarn,
                            $newAvail,
                            "Refund reversal for \"{$item['project_title']}\" (Order {$order['order_number']}): {$reason}",
                            $adminId,
                            $now,
                        ]);
                    }
                }
            }

            if ($isPaid) {
                $pdo->prepare("UPDATE `payments` SET `status` = 'refunded', `updated_at` = ? WHERE `order_id` = ? AND `status` = 'approved'")
                    ->execute([$now, $orderId]);
            }

            $pdo->prepare(
                "INSERT INTO `order_status_history` (`order_id`, `old_status`, `new_status`, `note`, `changed_by`, `created_at`)
                 VALUES (?, ?, ?, ?, ?, ?)"
            )->execute([$orderId, $oldStatus, $newStatus, $reason, $adminId, $now]);

            NotificationService::send(
                (int)$order['buyer_id'],
                'order_' . $newStatus,
                "Order {$order['order_number']} marked " . ucfirst($newStatus),
                $reason,
                'buyer/order-details.php?order=' . urlencode((string)$order['order_number']),
                'info'
            );

            log_admin_action($adminId, 'order_' . $newStatus, 'order', $orderId, "Order {$order['order_number']}: {$reason}");

            $pdo->commit();
            return ['success' => true, 'message' => "Order {$order['order_number']} has been marked " . ucfirst($newStatus) . "."];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Failed to update order: ' . $e->getMessage()];
        }
    }
}
