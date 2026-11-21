-- ============================================================================
-- PREMIUM PROJECT MARKETPLACE (CodeVault) - Initial System Seed Data (SQLite 3)
-- Automatically executed on first application launch (zero manual setup).
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. DEFAULT SITE, DESIGN, SECURITY, COMMISSION & PAYMENT SETTINGS
-- ----------------------------------------------------------------------------
INSERT INTO `site_settings` (`setting_group`, `setting_key`, `setting_value`) VALUES
('general', 'site_name', 'CodeVault'),
('general', 'site_tagline', 'Premium Project Marketplace'),
('general', 'site_description', 'Discover production-ready PHP projects, Android & Kotlin apps, WordPress themes & plugins, Python tools, and UI/UX design kits from verified creators.'),
('general', 'contact_email', 'support@codevault.market'),
('general', 'contact_phone', '+880 1700-000000'),
('general', 'contact_address', 'Agrabad Commercial Area, Chattogram, Bangladesh'),
('general', 'currency_code', 'BDT'),
('general', 'currency_symbol', '৳'),
('general', 'default_timezone', 'Asia/Dhaka'),
('general', 'maintenance_mode', '0'),
('branding', 'logo_path', 'assets/images/logo/logo.svg'),
('branding', 'favicon_path', 'assets/images/logo/favicon.svg'),
('branding', 'primary_color', '#4F46E5'),
('branding', 'secondary_color', '#475569'),
('homepage', 'hero_badge', 'Curated Multi-Vendor Digital Code Marketplace'),
('homepage', 'hero_title', 'Build faster with verified source code, apps & templates'),
('homepage', 'hero_subtitle', 'Discover studio-grade PHP applications, Android & Kotlin projects, WordPress products, Python tools, and UI/UX kits crafted by verified developers with instant secure downloads and bKash, Nagad & Rocket checkout.'),
('homepage', 'hero_primary_cta_label', 'Explore Projects'),
('homepage', 'hero_primary_cta_url', 'projects.php'),
('homepage', 'hero_secondary_cta_label', 'Start Selling'),
('homepage', 'hero_secondary_cta_url', 'register.php?role=seller'),
('homepage', 'show_stats_bar', '1'),
('finance', 'default_commission_rate', '10.00'),
('finance', 'min_withdrawal_amount', '500.00'),
('finance', 'max_withdrawal_amount', '100000.00'),
('finance', 'default_download_limit', '25'),
('registration', 'user_registration_enabled', '1'),
('registration', 'seller_registration_enabled', '1'),
('registration', 'seller_auto_approve', '1'),
('registration', 'project_auto_approve', '0'),
('upload', 'max_project_zip_mb', '100'),
('upload', 'max_image_mb', '5'),
('upload', 'max_proof_mb', '5'),
('notifications', 'notifications_enabled', '1'),
('notifications', 'newsletter_enabled', '1'),
('seo', 'meta_title', 'CodeVault — Premium Digital Project & Source Code Marketplace'),
('seo', 'meta_description', 'Buy and sell verified PHP projects, HTML/CSS templates, JavaScript apps, Android, Java, Kotlin, WordPress themes & plugins, Python projects, and UI/UX templates.'),
('social', 'github_url', 'https://github.com'),
('social', 'linkedin_url', 'https://linkedin.com'),
('social', 'youtube_url', 'https://youtube.com'),
('footer', 'footer_about', 'CodeVault connects software engineers, studios, agencies, and learners with quality-inspected digital products, transparent licensing, and secure Bangladesh-first manual payments.'),
('footer', 'copyright_text', 'CodeVault Marketplace. All rights reserved.')
ON CONFLICT(`setting_key`) DO NOTHING;

