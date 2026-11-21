<?php
declare(strict_types=1);

/**
 * Centralized Lucide-Style SVG Icon System
 * Strict PRD compliance: viewBox="0 0 24 24", stroke-width="1.85",
 * stroke="currentColor", fill="none", zero emojis, zero icon fonts.
 */

function icon_registry(): array
{
    static $icons = null;
    if ($icons !== null) {
        return $icons;
    }

    $icons = [
        // Navigation
        'home' => ['cat' => 'navigation', 'svg' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V19a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9.5"/><path d="M9.5 21v-6.5h5V21"/>'],
        'search' => ['cat' => 'navigation', 'svg' => '<circle cx="11" cy="11" r="7.5"/><path d="m20.5 20.5-4.3-4.3"/>'],
        'menu' => ['cat' => 'navigation', 'svg' => '<path d="M4 6.5h16"/><path d="M4 12h16"/><path d="M4 17.5h16"/>'],
        'grid' => ['cat' => 'navigation', 'svg' => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.8"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.8"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.8"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.8"/>'],
        'categories' => ['cat' => 'navigation', 'svg' => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.8"/><circle cx="17.25" cy="6.75" r="3.75"/><path d="M14 14h6.5a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-.5.5H14a.5.5 0 0 1-.5-.5v-6a.5.5 0 0 1 .5-.5Z"/><path d="M6.75 13.5 10.5 21H3l3.75-7.5Z"/>'],
        'list' => ['cat' => 'navigation', 'svg' => '<path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3.5 6h.01"/><path d="M3.5 12h.01"/><path d="M3.5 18h.01"/>'],
        'arrow-right' => ['cat' => 'navigation', 'svg' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>'],
        'arrow-left' => ['cat' => 'navigation', 'svg' => '<path d="M19 12H5"/><path d="m11 18-6-6 6-6"/>'],
        'arrow-up-right' => ['cat' => 'navigation', 'svg' => '<path d="M7 17 17 7"/><path d="M8 7h9v9"/>'],
        'chevron-down' => ['cat' => 'navigation', 'svg' => '<path d="m6 9 6 6 6-6"/>'],
        'chevron-up' => ['cat' => 'navigation', 'svg' => '<path d="m18 15-6-6-6 6"/>'],
        'chevron-left' => ['cat' => 'navigation', 'svg' => '<path d="m15 18-6-6 6-6"/>'],
        'chevron-right' => ['cat' => 'navigation', 'svg' => '<path d="m9 18 6-6-6-6"/>'],
        'external-link' => ['cat' => 'navigation', 'svg' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>'],
        'filter' => ['cat' => 'navigation', 'svg' => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>'],
        'sliders' => ['cat' => 'navigation', 'svg' => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="2" y1="14" x2="6" y2="14"/><line x1="10" y1="8" x2="14" y2="8"/><line x1="18" y1="16" x2="22" y2="16"/>'],

        // Account
        'user' => ['cat' => 'account', 'svg' => '<circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 0 0-16 0"/>'],
        'user-check' => ['cat' => 'account', 'svg' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/>'],
        'user-plus' => ['cat' => 'account', 'svg' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>'],
        'settings' => ['cat' => 'account', 'svg' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.6.77 1 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>'],
        'lock' => ['cat' => 'account', 'svg' => '<rect x="3.5" y="11" width="17" height="10" rx="2.2"/><path d="M7.5 11V7a4.5 4.5 0 0 1 9 0v4"/><circle cx="12" cy="16" r="1"/>'],
        'key' => ['cat' => 'account', 'svg' => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/>'],
        'logout' => ['cat' => 'account', 'svg' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>'],
        'eye-off' => ['cat' => 'account', 'svg' => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="2" y1="2" x2="22" y2="22"/>'],

        // Marketplace
        'shopping-bag' => ['cat' => 'marketplace', 'svg' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>'],
        'heart' => ['cat' => 'marketplace', 'svg' => '<path d="M19.5 12.57 12 20l-7.5-7.43A5 5 0 1 1 12 6a5 5 0 1 1 7.5 6.57Z"/>'],
        'tag' => ['cat' => 'marketplace', 'svg' => '<path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><line x1="7" y1="7" x2="7.01" y2="7"/>'],
        'package' => ['cat' => 'marketplace', 'svg' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>'],
        'star' => ['cat' => 'marketplace', 'svg' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
        'award' => ['cat' => 'marketplace', 'svg' => '<circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>'],
        'trending-up' => ['cat' => 'marketplace', 'svg' => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>'],
        'zap' => ['cat' => 'marketplace', 'svg' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>'],
        'sparkles' => ['cat' => 'marketplace', 'svg' => '<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/><path d="M5 3v4"/><path d="M3 5h4"/><path d="M19 17v4"/><path d="M17 19h4"/>'],
        'layers' => ['cat' => 'marketplace', 'svg' => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>'],
        'globe' => ['cat' => 'marketplace', 'svg' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/>'],
        'bookmark' => ['cat' => 'marketplace', 'svg' => '<path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16Z"/>'],

        // Project
        'code' => ['cat' => 'project', 'svg' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>'],
        'file' => ['cat' => 'project', 'svg' => '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2Z"/><polyline points="14 2 14 8 20 8"/>'],
        'file-code' => ['cat' => 'project', 'svg' => '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2Z"/><polyline points="14 2 14 8 20 8"/><path d="m10 13-2 2 2 2"/><path d="m14 17 2-2-2-2"/>'],
        'file-text' => ['cat' => 'project', 'svg' => '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/>'],
        'folder' => ['cat' => 'project', 'svg' => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>'],
        'download' => ['cat' => 'project', 'svg' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>'],
        'eye' => ['cat' => 'project', 'svg' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>'],
        'image' => ['cat' => 'project', 'svg' => '<rect x="3" y="3" width="18" height="18" rx="2.2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>'],
        'terminal' => ['cat' => 'project', 'svg' => '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>'],
        'smartphone' => ['cat' => 'project', 'svg' => '<rect x="5" y="2" width="14" height="20" rx="2.2"/><line x1="12" y1="18" x2="12.01" y2="18"/>'],
        'layout' => ['cat' => 'project', 'svg' => '<rect x="3" y="3" width="18" height="18" rx="2.2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>'],
        'cpu' => ['cat' => 'project', 'svg' => '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/>'],
        'play' => ['cat' => 'project', 'svg' => '<polygon points="6 3 20 12 6 21 6 3"/>'],
        'maximize' => ['cat' => 'project', 'svg' => '<polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/>'],
        'copy' => ['cat' => 'project', 'svg' => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>'],
        'refresh' => ['cat' => 'project', 'svg' => '<path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/>'],
        'calendar' => ['cat' => 'project', 'svg' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],

        // Seller
        'store' => ['cat' => 'seller', 'svg' => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2 2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"/>'],
        'wallet' => ['cat' => 'seller', 'svg' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>'],
        'chart' => ['cat' => 'seller', 'svg' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
        'badge-check' => ['cat' => 'seller', 'svg' => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>'],

        // Payment
        'receipt' => ['cat' => 'payment', 'svg' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>'],
        'banknote' => ['cat' => 'payment', 'svg' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>'],
        'credit-card' => ['cat' => 'payment', 'svg' => '<rect x="2" y="5" width="20" height="14" rx="2.2"/><line x1="2" y1="10" x2="22" y2="10"/>'],

        // Admin
        'shield' => ['cat' => 'admin', 'svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>'],
        'users' => ['cat' => 'admin', 'svg' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
        'database' => ['cat' => 'admin', 'svg' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>'],

        // Actions
        'plus' => ['cat' => 'actions', 'svg' => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>'],
        'minus' => ['cat' => 'actions', 'svg' => '<line x1="5" y1="12" x2="19" y2="12"/>'],
        'edit' => ['cat' => 'actions', 'svg' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>'],
        'trash' => ['cat' => 'actions', 'svg' => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>'],
        'check' => ['cat' => 'actions', 'svg' => '<polyline points="20 6 9 17 4 12"/>'],
        'close' => ['cat' => 'actions', 'svg' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>'],
        'upload' => ['cat' => 'actions', 'svg' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>'],
        'send' => ['cat' => 'actions', 'svg' => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>'],
        'flag' => ['cat' => 'actions', 'svg' => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1Z"/><line x1="4" y1="22" x2="4" y2="15"/>'],
        'share' => ['cat' => 'actions', 'svg' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>'],
        'printer' => ['cat' => 'actions', 'svg' => '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>'],

        // Communication
        'bell' => ['cat' => 'communication', 'svg' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>'],
        'message' => ['cat' => 'communication', 'svg' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/>'],
        'mail' => ['cat' => 'communication', 'svg' => '<rect x="2" y="4" width="20" height="16" rx="2.2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>'],
        'phone' => ['cat' => 'communication', 'svg' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92Z"/>'],
        'map-pin' => ['cat' => 'communication', 'svg' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>'],
        'help-circle' => ['cat' => 'communication', 'svg' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>'],
        'life-buoy' => ['cat' => 'communication', 'svg' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/><line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/><line x1="14.83" y1="9.17" x2="19.07" y2="4.93"/><line x1="4.93" y1="19.07" x2="9.17" y2="14.83"/>'],
        'quote' => ['cat' => 'communication', 'svg' => '<path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1Z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1Z"/>'],

        // Status
        'check-circle' => ['cat' => 'status', 'svg' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'],
        'alert-circle' => ['cat' => 'status', 'svg' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'],
        'clock' => ['cat' => 'status', 'svg' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
        'x-circle' => ['cat' => 'status', 'svg' => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'],
        'info' => ['cat' => 'status', 'svg' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>'],
        'loader' => ['cat' => 'status', 'svg' => '<path d="M21 12a9 9 0 1 1-6.219-8.56"/>'],
        'shield-check' => ['cat' => 'security', 'svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>'],
        'bar-chart' => ['cat' => 'commerce', 'svg' => '<line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>'],
        'percent' => ['cat' => 'commerce', 'svg' => '<line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>'],
        'log-out' => ['cat' => 'account', 'svg' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>'],
        'at-sign' => ['cat' => 'account', 'svg' => '<circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"/>'],
        'home' => ['cat' => 'nav', 'svg' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
    ];

    return $icons;
}

/**
 * Render a crisp, accessible Lucide-style SVG icon.
 */
function icon(string $name, int $size = 20, string $class = '', string $ariaLabel = ''): string
{
    $registry = icon_registry();
    $entry = $registry[$name] ?? $registry['package'];
    $classes = trim('pm-icon pm-icon-' . preg_replace('/[^a-z0-9-]/', '', $name) . ' ' . $class);
    $a11y = $ariaLabel !== ''
        ? 'role="img" aria-label="' . htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') . '"'
        : 'aria-hidden="true" focusable="false"';

    return sprintf(
        '<svg class="%s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" %s>%s</svg>',
        htmlspecialchars($classes, ENT_QUOTES, 'UTF-8'),
        $size,
        $size,
        $a11y,
        $entry['svg']
    );
}

/**
 * Render SVG star rating bar (strictly SVG, never unicode stars).
 */
function render_stars(float $rating, int $size = 15): string
{
    $html = '<span class="pm-stars" role="img" aria-label="Rated ' . number_format($rating, 1) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $filled = $rating >= ($i - 0.25);
        $half = !$filled && $rating >= ($i - 0.75);
        $stateClass = $filled ? 'is-filled' : ($half ? 'is-half' : 'is-empty');
        $html .= '<svg class="pm-icon pm-star ' . $stateClass . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="' . ($filled ? 'currentColor' : 'none') . '" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
    }
    $html .= '</span>';
    return $html;
}

/**
 * Render the custom 3D isometric CodeVault brand mark SVG.
 */
function brand_logo(string $siteName = 'CodeVault', bool $withTagline = false, string $tagline = 'Project Marketplace'): string
{
    $safeName = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
    $safeTag = htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8');
    $customLogo = function_exists('site_setting') ? site_setting('custom_logo_path', '') : '';

    if ($customLogo !== '' && is_file(ROOT_PATH . '/' . ltrim($customLogo, '/'))) {
        $src = htmlspecialchars(url($customLogo), ENT_QUOTES, 'UTF-8');
        $mark = '<span class="pm-brand-mark pm-brand-mark-custom"><img src="' . $src . '" alt="' . $safeName . '" width="28" height="28"></span>';
    } else {
        $mark = '<span class="pm-brand-mark" aria-hidden="true">'
            . '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.95" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M12 2.5 20.5 7.3v9.4L12 21.5l-8.5-4.8V7.3L12 2.5Z"/>'
            . '<path d="m9.5 10.2-2.2 1.8 2.2 1.8"/>'
            . '<path d="m14.5 10.2 2.2 1.8-2.2 1.8"/>'
            . '<path d="m12.8 9.2-1.6 5.6"/>'
            . '</svg></span>';
    }

    $text = '<span class="pm-brand-copy"><span class="pm-brand-name">' . $safeName . '</span>';
    if ($withTagline) {
        $text .= '<span class="pm-brand-tagline">' . $safeTag . '</span>';
    }
    $text .= '</span>';
    return $mark . $text;
}

/**
 * Render authentic, high-clarity vector SVG brand logo for bKash, Nagad & Rocket.
 * Supports generous heights (34px–58px) with true multi-facet brand geometry & colors.
 */
function payment_brand_svg(string $code, int $height = 42, bool $withWordmark = true): string
{
    $code = strtolower(trim($code));
    // Normalize aliases like "bKash" or "DBBL Rocket"
    if (str_contains($code, 'bkash')) {
        $code = 'bkash';
    } elseif (str_contains($code, 'nagad')) {
        $code = 'nagad';
    } elseif (str_contains($code, 'rocket')) {
        $code = 'rocket';
    }

    if ($code === 'bkash') {
        if (!$withWordmark) {
            $w = (int)round($height * 1.32);
            return '<svg class="pm-pay-svg pm-pay-svg-bkash" width="' . $w . '" height="' . $height . '" viewBox="0 0 88 66" fill="none" role="img" aria-label="bKash">'
                . '<polygon points="6,11 25,30 13,11" fill="#9E1638"/>'
                . '<polygon points="6,2 43,7 34,36" fill="#D12053"/>'
                . '<polygon points="44.3,7.4 35.3,36.2 69.8,41.6" fill="#E2136E"/>'
                . '<polygon points="35.3,36.9 69.5,42.3 40,53.4" fill="#D61E5C"/>'
                . '<polygon points="70.2,43.3 68.5,46.8 42.4,54.4" fill="#F01E7A"/>'
                . '<polygon points="34.2,37.4 39.4,54.5 23.4,62" fill="#9E1638"/>'
                . '<polygon points="59.6,24.8 77,21.5 70.5,40.8" fill="#D91D58"/>'
                . '<polygon points="78.2,22 86,30 75.6,30" fill="#E2136E"/>'
                . '</svg>';
        }
        $w = (int)round($height * 3.05);
        return '<svg class="pm-pay-svg pm-pay-svg-bkash" width="' . $w . '" height="' . $height . '" viewBox="0 0 208 68" fill="none" role="img" aria-label="bKash">'
            . '<g transform="translate(2, 2)">'
            . '<polygon points="6,11 25,30 13,11" fill="#9E1638"/>'
            . '<polygon points="6,2 43,7 34,36" fill="#D12053"/>'
            . '<polygon points="44.3,7.4 35.3,36.2 69.8,41.6" fill="#E2136E"/>'
            . '<polygon points="35.3,36.9 69.5,42.3 40,53.4" fill="#D61E5C"/>'
            . '<polygon points="70.2,43.3 68.5,46.8 42.4,54.4" fill="#F01E7A"/>'
            . '<polygon points="34.2,37.4 39.4,54.5 23.4,62" fill="#9E1638"/>'
            . '<polygon points="59.6,24.8 77,21.5 70.5,40.8" fill="#D91D58"/>'
            . '<polygon points="78.2,22 86,30 75.6,30" fill="#E2136E"/>'
            . '</g>'
            . '<text x="93" y="48" font-family="Inter, Manrope, -apple-system, sans-serif" font-size="38" font-weight="800" letter-spacing="-1.1" fill="#E2136E">b<tspan fill="#202438" font-weight="600" letter-spacing="-0.7">Kash</tspan></text>'
            . '</svg>';
    }

    if ($code === 'nagad') {
        if (!$withWordmark) {
            return '<svg class="pm-pay-svg pm-pay-svg-nagad" width="' . $height . '" height="' . $height . '" viewBox="0 0 66 66" fill="none" role="img" aria-label="Nagad">'
                . '<path d="M18.5 11.2C7.8 18.4 2 30.8 4.4 43.8C7.1 57.6 20.1 65.2 34.4 63.6C49.2 62 59.8 49.8 58.6 34.8L53.8 38.2C53.6 49.6 44.5 58.6 32.8 58.8C20.2 59 10.2 49.4 9.6 36.8C9.1 26.4 13.4 17.8 18.5 11.2Z" fill="#ED1C24"/>'
                . '<path d="M18.2 12.2C10.8 21.8 9.2 35.6 15.6 46.2C22.4 57.4 37.8 59.6 47.8 50.8C54.8 44.6 56.8 34.2 52.2 26.2L40.6 24.4C46.2 29.2 46.8 38.4 41.4 43.8C35.8 49.4 26.2 48.8 21.2 42.4C16.8 36.6 16.4 28.4 19.8 21.8L18.2 12.2Z" fill="#F7941D"/>'
                . '<path d="M14.8 34.2C14.2 21.4 20.6 9.2 31.8 2L38.2 12.4C27.2 17.2 19.2 24.8 14.8 34.2Z" fill="#F7941D" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round"/>'
                . '<path d="M20.2 29.6C24.4 17.6 34.6 5.2 49.4 3.6L50.8 18.6C38.4 17.8 27.6 22.2 20.2 29.6Z" fill="#ED1C24" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round"/>'
                . '<path d="M24.8 27.8C34.2 18.2 48.8 14.2 62.6 18.4L54.6 31.2C45.6 24.2 34.2 23.6 24.8 27.8Z" fill="#F7941D" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round"/>'
                . '<g fill="#ED1C24"><circle cx="31.2" cy="29.4" r="2.3"/><path d="M32.8 30.8C35.4 30.6 37.2 32.8 36.6 35.8C36.1 37.8 34.4 38.4 32.8 38.1Z"/><path d="M29.4 32.2L33.4 32.6L34.4 39.2L38.8 43.2L41.8 42.6L41.6 45.4L37.4 45.2L33.4 41.4L30.8 43.2L30.6 48.8L28.2 49.2L28.8 42.2L31.2 39.2Z"/><rect x="21.4" y="36.2" width="19.6" height="1.2" rx="0.6"/><polygon points="19.4,36.8 22.6,35.6 22.6,38"/></g>'
                . '</svg>';
        }
        $w = (int)round($height * 3.12);
        return '<svg class="pm-pay-svg pm-pay-svg-nagad" width="' . $w . '" height="' . $height . '" viewBox="0 0 212 68" fill="none" role="img" aria-label="Nagad">'
            . '<g transform="translate(2, 2)">'
            . '<path d="M18.5 11.2C7.8 18.4 2 30.8 4.4 43.8C7.1 57.6 20.1 65.2 34.4 63.6C49.2 62 59.8 49.8 58.6 34.8L53.8 38.2C53.6 49.6 44.5 58.6 32.8 58.8C20.2 59 10.2 49.4 9.6 36.8C9.1 26.4 13.4 17.8 18.5 11.2Z" fill="#ED1C24"/>'
            . '<path d="M18.2 12.2C10.8 21.8 9.2 35.6 15.6 46.2C22.4 57.4 37.8 59.6 47.8 50.8C54.8 44.6 56.8 34.2 52.2 26.2L40.6 24.4C46.2 29.2 46.8 38.4 41.4 43.8C35.8 49.4 26.2 48.8 21.2 42.4C16.8 36.6 16.4 28.4 19.8 21.8L18.2 12.2Z" fill="#F7941D"/>'
            . '<path d="M14.8 34.2C14.2 21.4 20.6 9.2 31.8 2L38.2 12.4C27.2 17.2 19.2 24.8 14.8 34.2Z" fill="#F7941D" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round"/>'
            . '<path d="M20.2 29.6C24.4 17.6 34.6 5.2 49.4 3.6L50.8 18.6C38.4 17.8 27.6 22.2 20.2 29.6Z" fill="#ED1C24" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round"/>'
            . '<path d="M24.8 27.8C34.2 18.2 48.8 14.2 62.6 18.4L54.6 31.2C45.6 24.2 34.2 23.6 24.8 27.8Z" fill="#F7941D" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round"/>'
            . '<g fill="#ED1C24"><circle cx="31.2" cy="29.4" r="2.3"/><path d="M32.8 30.8C35.4 30.6 37.2 32.8 36.6 35.8C36.1 37.8 34.4 38.4 32.8 38.1Z"/><path d="M29.4 32.2L33.4 32.6L34.4 39.2L38.8 43.2L41.8 42.6L41.6 45.4L37.4 45.2L33.4 41.4L30.8 43.2L30.6 48.8L28.2 49.2L28.8 42.2L31.2 39.2Z"/><rect x="21.4" y="36.2" width="19.6" height="1.2" rx="0.6"/><polygon points="19.4,36.8 22.6,35.6 22.6,38"/></g>'
            . '</g>'
            . '<text x="72" y="44" font-family="Manrope, Inter, -apple-system, sans-serif" font-size="35" font-weight="800" letter-spacing="-0.8" fill="#ED1C24">Nagad</text>'
            . '<text x="73" y="57" font-family="Inter, sans-serif" font-size="9" font-weight="700" letter-spacing="1.3" fill="#F26522">DIGITAL PAYMENT</text>'
            . '</svg>';
    }

    if ($code === 'rocket') {
        if (!$withWordmark) {
            return '<svg class="pm-pay-svg pm-pay-svg-rocket" width="' . $height . '" height="' . $height . '" viewBox="0 0 66 60" fill="none" role="img" aria-label="Rocket">'
                . '<polygon points="3,28.5 63,2 29.5,36.8" fill="#8C3494"/>'
                . '<polygon points="30.8,38.4 48.6,17.8 35.6,48.6 32.2,55.2" fill="#6D2275"/>'
                . '<polygon points="37.2,41.6 63,2 50,55.8" fill="#8C3494"/>'
                . '<line x1="30.4" y1="37.4" x2="61.2" y2="3.8" stroke="#FFFFFF" stroke-width="1.9" stroke-linecap="round"/>'
                . '</svg>';
        }
        $w = (int)round($height * 3.15);
        return '<svg class="pm-pay-svg pm-pay-svg-rocket" width="' . $w . '" height="' . $height . '" viewBox="0 0 214 68" fill="none" role="img" aria-label="Rocket">'
            . '<g transform="translate(2, 5)">'
            . '<polygon points="3,28.5 63,2 29.5,36.8" fill="#8C3494"/>'
            . '<polygon points="30.8,38.4 48.6,17.8 35.6,48.6 32.2,55.2" fill="#6D2275"/>'
            . '<polygon points="37.2,41.6 63,2 50,55.8" fill="#8C3494"/>'
            . '<line x1="30.4" y1="37.4" x2="61.2" y2="3.8" stroke="#FFFFFF" stroke-width="1.9" stroke-linecap="round"/>'
            . '</g>'
            . '<text x="74" y="42" font-family="Manrope, Inter, -apple-system, sans-serif" font-size="30" font-weight="800" letter-spacing="0.4" fill="#8C3494">ROCKET</text>'
            . '<text x="75" y="56" font-family="Inter, sans-serif" font-size="9" font-weight="700" letter-spacing="1.05" fill="#6D2275">DBBL MOBILE BANKING</text>'
            . '</svg>';
    }

    return icon('wallet', max(20, (int)round($height * 0.65))) . '<span>' . htmlspecialchars(ucfirst($code), ENT_QUOTES, 'UTF-8') . '</span>';
}

/**
 * Render a raised soft-neumorphic payment method brand badge (sm / md / lg / xl).
 */
function payment_brand_badge(string $code, string $size = 'md'): string
{
    $norm = strtolower(trim($code));
    if (str_contains($norm, 'bkash')) {
        $norm = 'bkash';
    } elseif (str_contains($norm, 'nagad')) {
        $norm = 'nagad';
    } elseif (str_contains($norm, 'rocket')) {
        $norm = 'rocket';
    }
    $heights = ['sm' => 28, 'md' => 38, 'lg' => 46, 'xl' => 54];
    $h = $heights[$size] ?? 38;
    $safe = htmlspecialchars(preg_replace('/[^a-z0-9-]/', '', $norm) ?: 'manual', ENT_QUOTES, 'UTF-8');
    return '<span class="pm-pay-badge pm-pay-badge-' . $safe . ' pm-pay-badge--' . htmlspecialchars($size, ENT_QUOTES, 'UTF-8') . '">'
        . payment_brand_svg($norm, $h, true)
        . '</span>';
}



/**
 * Strip anything dangerous from admin-supplied SVG markup.
 * Returns '' when the markup is not a usable <svg> element.
 */
function sanitize_svg(string $svg): string
{
    $svg = trim($svg);
    if ($svg === '' || stripos($svg, '<svg') === false) {
        return '';
    }
    $svg = preg_replace('/<\?xml[^>]*\?>|<!DOCTYPE[^>]*>|<!--.*?-->/is', '', $svg) ?? '';
    $start = stripos($svg, '<svg');
    $end = strripos($svg, '</svg>');
    if ($start === false || $end === false) {
        return '';
    }
    $svg = substr($svg, $start, $end + 6 - $start);
    $svg = preg_replace('#<(script|style|foreignObject|iframe|object|embed|audio|video|animate|set)\b.*?</\1\s*>#is', '', $svg) ?? '';
    $svg = preg_replace('#<(script|style|foreignObject|iframe|object|embed|audio|video|animate|set)\b[^>]*/?>#is', '', $svg) ?? '';
    $svg = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg) ?? '';
    $svg = preg_replace('/\s(xlink:href|href)\s*=\s*("\s*javascript:[^"]*"|\'\s*javascript:[^\']*\')/i', '', $svg) ?? '';
    return trim($svg);
}

/**
 * Validate a user-provided image URL (http/https or site-relative path).
 */
function safe_image_url(string $value): string
{
    $value = trim($value);
    if ($value === '' || preg_match('/[\s"\'<>]/', $value)) {
        return '';
    }
    if (preg_match('#^https?://#i', $value) || str_starts_with($value, '/')) {
        return $value;
    }
    return '';
}

/**
 * Render a category's visual: custom image / image URL / SVG code, or built-in icon fallback.
 */
function category_icon_html(array $category, int $size = 22): string
{
    $type = (string)($category['icon_type'] ?? 'icon');
    $name = (string)($category['name'] ?? '');

    if ($type === 'svg') {
        $svg = sanitize_svg((string)($category['icon_svg'] ?? ''));
        if ($svg !== '') {
            return '<span class="pm-cat-svg" aria-hidden="true">' . $svg . '</span>';
        }
    } elseif ($type === 'image' || $type === 'url') {
        $src = trim((string)($category['icon_image'] ?? ''));
        if ($src !== '') {
            $src = preg_match('#^https?://#i', $src) || str_starts_with($src, '/') ? $src : url($src);
            return '<img class="pm-cat-img" src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8')
                . '" alt="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" loading="lazy" decoding="async">';
        }
    }

    return icon((string)($category['icon_name'] ?? 'code'), $size);
}
