<?php
/**
 * Local Autoloader for Admin Panel
 * Ensures the admin folder remains 100% portable and self-contained.
 */

spl_autoload_register(function ($class) {
    $prefix = 'Admin\\Models\\';
    $len = strlen($prefix);
    
    // Case-insensitive prefix check for compatibility (Admin\Models or admin\Models)
    if (strncmp($prefix, $class, $len) !== 0 && strncasecmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = __DIR__ . '/Models/' . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});
