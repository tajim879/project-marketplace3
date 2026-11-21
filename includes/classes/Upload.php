<?php
declare(strict_types=1);

/**
 * Upload Validation & ZIP Archive Security Inspector
 */
final class Upload
{
    private const BLOCKED_ARCHIVE_PATTERNS = [
        '/\.\.\//',
        '/^\/+/',
        '/\.phar$/i',
        '/\.phtml$/i',
        '/\.exe$/i',
        '/\.bat$/i',
        '/\.cmd$/i',
        '/\.scr$/i',
        '/\.vbs$/i',
        '/\.dll$/i',
        '/\.so$/i',
    ];

    public static function validateImage(array $file, int $maxMb = 5): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'Image upload failed. Please choose a valid image file.'];
        }

        $maxBytes = $maxMb * 1024 * 1024;
        if ((int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > $maxBytes) {
            return ['valid' => false, 'message' => "Image must be smaller than {$maxMb} MB."];
        }

        $tmp = (string)$file['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            return ['valid' => false, 'message' => 'Invalid uploaded image source.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedMimes[$mime])) {
            return ['valid' => false, 'message' => 'Only JPG, PNG, and WebP images are permitted.'];
        }

        $imgInfo = @getimagesize($tmp);
        if ($imgInfo === false || $imgInfo[0] < 32 || $imgInfo[1] < 32 || $imgInfo[0] > 8000 || $imgInfo[1] > 8000) {
            return ['valid' => false, 'message' => 'Uploaded image dimensions are invalid.'];
        }

        return [
            'valid' => true,
            'mime' => $mime,
            'extension' => $allowedMimes[$mime],
            'width' => $imgInfo[0],
            'height' => $imgInfo[1],
        ];
    }

    /**
     * Inspect uploaded ZIP files against path traversal, zip bombs, and dangerous binaries.
     */
    public static function inspectZip(string $filePath, int $maxMb = 100): array
    {
        if (!file_exists($filePath) || filesize($filePath) === 0) {
            return ['valid' => false, 'message' => 'ZIP file is empty or unreadable.'];
        }

        if (filesize($filePath) > $maxMb * 1024 * 1024) {
            return ['valid' => false, 'message' => "ZIP file exceeds the {$maxMb} MB limit."];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($filePath);
        if (!in_array($mime, ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'], true)) {
            return ['valid' => false, 'message' => 'Only standard ZIP archives are accepted.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::RDONLY) !== true) {
            return ['valid' => false, 'message' => 'Archive is corrupted or cannot be opened.'];
        }

        $totalUncompressed = 0;
        $maxUncompressed = $maxMb * 5 * 1024 * 1024;
        $count = $zip->numFiles;

        if ($count === 0 || $count > 10000) {
            $zip->close();
            return ['valid' => false, 'message' => 'ZIP archive contains an invalid number of entries.'];
        }

        for ($i = 0; $i < $count; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat) {
                continue;
            }
            $name = (string)$stat['name'];
            foreach (self::BLOCKED_ARCHIVE_PATTERNS as $pattern) {
                if (preg_match($pattern, $name)) {
                    $zip->close();
                    return ['valid' => false, 'message' => 'ZIP archive contains disallowed or unsafe file paths.'];
                }
            }
            $totalUncompressed += (int)($stat['size'] ?? 0);
            if ($totalUncompressed > $maxUncompressed) {
                $zip->close();
                return ['valid' => false, 'message' => 'ZIP archive exceeds safe uncompressed size threshold.'];
            }
        }

        $zip->close();
        return [
            'valid' => true,
            'mime' => 'application/zip',
            'files_count' => $count,
            'checksum' => hash_file('sha256', $filePath),
        ];
    }
}
