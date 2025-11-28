# Upload File Issues - Fix Instructions

## Problem Identified
Your file uploads are failing because of PHP configuration limits that are too restrictive.

## Current PHP Settings
- Upload max filesize: 2M
- Post max size: 8M  
- File uploads enabled: YES

## Required Changes

### 1. Update PHP Configuration (php.ini)
Located typically at: `C:\xampp\php\php.ini`

Find and update these lines:
```ini
; Current values
upload_max_filesize = 2M
post_max_size = 8M
max_execution_time = 30
memory_limit = 128M

; Change to:
upload_max_filesize = 10M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M
```

### 2. Restart Apache Server
After editing php.ini, restart Apache in XAMPP Control Panel.

### 3. Verify Changes
Run this PHP code to verify:
```php
<?php
echo 'Upload max filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;
echo 'Post max size: ' . ini_get('post_max_size') . PHP_EOL;
?>
```

## What I've Fixed in Your Code

### 1. Added Debugging
- Enhanced error logging for all upload operations
- Better directory permission checking
- Detailed error messages in logs

### 2. Adjusted File Size Limits
- Reduced validation limits to match current PHP config (2MB)
- This will work with current settings until you update php.ini

### 3. Improved Error Handling
- Better directory creation and permission checks
- More informative error messages
- Proper validation feedback

## File Size Limits After Fix
Once you update php.ini:
- Profile photos: Up to 5MB
- Signatures: Up to 5MB  
- COR documents: Up to 10MB

## Testing Upload Functionality

1. Update php.ini as shown above
2. Restart Apache
3. Try uploading a small image (under 2MB) first
4. Check error logs at: `C:\xampp\apache\logs\error.log`
5. Check PHP error logs for detailed upload debugging

## Directory Permissions (Windows)
The upload directories should automatically be created with proper permissions. If issues persist:

1. Ensure the `uploads/` folder and subfolders are writable
2. Right-click folder → Properties → Security → Edit → Add "Full Control" for your web server user

## Quick Test
To test if uploads are working, use a small JPEG image (under 2MB) and check:
1. Browser developer tools → Network tab for form submission
2. PHP error logs for any upload errors
3. Check if files appear in the uploads folder

## Contact Support
If issues persist after following these steps, check:
1. PHP error logs
2. Apache error logs  
3. Browser developer console for JavaScript errors