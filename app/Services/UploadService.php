<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

final class UploadService
{
    public function __construct(private Config $config, private string $uploadRoot)
    {
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
}
