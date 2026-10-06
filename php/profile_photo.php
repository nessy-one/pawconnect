<?php
// php/profile_photo.php

define('PROFILE_PHOTO_DIR', __DIR__ . '/uploads/profile_photos/');
define('PROFILE_PHOTO_URL_PATH', '/pawconnect/php/uploads/profile_photos/');
function savedProfilePhotoPath(int $userId): string {
    return PROFILE_PHOTO_DIR . $userId . '.jpg';
}

function profilePhotoUrl(int $userId): ?string {
    $path = savedProfilePhotoPath($userId);
    if (!file_exists($path)) return null;
    return PROFILE_PHOTO_URL_PATH . $userId . '.jpg?v=' . filemtime($path);
}

function processProfilePhotoUpload(int $userId, array $file): array {
    if (!extension_loaded('gd')) {
        return ['success' => false, 'message' => 'GD image library not enabled. In XAMPP: open php.ini, remove the semicolon from ";extension=gd", then restart Apache.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error code: ' . $file['error']];
    }

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large (max 5 MB).'];
    }

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($file['tmp_name']);
    if (!in_array($mimeReal, $allowed, true)) {
        return ['success' => false, 'message' => 'Only JPEG, PNG, GIF, WEBP allowed.'];
    }

    if (!is_dir(PROFILE_PHOTO_DIR)) {
        mkdir(PROFILE_PHOTO_DIR, 0755, true);
    }

    $src = match ($mimeReal) {
        'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
        'image/png'  => imagecreatefrompng($file['tmp_name']),
        'image/gif'  => imagecreatefromgif($file['tmp_name']),
        'image/webp' => imagecreatefromwebp($file['tmp_name']),
        default      => null,
    };
    if (!$src) {
        return ['success' => false, 'message' => 'Could not process image.'];
    }

    imagejpeg($src, savedProfilePhotoPath($userId), 90);
    imagedestroy($src);

    return ['success' => true, 'message' => 'Photo updated.', 'photo' => profilePhotoUrl($userId)];
}

function removeProfilePhoto(int $userId): void {
    $path = savedProfilePhotoPath($userId);
    if (file_exists($path)) unlink($path);
}