<?php
namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class View
{
    /** @var array<string,mixed> */
    private static array $shared = [];

    /** @param array<string,mixed> $data */
    public static function share(array $data): void
    {
        self::$shared = array_merge(self::$shared, $data);
    }

    /** @param array<string,mixed> $data */
    public static function render(string $view, array $data = [], ?string $layout = 'app'): void
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        $layoutFile = Config::get('BASE_PATH') . '/views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }
        $vars = array_merge(self::$shared, $data, ['content' => $content]);
        extract($vars, EXTR_SKIP);
        require $layoutFile;
    }

    /** @param array<string,mixed> $data */
    public static function partial(string $view, array $data = []): string
    {
        $file = Config::get('BASE_PATH') . '/views/pages/' . ltrim($view, '/') . '.php';
        if (!is_file($file)) {
            $file = Config::get('BASE_PATH') . '/views/' . ltrim($view, '/') . '.php';
        }
        if (!is_file($file)) {
            return '<p class="text-danger">View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        $vars = array_merge(self::$shared, $data);
        extract($vars, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    /** @param array<string,mixed> $data */
    public static function component(string $component, array $data = []): string
    {
        $file = Config::get('BASE_PATH') . '/views/components/' . $component . '.php';
        if (!is_file($file)) {
            return '';
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    public static function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
