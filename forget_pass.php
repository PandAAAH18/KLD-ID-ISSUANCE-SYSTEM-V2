<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'admin/classes/EmailVerification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    $student_number = $_POST['student_number'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    
    if (empty($student_number) || empty($date_of_birth)) {
        $error = 'Please fill in all required fields.';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    } else {
        try {
            // Connect to database
            $database = new Database();
            $db = $database->getConnection();
            
            // Create password_resets table if it doesn't exist
            $createTableSql = "CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_token (token),
                INDEX idx_expires_at (expires_at),
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $db->exec($createTableSql);
            
            // Find user by student ID and date of birth
            $sql = "SELECT u.user_id, u.email, u.full_name, s.dob 
                    FROM users u 
                    INNER JOIN student s ON u.email = s.email 
                    WHERE s.student_id = :student_id 
                    AND s.dob = :dob 
                    AND u.deleted_at IS NULL 
                    LIMIT 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':student_id' => $student_number,
                ':dob' => $date_of_birth
            ]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate password reset token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
                
                // Store reset token in database
                $insertSql = "INSERT INTO password_resets (user_id, token, expires_at, created_at) 
                             VALUES (:user_id, :token, :expires_at, NOW())
                             ON DUPLICATE KEY UPDATE token = :token, expires_at = :expires_at, created_at = NOW()";
                
                $insertStmt = $db->prepare($insertSql);
                $insertStmt->execute([
                    ':user_id' => $user['user_id'],
                    ':token' => $token,
                    ':expires_at' => $expiresAt
                ]);
                
                // Send password reset email
                $emailVerification = new EmailVerification($db);
                if (sendPasswordResetEmail($user['email'], $token, $user['full_name'], $emailVerification)) {
                    $email = $user['email'];
                    // Mask email for privacy
                    $emailParts = explode('@', $email);
                    $maskedEmail = substr($emailParts[0], 0, 3) . '***@' . $emailParts[1];
                    $message = "Password reset link has been sent to $maskedEmail";
                    
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => $message]);
                        exit;
                    }
                } else {
                    $error = 'Failed to send password reset email. Please try again later.';
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => $error]);
                        exit;
                    }
                }
            } else {
                // Don't reveal whether the user exists or not for security
                $error = 'Invalid student number or date of birth. Please check your information and try again.';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $error]);
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log("Forgot password error: " . $e->getMessage());
            
            // In development, show the actual error
            if (defined('APP_DEBUG') && APP_DEBUG) {
                $error = 'An error occurred: ' . $e->getMessage();
            } else {
                $error = 'An error occurred. Please try again later.';
            }
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }
    }
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($email, $token, $userName, $emailVerification) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = defined('MAIL_USERNAME') ? MAIL_USERNAME : '';
        $mail->Password = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = defined('MAIL_PORT') ? MAIL_PORT : 587;
        
        // Recipients
        $senderEmail = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'noreply@kldschool.com';
        $senderName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'KLD School Portal';
        $mail->setFrom($senderEmail, $senderName);
        $mail->addAddress($email, $userName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset - KLD School Portal';
        
        // Generate reset link
        $resetUrl = APP_URL . '/resend_verification.php?action=reset&token=' . urlencode($token);
        
        // HTML email body
        $displayName = !empty($userName) ? htmlspecialchars($userName) : 'User';
        $mail->Body = getPasswordResetEmailTemplate($displayName, $resetUrl);
        $mail->AltBody = "Please reset your password by clicking this link: $resetUrl";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Password reset email error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get password reset email template
 */
function getPasswordResetEmailTemplate($userName, $resetUrl) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../assets/images/kldlogo.png" type="../assets/image/x-icon">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(90deg, #2e7d32, #1b5e20); color: #ffffff; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .greeting { color: #333; margin-bottom: 20px; }
        .message { color: #666; line-height: 1.6; margin-bottom: 30px; }
        .button-container { text-align: center; margin: 30px 0; }
        .reset-btn { background-color: #2e7d32; color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold; }
        .reset-btn:hover { background-color: #1b5e20; }
        .link-alternative { color: #666; margin-top: 20px; font-size: 12px; word-break: break-all; }
        .footer { background-color: #f4f4f4; padding: 20px; text-align: center; color: #999; font-size: 12px; border-radius: 0 0 8px 8px; }
        .warning { color: #d32f2f; font-size: 12px; margin-top: 20px; padding: 15px; background-color: #ffebee; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
            <p>KLD School Portal</p>
        </div>
        
        <div class="content">
            <p class="greeting">Hello <strong>$userName</strong>,</p>
            
            <p class="message">
                We received a request to reset your password for your KLD School Portal account. 
                Click the button below to create a new password.
            </p>
            
            <div class="button-container">
                <a href="$resetUrl" class="reset-btn">Reset Password</a>
            </div>
            
            <p class="link-alternative">
                If the button above doesn't work, copy and paste this link into your browser:<br>
                <code>$resetUrl</code>
            </p>
            
            <div class="warning">
                ⚠️ <strong>Important Security Information:</strong><br>
                • This link will expire in 1 hour<br>
                • If you didn't request a password reset, please ignore this email<br>
                • Never share your password reset link with anyone
            </div>
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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KLD School - Forgot Password Verification">
    <title>Forgot Password | KLD School Portal</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Local Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/login.css" rel="stylesheet">
    
    <!-- Preload background image -->
    <link rel="preload" href="assets/images/building.jpg" as="image">
</head>
<body class="login-body enhanced-bg">
    <div class="bg-overlay" aria-hidden="true"></div>
    <div class="particles-container" aria-hidden="true">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <div class="container position-relative">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card shadow-lg animate-fade-in">
                    <div class="card-header text-center py-3 school-header position-relative">
                        <div class="logo-container mb-2">
                            <img src="assets/images/kldlogo.png" alt="KLD School Logo" class="logo-image">
                        </div>
                        <h3 class="mb-0 fw-bold">KLD School Portal</h3>
                        <p class="mb-0 text-muted">Secure access to school services</p>
                        <div class="header-decoration" aria-hidden="true"></div>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Forgot Password Form -->
                        <form id="forgot-password-form" class="login-form" method="POST" action="">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control school-input" id="student_number" name="student_number" placeholder="Student Number" required>
                                <label for="student_number"><i class="fas fa-id-card me-2"></i>Student Number</label>
                                <div class="input-decoration" aria-hidden="true"></div>
                            </div>

                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label"><i class="fas fa-calendar me-2"></i>Date of Birth</label>
                                <input type="date" class="form-control school-input" id="date_of_birth" name="date_of_birth" required>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn school-btn btn-enhanced">
                                    <i class="fas fa-key me-2"></i> Reset Password
                                    <span class="btn-ripple" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>

                        <div class="text-center">
                            <div class="divider mb-3"><span>Remember your password?</span></div>
                            <p class="mb-3">
                                <a href="index.php" class="school-link btn-link-enhanced">
                                    <i class="fas fa-sign-in-alt me-2"></i>Back to Sign In
                                </a>
                            </p>
                            <div class="security-notice mt-2">
                                <i class="fas fa-shield-alt me-2"></i><small class="text-muted">Secured with SSL</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Local Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/login.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <!-- Inline enhanced styles + scripts for better UX (kept local for easy edits) -->
    <style>
        :root{--primary-green:#2e7d32;--primary-dark:#1b5e20;--primary-light:#4caf50}
        .enhanced-bg{background:linear-gradient(135deg, rgba(27,94,32,0.85), rgba(46,125,50,0.85)), url('assets/images/building.jpg') center/cover no-repeat fixed;min-height:100vh}
        .bg-overlay{position:fixed;inset:0;background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.15));z-index:0}
        .particles-container{position:fixed;inset:0;z-index:0;pointer-events:none}
        .particle{width:6px;height:6px;border-radius:50%;background:rgba(255,215,0,0.35);position:absolute;animation:float 6s linear infinite}
        .particle:nth-child(1){left:10%;top:10%;animation-delay:0s}
        .particle:nth-child(2){left:25%;top:5%;animation-delay:1.2s}
        .particle:nth-child(3){left:70%;top:15%;animation-delay:2.6s}
        @keyframes float{0%{transform:translateY(0)}50%{transform:translateY(-30px)}100%{transform:translateY(0)}}
        .container.position-relative{z-index:3}
        .login-card{border-radius:14px;background:rgba(255,255,255,0.98);backdrop-filter:blur(8px)}
        .school-header{background:linear-gradient(90deg,var(--primary-green),var(--primary-dark));color:#fff;border-radius:12px 12px 0 0}
        .logo-image{width:64px;height:64px;border-radius:50%;margin:0 auto 8px;border:2px solid rgba(255,215,0,0.15);object-fit:cover;background:rgba(255,255,255,0.1);padding:4px}
        .animate-fade-in{animation:fadeInUp .6s ease-out}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .school-input{border-radius:10px;padding:1rem}
        .btn-enhanced{background:linear-gradient(90deg,var(--primary-green),var(--primary-dark));color:#fff;border-radius:10px;padding:0.9rem 1rem;border:none}
        .btn-enhanced:active .btn-ripple{transform:scale(1);opacity:0}
        .divider{position:relative;text-align:center}
        .divider span{background:#fff;padding:0 12px;position:relative;z-index:2}
        .divider::before{content:'';position:absolute;left:0;right:0;top:50%;height:1px;background:rgba(0,0,0,0.06);z-index:1}
        .security-notice{display:flex;align-items:center;justify-content:center;gap:8px;padding:8px;border-radius:8px;background:rgba(46,125,50,0.06)}
        @media (max-width:768px){.enhanced-bg{background-attachment:scroll}.login-card{margin:12px}}
    </style>

    <script>
        // Simple ripple effect on button press - waits for DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-enhanced').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const ripple = this.querySelector('.btn-ripple');
                    if (ripple) {
                        ripple.style.width = '200px';
                        ripple.style.height = '200px';
                        ripple.style.opacity = '0.3';
                        setTimeout(() => {
                            ripple.style.width = '0';
                            ripple.style.height = '0';
                            ripple.style.opacity = '0';
                        }, 300);
                    }
                });
            });
            
            // Handle form submission
            const form = document.getElementById('forgot-password-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const studentNumber = document.getElementById('student_number').value.trim();
                    const dateOfBirth = document.getElementById('date_of_birth').value;
                    
                    // Check if fields are filled
                    if (!studentNumber || !dateOfBirth) {
                        Swal.fire({
                            title: "Missing Information!",
                            text: "Please fill in all required fields.",
                            icon: "info",
                            confirmButtonColor: "#3085d6",
                            confirmButtonText: "OK"
                        });
                        return;
                    }
                    
                    // Show loading
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we verify your information',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Send AJAX request
                    const formData = new FormData();
                    formData.append('student_number', studentNumber);
                    formData.append('date_of_birth', dateOfBirth);
                    
                    fetch('forget_pass.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        // Check if response is ok
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        // Try to parse as JSON
                        return response.text().then(text => {
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('Response is not valid JSON:', text);
                                throw new Error('Server returned invalid response');
                            }
                        });
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: "Success!",
                                text: data.message,
                                icon: "success",
                                confirmButtonColor: "#2e7d32",
                                confirmButtonText: "OK"
                            }).then(() => {
                                // Clear the form
                                document.getElementById('student_number').value = '';
                                document.getElementById('date_of_birth').value = '';
                            });
                        } else {
                            Swal.fire({
                                title: "Error",
                                text: data.error,
                                icon: "error",
                                confirmButtonColor: "#d32f2f",
                                confirmButtonText: "Try Again"
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: "Connection Error",
                            text: error.message || "Failed to connect to server. Please check your internet connection and try again.",
                            icon: "error",
                            confirmButtonColor: "#d32f2f",
                            confirmButtonText: "OK"
                        });
                    });
                });
            }
            
            // Show PHP messages on page load
            <?php if ($message): ?>
                Swal.fire({
                    title: "Success!",
                    text: "<?= addslashes($message) ?>",
                    icon: "success",
                    confirmButtonColor: "#2e7d32",
                    confirmButtonText: "OK"
                });
            <?php endif; ?>
            
            <?php if ($error): ?>
                Swal.fire({
                    title: "Error",
                    text: "<?= addslashes($error) ?>",
                    icon: "error",
                    confirmButtonColor: "#d32f2f",
                    confirmButtonText: "Try Again"
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>
