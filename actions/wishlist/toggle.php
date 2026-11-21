<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = require_login();
csrf_require();

$projectId = (int)($_POST['project_id'] ?? 0);
if ($projectId <= 0) {
    json_response(['success' => false, 'message' => 'Invalid project.'], 422);
}

$pdo = db();
$checkProj = $pdo->prepare("SELECT `id`, `title` FROM `projects` WHERE `id` = ? AND `status` = 'approved' LIMIT 1");
$checkProj->execute([$projectId]);
$proj = $checkProj->fetch();
if (!$proj) {
    json_response(['success' => false, 'message' => 'Project not found.'], 404);
}

$uid = (int)$user['id'];
$existsStmt = $pdo->prepare("SELECT `id` FROM `wishlists` WHERE `user_id` = ? AND `project_id` = ? LIMIT 1");
$existsStmt->execute([$uid, $projectId]);
$existingId = $existsStmt->fetchColumn();

$pdo->beginTransaction();
try {
    if ($existingId) {
        $pdo->prepare("DELETE FROM `wishlists` WHERE `id` = ?")->execute([$existingId]);
        $inWishlist = false;
        $msg = 'Removed from your wishlist.';
    } else {
        $pdo->prepare("INSERT INTO `wishlists` (`user_id`, `project_id`) VALUES (?, ?)")->execute([$uid, $projectId]);
        $inWishlist = true;
        $msg = 'Saved to your wishlist.';
    }

    $pdo->prepare(
        "UPDATE `projects` SET `wishlist_count` = (SELECT COUNT(*) FROM `wishlists` WHERE `project_id` = ?) WHERE `id` = ?"
    )->execute([$projectId, $projectId]);

    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM `wishlists` WHERE `user_id` = ?");
    $cntStmt->execute([$uid]);
    $totalWish = (int)$cntStmt->fetchColumn();

    $pdo->commit();
    json_response([
        'success' => true,
        'in_wishlist' => $inWishlist,
        'wishlist_count' => $totalWish,
        'message' => $msg,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'message' => 'Could not update wishlist.'], 500);
}
