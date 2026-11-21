<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$viewer = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $allowedCats = ['general', 'order', 'payment', 'download', 'technical', 'seller', 'refund'];
    $v = (new Validator($_POST))
        ->required('name', 'Your name')->minLength('name', 2, 'Your name')
        ->required('email', 'Email address')->email('email')
        ->required('category', 'Topic')->inList('category', $allowedCats, 'Topic')
        ->required('subject', 'Subject')->minLength('subject', 5, 'Subject')
        ->required('message', 'Message')->minLength('message', 15, 'Message')->maxLength('message', 4000, 'Message');

    if ($v->fails()) {
        set_old_input($_POST);
        flash('error', $v->firstError());
        redirect(url('contact.php'));
    }

    $pdo = db();
    $ticketNo = generate_unique_code('TKT');
    $uid = $viewer ? (int)$viewer['id'] : null;

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "INSERT INTO `support_tickets` (`ticket_number`, `user_id`, `guest_name`, `guest_email`, `category`, `subject`, `status`, `last_reply_at`)
             VALUES (?, ?, ?, ?, ?, ?, 'open', ?)"
        )->execute([
            $ticketNo,
            $uid,
            trim((string)$_POST['name']),
            strtolower(trim((string)$_POST['email'])),
            $_POST['category'],
            trim((string)$_POST['subject']),
            date('Y-m-d H:i:s'),
        ]);
        $ticketId = (int)$pdo->lastInsertId();

        $pdo->prepare(
            "INSERT INTO `ticket_messages` (`ticket_id`, `sender_id`, `sender_role`, `message`)
             VALUES (?, ?, 'user', ?)"
        )->execute([$ticketId, $uid, trim((string)$_POST['message'])]);

        NotificationService::notifyAdmins(
            'support_ticket',
            "New inquiry {$ticketNo}",
            trim((string)$_POST['name']) . ' submitted a contact ticket.',
            'admin/support/view.php?id=' . $ticketId,
            'mail'
        );

        $pdo->commit();
        flash('success', "Message received! Your reference ticket number is {$ticketNo}.");
        if ($viewer) {
            redirect(url('buyer/support.php?ticket=' . urlencode($ticketNo)));
        }
    } catch (Throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Unable to send your message right now. Please try again.');
    }
    redirect(url('contact.php'));
}

$pageTitle = 'Contact Support';
$pageDescription = 'Reach the CodeVault support team for order verification, download help, seller onboarding, or partnership inquiries.';

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <span>Contact Us</span>
        </nav>
        <h1 class="pm-h1">Contact Marketplace Support</h1>
        <p class="pm-muted" style="margin-top:6px;">Every inquiry generates an audited support ticket so nothing gets lost.</p>
    </div>
</section>

<section class="pm-section">
    <div class="pm-container">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <div class="pm-grid-2 pm-contact-grid">
            <div style="display:grid;gap:16px;">
                <div class="pm-card pm-card-pad" style="display:flex;gap:14px;align-items:flex-start;">
                    <span class="pm-stat-icon"><?= icon('mail', 20) ?></span>
                    <div>
                        <h3 class="pm-h4">Email Desk</h3>
                        <p class="pm-muted" style="margin-top:3px;"><?= e(site_setting('contact_email', 'support@codevault.market')) ?></p>
                        <small class="pm-muted">Average response within 4 business hours</small>
                    </div>
                </div>
                <div class="pm-card pm-card-pad" style="display:flex;gap:14px;align-items:flex-start;">
                    <span class="pm-stat-icon is-success"><?= icon('phone', 20) ?></span>
                    <div>
                        <h3 class="pm-h4">Support Hotline</h3>
                        <p class="pm-muted" style="margin-top:3px;"><?= e(site_setting('contact_phone', '+880 1711-004488')) ?></p>
                        <small class="pm-muted">Sat – Thu, 10:00 AM – 8:00 PM BST</small>
                    </div>
                </div>
                <div class="pm-card pm-card-pad" style="display:flex;gap:14px;align-items:flex-start;">
                    <span class="pm-stat-icon"><?= icon('map-pin', 20) ?></span>
                    <div>
                        <h3 class="pm-h4">Headquarters</h3>
                        <p class="pm-muted" style="margin-top:3px;"><?= e(site_setting('contact_address', 'Chattogram, Bangladesh')) ?></p>
                    </div>
                </div>
            </div>

            <form method="post" action="<?= e(url('contact.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:16px;padding:30px;" data-submit-lock>
                <?= csrf_field() ?>
                <h2 class="pm-h3">Send a Message</h2>

                <div class="pm-form-grid-2">
                    <div class="pm-field">
                        <label class="pm-label" for="cName">Your Name</label>
                        <input id="cName" name="name" class="pm-input" value="<?= e($viewer['full_name'] ?? old('name')) ?>" required>
                    </div>
                    <div class="pm-field">
                        <label class="pm-label" for="cEmail">Email Address</label>
                        <input type="email" id="cEmail" name="email" class="pm-input" value="<?= e($viewer['email'] ?? old('email')) ?>" required>
                    </div>
                </div>

                <div class="pm-form-grid-2">
                    <div class="pm-field">
                        <label class="pm-label" for="cCat">Category</label>
                        <select id="cCat" name="category" class="pm-select">
                            <option value="general">General Inquiry</option>
                            <option value="payment">bKash / Nagad / Rocket Payment</option>
                            <option value="order">Order &amp; Invoice</option>
                            <option value="download">Download &amp; License</option>
                            <option value="seller">Becoming a Seller</option>
                            <option value="technical">Technical Help</option>
                            <option value="refund">Refund Request</option>
                        </select>
                    </div>
                    <div class="pm-field">
                        <label class="pm-label" for="cSubj">Subject</label>
                        <input id="cSubj" name="subject" class="pm-input" value="<?= e(old('subject')) ?>" placeholder="How can we help?" required>
                    </div>
                </div>

                <div class="pm-field">
                    <label class="pm-label" for="cMsg">Message Details</label>
                    <textarea id="cMsg" name="message" class="pm-textarea" placeholder="Include your Order Number or project link if applicable..." required><?= e(old('message')) ?></textarea>
                </div>

                <div>
                    <button type="submit" class="pm-btn pm-btn-primary pm-btn-lg">
                        <?= icon('send', 16) ?><span>Submit Support Message</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
