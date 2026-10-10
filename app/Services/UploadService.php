<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

final class UploadService
{
    public function __construct(
        private Config $config,
        private string $uploadRoot,
        private string $privateRoot = ''
    ) {
    }

    public function handle(array $file, string $subdir = 'media'): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Файл не загружен.'];
        }
        $max = (int)$this->config->get('security.upload_max_bytes', 5242880);
        if (($file['size'] ?? 0) > $max) {
            return ['ok' => false, 'error' => 'Файл превышает допустимый размер.'];
        }
        $original = (string)($file['name'] ?? 'file');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = $this->config->get('security.allowed_image_extensions', ['jpg', 'jpeg', 'png', 'webp', 'avif']);
        if (!in_array($ext, $allowed, true)) {
            return ['ok' => false, 'error' => 'Недопустимое расширение файла.'];
        }
        $info = @getimagesize((string)$file['tmp_name']);
        if ($info === false) {
            return ['ok' => false, 'error' => 'Файл не является изображением.'];
        }
        $dir = $this->uploadRoot . '/' . trim($subdir, '/') . '/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return ['ok' => false, 'error' => 'Не удалось создать директорию загрузки.'];
        }
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $target = $dir . '/' . $name;
        if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
            return ['ok' => false, 'error' => 'Не удалось сохранить файл.'];
        }
        $relative = trim($subdir, '/') . '/' . date('Y/m') . '/' . $name;
        return ['ok' => true, 'path' => $relative, 'original' => $original, 'mime' => $info['mime'] ?? '', 'width' => $info[0] ?? null, 'height' => $info[1] ?? null];
    }

    /**
     * @param array<string,mixed> $file
     * @return array{ok:bool,error?:string,ext?:string,mime?:string,original?:string}
     */
    public function validatePrivateDocument(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true];
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Не удалось загрузить файл.'];
        }
        $max = (int)$this->config->get('security.document_max_bytes', 8388608);
        if (($file['size'] ?? 0) > $max) {
            return ['ok' => false, 'error' => 'Файл превышает допустимый размер 8 МБ.'];
        }
        $original = (string)($file['name'] ?? 'file');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = $this->config->get('security.allowed_document_extensions', ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx']);
        if (!in_array($ext, $allowed, true)) {
            return ['ok' => false, 'error' => 'Допустимы PDF, JPG, PNG, WEBP, DOC и DOCX.'];
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $tmp !== '' ? (string)$finfo->file($tmp) : '';
        $allowedMime = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/octet-stream',
        ];
        if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
            return ['ok' => false, 'error' => 'Тип файла не разрешён.'];
        }
        return ['ok' => true, 'ext' => $ext, 'mime' => $mime, 'original' => $original];
    }

    /**
     * Stores a private lead attachment outside the public web root.
     *
     * @param array<string,mixed> $file
     * @return array{ok:bool,error?:string,stored_name?:string,original_name?:string,mime_type?:string,size_bytes?:int}
     */
    public function handlePrivateDocument(array $file): array
    {
        $check = $this->validatePrivateDocument($file);
        if (empty($check['ok']) || empty($check['ext'])) {
            return $check['ok'] ?? false ? ['ok' => true] : $check;
        }
        $original = (string)$check['original'];
        $ext = (string)$check['ext'];
        $mime = (string)($check['mime'] ?? '');
        $tmp = (string)($file['tmp_name'] ?? '');
        $root = $this->privateRoot !== '' ? $this->privateRoot : dirname($this->uploadRoot, 2) . '/storage/lead-files';
        $dir = $root . '/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
            return ['ok' => false, 'error' => 'Не удалось сохранить файл на сервере.'];
        }
        $stored = date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($tmp, $root . '/' . $stored)) {
            return ['ok' => false, 'error' => 'Не удалось сохранить файл.'];
        }
        @chmod($root . '/' . $stored, 0640);
        return [
            'ok' => true,
            'stored_name' => $stored,
            'original_name' => $original,
            'mime_type' => $mime,
            'size_bytes' => (int)($file['size'] ?? 0),
        ];
    }

    public function privateAbsolutePath(string $storedName): ?string
    {
        $storedName = str_replace('\\', '/', $storedName);
        if ($storedName === '' || str_contains($storedName, '..')) {
            return null;
        }
        $root = $this->privateRoot !== '' ? $this->privateRoot : dirname($this->uploadRoot, 2) . '/storage/lead-files';
        $path = $root . '/' . ltrim($storedName, '/');
        return is_file($path) ? $path : null;
    }
}
