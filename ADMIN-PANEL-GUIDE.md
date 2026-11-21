# Marketplace Owner Admin Panel

The owner control panel is part of the same PHP application and uses the same SQLite database as the storefront, buyer area, and seller studio. It is not a separate mockup or a disconnected dashboard.

## Access

- Admin sign-in: `/admin.php`
- Admin dashboard: `/admin/dashboard.php`
- Project moderation and editor: `/admin/projects.php` → **Inspect** → **Edit Project Details**
- Seller management: `/admin/sellers.php`
- Category management: `/admin/categories.php`
- Website customization: `/admin/website-control.php`

## First deployment / owner account

Set these environment variables in Render before the first admin sign-in:

- `MARKETPLACE_ADMIN_EMAIL`: your owner email address
- `MARKETPLACE_ADMIN_PASSWORD`: a unique password of at least 12 characters

Do not use a sample/default password. Keep these values private. Existing admin accounts in an already initialized persistent database are preserved; changing the environment variables does not silently overwrite an existing account password. Use **Users & Roles → Reset PW** while signed in to rotate an existing admin password.

## Admin capabilities

The panel includes dashboard metrics, payment verification, orders, invoices, commissions, withdrawals, payment methods, coupons, project moderation, project metadata editing (including live demo/video/documentation URLs), categories, seller approval and verification, seller commissions, user roles/status/password resets, support tickets, reviews/reports, notifications, website branding, homepage sections, banners, menus, CMS pages/FAQs, maintenance mode, registration controls, upload limits, audit logs, and database backup tools.

All administrative POST actions use the application's CSRF protection and administrator session checks.
