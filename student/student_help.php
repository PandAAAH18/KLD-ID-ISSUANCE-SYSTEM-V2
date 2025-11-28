<?php
require_once '../includes/config.php';
require_once 'student_header.php';
require_once 'student.php';
require_once __DIR__ . '/../vendor/autoload.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


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
    $subject  = trim($_POST['subject']  ?? '');
    $category = trim($_POST['category'] ?? '');
    $message  = trim($_POST['message']  ?? '');
    $fromMail = filter_var($stu['email'], FILTER_VALIDATE_EMAIL);

    if ($subject && $category && $message && filter_var($fromMail, FILTER_VALIDATE_EMAIL)) {
        $ticketRef = 'TKT-' . strtoupper(substr(uniqid(), -6));

        $mail = new PHPMailer(true);
        try {
            /* ---------- SMTP still uses YOUR Gmail ---------- */
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'caballeroangelo321@gmail.com'; // your account
            $mail->Password   = 'flzv unam icfk yxok';         // your app pwd
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            /* ---------- MESSAGE ---------- */
            $mail->setFrom($fromMail);                 // student’s address
            $mail->addReplyTo($fromMail);              // replies go to student
            $mail->addAddress('caballeroangelo321@gmail.com'); // you
            $mail->Subject = "New Support Ticket: $subject";
            $mail->Body    = "Ticket Reference: $ticketRef\n"
                           . "From: $fromMail\n"
                           . "Category: $category\n\n"
                           . "Message:\n$message\n";

            $mail->send();

            $msg     = "✓ Ticket submitted successfully! Reference: $ticketRef";
            $msgType = 'success';
        } catch (Exception $e) {
            $msg     = '✗ Could not send ticket. Mailer Error: ' . $mail->ErrorInfo;
            $msgType = 'error';
        }
    } else {
        $msg     = '✕ Please fill in all fields with a valid e-mail address.';
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

<!-- PAGE CONTENT STARTS HERE -->
<style>
:root {
    --primary-dark: #1b5e20;
    --primary-medium: #2e7d32;
    --primary-light: #4caf50;
    --accent-orange: #ff9800;
    --accent-orange-dark: #f57c00;
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
    --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);
    --shadow-xl: 0 12px 36px rgba(0, 0, 0, 0.2);
    --transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.help-wrapper {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0;
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* PAGE HEADER */
.help-header {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-medium) 50%, var(--primary-light) 100%);
    color: white;
    padding: 45px 50px;
    margin-bottom: 50px;
    border-radius: 18px;
    box-shadow: var(--shadow-lg);
    animation: slideInDown 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

.help-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: rotateGlow 20s linear infinite;
}

