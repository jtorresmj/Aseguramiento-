<?php

// Simple router for PHP's built-in server used by `php artisan serve`.
// Routes existing files directly and forwards all other requests to
// the framework entry point at public/index.php.

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

$publicPath = __DIR__.'/public';

// If the requested URI is a real file under public/, let the server serve it.
if ($uri !== '/' && file_exists($publicPath.$uri) && is_file($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
