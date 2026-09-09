<?php
/**
 * Zero LMS - Upload Security Helper
 * Provides strict validation and safe storage for uploaded user content.
 */

if (!function_exists('is_safe_upload')) {
    /**
     * Validate an uploaded file against allowed extensions and MIME types.
     *
     * @param array $file The $_FILES['field'] array
     * @param array $allowed_exts Whitelist of allowed extensions (lowercase)
     * @param array $allowed_mimes Whitelist of allowed MIME types
     * @param int $max_bytes Maximum allowed file size in bytes (default: 20MB)
     * @return array [bool $valid, string $error_message, string $safe_extension]
     */
    function validate_uploaded_file($file, array $allowed_exts, array $allowed_mimes = [], $max_bytes = 20971520) {
        if (!isset($file['error']) || is_array($file['error'])) {
            return [false, 'پارامترهای فایل نامعتبر است.', ''];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [false, 'خطا در ارسال فایل (کد ' . $file['error'] . ')', ''];
        }

        if ($file['size'] > $max_bytes) {
            return [false, 'حجم فایل بیش از حد مجاز است.', ''];
        }

        $orig_name = $file['name'] ?? '';
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        // Block dangerous extensions unconditionally
        $blocked_exts = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phar', 'sh', 'pl', 'py', 'cgi', 'asp', 'aspx', 'exe', 'bat', 'cmd', 'js', 'html', 'htm'];
        if (in_array($ext, $blocked_exts, true) || !in_array($ext, $allowed_exts, true)) {
            return [false, 'فرمت فایل ارسالی مجاز نیست.', ''];
        }

        // Validate MIME type if file exists in tmp
        if (!empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!empty($allowed_mimes) && !in_array($mime, $allowed_mimes, true)) {
                return [false, 'نوع محتوای فایل نامعتبر است.', ''];
            }
        }

        return [true, '', $ext];
    }

    /**
     * Store an uploaded file with a cryptographically randomized name.
     *
     * @param array $file
     * @param string $target_directory Absolute or relative directory to store file
     * @param array $allowed_exts
     * @param array $allowed_mimes
     * @param string $prefix
     * @param int $max_bytes
     * @return array [bool $success, string $file_name_or_error, string $relative_path]
     */
    function store_safe_upload($file, $target_directory, array $allowed_exts, array $allowed_mimes = [], $prefix = 'file_', $max_bytes = 20971520) {
        list($valid, $err, $ext) = validate_uploaded_file($file, $allowed_exts, $allowed_mimes, $max_bytes);
        if (!$valid) {
            return [false, $err, ''];
        }

        if (!is_dir($target_directory)) {
            mkdir($target_directory, 0755, true);
        }

        $safe_filename = $prefix . bin2hex(random_bytes(12)) . '.' . $ext;
        $destination = rtrim($target_directory, '/') . '/' . $safe_filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return [true, $safe_filename, $destination];
        }

        return [false, 'خطا در ذخیره‌سازی فایل روی سرور.', ''];
    }
}
