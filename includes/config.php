<?php

// Turn on all errors while we code
error_reporting(E_ALL);
ini_set('display_errors', 0); // SECURITY: Don't display errors publicly
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errors.log');

// Ensure logs directory exists
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Start session once
if (session_status() === PHP_SESSION_NONE) session_start();

// ==================== ENVIRONMENT CONFIGURATION ====================
// Load from .env file if exists, otherwise use defaults
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        return $default;
    }
}

// Load .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') === false || strpos($line, '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $_ENV[$key] = $value;
    }
}

// ==================== APPLICATION SETTINGS ====================
define('APP_URL', env('APP_URL', 'http://localhost/KLD-ID-ISSUANCE-SYSTEM-V2'));
define('APP_DEBUG', env('APP_DEBUG', 'false') === 'true');

// ==================== EMAIL CONFIGURATION ====================
define('MAIL_HOST', env('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int)env('MAIL_PORT', 587));
define('MAIL_USERNAME', env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', ''));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', ''));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'KLD School Portal'));
define('MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'tls'));

// ==================== EMAIL VERIFICATION SETTINGS ====================
define('VERIFICATION_TOKEN_EXPIRY', (int)env('VERIFICATION_TOKEN_EXPIRY', 24));
define('SEND_VERIFICATION_EMAIL', env('SEND_VERIFICATION_EMAIL', 'true') === 'true');
define('REQUIRE_EMAIL_VERIFICATION', env('REQUIRE_EMAIL_VERIFICATION', 'true') === 'true');

// ==================== DATABASE CONFIGURATION ====================
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int)env('DB_PORT', 3306));
define('DB_NAME', env('DB_NAME', 'school_id_system'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));

// ==================== SESSION SETTINGS ====================
define('SESSION_TIMEOUT', (int)env('SESSION_TIMEOUT', 1800)); // 30 minutes
define('SESSION_SECURE_COOKIE', env('SESSION_SECURE_COOKIE', 'false') === 'true');
define('SESSION_HTTPONLY', env('SESSION_HTTPONLY', 'true') === 'true');

// ==================== SECURITY SETTINGS ====================
define('CSRF_TOKEN_TIMEOUT', (int)env('CSRF_TOKEN_TIMEOUT', 3600));
define('ENABLE_RATE_LIMITING', env('ENABLE_RATE_LIMITING', 'true') === 'true');
define('MAX_LOGIN_ATTEMPTS', (int)env('MAX_LOGIN_ATTEMPTS', 5));
define('LOGIN_ATTEMPT_WINDOW', (int)env('LOGIN_ATTEMPT_WINDOW', 300));

// ==================== FILE UPLOAD SETTINGS ====================
define('MAX_FILE_SIZE', (int)env('MAX_FILE_SIZE', 5242880)); // 5MB
define('ALLOWED_IMAGE_TYPES', array_map('trim', explode(',', env('ALLOWED_IMAGE_TYPES', 'image/jpeg,image/png,image/gif'))));
define('ALLOWED_DOCUMENT_TYPES', array_map('trim', explode(',', env('ALLOWED_DOCUMENT_TYPES', 'application/pdf,image/png'))));
define('UPLOAD_VIRUS_SCAN', env('UPLOAD_VIRUS_SCAN', 'false') === 'true');

// ==================== CONFIGURE SESSION COOKIES ====================
ini_set('session.cookie_httponly', SESSION_HTTPONLY ? '1' : '0');
if (SESSION_SECURE_COOKIE && isset($_SERVER['HTTPS'])) {
    ini_set('session.cookie_secure', '1');
}
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

// Helper: redirect
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

// Helper: sanitize output
function esc_html(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Helper: check and enforce session timeout
function enforceSessionTimeout(): void
{
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            redirect(APP_URL . '/index.php?session_expired=1');
        }
    }
    $_SESSION['last_activity'] = time();
}
?>