<?php
// Turn on all errors while we code
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session once
if (session_status() === PHP_SESSION_NONE) session_start();

define('APP_URL', 'http://localhost/school_id_practice');

// Helper: redirect
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}
?>