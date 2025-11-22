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
    <link href="../assets/css/student.css" rel="stylesheet">
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