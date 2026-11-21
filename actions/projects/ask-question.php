<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('projects.php'));
}

$user = require_login();
csrf_require();

$projectId = (int)($_POST['project_id'] ?? 0);
$slug = trim((string)($_POST['project_slug'] ?? ''));
$question = trim((string)($_POST['question_text'] ?? ''));

$v = (new Validator(['question_text' => $question]))
    ->required('question_text', 'Question')
    ->minLength('question_text', 10, 'Question')
    ->maxLength('question_text', 1000, 'Question');

if ($v->fails()) {
    flash('error', $v->firstError());
    redirect(url('project.php?slug=' . urlencode($slug) . '#qa'));
}

$stmt = db()->prepare("SELECT `id`, `seller_id`, `title`, `slug` FROM `projects` WHERE `id` = ? AND `status` = 'approved' LIMIT 1");
$stmt->execute([$projectId]);
$project = $stmt->fetch();

if (!$project) {
    flash('error', 'Project could not be found.');
    redirect(url('projects.php'));
}

db()->prepare(
    "INSERT INTO `project_questions` (`project_id`, `user_id`, `question_text`, `status`)
     VALUES (?, ?, ?, 'visible')"
)->execute([$project['id'], $user['id'], $question]);

if ((int)$project['seller_id'] !== (int)$user['id']) {
    NotificationService::send(
        (int)$project['seller_id'],
        'project_question',
        'New question on ' . $project['title'],
        $user['full_name'] . ' asked a pre-sale question on your project.',
        url('project.php?slug=' . urlencode((string)$project['slug']) . '#qa'),
        'message'
    );
}

flash('success', 'Your question has been posted publicly for the seller.');
redirect(url('project.php?slug=' . urlencode((string)$project['slug']) . '#qa'));
