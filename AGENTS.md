# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Setup/Run (non-std)
- `composer install` (autoloads dompdf/endroid/qr-code/phpmailer)
- XAMPP: Apache/MySQL; DB=school_id_system (root/'')
- APP_URL='http://localhost/school_id_practice' hardcoded in [`includes/config.php`](includes/config.php:9)

## No tests/lint/build scripts

## ID System (custom flow)
- Requests: id_requests -> approve -> issued_ids w/ generateId() in [`admin/admin.php`](admin/admin.php:131)
- ID format: YYYY###### (incremental 6-digits from issued_ids.id_number; locks w/ FOR UPDATE)
- QR: Endroid [`admin/admin.php`](admin/admin.php:160+) w/ [`assets/images/kldlogo.png`](assets/images/kldlogo.png); data=APP_URL/verify_id.php?n=ID -> [`uploads/qr/qr_*.png`](uploads/qr/)
- PDF: Dompdf CR80 landscape; hardcoded HTML front/back -> [`uploads/digital_id/email_*.pdf`](uploads/digital_id/)
- Bulk gen: transactions; cleanup files/DB on fail [`admin/admin.php`](admin/admin.php:342+)

## Gotchas
- Student row auto-INSERT IGNORE on first login [`index.php`](index.php:18)
- All classes extend [`includes/User.php`](includes/User.php) (inits PDO)
- Uploads: uniqid_time.ext; dirs auto-mkdir; MIME checks inconsistent
- Dev: error_reporting(E_ALL); display_errors=1 [`includes/config.php`](includes/config.php:3)
- admin/classes/: OOP managers (StudentManager etc.); FileUploader/Validators empty