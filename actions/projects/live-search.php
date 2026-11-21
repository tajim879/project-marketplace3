<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q, 'UTF-8') < 2) {
    json_response(['success' => true, 'items' => []]);
}

$result = ProjectRepository::search(['q' => $q, 'sort' => 'best_selling'], 1, 6);
$items = [];
foreach ($result['items'] as $p) {
    $isFree = (int)$p['is_free'] === 1;
    $eff = effective_project_price($p);
    $items[] = [
        'id' => (int)$p['id'],
        'title' => (string)$p['title'],
        'category' => (string)$p['category_name'],
        'seller' => (string)$p['seller_display_name'],
        'thumbnail' => media_url((string)$p['thumbnail'], 'assets/images/placeholders/project-default.svg'),
        'url' => url('project.php?slug=' . urlencode((string)$p['slug'])),
        'is_free' => $isFree,
        'price_label' => $isFree ? 'Free' : format_price($eff),
    ];
}

json_response(['success' => true, 'items' => $items, 'total' => $result['total']]);
