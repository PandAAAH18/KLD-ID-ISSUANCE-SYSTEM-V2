<?php
if (extension_loaded('gd')) {
    echo "✓ GD Extension is ENABLED\n";
    echo "GD Version: " . gd_info()['GD Version'] . "\n";
} else {
    echo "✗ GD Extension is NOT enabled\n";
    echo "Please enable it in php.ini\n";
}
phpinfo(INFO_MODULES);
