# Project Debug Rules (Non-Obvious Only)
- Errors always on: error_reporting(E_ALL); display_errors=1 [`includes/config.php`](includes/config.php:3)
- DB conn fails: error_log + die("Fatal Error: ... DB 'school_id_system' not found") [`includes/db.php`](includes/db.php:20)
- No tests; debug via XAMPP Apache/MySQL + browser on localhost/school_id_practice
- Bulk ops rollback files/DB on fail (transactions) [`admin/admin.php`](admin/admin.php:342+)