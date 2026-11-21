# CodeVault Marketplace — System Architecture & Security Guide

## 1. Zero-Setup Portable External SQLite 3 Architecture
- **No manual installer (`install.php`) and zero database files inside the webroot.**
- On the very first request anywhere the folder is deployed, `includes/database.php` (`Database::connection()`) and `includes/classes/AutoBootstrap.php` automatically resolve a private data directory strictly **outside** the project root (`dirname($realRoot)/.codevault-data`, or custom `MARKETPLACE_DB_PATH` / `MARKETPLACE_DATA_DIR`), acquire an exclusive `flock()`, execute `database/migrations/001_schema.sql` (WAL mode + `PRAGMA foreign_keys = ON`), and seed essential system settings, 14 digital product categories, bKash/Nagad/Rocket gateways, navigation menus, homepage section definitions, FAQs, and legal CMS pages.
- **Zero demo accounts or synthetic records** are ever created.

## 2. Role-Based Access Control (RBAC)
1. **Guest:** Browse/search/filter marketplace, inspect project details, view seller stores, read CMS pages/FAQs, register as **Buyer** or **Seller**.
2. **Buyer (`buyer/*`):** Cart, coupon codes, checkout, manual bKash/Nagad/Rocket payment & proof submission, order tracking, printable invoices, gated ZIP downloads + license keys, verified reviews, Q&A, wishlist, support tickets, profile & session management.
3. **Seller (`seller/*`):** Full Creator Studio — upload inspected ZIP archives + cover thumbnails + gallery screenshots, manage pricing (Paid BDT & Free), push new version releases & changelogs (auto-notifies buyers), track sales, view 10%/90% commission breakdown & immutable financial ledger, request bKash/Nagad/Rocket withdrawals, answer Q&A, reply to reviews, and customize public storefront & payout wallet.
4. **Admin (`admin.php` + `admin/*`):** Root portal at `/admin.php` handles one-time Owner Admin initialization (permanently locks after first admin is created) and Administrator Sign-In, routing authenticated admins into the 18-module Control Center covering executive KPIs, manual bKash/Nagad/Rocket payment verification, orders & refunds, commissions & immutable financial ledger, seller withdrawals, payment method config, coupons, project moderation & private ZIP audit, categories, sellers & custom commission rates, users & roles, support helpdesk, reviews/Q&A/reports, broadcast notifications, complete Website/Homepage/Banner/Menu Control Center, CMS pages/FAQs/newsletter CSV export, audit/security logs, and one-click `VACUUM INTO` SQLite backup download.

## 3. Security Controls
- Password hashing via Argon2id / strong Bcrypt (`Auth::hashPassword()`), session fixation rotation, HttpOnly + SameSite=Lax cookies, active session tracking & revocation, and IP/email brute-force login throttling.
- 100% PDO prepared statements across all repositories, services, actions, and views.
- Mandatory CSRF token verification on all state-changing POST forms and JSON endpoints.
- Deep ZIP upload inspection (`Upload` + `UploadService`) blocking executable scripts, PHP webshells, and Zip-Slip path traversal; real MIME/GD verification on all uploaded images.
- Private file confinement: `storage/projects/` and `storage/payment-proofs/` are protected with `.htaccess` deny rules and streamed solely through authorization-checked `download.php` and `payment-proof.php`.
- Exact fixed-point string arithmetic (`bcmath`) inside atomic SQLite transactions for all order totals, coupon discounts, commission splits, seller wallet credits, withdrawals, and refunds.
