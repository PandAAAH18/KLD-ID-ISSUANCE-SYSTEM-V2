<?php
require_once '../includes/config.php';
require_once 'student_header.php';
require_once 'student.php';

if (
    !isset($_SESSION['user_id'], $_SESSION['user_type'], $_SESSION['student_id']) ||
    $_SESSION['user_type'] !== 'student'
) {
    header('Location: ../index.php');
    exit();
}

$stu = (new Student())->findById((int)$_SESSION['student_id']);
if (!$stu) {
    header('Location: ../index.php');
    exit();
}

$msg = '';
$msgType = '';

// Handle ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject && $category && $message) {
        // In production, save to database
        $msg = '✓ Ticket submitted successfully! Reference: TKT-' . strtoupper(substr(uniqid(), -6));
        $msgType = 'success';
    } else {
        $msg = '✕ Please fill in all required fields.';
        $msgType = 'error';
    }
}

$faqs = [
    [
        'category' => 'ID Application',
        'question' => 'How long does it take to get my student ID?',
        'answer' => 'Student IDs are typically processed within 3-5 business days. You will receive an email notification when your ID is ready for pickup.'
    ],
    [
        'category' => 'ID Application',
        'question' => 'What documents do I need to apply for an ID?',
        'answer' => 'You need to complete your profile with a recent photo, Certificate of Registration (COR), and signature. All information must be accurate and complete.'
    ],
    [
        'category' => 'ID Application',
        'question' => 'Can I replace my lost student ID?',
        'answer' => 'Yes! Go to "My ID" section and submit a replacement request. There may be a replacement fee. Provide details about the loss or damage.'
    ],
    [
        'category' => 'Profile',
        'question' => 'How do I update my profile information?',
        'answer' => 'Visit the "Profile" section and click "Edit Profile". Update your information and upload any new documents if needed. Save your changes.'
    ],
    [
        'category' => 'Profile',
        'question' => 'Why is my profile marked as incomplete?',
        'answer' => 'Some fields are required to complete your profile. Please update your photo, signature, COR, and other required information.'
    ],
    [
        'category' => 'Account',
        'question' => 'How do I change my password?',
        'answer' => 'Go to Edit Profile and scroll to the "Change Password" section. Click the button and enter your new password, then save changes.'
    ],
    [
        'category' => 'Account',
        'question' => 'What if I forget my login credentials?',
        'answer' => 'Contact the Registrar\'s Office or IT Helpdesk. They can help reset your password or recover your account.'
    ],
    [
        'category' => 'Technical',
        'question' => 'Why am I having trouble uploading files?',
        'answer' => 'Check that your file size is within limits (5MB for photos, 10MB for COR). Ensure you\'re using JPG, PNG, or PDF formats. Clear your browser cache if issues persist.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support & Helpdesk</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f3f3f3;
            margin: 0;
            padding: 0;
            padding-top: 80px;
        }

        /* ▬▬▬▬ MAIN CONTAINER ▬▬▬▬ */
        .help-wrapper {
            width: 95%;
            max-width: 1200px;
            margin: 30px auto;
        }

        /* ▬▬▬▬ PAGE HEADER ▬▬▬▬ */
        .help-header {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            padding: 60px 30px;
            border-radius: 12px;
            margin-bottom: 50px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .help-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .help-header::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
        }

        .help-header-content {
            position: relative;
            z-index: 1;
        }

        .help-header h1 {
            margin: 0 0 15px 0;
            font-size: 40px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .help-header p {
            margin: 0;
            font-size: 17px;
            opacity: 0.95;
            font-weight: 300;
            letter-spacing: 0.3px;
        }

        /* ▬▬▬▬ CONTACT CARDS ▬▬▬▬ */
        .contact-section {
            margin-bottom: 50px;
        }

        .contact-cards {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 25px;
            margin-bottom: 40px;
        }

        .contact-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            text-align: center;
            transition: all 0.3s ease;
            border-top: 5px solid #1b5e20;
            position: relative;
            overflow: hidden;
        }

        .contact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1b5e20, #b69b04);
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: left;
        }

        .contact-card:hover::before {
            transform: scaleX(1);
        }

        .contact-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
            border-top-color: #0d3817;
        }

        .contact-icon {
            font-size: 48px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }

        .contact-card:hover .contact-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .contact-card h3 {
            margin: 0 0 12px 0;
            color: #1b5e20;
            font-size: 19px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .contact-card p {
            margin: 8px 0;
            color: #666;
            font-size: 14px;
            line-height: 1.7;
            font-weight: 500;
        }

        .contact-card a {
            display: inline-block;
            margin-top: 12px;
            color: #1b5e20;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            padding: 6px 0;
            border-bottom: 2px solid transparent;
        }

        .contact-card a:hover {
            color: #0d3817;
            border-bottom-color: #b69b04;
        }

        /* ▬▬▬▬ SECTION TITLE ▬▬▬▬ */
        .section-title {
            font-size: 26px;
            font-weight: 800;
            color: #1b5e20;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 4px solid #b69b04;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.3px;
        }

        .section-title::before {
            content: '';
            width: 6px;
            height: 30px;
            background: linear-gradient(180deg, #1b5e20, #b69b04);
            border-radius: 3px;
        }

        /* ▬▬▬▬ TICKET FORM ▬▬▬▬ */
        .ticket-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            margin-bottom: 50px;
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }

        .ticket-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .ticket-header {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            padding: 25px 30px;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ticket-body {
            padding: 40px 30px;
        }

        .ticket-body>p {
            color: #666;
            font-size: 15px;
            margin-bottom: 25px;
            line-height: 1.6;
            font-weight: 500;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            font-size: 14px;
            letter-spacing: -0.2px;
        }

        .form-group.required label::after {
            content: ' *';
            color: #dc3545;
            font-weight: 800;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: #fafafa;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #999;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1b5e20;
            box-shadow: 0 0 12px rgba(27, 94, 32, 0.15);
            background: white;
        }

        .ticket-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            padding: 13px 35px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
            letter-spacing: -0.2px;
            box-shadow: 0 4px 12px rgba(27, 94, 32, 0.2);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(27, 94, 32, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-reset {
            background: #f0f0f0;
            color: #333;
            padding: 13px 35px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
            letter-spacing: -0.2px;
        }

        .btn-reset:hover {
            background: #e8e8e8;
            border-color: #1b5e20;
            color: #1b5e20;
        }

        /* ▬▬▬▬ ALERT MESSAGE ▬▬▬▬ */
        .alert-banner {
            padding: 18px 24px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 15px;
            font-weight: 600;
            animation: slideDown 0.3s ease-out;
            border-left: 5px solid;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }

        /* ▬▬▬▬ SEARCH BAR ▬▬▬▬ */
        .search-container {
            margin-bottom: 35px;
        }

        .search-box {
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            overflow: hidden;
        }

        .search-box input {
            width: 100%;
            padding: 16px 24px;
            padding-left: 50px;
            border: 2px solid transparent;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
            font-weight: 500;
        }

        .search-box input:focus {
            outline: none;
            border-color: #1b5e20;
            box-shadow: 0 6px 20px rgba(27, 94, 32, 0.15);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #1b5e20;
        }

        /* ▬▬▬▬ FAQ SECTION ▬▬▬▬ */
        .faq-filters {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 18px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s ease;
            color: #666;
            letter-spacing: -0.2px;
        }

        .filter-btn:hover {
            border-color: #1b5e20;
            background: #f0f8f5;
            color: #1b5e20;
        }

        .filter-btn.active {
            border-color: #1b5e20;
            background: #1b5e20;
            color: white;
            box-shadow: 0 4px 12px rgba(27, 94, 32, 0.2);
        }

        .faq-list {
            display: grid;
            gap: 12px;
        }

        .faq-item {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border: 1px solid #f0f0f0;
        }

        .faq-item:hover {
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
            border-color: #1b5e20;
        }

        .faq-question {
            padding: 20px 24px;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            color: #1b5e20;
            border-left: 4px solid #1b5e20;
            transition: all 0.3s;
            font-size: 15px;
            letter-spacing: -0.2px;
        }

        .faq-question:hover {
            background: #f8f9fa;
            padding-left: 28px;
        }

        .faq-toggle {
            font-size: 18px;
            transition: transform 0.3s ease;
            color: #1b5e20;
            font-weight: 800;
        }

        .faq-toggle.open {
            transform: rotate(180deg);
        }

        .faq-answer {
            padding: 0 24px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .faq-answer.open {
            padding: 24px;
            max-height: 600px;
        }

        .faq-answer p {
            margin: 0;
            color: #555;
            line-height: 1.8;
            font-weight: 500;
        }

        .faq-category {
            display: inline-block;
            background: linear-gradient(135deg, #b69b04, #8b7600);
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* ▬▬▬▬ RESPONSIVE ▬▬▬▬ */
        @media (max-width: 992px) {
            .contact-cards {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .help-wrapper {
                width: 100%;
            }

            .help-header {
                padding: 25px 20px;
            }

            .help-header h1 {
                font-size: 28px;
            }

            .contact-cards {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .ticket-actions {
                flex-direction: column;
            }

            .btn-submit,
            .btn-reset {
                width: 100%;
            }

            .section-title {
                font-size: 20px;
            }

            .faq-question {
                font-size: 15px;
            }
        }

        @media (max-width: 576px) {
            .help-header h1 {
                font-size: 24px;
            }

            .ticket-body {
                padding: 20px;
            }

            .faq-category {
                display: block;
                margin-bottom: 8px;
            }
        }
    </style>
</head>

<body class="admin-body">

    <div class="help-wrapper">
        <!-- PAGE HEADER -->
        <div class="help-header">
            <div class="help-header-content">
                <h1>Support & Helpdesk</h1>
                <p>We're here to help! Get instant answers or submit a ticket</p>
            </div>
        </div>

        <!-- CONTACT SECTION -->
        <div class="contact-section">
            <div class="section-title">Contact Information</div>
            <div class="contact-cards">
                <div class="contact-card">
                    <div class="contact-icon">📧</div>
                    <h3>Email Support</h3>
                    <p>Send us your inquiry and we'll respond within 24 hours</p>
                    <a href="mailto:support@school.edu">kld.edu.ph</a>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">📱</div>
                    <h3>Phone Support</h3>
                    <p>Call us during office hours for immediate assistance</p>
                    <p style="color: #1b5e20; font-weight: 700; font-size: 16px; margin: 10px 0 0 0;">+63 (555) 123-4567</p>
                    <p style="font-size: 12px; color: #999;">Mon-Sat: 8AM-5PM</p>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">🏢</div>
                    <h3>Visit Us</h3>
                    <p>Registrar's Office - Building 1, Room 101</p>
                    <p style="color: #1b5e20; font-weight: 600; margin: 10px 0 0 0;">Kolehiyo ng Lungsod ng Dasmariñas</p>
                    <p style="font-size: 12px; color: #999;">Walk-in Hours: 8AM-5PM</p>
                </div>
            </div>
        </div>

        <!-- ALERT MESSAGE -->
        <?php if ($msg): ?>
            <div class="alert-banner alert-<?php echo $msgType; ?>">
                <span><?php echo $msg; ?></span>
            </div>
        <?php endif; ?>

        <!-- TICKET SUBMISSION -->
        <div class="ticket-card">
            <div class="ticket-header">
                Submit a Support Ticket
            </div>
            <div class="ticket-body">
                <p>Can't find what you're looking for? Submit a support ticket and our team will get back to you within 24 hours.</p>

                <form method="post" onsubmit="handleSubmit(event)">
                    <div class="form-row">
                        <div class="form-group required">
                            <label>Subject</label>
                            <input type="text" name="subject" placeholder="Brief description of your issue" required>
                        </div>
                        <div class="form-group required">
                            <label>Category</label>
                            <select name="category" required>
                                <option value="">-- Select Category --</option>
                                <option value="id_application">ID Application</option>
                                <option value="profile">Profile</option>
                                <option value="account">Account</option>
                                <option value="technical">Technical Issue</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row full">
                        <div class="form-group required">
                            <label>Message</label>
                            <textarea name="message" rows="6" placeholder="Please provide detailed information about your issue..." required></textarea>
                        </div>
                    </div>

                    <div class="ticket-actions">
                        <button type="submit" name="submit_ticket" class="btn-submit">✓ Submit Ticket</button>
                        <button type="reset" class="btn-reset">↻ Clear</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- FAQ SECTION -->
        <div>
            <div class="section-title">Frequently Asked Questions</div>

            <!-- SEARCH BAR -->
            <div class="search-container">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="faqSearch" placeholder="Search FAQs...">
                </div>
            </div>

            <!-- CATEGORY FILTERS -->
            <div class="faq-filters">
                <button class="filter-btn active" onclick="filterFAQ('all')">All</button>
                <button class="filter-btn" onclick="filterFAQ('ID Application')">ID Application</button>
                <button class="filter-btn" onclick="filterFAQ('Profile')">Profile</button>
                <button class="filter-btn" onclick="filterFAQ('Account')">Account</button>
                <button class="filter-btn" onclick="filterFAQ('Technical')">Technical</button>
            </div>

            <!-- FAQ LIST -->
            <div class="faq-list">
                <?php foreach ($faqs as $index => $faq): ?>
                    <div class="faq-item" data-category="<?= htmlspecialchars($faq['category']) ?>" data-question="<?= htmlspecialchars(strtolower($faq['question'])) ?>">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <div style="flex: 1;">
                                <div class="faq-category"><?= htmlspecialchars($faq['category']) ?></div>
                                <div><?= htmlspecialchars($faq['question']) ?></div>
                            </div>
                            <div class="faq-toggle">▼</div>
                        </div>
                        <div class="faq-answer">
                            <p><?= htmlspecialchars($faq['answer']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Handle form submission with alert
        function handleSubmit(event) {
            event.preventDefault();

            const subject = document.querySelector('input[name="subject"]').value.trim();
            const category = document.querySelector('select[name="category"]').value.trim();
            const message = document.querySelector('textarea[name="message"]').value.trim();

            if (!subject || !category || !message) {
                showAlert('⚠️ Please fill in all required fields.', 'error');
                return false;
            }

            // Show success notification
            showAlert('✓ Ticket submitted successfully! Reference: TKT-' + generateTicketId() + '\nOur team will get back to you within 24 hours.', 'success');

            // Reset form after 2 seconds
            setTimeout(() => {
                event.target.reset();
            }, 2000);

            return false;
        }

        // Generate ticket ID
        function generateTicketId() {
            return Math.random().toString(36).substr(2, 6).toUpperCase();
        }

        // Show alert banner
        function showAlert(message, type) {
            // Remove existing alert if any
            const existingAlert = document.getElementById('notificationAlert');
            if (existingAlert) {
                existingAlert.remove();
            }

            // Create alert element
            const alert = document.createElement('div');
            alert.id = 'notificationAlert';
            alert.className = `alert-banner alert-${type}`;
            alert.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                width: 400px;
                z-index: 9999;
                animation: slideInRight 0.4s ease-out;
            `;

            alert.innerHTML = `
                <span style="font-size: 18px; font-weight: 800;">${type === 'success' ? '✓' : '⚠️'}</span>
                <span style="white-space: pre-line;">${message}</span>
            `;

            document.body.appendChild(alert);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                alert.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        }

        // Add animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(500px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(500px);
                    opacity: 0;
                }
            }

            @media (max-width: 768px) {
                #notificationAlert {
                    width: calc(100% - 40px) !important;
                    right: 20px !important;
                    left: 20px !important;
                }
            }
        `;
        document.head.appendChild(style);

        // FAQ Toggle
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const toggle = element.querySelector('.faq-toggle');

            answer.classList.toggle('open');
            toggle.classList.toggle('open');
        }

        // FAQ Filter
        function filterFAQ(category) {
            const items = document.querySelectorAll('.faq-item');
            const buttons = document.querySelectorAll('.filter-btn');

            // Update button states
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // Filter items
            items.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // FAQ Search
        document.getElementById('faqSearch').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const items = document.querySelectorAll('.faq-item');

            items.forEach(item => {
                const question = item.dataset.question;
                if (question.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>

</body>

</html>