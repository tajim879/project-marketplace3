<?php
declare(strict_types=1);

/**
 * License Key Generator & Certificate Formatter
 */
final class License
{
    public static function generateKey(string $licenseType = 'regular'): string
    {
        $prefix = match ($licenseType) {
            'extended' => 'CVX',
            'free_personal', 'mit' => 'CVF',
            default => 'CVR',
        };
        $blocks = [
            strtoupper(bin2hex(random_bytes(2))),
            strtoupper(bin2hex(random_bytes(2))),
            strtoupper(bin2hex(random_bytes(2))),
            strtoupper(bin2hex(random_bytes(2))),
        ];
        return $prefix . '-' . implode('-', $blocks);
    }

    public static function rightsSummary(string $licenseType): array
    {
        return match ($licenseType) {
            'extended' => [
                'Single commercial or SaaS end product (end users CAN be charged)',
                'Full unencrypted source code & documentation included',
                'Free minor version updates & verified security patches',
                '6 months direct author technical support',
                'Redistribution or resale of source code is strictly prohibited',
            ],
            'free_personal', 'mit' => [
                'Unlimited personal, educational, and portfolio usage',
                'Clean inspected source package & setup notes included',
                'Access to published community updates',
                'Reselling as a standalone marketplace item is prohibited',
            ],
            default => [
                'Single end product for yourself or one client (free end-user access)',
                'Complete documented source code & database schema',
                'Free version updates from your secure Downloads vault',
                '6 months verified seller support included',
                'Redistribution or resale as a template/script is prohibited',
            ],
        };
    }
}
