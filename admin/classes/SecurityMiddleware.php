<?php
/**
 * SecurityMiddleware Class
 * Centralized security checks for all admin pages
 */
class SecurityMiddleware
{
    /**
     * Check admin authentication
     */
    public static function requireAdmin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        enforceSessionTimeout(); // From config.php

        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            http_response_code(403);
            die('Access denied: Admin privileges required');
        }
    }

    /**
     * Check student authentication
     */
    public static function requireStudent(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        enforceSessionTimeout();

        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
            http_response_code(403);
            die('Access denied: Student access required');
        }
    }

    /**
     * Check teacher authentication
     */
    public static function requireTeacher(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        enforceSessionTimeout();

        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
            http_response_code(403);
            die('Access denied: Teacher access required');
        }
    }

    /**
     * Check authenticated user (any role)
     */
    public static function requireAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        enforceSessionTimeout();

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            http_response_code(401);
            die('Unauthorized: Please login first');
        }
    }

    /**
     * Validate CSRF token (use with enforcePostMethod)
     * @param string $tokenName Form field name
     * @param string $action Action for logging
     * @return bool
     */
    public static function validateCsrf(string $tokenName = 'csrf_token', string $action = 'unknown'): bool
    {
        require_once __DIR__ . '/CsrfToken.php';
        CsrfToken::init();

        if (!CsrfToken::validateAndLog($tokenName, $action)) {
            http_response_code(403);
            die('CSRF token validation failed');
        }

        return true;
    }

    /**
     * Enforce POST method
     */
    public static function enforcePostMethod(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method not allowed');
        }
    }

    /**
     * Enforce GET method
     */
    public static function enforceGetMethod(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            die('Method not allowed');
        }
    }

    /**
     * Rate limiting check
     * @param string $identifier IP or user ID
     * @param int $maxAttempts Max attempts allowed
     * @param int $window Time window in seconds
     * @return bool True if within limits
     */
    public static function checkRateLimit(string $identifier, int $maxAttempts = 5, int $window = 300): bool
    {
        if (!ENABLE_RATE_LIMITING) {
            return true;
        }

        $key = 'rate_limit_' . hash('sha256', $identifier);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }

        $now = time();
        $attempts = array_filter($_SESSION[$key], fn($time) => ($now - $time) < $window);

        if (count($attempts) >= $maxAttempts) {
            error_log("Rate limit exceeded for: $identifier");
            return false;
        }

        $_SESSION[$key][] = $now;
        return true;
    }

    /**
     * Get safe response header for JSON
     */
    public static function setJsonHeader(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
    }

    /**
     * Get safe response headers
     */
    public static function setSafeHeaders(): void
    {
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');

        // Disable framing
        header('X-Frame-Options: DENY');

        // CSP (basic)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Log security event
     */
    public static function logSecurityEvent(string $event, string $details = ''): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $userId = $_SESSION['user_id'] ?? 'unknown';

        $message = "SECURITY EVENT: $event | User: $userId | IP: $ip | Details: $details";
        error_log($message);
    }

    /**
     * Regenerate session on sensitive operations
     */
    public static function regenerateSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);
    }
}
?>