-- ----------------------------------------------------------------------------
-- 2. ALL 14 DIGITAL PRODUCT CATEGORIES (PRD Section 2)
-- ----------------------------------------------------------------------------
INSERT INTO `categories` (`name`, `slug`, `short_description`, `description`, `icon_name`, `accent_color`, `sort_order`, `is_popular`, `status`) VALUES
('PHP Projects', 'php-projects', 'Full-stack PHP web apps, SaaS, POS, CRM & ERP systems', 'Production-ready native PHP and OOP web applications including management portals, eCommerce systems, invoicing suites, and modular SaaS starters.', 'code', '#635BFF', 1, 1, 'active'),
('HTML Templates', 'html-templates', 'Semantic HTML5 landing pages, admin layouts & portals', 'Clean, fast-loading W3C-valid HTML5 website templates crafted for agencies, startups, portfolios, documentation hubs, and SaaS products.', 'layout', '#FF5E3A', 2, 1, 'active'),
('CSS Templates', 'css-templates', 'Modular CSS3 design systems, UI tokens & component kits', 'Zero-dependency modern CSS3 layout kits, animation libraries, soft neumorphic surfaces, and responsive grid stylesheets.', 'palette', '#0EA5E9', 3, 0, 'active'),
('JavaScript Projects', 'javascript-projects', 'Interactive web apps, dashboards, visualizers & tools', 'Pure Vanilla JavaScript and ES6+ interactive applications, chart engines, kanban boards, calculators, and browser productivity tools.', 'zap', '#F59E0B', 4, 1, 'active'),
('Android Applications', 'android-applications', 'Complete Android Studio projects with clean architecture', 'Ready-to-build Android mobile applications for commerce, delivery, finance tracking, education, utilities, and on-demand services.', 'smartphone', '#10B981', 5, 1, 'active'),
('Java Applications', 'java-applications', 'Enterprise Java desktop, inventory, academic & backend apps', 'Structured OOP Java applications covering POS billing, library and hospital management, desktop utilities, and backend services.', 'cpu', '#F43F5E', 6, 0, 'active'),
('Kotlin Projects', 'kotlin-projects', 'Modern Kotlin & Jetpack Compose native mobile apps', 'Idiomatic Kotlin applications with MVVM architecture, Material design patterns, offline-first storage, and clean modular packages.', 'layers', '#8B5CF6', 7, 1, 'active'),
('WordPress Themes', 'wordpress-themes', 'Fast, SEO-optimized business, blog & WooCommerce themes', 'Lightweight Gutenberg and WooCommerce-ready WordPress themes engineered for Core Web Vitals, accessibility, and easy customization.', 'globe', '#2563EB', 8, 1, 'active'),
('WordPress Plugins', 'wordpress-plugins', 'Custom WooCommerce, booking, SEO & payment extensions', 'Secure, standards-compliant WordPress plugins to extend checkout, analytics, automation, memberships, and editorial workflows.', 'sliders', '#06B6D4', 9, 1, 'active'),
('Python Projects', 'python-projects', 'Automation suites, data dashboards, AI tools & web apps', 'Well-documented Python software projects spanning analytics pipelines, OCR utilities, reporting systems, APIs, and automation bots.', 'terminal', '#14B8A6', 10, 1, 'active'),
('UI/UX Templates', 'ui-ux-templates', 'Design systems, wireframes, mobile & SaaS interface kits', 'High-craft interface templates, design tokens, component libraries, and multi-screen product flows for web and mobile products.', 'sparkles', '#EC4899', 11, 1, 'active'),
('Source Code', 'source-code', 'Reusable architecture blueprints, SDKs & starter modules', 'Audited core libraries, authentication starters, billing ledgers, API clients, and clean reference implementations.', 'file-code', '#4F46E5', 12, 0, 'active'),
('Scripts', 'scripts', 'Lightweight PHP, JS & Python automation and utility scripts', 'Drop-in server scripts for backups, file handling, form processing, invoice generation, SEO sitemaps, and monitoring.', 'file-text', '#D946EF', 13, 0, 'active'),
('Other Digital Products', 'other-digital-products', 'SQL schemas, icon packs, technical blueprints & docs', 'Curated developer assets including normalized database schemas, custom SVG icon sets, QA checklists, and architecture kits.', 'package', '#EA580C', 14, 0, 'active')
ON CONFLICT(`slug`) DO UPDATE SET
    `name` = excluded.`name`,
    `short_description` = excluded.`short_description`;

