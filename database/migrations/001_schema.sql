-- ============================================================================
-- PREMIUM PROJECT MARKETPLACE (CodeVault) - Normalized SQLite 3 Schema
-- Engine: SQLite 3 (WAL mode, Foreign Keys ON, STRICT Decimal Text/Numeric)
-- Automatically executed on first application launch (zero manual setup).
-- ============================================================================

PRAGMA foreign_keys = ON;

-- ----------------------------------------------------------------------------
-- 1. AUTHENTICATION, USERS, PROFILES, SESSIONS & SECURITY THROTTLE
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `full_name` TEXT NOT NULL,
    `username` TEXT NOT NULL COLLATE NOCASE,
    `email` TEXT NOT NULL COLLATE NOCASE,
    `phone` TEXT DEFAULT NULL,
    `password_hash` TEXT NOT NULL,
    `role` TEXT NOT NULL DEFAULT 'buyer' CHECK (`role` IN ('buyer','seller','admin')),
    `is_seller` INTEGER NOT NULL DEFAULT 0,
    `seller_status` TEXT NOT NULL DEFAULT 'none' CHECK (`seller_status` IN ('none','pending','approved','rejected','suspended')),
    `is_verified_seller` INTEGER NOT NULL DEFAULT 0,
    `avatar` TEXT DEFAULT NULL,
    `email_verified_at` TEXT DEFAULT NULL,
    `status` TEXT NOT NULL DEFAULT 'active' CHECK (`status` IN ('active','suspended','blocked')),
    `last_login_at` TEXT DEFAULT NULL,
    `last_login_ip` TEXT DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_users_username` UNIQUE (`username`),
    CONSTRAINT `uk_users_email` UNIQUE (`email`)
);
CREATE INDEX IF NOT EXISTS `idx_users_role_status` ON `users` (`role`, `status`);
CREATE INDEX IF NOT EXISTS `idx_users_seller_status` ON `users` (`is_seller`, `seller_status`, `status`);

CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INTEGER NOT NULL,
    `store_name` TEXT DEFAULT NULL,
    `store_slug` TEXT DEFAULT NULL COLLATE NOCASE,
    `store_banner` TEXT DEFAULT NULL,
    `headline` TEXT DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `skills` TEXT DEFAULT NULL,
    `website_url` TEXT DEFAULT NULL,
    `github_url` TEXT DEFAULT NULL,
    `portfolio_url` TEXT DEFAULT NULL,
    `support_email` TEXT DEFAULT NULL,
    `country` TEXT DEFAULT 'Bangladesh',
    `city` TEXT DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `payout_method` TEXT DEFAULT NULL CHECK (`payout_method` IS NULL OR `payout_method` IN ('bkash','nagad','rocket')),
    `payout_number` TEXT DEFAULT NULL,
    `payout_account_type` TEXT DEFAULT 'personal' CHECK (`payout_account_type` IS NULL OR `payout_account_type` IN ('personal','agent','merchant')),
    `custom_commission_rate` TEXT DEFAULT NULL,
    `response_time` TEXT DEFAULT 'Usually replies within 2 hours',
    `total_sales_count` INTEGER NOT NULL DEFAULT 0,
    `average_rating` REAL NOT NULL DEFAULT 0.00,
    `total_reviews_count` INTEGER NOT NULL DEFAULT 0,
    `seller_application_note` TEXT DEFAULT NULL,
    `seller_applied_at` TEXT DEFAULT NULL,
    `seller_approved_at` TEXT DEFAULT NULL,
    `seller_rejection_reason` TEXT DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_user_profiles_user` UNIQUE (`user_id`),
    CONSTRAINT `uk_user_profiles_store_slug` UNIQUE (`store_slug`),
    CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INTEGER NOT NULL,
    `session_token` TEXT NOT NULL,
    `ip_address` TEXT NOT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `status` TEXT NOT NULL DEFAULT 'active' CHECK (`status` IN ('active','revoked','expired')),
    `last_activity_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_user_sessions_token` UNIQUE (`session_token`),
    CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_user_sessions_user_status` ON `user_sessions` (`user_id`, `status`);

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INTEGER NOT NULL,
    `email` TEXT NOT NULL COLLATE NOCASE,
    `token_hash` TEXT NOT NULL,
    `status` TEXT NOT NULL DEFAULT 'pending' CHECK (`status` IN ('pending','used','expired')),
    `ip_address` TEXT DEFAULT NULL,
    `expires_at` TEXT NOT NULL,
    `used_at` TEXT DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_password_resets_token` ON `password_resets` (`token_hash`, `status`);
