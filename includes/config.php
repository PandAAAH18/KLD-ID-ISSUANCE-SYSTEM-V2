<?php

// Turn on all errors while we code
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session once
if (session_status() === PHP_SESSION_NONE) session_start();

define('APP_URL', 'http://localhost/school_id_practice');

// ==================== EMAIL CONFIGURATION ====================
// Configure your email settings here
// For Gmail: Use App Password (not regular password)
// For other providers: Use your SMTP credentials

define('MAIL_HOST', 'smtp.gmail.com');          // SMTP server
define('MAIL_PORT', 587);                       // SMTP port (587 for TLS, 465 for SSL)
define('MAIL_USERNAME', 'capungcolshairilkriztel@gmail.com'); // Your email address
define('MAIL_PASSWORD', 'ceajuqokszhpmurg');   // Gmail App Password or Email Password
define('MAIL_FROM_ADDRESS', 'capungcolshairilkriztel@gmail.com'); // Sender email address (use same as MAIL_USERNAME for Gmail)
define('MAIL_FROM_NAME', 'KLD School Portal');  // Sender name
define('MAIL_ENCRYPTION', 'tls');               // 'tls' or 'ssl'

// ==================== EMAIL VERIFICATION SETTINGS ====================
define('VERIFICATION_TOKEN_EXPIRY', 24);        // Hours until verification token expires
define('SEND_VERIFICATION_EMAIL', true);        // Set to false to disable email verification during dev
define('REQUIRE_EMAIL_VERIFICATION', true);    // Set to false to allow login without verification (dev mode)

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
?>