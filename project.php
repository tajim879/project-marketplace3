<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    redirect(url('projects.php'));
}

$project = ProjectRepository::findApprovedBySlug($slug);
if (!$project) {
    flash('warning', 'The requested project was not found or is no longer listed.');
    redirect(url('projects.php'));
}

$pid = (int)$project['id'];
ProjectRepository::incrementViews($pid);

$viewer = current_user();
$ownedItem = $viewer ? user_has_purchased_project((int)$viewer['id'], $pid) : null;
$inWishlist = in_array($pid, user_wishlist_ids(), true);

$isFree = (int)$project['is_free'] === 1;
$regPrice = effective_project_price($project, 'regular');
$extPrice = !empty($project['extended_price']) && bccomp((string)$project['extended_price'], '0.00', 2) > 0
    ? number_format((float)$project['extended_price'], 2, '.', '')
    : bcmul($regPrice, '3.50', 2);
$discPct = discount_percentage($project);

// Build combined gallery (thumbnail + screenshots)
$gallery = [];
$mainThumb = media_url((string)$project['thumbnail'], 'assets/images/placeholders/project-default.svg');
$gallery[] = ['src' => $mainThumb, 'caption' => $project['title'] . ' — Primary Overview'];
foreach ($project['images'] as $img) {
    $gallery[] = [
        'src' => media_url((string)$img['image_path']),
        'caption' => (string)($img['caption'] ?: $project['title']),
    ];
}

$reviews = ProjectRepository::projectReviews($pid);
$breakdown = ProjectRepository::ratingBreakdown($pid);
$totalRev = count($reviews);
$questions = ProjectRepository::projectQuestions($pid);

$techSlugs = array_map(static fn(array $t): string => (string)$t['tech_slug'], $project['technologies']);
$relatedProjects = ProjectRepository::relatedProjects($pid, (int)$project['category_id'], 3);
$similarProjects = ProjectRepository::similarProjects($pid, (int)$project['category_id'], $techSlugs, 3);

$existingViewerReview = null;
if ($ownedItem) {
    $rSt = db()->prepare("SELECT * FROM `reviews` WHERE `order_item_id` = ? LIMIT 1");
    $rSt->execute([$ownedItem['id']]);
    $existingViewerReview = $rSt->fetch() ?: null;
}

$pageTitle = (string)$project['title'];
$pageDescription = (string)$project['short_description'];
$pageCanonical = absolute_url('project.php?slug=' . urlencode((string)$project['slug']));
$pageOgImage = absolute_url(ltrim((string)$project['thumbnail'], '/'));
$ogType = 'product';
$pageStyles = ['project-details.css'];
$pageScripts = ['gallery.js', 'dashboard.js'];

$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => $project['title'],
    'description' => $project['short_description'],
    'applicationCategory' => $project['category_name'],
    'softwareVersion' => $project['version'],
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'BDT',
        'price' => $isFree ? '0.00' : $regPrice,
        'availability' => 'https://schema.org/InStock',
    ],
];
if ($totalRev > 0) {
    $structuredData['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => (float)$project['average_rating'],
        'reviewCount' => $totalRev,
    ];
}

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <a href="<?= e(url('projects.php')) ?>">Projects</a>
            <?= icon('chevron-right', 14) ?>
            <a href="<?= e(url('category.php?slug=' . urlencode((string)$project['category_slug']))) ?>"><?= e($project['category_name']) ?></a>
            <?= icon('chevron-right', 14) ?>
            <span><?= e($project['title']) ?></span>
        </nav>

        <div style="display:grid;gap:12px;max-width:860px;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <a href="<?= e(url('category.php?slug=' . urlencode((string)$project['category_slug']))) ?>" class="pm-badge pm-badge-primary">
                    <?= icon((string)$project['category_icon'], 13) ?><span><?= e($project['category_name']) ?></span>
                </a>
                <?php if (!empty($project['is_featured'])): ?>
                    <span class="pm-badge pm-badge-warning"><?= icon('award', 13) ?><span>Featured Pick</span></span>
                <?php endif; ?>
                <span class="pm-badge pm-badge-muted">v<?= e($project['version']) ?></span>
                <?php if ($totalRev > 0): ?>
                    <span class="pm-project-rating" style="margin-left:4px;">
                        <?= render_stars((float)$project['average_rating'], 14) ?>
                        <span><?= number_format((float)$project['average_rating'], 1) ?></span>
                        <span class="count">(<?= $totalRev ?> verified <?= $totalRev === 1 ? 'review' : 'reviews' ?>)</span>
                    </span>
                <?php endif; ?>
                <span class="pm-muted" style="font-size:0.82rem;display:inline-flex;align-items:center;gap:5px;">
                    <?= icon('shopping-bag', 14) ?><?= number_format((int)$project['sales_count']) ?> sales
                </span>
            </div>

            <h1 class="pm-h1"><?= e($project['title']) ?></h1>
            <p class="pm-lead" style="font-size:1rem;"><?= e($project['short_description']) ?></p>
        </div>
    </div>
