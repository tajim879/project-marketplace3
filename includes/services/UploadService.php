<?php
declare(strict_types=1);

/**
 * Upload Service - Strictly validates & stores avatars, thumbnails, screenshots,
 * brand assets, private payment receipts, support attachments, and private project ZIPs.
 */
final class UploadService
{
    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    private static function persistFile(string $tmp, string $dest): bool
    {
        if (is_uploaded_file($tmp)) {
            return move_uploaded_file($tmp, $dest);
        }
        return false;
    }

    public static function storeCategoryImage(array $file, string $slug): array
    {
        $check = Upload::validateImage($file, 3);
        if (!$check['valid']) {
            return ['success' => false, 'message' => $check['message']];
        }

        $dir = STORAGE_PATH . '/category-icons';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $safeSlug = mb_substr(slugify($slug) ?: 'category', 0, 40);
        $filename = $safeSlug . '-' . bin2hex(random_bytes(5)) . '.' . $check['extension'];
        $dest = $dir . '/' . $filename;
        if (!self::persistFile((string)$file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Could not save category image.'];
        }

        @chmod($dest, 0644);
        return ['success' => true, 'path' => 'storage/category-icons/' . $filename];
    }

    public static function storeAvatar(array $file, int $userId): array
    {
        $maxMb = max(1, min(10, (int)site_setting('max_image_mb', '5')));
        $check = Upload::validateImage($file, $maxMb);
        if (!$check['valid']) {
            return ['success' => false, 'message' => $check['message']];
        }

        if (!is_dir(AVATARS_PATH)) {
            @mkdir(AVATARS_PATH, 0755, true);
        }

        $filename = 'avatar-' . $userId . '-' . bin2hex(random_bytes(8)) . '.' . $check['extension'];
        $dest = AVATARS_PATH . '/' . $filename;
        if (!self::persistFile((string)$file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Failed to save avatar image.'];
        }

        @chmod($dest, 0644);
        return ['success' => true, 'path' => 'storage/avatars/' . $filename];
    }

    public static function storeProjectThumbnail(array $file, string $slug): array
    {
        $maxMb = max(1, min(15, (int)site_setting('max_image_mb', '5')));
        $check = Upload::validateImage($file, $maxMb);
        if (!$check['valid']) {
            return ['success' => false, 'message' => 'Thumbnail: ' . $check['message']];
        }

        if (!is_dir(THUMBNAILS_PATH)) {
            @mkdir(THUMBNAILS_PATH, 0755, true);
        }

        $safeSlug = mb_substr(slugify($slug) ?: 'project', 0, 48);
        $filename = $safeSlug . '-' . bin2hex(random_bytes(6)) . '.' . $check['extension'];
        $dest = THUMBNAILS_PATH . '/' . $filename;
        if (!self::persistFile((string)$file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Could not save project thumbnail.'];
        }

        @chmod($dest, 0644);
        return ['success' => true, 'path' => 'storage/thumbnails/' . $filename];
    }

    public static function storeProjectScreenshot(array $file, string $slug, int $index = 1): array
    {
        $maxMb = max(1, min(15, (int)site_setting('max_image_mb', '5')));
        $check = Upload::validateImage($file, $maxMb);
        if (!$check['valid']) {
            return ['success' => false, 'message' => "Screenshot #{$index}: " . $check['message']];
        }

        if (!is_dir(SCREENSHOTS_PATH)) {
            @mkdir(SCREENSHOTS_PATH, 0755, true);
        }

        $safeSlug = mb_substr(slugify($slug) ?: 'project', 0, 44);
        $filename = $safeSlug . '-s' . $index . '-' . bin2hex(random_bytes(6)) . '.' . $check['extension'];
        $dest = SCREENSHOTS_PATH . '/' . $filename;
        if (!self::persistFile((string)$file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => "Could not save screenshot #{$index}."];
        }

        @chmod($dest, 0644);
        return ['success' => true, 'path' => 'storage/screenshots/' . $filename];
    }

    public static function storeProjectZip(array $file, string $slug, string $version): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Please select a valid .zip archive for your project.'];
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) {
            return ['success' => false, 'message' => 'Invalid project archive upload source.'];
        }

        $maxMb = max(5, min(500, (int)site_setting('max_project_zip_mb', '100')));
        $check = Upload::inspectZip($tmp, $maxMb);
        if (!$check['valid']) {
            return ['success' => false, 'message' => $check['message']];
        }

        if (!is_dir(PRIVATE_PROJECTS_PATH)) {
            @mkdir(PRIVATE_PROJECTS_PATH, 0750, true);
        }

        $sizeBytes = (int)filesize($tmp);
        $safeSlug = mb_substr(slugify($slug) ?: 'project', 0, 44);
        $safeVer = preg_replace('/[^0-9a-zA-Z._-]/', '', $version) ?: '1.0.0';
        $storedName = $safeSlug . '-v' . $safeVer . '-' . bin2hex(random_bytes(8)) . '.zip';
        $dest = PRIVATE_PROJECTS_PATH . '/' . $storedName;

        if (!self::persistFile($tmp, $dest)) {
            return ['success' => false, 'message' => 'Could not save project ZIP into private storage.'];
        }

        @chmod($dest, 0640);
        $origName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string)($file['name'] ?? $storedName))) ?: $storedName;

        return [
            'success' => true,
            'original_name' => mb_substr($origName, 0, 150),
            'stored_name' => $storedName,
            'private_path' => 'storage/projects/' . $storedName,
            'mime_type' => 'application/zip',
            'file_size_bytes' => $sizeBytes,
            'file_size_label' => self::formatBytes($sizeBytes),
            'checksum_sha256' => (string)$check['checksum'],
            'files_count' => (int)$check['files_count'],
        ];
    }

    public static function storePaymentProof(array $file, int $orderId, int $buyerId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'uploaded' => false];
        }

        $maxMb = max(1, min(10, (int)site_setting('max_proof_mb', '5')));
        $check = Upload::validateImage($file, $maxMb);
        if (!$check['valid']) {
            return ['success' => false, 'message' => 'Payment screenshot: ' . $check['message']];
        }

        if (!is_dir(PAYMENT_PROOFS_PATH)) {
            @mkdir(PAYMENT_PROOFS_PATH, 0750, true);
        }

        $storedName = 'proof-o' . $orderId . '-u' . $buyerId . '-' . bin2hex(random_bytes(8)) . '.' . $check['extension'];
        $dest = PAYMENT_PROOFS_PATH . '/' . $storedName;
        $sizeBytes = (int)filesize((string)$file['tmp_name']);

        if (!self::persistFile((string)$file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Could not store payment proof screenshot.'];
        }

        @chmod($dest, 0640);
        $origName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string)($file['name'] ?? $storedName))) ?: $storedName;

        return [
            'success' => true,
            'uploaded' => true,
            'original_name' => mb_substr($origName, 0, 140),
            'stored_name' => $storedName,
            'private_path' => 'storage/payment-proofs/' . $storedName,
            'mime_type' => (string)$check['mime'],
            'file_size_bytes' => $sizeBytes,
        ];
    }

    public static function storeBrandAsset(array $file, string $prefix = 'logo'): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'uploaded' => false, 'path' => null];
        }

        $check = Upload::validateImage($file, 3);
        if (!$check['valid']) {
            return ['success' => false, 'message' => 'Brand image must be a valid PNG, JPG, or WebP under 3 MB.'];
        }

        $dir = ROOT_PATH . '/assets/images/logo';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filename = preg_replace('/[^a-z0-9-]/', '', $prefix) . '-' . bin2hex(random_bytes(6)) . '.' . $check['extension'];
        $dest = $dir . '/' . $filename;
        if (!self::persistFile((string)$file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Failed to save brand image.'];
        }

        @chmod($dest, 0644);
        return ['success' => true, 'uploaded' => true, 'path' => 'assets/images/logo/' . $filename];
    }

    public static function storeSupportAttachment(array $file, int $userId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'path' => null, 'name' => null];
        }

        $check = Upload::validateImage($file, 5);
        if (!$check['valid']) {
            return ['success' => false, 'message' => 'Ticket attachment must be a JPG, PNG, or WebP screenshot under 5 MB.'];
        }

        $dir = PRIVATE_PATH . '/ticket-attachments';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        $filename = 'ticket-' . $userId . '-' . bin2hex(random_bytes(8)) . '.' . $check['extension'];
        $dest = $dir . '/' . $filename;
        if (!self::persistFile((string)$file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Could not store attachment.'];
        }

        @chmod($dest, 0640);
        $safeOrig = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string)$file['name'])) ?: $filename;
        return [
            'success' => true,
            'path' => 'storage/private/ticket-attachments/' . $filename,
            'name' => mb_substr($safeOrig, 0, 120),
        ];
    }
}