-- ----------------------------------------------------------------------------
-- 3. MANUAL PAYMENT METHODS (bKash, Nagad, Rocket)
-- ----------------------------------------------------------------------------
INSERT INTO `payment_methods` (`code`, `name`, `account_number`, `account_type`, `instructions`, `brand_color`, `sort_order`, `is_enabled`) VALUES
('bkash', 'bKash', '01700-000001', 'personal', '1. Open your bKash App or dial *247#.\n2. Choose "Send Money" and enter the bKash number shown above.\n3. Enter the exact payable BDT amount and your Order Number in Reference.\n4. Confirm with your PIN, copy the Transaction ID (TrxID), and submit it below with an optional screenshot.', '#E2136E', 1, 1),
('nagad', 'Nagad', '01800-000002', 'personal', '1. Open your Nagad App or dial *167#.\n2. Select "Send Money" and enter the Nagad account number shown above.\n3. Enter the exact payable BDT total and add your Order Number as reference.\n4. After confirmation, enter your sender number and Transaction ID (TrxID) below.', '#F6921E', 2, 1),
('rocket', 'Rocket', '01900-000003-4', 'personal', '1. Open the DBBL NexusPay/Rocket App or dial *322#.\n2. Select "Send Money" and enter the 12-digit Rocket number above.\n3. Transfer the exact order amount in BDT.\n4. Copy the Transaction ID (TrxID) from your confirmation SMS and submit it below for admin verification.', '#8C3494', 3, 1)
ON CONFLICT(`code`) DO NOTHING;

-- ----------------------------------------------------------------------------
-- 4. NAVIGATION MENUS & MENU ITEMS
-- ----------------------------------------------------------------------------
INSERT INTO `menus` (`id`, `location`, `name`, `status`) VALUES
(1, 'header_primary', 'Primary Header Navigation', 'active'),
(2, 'footer_marketplace', 'Footer Marketplace Links', 'active'),
(3, 'footer_company', 'Footer Company & Support', 'active'),
(4, 'footer_legal', 'Footer Legal & Policies', 'active')
ON CONFLICT(`location`) DO UPDATE SET `name` = excluded.`name`;

INSERT OR IGNORE INTO `menu_items` (`menu_id`, `label`, `url`, `icon_name`, `sort_order`, `is_visible`) VALUES
(1, 'Home', 'index.php', 'home', 1, 1),
(1, 'Explore', 'projects.php', 'grid', 2, 1),
(1, 'Categories', 'categories.php', 'categories', 3, 1),
(1, 'Featured', 'featured-projects.php', 'award', 4, 1),
(1, 'Free Projects', 'free-projects.php', 'tag', 5, 1),
(1, 'Become a Seller', 'seller/become-seller.php', 'store', 6, 1),
(2, 'Explore All Projects', 'projects.php', NULL, 1, 1),
(2, 'Featured Projects', 'featured-projects.php', NULL, 2, 1),
(2, 'Free Projects', 'free-projects.php', NULL, 3, 1),
(2, 'Browse Categories', 'categories.php', NULL, 4, 1),
(2, 'Start Selling', 'seller/become-seller.php', NULL, 5, 1),
(3, 'About Marketplace', 'about.php', NULL, 1, 1),
(3, 'Help & FAQ', 'faq.php', NULL, 2, 1),
(3, 'Contact Support', 'contact.php', NULL, 3, 1),
(3, 'Buyer Dashboard', 'buyer/dashboard.php', NULL, 4, 1),
(3, 'XML Sitemap', 'sitemap.php', NULL, 5, 1),
(4, 'Terms & Conditions', 'terms.php', NULL, 1, 1),
(4, 'Privacy Policy', 'privacy.php', NULL, 2, 1),
(4, 'Refund Policy', 'refund-policy.php', NULL, 3, 1),
(4, 'License Policy', 'license-policy.php', NULL, 4, 1);

