<?php
/**
 * Test Email Verification System
 * This page helps diagnose email verification issues
 */

require_once 'includes/config.php';
require_once 'admin/classes/EmailVerification.php';

$message = '';
$type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'test_email') {
        $email = $_POST['email'] ?? '';
        if (empty($email)) {
            $message = 'Email required';
            $type = 'error';
        } else {
            try {
                $emailVerifier = new EmailVerification();
                $token = bin2hex(random_bytes(32));
                $testEmail = $emailVerifier->sendVerificationEmail($email, $token, 'Test User');
                
                if ($testEmail) {
                    $message = "✓ Test email sent successfully to $email!<br/>Check logs for token: $token";
                    $type = 'success';
                } else {
                    $message = "✗ Failed to send test email to $email. Check error logs.";
                    $type = 'error';
                }
            } catch (Exception $e) {
                $message = "✗ Error: " . $e->getMessage();
                $type = 'error';
            }
        }
    } elseif ($action === 'check_config') {
        $config = [
            'MAIL_HOST' => defined('MAIL_HOST') ? MAIL_HOST : 'NOT SET',
            'MAIL_PORT' => defined('MAIL_PORT') ? MAIL_PORT : 'NOT SET',
            'MAIL_USERNAME' => defined('MAIL_USERNAME') ? MAIL_USERNAME : 'NOT SET',
            'MAIL_FROM_ADDRESS' => defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'NOT SET',
            'SEND_VERIFICATION_EMAIL' => defined('SEND_VERIFICATION_EMAIL') ? (SEND_VERIFICATION_EMAIL ? 'TRUE' : 'FALSE') : 'NOT SET',
            'REQUIRE_EMAIL_VERIFICATION' => defined('REQUIRE_EMAIL_VERIFICATION') ? (REQUIRE_EMAIL_VERIFICATION ? 'TRUE' : 'FALSE') : 'NOT SET',
            'APP_URL' => defined('APP_URL') ? APP_URL : 'NOT SET'
        ];
        
        $message = "<strong>Email Configuration:</strong><br/>";
        foreach ($config as $key => $value) {
            $masked = ($key === 'MAIL_USERNAME' || $key === 'MAIL_PASSWORD') ? '***MASKED***' : $value;
            $message .= "$key: $masked<br/>";
        }
        $type = 'info';
    } elseif ($action === 'check_database') {
        try {
            $pdo = (new Database())->getConnection();
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_verified = 0");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $message = "✓ Database connected<br/>Unverified accounts: <strong>" . $result['count'] . "</strong><br/><br/>";
            $message .= "<strong>Unverified Users:</strong><br/>";
            
            $stmt = $pdo->query("SELECT user_id, email, is_verified FROM users WHERE is_verified = 0 LIMIT 5");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $message .= "- " . $row['email'] . " (verified: " . $row['is_verified'] . ")<br/>";
            }
            $type = 'success';
        } catch (Exception $e) {
            $message = "✗ Database error: " . $e->getMessage();
            $type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Test</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .container { max-width: 600px; margin-top: 40px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(90deg, #2e7d32, #1b5e20); color: white; border-radius: 12px 12px 0 0; }
        .alert { border-radius: 8px; }
        .btn { border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Email Verification Diagnostics</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo ($type === 'error' ? 'danger' : ($type === 'success' ? 'success' : 'info')); ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <form method="POST">
                            <input type="hidden" name="action" value="check_config">
                            <button type="submit" class="btn btn-primary w-100">Check Email Config</button>
                        </form>
                    </div>
                    <div class="col-md-6 mb-3">
                        <form method="POST">
                            <input type="hidden" name="action" value="check_database">
                            <button type="submit" class="btn btn-info w-100">Check Database</button>
                        </form>
                    </div>
                </div>

                <hr>

                <h5>Send Test Email</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="test_email">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Send Test Email</button>
                </form>

                <hr>
                <small class="text-muted">
                    This page helps diagnose email verification issues.<br>
                    Check error logs: <code>logs/errors.log</code>
                </small>
            </div>
        </div>
    </div>
</body>
</html>
