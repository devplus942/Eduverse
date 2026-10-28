<?php
declare(strict_types=1);

class Upload
{
    public const EXT = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'mp4', 'png', 'jpg', 'jpeg'];

    public static function maxBytes(): int { return (int)Env::get('MAX_UPLOAD_MB', '20') * 1024 * 1024; }

    /** Returns an error message, or null when the file name and size are acceptable. */
    public static function validate(string $name, int $size, int $max, array $allowed = self::EXT): ?string
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, $allowed, true)) return 'File type not allowed. Allowed: ' . implode(', ', $allowed) . '.';
        if ($size <= 0) return 'The file is empty.';
        if ($size > $max) return 'The file is larger than the allowed limit.';
        return null;
    }

    /** Stores a $_FILES entry outside the web root. @return array{0:?string,1:?string,2:?string} [path, original name, error] */
    public static function store(?array $file, string $sub): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null, null];
        if ($file['error'] !== UPLOAD_ERR_OK) return [null, null, 'Upload failed (error code ' . (int)$file['error'] . ').'];
        $err = self::validate((string)$file['name'], (int)$file['size'], self::maxBytes());
        if ($err) return [null, null, $err];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $dir = ROOT . '/storage/uploads/' . $sub;
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) return [null, null, 'Could not create upload directory.'];
        $rel = $sub . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], ROOT . '/storage/uploads/' . $rel)) return [null, null, 'Could not save the uploaded file.'];
        return [$rel, mb_substr(basename((string)$file['name']), 0, 200), null];
    }

    public static function send(string $rel, string $downloadName): never
    {
        $base = realpath(ROOT . '/storage/uploads');
        $path = realpath(ROOT . '/storage/uploads/' . $rel);
        if (!$base || !$path || !str_starts_with($path, $base . DIRECTORY_SEPARATOR) || !is_file($path)) abort(404, 'File not found.');
        header('Content-Type: application/octet-stream');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $downloadName) . '"');
        readfile($path);
        exit;
    }
}