@keyframes rotateGlow {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.help-header-content {
    position: relative;
    z-index: 1;
}

.help-header-content h1 {
    margin: 0 0 15px 0;
    font-size: 3rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.help-header-content p {
    margin: 0;
    font-size: 1.25rem;
    opacity: 0.95;
    font-weight: 500;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* CONTACT SECTION */
.contact-section {
    margin-bottom: 60px;
}

.section-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-dark);
    margin-bottom: 20px;
    padding: 1px 20px;
    border-bottom: 3px solid var(--accent-orange);
    letter-spacing: 0.3px;
}

.contact-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.contact-card {
    background: white;
    padding: 40px 35px;
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border-top: 6px solid var(--primary-light);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.contact-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(76, 175, 80, 0.05) 0%, rgba(255, 152, 0, 0.05) 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
}

.contact-card:hover::before {
    opacity: 1;
}

.contact-card:hover {
    box-shadow: var(--shadow-xl);
    transform: translateY(-8px) scale(1.02);
    border-top-color: var(--accent-orange);
}

.contact-icon {
    font-size: 4rem;
    margin-bottom: 25px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    z-index: 1;
}

.contact-card:hover .contact-icon {
    transform: scale(1.2) rotate(5deg);
    animation: iconPulse 0.6s ease-out;
}

@keyframes iconPulse {
    0%, 100% {
        transform: scale(1.2) rotate(5deg);
    }
    50% {
        transform: scale(1.3) rotate(5deg);
    }
}

.contact-card h3 {
    margin: 0 0 15px 0;
    font-size: 1.35rem;
    color: var(--primary-dark);
    font-weight: 700;
}

.contact-card p {
    margin: 12px 0;
    color: #666;
    line-height: 1.6;
    font-size: 0.95rem;
}

.contact-card a {
    color: var(--accent-orange);
    font-weight: 700;
    text-decoration: none;
    transition: var(--transition);
}

.contact-card a:hover {
    color: var(--accent-orange-dark);
    text-decoration: underline;
}

/* ALERT MESSAGES */
.alert-banner {
    padding: 18px 25px;
    border-radius: 10px;
    margin-bottom: 40px;
    display: flex;
    align-items: center;
    gap: 15px;
    font-weight: 600;
    animation: slideInDown 0.4s ease-out;
}

.alert-success {
    background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
    border-left: 5px solid var(--primary-light);
    color: var(--primary-dark);
}

.alert-error {
    background: linear-gradient(135deg, rgba(244, 67, 54, 0.1) 0%, rgba(244, 67, 54, 0.05) 100%);
    border-left: 5px solid #f44336;
    color: #d32f2f;
}

/* TICKET CARD */
.ticket-card {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    overflow: hidden;
    margin-bottom: 60px;
    transition: var(--transition);
    animation: fadeIn 0.5s ease-out;
}

.ticket-card:hover {
    box-shadow: var(--shadow-lg);
}

.ticket-header {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
    color: white;
    padding: 25px 25px;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.ticket-body {
    padding: 40px;
}

.ticket-body > p {
    color: #666;
    font-size: 1.05rem;
    margin-bottom: 30px;
    line-height: 1.6;
}

/* FORM STYLING */
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.form-row.full {
    grid-template-columns: 1fr;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.form-group label {
    font-weight: 700;
    color: var(--primary-dark);
    font-size: 0.95rem;
}

.form-group.required label::after {
    content: ' *';
    color: #f44336;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    background: #f8f9fa;
    transition: var(--transition);
    color: #333;
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: #999;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-light);
    background: white;
    box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.1);
}

/* TICKET ACTIONS */
.ticket-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.btn-submit,
.btn-reset {
    padding: 16px 36px;
    border-radius: 12px;
    border: none;
    font-weight: 700;
    font-size: 1.05rem;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: inline-flex;
    align-items: center;
    gap: 12px;
    letter-spacing: 0.5px;
    position: relative;
    overflow: hidden;
}

.btn-submit::before,
.btn-reset::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-submit:hover::before,
.btn-reset:hover::before {
    width: 300px;
    height: 300px;
}

.btn-submit {
    background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
    color: white;
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.25), 0 2px 8px rgba(76, 175, 80, 0.15);
}

.btn-submit:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(76, 175, 80, 0.35), 0 4px 12px rgba(76, 175, 80, 0.2);
    background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
}

.btn-submit:active {
    transform: translateY(-2px);
}

.btn-submit i,
.btn-reset i {
    font-size: 1.2rem;
    transition: transform 0.3s ease;
}

.btn-submit:hover i,
.btn-reset:hover i {
    transform: scale(1.2) rotate(5deg);
}