-- ----------------------------------------------------------------------------
-- 5. HOMEPAGE DYNAMIC SECTIONS (ADMIN-CONTROLLABLE)
-- ----------------------------------------------------------------------------
INSERT INTO `homepage_sections` (`section_key`, `badge_text`, `title`, `subtitle`, `sort_order`, `items_limit`, `is_visible`) VALUES
('popular_categories', 'Browse Ecosystem', 'Popular Product Categories', 'Explore 14 specialized categories spanning web engineering, native mobile apps, WordPress solutions, Python systems, and UI kits.', 1, 8, 1),
('featured_projects', 'Editor''s Selection', 'Featured Premium Projects', 'Hand-inspected software releases chosen for architectural clarity, security hygiene, documentation quality, and refined UX.', 2, 6, 1),
('trending_projects', 'Momentum Now', 'Trending Across the Marketplace', 'High-velocity releases developers, agencies, and founders are bookmarking and deploying this week.', 3, 6, 1),
('latest_projects', 'Fresh Releases', 'Newly Published Projects', 'The newest approved digital products, complete with versioned archives and verified source structure.', 4, 6, 1),
('best_selling', 'Proven in Production', 'Best Selling Products', 'Battle-tested codebases with the strongest buyer adoption, repeat updates, and verified ratings.', 5, 3, 1),
('free_projects', 'Community Edition', 'Free Starter Projects & Kits', 'Download clean, inspected open starter kits and components at zero cost to experience CodeVault quality.', 6, 3, 1),
('top_sellers', 'Verified Creators', 'Top Rated Author Studios', 'Independent software engineers and product studios maintaining rapid support and high code review scores.', 7, 4, 1),
('promo_banner', 'Monetize Your Code', 'Turn your best engineering into recurring income', 'Keep 90% of every verified sale and withdraw directly to your bKash, Nagad, or Rocket account.', 8, 1, 1),
('testimonials', 'Verified Reviews', 'Trusted by Buyers & Agencies', 'Authentic feedback written exclusively by buyers with verified completed purchases on CodeVault.', 9, 3, 1),
('faq', 'Clear Answers', 'Frequently Asked Questions', 'Everything you need to know about licensing, bKash/Nagad/Rocket verification, secure downloads, and selling.', 10, 6, 1),
('newsletter', 'Release Digest', 'Get weekly curated releases & coupon drops', 'One concise digest every week featuring newly vetted projects, free starter packs, and seasonal discount codes.', 11, 1, 1)
ON CONFLICT(`section_key`) DO UPDATE SET `title` = excluded.`title`;

-- ----------------------------------------------------------------------------
-- 6. PROMOTIONAL BANNER
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO `banners` (`position`, `badge_text`, `title`, `subtitle`, `primary_cta_label`, `primary_cta_url`, `secondary_cta_label`, `secondary_cta_url`, `sort_order`, `status`) VALUES
('homepage_promo', '90% Creator Revenue Share', 'Publish your digital projects and get paid via bKash, Nagad & Rocket', 'Join verified Bangladeshi and global developers selling PHP apps, Android projects, WordPress plugins, Python tools, and UI kits. Every release is protected by private storage, license keys, and audited ledger payouts.', 'Apply as a Seller', 'seller/become-seller.php', 'Read License Policy', 'license-policy.php', 1, 'active');

-- ----------------------------------------------------------------------------
-- 7. FREQUENTLY ASKED QUESTIONS
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO `faqs` (`category`, `question`, `answer`, `show_on_homepage`, `sort_order`, `status`) VALUES
('Payments', 'How do bKash, Nagad, and Rocket manual payments work?', 'At checkout, select bKash, Nagad, or Rocket to view our official payment number and step-by-step instructions. After sending the exact order total, submit your sender number, Transaction ID (TrxID), and an optional payment screenshot. Our verification team validates the transaction and unlocks your secure download immediately upon approval.', 1, 1, 'active'),
('Downloads', 'Are project ZIP files safe and how do I download updates?', 'Every project archive lives in non-public protected storage and undergoes automated ZIP and extension inspection before approval. Once your order is approved as Paid, you can download the full ZIP package and license certificate anytime from Buyer Dashboard > Downloads, including free minor version updates released by the seller.', 1, 2, 'active'),
('Licensing', 'What is the difference between a Regular and Extended License?', 'A Regular License permits using the item in a single end product for yourself or one client where end users are not charged to access the product. An Extended License allows use in a single commercial SaaS or paid-access end product, plus multi-environment deployment rights as detailed in our License Policy.', 1, 3, 'active'),
('Selling', 'Can my single account buy projects and also sell my own code?', 'Yes. Every registered account starts with full Buyer capabilities and can apply for Seller privileges from the Become a Seller page without creating a second account. Once approved, you gain access to the Seller Dashboard while keeping all your personal purchases, wishlist, and downloads intact.', 1, 4, 'active'),
('Selling', 'What commission does CodeVault charge and how do sellers withdraw earnings?', 'CodeVault applies a transparent 10% platform commission calculated server-side on every confirmed sale, meaning sellers earn 90% of the item price. Every credit is recorded in an immutable financial ledger, and available balances can be withdrawn directly to bKash, Nagad, or Rocket.', 1, 5, 'active'),
('Support', 'What happens if a downloaded project does not match its description?', 'Every listing displays documented compatibility, features, and live demo previews. If a verified download is materially broken or fails to match its published specification and the seller cannot resolve it, you may open a Support Ticket from your Buyer Dashboard under our Refund Policy.', 1, 6, 'active'),
('Payments', 'Why was my Transaction ID flagged as already submitted?', 'To protect buyers and prevent duplicate claims, every Transaction ID is uniquely indexed per payment channel. If you accidentally mistyped a digit, open your pending order or contact support with your payment screenshot so our team can assist.', 0, 7, 'active'),
('Reviews', 'Who is allowed to leave ratings and reviews on a project?', 'Only authenticated buyers who have completed a verified order for a project can submit a rating and written review, strictly limited to one review per purchased order item so marketplace ratings remain trustworthy.', 0, 8, 'active');

