<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

csrf_require();

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Please enter a valid email address.'], 422);
}

$pdo = db();
$stmt = $pdo->prepare("SELECT `id`, `status` FROM `newsletter_subscribers` WHERE `email` = ? LIMIT 1");
$stmt->execute([$email]);
$row = $stmt->fetch();

if ($row && $row['status'] === 'subscribed') {
    json_response(['success' => true, 'message' => 'You are already subscribed to the weekly release digest.']);
}

$pdo->prepare(
    "INSERT INTO `newsletter_subscribers` (`email`, `ip_address`, `status`)
     VALUES (?, ?, 'subscribed')
     ON CONFLICT(`email`) DO UPDATE SET `status` = 'subscribed', `ip_address` = excluded.`ip_address`"
)->execute([$email, client_ip()]);

json_response(['success' => true, 'message' => 'Subscribed! Watch your inbox for weekly curated releases.']);
