<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('cart.php'));
}

csrf_require();

$action = (string)($_POST['action'] ?? '');

if ($action === 'add') {
    $projectId = (int)($_POST['project_id'] ?? 0);
    $license = (string)($_POST['license_type'] ?? 'regular');
    $res = OrderService::addItem($projectId, $license);
    if (is_ajax_request()) {
        json_response($res, $res['success'] ? 200 : 400);
    }
    flash($res['success'] ? 'success' : 'warning', $res['message']);
    redirect(url('cart.php'));
}

if ($action === 'remove') {
    $projectId = (int)($_POST['project_id'] ?? 0);
    $res = OrderService::removeItem($projectId);
    if (is_ajax_request()) {
        json_response($res);
    }
    flash('success', $res['message']);
    redirect(url('cart.php'));
}

if ($action === 'apply_coupon') {
    $code = (string)($_POST['coupon_code'] ?? '');
    $res = OrderService::applyCouponCode($code);
    if (is_ajax_request()) {
        json_response($res, $res['success'] ? 200 : 400);
    }
    flash($res['success'] ? 'success' : 'error', $res['message']);
    redirect(url('cart.php'));
}

if ($action === 'remove_coupon') {
    OrderService::removeCoupon();
    if (is_ajax_request()) {
        json_response(['success' => true, 'message' => 'Coupon removed from cart.']);
    }
    flash('info', 'Coupon removed from cart.');
    redirect(url('cart.php'));
}

if ($action === 'claim_free') {
    $user = require_login();
    $projectId = (int)($_POST['project_id'] ?? 0);
    $res = OrderService::claimFreeProject((int)$user['id'], $projectId);
    if ($res['success']) {
        redirect(url('download.php?item=' . (int)$res['order_item_id']));
    }
    flash('error', $res['message'] ?? 'Unable to download free project.');
    redirect(url('free-projects.php'));
}

redirect(url('cart.php'));
