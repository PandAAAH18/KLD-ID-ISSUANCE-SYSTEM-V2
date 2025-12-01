<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'admin/classes/EmailVerification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = null;
$success = null;
$action = $_GET['action'] ?? 'verification'; // 'verification' or 'reset'

// Handle email verification resend
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'verification') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            $emailVerifier = new EmailVerification();

            if ($emailVerifier->resendVerificationEmail($email)) {
                $success = 'Verification email sent! Please check your inbox.';
            } else {
                $error = 'Could not resend verification email. Email may already be verified or user not found.';
            }
        } catch (\Exception $e) {
            error_log("Resend verification error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}

// Handle password reset
$token = '';
$validToken = false;
$userEmail = '';
$userId = 0;

if ($action === 'reset') {
    $token = $_GET['token'] ?? '';

    // Validate token
    if (!empty($token)) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Check if token exists and is not expired
            $sql = "SELECT pr.user_id, pr.expires_at, pr.created_at, u.email, u.full_name,
                    NOW() as server_time,
                    TIMESTAMPDIFF(MINUTE, NOW(), pr.expires_at) as minutes_remaining
                    FROM password_resets pr
                    INNER JOIN users u ON pr.user_id = u.user_id
                    WHERE pr.token = :token 
                    AND u.deleted_at IS NULL
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([':token' => $token]);
            $resetData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resetData) {
                // Check if token is expired
                if (strtotime($resetData['expires_at']) > time()) {
                    $validToken = true;
                    $userEmail = $resetData['email'];
                    $userId = $resetData['user_id'];
                } else {
                    error_log("Password reset token expired. Expires: " . $resetData['expires_at'] . ", Server: " . $resetData['server_time']);
                    $error = 'This password reset link has expired. Please request a new one.';
                }
            } else {
                error_log("Password reset token not found: " . $token);
                $error = 'Invalid password reset link. Please request a new one.';
            }
        } catch (Exception $e) {
            error_log("Reset password token validation error: " . $e->getMessage());
            $error = 'An error occurred while validating your request.';
        }
    } else {
        $error = 'No reset token provided.';
    }

    // Handle password reset form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Please fill in all fields.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            try {
                // Hash the new password
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

                // Update user password
                $updateSql = "UPDATE users 
                             SET password_hash = :password_hash 
                             WHERE user_id = :user_id";

                $updateStmt = $db->prepare($updateSql);
                $updateStmt->execute([
                    ':password_hash' => $passwordHash,
                    ':user_id' => $userId
                ]);

                // Delete the used token
                $deleteSql = "DELETE FROM password_resets WHERE token = :token";
                $deleteStmt = $db->prepare($deleteSql);
                $deleteStmt->execute([':token' => $token]);

                $success = 'Your password has been successfully reset. You can now log in with your new password.';
                $validToken = false; // Prevent form from showing again

            } catch (Exception $e) {
                error_log("Reset password update error: " . $e->getMessage());
                $error = 'An error occurred while resetting your password. Please try again.';
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Resend Email Verification - KLD School Portal">
    <link rel="shortcut icon" href="../assets/images/kldlogo.png" type="../assets/image/x-icon">
    <title><?php echo $action === 'reset' ? 'Reset Password' : 'Resend Verification'; ?> | KLD School Portal</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Local Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Preload background image -->
    <link rel="preload" href="assets/images/building.jpg" as="image">
    <style>
        :root {
            --primary-green: #2e7d32;
            --primary-dark: #1b5e20;
            --primary-light: #4caf50
        }

        .enhanced-bg {
            background: linear-gradient(135deg, rgba(27, 94, 32, 0.85), rgba(46, 125, 50, 0.85)), url('assets/images/building.jpg') center/cover no-repeat fixed;
            min-height: 100vh
        }

        .bg-overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(0, 0, 0, 0.15));
            z-index: 0
        }

        .container.position-relative {
            z-index: 3
        }

        .card {
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(8px)
        }

        .school-header {
            background: linear-gradient(90deg, var(--primary-green), var(--primary-dark));
            color: #fff;
            border-radius: 12px 12px 0 0
        }

        .logo-image {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin: 0 auto 8px;
            border: 2px solid rgba(255, 215, 0, 0.15);
            object-fit: cover;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px
        }

        .animate-fade-in {
            animation: fadeInUp .6s ease-out
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .school-input {
            border-radius: 10px;
            padding: 1rem
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--primary-green), var(--primary-dark));
            border: none;
            border-radius: 10px;
            padding: 0.9rem 1rem
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, var(--primary-dark), #0d3818)
        }

        .school-link {
            color: var(--primary-green);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease
        }

        .school-link:hover {
            color: var(--primary-dark);
            text-decoration: underline
        }

        .password-strength {
            height: 4px;
            margin-top: 8px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease
        }

        .password-strength-bar.weak {
            width: 33%;
            background: #f44336
        }

        .password-strength-bar.medium {
            width: 66%;
            background: #ff9800
        }

        .password-strength-bar.strong {
            width: 100%;
            background: #4caf50
        }

        .password-requirements {
            font-size: 0.875rem;
            margin-top: 8px;
            color: #666
        }

        .password-requirements li {
            margin: 4px 0
        }

        .password-requirements li.valid {
            color: #4caf50
        }

        .password-requirements li.valid i {
            color: #4caf50
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            z-index: 10
        }

        .password-toggle:hover {
            color: #333
        }

        .form-floating.password-field {
            position: relative
        }

        .token-expired-icon {
            font-size: 4rem;
            color: #f44336;
            margin-bottom: 1rem
        }

        .success-icon {
            font-size: 4rem;
            color: #4caf50;
            margin-bottom: 1rem
        }

        @media (max-width:768px) {
            .enhanced-bg {
                background-attachment: scroll
            }

            .card {
                margin: 12px
            }
        }
    </style>
