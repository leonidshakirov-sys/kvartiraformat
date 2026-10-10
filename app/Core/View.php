<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public function __construct(private string $viewsPath)
    {
    }

    public function render(string $template, array $data = [], string $layout = 'layouts/main'): string
    {
        $content = $this->partial($template, $data);
        return $this->partial($layout, array_merge($data, ['content' => $content]));
    }

    public function partial(string $template, array $data = []): string
    {
        $file = $this->viewsPath . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Шаблон не найден: ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string)ob_get_clean();
    }
}
