<?php
declare(strict_types=1);

/**
 * Loaded at the top of every page.
 * Sets up autoloading, configuration, error handling and the session.
 */
define('ROOT_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $file = ROOT_PATH . '/classes/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require ROOT_PATH . '/includes/helpers.php';
load_env(ROOT_PATH . '/.env');

// Never show technical errors to end users; log them instead.
ini_set('display_errors', env('APP_DEBUG', '0') === '1' ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Manila');

session_name('agri_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
]);
session_start();