.btn-reset {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    color: var(--primary-dark);
    border: 2px solid var(--primary-light);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.btn-reset:hover {
    background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
    border-color: var(--primary-medium);
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(76, 175, 80, 0.2);
}

.btn-reset:active {
    transform: translateY(-2px);
}

/* Fix: prevent the ticket-actions column from stretching to match adjacent grid items
   and keep buttons a sensible size on desktop while allowing full-width on mobile */
.ticket-actions {
    align-self: start; /* keep actions at top of the grid cell */
    flex-direction: row; /* stack buttons vertically */
    justify-content: flex-start;
    align-items: center;
}

.ticket-actions .btn-submit,
.ticket-actions .btn-reset {
    width: 230px; /* fixed desktop width */
    min-height: 48px;
    box-sizing: border-box;
}

@media (max-width: 480px) {
    .ticket-actions {
        flex-direction: row;
        align-items: stretch;
    }
    .ticket-actions .btn-submit,
    .ticket-actions .btn-reset {
        width: 100%;
    }
}

/* GUIDELINES CONTAINER */
.guidelines-container {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    overflow: hidden;
    margin-bottom: 60px;
    animation: fadeIn 0.5s ease-out;
}

.guidelines-header {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
    color: white;
    padding: 20px 25px;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.guidelines-body {
    padding: 40px;
}

.guidelines-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

/* GUIDELINE CARDS */
.guideline-card {
    background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
    border-radius: 14px;
    border: 2px solid #e8e8e8;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: var(--shadow-sm);
}

.guideline-card:hover {
    border-color: var(--primary-light);
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
}

.guideline-card.expanded {
    border-color: var(--accent-orange);
    box-shadow: var(--shadow-xl);
}

.guideline-card-wide {
    grid-column: 1 / -1;
}

.guideline-header {
    padding: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    background: white;
    transition: all 0.4s ease;
}

.guideline-card:hover .guideline-header {
    background: linear-gradient(135deg, rgba(76, 175, 80, 0.05) 0%, rgba(255, 152, 0, 0.05) 100%);
}

.guideline-card.expanded .guideline-header {
    background: linear-gradient(135deg, rgba(255, 152, 0, 0.08) 0%, rgba(76, 175, 80, 0.08) 100%);
}

.guideline-header > div {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 20px;
}

.guideline-icon {
    font-size: 2.5rem;
    min-width: 60px;
    text-align: center;
}

.guideline-header h4 {
    margin: 0 0 8px 0;
    color: var(--primary-dark);
    font-size: 1.2rem;
    font-weight: 700;
}

.guideline-header p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
    font-weight: 400;
    line-height: 1.5;
}

.guideline-toggle {
    font-size: 1.5rem;
    color: var(--primary-dark);
    transition: var(--transition);
    flex-shrink: 0;
}

.guideline-card.expanded .guideline-toggle {
    transform: rotate(180deg);
    color: var(--accent-orange);
}

.guideline-content {
    max-height: 0;
    overflow: hidden;
    transition: var(--transition);
    padding: 0 25px;
    background: white;
}

.guideline-card.expanded .guideline-content {
    max-height: 2000px;
    padding: 25px;
}

.guideline-list {
    list-style: none;
    padding: 0;
    margin: 0 0 25px 0;
}

.guideline-list li {
    padding: 12px 0;
    padding-left: 30px;
    position: relative;
    color: #555;
    line-height: 1.6;
    font-size: 0.95rem;
}

.guideline-list li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: var(--primary-light);
    font-weight: 700;
    font-size: 1.1rem;
}

.highlight-box,
.success-box {
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
    font-size: 0.95rem;
    line-height: 1.6;
}

.highlight-box {
    background: linear-gradient(135deg, rgba(255, 152, 0, 0.1) 0%, rgba(255, 152, 0, 0.05) 100%);
    border-left: 4px solid var(--accent-orange);
    color: #666;
}

.success-box {
    background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
    border-left: 4px solid var(--primary-light);
    color: #333;
}

/* SCHEDULE TABLE */
.schedule-wrapper {
    overflow-x: auto;
    margin-bottom: 25px;
}

.schedule-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 25px;
}

.schedule-table thead {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
    color: white;
}

.schedule-table th {
    padding: 16px;
    text-align: left;
    font-weight: 700;
    border: none;
    letter-spacing: 0.3px;
}

