<?php
declare(strict_types=1);

define('APP_ROOT', __DIR__);
define('APP_BOOTSTRAPPED', true);
require_once APP_ROOT . '/database.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
$cookiePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookiePath === '/' ? '/' : $cookiePath,
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > 1800) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION['last_activity'] = time();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, private');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");

function agriloop_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function agriloop_render(string $view, array $data = [], int $status = 200): void
{
    $path = APP_ROOT . '/views/frontoffice/' . $view . '.php';
    if (!is_file($path)) {
        throw new RuntimeException('Front-office view not found.');
    }

    http_response_code($status);
    extract($data, EXTR_SKIP);
    require $path;
}

function agriloop_render_html(string $view, array $replacements, int $status = 200): void
{
    if (!in_array($view, ['ajouter-parcelle', 'saisir-consommation-eau'], true)) {
        throw new RuntimeException('Front-office HTML template not allowed.');
    }

    $path = APP_ROOT . '/views/frontoffice/' . $view . '.html';
    $html = file_get_contents($path);
    if ($html === false) {
        throw new RuntimeException('Front-office HTML template not found.');
    }

    $html = strtr($html, $replacements);
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $assetBase = $scriptDirectory === '/' ? '' : rtrim($scriptDirectory, '/');
    $html = str_replace(
        ['href="template.css"', 'href="template.css?v=20261122e"'],
        ['href="' . $assetBase . '/views/frontoffice/template.css?v=20261122e"', 'href="' . $assetBase . '/views/frontoffice/template.css?v=20261122e"'],
        $html
    );
    $html = strtr($html, [
        'href="http://localhost/agriloop/index.php?route=parcelles" data-app-route="parcelles"'
            => 'href="' . $assetBase . '/index.php?route=parcelles"',
        'href="ajouter-parcelle.html" data-app-route="parcelle.create"'
            => 'href="' . $assetBase . '/index.php?route=parcelle.create"',
        'href="saisir-consommation-eau.html" data-app-route="consommations"'
            => 'href="' . $assetBase . '/index.php?route=consommations"',
    ]);
    $html = str_replace('http://localhost/agriloop', $assetBase, $html);
    $html = preg_replace_callback(
        '/<title\b([^>]*)\sdata-template-text="([^"]*)"([^>]*)>.*?<\/title>/is',
        static function (array $matches): string {
            $attributes = $matches[1] . $matches[3];
            $attributes = preg_replace('/\sdata-template-text="[^"]*"/i', '', $attributes) ?? $attributes;
            return '<title' . $attributes . '>' . $matches[2] . '</title>';
        },
        $html
    ) ?? throw new RuntimeException('Unable to render HTML title.');
    $html = preg_replace_callback(
        '/<h1\b([^>]*)\sdata-template-text="([^"]*)"([^>]*)>.*?<\/h1>/is',
        static function (array $matches): string {
            $attributes = $matches[1] . $matches[3];
            $attributes = preg_replace('/\sdata-template-text="[^"]*"/i', '', $attributes) ?? $attributes;
            return '<h1' . $attributes . '>' . $matches[2] . '</h1>';
        },
        $html
    ) ?? throw new RuntimeException('Unable to render HTML heading.');
    $html = preg_replace_callback(
        '/<input\b([^>]*)\sdata-template-value="([^"]*)"([^>]*)>/i',
        static function (array $matches): string {
            $attributes = $matches[1] . $matches[3];
            $attributes = preg_replace('/\sdata-template-value="[^"]*"/i', '', $attributes) ?? $attributes;
            $attributes = preg_replace('/\svalue="[^"]*"/i', '', $attributes) ?? $attributes;
            return '<input' . $attributes . ' value="' . $matches[2] . '">';
        },
        $html
    ) ?? throw new RuntimeException('Unable to render HTML input.');
    $html = preg_replace_callback(
        '/<textarea\b([^>]*)\sdata-template-value="([^"]*)"([^>]*)>.*?<\/textarea>/is',
        static function (array $matches): string {
            $attributes = $matches[1] . $matches[3];
            $attributes = preg_replace('/\sdata-template-value="[^"]*"/i', '', $attributes) ?? $attributes;
            return '<textarea' . $attributes . '>' . $matches[2] . '</textarea>';
        },
        $html
    ) ?? throw new RuntimeException('Unable to render HTML textarea.');
    $html = preg_replace_callback(
        '/<select\b([^>]*)\sdata-template-value="([^"]*)"([^>]*)>(.*?)<\/select>/is',
        static function (array $matches): string {
            $attributes = $matches[1] . $matches[3];
            $attributes = preg_replace('/\sdata-template-value="[^"]*"/i', '', $attributes) ?? $attributes;
            $options = preg_replace_callback(
                '/<option\b([^>]*)\bvalue="([^"]*)"([^>]*)>/i',
                static function (array $option): string {
                    $attributes = $option[1] . $option[3];
                    $attributes = preg_replace('/\sselected(?:="selected")?/i', '', $attributes) ?? $attributes;
                    return '<option' . $attributes . ' value="' . $option[2] . '">';
                },
                $matches[4]
            );
            if ($options === null) {
                throw new RuntimeException('Unable to render HTML options.');
            }
            $selectedValue = $matches[2];
            $options = preg_replace_callback(
                '/<option\b([^>]*)\bvalue="([^"]*)"([^>]*)>/i',
                static function (array $option) use ($selectedValue): string {
                    $attributes = $option[1] . $option[3];
                    $attributes = preg_replace('/\sselected(?:="selected")?/i', '', $attributes) ?? $attributes;
                    $selected = $option[2] === $selectedValue ? ' selected' : '';
                    return '<option' . $attributes . ' value="' . $option[2] . '"' . $selected . '>';
                },
                $options
            );
            if ($options === null) {
                throw new RuntimeException('Unable to select HTML option.');
            }

            return '<select' . $attributes . '>' . $options . '</select>';
        },
        $html
    ) ?? throw new RuntimeException('Unable to render HTML select.');
    if (preg_match('/\{\{[A-Z_]+\}\}/', $html)) {
        throw new RuntimeException('Front-office HTML template has unresolved values.');
    }

    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
}

function agriloop_csrf_token(): string
{
    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function agriloop_valid_csrf(): bool
{
    $submitted = $_POST['_csrf_token'] ?? '';
    $stored = $_SESSION['_csrf_token'] ?? '';
    return is_string($submitted) && is_string($stored) && $stored !== '' && hash_equals($stored, $submitted);
}

function agriloop_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('Méthode non autorisée.');
    }
}

function agriloop_flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function agriloop_take_flash(): ?string
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_string($message) ? $message : null;
}

function agriloop_redirect(string $route): never
{
    header('Location: index.php?route=' . rawurlencode($route), true, 303);
    exit;
}
