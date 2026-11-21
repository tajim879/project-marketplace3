<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');

$staticPages = [
    ['loc' => absolute_url('index.php'), 'priority' => '1.0', 'changefreq' => 'daily'],
    ['loc' => absolute_url('projects.php'), 'priority' => '0.9', 'changefreq' => 'daily'],
    ['loc' => absolute_url('categories.php'), 'priority' => '0.8', 'changefreq' => 'weekly'],
    ['loc' => absolute_url('featured-projects.php'), 'priority' => '0.8', 'changefreq' => 'daily'],
    ['loc' => absolute_url('free-projects.php'), 'priority' => '0.8', 'changefreq' => 'daily'],
    ['loc' => absolute_url('about.php'), 'priority' => '0.5', 'changefreq' => 'monthly'],
    ['loc' => absolute_url('contact.php'), 'priority' => '0.5', 'changefreq' => 'monthly'],
    ['loc' => absolute_url('faq.php'), 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => absolute_url('terms.php'), 'priority' => '0.4', 'changefreq' => 'yearly'],
    ['loc' => absolute_url('privacy.php'), 'priority' => '0.4', 'changefreq' => 'yearly'],
    ['loc' => absolute_url('refund-policy.php'), 'priority' => '0.4', 'changefreq' => 'yearly'],
    ['loc' => absolute_url('license-policy.php'), 'priority' => '0.4', 'changefreq' => 'yearly'],
];

$categories = db()->query("SELECT `slug`, `updated_at` FROM `categories` WHERE `status` = 'active' ORDER BY `sort_order` ASC")->fetchAll();
$projects = db()->query("SELECT `slug`, `updated_at` FROM `projects` WHERE `status` = 'approved' ORDER BY `published_at` DESC LIMIT 1000")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($staticPages as $sp): ?>
  <url>
    <loc><?= e($sp['loc']) ?></loc>
    <changefreq><?= e($sp['changefreq']) ?></changefreq>
    <priority><?= e($sp['priority']) ?></priority>
  </url>
<?php endforeach; ?>
<?php foreach ($categories as $cat): ?>
  <url>
    <loc><?= e(absolute_url('category.php?slug=' . urlencode($cat['slug']))) ?></loc>
    <lastmod><?= e(substr((string)$cat['updated_at'], 0, 10)) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
<?php endforeach; ?>
<?php foreach ($projects as $proj): ?>
  <url>
    <loc><?= e(absolute_url('project.php?slug=' . urlencode($proj['slug']))) ?></loc>
    <lastmod><?= e(substr((string)$proj['updated_at'], 0, 10)) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach; ?>
</urlset>
