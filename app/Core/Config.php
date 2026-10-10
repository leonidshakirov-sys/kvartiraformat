<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    private array $items;

    public function __construct(string $root)
    {
        $sample = $root . '/config/config.sample.php';
        $local = $root . '/config/config.php';
        $this->items = is_file($local) ? require $local : require $sample;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->items;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function all(): array
    {
        return $this->items;
    }
}
