# Project Architecture Rules (Non-Obvious Only)
- ID workflow: id_requests -> approve -> issued_ids (generateId w/ FOR UPDATE lock) centralized in [`admin/admin.php`](admin/admin.php)
- All classes extend [`includes/User.php`](includes/User.php) (PDO init); no DI
- Bulk gen: DB transactions + file unlink/rollback [`admin/admin.php`](admin/admin.php:342+)
- Student auto-INSERT IGNORE on login [`index.php`](index.php:18); no explicit create