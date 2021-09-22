<?php
spl_autoload_register(function ($class) {
    $prefix = 'WP_CLI\Unlikely';
    if (strpos($class, $prefix) === 0) {
        $sep = DIRECTORY_SEPARATOR;
        // strip off "WP_CLI"
        $name = substr($class, strlen($prefix));
        $fn = str_replace('\\', $sep, $name) . '.php';
        $fn = __DIR__ . $sep . 'src/Unlikely' . $sep . $fn;
        $fn = str_replace($sep . $sep, $sep, $fn);
        require_once $fn;
    }
});