</section>

<section class="pm-section-tight">
    <div class="pm-container">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <div class="pm-detail-layout">
            <!-- LEFT PRIMARY CONTENT -->
            <div class="pm-detail-main">
                <!-- Interactive Gallery Card -->
                <div class="pm-card pm-gallery-card">
                    <div class="pm-gallery-stage">
                        <img id="pmGalleryMain" src="<?= e($gallery[0]['src']) ?>" alt="<?= e($gallery[0]['caption']) ?>">
                        <button type="button" id="pmGalleryZoomBtn" class="pm-gallery-zoom-btn">
                            <?= icon('maximize', 15) ?><span>Inspect Fullscreen</span>
                        </button>
                    </div>

                    <?php if (count($gallery) > 1): ?>
                        <div class="pm-gallery-thumbs" role="list" aria-label="Project screenshots">
                            <?php foreach ($gallery as $idx => $slide): ?>
                                <button type="button"
                                        class="pm-gallery-thumb <?= $idx === 0 ? 'is-active' : '' ?>"
                                        data-gallery-src="<?= e($slide['src']) ?>"
                                        data-gallery-caption="<?= e($slide['caption']) ?>"
                                        aria-label="View screenshot <?= $idx + 1 ?>">
                                    <img src="<?= e($slide['src']) ?>" alt="" loading="lazy">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="pm-preview-actions">
                        <div class="pm-btn-row">
                            <?php if (!empty($project['demo_url'])): ?>
                                <a href="<?= e((string)$project['demo_url']) ?>" target="_blank" rel="noopener noreferrer" class="pm-btn pm-btn-sky pm-btn-sm">
                                    <?= icon('external-link', 15) ?><span>Live Demo Preview</span>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($project['video_preview_url'])): ?>
                                <a href="<?= e((string)$project['video_preview_url']) ?>" target="_blank" rel="noopener noreferrer" class="pm-btn pm-btn-secondary pm-btn-sm">
                                    <?= icon('play', 15) ?><span>Video Walkthrough</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="pm-btn pm-btn-ghost pm-btn-sm" data-copy="<?= e($pageCanonical) ?>">
                            <?= icon('copy', 15) ?><span>Copy Link</span>
                        </button>
                    </div>
                </div>

                <!-- Content Tabs: Overview, Changelog, Reviews, Q&A -->
                <div class="pm-tabs-bar" role="tablist" data-tabs>
                    <button type="button" class="pm-tab-btn is-active" role="tab" aria-selected="true" data-tab-target="tabOverview">
                        <?= icon('file-text', 16) ?><span>Overview &amp; Features</span>
                    </button>
                    <button type="button" class="pm-tab-btn" role="tab" aria-selected="false" data-tab-target="tabChangelog">
                        <?= icon('layers', 16) ?><span>Changelog (<?= count($project['versions']) ?>)</span>
                    </button>
                    <button type="button" class="pm-tab-btn" role="tab" aria-selected="false" data-tab-target="tabReviews">
                        <?= icon('star', 16) ?><span>Reviews (<?= $totalRev ?>)</span>
                    </button>
                    <button type="button" class="pm-tab-btn" role="tab" aria-selected="false" data-tab-target="tabQa">
                        <?= icon('message', 16) ?><span>Q&amp;A (<?= count($questions) ?>)</span>
                    </button>
                </div>

                <!-- TAB 1: Overview, Features & Technology Stack -->
                <div id="tabOverview" class="pm-tab-panel is-active" data-tab-panel>
                    <div class="pm-card pm-card-pad" style="display:grid;gap:26px;">
                        <div>
                            <h2 class="pm-h3" style="margin-bottom:12px;">Project Architecture &amp; Description</h2>
                            <div class="pm-prose">
                                <?php foreach (preg_split('/\R{2,}/', trim((string)$project['description'])) ?: [] as $para): ?>
                                    <p><?= nl2br(e(trim($para))) ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php if (!empty($project['features'])): ?>
                            <div>
                                <h2 class="pm-h3">Included Key Features</h2>
                                <ul class="pm-feature-grid">
                                    <?php foreach ($project['features'] as $feat): ?>
                                        <li class="pm-feature-item">
                                            <?= icon('check-circle', 17) ?>
                                            <span><?= e($feat) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($project['technologies'])): ?>
                            <div>
                                <h2 class="pm-h3" style="margin-bottom:12px;">Technologies &amp; Frameworks Used</h2>
                                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                    <?php foreach ($project['technologies'] as $tech): ?>
                                        <a href="<?= e(url('projects.php?tech=' . urlencode((string)$tech['tech_slug']))) ?>" class="pm-chip">
                                            <?= icon('code', 13) ?><span><?= e($tech['tech_name']) ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TAB 2: Version Changelog -->
                <div id="tabChangelog" class="pm-tab-panel" data-tab-panel>
                    <div class="pm-card pm-card-pad" style="display:grid;gap:16px;">
                        <h2 class="pm-h3">Release History &amp; Version Notes</h2>
                        <?php if (empty($project['versions'])): ?>
                            <p class="pm-muted">Initial verified release v<?= e($project['version']) ?> published on <?= e(format_date((string)$project['published_at'])) ?>.</p>
                        <?php else: ?>
                            <?php foreach ($project['versions'] as $ver): ?>
                                <div class="pm-review-item">
                                    <div class="pm-review-head">
                                        <span class="pm-badge pm-badge-primary">v<?= e($ver['version']) ?></span>
                                        <small class="pm-muted"><?= e(format_date((string)$ver['released_at'])) ?></small>
                                    </div>
                                    <p class="pm-muted" style="color:var(--text-secondary);"><?= nl2br(e((string)$ver['changelog'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TAB 3: Verified Reviews & Ratings -->
                <div id="tabReviews" class="pm-tab-panel" data-tab-panel>
                    <div id="reviews" class="pm-card pm-card-pad">
                        <h2 class="pm-h3" style="margin-bottom:16px;">Verified Buyer Ratings &amp; Reviews</h2>

                        <div class="pm-rating-overview">
                            <div class="pm-rating-score">
                                <strong><?= number_format((float)$project['average_rating'], 1) ?></strong>
                                <?= render_stars((float)$project['average_rating'], 17) ?>
                                <span class="pm-muted" style="font-size:0.8rem;"><?= $totalRev ?> verified <?= $totalRev === 1 ? 'review' : 'reviews' ?></span>
                            </div>
                            <div class="pm-rating-bars">
                                <?php foreach ([5, 4, 3, 2, 1] as $star):
                                    $c = $breakdown[$star] ?? 0;
                                    $pct = $totalRev > 0 ? round(($c / $totalRev) * 100) : 0; ?>
                                    <div class="pm-bar-row">
                                        <span><?= $star ?> star</span>
                                        <div class="pm-bar-track"><div class="pm-bar-fill" style="width:<?= $pct ?>%;"></div></div>
                                        <span style="text-align:right;"><?= $c ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php if ($ownedItem): ?>
                            <form method="post" action="<?= e(url('actions/reviews/save.php')) ?>" class="pm-review-item" style="margin-bottom:20px;display:grid;gap:12px;" data-submit-lock>
                                <?= csrf_field() ?>
                                <input type="hidden" name="order_item_id" value="<?= (int)$ownedItem['id'] ?>">
                                <input type="hidden" name="return_to" value="project">
                                <strong><?= $existingViewerReview ? 'Update Your Verified Review' : 'Write a Verified Buyer Review' ?></strong>
                                <div class="pm-star-picker" role="radiogroup" aria-label="Select rating">
                                    <?php for ($s = 5; $s >= 1; $s--):
                                        $checked = (int)($existingViewerReview['rating'] ?? 5) === $s; ?>
                                        <input type="radio" id="revStar<?= $s ?>" name="rating" value="<?= $s ?>" <?= $checked ? 'checked' : '' ?>>
                                        <label for="revStar<?= $s ?>" title="<?= $s ?> stars"><?= icon('star', 22) ?></label>
                                    <?php endfor; ?>
                                </div>
                                <input type="text" name="title" class="pm-input" placeholder="Short headline (e.g. Clean code and easy installation)" value="<?= e($existingViewerReview['title'] ?? '') ?>">
                                <textarea name="comment" class="pm-textarea" placeholder="Share your experience with code quality, documentation, and support..." required><?= e($existingViewerReview['comment'] ?? '') ?></textarea>
                                <div><button type="submit" class="pm-btn pm-btn-primary pm-btn-sm"><?= icon('check', 15) ?><span><?= $existingViewerReview ? 'Update Review' : 'Publish Review' ?></span></button></div>
                            </form>
                        <?php endif; ?>

                        <?php if (empty($reviews)): ?>
                            <p class="pm-muted" style="text-align:center;padding:24px;">No verified reviews yet. Only buyers who have completed a purchase can rate this project.</p>
                        <?php else: ?>
                            <div class="pm-review-list">
                                <?php foreach ($reviews as $rev): ?>
                                    <article class="pm-review-item">
                                        <div class="pm-review-head">
                                            <div style="display:flex;align-items:center;gap:10px;">
                                                <span class="pm-avatar"><?= e(user_initials((string)$rev['buyer_name'])) ?></span>
                                                <div>
                                                    <strong style="font-size:0.88rem;"><?= e($rev['buyer_name']) ?></strong>
                                                    <span class="pm-badge pm-badge-success" style="margin-left:6px;"><?= icon('check', 11) ?><span>Verified Buyer</span></span>
                                                    <small class="pm-muted" style="display:block;font-size:0.75rem;"><?= e(format_date((string)$rev['created_at'])) ?> · <?= e(license_label((string)$rev['license_type'])) ?></small>
                                                </div>
                                            </div>
                                            <div style="display:flex;align-items:center;gap:10px;">
                                                <?= render_stars((float)$rev['rating'], 14) ?>
                                                <?php if ($viewer && (int)$viewer['id'] !== (int)$rev['buyer_id']): ?>
                                                    <button type="button" class="pm-btn pm-btn-ghost pm-btn-sm" data-report-review="<?= (int)$rev['id'] ?>" title="Report review" aria-label="Report review">
                                                        <?= icon('flag', 14) ?>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($rev['title'])): ?>
                                            <strong style="display:block;margin-bottom:5px;font-size:0.92rem;"><?= e($rev['title']) ?></strong>
                                        <?php endif; ?>
                                        <p style="color:var(--text-secondary);font-size:0.89rem;line-height:1.65;"><?= nl2br(e((string)$rev['comment'])) ?></p>

                                        <?php if (!empty($rev['seller_reply'])): ?>
                                            <div class="pm-seller-reply">
                                                <strong style="color:var(--primary);display:inline-flex;align-items:center;gap:5px;margin-bottom:4px;">
                                                    <?= icon('store', 14) ?><span>Author Response</span>
                                                </strong>
                                                <p><?= nl2br(e((string)$rev['seller_reply'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TAB 4: Questions & Answers -->
                <div id="tabQa" class="pm-tab-panel" data-tab-panel>
                    <div id="qa" class="pm-card pm-card-pad" style="display:grid;gap:18px;">
                        <h2 class="pm-h3">Pre-Sale Questions &amp; Answers</h2>

                        <?php if ($viewer): ?>
                            <form method="post" action="<?= e(url('actions/projects/ask-question.php')) ?>" style="display:grid;gap:10px;" data-submit-lock>
                                <?= csrf_field() ?>
                                <input type="hidden" name="project_id" value="<?= $pid ?>">
                                <input type="hidden" name="project_slug" value="<?= e($project['slug']) ?>">
                                <label class="pm-label" for="qaInput">Ask the author a technical question</label>
                                <textarea id="qaInput" name="question_text" class="pm-textarea" style="min-height:88px;" placeholder="Ask about PHP/MySQL requirements, hosting compatibility, customization, or included files..." required></textarea>
                                <div>
                                    <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm">
                                        <?= icon('send', 14) ?><span>Post Question</span>
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="pm-alert pm-alert-info" style="margin:0;">
                                <?= icon('info', 18) ?>
                                <span><a class="pm-link" href="<?= e(url('login.php')) ?>">Sign in</a> to ask the seller a technical question about this project.</span>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($questions)): ?>
                            <p class="pm-muted">No questions asked yet. Be the first to ask the author.</p>
                        <?php else: ?>
                            <div class="pm-qa-list">
                                <?php foreach ($questions as $qa): ?>
                                    <div class="pm-qa-item">
                                        <div class="pm-review-head">
                                            <strong style="font-size:0.86rem;"><?= e($qa['asker_name']) ?> <span class="pm-muted" style="font-weight:400;">asked</span></strong>
                                            <small class="pm-muted"><?= e(time_ago((string)$qa['created_at'])) ?></small>
                                        </div>
                                        <p style="color:var(--text-primary);font-size:0.9rem;"><?= nl2br(e((string)$qa['question_text'])) ?></p>
                                        <?php if (!empty($qa['answer_text'])): ?>
                                            <div class="pm-seller-reply">
                                                <strong style="color:var(--primary);display:inline-flex;align-items:center;gap:5px;margin-bottom:4px;">
                                                    <?= icon('badge-check', 14) ?><span><?= e($qa['answerer_name'] ?: $project['seller_display_name']) ?> · Author</span>
                                                </strong>
                                                <p><?= nl2br(e((string)$qa['answer_text'])) ?></p>
                                            </div>
                                        <?php else: ?>
                                            <small class="pm-muted" style="display:inline-flex;align-items:center;gap:5px;margin-top:8px;">
                                                <?= icon('clock', 13) ?>Awaiting author reply
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT STICKY SIDEBAR (PURCHASE, SELLER & TECHNICAL SPECS) -->
            <aside class="pm-detail-sidebar">
                <div class="pm-card pm-purchase-card">
                    <?php if ($ownedItem): ?>
                        <div class="pm-alert pm-alert-success" style="margin:0;">
                            <?= icon('check-circle', 18) ?>
                            <span>You own a verified license (Order <?= e($ownedItem['order_number']) ?>).</span>
                        </div>
                        <a href="<?= e(url('download.php?item=' . (int)$ownedItem['id'])) ?>" class="pm-btn pm-btn-success pm-btn-lg pm-btn-block">
                            <?= icon('download', 19) ?><span>Download ZIP (v<?= e($project['version']) ?>)</span>
                        </a>
                        <a href="<?= e(url('buyer/downloads.php')) ?>" class="pm-btn pm-btn-secondary pm-btn-block">
                            <?= icon('key', 17) ?><span>View License &amp; Updates</span>
                        </a>
                    <?php elseif ($isFree): ?>
                        <div style="display:flex;align-items:baseline;justify-content:space-between;">
                            <div>
                                <span class="pm-badge pm-badge-success"><?= e(license_label((string)$project['license_type'])) ?></span>
                                <div class="pm-price-current is-free" style="font-size:2rem;margin-top:6px;">Free</div>
                            </div>
                            <span class="pm-muted" style="font-size:0.82rem;"><?= e($project['file_size_label']) ?> ZIP</span>
                        </div>

                        <form method="post" action="<?= e(url('actions/orders/cart.php')) ?>" data-submit-lock>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="claim_free">
                            <input type="hidden" name="project_id" value="<?= $pid ?>">
                            <button type="submit" class="pm-btn pm-btn-success pm-btn-lg pm-btn-block">
                                <?= icon('download', 19) ?><span>Free Secure Download</span>
                            </button>
                        </form>

                        <button type="button"
                                class="pm-btn pm-btn-secondary pm-btn-block <?= $inWishlist ? 'is-active' : '' ?>"
                                data-wishlist-toggle="<?= $pid ?>">
                            <?= icon('heart', 17) ?>
                            <span data-wishlist-label><?= $inWishlist ? 'Saved in Wishlist' : 'Add to Wishlist' ?></span>
                        </button>
                    <?php else: ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                            <div class="pm-price-group">
                                <span id="pmSelectedPriceLabel" class="pm-price-current" style="font-size:1.85rem;"><?= e(format_price($regPrice)) ?></span>
                                <?php if ($discPct > 0): ?>
                                    <span class="pm-price-old" style="font-size:0.95rem;"><?= e(format_price((string)$project['price'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($discPct > 0): ?>
                                <span class="pm-badge pm-badge-danger">Save <?= $discPct ?>%</span>
                            <?php endif; ?>
                        </div>

                        <div class="pm-license-selector" role="radiogroup" aria-label="Choose license">
                            <label class="pm-license-option">
                                <input type="radio" name="selected_license" value="regular" data-formatted="<?= e(format_price($regPrice)) ?>" checked>
                                <span class="pm-license-meta">
                                    <strong>Regular License</strong>
                                    <small>Single client or personal project, <?= (int)$project['support_months'] ?> months support &amp; updates.</small>
                                </span>
                                <span class="pm-license-price"><?= e(format_price($regPrice)) ?></span>
                            </label>
                            <label class="pm-license-option">
                                <input type="radio" name="selected_license" value="extended" data-formatted="<?= e(format_price($extPrice)) ?>">
                                <span class="pm-license-meta">
                                    <strong>Extended License</strong>
                                    <small>Commercial SaaS &amp; paid end-user product rights included.</small>
                                </span>
                                <span class="pm-license-price"><?= e(format_price($extPrice)) ?></span>
                            </label>
                        </div>

                        <div style="display:grid;gap:10px;">
                            <button type="button"
                                    class="pm-btn pm-btn-success pm-btn-lg pm-btn-block"
                                    data-add-cart="<?= $pid ?>"
                                    data-license="regular"
                                    data-dynamic-license="1"
                                    data-buy-now="1">
                                <?= icon('zap', 18) ?><span>Buy Now</span>
                            </button>
                            <div style="display:grid;grid-template-columns:1fr auto;gap:10px;">
                                <button type="button"
                                        class="pm-btn pm-btn-primary pm-btn-block"
                                        data-add-cart="<?= $pid ?>"
                                        data-license="regular"
                                        data-dynamic-license="1">
                                    <?= icon('shopping-bag', 17) ?><span>Add to Cart</span>
                                </button>
                                <button type="button"
                                        class="pm-btn pm-btn-secondary <?= $inWishlist ? 'is-active' : '' ?>"
                                        data-wishlist-toggle="<?= $pid ?>"
                                        aria-label="Toggle wishlist"
                                        style="padding-inline:14px;<?= $inWishlist ? 'color:var(--danger);' : '' ?>">
                                    <?= icon('heart', 18) ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <ul class="pm-guarantees">
                        <li><?= icon('shield', 15) ?><span>ZIP archive security inspected by CodeVault</span></li>
                        <li><?= icon('download', 15) ?><span>Full source code &amp; <?= $project['documentation_included'] ? 'setup documentation' : 'readme' ?></span></li>
                        <li><?= icon('banknote', 15) ?><span>Pay securely with bKash, Nagad, or Rocket</span></li>
                        <li><?= icon('refresh', 15) ?><span>Free version updates in your Downloads Vault</span></li>
                    </ul>
                </div>

                <!-- Seller Profile Card -->
                <div class="pm-card pm-card-pad" style="display:grid;gap:10px;">
                    <span style="font-size:0.79rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:6px;">
                        <?= icon('shield-check', 16) ?><span>Verified BDT Checkout</span>
                    </span>
                    <div class="pm-pay-strip">
                        <?= payment_brand_badge('bkash', 'sm') ?>
                        <?= payment_brand_badge('nagad', 'sm') ?>
                        <?= payment_brand_badge('rocket', 'sm') ?>
                    </div>
                </div>

                <div class="pm-card pm-card-pad" style="display:grid;gap:15px;">
                    <div class="pm-seller-head">
                        <span class="pm-avatar pm-avatar-lg"><?= e(user_initials((string)$project['seller_display_name'])) ?></span>
                        <div class="pm-seller-title">
                            <h3>
                                <span><?= e($project['seller_display_name']) ?></span>
                                <?php if (!empty($project['is_verified_seller'])): ?>
                                    <span class="pm-verified" title="Verified Seller"><?= icon('badge-check', 16, '', 'Verified Seller') ?></span>
                                <?php endif; ?>
                            </h3>
                            <p><?= e($project['seller_headline'] ?: ('@' . $project['seller_username'])) ?></p>
                        </div>
                    </div>

                    <div class="pm-seller-metrics">
                        <div><strong><?= number_format((float)$project['seller_avg_rating'], 1) ?></strong><span>Rating</span></div>
                        <div><strong><?= number_format((int)$project['seller_total_sales']) ?></strong><span>Sales</span></div>
                        <div><strong><?= (int)$project['seller_projects_count'] ?></strong><span>Projects</span></div>
                    </div>

                    <a href="<?= e(url('seller-profile.php?username=' . urlencode((string)$project['seller_username']))) ?>" class="pm-btn pm-btn-secondary pm-btn-sm pm-btn-block">
                        <?= icon('store', 15) ?><span>View Seller Profile</span>
                    </a>
                </div>

                <!-- Technical Metadata Card -->
                <div class="pm-card pm-card-pad">
                    <h3 class="pm-h4" style="margin-bottom:14px;">Project Specifications</h3>
                    <dl class="pm-spec-list">
                        <div class="pm-spec-row"><dt><?= icon('tag', 14) ?>Version</dt><dd>v<?= e($project['version']) ?></dd></div>
                        <div class="pm-spec-row"><dt><?= icon('folder', 14) ?>File Size</dt><dd><?= e($project['file_size_label']) ?></dd></div>
                        <div class="pm-spec-row"><dt><?= icon('cpu', 14) ?>Compatibility</dt><dd><?= e($project['compatibility'] ?: 'Standard Web/Mobile') ?></dd></div>
                        <div class="pm-spec-row"><dt><?= icon('calendar', 14) ?>Last Updated</dt><dd><?= e(format_date((string)$project['last_updated_at'])) ?></dd></div>
                        <div class="pm-spec-row"><dt><?= icon('shield', 14) ?>License</dt><dd><?= e(license_label((string)$project['license_type'])) ?></dd></div>
                        <div class="pm-spec-row"><dt><?= icon('file-code', 14) ?>Slug</dt><dd><code style="font-size:0.75rem;"><?= e($project['slug']) ?></code></dd></div>
                        <div class="pm-spec-row"><dt><?= icon('eye', 14) ?>Views</dt><dd><?= number_format((int)$project['views_count'] + 1) ?></dd></div>
                    </dl>
                </div>
            </aside>
        </div>

        <!-- RELATED PROJECTS -->
        <?php if (!empty($relatedProjects)): ?>
            <div style="margin-top:64px;">
                <div class="pm-section-head">
                    <div class="pm-section-head-copy">
                        <h2 class="pm-h2">Related in <?= e($project['category_name']) ?></h2>
                        <p class="pm-muted">More vetted releases from the same category.</p>
                    </div>
                    <a href="<?= e(url('category.php?slug=' . urlencode((string)$project['category_slug']))) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">
                        <span>View Category</span><?= icon('arrow-right', 15) ?>
                    </a>
                </div>
                <div class="pm-project-grid">
                    <?php foreach ($relatedProjects as $relItem):
                        $project = $relItem;
                        require INCLUDES_PATH . '/components/project-card.php';
                    endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- SIMILAR TECHNOLOGY PROJECTS -->
        <?php if (!empty($similarProjects)): ?>
            <div style="margin-top:52px;">
                <div class="pm-section-head">
                    <div class="pm-section-head-copy">
                        <h2 class="pm-h2">Similar Projects You May Like</h2>
                        <p class="pm-muted">Projects sharing compatible architectures, tooling, and quality ratings.</p>
                    </div>
                </div>
                <div class="pm-project-grid">
                    <?php foreach ($similarProjects as $simItem):
                        $project = $simItem;
                        require INCLUDES_PATH . '/components/project-card.php';
                    endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Fullscreen Screenshot Lightbox Modal -->
<div class="pm-modal" id="pmLightboxModal" aria-hidden="true" role="dialog" aria-label="Screenshot viewer">
    <div class="pm-modal-dialog pm-lightbox-dialog">
        <img id="pmLightboxImage" src="<?= e($gallery[0]['src']) ?>" alt="">
        <div class="pm-lightbox-bar">
            <span id="pmLightboxCaption" class="pm-muted" style="font-size:0.84rem;"><?= e($gallery[0]['caption']) ?></span>
            <div class="pm-btn-row">
                <?php if (count($gallery) > 1): ?>
                    <button type="button" id="pmLightboxPrev" class="pm-btn pm-btn-secondary pm-btn-sm" aria-label="Previous screenshot"><?= icon('chevron-left', 16) ?></button>
                    <button type="button" id="pmLightboxNext" class="pm-btn pm-btn-secondary pm-btn-sm" aria-label="Next screenshot"><?= icon('chevron-right', 16) ?></button>
                <?php endif; ?>
                <button type="button" class="pm-btn pm-btn-primary pm-btn-sm" data-modal-close><?= icon('close', 15) ?><span>Close</span></button>
            </div>
        </div>
    </div>
</div>

<!-- Report Review Modal -->
<div class="pm-modal" id="pmReportReviewModal" aria-hidden="true" role="dialog" aria-label="Report review">
    <div class="pm-modal-dialog">
        <div class="pm-modal-head">
            <strong class="pm-h3">Report Review</strong>
            <button type="button" class="pm-icon-btn" style="width:34px;height:34px;" data-modal-close aria-label="Close"><?= icon('close', 16) ?></button>
        </div>
        <form method="post" action="<?= e(url('actions/reviews/report.php')) ?>" style="display:grid;gap:14px;">
            <?= csrf_field() ?>
            <input type="hidden" id="pmReportReviewId" name="review_id" value="">
            <input type="hidden" name="project_slug" value="<?= e($slug) ?>">
            <div class="pm-field">
                <label class="pm-label" for="repReason">Why are you flagging this review?</label>
                <textarea id="repReason" name="reason" class="pm-textarea" placeholder="Spam, abusive content, or unrelated technical claim..." required></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" data-modal-close>Cancel</button>
                <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