.schedule-table td {
    padding: 16px;
    border-bottom: 1px solid #e0e0e0;
    color: #555;
    font-size: 0.95rem;
}

.schedule-table tbody tr:hover {
    background: rgba(76, 175, 80, 0.05);
}

.status-label {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
}

.status-label.active {
    background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
    color: white;
}

.status-label.inactive {
    background: #e0e0e0;
    color: #999;
}

/* TIPS SECTION */
.tips-section {
    padding: 20px;
    background: linear-gradient(135deg, #f0f8f5 0%, #e8f5e9 100%);
    border-radius: 10px;
    border: 2px solid var(--primary-light);
}

.tips-section h4 {
    margin: 0 0 15px 0;
    color: var(--primary-dark);
    font-size: 1.1rem;
}

.tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tips-list li {
    padding: 10px 0;
    padding-left: 30px;
    position: relative;
    color: #555;
    font-size: 0.95rem;
    line-height: 1.5;
}

.tips-list li::before {
    content: '💡';
    position: absolute;
    left: 0;
}

/* SEARCH CONTAINER */
.search-container {
    margin-bottom: 35px;
    animation: fadeIn 0.6s ease-out;
}

.search-box {
    position: relative;
    max-width: 550px;
}

.search-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.4rem;
    color: var(--primary-light);
    transition: all 0.3s ease;
}

.search-box input:focus ~ .search-icon {
    color: var(--primary-medium);
    transform: translateY(-50%) scale(1.1);
}

.search-box input {
    width: 100%;
    padding: 16px 18px 16px 55px;
    border: 2px solid #e8e8e8;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    background: white;
    box-shadow: var(--shadow-sm);
}

.search-box input:hover {
    border-color: var(--primary-light);
    box-shadow: var(--shadow-md);
}

.search-box input:focus {
    outline: none;
    border-color: var(--primary-medium);
    box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.1), var(--shadow-md);
    transform: translateY(-2px);
}

/* FAQ FILTERS */
.faq-filters {
    display: flex;
    gap: 12px;
    margin-bottom: 35px;
    flex-wrap: wrap;
    animation: fadeIn 0.6s ease-out;
}

.filter-btn {
    padding: 12px 24px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    background: white;
    color: #666;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    letter-spacing: 0.3px;
    position: relative;
    overflow: hidden;
}

.filter-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(76, 175, 80, 0.1);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.filter-btn:hover::before {
    width: 300px;
    height: 300px;
}

.filter-btn:hover {
    border-color: var(--primary-light);
    color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
}

.filter-btn.active {
    background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
    border-color: var(--primary-light);
    color: white;
    box-shadow: 0 4px 16px rgba(76, 175, 80, 0.3);
    transform: translateY(-2px);
}

.filter-btn.active:hover {
    background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
}

/* FAQ LIST */
.faq-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    animation: fadeIn 0.5s ease-out;
}

.faq-item {
    background: white;
    border-radius: 12px;
    border: 2px solid #e8e8e8;
    overflow: hidden;
    transition: var(--transition);
}

.faq-item:hover {
    border-color: var(--accent-orange);
    box-shadow: var(--shadow-md);
}

.faq-question {
    padding: 20px 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f0 100%);
    transition: var(--transition);
    font-weight: 600;
    color: var(--primary-dark);
}

.faq-item:hover .faq-question {
    background: linear-gradient(135deg, rgba(255, 152, 0, 0.05) 0%, rgba(76, 175, 80, 0.05) 100%);
}

.faq-category {
    display: inline-block;
    padding: 4px 10px;
    background: var(--primary-light);
    color: white;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 8px;
    letter-spacing: 0.3px;
}

.faq-toggle {
    font-size: 1.2rem;
    color: var(--primary-dark);
    transition: var(--transition);
    flex-shrink: 0;
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    background: white;
}

.faq-answer.open {
    max-height: 500px;
}

