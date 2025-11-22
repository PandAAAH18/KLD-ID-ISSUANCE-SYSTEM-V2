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
            padding: 25px 30px;
            border-radius: 12px;
            margin-top: 95px;
            margin-bottom: 30px;
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

        /* ▬▬▬▬ ID GUIDELINES SECTION ▬▬▬▬ */
        .guidelines-container {
            background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(27, 94, 32, 0.12), 0 0 1px rgba(27, 94, 32, 0.05);
            overflow: hidden;
            margin-bottom: 60px;
            border-left: 8px solid #1b5e20;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .guidelines-container::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(27, 94, 32, 0.05) 0%, transparent 70%);
            border-radius: 0 20px 0 0;
            pointer-events: none;
        }

        .guidelines-container:hover {
            box-shadow: 0 20px 60px rgba(27, 94, 32, 0.16), 0 0 1px rgba(27, 94, 32, 0.08);
            transform: translateY(-6px);
            border-left-color: #0d3817;
        }

        .guidelines-header {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            padding: 45px 50px;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.4px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            box-shadow: inset 0 -4px 15px rgba(0, 0, 0, 0.1);
        }

        .guidelines-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, rgba(255,255,255,0.3) 0%, transparent 100%);
        }

        .guidelines-body {
            padding: 50px 50px;
            position: relative;
            z-index: 1;
        }

        .guidelines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 32px;
            margin-top: 20px;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .guideline-card {
            background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
            border: 2px solid #e8e8e8;
            border-radius: 14px;
            padding: 32px 28px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08), 0 0 1px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
        }

        .guideline-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #1b5e20 0%, #0d3817 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .guideline-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(27, 94, 32, 0.04) 0%, transparent 70%);
            border-radius: 0 14px 0 0;
            pointer-events: none;
        }

        .guideline-card:hover {
            border-color: #1b5e20;
            box-shadow: 0 20px 40px rgba(27, 94, 32, 0.16), 0 0 1px rgba(27, 94, 32, 0.06);
            transform: translateY(-14px) scale(1.03);
            background: linear-gradient(135deg, #ffffff 0%, #f5faf5 100%);
        }

        .guideline-card:hover::before {
            transform: scaleX(1);
        }

        .guideline-card.expanded {
            transform: none;
            box-shadow: 0 12px 32px rgba(27, 94, 32, 0.14);
            background: linear-gradient(135deg, #ffffff 0%, #f9fff9 100%);
        }

        .guideline-icon {
            font-size: 48px;
            margin-bottom: 18px;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: block;
            filter: drop-shadow(0 4px 8px rgba(27, 94, 32, 0.1));
        }

        .guideline-card:hover .guideline-icon {
            transform: scale(1.05) translateY(1px);
            filter: drop-shadow(0 8px 16px rgba(27, 94, 32, 0.15));
        }

        .guideline-card.expanded .guideline-icon {
            transform: none;
            filter: drop-shadow(0 4px 8px rgba(27, 94, 32, 0.1));
        }

        .guideline-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            cursor: pointer;
            flex-grow: 1;
            position: relative;
            z-index: 2;
        }

        .guideline-card h4 {
            margin: 0 0 12px 0;
            color: #1b5e20;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.3px;
            line-height: 1.4;
        }

        .guideline-card p {
            margin: 0;
            color: #666;
            font-size: 14px;
            line-height: 1.7;
            font-weight: 500;
        }

        .guideline-toggle {
            font-size: 22px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            color: #1b5e20;
            font-weight: 800;
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(27, 94, 32, 0.08);
            border-radius: 8px;
            position: relative;
            z-index: 2;
        }

        .guideline-card:hover .guideline-toggle {
            background: rgba(27, 94, 32, 0.12);
            transform: scale(1.1);
        }

        .guideline-card.expanded .guideline-toggle {
            transform: rotate(180deg) scale(1.1);
            background: rgba(27, 94, 32, 0.15);
        }

        .guideline-content {
            max-height: 0;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            padding-top: 0;
            position: relative;
            z-index: 2;
        }

        .guideline-card.expanded .guideline-content {
            max-height: 2000px;
            padding-top: 28px;
            border-top: 2px solid #e8e8e8;
            animation: expandContentFade 0.4s ease-out;
        }

        @keyframes expandContentFade {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* WIDE CARD LAYOUT - PRINTING SCHEDULE */
        @media (min-width: 769px) {
            .guideline-card-wide {
                grid-column: 1 / -1;
            }
        }

        .guideline-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #1b5e20;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 6px;
            background: rgba(27, 94, 32, 0.08);
            transition: all 0.3s ease;
            margin-top: 10px;
            border: 1px solid transparent;
            letter-spacing: -0.2px;
        }

        .guideline-link:hover {
            background: #1b5e20;
            color: white;
            border-color: #1b5e20;
            transform: translateX(4px);
        }

        .guideline-link::after {
            content: '→';
            transition: transform 0.3s ease;
        }

        .guideline-card:hover .guideline-link::after {
            transform: translateX(4px);
        }

        /* ▬▬▬▬ DETAILED GUIDELINES ▬▬▬▬ */
        .detailed-guideline {
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            border-left: 6px solid #1b5e20;
        }

        .detailed-guideline:hover {
            border-color: #1b5e20;
            box-shadow: 0 6px 20px rgba(27, 94, 32, 0.12);
        }

        .detailed-guideline h3 {
            margin: 0 0 20px 0;
            color: #1b5e20;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .guideline-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .guideline-list li {
            padding: 14px 0;
            border-bottom: 1px solid #e8e8e8;
            color: #555;
            font-size: 15px;
            line-height: 1.8;
            font-weight: 500;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .guideline-list li:last-child {
            border-bottom: none;
        }

        .guideline-list li::before {
            content: '✓';
            color: #1b5e20;
            font-weight: 800;
            font-size: 18px;
            flex-shrink: 0;
            margin-top: -2px;
        }

        .requirement-tag {
            display: inline-block;
            background: #e8f5e9;
            color: #1b5e20;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #1b5e20;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-top: 8px;
        }

        .highlight-box {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-left: 6px solid #ff9800;
            padding: 20px 24px;
            border-radius: 8px;
            margin-top: 20px;
            color: #e65100;
            font-weight: 600;
            font-size: 14px;
            line-height: 1.7;
        }

        .success-box {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border-left: 6px solid #4caf50;
            padding: 20px 24px;
            border-radius: 8px;
            margin-top: 20px;
            color: #1b5e20;
            font-weight: 600;
            font-size: 14px;
            line-height: 1.7;
        }

        /* ▬▬▬▬ SCHEDULE TABLE ▬▬▬▬ */
        .schedule-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-top: 20px;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .schedule-table thead {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
        }

        .schedule-table th {
            padding: 18px 20px;
            font-weight: 700;
            text-align: left;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid rgba(27, 94, 32, 0.2);
        }

        .schedule-table td {
            padding: 18px 20px;
            border-bottom: 1px solid #e8e8e8;
            font-size: 14px;
            color: #555;
            font-weight: 500;
        }

        .schedule-table tbody tr {
            transition: all 0.3s ease;
        }

        .schedule-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(27, 94, 32, 0.06) 0%, rgba(27, 94, 32, 0.02) 100%);
            border-left: 4px solid #1b5e20;
            padding-left: 16px;
        }

        .schedule-table tbody tr:last-child td {
            border-bottom: none;
        }

        .status-label {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .status-label.active {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #1b5e20;
            border: 1px solid #4caf50;
        }

        .status-label.inactive {
            background: linear-gradient(135deg, #f5f5f5 0%, #eeeeee 100%);
            color: #999;
            border: 1px solid #ddd;
        }

        /* ▬▬▬▬ TIPS SECTION ▬▬▬▬ */
        .tips-section {
            background: linear-gradient(135deg, #f0f8f5 0%, #e8f5e9 100%);
            border: 2px solid #c8e6c9;
            border-radius: 12px;
            padding: 30px;
            margin-top: 30px;
            border-left: 6px solid #1b5e20;
        }

        .tips-section h4 {
            margin: 0 0 20px 0;
            color: #1b5e20;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.2px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tips-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .tips-list li {
            padding: 12px 0 12px 30px;
            position: relative;
            color: #555;
            font-size: 14px;
            line-height: 1.7;
            font-weight: 500;
            border-bottom: 1px dashed #c8e6c9;
        }

        .tips-list li:last-child {
            border-bottom: none;
        }

        .tips-list li::before {
            content: '💡';
            position: absolute;
            left: 0;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .guidelines-container {
                margin-bottom: 45px;
                border-radius: 16px;
            }

            .guidelines-header {
                padding: 35px 30px;
                font-size: 22px;
                gap: 12px;
            }

            .guidelines-body {
                padding: 35px 30px;
            }

            .guidelines-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .guideline-card {
                padding: 28px 24px;
            }

            .guideline-icon {
                font-size: 44px;
                margin-bottom: 16px;
            }

            .guideline-card:hover {
                transform: translateY(-10px) scale(1.02);
            }

            .detailed-guideline {
                padding: 22px;
            }

            .guideline-list li {
                padding: 12px 0;
                font-size: 14px;
            }

            .schedule-table th,
            .schedule-table td {
                padding: 15px 12px;
                font-size: 13px;
            }

            .tips-section {
                padding: 26px;
            }

            .tips-list li {
                padding: 11px 0 11px 30px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .guidelines-container {
                margin-bottom: 35px;
                border-radius: 14px;
                box-shadow: 0 6px 20px rgba(27, 94, 32, 0.1);
            }

            .guidelines-container::before {
                width: 120px;
                height: 120px;
            }

            .guidelines-header {
                padding: 28px 20px;
                font-size: 20px;
                font-weight: 700;
                gap: 12px;
            }

            .guidelines-body {
                padding: 25px 20px;
            }

            .guidelines-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .guideline-card {
                padding: 24px 20px;
                border-radius: 12px;
            }

            .guideline-icon {
                font-size: 40px;
                margin-bottom: 14px;
            }

            .guideline-card h4 {
                font-size: 16px;
                margin-bottom: 10px;
            }

            .guideline-card p {
                font-size: 13px;
                line-height: 1.6;
            }

            .guideline-card:hover {
                transform: translateY(-8px) scale(1.01);
            }

            .guideline-toggle {
                width: 28px;
                height: 28px;
                font-size: 20px;
            }

            .detailed-guideline {
                padding: 18px;
                margin-bottom: 14px;
                border-radius: 10px;
            }

            .guideline-list li {
                padding: 11px 0;
                font-size: 13px;
                gap: 10px;
            }

            .schedule-table {
                font-size: 12px;
            }

            .schedule-table th,
            .schedule-table td {
                padding: 12px 10px;
                font-size: 12px;
            }

            .tips-section {
                padding: 20px;
                border-radius: 10px;
            }

            .tips-section h4 {
                font-size: 16px;
                margin-bottom: 16px;
            }

            .tips-list li {
                padding: 10px 0 10px 26px;
                font-size: 12px;
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

        /* Back-to-Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(27, 94, 32, 0.3);
            z-index: 999;
            transition: all 0.3s ease;
        }

        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(27, 94, 32, 0.4);
            background: linear-gradient(135deg, #0d3817 0%, #051a0f 100%);
        }

        .back-to-top:active {
            transform: translateY(-2px);
        }

        @media (max-width: 576px) {
            .back-to-top {
                width: 45px;
                height: 45px;
                bottom: 20px;
                right: 20px;
                font-size: 20px;
            }
        }
    </style>
</head>

<body class="admin-body">

    <!-- BACK-TO-TOP BUTTON -->
    <button id="backToTopBtn" class="back-to-top" onclick="scrollToTop()">↑</button>

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
            <div class="section-title">ID Guidelines & Requirements</div>

            <!-- QUICK GUIDELINES OVERVIEW -->
            <div class="guidelines-container">
                <div class="guidelines-header">
                    📋 ID Guidelines & Requirements
                </div>
                <div class="guidelines-body">
                    <div class="guidelines-grid">
                        <!-- PHOTO REQUIREMENTS CARD -->
                        <div class="guideline-card" onclick="toggleGuidelineCard(this)">
                            <div class="guideline-header">
                                <div>
                                    <div class="guideline-icon">📸</div>
                                    <h4>Photo Requirements</h4>
                                    <p>Recent professional photo (3x4 or 4x6) in JPG/PNG format with clear facial features and neutral background</p>
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
                                    <div class="guideline-icon">👕</div>
                                    <h4>Allowed Clothing</h4>
                                    <p>School uniform recommended. White or light-colored solid tops. Avoid graphics, logos, or patterned clothing</p>
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
                                    <div class="guideline-icon">📍</div>
                                    <h4>Pickup Location</h4>
                                    <p>Registrar's Office - Building 1, Room 101. Present valid student ID and reference number for pickup</p>
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
                                    <div class="guideline-icon">📅</div>
                                    <h4>Printing Schedule</h4>
                                    <p>Processing time: 3-5 business days. Check your email for notification when your ID is ready</p>
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
    </script>

</body>

</html>