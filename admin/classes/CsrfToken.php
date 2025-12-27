<?php
/**
 * CSRF Token Manager
 * Generates and validates CSRF tokens to prevent cross-site request forgery attacks
 */
class CsrfToken
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = 'csrf_token';
    private const TOKEN_TIMEOUT = 3600; // 1 hour

    /**
     * Initialize CSRF protection (must be called at session start)
     */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate token if not exists or expired
        if (!isset($_SESSION[self::SESSION_KEY]) || 
            !isset($_SESSION['csrf_token_time']) || 
            time() - $_SESSION['csrf_token_time'] > self::TOKEN_TIMEOUT) {
            self::regenerate();
        }
    }

    /**
     * Generate a new CSRF token
     */
    public static function regenerate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION['csrf_token_time'] = time();
        
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Get current CSRF token
     */
    public static function get(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY])) {
            return self::regenerate();
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Validate CSRF token from POST/REQUEST
     * @param string $tokenName Name of the form field containing the token (default: 'csrf_token')
     * @return bool True if valid, false otherwise
     */
    public static function validate(string $tokenName = 'csrf_token'): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get token from POST or REQUEST
        $submittedToken = $_POST[$tokenName] ?? $_REQUEST[$tokenName] ?? null;

        if ($submittedToken === null) {
            error_log("CSRF: Missing token in request");
            return false;
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;

        if ($sessionToken === null) {
            error_log("CSRF: No token in session");
            return false;
        }

        // Use hash_equals to prevent timing attacks
        $isValid = hash_equals($sessionToken, $submittedToken);

        if (!$isValid) {
            error_log("CSRF: Token mismatch - submitted: " . substr($submittedToken, 0, 8) . 
                     "... vs session: " . substr($sessionToken, 0, 8) . "...");
        }

        return $isValid;
    }

    /**
     * Validate token and log IP/User-Agent for audit
     * @param string $tokenName Form field name
     * @param string $action Action being performed (for logging)
     * @return bool True if valid
     */
    public static function validateAndLog(string $tokenName = 'csrf_token', string $action = 'unknown'): bool
    {
        $isValid = self::validate($tokenName);

        if (!$isValid) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            error_log("CSRF FAILURE - Action: $action | IP: $ip | User-Agent: $ua");
        }

        return $isValid;
    }

    /**
     * Generate HTML hidden input field with CSRF token
     * @param string $fieldName Form field name (default: 'csrf_token')
     * @return string HTML hidden input
     */
    public static function field(string $fieldName = 'csrf_token'): string
    {
        $token = self::get();
        return "<input type=\"hidden\" name=\"{$fieldName}\" value=\"{$token}\">";
    }

    /**
     * Generate HTML with CSRF token in a data attribute (for AJAX)
     * @return string HTML meta tag
     */
    public static function meta(): string
    {
        $token = self::get();
        return "<meta name=\"csrf-token\" content=\"{$token}\">";
    }

    /**
     * Get token for AJAX requests (via header X-CSRF-Token)
     */
    public static function getAjaxToken(): string
    {
        return self::get();
    }
}
?>