CREATE INDEX IF NOT EXISTS `idx_password_resets_email` ON `password_resets` (`email`, `status`);

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `email` TEXT NOT NULL COLLATE NOCASE,
    `ip_address` TEXT NOT NULL,
    `status` TEXT NOT NULL DEFAULT 'failed' CHECK (`status` IN ('success','failed')),
    `user_agent` TEXT DEFAULT NULL,
    `attempted_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);
CREATE INDEX IF NOT EXISTS `idx_login_attempts_ip_time` ON `login_attempts` (`ip_address`, `attempted_at`);
CREATE INDEX IF NOT EXISTS `idx_login_attempts_email_time` ON `login_attempts` (`email`, `attempted_at`);

-- ----------------------------------------------------------------------------
-- 2. CATEGORIES & PROJECTS CATALOG
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `parent_id` INTEGER DEFAULT NULL,
    `name` TEXT NOT NULL,
    `slug` TEXT NOT NULL COLLATE NOCASE,
    `short_description` TEXT DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `meta_title` TEXT DEFAULT NULL,
    `meta_description` TEXT DEFAULT NULL,
    `icon_name` TEXT NOT NULL DEFAULT 'code',
    `icon_type` TEXT NOT NULL DEFAULT 'icon',
    `icon_image` TEXT DEFAULT NULL,
    `icon_svg` TEXT DEFAULT NULL,
    `accent_color` TEXT NOT NULL DEFAULT '#635BFF',
    `sort_order` INTEGER NOT NULL DEFAULT 0,
    `is_popular` INTEGER NOT NULL DEFAULT 1,
    `status` TEXT NOT NULL DEFAULT 'active' CHECK (`status` IN ('active','inactive')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_categories_slug` UNIQUE (`slug`),
    CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_categories_status_sort` ON `categories` (`status`, `sort_order`);

CREATE TABLE IF NOT EXISTS `projects` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `seller_id` INTEGER NOT NULL,
    `category_id` INTEGER NOT NULL,
    `title` TEXT NOT NULL,
    `slug` TEXT NOT NULL COLLATE NOCASE,
    `short_description` TEXT NOT NULL,
    `description` TEXT NOT NULL,
    `thumbnail` TEXT DEFAULT NULL,
    `demo_url` TEXT DEFAULT NULL,
    `video_preview_url` TEXT DEFAULT NULL,
    `documentation_url` TEXT DEFAULT NULL,
    `version` TEXT NOT NULL DEFAULT '1.0.0',
    `file_size_bytes` INTEGER NOT NULL DEFAULT 0,
    `file_size_label` TEXT DEFAULT NULL,
    `compatibility` TEXT DEFAULT NULL,
    `license_type` TEXT NOT NULL DEFAULT 'regular' CHECK (`license_type` IN ('regular','extended','free_personal','mit','commercial')),
    `support_months` INTEGER NOT NULL DEFAULT 6,
    `documentation_included` INTEGER NOT NULL DEFAULT 1,
    `price` TEXT NOT NULL DEFAULT '0.00',
    `discount_price` TEXT DEFAULT NULL,
    `extended_price` TEXT DEFAULT NULL,
    `is_free` INTEGER NOT NULL DEFAULT 0,
    `is_featured` INTEGER NOT NULL DEFAULT 0,
    `is_trending` INTEGER NOT NULL DEFAULT 0,
    `views_count` INTEGER NOT NULL DEFAULT 0,
    `sales_count` INTEGER NOT NULL DEFAULT 0,
    `downloads_count` INTEGER NOT NULL DEFAULT 0,
    `wishlist_count` INTEGER NOT NULL DEFAULT 0,
    `reviews_count` INTEGER NOT NULL DEFAULT 0,
    `average_rating` REAL NOT NULL DEFAULT 0.00,
    `status` TEXT NOT NULL DEFAULT 'draft' CHECK (`status` IN ('draft','pending','approved','rejected','hidden','archived')),
    `rejection_reason` TEXT DEFAULT NULL,
    `approved_by` INTEGER DEFAULT NULL,
    `approved_at` TEXT DEFAULT NULL,
    `last_updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `published_at` TEXT DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_projects_slug` UNIQUE (`slug`),
    CONSTRAINT `fk_projects_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_projects_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_projects_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_projects_status_published` ON `projects` (`status`, `published_at`);
CREATE INDEX IF NOT EXISTS `idx_projects_category_status` ON `projects` (`category_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_projects_seller_status` ON `projects` (`seller_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_projects_featured` ON `projects` (`status`, `is_featured`);
CREATE INDEX IF NOT EXISTS `idx_projects_trending` ON `projects` (`status`, `is_trending`);
CREATE INDEX IF NOT EXISTS `idx_projects_free` ON `projects` (`status`, `is_free`);
CREATE INDEX IF NOT EXISTS `idx_projects_sales` ON `projects` (`status`, `sales_count`);
CREATE INDEX IF NOT EXISTS `idx_projects_rating` ON `projects` (`status`, `average_rating`);

CREATE TABLE IF NOT EXISTS `project_images` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `project_id` INTEGER NOT NULL,
    `image_path` TEXT NOT NULL,
    `caption` TEXT DEFAULT NULL,
    `sort_order` INTEGER NOT NULL DEFAULT 0,
    `status` TEXT NOT NULL DEFAULT 'active' CHECK (`status` IN ('active','hidden')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_project_images_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_project_images_project` ON `project_images` (`project_id`, `sort_order`);

