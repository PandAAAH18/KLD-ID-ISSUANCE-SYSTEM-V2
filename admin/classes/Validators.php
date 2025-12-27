<?php
/**
 * Validators Class
 * Comprehensive input validation for all data types and fields
 */
class Validators
{
    /**
     * Validate email address
     */
    public static function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate password strength
     * Requires: min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
     */
    public static function password(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }

        return $errors;
    }

    /**
     * Validate phone number (Philippine format)
     */
    public static function phoneNumber(string $phone): bool
    {
        // Philippine format: 09xx-xxx-xxxx or 09xxxxxxxxx
        return preg_match('/^(09|\+639)\d{9}$/', str_replace(['-', ' '], '', $phone)) === 1;
    }

    /**
     * Validate URL
     */
    public static function url(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate positive integer
     */
    public static function positiveInteger(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) !== false;
    }

    /**
     * Validate non-negative integer
     */
    public static function nonNegativeInteger(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]) !== false;
    }

    /**
     * Validate date format YYYY-MM-DD
     */
    public static function date(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Validate date of birth (age must be between 15 and 100)
     */
    public static function dateOfBirth(string $dob): bool
    {
        if (!self::date($dob)) {
            return false;
        }

        $age = (new DateTime())->diff(new DateTime($dob))->y;
        return $age >= 15 && $age <= 100;
    }

    /**
     * Validate enum value
     */
    public static function enum(string $value, array $allowedValues): bool
    {
        return in_array($value, $allowedValues, true);
    }

    /**
     * Validate string length
     */
    public static function stringLength(string $value, int $min = 1, int $max = PHP_INT_MAX): bool
    {
        $len = strlen($value);
        return $len >= $min && $len <= $max;
    }

    /**
     * Validate alphanumeric with allowed special chars
     */
    public static function alphanumeric(string $value, array $allowedSpecialChars = []): bool
    {
        $pattern = '/^[a-zA-Z0-9';
        foreach ($allowedSpecialChars as $char) {
            $pattern .= preg_quote($char, '/');
        }
        $pattern .= ']+$/';

        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate student ID format (e.g., 2024-2-000548)
     */
    public static function studentId(string $studentId): bool
    {
        return preg_match('/^\d{4}-\d{1,2}-\d{6}$/', $studentId) === 1;
    }

    /**
     * Validate ID number format (e.g., 2025100001)
     */
    public static function idNumber(string $idNumber): bool
    {
        return preg_match('/^\d{4}\d{6}$/', $idNumber) === 1;
    }

    /**
     * Validate blood type
     */
    public static function bloodType(string $bloodType): bool
    {
        $validTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        return in_array($bloodType, $validTypes, true);
    }

    /**
     * Validate gender
     */
    public static function gender(string $gender): bool
    {
        return in_array($gender, ['Male', 'Female', 'Other'], true);
    }

    /**
     * Validate no XSS attempts
     */
    public static function noXss(string $value): bool
    {
        // Check for common XSS patterns
        $xssPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/<svg/i'
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sanitize string input
     */
    public static function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Trim whitespace
        $value = trim($value);

        // HTML encode dangerous characters
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $value;
    }

    /**
     * Sanitize integer
     */
    public static function sanitizeInteger(mixed $value): int|false
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Validate request status
     */
    public static function requestStatus(string $status): bool
    {
        $validStatuses = ['pending', 'approved', 'rejected', 'generated', 'completed'];
        return in_array($status, $validStatuses, true);
    }

    /**
     * Validate issued ID status
     */
    public static function issuedIdStatus(string $status): bool
    {
        $validStatuses = ['pending', 'generated', 'printed', 'delivered', 'active', 'expired', 'lost'];
        return in_array($status, $validStatuses, true);
    }

    /**
     * Batch validate multiple fields
     */
    public static function batch(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            if (isset($rule['required']) && $rule['required'] && (empty($value) || !isset($data[$field]))) {
                $errors[$field][] = ucfirst($field) . " is required";
                continue;
            }

            if (!isset($data[$field]) || empty($value)) {
                continue;
            }

            // Apply validators
            if (isset($rule['email']) && !self::email($value)) {
                $errors[$field][] = "Invalid email format";
            }

            if (isset($rule['phone']) && !self::phoneNumber($value)) {
                $errors[$field][] = "Invalid phone number";
            }

            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field][] = "Must be at least " . $rule['min_length'] . " characters";
            }

            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field][] = "Must not exceed " . $rule['max_length'] . " characters";
            }

            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field][] = "Invalid format";
            }
        }

        return $errors;
    }
}
?>