</head>

<body class="enhanced-bg">
    <div class="bg-overlay" aria-hidden="true"></div>
    <div class="container position-relative">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg animate-fade-in">
                    <div class="card-header text-center py-3 school-header position-relative">
                        <div class="logo-container mb-2">
                            <img src="assets/images/kldlogo.png" alt="KLD School Logo" class="logo-image">
                        </div>
                        <h3 class="mb-0 fw-bold"><?php echo $action === 'reset' ? 'Reset Password' : 'Resend Verification'; ?></h3>
                        <p class="mb-0 text-muted">KLD School Portal</p>
                    </div>

                    <div class="card-body p-4">
                        <?php if ($action === 'reset'): ?>
                            <!-- PASSWORD RESET SECTION -->
                            <?php if ($success): ?>
                                <!-- Success Message -->
                                <div class="text-center">
                                    <i class="fas fa-check-circle success-icon"></i>
                                    <h4 class="mb-3">Password Reset Successful!</h4>
                                    <div class="alert alert-success" role="alert">
                                        <?= htmlspecialchars($success) ?>
                                    </div>
                                    <div class="d-grid mt-4">
                                        <a href="index.php" class="btn btn-primary">
                                            <i class="fas fa-sign-in-alt me-2"></i>
                                            Go to Login
                                        </a>
                                    </div>
                                </div>
                            <?php elseif (!$validToken): ?>
                                <!-- Invalid/Expired Token -->
                                <div class="text-center">
                                    <i class="fas fa-exclamation-triangle token-expired-icon"></i>
                                    <h4 class="mb-3">Invalid or Expired Link</h4>
                                    <div class="alert alert-danger" role="alert">
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                    <div class="d-grid gap-2 mt-4">
                                        <a href="forget_pass.php" class="btn btn-primary">
                                            <i class="fas fa-redo me-2"></i>
                                            Request New Reset Link
                                        </a>
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>
                                            Back to Login
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Reset Password Form -->
                                <h4 class="mb-3 text-center">Reset Your Password</h4>
                                <p class="text-muted text-center mb-4">
                                    Enter your new password for <strong><?= htmlspecialchars($userEmail) ?></strong>
                                </p>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <?= htmlspecialchars($error) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <form id="reset-password-form" method="POST" action="?action=reset&token=<?= htmlspecialchars($token) ?>">
                                    <div class="form-floating password-field mb-3">
                                        <input
                                            type="password"
                                            class="form-control school-input"
                                            id="new_password"
                                            name="new_password"
                                            placeholder="New Password"
                                            required
                                            minlength="8">
                                        <label for="new_password">
                                            <i class="fas fa-lock me-2"></i>New Password
                                        </label>
                                        <span class="password-toggle" onclick="togglePassword('new_password')">
                                            <i class="fas fa-eye" id="toggle-icon-new"></i>
                                        </span>
                                    </div>

                                    <div class="password-strength">
                                        <div class="password-strength-bar" id="strength-bar"></div>
                                    </div>

                                    <ul class="password-requirements" id="password-requirements">
                                        <li id="req-length">
                                            <i class="fas fa-circle-xmark"></i> At least 8 characters
                                        </li>
                                        <li id="req-uppercase">
                                            <i class="fas fa-circle-xmark"></i> One uppercase letter
                                        </li>
                                        <li id="req-lowercase">
                                            <i class="fas fa-circle-xmark"></i> One lowercase letter
                                        </li>
                                        <li id="req-number">
                                            <i class="fas fa-circle-xmark"></i> One number
                                        </li>
                                    </ul>

                                    <div class="form-floating password-field mb-4">
                                        <input
                                            type="password"
                                            class="form-control school-input"
                                            id="confirm_password"
                                            name="confirm_password"
                                            placeholder="Confirm Password"
                                            required
                                            minlength="8">
                                        <label for="confirm_password">
                                            <i class="fas fa-lock me-2"></i>Confirm Password
                                        </label>
                                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye" id="toggle-icon-confirm"></i>
                                        </span>
                                    </div>

                                    <div class="d-grid mb-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-check-circle me-2"></i>
                                            Reset Password
                                        </button>
                                    </div>
                                </form>

                                <div class="text-center">
                                    <p class="mb-0">
                                        <a href="index.php" class="school-link">
                                            <i class="fas fa-sign-in-alt me-2"></i>Back to Sign In
                                        </a>
                                    </p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- EMAIL VERIFICATION RESEND SECTION -->
                            <!-- EMAIL VERIFICATION RESEND SECTION -->
                            <!-- Error Message -->
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo esc_html($error); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Success Message -->
                            <?php if (isset($success)): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo esc_html($success); ?>
                                </div>
                                <div class="d-grid gap-2 mt-3">
                                    <a href="index.php" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt me-2"></i>Back to Login
                                    </a>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-3">
                                    Enter your email address and we'll send you a new verification link.
                                </p>

                                <!-- Resend Form -->
                                <form method="post" class="login-form">
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control school-input" id="email" name="email"
                                            placeholder="name@example.com" required>
                                        <label for="email"><i class="fas fa-envelope me-2"></i>Email address</label>
                                    </div>

                                    <div class="d-grid mb-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>Resend Verification Email
                                        </button>
                                    </div>
                                </form>

                                <div class="text-center mt-3">
                                    <p class="mb-0">
                                        <a href="index.php" class="school-link">
                                            <i class="fas fa-arrow-left me-1"></i>Back to Login
                                        </a>
                                    </p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Local Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <?php if ($action === 'reset'): ?>
        <!-- Password Reset Scripts -->
        <script>
            // Password visibility toggle
            function togglePassword(fieldId) {
                const field = document.getElementById(fieldId);
                const icon = document.getElementById('toggle-icon-' + (fieldId === 'new_password' ? 'new' : 'confirm'));

                if (field.type === 'password') {
                    field.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    field.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }

            // Password strength checker
            document.addEventListener('DOMContentLoaded', function() {
                const newPassword = document.getElementById('new_password');
                const confirmPassword = document.getElementById('confirm_password');
                const strengthBar = document.getElementById('strength-bar');

                if (newPassword) {
                    newPassword.addEventListener('input', function() {
                        const password = this.value;
                        let strength = 0;

                        // Check requirements
                        const hasLength = password.length >= 8;
                        const hasUppercase = /[A-Z]/.test(password);
                        const hasLowercase = /[a-z]/.test(password);
                        const hasNumber = /[0-9]/.test(password);

                        // Update requirement indicators
                        updateRequirement('req-length', hasLength);
                        updateRequirement('req-uppercase', hasUppercase);
                        updateRequirement('req-lowercase', hasLowercase);
                        updateRequirement('req-number', hasNumber);

                        // Calculate strength
                        if (hasLength) strength++;
                        if (hasUppercase) strength++;
                        if (hasLowercase) strength++;
                        if (hasNumber) strength++;

                        // Update strength bar
                        strengthBar.className = 'password-strength-bar';
                        if (strength <= 2) {
                            strengthBar.classList.add('weak');
                        } else if (strength === 3) {
                            strengthBar.classList.add('medium');
                        } else if (strength === 4) {
                            strengthBar.classList.add('strong');
                        }
                    });

                    // Check password match
                    confirmPassword.addEventListener('input', function() {
                        if (this.value && this.value !== newPassword.value) {
                            this.setCustomValidity('Passwords do not match');
                        } else {
                            this.setCustomValidity('');
                        }
                    });

                    newPassword.addEventListener('input', function() {
                        if (confirmPassword.value && confirmPassword.value !== this.value) {
                            confirmPassword.setCustomValidity('Passwords do not match');
                        } else {
                            confirmPassword.setCustomValidity('');
                        }
                    });
                }

                function updateRequirement(id, valid) {
                    const element = document.getElementById(id);
                    const icon = element.querySelector('i');

                    if (valid) {
                        element.classList.add('valid');
                        icon.classList.remove('fa-circle-xmark');
                        icon.classList.add('fa-circle-check');
                    } else {
                        element.classList.remove('valid');
                        icon.classList.remove('fa-circle-check');
                        icon.classList.add('fa-circle-xmark');
                    }
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>