.faq-answer p {
    padding: 25px;
    margin: 0;
    color: #666;
    line-height: 1.8;
    font-weight: 400;
    font-size: 0.95rem;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* RESPONSIVE DESIGN */
@media (max-width: 1024px) {
    .help-wrapper {
        margin: 20px auto;
    }

    .help-header {
        padding: 40px 35px;
    }

    .help-header-content h1 {
        font-size: 2.5rem;
    }
}

@media (max-width: 768px) {
    .help-wrapper {
        margin: 15px;
    }

    .help-header {
        padding: 35px 30px;
        margin-bottom: 35px;
        border-radius: 14px;
    }
    
    .help-header-content h1 {
        font-size: 2.2rem;
    }
    
    .help-header-content p {
        font-size: 1.05rem;
    }
    
    .section-title {
        font-size: 1.7rem;
        margin-bottom: 25px;
    }
    
    .contact-cards {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .ticket-body {
        padding: 30px;
    }
    
    .guidelines-body {
        padding: 30px;
    }
    
    .guidelines-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .guideline-card-wide {
        grid-column: 1;
    }
    
    .schedule-wrapper {
        font-size: 0.85rem;
    }
    
    .schedule-table th,
    .schedule-table td {
        padding: 12px;
    }
    
    .faq-filters {
        gap: 10px;
    }
    
    .filter-btn {
        padding: 10px 18px;
        font-size: 0.9rem;
    }

    .btn-submit,
    .btn-reset {
        padding: 14px 30px;
    }
}

@media (max-width: 480px) {
    .help-wrapper {
        padding: 0;
        margin: 10px;
    }
    
    .help-header {
        padding: 30px 25px;
        margin-bottom: 30px;
        border-radius: 12px;
    }
    
    .help-header-content h1 {
        font-size: 1.8rem;
    }

    .help-header-content p {
        font-size: 1rem;
    }
    
    .section-title {
        font-size: 1.4rem;
        padding: 1px 15px;
    }
    
    .ticket-body,
    .guidelines-body {
        padding: 25px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 18px;
    }
    
    .ticket-actions {
        flex-direction: column;
        gap: 12px;
    }
    
    .btn-submit,
    .btn-reset {
        width: 100%;
        justify-content: center;
        padding: 14px 24px;
    }
    
    .guideline-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
        padding: 22px;
    }
    
    .guideline-header > div {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .contact-card {
        padding: 30px 25px;
    }

    .contact-icon {
        font-size: 3.5rem;
    }

    .search-box {
        max-width: 100%;
    }

    .filter-btn {
        padding: 9px 16px;
        font-size: 0.85rem;
    }
}
</style>

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
                    <div class="contact-icon"><i class="fas fa-envelope" style="color: var(--primary-light);"></i></div>
                    <h3>Email Support</h3>
                    <p>Send us your inquiry and we'll respond within 24 hours</p>
                    <a href="mailto:support@school.edu">kld.edu.ph</a>
                </div>

                <div class="contact-card">
                    <div class="contact-icon"><i class="fas fa-phone" style="color: var(--accent-orange);"></i></div>
                    <h3>Phone Support</h3>
                    <p>Call us during office hours for immediate assistance</p>
                    <p style="color: #1b5e20; font-weight: 700; font-size: 16px; margin: 10px 0 0 0;">+63 (555) 123-4567</p>
                    <p style="font-size: 12px; color: #999;">Mon-Sat: 8AM-5PM</p>
                </div>

                <div class="contact-card">
                    <div class="contact-icon"><i class="fas fa-building" style="color: var(--primary-dark);"></i></div>
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

                <form method="post" id="ticketForm">
                    <div class="form-group required">
                        <label>Subject</label>
                        <input type="text" name="subject" placeholder="Brief description of your issue" required>
                    </div>

                    <div class="form-group required">
                        <label>Message</label>
                        <textarea name="message" rows="6" placeholder="Please provide detailed information about your issue..." required></textarea>
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

                    <div class="ticket-actions">
                        <button type="submit" name="submit_ticket" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Ticket</button>
                        <button type="reset" class="btn-reset"><i class="fas fa-redo"></i> Clear</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- FAQ SECTION -->
        <div>
            <div class="section-title">ID Guidelines & Requirements</div>

            <!-- QUICK GUIDELINES OVERVIEW -->
            <div class="guidelines-container">
                <div class="guidelines-header">
                    ID Guidelines & Requirements
                </div>
                <div class="guidelines-body">
                    <div class="guidelines-grid">
                        <!-- PHOTO REQUIREMENTS CARD -->
                        <div class="guideline-card" onclick="toggleGuidelineCard(this)">
                            <div class="guideline-header">
                                <div>
                                    <div class="guideline-icon"><i class="fas fa-camera" style="color: var(--accent-orange);"></i></div>
                                    <h4>Photo Requirements</h4>
                                </div>
                                <span class="guideline-toggle">▼</span>
                            </div>
                            <div class="guideline-content">
                                <ul class="guideline-list">
                                    <li><strong>Size:</strong> 3x4 inches or 4x6 inches</li>
                                    <li><strong>Format:</strong> JPG or PNG file only</li>
                                    <li><strong>File Size:</strong> Maximum 5MB</li>
                                    <li><strong>Background:</strong> Plain white or light neutral color (no patterns or busy backgrounds)</li>
                                    <li><strong>Lighting:</strong> Clear, well-lit photo with no shadows on the face</li>
                                    <li><strong>Face Position:</strong> Full face view, centered with natural expression</li>
                                    <li><strong>Clothing:</strong> School uniform or white/light solid-colored top</li>
                                    <li><strong>Accessories:</strong> No sunglasses or heavy makeup; light glasses are acceptable</li>
                                    <li><strong>Hair:</strong> Should not cover forehead or eyes - face must be clearly visible</li>
                                    <li><strong>Quality:</strong> Clear, sharp image with good contrast (no blurry or pixelated photos)</li>
                                </ul>
                                <div class="highlight-box">
                                    ⚠️ Photos not meeting these requirements may be rejected. Ensure proper lighting and clear facial visibility for best results.
                                </div>
                            </div>
                        </div>

                        <!-- CLOTHING GUIDELINES CARD -->
                        <div class="guideline-card" onclick="toggleGuidelineCard(this)">
                            <div class="guideline-header">
                                <div>
                                    <div class="guideline-icon"><i class="fas fa-shirt" style="color: var(--primary-light);"></i></div>
                                    <h4>Allowed Clothing</h4>
                                    </div>
                                <span class="guideline-toggle">▼</span>
                            </div>
                            <div class="guideline-content">
                                <ul class="guideline-list">
                                    <li><strong>RECOMMENDED:</strong> School uniform (white shirt with school logo or crest)</li>
                                    <li><strong>ACCEPTABLE:</strong> White, cream, or light gray solid-colored tops or shirts</li>
                                    <li><strong>ACCEPTABLE:</strong> Light blue or light neutral colored formal attire</li>
                                    <li><strong>NOT ALLOWED:</strong> Graphic t-shirts with logos, characters, or designs</li>
                                    <li><strong>NOT ALLOWED:</strong> Patterned clothing (stripes, checkered, floral, etc.)</li>
                                    <li><strong>NOT ALLOWED:</strong> Dark or bright neon colors</li>
                                    <li><strong>NOT ALLOWED:</strong> Tank tops, sleeveless, or revealing clothing</li>
                                    <li><strong>NOT ALLOWED:</strong> Casual wear like hoodies, sweatshirts, or jackets</li>
                                    <li><strong>NOT ALLOWED:</strong> Dirty, torn, or wrinkled clothing</li>
                                    <li><strong>Collar:</strong> Collared shirts are preferred but not mandatory</li>
                                </ul>
                                <div class="success-box">
                                    ✓ Pro tip: Wear the school uniform whenever possible. This ensures consistency and compliance with all school ID requirements.
                                </div>
                            </div>
                        </div>

                        <!-- PICKUP LOCATION CARD -->
                        <div class="guideline-card" onclick="toggleGuidelineCard(this)">
                            <div class="guideline-header">
                                <div>
                                    <div class="guideline-icon"><i class="fas fa-map-marker-alt" style="color: var(--primary-medium);"></i></div>
                                    <h4>Pickup Location</h4>
                                    </div>
                                <span class="guideline-toggle">▼</span>
                            </div>
                            <div class="guideline-content">
                                <ul class="guideline-list">
                                    <li><strong>Location:</strong> Registrar's Office, Building 1, Room 101</li>
                                    <li><strong>Address:</strong> Kolehiyo ng Lungsod ng Dasmariñas, Dasmariñas City, Cavite</li>
                                    <li><strong>Office Hours:</strong> Monday - Saturday, 8:00 AM - 5:00 PM</li>
                                    <li><strong>Closed:</strong> Sundays and School Holidays</li>
                                    <li><strong>Bring:</strong> Valid student ID (if available) and pickup reference number</li>
                                    <li><strong>Authorized Person:</strong> You may authorize someone to pick up your ID with a written authorization letter and ID</li>
                                    <li><strong>Processing:</strong> Pickup takes 2-3 minutes. Verify your ID details before leaving</li>
                                    <li><strong>Replacement Fee:</strong> Lost/damaged ID replacement fee applies (see fees page)</li>
                                    <li><strong>Expired ID:</strong> Old IDs must be returned with pickup of replacement</li>
                                    <li><strong>Questions:</strong> Contact Registrar's Office or call +63 (555) 123-4567</li>
                                </ul>
                                <div class="highlight-box">
                                    ⚠️ IDs not claimed within 30 days will be returned to storage. Contact the office to reschedule pickup if needed.
                                </div>
                            </div>
                        </div>

                        <!-- PRINTING SCHEDULE CARD -->
                        <div class="guideline-card guideline-card-wide" onclick="toggleGuidelineCard(this)">
                            <div class="guideline-header">
                                <div>
                                    <div class="guideline-icon"><i class="fas fa-calendar-alt" style="color: var(--accent-orange-dark);"></i></div>
                                    <h4>Printing Schedule</h4>
                                    </div>
                                <span class="guideline-toggle">▼</span>
                            </div>
                            <div class="guideline-content">
                                <div class="schedule-wrapper">
                                    <table class="schedule-table">
                                        <thead>
                                            <tr>
                                                <th>Submission Period</th>
                                                <th>Processing Time</th>
                                                <th>Expected Pickup Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Monday - Wednesday</td>
                                                <td>3-4 Business Days</td>
                                                <td>Following Friday - Monday</td>
                                                <td><span class="status-label active">Active</span></td>
                                            </tr>
                                            <tr>
                                                <td>Thursday - Friday</td>
                                                <td>4-5 Business Days</td>
                                                <td>Following Wednesday - Thursday</td>
                                                <td><span class="status-label active">Active</span></td>
                                            </tr>
                                            <tr>
                                                <td>Saturday</td>
                                                <td>4-5 Business Days</td>
                                                <td>Following Thursday - Friday</td>
                                                <td><span class="status-label active">Active</span></td>
                                            </tr>
                                            <tr>
                                                <td>Replacement Requests</td>
                                                <td>5-7 Business Days</td>
                                                <td>Following Monday - Wednesday</td>
                                                <td><span class="status-label active">Active</span></td>
                                            </tr>
                                            <tr>
                                                <td>Update Information</td>
                                                <td>3-5 Business Days</td>
                                                <td>Following Friday - Tuesday</td>
                                                <td><span class="status-label active">Active</span></td>
                                            </tr>
                                            <tr>
                                                <td>School Holidays</td>
                                                <td>Extended (Check Calendar)</td>
                                                <td>After Holiday Period</td>
                                                <td><span class="status-label inactive">Suspended</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="tips-section">
                                    <h4>💡 Important Notes</h4>
                                    <ul class="tips-list">
                                        <li>All dates are estimates. You will receive an email notification when your ID is ready</li>
                                        <li>Weekends and holidays are not counted as business days</li>
                                        <li>Processing times may extend during peak periods (start of school year)</li>
                                        <li>Check your email regularly for status updates and notification</li>
                                        <li>If there are issues with your photo or information, the office will contact you</li>
                                        <li>Late submissions may push your processing to the next batch</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FREQUENTLY ASKED QUESTIONS -->
            <div style="margin-top: 50px;">
                <div class="section-title">Frequently Asked Questions</div>

                <!-- SEARCH BAR -->
                <div class="search-container">
                    <div class="search-box">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
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
    </div>

    <!-- BACK TO TOP BUTTON -->
    <div id="backToTopBtn" class="back-to-top">
        <i class="fas fa-chevron-up"></i>
    </div>

    <style>
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
        color: white;
        padding: 16px 18px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.3rem;
        box-shadow: 0 6px 20px rgba(76, 175, 80, 0.25), 0 2px 8px rgba(76, 175, 80, 0.15);
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        z-index: 999;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .back-to-top.visible {
        opacity: 1;
    }

    .back-to-top:hover {
        transform: translateY(-6px) scale(1.1);
        box-shadow: 0 12px 32px rgba(76, 175, 80, 0.35), 0 4px 12px rgba(76, 175, 80, 0.2);
        background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
    }

    .back-to-top:active {
        transform: translateY(-4px) scale(1.05);
    }

    .back-to-top i {
        transition: transform 0.3s ease;
    }

    .back-to-top:hover i {
        transform: translateY(-2px);
    }

    @media (max-width: 480px) {
        .back-to-top {
            bottom: 20px;
            right: 20px;
            padding: 14px 16px;
            font-size: 1.1rem;
        }
    }
    </style>

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

        // Back-to-Top Button Functionality
        window.addEventListener('scroll', function() {
            const backToTopBtn = document.getElementById('backToTopBtn');
            if (window.scrollY > 300) {
                backToTopBtn.style.display = 'flex';
            } else {
                backToTopBtn.style.display = 'none';
            }
        });

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Toggle guideline card expansion
        function toggleGuidelineCard(cardElement) {
            const isExpanded = cardElement.classList.contains('expanded');

            if (isExpanded) {
                // Close the card
                cardElement.classList.remove('expanded');
            } else {
                // Close all other cards first
                document.querySelectorAll('.guideline-card.expanded').forEach(card => {
                    card.classList.remove('expanded');
                });

                // Open this card
                cardElement.classList.add('expanded');
            }
        }

        // Scroll to guideline section
        function scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                // Add highlight animation
                element.style.animation = 'none';
                setTimeout(() => {
                    element.style.animation = 'highlightPulse 0.6s ease-out';
                }, 10);
            }
        }

        // Add highlight animation style
        const highlightStyle = document.createElement('style');
        highlightStyle.textContent = `
            @keyframes highlightPulse {
                0% {
                    background-color: #fff9e6;
                    box-shadow: 0 0 0 0 rgba(27, 94, 32, 0.7);
                }
                50% {
                    background-color: #fffcf0;
                    box-shadow: 0 0 0 10px rgba(27, 94, 32, 0);
                }
                100% {
                    background-color: inherit;
                    box-shadow: 0 0 0 0 rgba(27, 94, 32, 0);
                }
            }
        `;
        document.head.appendChild(highlightStyle);

        // Back to top button functionality
        const backToTopBtn = document.getElementById('backToTopBtn');
        
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
            </div><!-- End admin-content -->
        </main><!-- End admin-main -->
    </div><!-- End admin-wrapper -->
</body>

</html>