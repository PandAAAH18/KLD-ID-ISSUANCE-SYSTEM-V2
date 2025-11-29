<?php
$vendorPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($vendorPath)) {
    require_once $vendorPath;
} else {
    // Fallback path if vendor is in different location
    require_once __DIR__ . '/../../../vendor/autoload.php';
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailVerification
{
    private ?\PDO $db;
    private string $senderEmail;
    private string $senderName;
    private string $smtpHost;
    private string $smtpUser;
    private string $smtpPass;
    private int $smtpPort;

    public function __construct(?PDO $db = null)
    {
        if ($db === null) {
            $database = new Database();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        // Load email configuration from constants (defined in config.php)
        $this->senderEmail = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'noreply@kldschool.com';
        $this->senderName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'KLD School Portal';
        $this->smtpHost = defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com';
        $this->smtpUser = defined('MAIL_USERNAME') ? MAIL_USERNAME : '';
        $this->smtpPass = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
        $this->smtpPort = defined('MAIL_PORT') ? MAIL_PORT : 587;
    }

    /**
     * Generate a secure verification token
     */
    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Create verification record for user
     */
    public function createVerificationToken(int $userId, string $email): ?string
    {
        try {
            $token = $this->generateToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours')); // Token expires in 24 hours

            $sql = "INSERT INTO email_verification (user_id, email, token, expires_at, is_verified, created_at)
                    VALUES (:user_id, :email, :token, :expires_at, 0, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':email' => $email,
                ':token' => $token,
                ':expires_at' => $expiresAt
            ]);

            return $token;
        } catch (\Exception $e) {
            error_log("Error creating verification token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send verification email using PHPMailer
     */
    public function sendVerificationEmail(string $email, string $token, string $userName = ''): bool
    {
        try {
            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;

            // Recipients
            $mail->setFrom($this->senderEmail, $this->senderName);
            $mail->addAddress($email, $userName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Email Verification - KLD School Portal';

            // Generate verification link
            $verifyUrl = APP_URL . '/verify_email.php?token=' . urlencode($token);

            // HTML email body
            $mail->Body = $this->getEmailTemplate($userName, $verifyUrl);
            $mail->AltBody = "Please verify your email by clicking this link: $verifyUrl";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify email token and mark as verified
     */
    public function verifyToken(string $token): ?array
    {
        try {
            $sql = "SELECT * FROM email_verification 
                    WHERE token = :token 
                    AND expires_at > NOW() 
                    AND is_verified = 0 
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);
            $record = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($record) {
                // Mark token as verified
                $updateSql = "UPDATE email_verification 
                             SET is_verified = 1, verified_at = NOW() 
                             WHERE id = :id";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([':id' => $record['id']]);

                // Update user record
                $userSql = "UPDATE users 
                           SET is_verified = 1, verified = 1, verified_at = NOW() 
                           WHERE user_id = :user_id";
                $userStmt = $this->db->prepare($userSql);
                $userStmt->execute([':user_id' => $record['user_id']]);

                return $record;
            }

            return null;
        } catch (\Exception $e) {
            error_log("Error verifying token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if email is verified
     */
    public function isEmailVerified(string $email): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM users 
                    WHERE email = :email 
                    AND is_verified = 1 
                    AND deleted_at IS NULL";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            error_log("Error checking email verification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if token is valid (not expired, not verified)
     */
    public function isTokenValid(string $token): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM email_verification 
                    WHERE token = :token 
                    AND expires_at > NOW() 
                    AND is_verified = 0";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            error_log("Error checking token validity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resend verification email
     */
    public function resendVerificationEmail(string $email): bool
    {
        try {
            // Get user info
            $userSql = "SELECT user_id, full_name FROM users 
                       WHERE email = :email 
                       AND is_verified = 0 
                       AND deleted_at IS NULL 
                       LIMIT 1";

            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([':email' => $email]);
            $user = $userStmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                return false; // User not found or already verified
            }

            // Invalidate old tokens
            $invalidateSql = "UPDATE email_verification 
                             SET is_verified = 1 
                             WHERE user_id = :user_id 
                             AND is_verified = 0";
            $invalidateStmt = $this->db->prepare($invalidateSql);
            $invalidateStmt->execute([':user_id' => $user['user_id']]);

            // Create new token and send
            $token = $this->createVerificationToken($user['user_id'], $email);
            if (!$token) {
                return false;
            }

            return $this->sendVerificationEmail($email, $token, $user['full_name'] ?? '');
        } catch (\Exception $e) {
            error_log("Error resending verification email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get email template HTML
     */
    private function getEmailTemplate(string $userName, string $verifyUrl): string
    {
        $displayName = !empty($userName) ? htmlspecialchars($userName) : 'User';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(90deg, #2e7d32, #1b5e20); color: #ffffff; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .greeting { color: #333; margin-bottom: 20px; }
        .message { color: #666; line-height: 1.6; margin-bottom: 30px; }
        .button-container { text-align: center; margin: 30px 0; }
        .verify-btn { background-color: #2e7d32; color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold; }
        .verify-btn:hover { background-color: #1b5e20; }
        .link-alternative { color: #666; margin-top: 20px; font-size: 12px; }
        .footer { background-color: #f4f4f4; padding: 20px; text-align: center; color: #999; font-size: 12px; border-radius: 0 0 8px 8px; }
        .warning { color: #d32f2f; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Email Verification</h1>
            <p>KLD School Portal</p>
        </div>
        
        <div class="content">
            <p class="greeting">Hello <strong>$displayName</strong>,</p>
            
            <p class="message">
                Thank you for registering with KLD School Portal! To complete your registration and access all features, 
                please verify your email address by clicking the button below.
            </p>
            
            <div class="button-container">
                <a href="$verifyUrl" class="verify-btn">Verify Email Address</a>
            </div>
            
            <p class="link-alternative">
                If the button above doesn't work, copy and paste this link into your browser:<br>
                <code>$verifyUrl</code>
            </p>
            
            <p class="warning">
                This verification link will expire in 24 hours. If you didn't create this account, please ignore this email.
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; KLD School Portal. All rights reserved.</p>
            <p>This is an automated email. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
?>