-- ----------------------------------------------------------------------------
-- 8. STATIC / LEGAL CMS PAGES
-- ----------------------------------------------------------------------------
INSERT INTO `pages` (`slug`, `title`, `subtitle`, `content`, `meta_title`, `meta_description`, `status`) VALUES
('about', 'About CodeVault Marketplace', 'Engineered to connect serious software creators with teams that ship faster.', '<h2>Our Mission</h2><p>CodeVault was built to give developers, digital agencies, students, and founders a trustworthy marketplace for production-grade software projects—with native support for Bangladesh''s local payment ecosystem (bKash, Nagad, and Rocket) alongside rigorous architectural quality review.</p><h2>Why Teams Choose CodeVault</h2><p>Unlike uncurated file boards, every submission on CodeVault undergoes ZIP structure verification, security review, documentation checks, and metadata validation before reaching the public catalog. Project files are stored inside private non-public storage and streamed exclusively to verified license holders.</p><h2>Fair Creator Economics</h2><p>A single CodeVault account operates seamlessly as both Buyer and Seller. Approved creators retain 90% of net product sales, tracked inside an immutable double-checked financial ledger with transparent commission calculations and direct payouts to bKash, Nagad, and Rocket.</p><h2>14 Specialized Software Verticals</h2><p>From Native PHP applications, HTML5/CSS3 design systems, and JavaScript web apps to Android, Java, Kotlin, WordPress themes and plugins, Python tools, and UI/UX kits, our catalog covers the complete modern product lifecycle.</p>', 'About CodeVault — Premium Project Marketplace', 'Learn how CodeVault connects verified software authors with buyers across 14 digital product categories.', 'published'),
('terms', 'Terms & Conditions', 'Clear rules governing marketplace accounts, purchases, seller conduct, and intellectual property.', '<h2>1. Acceptance of Terms</h2><p>By registering an account, browsing listings, purchasing digital products, or submitting items as a seller on CodeVault, you agree to comply with these Terms & Conditions and all referenced marketplace policies.</p><h2>2. Account Registration & Security</h2><p>You must provide accurate registration information and safeguard your account credentials. A single account may hold Buyer privileges and, subject to administrator approval, Seller privileges. Accounts engaging in fraudulent payment submissions, unauthorized redistribution, or security abuse may be suspended or permanently blocked.</p><h2>3. Digital Product Purchases & Manual Verification</h2><p>All paid orders in the current release are processed via verified manual transfer through bKash, Nagad, or Rocket. Buyers must transfer the exact invoice amount and submit an authentic sender number and Transaction ID. Download access is granted strictly after administrator verification confirms receipt of funds.</p><h2>4. Seller Obligations & Originality</h2><p>Sellers warrant that every uploaded project archive, screenshot, and documentation file is either their original work or properly licensed for commercial distribution, free of malware, backdoors, obfuscated exploits, or unlicensed third-party assets.</p><h2>5. Commission & Financial Ledger</h2><p>Platform commission is deducted automatically on the server when an order transitions to Paid status. All seller balances, commissions, withdrawals, and refund adjustments are governed by immutable audited ledger records.</p>', 'Terms & Conditions — CodeVault', 'Marketplace terms covering accounts, manual payment verification, seller conduct, and licensing.', 'published'),
('privacy', 'Privacy Policy', 'How we collect, secure, and minimize personal, order, and payment verification data.', '<h2>1. Information We Collect</h2><p>We collect only the information required to operate the marketplace safely: account details (name, username, email, optional phone, hashed password), profile information, order history, download audit logs, support ticket messages, and manual payment verification details (sender mobile number, Transaction ID, and optional proof screenshot).</p><h2>2. How Your Data Is Used</h2><p>Your information is used strictly to authenticate sessions, verify manual payments, generate purchase invoices and license keys, deliver purchased software updates, process seller withdrawals, prevent fraud, and provide technical support.</p><h2>3. Security Controls</h2><p>Passwords are stored exclusively using modern cryptographic hashing. All database interactions use parameterized PDO prepared statements, state-changing actions require CSRF validation, and private files such as the external SQLite database, payment proofs, and project ZIP archives are stored outside public web access.</p><h2>4. Data Retention & Access</h2><p>Financial ledger entries, order invoices, and payment audit logs are retained to preserve accounting integrity and dispute resolution. You may update your profile details and manage active sessions anytime from your Buyer Dashboard.</p>', 'Privacy Policy — CodeVault', 'Learn how CodeVault protects your personal data, payment proofs, and session security.', 'published'),
('refund-policy', 'Refund Policy', 'Fair buyer protection balanced with the non-returnable nature of downloadable source code.', '<h2>1. Eligibility for Refunds</h2><p>Because digital source code cannot be physically returned once downloaded, refund requests are evaluated on clear technical grounds within 14 days of payment approval:</p><ul><li>The item has not yet been downloaded and you request cancellation promptly.</li><li>The project archive is corrupted, missing promised core modules, or materially differs from the published listing description and the seller fails to deliver a working fix within 5 business days.</li><li>A verified security vulnerability prevents safe operation of the purchased product.</li></ul><h2>2. Non-Refundable Situations</h2><p>Refunds cannot be approved for change of mind after downloading the ZIP archive, lack of technical knowledge for clearly stated system requirements, or incompatibilities with unadvertised third-party hosts.</p><h2>3. How to Request a Refund</h2><p>Submit a ticket under the Refund category in Buyer Dashboard &gt; Support Tickets with your Order Number and detailed issue notes. Approved refunds revoke the associated download license, record an audited ledger adjustment, and return funds via your verified bKash, Nagad, or Rocket account.</p>', 'Refund Policy — CodeVault', 'Understand refund eligibility, non-refundable cases, and dispute resolution for digital source code.', 'published'),
('license-policy', 'License Policy', 'Straightforward usage rights for Regular, Extended, MIT, and Free Personal releases.', '<h2>1. Regular License</h2><p>Grants the purchaser a non-exclusive, ongoing right to use the digital product in a <strong>single end product</strong> for personal use or for one commercial client where end users are not charged to access or use the end product itself.</p><h2>2. Extended License</h2><p>Grants the purchaser a non-exclusive, ongoing right to incorporate the item into a <strong>single commercial end product</strong> (including SaaS platforms, subscription applications, or monetized products) where end users may be charged to access the service.</p><h2>3. Free &amp; Open-Source Items</h2><p>Items labeled <strong>MIT</strong> or <strong>Free Personal</strong> may be downloaded at zero cost by registered users and governed by the included license notice inside the project archive.</p><h2>4. Prohibited Actions Across All Licenses</h2><ul><li>Reselling, redistributing, sharing, or re-uploading the source archive on any marketplace, repository, or file-sharing service.</li><li>Claiming original authorship or removing required copyright headers where prohibited.</li><li>Using a single Regular License across multiple unrelated clients without purchasing separate licenses.</li></ul>', 'License Policy — CodeVault', 'Compare Regular, Extended, MIT, and Free Personal software license rights on CodeVault.', 'published')
ON CONFLICT(`slug`) DO UPDATE SET `title` = excluded.`title`, `content` = excluded.`content`;
