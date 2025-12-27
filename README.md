# KLD ID Issuance System v2.0

A comprehensive school ID issuance and management platform with secure student enrollment, automated ID generation, QR code integration, and digital PDF production.

---

## Table of Contents

- [Features](#features)
- [System Architecture](#system-architecture)
- [ID Generation Process](#id-generation-process)
- [Installation & Setup](#installation--setup)
- [Usage Guide](#usage-guide)
- [Project Structure](#project-structure)

---

## Features

### Core Functionality
- **Student Registration**: Self-service sign-up with email verification
- **Profile Management**: Students can update personal information and upload photos
- **ID Request System**: Students submit requests for ID generation
- **Admin Approval Workflow**: Review and approve/reject ID requests
- **Automated ID Generation**: System-generated unique ID numbers with custom format
- **QR Code Integration**: Each ID contains scannable QR code with verification link
- **PDF Generation**: Beautiful CR80-sized ID cards in landscape format
- **Bulk Generation**: Process multiple IDs in a single transaction with automatic cleanup on failure

### Security Features

#### Authentication & Access Control
- **Email Verification**: Two-step verification for new accounts
  - Unique token sent to email address
  - Token expires after 24 hours
  - Prevents fake email registrations
- **Session Management**: Automatic timeout after 30 minutes of inactivity
  - Sessions regenerated on login (prevents session fixation)
  - Secure session cookies with HttpOnly flag
  - CSRF tokens bound to user sessions
- **Role-Based Access Control**: Separate admin and student portals
  - Admin-only pages require authentication check
  - Student pages verify student session
  - Unauthorized access returns 403 error

#### Form & Request Security
- **CSRF Protection** ([`admin/classes/CsrfToken.php`](admin/classes/CsrfToken.php))
  - Token required on all forms (POST/PUT/DELETE)
  - Tokens generated per session and expire after 1 hour
  - Uses `hash_equals()` for timing-attack-resistant validation
  - Prevents cross-site request forgery attacks
  - Hidden token field automatically added to forms
- **Rate Limiting**: Login attempts limited to prevent brute force
  - Maximum 5 failed login attempts per 5 minutes
  - Temporary account lockout after threshold exceeded
  - IP-based tracking of attempts

#### File Upload Security
- **MIME Type Validation** ([`admin/classes/FileUploader.php`](admin/classes/FileUploader.php))
  - Images checked for actual MIME type (not just extension)
  - Whitelist of allowed file types: JPG, PNG, GIF
  - Blocks disguised executables
- **File Size Limits**
  - Profile photos: max 5MB
  - Signatures: max 2MB
  - Prevents disk space exhaustion attacks
- **Dangerous Extension Blocking**
  - Blocks: .php, .exe, .sh, .bat, .com, .pif, .scr, .vbs
  - Prevents code execution through uploads
- **Secure Filenames**
  - Uploaded files renamed with `uniqid()` + timestamp
  - Original filenames discarded
  - Prevents directory traversal attacks
- **Upload Directory Security**
  - Uploaded files stored outside web root (best practice)
  - `.htaccess` prevents direct script execution
  - Optional ClamAV virus scanning supported

#### Input Validation & Sanitization
- **XSS Prevention** ([`admin/classes/Validators.php`](admin/classes/Validators.php))
  - All user input checked for XSS patterns
  - Detects: `<script>`, event handlers, encoded attacks
  - HTML-encodes output with `esc_html()` function
  - Prevents stored and reflected XSS attacks
- **Input Validation** (20+ validators)
  - Email format validation
  - Password strength requirements:
    * Minimum 8 characters
    * Must contain: uppercase, lowercase, number, special char
  - Phone number format (Philippine: 09xx or +639xx)
  - Date validation with format checking
  - Date of birth validation (prevents future dates)
  - Prevents SQL injection through prepared statements
- **String Sanitization**
  - Whitespace trimming
  - HTML tag stripping
  - Special character encoding
  - Batch validation for multiple fields

#### Database Security
- **Prepared Statements**: All queries use parameterized statements
  - User input never directly concatenated into SQL
  - Prevents SQL injection attacks
  - Automatic escaping of special characters
- **Row-Level Locking**: ID generation uses `FOR UPDATE` lock
  - Prevents duplicate ID generation in concurrent requests
  - Ensures each ID number is unique
  - Database-level transaction isolation
- **Password Hashing**: All passwords stored with PHP `password_hash()`
  - Bcrypt algorithm with 12 salt rounds
  - Passwords never stored in plaintext
  - One-way hashing (passwords not recoverable)

#### Configuration & Secrets Management
- **Environment Variables** ([`.env` file](,.env))
  - All sensitive data in `.env`, not in code
  - Credentials never committed to version control
  - Supports different configs per environment (dev/staging/prod)
- **.gitignore Protection**
  - `.env` file explicitly ignored
  - Logs directory ignored
  - Upload directories ignored
  - Prevents credential leaks to GitHub
- **Hardcoded Defaults Removed**
  - Database passwords not in code
  - Email credentials not in config.php
  - API keys stored safely in environment

#### HTTP Security Headers
- **Content Security Policy (CSP)**
  - Restricts script sources to prevent XSS
  - Blocks inline scripts
  - Whitelist of trusted domains
- **X-Frame-Options**: Prevents clickjacking attacks
  - Pages can't be embedded in iframes
- **X-Content-Type-Options**: Prevents MIME type sniffing
- **Referrer-Policy**: Limits referrer information leakage

#### Audit & Logging
- **Comprehensive Audit Logging** ([`admin/classes/AuditLogger.php`](admin/classes/AuditLogger.php))
  - All ID generation logged with timestamp and admin
  - Student login/logout tracked
  - Profile updates recorded
  - File uploads logged
  - Failed login attempts tracked
  - Helps detect suspicious activity
- **Error Logging**
  - Errors logged to `logs/errors.log`
  - File-based, not exposed to users
  - Helps diagnose security issues

#### Additional Protections
- **Email Address Verification**
  - Students must verify email before account activation
  - Prevents disposable email abuse
  - Confirms email ownership
- **Transaction Safety**
  - Bulk ID generation wrapped in database transactions
  - Automatic rollback on failure
  - File cleanup on errors (prevents orphaned files)
- **Password Reset Security**
  - Reset tokens expire after 1 hour
  - Tokens are random and cryptographically secure
  - Can't reset without accessing email account
- **Error Message Security**
  - Generic error messages to users
  - Detailed errors logged internally
  - Prevents information disclosure about system structure

### Administrative Features
- **Student Management**: View, search, and manage student records
- **Audit Logging**: Track all ID generation and system changes
- **Reports**: Generate ID statistics and request reports
- **Bulk Print**: Export multiple IDs for printing

---

## System Architecture

### Technology Stack
- **Backend**: PHP 8.2.12
- **Database**: MySQL/MariaDB
- **Email**: Gmail SMTP (TLS encryption)
- **PDF Generation**: Dompdf library
- **QR Code**: Endroid QR Code library
- **Environment**: XAMPP (Apache + MySQL)

### Database Schema
- **students**: Student account and profile data
- **id_requests**: Pending ID requests from students
- **issued_ids**: Successfully generated and issued IDs
- **audit_logs**: System activity tracking
- **email_verification**: Email confirmation tokens

---

## ID Generation Process

### Step-by-Step Workflow

#### Phase 1: Student Request (Student Portal)
1. Student logs into `/index.php` (student dashboard)
2. Navigates to "Request ID" section
3. Fills in ID request form (if additional info needed)
4. Submits request → Record created in `id_requests` table with status `pending`

#### Phase 2: Admin Review (Admin Panel)
1. Admin logs into `/admin/admin.php` dashboard
2. Views "ID Requests" section showing all pending requests
3. Reviews student details:
   - Full name
   - Birth date
   - Student photo
   - Signature image
4. Admin chooses to **Approve** or **Reject**

#### Phase 3: ID Generation (generateId Function)
**Location**: [`admin/admin.php`](admin/admin.php#L131)

When admin clicks **Approve**, the system executes:

**Step 1: Calculate Next ID Number**
```
- Query issued_ids table for highest id_number
- Lock row with FOR UPDATE (prevents concurrent duplicates)
- Increment by 1
- ID format: YYYY###### 
  Example: 2025000001 (year 2025 + 6-digit sequential)
```

**Step 2: Generate QR Code**
```
- Data: http://localhost/KLD-ID-ISSUANCE-SYSTEM-V2/verify_id.php?n=2025000001
- Embed KLD logo from assets/images/kldlogo.png
- Generate PNG image
- Save to uploads/qr/qr_[timestamp].png
```

**Step 3: Generate PDF ID Card**
```
- Use Dompdf library
- Format: CR80 landscape (8.56" x 5.4")
- Content:
  * Front side: Student photo, name, ID number, QR code
  * Back side: School logo, issue date, validity info
- Save to uploads/digital_id/email_[timestamp].pdf
```

**Step 4: Database Update**
```
- Insert into issued_ids table:
  * id_number: 2025000001
  * student_id: (linked to student)
  * issue_date: NOW()
  * status: active
  * qr_path: uploads/qr/qr_[timestamp].png
  * pdf_path: uploads/digital_id/email_[timestamp].pdf
- Update id_requests: status = approved
```

#### Phase 4: Delivery & Verification
1. Admin downloads or prints PDF from admin panel
2. PDF delivered to student (physical or digital)
3. Anyone can verify ID authenticity:
   - Scan QR code → redirects to `/verify_id.php?n=ID_NUMBER`
   - System displays: Student name, photo, ID number, issue date
   - Proves ID is legitimate and in system

### ID Format Specification
- **Pattern**: `YYYY######`
- **Year**: Calendar year (e.g., 2025)
- **Sequential**: 6-digit auto-increment (000001, 000002, etc.)
- **Examples**: 
  - 2025000001 (First ID issued in 2025)
  - 2025000042 (42nd ID issued in 2025)
  - 2026000001 (Resets in new year)

### Key Technical Details
- **Lock Mechanism**: Uses MySQL `FOR UPDATE` to prevent duplicate IDs during concurrent requests
- **Transaction Safety**: Bulk generation wrapped in database transactions with rollback on failure
- **File Cleanup**: If PDF/QR generation fails, temporary files are automatically deleted
- **Idempotency**: Re-approving same request creates new ID (not recommended)

---

## Installation & Setup

### Prerequisites
- XAMPP with PHP 8.2.12+
- MySQL/MariaDB
- Composer package manager
- Gmail account with app passwords enabled

### Installation Steps

**1. Create .env Configuration File**
```bash
cd C:\xampp\htdocs\KLD-ID-ISSUANCE-SYSTEM-V2
copy .env.example .env
```

**2. Edit .env with Your Settings**
```
APP_URL=http://localhost/KLD-ID-ISSUANCE-SYSTEM-V2
APP_DEBUG=false

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-email@gmail.com

DB_HOST=localhost
DB_PORT=3306
DB_NAME=school_id_system
DB_USER=root
DB_PASSWORD=
```

**3. Install Dependencies**
```bash
composer install
```

**4. Create Database**
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Create database: `school_id_system`
- Import: `school_id_system.sql`

**5. Create Upload Directories**
```bash
mkdir uploads\qr
mkdir uploads\digital_id
mkdir uploads\student_photos
mkdir uploads\student_signatures
mkdir uploads\student_cor
mkdir logs
```

**6. Verify Installation**
- Navigate to: `http://localhost/KLD-ID-ISSUANCE-SYSTEM-V2`
- Create test student account
- Verify email notification received

---

## Usage Guide

### For Students

**Registration**
1. Visit system homepage
2. Click "Sign Up"
3. Enter: email, password, full name
4. Verify email (check inbox)
5. Complete profile: birthdate, photo, signature

**Request ID**
1. Login to dashboard
2. Click "Request ID"
3. Review information
4. Submit request
5. Wait for admin approval (can take 1-5 business days)

**View ID**
1. Once approved, "My ID" section shows downloadable PDF
2. Download for printing or digital storage
3. Share ID with others for verification

### For Administrators

**Login**
- URL: `http://localhost/KLD-ID-ISSUANCE-SYSTEM-V2/admin/admin.php`
- Default admin created manually in database

**Manage Requests**
1. Dashboard → "ID Requests"
2. Review student details and request info
3. Click "Approve" to generate ID immediately
4. Click "Reject" to deny request with reason

**Generate IDs**
1. Click "Approve" on pending request
2. System automatically:
   - Creates unique ID number
   - Generates QR code
   - Creates PDF card
   - Updates database
3. ID ready for download/printing

**Reports**
1. Dashboard → "Reports"
2. View: Total IDs issued, requests pending, monthly statistics
3. Export data for records

**Manage Students**
1. Dashboard → "Students"
2. Search, view, update student information
3. Resend email verification if needed

---

## Project Structure

```
KLD-ID-ISSUANCE-SYSTEM-V2/
├── admin/
│   ├── admin.php                    # Main admin dashboard
│   ├── admin_dashboard.php          # Dashboard overview
│   ├── admin_id.php                 # ID management
│   ├── admin_students.php           # Student management
│   ├── admin_user.php               # User management
│   ├── admin_logs.php               # Audit logs viewer
│   ├── admin_reports.php            # Reports & statistics
│   ├── classes/
│   │   ├── AuditLogger.php         # Log system activity
│   │   ├── IdManager.php           # ID generation logic
│   │   ├── StudentManager.php      # Student database ops
│   │   ├── UserManager.php         # User authentication
│   │   ├── ReportsManager.php      # Report generation
│   │   ├── CsrfToken.php           # CSRF protection
│   │   ├── SecurityMiddleware.php  # Auth & security checks
│   │   ├── FileUploader.php        # Secure file handling
│   │   ├── Validators.php          # Input validation
│   │   └── EmailVerification.php   # Email confirmation logic
│   └── student_details.php          # View individual student
├── student/
│   ├── student.php                  # Student dashboard
│   ├── student_home.php             # Home page
│   ├── student_profile.php          # Profile management
│   ├── student_id.php               # View/download ID
│   ├── edit_profile.php             # Update profile
│   └── student_help.php             # Help & support
├── includes/
│   ├── config.php                   # App configuration
│   ├── db.php                       # Database connection
│   ├── User.php                     # User base class
│   ├── logout.php                   # Session termination
│   ├── register.php                 # Registration logic
│   └── complete_profile.php         # Profile completion
├── uploads/
│   ├── qr/                          # Generated QR codes
│   ├── digital_id/                  # Generated PDFs
│   ├── student_photos/              # Student profile images
│   ├── student_signatures/          # Student signature images
│   └── student_cor/                 # Certificate of registration
├── assets/
│   ├── css/                         # Stylesheets (Bootstrap)
│   ├── js/                          # JavaScript libraries
│   └── images/                      # Logos and assets
├── .env                             # Environment configuration
├── composer.json                    # PHP dependencies
├── school_id_system.sql             # Database schema
└── verify_id.php                    # Public QR code verification page
```

---

## Common Tasks

### Generate a Single ID
1. Admin dashboard → ID Requests
2. Click "Approve" on student request
3. System auto-generates ID number, QR, PDF
4. Download PDF from dashboard

### Generate Bulk IDs
1. Admin dashboard → Bulk Print
2. Select multiple pending requests (checkbox)
3. Click "Generate All"
4. System creates transactions, generates IDs for each
5. Downloads zip file with all PDFs

### Verify an ID (Public)
1. Scan QR code on ID card
2. Redirects to: `verify_id.php?n=[ID_NUMBER]`
3. Shows: Student name, photo, ID number, issue date
4. Confirms ID is legitimate

### Resend Email Verification
1. Admin → Students
2. Select student
3. Click "Resend Verification Email"
4. Student receives new verification link

---

## Support & Troubleshooting

### Common Issues

**Q: PDF not generating**
- Check `uploads/digital_id/` directory exists and is writable
- Verify Dompdf library installed: `composer install`
- Check error logs: `logs/errors.log`

**Q: QR code not scanning**
- Ensure `uploads/qr/` directory exists
- Check Endroid library installed: `composer install`
- Verify APP_URL in .env is correct

**Q: Email not sending**
- Verify Gmail SMTP settings in .env
- Ensure Gmail app password (not regular password)
- Check `MAIL_HOST=smtp.gmail.com` and `MAIL_PORT=587`
- View mail logs for error details

**Q: Database errors**
- Verify `school_id_system` database created
- Run: `mysql -u root < school_id_system.sql`
- Check DB_* settings in .env match your setup

**Q: Login issues**
- Verify student/admin user exists in database
- Check password hasn't expired or been reset
- Clear browser cookies and try again

---

## Security Notes

- **Never** commit `.env` file to version control
- All files are protected by `.gitignore`
- Credentials stored in environment variables only
- Database passwords not in code
- CSRF tokens protect all forms
- File uploads validated for MIME type and extension
- Input sanitized against XSS attacks

---

## Version History

- **v2.0** (Current): Enhanced security, email verification, QR codes, PDF generation
- **v1.0**: Initial release

---

## License & Credits

Developed for KLD School System.

Contributors:
- CABALLERO
- CONCEPCION
- DAPAT
- CAPUNGCOL
- CERTIFICO
