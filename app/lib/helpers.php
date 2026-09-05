<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $route, array $params = []): never
{
    $query = array_merge(['r' => $route], $params);
    header('Location: /index.php?' . http_build_query($query));
    exit;
}

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewFile = APP_PATH . '/views/' . $view . '.php';
    require APP_PATH . '/views/layout.php';
}

function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function postString(string $key, string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}

function postInt(string $key, int $default = 0): int
{
    if (!array_key_exists($key, $_POST)) {
        return $default;
    }
    return filter_var($_POST[$key], FILTER_VALIDATE_INT) !== false ? (int)$_POST[$key] : $default;
}

function getInt(string $key, int $default = 0): int
{
    if (!array_key_exists($key, $_GET)) {
        return $default;
    }
    return filter_var($_GET[$key], FILTER_VALIDATE_INT) !== false ? (int)$_GET[$key] : $default;
}
