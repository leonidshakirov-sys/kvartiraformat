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
     * @return list<array<string,mixed>>
     */
    public function incomingLeadFiles(): array
    {
        $collected = [];
        foreach (['attachments', 'brief_file'] as $field) {
            if (!isset($_FILES[$field])) {
                continue;
            }
            $bag = $_FILES[$field];
            if (isset($bag['name']) && is_array($bag['name'])) {
                $collected = array_merge($collected, self::normalizeMulti($bag));
            } elseif (!empty($bag['tmp_name']) || (($bag['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
                $collected[] = $bag;
            }
        }
        return array_values(array_filter($collected, static fn(array $file): bool => ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE));
    }

    public function maxFileBytes(): int
    {
        return (int)$this->config->get('security.document_max_bytes', 15728640);
    }

    public function maxFiles(): int
    {
        return (int)$this->config->get('security.document_max_files', 5);
    }

    public function maxTotalBytes(): int
    {
        return (int)$this->config->get('security.document_max_total_bytes', 52428800);
    }

    /**
     * @return list<string>
     */
    public function allowedExtensions(): array
    {
        return $this->config->get('security.allowed_document_extensions', ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);
    }

    /**
     * @param list<array<string,mixed>> $files
     * @return array{ok:bool,error?:string}
     */
    public function validatePrivateDocuments(array $files): array
    {
        if (!$files) {
            return ['ok' => true];
        }
        if (count($files) > $this->maxFiles()) {
            return ['ok' => false, 'error' => 'Можно прикрепить не больше ' . $this->maxFiles() . ' файлов.'];
        }
        $total = 0;
        foreach ($files as $file) {
            $check = $this->validatePrivateDocument($file);
            if (empty($check['ok'])) {
                return $check;
            }
            $total += (int)($file['size'] ?? 0);
        }
        if ($total > $this->maxTotalBytes()) {
            return ['ok' => false, 'error' => 'Общий размер файлов превышает ' . (int)round($this->maxTotalBytes() / 1048576) . ' МБ.'];
        }
        return ['ok' => true];
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
            return ['ok' => false, 'error' => $this->phpUploadError((int)($file['error'] ?? 0))];
        }
        $max = $this->maxFileBytes();
        if (($file['size'] ?? 0) > $max) {
            return ['ok' => false, 'error' => 'Файл превышает допустимый размер ' . (int)round($max / 1048576) . ' МБ.'];
        }
        $original = (string)($file['name'] ?? 'file');
        if ($this->hasDangerousName($original)) {
            return ['ok' => false, 'error' => 'Недопустимое имя файла.'];
        }
        $ext = strtolower((string)pathinfo($original, PATHINFO_EXTENSION));
        $allowed = $this->allowedExtensions();
        if (!in_array($ext, $allowed, true)) {
            return ['ok' => false, 'error' => 'Допустимы JPG, JPEG, PNG, WebP, PDF, DOC, DOCX, XLS и XLSX.'];
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'Не удалось проверить загруженный файл.'];
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        if (!$this->mimeMatches($ext, $mime, $tmp)) {
            return ['ok' => false, 'error' => 'Тип файла не соответствует содержимому.'];
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

    /**
     * @param list<array<string,mixed>> $files
     * @return list<array{ok:bool,stored_name?:string,original_name?:string,mime_type?:string,size_bytes?:int,error?:string}>
     */
    public function storePrivateDocuments(array $files): array
    {
        $stored = [];
        foreach ($files as $file) {
            $result = $this->handlePrivateDocument($file);
            if (!empty($result['stored_name'])) {
                $stored[] = $result;
            } elseif (empty($result['ok'])) {
                $this->deleteStored(array_column($stored, 'stored_name'));
                return [$result];
            }
        }
        return $stored;
    }

    /**
     * @param list<string> $storedNames
     */
    public function deleteStored(array $storedNames): void
    {
        foreach ($storedNames as $name) {
            $path = $this->privateAbsolutePath((string)$name);
            if ($path) {
                @unlink($path);
            }
        }
    }

    public function privateAbsolutePath(string $storedName): ?string
    {
        $storedName = str_replace('\\', '/', $storedName);
        if ($storedName === '' || str_contains($storedName, '..')) {
            return null;
        }
        $root = $this->privateRoot !== '' ? $this->privateRoot : dirname($this->uploadRoot, 2) . '/storage/lead-files';
        $path = $root . '/' . ltrim($storedName, '/');
        $realRoot = realpath($root);
        $realPath = realpath($path);
        if (!$realRoot || !$realPath || !str_starts_with($realPath, $realRoot) || !is_file($realPath)) {
            return null;
        }
        return $realPath;
    }

    /**
     * @param array<string,mixed> $bag
     * @return list<array<string,mixed>>
     */
    private static function normalizeMulti(array $bag): array
    {
        $files = [];
        foreach (array_keys($bag['name']) as $i) {
            $files[] = [
                'name' => $bag['name'][$i] ?? 'file',
                'type' => $bag['type'][$i] ?? '',
                'tmp_name' => $bag['tmp_name'][$i] ?? '',
                'error' => $bag['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $bag['size'][$i] ?? 0,
            ];
        }
        return $files;
    }

    private function hasDangerousName(string $name): bool
    {
        $name = strtolower(str_replace('\\', '/', $name));
        if (str_contains($name, '..') || str_contains($name, "\0")) {
            return true;
        }
        $parts = explode('.', basename($name));
        if (count($parts) < 2) {
            return true;
        }
        $forbidden = ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8', 'html', 'htm', 'js', 'mjs', 'exe', 'bat', 'cmd', 'sh', 'cgi', 'pl', 'py', 'rb', 'asp', 'aspx', 'jsp', 'svg', 'htaccess', 'shtml', 'com', 'dll', 'so'];
        foreach ($parts as $i => $part) {
            if ($i === 0) {
                continue;
            }
            if (in_array($part, $forbidden, true)) {
                return true;
            }
        }
        return false;
    }

    private function mimeMatches(string $ext, string $mime, string $tmp): bool
    {
        $mime = strtolower(trim(explode(';', $mime)[0]));
        $map = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword', 'application/octet-stream'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
            'xls' => ['application/vnd.ms-excel', 'application/msword', 'application/octet-stream'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        ];
        $allowed = $map[$ext] ?? [];
        if ($mime !== '' && !in_array($mime, $allowed, true)) {
            return false;
        }
        $fh = fopen($tmp, 'rb');
        if (!$fh) {
            return false;
        }
        $head = (string)fread($fh, 12);
        fclose($fh);
        return match ($ext) {
            'jpg', 'jpeg' => str_starts_with($head, "\xFF\xD8\xFF"),
            'png' => str_starts_with($head, "\x89PNG"),
            'webp' => str_starts_with($head, 'RIFF') && str_contains($head, 'WEBP'),
            'pdf' => str_starts_with($head, '%PDF'),
            'doc', 'xls' => str_starts_with($head, "\xD0\xCF\x11\xE0"),
            'docx', 'xlsx' => str_starts_with($head, 'PK'),
            default => false,
        };
    }

    private function phpUploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Файл превышает лимит сервера. Можно прикрепить до 15 МБ на файл и 50 МБ на все файлы.',
            UPLOAD_ERR_PARTIAL => 'Файл загружен частично. Повторите попытку.',
            UPLOAD_ERR_NO_TMP_DIR => 'На сервере нет временной папки для загрузки.',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск.',
            UPLOAD_ERR_EXTENSION => 'Загрузка остановлена расширением PHP.',
            default => 'Не удалось загрузить файл.',
        };
    }
}