CREATE TABLE IF NOT EXISTS `project_files` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `project_id` INTEGER NOT NULL,
    `version` TEXT NOT NULL,
    `original_name` TEXT NOT NULL,
    `stored_name` TEXT NOT NULL,
    `private_path` TEXT NOT NULL,
    `mime_type` TEXT NOT NULL DEFAULT 'application/zip',
    `file_size_bytes` INTEGER NOT NULL DEFAULT 0,
    `checksum_sha256` TEXT NOT NULL,
    `is_inspected` INTEGER NOT NULL DEFAULT 1,
    `is_current` INTEGER NOT NULL DEFAULT 1,
    `status` TEXT NOT NULL DEFAULT 'active' CHECK (`status` IN ('active','archived','quarantined')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_project_files_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_project_files_current` ON `project_files` (`project_id`, `is_current`, `status`);

CREATE TABLE IF NOT EXISTS `project_versions` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `project_id` INTEGER NOT NULL,
    `file_id` INTEGER DEFAULT NULL,
    `version` TEXT NOT NULL,
    `changelog` TEXT NOT NULL,
    `status` TEXT NOT NULL DEFAULT 'published' CHECK (`status` IN ('published','draft','hidden')),
    `released_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_project_versions_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_project_versions_file` FOREIGN KEY (`file_id`) REFERENCES `project_files` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_project_versions_project` ON `project_versions` (`project_id`, `released_at`);

CREATE TABLE IF NOT EXISTS `project_features` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `project_id` INTEGER NOT NULL,
    `feature_text` TEXT NOT NULL,
    `sort_order` INTEGER NOT NULL DEFAULT 0,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_project_features_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_project_features_project` ON `project_features` (`project_id`, `sort_order`);

CREATE TABLE IF NOT EXISTS `project_technologies` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `project_id` INTEGER NOT NULL,
    `tech_name` TEXT NOT NULL,
    `tech_slug` TEXT NOT NULL COLLATE NOCASE,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_project_tech` UNIQUE (`project_id`, `tech_slug`),
    CONSTRAINT `fk_project_tech_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_project_tech_slug` ON `project_technologies` (`tech_slug`);

CREATE TABLE IF NOT EXISTS `project_questions` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `project_id` INTEGER NOT NULL,
    `user_id` INTEGER NOT NULL,
    `question_text` TEXT NOT NULL,
    `answer_text` TEXT DEFAULT NULL,
    `answered_by` INTEGER DEFAULT NULL,
    `answered_at` TEXT DEFAULT NULL,
    `status` TEXT NOT NULL DEFAULT 'visible' CHECK (`status` IN ('visible','hidden')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_project_questions_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_project_questions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_project_questions_answerer` FOREIGN KEY (`answered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_project_questions_project` ON `project_questions` (`project_id`, `status`, `created_at`);

-- ----------------------------------------------------------------------------
-- 3. COUPONS & SHOPPING (CARTS, CART ITEMS & WISHLISTS)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `code` TEXT NOT NULL COLLATE NOCASE,
    `title` TEXT DEFAULT NULL,
    `discount_type` TEXT NOT NULL DEFAULT 'percentage' CHECK (`discount_type` IN ('percentage','fixed')),
    `discount_value` TEXT NOT NULL DEFAULT '0.00',
    `min_order_amount` TEXT NOT NULL DEFAULT '0.00',
    `max_discount_amount` TEXT DEFAULT NULL,
    `usage_limit` INTEGER DEFAULT NULL,
    `used_count` INTEGER NOT NULL DEFAULT 0,
    `starts_at` TEXT NOT NULL,
    `expires_at` TEXT NOT NULL,
    `status` TEXT NOT NULL DEFAULT 'active' CHECK (`status` IN ('active','inactive','expired')),
    `created_by` INTEGER DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_coupons_code` UNIQUE (`code`),
    CONSTRAINT `fk_coupons_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_coupons_status_dates` ON `coupons` (`status`, `starts_at`, `expires_at`);

CREATE TABLE IF NOT EXISTS `carts` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INTEGER DEFAULT NULL,
    `session_id` TEXT DEFAULT NULL,
    `coupon_id` INTEGER DEFAULT NULL,
    `status` TEXT NOT NULL DEFAULT 'active' CHECK (`status` IN ('active','converted','abandoned')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_carts_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_carts_user_status` ON `carts` (`user_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_carts_session_status` ON `carts` (`session_id`, `status`);

CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `cart_id` INTEGER NOT NULL,
    `project_id` INTEGER NOT NULL,
    `license_type` TEXT NOT NULL DEFAULT 'regular' CHECK (`license_type` IN ('regular','extended')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_cart_project` UNIQUE (`cart_id`, `project_id`),
    CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cart_items_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `wishlists` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INTEGER NOT NULL,
    `project_id` INTEGER NOT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_wishlist_user_project` UNIQUE (`user_id`, `project_id`),
    CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wishlist_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_wishlist_project` ON `wishlists` (`project_id`);

-- ----------------------------------------------------------------------------
-- 4. ORDERS & MANUAL PAYMENTS (bKash, Nagad, Rocket)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `orders` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `order_number` TEXT NOT NULL COLLATE NOCASE,
    `buyer_id` INTEGER NOT NULL,
    `coupon_id` INTEGER DEFAULT NULL,
    `coupon_code` TEXT DEFAULT NULL,
    `subtotal_amount` TEXT NOT NULL DEFAULT '0.00',
    `discount_amount` TEXT NOT NULL DEFAULT '0.00',
    `total_amount` TEXT NOT NULL DEFAULT '0.00',
    `currency` TEXT NOT NULL DEFAULT 'BDT',
    `status` TEXT NOT NULL DEFAULT 'pending_payment' CHECK (`status` IN ('pending_payment','payment_submitted','under_verification','paid','rejected','cancelled','refund_requested','refunded')),
    `notes` TEXT DEFAULT NULL,
    `paid_at` TEXT DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_orders_number` UNIQUE (`order_number`),
    CONSTRAINT `fk_orders_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_orders_buyer_status` ON `orders` (`buyer_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_orders_status_created` ON `orders` (`status`, `created_at`);

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `order_id` INTEGER NOT NULL,
    `project_id` INTEGER NOT NULL,
    `seller_id` INTEGER NOT NULL,
    `project_title` TEXT NOT NULL,
    `license_type` TEXT NOT NULL DEFAULT 'regular' CHECK (`license_type` IN ('regular','extended','free_personal','mit','commercial')),
    `license_key` TEXT DEFAULT NULL,
    `unit_price` TEXT NOT NULL DEFAULT '0.00',
    `discount_share` TEXT NOT NULL DEFAULT '0.00',
    `final_price` TEXT NOT NULL DEFAULT '0.00',
    `commission_rate` TEXT NOT NULL DEFAULT '10.00',
    `commission_amount` TEXT NOT NULL DEFAULT '0.00',
    `seller_earning` TEXT NOT NULL DEFAULT '0.00',
    `download_permitted` INTEGER NOT NULL DEFAULT 0,
    `download_limit` INTEGER DEFAULT NULL,
    `download_count` INTEGER NOT NULL DEFAULT 0,
    `status` TEXT NOT NULL DEFAULT 'pending' CHECK (`status` IN ('pending','active','revoked','refunded')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_order_items_license` UNIQUE (`license_key`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_items_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_order_items_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS `idx_order_items_order` ON `order_items` (`order_id`);
CREATE INDEX IF NOT EXISTS `idx_order_items_project` ON `order_items` (`project_id`);
CREATE INDEX IF NOT EXISTS `idx_order_items_seller` ON `order_items` (`seller_id`, `status`);

CREATE TABLE IF NOT EXISTS `order_status_history` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `order_id` INTEGER NOT NULL,
    `old_status` TEXT DEFAULT NULL,
    `new_status` TEXT NOT NULL,
    `note` TEXT DEFAULT NULL,
    `changed_by` INTEGER DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_order_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_order_history_order` ON `order_status_history` (`order_id`, `created_at`);

CREATE TABLE IF NOT EXISTS `coupon_usages` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `coupon_id` INTEGER NOT NULL,
    `user_id` INTEGER NOT NULL,
    `order_id` INTEGER NOT NULL,
    `discount_amount` TEXT NOT NULL DEFAULT '0.00',
    `used_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_coupon_usages_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_coupon_usages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_coupon_usages_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `code` TEXT NOT NULL COLLATE NOCASE,
    `name` TEXT NOT NULL,
    `account_number` TEXT NOT NULL,
    `account_type` TEXT NOT NULL DEFAULT 'personal' CHECK (`account_type` IN ('personal','merchant','agent')),
    `instructions` TEXT NOT NULL,
    `brand_color` TEXT NOT NULL DEFAULT '#635BFF',
    `sort_order` INTEGER NOT NULL DEFAULT 0,
    `is_enabled` INTEGER NOT NULL DEFAULT 1,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_payment_methods_code` UNIQUE (`code`)
);

CREATE TABLE IF NOT EXISTS `payments` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `order_id` INTEGER NOT NULL,
    `buyer_id` INTEGER NOT NULL,
    `payment_method_id` INTEGER NOT NULL,
    `method_code` TEXT NOT NULL CHECK (`method_code` IN ('bkash','nagad','rocket')),
    `amount` TEXT NOT NULL DEFAULT '0.00',
    `sender_number` TEXT NOT NULL,
    `transaction_id` TEXT NOT NULL COLLATE NOCASE,
    `buyer_note` TEXT DEFAULT NULL,
    `status` TEXT NOT NULL DEFAULT 'submitted' CHECK (`status` IN ('submitted','under_verification','approved','rejected','refunded')),
    `admin_note` TEXT DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,
    `verified_by` INTEGER DEFAULT NULL,
    `submitted_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `verified_at` TEXT DEFAULT NULL,
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_payments_method_trx` UNIQUE (`method_code`, `transaction_id`),
    CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_payments_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_payments_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_payments_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_payments_order` ON `payments` (`order_id`);
CREATE INDEX IF NOT EXISTS `idx_payments_buyer_status` ON `payments` (`buyer_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_payments_status_submitted` ON `payments` (`status`, `submitted_at`);

CREATE TABLE IF NOT EXISTS `payment_proofs` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `payment_id` INTEGER NOT NULL,
    `original_name` TEXT NOT NULL,
    `stored_name` TEXT NOT NULL,
    `private_path` TEXT NOT NULL,
    `mime_type` TEXT NOT NULL,
    `file_size_bytes` INTEGER NOT NULL DEFAULT 0,
    `uploaded_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_payment_proofs_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 5. IMMUTABLE FINANCIAL LEDGER, COMMISSIONS, BALANCES & WITHDRAWALS
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `commissions` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `order_item_id` INTEGER NOT NULL,
    `seller_id` INTEGER NOT NULL,
    `gross_amount` TEXT NOT NULL DEFAULT '0.00',
    `commission_rate` TEXT NOT NULL DEFAULT '10.00',
    `admin_commission` TEXT NOT NULL DEFAULT '0.00',
    `seller_earning` TEXT NOT NULL DEFAULT '0.00',
    `status` TEXT NOT NULL DEFAULT 'credited' CHECK (`status` IN ('credited','reversed')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_commissions_order_item` UNIQUE (`order_item_id`),
    CONSTRAINT `fk_commissions_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_commissions_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS `seller_balances` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `seller_id` INTEGER NOT NULL,
    `gross_sales` TEXT NOT NULL DEFAULT '0.00',
    `total_commission` TEXT NOT NULL DEFAULT '0.00',
    `total_earnings` TEXT NOT NULL DEFAULT '0.00',
    `available_balance` TEXT NOT NULL DEFAULT '0.00',
    `pending_withdrawal` TEXT NOT NULL DEFAULT '0.00',
    `withdrawn_total` TEXT NOT NULL DEFAULT '0.00',
    `refunded_total` TEXT NOT NULL DEFAULT '0.00',
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_seller_balances_seller` UNIQUE (`seller_id`),
    CONSTRAINT `fk_seller_balances_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS `withdrawals` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `request_number` TEXT NOT NULL COLLATE NOCASE,
    `seller_id` INTEGER NOT NULL,
    `method` TEXT NOT NULL CHECK (`method` IN ('bkash','nagad','rocket')),
    `account_number` TEXT NOT NULL,
    `amount` TEXT NOT NULL DEFAULT '0.00',
    `status` TEXT NOT NULL DEFAULT 'pending' CHECK (`status` IN ('pending','approved','rejected','paid')),
    `payment_reference` TEXT DEFAULT NULL,
    `admin_note` TEXT DEFAULT NULL,
    `processed_by` INTEGER DEFAULT NULL,
    `processed_at` TEXT DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_withdrawals_number` UNIQUE (`request_number`),
    CONSTRAINT `fk_withdrawals_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_withdrawals_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_withdrawals_seller_status` ON `withdrawals` (`seller_id`, `status`);

CREATE TABLE IF NOT EXISTS `financial_ledger` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `reference_code` TEXT NOT NULL,
    `user_id` INTEGER NOT NULL,
    `order_id` INTEGER DEFAULT NULL,
    `withdrawal_id` INTEGER DEFAULT NULL,
    `entry_type` TEXT NOT NULL CHECK (`entry_type` IN ('sale_credit','commission_debit','withdrawal_hold','withdrawal_paid','withdrawal_release','refund_debit')),
    `direction` TEXT NOT NULL CHECK (`direction` IN ('credit','debit')),
    `amount` TEXT NOT NULL DEFAULT '0.00',
    `balance_after` TEXT NOT NULL DEFAULT '0.00',
    `description` TEXT NOT NULL,
    `created_by` INTEGER DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_ledger_reference` UNIQUE (`reference_code`),
    CONSTRAINT `fk_ledger_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_ledger_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ledger_withdrawal` FOREIGN KEY (`withdrawal_id`) REFERENCES `withdrawals` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ledger_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_ledger_user_created` ON `financial_ledger` (`user_id`, `created_at`);

CREATE TABLE IF NOT EXISTS `withdrawal_history` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `withdrawal_id` INTEGER NOT NULL,
    `old_status` TEXT DEFAULT NULL,
    `new_status` TEXT NOT NULL,
    `note` TEXT DEFAULT NULL,
    `changed_by` INTEGER DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_withdrawal_history_wd` FOREIGN KEY (`withdrawal_id`) REFERENCES `withdrawals` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_withdrawal_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- ----------------------------------------------------------------------------
-- 6. BUYER ACTIVITY (DOWNLOADS, VERIFIED REVIEWS, REVIEW REPORTS)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `downloads` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INTEGER NOT NULL,
    `project_id` INTEGER NOT NULL,
    `order_id` INTEGER DEFAULT NULL,
    `order_item_id` INTEGER DEFAULT NULL,
    `file_id` INTEGER NOT NULL,
    `version` TEXT NOT NULL,
    `ip_address` TEXT DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `status` TEXT NOT NULL DEFAULT 'completed' CHECK (`status` IN ('completed','blocked','failed')),
    `downloaded_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_downloads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_downloads_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_downloads_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_downloads_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_downloads_file` FOREIGN KEY (`file_id`) REFERENCES `project_files` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_downloads_user_date` ON `downloads` (`user_id`, `downloaded_at`);
CREATE INDEX IF NOT EXISTS `idx_downloads_item` ON `downloads` (`order_item_id`);

CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `project_id` INTEGER NOT NULL,
    `buyer_id` INTEGER NOT NULL,
    `order_item_id` INTEGER NOT NULL,
    `rating` INTEGER NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `title` TEXT DEFAULT NULL,
    `comment` TEXT NOT NULL,
    `seller_reply` TEXT DEFAULT NULL,
    `seller_replied_at` TEXT DEFAULT NULL,
    `is_featured_testimonial` INTEGER NOT NULL DEFAULT 0,
    `status` TEXT NOT NULL DEFAULT 'published' CHECK (`status` IN ('published','hidden','flagged')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_reviews_order_item` UNIQUE (`order_item_id`),
    CONSTRAINT `fk_reviews_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reviews_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reviews_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_reviews_project_status` ON `reviews` (`project_id`, `status`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_reviews_buyer` ON `reviews` (`buyer_id`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_reviews_testimonial` ON `reviews` (`is_featured_testimonial`, `status`);

CREATE TABLE IF NOT EXISTS `review_reports` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `review_id` INTEGER NOT NULL,
    `reported_by` INTEGER NOT NULL,
    `reason` TEXT NOT NULL,
    `status` TEXT NOT NULL DEFAULT 'pending' CHECK (`status` IN ('pending','open','reviewed','dismissed')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_review_report_once` UNIQUE (`review_id`, `reported_by`),
    CONSTRAINT `fk_review_reports_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_review_reports_user` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 7. MARKETING (REFERRALS, PROMO BANNERS, NEWSLETTER, CAMPAIGNS)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `referrals` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `referrer_id` INTEGER NOT NULL,
    `referred_user_id` INTEGER NOT NULL,
    `referral_code` TEXT NOT NULL,
    `reward_amount` TEXT NOT NULL DEFAULT '0.00',
    `status` TEXT NOT NULL DEFAULT 'registered' CHECK (`status` IN ('registered','qualified','rewarded','cancelled')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_referrals_referred` UNIQUE (`referred_user_id`),
    CONSTRAINT `fk_referrals_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_referrals_referred` FOREIGN KEY (`referred_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `banners` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `position` TEXT NOT NULL DEFAULT 'homepage_promo',
    `badge_text` TEXT DEFAULT NULL,
    `title` TEXT NOT NULL,
    `subtitle` TEXT DEFAULT NULL,
    `primary_cta_label` TEXT DEFAULT NULL,
    `primary_cta_url` TEXT DEFAULT NULL,
    `secondary_cta_label` TEXT DEFAULT NULL,
    `secondary_cta_url` TEXT DEFAULT NULL,
    `image_path` TEXT DEFAULT NULL,
    `sort_order` INTEGER NOT NULL DEFAULT 0,
    `status` TEXT NOT NULL DEFAULT 'active' CHECK (`status` IN ('active','inactive')),
    `starts_at` TEXT DEFAULT NULL,
    `ends_at` TEXT DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_banners_pos_title` UNIQUE (`position`, `title`)
);
CREATE INDEX IF NOT EXISTS `idx_banners_position` ON `banners` (`position`, `status`, `sort_order`);

CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `email` TEXT NOT NULL COLLATE NOCASE,
    `status` TEXT NOT NULL DEFAULT 'subscribed' CHECK (`status` IN ('subscribed','unsubscribed')),
    `ip_address` TEXT DEFAULT NULL,
    `subscribed_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `unsubscribed_at` TEXT DEFAULT NULL,
    CONSTRAINT `uk_newsletter_email` UNIQUE (`email`)
);

CREATE TABLE IF NOT EXISTS `promotional_campaigns` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` TEXT NOT NULL,
    `slug` TEXT NOT NULL COLLATE NOCASE,
    `description` TEXT DEFAULT NULL,
    `discount_percent` TEXT NOT NULL DEFAULT '0.00',
    `starts_at` TEXT NOT NULL,
    `ends_at` TEXT NOT NULL,
    `status` TEXT NOT NULL DEFAULT 'draft' CHECK (`status` IN ('draft','active','ended')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_campaigns_slug` UNIQUE (`slug`)
);

-- ----------------------------------------------------------------------------
-- 8. COMMUNICATION (NOTIFICATIONS, SUPPORT TICKETS & MESSAGES)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INTEGER NOT NULL,
    `type` TEXT NOT NULL DEFAULT 'system',
    `title` TEXT NOT NULL,
    `message` TEXT NOT NULL,
    `action_url` TEXT DEFAULT NULL,
    `icon_name` TEXT NOT NULL DEFAULT 'bell',
    `is_read` INTEGER NOT NULL DEFAULT 0,
    `read_at` TEXT DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_notifications_user_read` ON `notifications` (`user_id`, `is_read`, `created_at`);

CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `ticket_number` TEXT NOT NULL COLLATE NOCASE,
    `user_id` INTEGER DEFAULT NULL,
    `guest_name` TEXT DEFAULT NULL,
    `guest_email` TEXT DEFAULT NULL,
    `order_id` INTEGER DEFAULT NULL,
    `category` TEXT NOT NULL DEFAULT 'general' CHECK (`category` IN ('general','order','payment','download','technical','seller','refund','account')),
    `priority` TEXT NOT NULL DEFAULT 'normal' CHECK (`priority` IN ('low','normal','high','urgent')),
    `subject` TEXT NOT NULL,
    `status` TEXT NOT NULL DEFAULT 'open' CHECK (`status` IN ('open','in_progress','answered','resolved','closed')),
    `last_reply_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_tickets_number` UNIQUE (`ticket_number`),
    CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tickets_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_tickets_user_status` ON `support_tickets` (`user_id`, `status`, `last_reply_at`);

CREATE TABLE IF NOT EXISTS `ticket_messages` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `ticket_id` INTEGER NOT NULL,
    `sender_id` INTEGER DEFAULT NULL,
    `sender_role` TEXT NOT NULL DEFAULT 'user' CHECK (`sender_role` IN ('user','admin')),
    `message` TEXT NOT NULL,
    `attachment_path` TEXT DEFAULT NULL,
    `attachment_name` TEXT DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_ticket_messages_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ticket_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_ticket_messages_ticket` ON `ticket_messages` (`ticket_id`, `created_at`);

-- ----------------------------------------------------------------------------
-- 9. WEBSITE CMS, SETTINGS, NAVIGATION, HOMEPAGE SECTIONS & FAQS
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `setting_group` TEXT NOT NULL DEFAULT 'general',
    `setting_key` TEXT NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_site_settings_key` UNIQUE (`setting_key`)
);
CREATE INDEX IF NOT EXISTS `idx_site_settings_group` ON `site_settings` (`setting_group`);

CREATE TABLE IF NOT EXISTS `pages` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `slug` TEXT NOT NULL COLLATE NOCASE,
    `title` TEXT NOT NULL,
    `subtitle` TEXT DEFAULT NULL,
    `content` TEXT NOT NULL,
    `meta_title` TEXT DEFAULT NULL,
    `meta_description` TEXT DEFAULT NULL,
    `status` TEXT NOT NULL DEFAULT 'published' CHECK (`status` IN ('published','draft')),
    `updated_by` INTEGER DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_pages_slug` UNIQUE (`slug`),
    CONSTRAINT `fk_pages_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `menus` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `location` TEXT NOT NULL,
    `name` TEXT NOT NULL,
    `status` TEXT NOT NULL DEFAULT 'active' CHECK (`status` IN ('active','inactive')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_menus_location` UNIQUE (`location`)
);

CREATE TABLE IF NOT EXISTS `menu_items` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `menu_id` INTEGER NOT NULL,
    `label` TEXT NOT NULL,
    `url` TEXT NOT NULL,
    `icon_name` TEXT DEFAULT NULL,
    `target` TEXT NOT NULL DEFAULT '_self' CHECK (`target` IN ('_self','_blank')),
    `sort_order` INTEGER NOT NULL DEFAULT 0,
    `is_visible` INTEGER NOT NULL DEFAULT 1,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_menu_items_menu_url` UNIQUE (`menu_id`, `url`),
    CONSTRAINT `fk_menu_items_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_menu_items_menu_sort` ON `menu_items` (`menu_id`, `is_visible`, `sort_order`);

CREATE TABLE IF NOT EXISTS `homepage_sections` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `section_key` TEXT NOT NULL,
    `badge_text` TEXT DEFAULT NULL,
    `title` TEXT NOT NULL,
    `subtitle` TEXT DEFAULT NULL,
    `sort_order` INTEGER NOT NULL DEFAULT 0,
    `items_limit` INTEGER NOT NULL DEFAULT 6,
    `is_visible` INTEGER NOT NULL DEFAULT 1,
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_homepage_sections_key` UNIQUE (`section_key`)
);
CREATE INDEX IF NOT EXISTS `idx_homepage_sections_sort` ON `homepage_sections` (`is_visible`, `sort_order`);

CREATE TABLE IF NOT EXISTS `faqs` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `category` TEXT NOT NULL DEFAULT 'General',
    `question` TEXT NOT NULL,
    `answer` TEXT NOT NULL,
    `show_on_homepage` INTEGER NOT NULL DEFAULT 1,
    `sort_order` INTEGER NOT NULL DEFAULT 0,
    `status` TEXT NOT NULL DEFAULT 'active' CHECK (`status` IN ('active','inactive')),
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    `updated_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `uk_faqs_question` UNIQUE (`question`)
);
CREATE INDEX IF NOT EXISTS `idx_faqs_status_sort` ON `faqs` (`status`, `sort_order`);

-- ----------------------------------------------------------------------------
-- 10. SECURITY, AUDIT LOGS & SYSTEM BACKUPS
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admin_logs` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `admin_id` INTEGER NOT NULL,
    `action` TEXT NOT NULL,
    `target_type` TEXT DEFAULT NULL,
    `target_id` INTEGER DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` TEXT DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_admin_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_admin_logs_admin_date` ON `admin_logs` (`admin_id`, `created_at`);

CREATE TABLE IF NOT EXISTS `security_logs` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INTEGER DEFAULT NULL,
    `event_type` TEXT NOT NULL,
    `severity` TEXT NOT NULL DEFAULT 'info' CHECK (`severity` IN ('info','warning','critical')),
    `description` TEXT NOT NULL,
    `ip_address` TEXT DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_security_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_security_logs_user_date` ON `security_logs` (`user_id`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_security_logs_severity` ON `security_logs` (`severity`, `created_at`);

CREATE TABLE IF NOT EXISTS `backups` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `filename` TEXT NOT NULL,
    `private_path` TEXT NOT NULL,
    `file_size_bytes` INTEGER NOT NULL DEFAULT 0,
    `created_by` INTEGER DEFAULT NULL,
    `created_at` TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT `fk_backups_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
