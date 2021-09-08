<?php
spl_autoload_register(function ($class) {
    $prefix = 'WP_CLI\Unlikely';
    if (strpos($class, $prefix) === 0) {
        $sep = DIRECTORY_SEPARATOR;
        // strip off "WP_CLI"
        $name = substr($class, 7);
        $fn = str_replace('\\', $sep, $name) . '.php';
        require_once __DIR__ . $sep . $fn;
    }
});
